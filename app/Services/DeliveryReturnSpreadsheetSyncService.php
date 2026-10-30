<?php

namespace App\Services;

use App\Exceptions\GoogleSheetsApiException;
use App\Models\DeliveryReturn;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryReturnSpreadsheetSyncService
{
    public function sync(DeliveryReturn $deliveryReturn, GoogleSheetsApiClient $sheets, GoogleSheetsBillingConfiguration $configuration, bool $includeReceivedDate = false): ?bool
    {
        $configurationStatus = $sheets->configurationStatus();
        if (! $configurationStatus['configured']) {
            Log::warning('Delivery return was recorded without a spreadsheet date sync.', [
                'return_id' => $deliveryReturn->return_id,
                'reason' => $configurationStatus['reason'],
            ]);

            return null;
        }

        $configuration = $configuration->current();
        $quotedTab = "'".str_replace("'", "''", $configuration['sheet_tab'])."'!";

        try {
            $rows = $sheets->values($quotedTab.'A:CU', $configuration['spreadsheet_id']);
            $header = $this->dateColumns($rows);
            if ($header === null) {
                throw new GoogleSheetsApiException('Returned Date and Received Date columns could not be identified uniquely.');
            }

            $schoolColumns = [];
            $drColumns = [];
            foreach ($rows[$header['row']] as $column => $heading) {
                $normalized = $this->normalizedHeader($heading);
                if ($normalized === 'schoolid') {
                    $schoolColumns[] = $column;
                }
                if ($normalized === 'birdr') {
                    $drColumns[] = $column;
                }
            }
            if (count($schoolColumns) !== 1 || $drColumns === []) {
                throw new GoogleSheetsApiException('School ID and BIR DR columns could not be identified uniquely.');
            }

            $matches = [];
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $header['row'] || trim((string) ($row[$schoolColumns[0]] ?? '')) !== trim((string) $deliveryReturn->school_id)) {
                    continue;
                }
                foreach ($drColumns as $drColumn) {
                    if (trim((string) ($row[$drColumn] ?? '')) === trim((string) $deliveryReturn->dr_no)) {
                        $matches[] = $rowIndex + 1;
                        break;
                    }
                }
            }

            if (count($matches) !== 1) {
                throw new GoogleSheetsApiException('The spreadsheet must contain exactly one row matching this School ID and BIR DR.');
            }

            $rowNumber = $matches[0];
            $updates = [[
                'range' => $quotedTab.$this->columnName($header['returned_date_column']).$rowNumber,
                'majorDimension' => 'ROWS',
                'values' => [[$deliveryReturn->monitoring_date->format('Y-m-d')]],
            ]];
            if ($includeReceivedDate && $deliveryReturn->received_date !== null) {
                $updates[] = [
                    'range' => $quotedTab.$this->columnName($header['received_date_column']).$rowNumber,
                    'majorDimension' => 'ROWS',
                    'values' => [[$deliveryReturn->received_date->format('Y-m-d')]],
                ];
            }
            $sheets->updateCells($updates, $configuration['spreadsheet_id']);

            return true;
        } catch (Throwable $exception) {
            Log::error('Delivery return spreadsheet date sync failed.', [
                'return_id' => $deliveryReturn->return_id,
                'school_id' => $deliveryReturn->school_id,
                'dr_no' => $deliveryReturn->dr_no,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /** @param array<int, array<int, mixed>> $rows
     * @return array{row: int, returned_date_column: int, received_date_column: int}|null
     */
    private function dateColumns(array $rows): ?array
    {
        $candidates = [];
        foreach (array_slice($rows, 0, 20, true) as $rowIndex => $row) {
            $returnedDateColumns = [];
            $receivedDateColumns = [];
            foreach ($row as $column => $heading) {
                $normalized = $this->normalizedHeader($heading);
                if ($normalized === 'returneddate') {
                    $returnedDateColumns[] = $column;
                }
                if ($normalized === 'receiveddate') {
                    $receivedDateColumns[] = $column;
                }
            }
            if (count($returnedDateColumns) === 1 && count($receivedDateColumns) === 1) {
                $candidates[] = [
                    'row' => $rowIndex,
                    'returned_date_column' => $returnedDateColumns[0],
                    'received_date_column' => $receivedDateColumns[0],
                ];
            }
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function normalizedHeader(mixed $value): string
    {
        return preg_replace('/[^a-z0-9]/u', '', mb_strtolower(trim((string) $value))) ?? '';
    }

    private function columnName(int $columnIndex): string
    {
        $column = '';
        for ($number = $columnIndex + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $column = chr(65 + (($number - 1) % 26)).$column;
        }

        return $column;
    }
}
