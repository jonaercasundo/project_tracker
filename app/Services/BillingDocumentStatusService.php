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
            ->select(['d.dr_no', 'd.school_id', 'd.status', 'l.lot_name'])
            ->get();

        $projectDrsByLot = array_fill_keys(self::LOTS, []);
        $projectSchoolsByLot = array_fill_keys(self::LOTS, []);
        foreach ($deliveries as $delivery) {
            if (in_array(mb_strtolower(trim((string) $delivery->status)), self::EXCLUDED_DELIVERY_STATUSES, true)) {
                continue;
            }

            $lotNumber = $this->lotNumber((string) $delivery->lot_name);
            if ($lotNumber === null || ! isset($projectDrsByLot[$lotNumber])) {
                continue;
            }

            $drNumber = $this->normalizeDrNumber((string) $delivery->dr_no);
            $schoolId = $this->normalizeSchoolId((string) $delivery->school_id);
            if ($drNumber !== '' && $schoolId !== '') {
                $projectDrsByLot[$lotNumber][$drNumber][$schoolId] = true;
                $projectSchoolsByLot[$lotNumber][$schoolId] = true;
            }
        }

        try {
            $rowsByLot = [];

            foreach (self::LOTS as $lotNumber) {
                $rowsByLot[$lotNumber] = $this->lotSheetRows($lotNumber);
            }

            $schoolRows = [];
            foreach (self::LOTS as $lotNumber) {
                foreach ($rowsByLot[$lotNumber] as $row) {
                    $schoolId = $this->normalizeSchoolId($row['school_id']);
                    if ($schoolId === '' || ! isset($projectSchoolsByLot[$lotNumber][$schoolId])) {
                        continue;
                    }

                    $schoolRows[$schoolId] ??= ['lots' => [], 'dates' => [], 'has_project_dr' => false, 'has_sap_date_today' => false];

                    if (trim($row['package']) !== '') {
                        $schoolRows[$schoolId]['lots'][$lotNumber] = true;
                        $encodedDate = trim($row['encoded_date']);
                        if ($encodedDate !== '') {
                            $schoolRows[$schoolId]['dates'][$lotNumber] ??= $encodedDate;
                            if ($this->dateInApplicationTimezone($encodedDate) === now(config('app.timezone'))->toDateString()) {
                                $schoolRows[$schoolId]['has_sap_date_today'] = true;
                            }
                        }
                    }

                    $drNumber = $this->normalizeDrNumber($row['dr']);
                    if ($drNumber !== '' && isset($projectDrsByLot[$lotNumber][$drNumber][$schoolId])) {
                        $schoolRows[$schoolId]['has_project_dr'] = true;
                    }
                }
            }

            $applicableSchools = [];
            $processedSchools = [];
            $processedTodaySchools = [];
            foreach ($schoolRows as $schoolId => $schoolRow) {
                if (! $schoolRow['has_project_dr']) {
                    continue;
                }

                $applicableLots = array_keys($schoolRow['lots']);
                if ($applicableLots === []) {
                    continue;
                }

                $applicableSchools[$schoolId] = true;
                if ($schoolRow['has_sap_date_today']) {
                    $processedTodaySchools[$schoolId] = true;
                }

                $isFullyEncoded = true;
                foreach ($applicableLots as $lotNumber) {
                    $encodedDate = $schoolRow['dates'][$lotNumber] ?? null;
                    if ($encodedDate === null) {
                        $isFullyEncoded = false;

                        continue;
                    }
                }

                if (! $isFullyEncoded) {
                    continue;
                }

                $processedSchools[$schoolId] = true;
            }

            $total = count($applicableSchools);
            $processed = count($processedSchools);

            return [
                'total' => $total,
                'processed' => $processed,
                'remaining' => max(0, $total - $processed),
                'processed_today' => count($processedTodaySchools),
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
        $timezone = config('app.timezone');
        $formats = match (true) {
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 => ['!Y-m-d'],
            preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value) === 1 => ['!m/d/Y', '!d/m/Y'],
            preg_match('/^\d{1,2}[-\/]\p{L}{3}[-\/]\d{4}$/u', $value) === 1 => ['!d-M-Y', '!d/M/Y'],
            default => [],
        };

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value, $timezone);
                if ($date !== false) {
                    return $date->setTimezone($timezone)->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return CarbonImmutable::parse($value, $timezone)
                ->setTimezone($timezone)
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

    private function normalizeSchoolId(string $schoolId): string
    {
        return mb_strtolower(trim($schoolId));
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
