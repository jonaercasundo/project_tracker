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
            $rowsByLot = [];

            foreach (self::LOTS as $lotNumber) {
                $rowsByLot[$lotNumber] = $this->lotSheetRows($lotNumber);
                foreach ($rowsByLot[$lotNumber] as $row) {
                    $drNumber = $this->normalizeDrNumber($row['dr']);
                    if ($drNumber !== '' && isset($projectDrsByLot[$lotNumber][$drNumber])) {
                        $applicableDrs[$drNumber] = true;
                    }
                }
            }

            $schoolRows = [];
            $rowCount = max(array_map('count', $rowsByLot));
            for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
                $schoolId = '';
                foreach (self::LOTS as $lotNumber) {
                    $schoolId = trim((string) ($rowsByLot[$lotNumber][$rowIndex]['school_id'] ?? ''));
                    if ($schoolId !== '') {
                        break;
                    }
                }

                $schoolKey = $schoolId !== '' ? 'school:'.$schoolId : 'row:'.$rowIndex;
                $schoolRows[$schoolKey] ??= ['lots' => [], 'dates' => [], 'drs' => []];

                foreach (self::LOTS as $lotNumber) {
                    $row = $rowsByLot[$lotNumber][$rowIndex] ?? null;
                    if ($row === null) {
                        continue;
                    }

                    if (trim($row['package']) !== '') {
                        $schoolRows[$schoolKey]['lots'][$lotNumber] = true;
                    }

                    $encodedDate = trim($row['encoded_date']);
                    if ($encodedDate !== '') {
                        $schoolRows[$schoolKey]['dates'][$lotNumber] ??= $encodedDate;
                    }

                    $drNumber = $this->normalizeDrNumber($row['dr']);
                    if ($drNumber !== '' && isset($projectDrsByLot[$lotNumber][$drNumber])) {
                        $schoolRows[$schoolKey]['drs'][$lotNumber][$drNumber] = true;
                    }
                }
            }

            foreach ($schoolRows as $schoolRow) {
                $applicableLots = array_keys($schoolRow['lots']);
                if ($applicableLots === []) {
                    continue;
                }

                $isFullyEncoded = true;
                $wereAllDatesEnteredToday = true;
                foreach ($applicableLots as $lotNumber) {
                    $encodedDate = $schoolRow['dates'][$lotNumber] ?? null;
                    if ($encodedDate === null) {
                        $isFullyEncoded = false;
                        $wereAllDatesEnteredToday = false;

                        continue;
                    }

                    if ($this->dateInApplicationTimezone($encodedDate) !== now(config('app.timezone'))->toDateString()) {
                        $wereAllDatesEnteredToday = false;
                    }
                }

                if (! $isFullyEncoded) {
                    continue;
                }

                foreach ($schoolRow['drs'] as $lotDrs) {
                    foreach ($lotDrs as $drNumber => $_) {
                        $processedDrs[$drNumber] = true;
                        if ($wereAllDatesEnteredToday) {
                            $processedTodayDrs[$drNumber] = true;
                        }
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

    /** @return array<int, array{school_id: string, dr: string, package: string, encoded_date: string}> */
    private function lotSheetRows(string $lotNumber): array
    {
        $settings = $this->configuration->current();
        $cacheKey = 'billing-document-status:v3:'.hash('sha256', $settings['spreadsheet_id'].'|'.$settings['sheet_tab'].'|'.$lotNumber);

        return Cache::remember($cacheKey, now()->addSeconds(10), function () use ($settings, $lotNumber): array {
            $tab = "'".str_replace("'", "''", $settings['sheet_tab'])."'!";
            $headers = $this->sheets->values($tab.'A1:CU20', $settings['spreadsheet_id']);
            $headerMap = $this->findLotColumns($headers, $lotNumber);
            $schoolIdValues = $this->sheets->values($tab.$headerMap['school_id_column'].'2:'.$headerMap['school_id_column'], $settings['spreadsheet_id']);
            $drValues = $this->sheets->values($tab.$headerMap['dr_column'].'2:'.$headerMap['dr_column'], $settings['spreadsheet_id']);
            $packageValues = $this->sheets->values($tab.$headerMap['package_column'].'2:'.$headerMap['package_column'], $settings['spreadsheet_id']);
            $encodedDateValues = $this->sheets->values($tab.$headerMap['encoded_date_column'].'2:'.$headerMap['encoded_date_column'], $settings['spreadsheet_id']);
            $rows = [];
            $rowCount = max(count($schoolIdValues), count($drValues), count($packageValues), count($encodedDateValues));

            for ($index = 0; $index < $rowCount; $index++) {
                $rows[] = [
                    'school_id' => (string) ($schoolIdValues[$index][0] ?? ''),
                    'dr' => (string) ($drValues[$index][0] ?? ''),
                    'package' => (string) ($packageValues[$index][0] ?? ''),
                    'encoded_date' => (string) ($encodedDateValues[$index][0] ?? ''),
                ];
            }

            return $rows;
        });
    }

    /** @param array<int, array<int, mixed>> $headerRows
     * @return array{school_id_column: string, dr_column: string, package_column: string, encoded_date_column: string}
     */
    private function findLotColumns(array $headerRows, string $lotNumber): array
    {
        foreach ($headerRows as $headerRow) {
            $drColumns = [];
            $schoolIdColumn = null;
            $packageColumn = null;
            $encodedDateColumn = null;

            foreach ($headerRow as $index => $value) {
                $header = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)) ?? '');
                if ($header === 'schoolid') {
                    $schoolIdColumn = $this->columnLetters($index + 1);
                }
                if ($header === 'bir dr') {
                    $drColumns[] = $this->columnLetters($index + 1);
                }
                if (preg_match('/^core package code lot\s*'.preg_quote($lotNumber, '/').'$/', $header) === 1) {
                    $packageColumn = $this->columnLetters($index + 1);
                }
                if (str_contains($header, 'date encoded in sap') && str_contains($header, 'lot '.$lotNumber)) {
                    $encodedDateColumn = $this->columnLetters($index + 1);
                }
            }

            $lotIndex = array_search($lotNumber, self::LOTS, true);
            if ($schoolIdColumn !== null && $lotIndex !== false && isset($drColumns[$lotIndex]) && $packageColumn !== null && $encodedDateColumn !== null) {
                return [
                    'school_id_column' => $schoolIdColumn,
                    'dr_column' => $drColumns[$lotIndex],
                    'package_column' => $packageColumn,
                    'encoded_date_column' => $encodedDateColumn,
                ];
            }
        }

        throw new RuntimeException('The school, BIR DR, Core Package Code, and DATE ENCODED IN SAP columns for a supported lot could not be found in the configured billing sheet.');
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
