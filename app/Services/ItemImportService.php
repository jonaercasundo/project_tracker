<?php

namespace App\Services;

use App\Http\Requests\SaveCatalogItemRequest;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;
use Throwable;
use ZipArchive;

class ItemImportService
{
    public const HEADERS = [
        'code_prefix', 'item_name', 'description', 'unit', 'price', 'supplier_price', 'active',
    ];

    private const MAX_ROWS = 1000;

    /** @return array{token: string, filename: string, rows: array, summary: array, expires_at: string} */
    public function preview(UploadedFile $file, Request $request): array
    {
        try {
            $sheet = strtolower($file->getClientOriginalExtension()) === 'csv'
                ? $this->readCsv($file->getRealPath())
                : $this->readWorkbook($file->getRealPath());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The file could not be read. Use a UTF-8 CSV or a single-sheet XLSX workbook.']);
        }

        $headers = array_map(fn (mixed $value): string => trim((string) $value), array_shift($sheet) ?? []);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        if (count($headers) !== count(self::HEADERS) || count(array_unique($headers)) !== count(self::HEADERS)
            || array_diff(self::HEADERS, $headers) !== []) {
            throw ValidationException::withMessages(['file' => 'The first row must contain exactly the 7 column headers from the CSV template.']);
        }

        $rows = [];
        foreach ($sheet as $index => $cells) {
            if (count(array_filter($cells, fn (mixed $value): bool => $value !== null && trim((string) $value) !== '')) === 0) {
                continue;
            }

            $errors = count($cells) > count($headers) ? ['This row contains more than 7 columns.'] : [];
            $data = [];
            foreach ($headers as $column => $header) {
                $value = trim((string) ($cells[$column] ?? ''));
                if (! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                    $errors[] = 'Use UTF-8 text without null bytes.';
                    $value = mb_convert_encoding(str_replace("\0", '', $value), 'UTF-8', 'UTF-8');
                }
                if (str_starts_with($value, '=')) {
                    $errors[] = 'Use plain values instead of spreadsheet formulas.';
                }
                $data[$header] = $value === '' ? null : $value;
            }

            if (is_string($data['code_prefix'])) {
                $data['code_prefix'] = trim($data['code_prefix']) === ''
                    ? CatalogItem::DEFAULT_CODE_PREFIX
                    : mb_strtoupper(trim($data['code_prefix']));
            } else {
                $data['code_prefix'] = CatalogItem::DEFAULT_CODE_PREFIX;
            }
            $validator = Validator::make($data, SaveCatalogItemRequest::itemRules());
            $errors = array_values(array_unique(array_merge($errors, $validator->errors()->all())));
            if ($errors === []) {
                $data['price'] = number_format((float) $data['price'], 2, '.', '');
                $data['supplier_price'] = number_format((float) $data['supplier_price'], 2, '.', '');
                $data['active'] = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
            $rows[] = [
                'row' => $index + 2,
                'data' => $data,
                'status' => $errors === [] ? 'ready' : 'failed',
                'errors' => $errors,
            ];
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file contains no item rows.']);
        }

        $existingKeys = $this->existingKeys();
        $seen = [];
        foreach ($rows as &$row) {
            if ($row['status'] !== 'ready') {
                continue;
            }

            $key = $this->comparisonKey($row['data']);
            if (isset($seen[$key])) {
                $row['status'] = 'duplicate';
                $row['errors'] = ['This item duplicates row '.$seen[$key].' in this file.'];
            } elseif (isset($existingKeys[$key])) {
                $row['status'] = 'duplicate';
                $row['errors'] = ['An identical item already exists. Existing items will not be changed.'];
            }
            $seen[$key] ??= $row['row'];
        }
        unset($row);

        $token = (string) Str::uuid();
        $preview = [
            'token' => $token,
            'filename' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 200),
            'rows' => $rows,
            'summary' => $this->summary($rows),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
        $this->store('preview', $token, ['owner' => $this->owner($request), 'preview' => $preview]);

        return $preview;
    }

    /** @return array{token: string, filename: string, rows: array, summary: array, rolled_back: bool} */
    public function confirm(string $token, Request $request): array
    {
        $cache = Cache::store('file');
        $tokenLock = $cache->lock('catalog-item-import:confirm:'.$token, 120);
        if (! $tokenLock->get()) {
            throw ValidationException::withMessages(['token' => 'This import is already being processed.']);
        }

        try {
            $draft = $cache->get('catalog-item-import:preview:'.$token);
            if (! is_array($draft) || ! hash_equals($draft['owner'], $this->owner($request))
                || now()->greaterThanOrEqualTo($draft['preview']['expires_at'])) {
                throw ValidationException::withMessages(['token' => 'This preview has expired, was already used, or belongs to another session. Upload the file again.']);
            }
            if (! $cache->forget('catalog-item-import:preview:'.$token)) {
                throw new RuntimeException('Unable to consume the import preview.');
            }

            $rows = $draft['preview']['rows'];
            $rolledBack = false;
            try {
                $rows = DB::transaction(function () use ($rows): array {
                    $existingKeys = $this->existingKeys();

                    foreach ($rows as &$row) {
                        if ($row['status'] !== 'ready') {
                            continue;
                        }

                        $key = $this->comparisonKey($row['data']);
                        if (isset($existingKeys[$key])) {
                            $row['status'] = 'duplicate';
                            $row['errors'] = ['An identical item now exists. The existing item was not changed.'];

                            continue;
                        }

                        CatalogItem::query()->create($row['data']);
                        $row['status'] = 'imported';
                        $existingKeys[$key] = true;
                    }
                    unset($row);

                    return $rows;
                });
            } catch (Throwable $exception) {
                report($exception);
                $rolledBack = true;
                foreach ($rows as &$row) {
                    if ($row['status'] === 'ready' || $row['status'] === 'imported') {
                        $row['status'] = 'failed';
                        $row['errors'] = ['No rows were saved because a database error interrupted this import. Upload the file again to retry.'];
                    }
                }
                unset($row);
            }

            $result = [
                'token' => (string) Str::uuid(),
                'filename' => $draft['preview']['filename'],
                'rows' => $rows,
                'summary' => $this->summary($rows),
                'rolled_back' => $rolledBack,
            ];
            $this->store('result', $result['token'], ['owner' => $this->owner($request), 'result' => $result]);

            return $result;
        } finally {
            $tokenLock->release();
        }
    }

    /** @return array{token: string, filename: string, rows: array, summary: array, rolled_back: bool}|null */
    public function result(Request $request): ?array
    {
        $token = $request->session()->get('import_result_token');
        if (! is_string($token)) {
            return null;
        }

        $result = Cache::store('file')->get('catalog-item-import:result:'.$token);

        return is_array($result) && hash_equals($result['owner'], $this->owner($request)) ? $result['result'] : null;
    }

    /** @return list<list<mixed>> */
    private function readCsv(string $path): array
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open import file.');
        }

