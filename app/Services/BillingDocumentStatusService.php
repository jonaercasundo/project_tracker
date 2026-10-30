<?php

namespace App\Services;

use App\Exceptions\GoogleSheetsApiException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingDocumentStatusService
{
    private const LOTS = ['2', '9', '11'];

    private const EXCLUDED_DELIVERY_STATUSES = ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected'];

    public function __construct(private GoogleSheetsBillingConfiguration $configuration, private GoogleSheetsApiClient $sheets) {}

    /** @return array{total: ?int, processed: ?int, remaining: ?int, processed_today: ?int, source_error: ?string} */
    public function summarize(int $projectId): array
    {
        $deliveries = DB::table('deliveries as d')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->where('d.project_id', $projectId)
            ->whereNotNull('d.dr_no')
            ->select(['d.dr_no', 'd.status', 'l.lot_name'])
            ->get();

        $projectDrsByLot = array_fill_keys(self::LOTS, []);
        foreach ($deliveries as $delivery) {
            if (in_array(mb_strtolower(trim((string) $delivery->status)), self::EXCLUDED_DELIVERY_STATUSES, true)) {
                continue;
            }

            $lotNumber = $this->lotNumber((string) $delivery->lot_name);
            if ($lotNumber === null || ! isset($projectDrsByLot[$lotNumber])) {
                continue;
            }

            $drNumber = $this->normalizeDrNumber((string) $delivery->dr_no);
            if ($drNumber !== '') {
                $projectDrsByLot[$lotNumber][$drNumber] = true;
            }
        }

        try {
            $applicableDrs = [];
            $processedDrs = [];
            $processedTodayDrs = [];

            foreach (self::LOTS as $lotNumber) {
                $rows = $this->lotSheetRows($lotNumber);
                $projectDrs = $projectDrsByLot[$lotNumber];

                foreach ($rows as $row) {
                    $drNumber = $this->normalizeDrNumber((string) ($row['dr'] ?? ''));
                    if ($drNumber === '' || ! isset($projectDrs[$drNumber])) {
                        continue;
                    }

                    $applicableDrs[$drNumber] = true;
                    $receivedDate = trim((string) ($row['received'] ?? ''));
                    if ($receivedDate === '') {
                        continue;
                    }

                    $processedDrs[$drNumber] = true;
                    if ($this->dateInApplicationTimezone($receivedDate) === now(config('app.timezone'))->toDateString()) {
                        $processedTodayDrs[$drNumber] = true;
                    }
                }
            }

            $processed = count($processedDrs);
            $total = count($applicableDrs);

            return [
                'total' => $total,
                'processed' => $processed,
                'remaining' => max(0, $total - $processed),
                'processed_today' => count($processedTodayDrs),
                'source_error' => null,
            ];
        } catch (GoogleSheetsApiException|RuntimeException $exception) {
            return [
                'total' => null,
                'processed' => null,
                'remaining' => null,
                'processed_today' => null,
                'source_error' => $exception->getMessage(),
            ];
        }
    }

    /** @return array<int, array{dr: string, received: string}> */
    private function lotSheetRows(string $lotNumber): array
    {
        $settings = $this->configuration->current();
        $cacheKey = 'billing-document-status:'.hash('sha256', $settings['spreadsheet_id'].'|'.$settings['sheet_tab'].'|'.$lotNumber);

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($settings, $lotNumber): array {
            $tab = "'".str_replace("'", "''", $settings['sheet_tab'])."'!";
            $headers = $this->sheets->values($tab.'A1:CU20', $settings['spreadsheet_id']);
            $headerMap = $this->findLotColumns($headers, $lotNumber);
            $drValues = $this->sheets->values($tab.$headerMap['dr_column'].'2:'.$headerMap['dr_column'], $settings['spreadsheet_id']);
            $receivedValues = $this->sheets->values($tab.$headerMap['received_column'].'2:'.$headerMap['received_column'], $settings['spreadsheet_id']);
            $rows = [];
            $rowCount = max(count($drValues), count($receivedValues));

            for ($index = 0; $index < $rowCount; $index++) {
                $rows[] = [
                    'dr' => (string) ($drValues[$index][0] ?? ''),
                    'received' => (string) ($receivedValues[$index][0] ?? ''),
                ];
            }

            return $rows;
        });
    }

    /** @param array<int, array<int, mixed>> $headerRows
     * @return array{dr_column: string, received_column: string}
     */
    private function findLotColumns(array $headerRows, string $lotNumber): array
    {
        foreach ($headerRows as $headerRow) {
            $drColumns = [];
            $receivedColumn = null;

            foreach ($headerRow as $index => $value) {
                $header = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)) ?? '');
                if ($header === 'bir dr') {
                    $drColumns[] = $this->columnLetters($index + 1);
                }
                if (str_contains($header, 'date received') && str_contains($header, 'lot '.$lotNumber) && ! str_contains($header, 'from 3pl')) {
                    $receivedColumn = $this->columnLetters($index + 1);
                }
            }

            $lotIndex = array_search($lotNumber, self::LOTS, true);
            if ($lotIndex !== false && isset($drColumns[$lotIndex]) && $receivedColumn !== null) {
                return ['dr_column' => $drColumns[$lotIndex], 'received_column' => $receivedColumn];
            }
        }

        throw new RuntimeException('The BIR DR and DATE RECEIVED columns for a supported lot could not be found in the configured billing sheet.');
    }

    private function dateInApplicationTimezone(string $value): ?string
    {
        try {
            return CarbonImmutable::parse($value, config('app.timezone'))
                ->setTimezone(config('app.timezone'))
                ->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function lotNumber(string $lotName): ?string
    {
        if (preg_match('/\b(?:lot\s*)?(2|9|11)\b/i', trim($lotName), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeDrNumber(string $drNumber): string
    {
        return mb_strtolower(trim($drNumber));
    }

    private function columnLetters(int $columnNumber): string
    {
        $letters = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $letters;
    }
}
