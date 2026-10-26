<?php

namespace App\Services;

use App\Models\AiAssistantAttachment;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;
use ZipArchive;

class AiLiquidationExcelDraftService
{
    private const MAX_BYTES = 10_485_760;

    private const MAX_SHEETS = 10;

    private const MAX_ROWS = 5_000;

    private const MAX_COLUMNS = 50;

    /** @var array<string, array<int, string>> */
    private const HEADERS = [
        'item_date' => ['date', 'expense date', 'transaction date', 'purchase date', 'item date'],
        'payee' => ['payee', 'vendor', 'supplier', 'merchant', 'paid to'],
        'expense_type' => ['expense type', 'expense category', 'category', 'classification', 'expense'],
        'account_buyer' => ['account', 'buyer', 'account buyer', 'account/buyer', 'charged to', 'customer'],
        'amount_vnd' => ['amount vnd', 'amount (vnd)', 'vnd amount', 'expense amount vnd', 'amount', 'total amount'],
        'remarks' => ['remarks', 'description', 'particulars', 'details', 'notes'],
        'requested_by' => ['requested by', 'requester', 'employee', 'prepared by'],
    ];

    /** @var array<int, string> */
    private const EXPENSE_TYPES = [
        'Transportation and Travel', 'HO - Transportation and Travel', 'RND Expense', 'Courier fees', 'Utilities',
        'Communication', 'HO - Communication', 'Entertainment Expense', 'Meal Employee', 'Repairs & Maintenance',
        'Outsourcing services', 'Advertising & Promotions', 'Stationery & office supplies',
    ];