        try {
            $rows = [];
            while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $rows[] = $row;
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw ValidationException::withMessages(['file' => 'Import at most 1,000 rows at a time, excluding the header.']);
                }
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    /** @return list<list<mixed>> */
    private function readWorkbook(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'This is not a readable XLSX workbook.']);
        }

        try {
            $expandedSize = 0;
            if ($zip->numFiles > 200) {
                throw ValidationException::withMessages(['file' => 'The workbook is too complex. Export one sheet as CSV.']);
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $expandedSize += $zip->statIndex($index)['size'];
                if ($expandedSize > 20 * 1024 * 1024) {
                    throw ValidationException::withMessages(['file' => 'The expanded workbook exceeds 20 MB. Export one sheet as CSV.']);
                }
            }
        } finally {
            $zip->close();
        }

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $info = $reader->listWorksheetInfo($path);
        if (count($info) !== 1 || $info[0]['totalRows'] > self::MAX_ROWS + 1 || $info[0]['totalColumns'] > count(self::HEADERS)) {
            throw ValidationException::withMessages(['file' => 'Use one worksheet with the 7 template columns and at most 1,000 item rows.']);
        }

        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= 1001 && Coordinate::columnIndexFromString($columnAddress) <= count(ItemImportService::HEADERS);
            }
        });
        $book = $reader->load($path);
        try {
            $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));

            return $book->getSheet(0)->rangeToArray('A1:'.$lastColumn.max(1, $info[0]['totalRows']), null, false, false);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    /** @return array<string, true> */
    private function existingKeys(): array
    {
        $items = CatalogItem::query()->get(self::HEADERS);

        $keys = [];
        foreach ($items as $item) {
            $keys[$this->comparisonKey($item->only(self::HEADERS))] = true;
        }

        return $keys;
    }

    /** @param array<string, mixed> $data */
    private function comparisonKey(array $data): string
    {
        $normalized = [];
        foreach (self::HEADERS as $header) {
            $value = $data[$header] ?? null;
            if (in_array($header, ['price', 'supplier_price'], true)) {
                $normalized[$header] = number_format((float) $value, 2, '.', '');
            } elseif ($header === 'active') {
                $normalized[$header] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } else {
                $normalized[$header] = mb_strtolower(trim((string) $value));
            }
        }

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    /** @param list<array{row: int, data: array, status: string, errors: array}> $rows
     * @return array<string, int>
     */
    private function summary(array $rows): array
    {
        $summary = ['ready' => 0, 'imported' => 0, 'skipped' => 0, 'failed' => 0, 'total' => count($rows)];
        foreach ($rows as $row) {
            $summary[match ($row['status']) {
                'duplicate' => 'skipped',
                default => $row['status'],
            }]++;
        }

        return $summary;
    }

    private function owner(Request $request): string
    {
        return hash('sha256', json_encode([
            $request->user()->getAuthIdentifier(),
            $request->session()->get('company_id'),
            $request->session()->getId(),
        ], JSON_THROW_ON_ERROR));
    }

    /** @param array{owner: string, preview?: array, result?: array} $data */
    private function store(string $kind, string $token, array $data): void
    {
        if (! Cache::store('file')->put('catalog-item-import:'.$kind.':'.$token, $data, now()->addMinutes(15))) {
            throw new RuntimeException('Unable to save the item import preview.');
        }
    }
}