    /** @return array<string, mixed> */
    public function inspect(UploadedFile $file, string $defaultRequestedBy): array
    {
        if (($file->getSize() ?? 0) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'The workbook must be 10 MB or smaller.']);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $readerType = match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            default => null,
        };
        if ($readerType === null) {
            throw ValidationException::withMessages(['file' => 'Upload an .xlsx, .xls, or .csv file.']);
        }

        try {
            $path = $file->getRealPath();
            if (IOFactory::identify($path) !== $readerType) {
                throw ValidationException::withMessages(['file' => 'The file contents do not match its Excel or CSV extension.']);
            }
            $reader = IOFactory::createReader($readerType);
            $reader->setReadDataOnly(true);
            $worksheetInfo = $reader->listWorksheetInfo($path);
            if (count($worksheetInfo) > self::MAX_SHEETS) {
                throw ValidationException::withMessages(['file' => 'The workbook is limited to 10 worksheets.']);
            }
            foreach ($worksheetInfo as $sheetInfo) {
                if (($sheetInfo['totalRows'] ?? 0) > self::MAX_ROWS || ($sheetInfo['totalColumns'] ?? 0) > self::MAX_COLUMNS) {
                    throw ValidationException::withMessages(['file' => 'Each worksheet is limited to 5,000 rows and 50 columns.']);
                }
            }
            $sheetNames = $reader->listWorksheetNames($path);
            $reader->setLoadSheetsOnly($sheetNames);
            $spreadsheet = $reader->load($path);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'The workbook could not be read. Check that it is a valid, unencrypted Excel or CSV file.']);
        }

        $rows = [];
        $warnings = [];
        $selectedWorksheets = [];
        $signatures = [];
        try {
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $highestRow = min($worksheet->getHighestDataRow(), self::MAX_ROWS);
                $highestColumn = min(Coordinate::columnIndexFromString($worksheet->getHighestDataColumn()), self::MAX_COLUMNS);
                $header = $this->findHeader($worksheet, $highestRow, $highestColumn);
                if ($header === null) {
                    continue;
                }
                [$headerRow, $columns] = $header;
                $selectedWorksheets[] = $worksheet->getTitle();
                if (! isset($columns['item_date'], $columns['amount_vnd'])) {
                    $warnings[] = $worksheet->getTitle().': Date and Amount columns are required to identify expense rows.';

                    continue;
                }
                if ($this->headerText($worksheet->getCell([$columns['amount_vnd'], $headerRow])->getValue()) === 'amount') {
                    $warnings[] = $worksheet->getTitle().': a generic Amount column is treated as VND for form prefill. Verify its currency before saving.';
                }

                for ($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++) {
                    $values = [];
                    $containsFormula = false;
                    foreach ($columns as $field => $column) {
                        $cell = $worksheet->getCell([$column, $rowNumber]);
                        $value = $cell->getValue();
                        if ($cell->getDataType() === DataType::TYPE_FORMULA || (is_string($value) && str_starts_with(ltrim($value), '='))) {
                            $containsFormula = true;
                            $value = null;
                        }
                        $values[$field] = is_string($value) ? trim($value) : $value;
                    }
                    if ($containsFormula) {
                        $warnings[] = $worksheet->getTitle().' row '.$rowNumber.': formula cells were not evaluated; review missing values before saving.';
                    }
                    if (collect($values)->every(fn (mixed $value): bool => $value === null || $value === '')) {
                        continue;
                    }
                    $allRowValues = strtolower(implode(' ', array_map('strval', $values)));
                    if (preg_match('/\b(?:grand\s+)?(?:sub\s*)?total\b/i', $allRowValues) === 1) {
                        $warnings[] = $worksheet->getTitle().' row '.$rowNumber.': total row skipped.';

                        continue;
                    }

                    $date = $this->normalizeDate($values['item_date'] ?? null);
                    $amount = $this->normalizeAmount($values['amount_vnd'] ?? null);
                    $type = $this->normalizeExpenseType($values['expense_type'] ?? null);
                    $item = [
                        'item_date' => $date,
                        'requested_by' => $this->text($values['requested_by'] ?? null, 255) ?? $defaultRequestedBy,
                        'payee' => $this->text($values['payee'] ?? null, 255),
                        'expense_type' => $type,
                        'account_buyer' => $this->text($values['account_buyer'] ?? null, 255),
                        'amount_vnd' => $amount,
                        'remarks' => $this->text($values['remarks'] ?? null, 5000),
                    ];
                    $missing = [];
                    foreach (['item_date' => 'date', 'payee' => 'payee/vendor', 'expense_type' => 'recognized expense category', 'account_buyer' => 'account/buyer', 'amount_vnd' => 'positive amount'] as $field => $label) {
                        if ($item[$field] === null || $item[$field] === '') {
                            $missing[] = $label;
                        }
                    }
                    $item['source'] = ['worksheet' => $worksheet->getTitle(), 'row' => $rowNumber];
                    $item['missing'] = $missing;
                    $signature = hash('sha256', json_encode([
                        $item['item_date'], mb_strtolower((string) $item['payee']), $item['expense_type'],
                        mb_strtolower((string) $item['account_buyer']), $item['amount_vnd'], mb_strtolower((string) $item['remarks']),
                    ], JSON_THROW_ON_ERROR));
                    $item['duplicate'] = isset($signatures[$signature]);
                    if ($item['duplicate']) {
                        $item['duplicate_of'] = $signatures[$signature];
                    } else {
                        $signatures[$signature] = count($rows) + 1;
                    }
                    if ($missing !== []) {
                        $warnings[] = $worksheet->getTitle().' row '.$rowNumber.': missing or invalid '.implode(', ', $missing).'.';
                    }
                    $rows[] = $item;
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'No expense rows were found. Include a header row with Date and Amount columns, followed by expense data.']);
        }
        $total = MiFinancialAmount::sum(array_filter(array_column($rows, 'amount_vnd'), fn (mixed $amount): bool => is_string($amount)), 'items', '999999999999.99');

        return [
            'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'worksheets' => array_values(array_unique($selectedWorksheets)),
            'item_count' => count($rows),
            'missing_required_count' => collect($rows)->sum(fn (array $item): int => count($item['missing'])),
            'total_vnd' => $total,
            'rows' => $rows,
            'warnings' => array_values(array_unique($warnings)),
            'duplicate_count' => collect($rows)->where('duplicate', true)->count(),
            'date_prepared' => now()->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    public function inspectAttachment(UploadedFile $file, string $defaultRequestedBy): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return [
                ...$this->inspect($file, $defaultRequestedBy),
                'kind' => 'workbook',
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        }

        if (($file->getSize() ?? 0) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'Attachments must be 10 MB or smaller.']);
        }

        $text = match ($extension) {
            'txt' => $this->readTextFile($file),
            'docx' => $this->readWordDocument($file),
            'pdf' => $this->validatePdf($file),
            default => throw ValidationException::withMessages(['file' => 'Upload an .xlsx, .xls, .csv, .pdf, .docx, or .txt file.']),
        };

        $kind = $extension === 'pdf' ? 'pdf' : 'document';

        return [
            'kind' => $kind,
            'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extracted_text' => $text,
            'text_truncated' => $text !== null && mb_strlen($text) >= 50_000,
            'worksheets' => [],
            'item_count' => 0,
            'missing_required_count' => 0,
            'total_vnd' => '0.00',
            'rows' => [],
            'warnings' => $kind === 'pdf' ? ['PDF content is sent securely to the configured AI model for reading; verify all extracted details.'] : [],
            'duplicate_count' => 0,
            'date_prepared' => now()->toDateString(),
        ];
    }

    private function readTextFile(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());
        if (! is_string($contents) || ! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            throw ValidationException::withMessages(['file' => 'The TXT file must contain readable UTF-8 text.']);
        }

        $text = trim($contents);
        if ($text === '') {
            throw ValidationException::withMessages(['file' => 'The TXT file does not contain readable text.']);
        }

        return mb_substr($text, 0, 50_000);
    }

    private function readWordDocument(UploadedFile $file): string
    {
        $archive = new ZipArchive;
        if ($archive->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'The DOCX file could not be opened.']);
        }

        try {
            if ($archive->numFiles > 500 || $archive->locateName('word/document.xml') === false) {
                throw ValidationException::withMessages(['file' => 'The DOCX file is invalid or contains too many parts.']);
            }

            $uncompressedBytes = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);
                $uncompressedBytes += (int) ($stat['size'] ?? 0);
                if ($uncompressedBytes > 30_000_000) {
                    throw ValidationException::withMessages(['file' => 'The DOCX contents exceed the 30 MB expanded-size limit.']);
                }
            }

            $xml = $archive->getFromName('word/document.xml');
            if (! is_string($xml) || strlen($xml) > 10_000_000) {
                throw ValidationException::withMessages(['file' => 'The DOCX document content is missing or too large.']);
            }
        } finally {
            $archive->close();
        }

        $document = new \DOMDocument;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw ValidationException::withMessages(['file' => 'The DOCX document content is malformed.']);
        }
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = [];
        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            $paragraphs[] = $paragraph->textContent;
        }
        $text = trim(implode("\n", $paragraphs));
        if ($text === '') {
            throw ValidationException::withMessages(['file' => 'No readable text was found in the DOCX document.']);
        }

        return mb_substr($text, 0, 50_000);
    }

    private function validatePdf(UploadedFile $file): ?string
    {
        $header = file_get_contents($file->getRealPath(), false, null, 0, 1024);
        if (! is_string($header) || preg_match('/%PDF-[0-9.]+/', $header) !== 1) {
            throw ValidationException::withMessages(['file' => 'The PDF file signature is invalid.']);
        }

        return null;
    }

    public function purgeExpired(): int
    {
        $expired = AiAssistantAttachment::query()->where('expires_at', '<=', now())->get();
        foreach ($expired as $attachment) {
            DB::table('ai_assistant_attachment_audits')->insert([
                'attachment_id' => $attachment->getKey(), 'actor_user_id' => $attachment->user_id,
                'company_id' => $attachment->company_id, 'conversation_id' => $attachment->conversation_id,
                'event' => 'expired', 'item_count' => (int) ($attachment->parsed_data['item_count'] ?? 0),
                'file_sha256' => $attachment->sha256, 'created_at' => now(),
            ]);
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        }

        return $expired->count();
    }

    public function forget(AiAssistantAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    /** @return array{0: int, 1: array<string, int>}|null */
    private function findHeader(object $worksheet, int $highestRow, int $highestColumn): ?array
    {
        $best = null;
        $bestScore = 0;
        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $columns = [];
            for ($column = 1; $column <= $highestColumn; $column++) {
                $header = $this->headerText($worksheet->getCell([$column, $row])->getValue());
                foreach (self::HEADERS as $field => $aliases) {
                    if ($header !== '' && in_array($header, $aliases, true)) {
                        $columns[$field] = $column;
                        break;
                    }
                }
            }
            $score = count($columns);
            if ($score > $bestScore && isset($columns['item_date'], $columns['amount_vnd'])) {
                $best = [$row, $columns];
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function headerText(mixed $value): string
    {
        return preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower(trim((string) $value))) ?: '';
    }

    private function text(mixed $value, int $limit): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, $limit);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }
            foreach (['Y-m-d', 'm/d/Y', 'd/m/Y', 'm-d-Y', 'd-m-Y', 'd-M-Y', 'M d, Y', 'd F Y'] as $format) {
                $date = \DateTimeImmutable::createFromFormat('!'.$format, trim((string) $value));
                $errors = \DateTimeImmutable::getLastErrors();
                if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return $date->format('Y-m-d');
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function normalizeAmount(mixed $value): ?string
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if (! is_string($normalized) || ! is_numeric($normalized) || (float) $normalized <= 0) {
            return null;
        }
        try {
            return (string) BigDecimal::of($normalized)->toScale(2, RoundingMode::HALF_UP);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeExpenseType(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $normalized = $this->headerText($value);
        foreach (self::EXPENSE_TYPES as $expenseType) {
            if ($normalized === $this->headerText($expenseType)) {
                return $expenseType;
            }
        }

        return null;
    }
}
