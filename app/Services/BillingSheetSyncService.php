<?php

namespace App\Services;

use App\Jobs\SyncBillingSheetDate;
use App\Models\BillingSheetSync;
use App\Models\Delivery;
use App\Models\DeliveryBillingHistory;
use App\Models\DeliveryBillingRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingSheetSyncService
{
    /**
     * Queue a sheet reconciliation row for every package in the delivery's lot.
     */
    public function queueForTransition(Delivery $delivery, DeliveryBillingRecord $billingRecord, DeliveryBillingHistory $history): void
    {
        $timezone = (string) config('google_sheets.billing.business_timezone', config('app.timezone', 'Asia/Manila'));
        $transitionAt = $billingRecord->submitted_for_billing_at?->timezone($timezone)
            ?? $history->created_at?->timezone($timezone)
            ?? now($timezone);
        $spreadsheetId = (string) config('google_sheets.billing.spreadsheet_id');
        $tab = (string) config('google_sheets.billing.sheet_tab');
        $packages = DB::table('package_status as package_status')
            ->join('package as package', 'package.package_id', '=', 'package_status.package_id')
            ->leftJoin('lot as lot', 'lot.lot_id', '=', 'package.lot_id')
            ->where('package_status.delivery_id', $delivery->delivery_id)
            ->where('package.lot_id', $delivery->lot_id)
            ->orderBy('package_status.package_status_id')
            ->get([
                'package_status.package_status_id',
                'package_status.package_id',
                'package.package_code',
                'package.lot_id',
                'lot.lot_name',
            ]);

        if ($packages->isEmpty()) {
            $this->createAndDispatch([
                'delivery_billing_history_id' => $history->getKey(),
                'delivery_id' => $delivery->delivery_id,
                'package_status_id' => null,
                'package_id' => null,
                'lot_id' => $delivery->lot_id,
                'lot_number' => null,
                'bir_dr' => $delivery->dr_no,
                'package_code' => null,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_tab' => $tab,
                'target_cell' => null,
                'transition_at' => $transitionAt,
                'transition_date' => $transitionAt->toDateString(),
                'idempotency_key' => hash('sha256', 'billing-history:'.$history->getKey().':no-package'),
                'outcome' => 'reconciliation_error',
                'error_details' => 'No package status rows match the delivery lot.',
            ], false);

            return;
        }

        foreach ($packages as $package) {
            $lotNumber = $this->lotNumber((string) $package->lot_name);
            $cell = $lotNumber === null ? null : $this->targetColumn($lotNumber);
            $supportedLot = $cell !== null;
            $this->createAndDispatch([
                'delivery_billing_history_id' => $history->getKey(),
                'delivery_id' => $delivery->delivery_id,
                'package_status_id' => $package->package_status_id,
                'package_id' => $package->package_id,
                'lot_id' => $package->lot_id,
                'lot_number' => $lotNumber,
                'bir_dr' => $delivery->dr_no,
                'package_code' => $package->package_code,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_tab' => $tab,
                'target_cell' => $cell,
                'transition_at' => $transitionAt,
                'transition_date' => $transitionAt->toDateString(),
                'idempotency_key' => hash('sha256', 'billing-history:'.$history->getKey().':package-status:'.$package->package_status_id),
                'outcome' => $supportedLot ? 'queued' : 'not_applicable',
                'error_details' => $supportedLot ? null : 'Only Lots 2, 9, and 11 are configured for the target sheet.',
            ], $supportedLot);
        }
    }

    public function sync(BillingSheetSync $sync, GoogleSheetsApiClient $sheets): void
    {
        $sync->forceFill(['outcome' => 'syncing', 'attempts' => $sync->attempts + 1, 'error_details' => null])->save();
        $configuration = $sheets->configurationStatus();
        if (! $configuration['configured']) {
            $this->finish($sync, 'configuration_missing', $configuration['reason']);

            return;
        }

        if ($sync->lot_number === null || $sync->target_cell === null || $sync->bir_dr === null || $sync->package_code === null) {
            $this->finish($sync, 'reconciliation_error', 'The audit record is missing a lot, BIR DR, package code, or target column.');

            return;
        }

        $range = $this->quotedTab($sync->sheet_tab).'!'.config('google_sheets.billing.read_range', 'A:CI');
        $rows = $sheets->values($range);
        $headers = $this->findHeaderColumns($rows, $sync->lot_number);
        if ($headers === null) {
            $this->finish($sync, 'reconciliation_error', 'Could not identify unique BIR DR and Core Package Code headers for Lot '.$sync->lot_number.'.');

            return;
        }

        $matches = [];
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex < $headers['header_row']) {
                continue;
            }

            if ($this->normalizeCell($row[$headers['bir_dr_column']] ?? '') === $this->normalizeCell($sync->bir_dr)
                && $this->normalizeCell($row[$headers['package_code_column']] ?? '') === $this->normalizeCell($sync->package_code)) {
                $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
            }
        }

        if (count($matches) === 0) {
            $this->finish($sync, 'no_match', 'No sheet row matched both BIR DR and Core Package Code for Lot '.$sync->lot_number.'.');

            return;
        }

        if (count($matches) > 1) {
            $this->finish($sync, 'duplicate_match', 'Multiple sheet rows matched both BIR DR and Core Package Code for Lot '.$sync->lot_number.'.');

            return;
        }

        $match = $matches[0];
        $cell = $sync->target_cell.$match['row'];
        $existingValue = trim((string) ($match['values'][$this->columnIndex($sync->target_cell)] ?? ''));
        if ($existingValue !== '') {
            if ($existingValue === $sync->transition_date->toDateString()) {
                $this->finish($sync, 'already_synced', null, $cell);

                return;
            }

            $this->finish($sync, 'conflict', 'Target cell already contains a value; it was left unchanged.', $cell);

            return;
        }

        $sheets->updateCell($this->quotedTab($sync->sheet_tab).'!'.$cell, $sync->transition_date->toDateString());
        $this->finish($sync, 'synced', null, $cell);
    }

    /** @param array<string, mixed> $attributes */
    private function createAndDispatch(array $attributes, bool $dispatch): void
    {
        $sync = BillingSheetSync::query()->firstOrCreate(
            ['idempotency_key' => $attributes['idempotency_key']],
            $attributes
        );

        if (! $dispatch || ! in_array($sync->outcome, ['queued', 'dispatch_failed', 'queue_unavailable'], true)) {
            return;
        }

        DB::afterCommit(function () use ($sync): void {
            $this->dispatch($sync);
        });
    }

    public function dispatch(BillingSheetSync $sync): void
    {
        $queueName = (string) config('queue.default');
        $driver = (string) config('queue.connections.'.$queueName.'.driver');
        if (in_array($driver, ['sync', 'null', ''], true)) {
            $sync->forceFill([
                'outcome' => 'queue_unavailable',
                'error_details' => 'An asynchronous queue connection is required for non-blocking Google Sheets sync.',
            ])->save();

            return;
        }

        try {
            SyncBillingSheetDate::dispatch($sync->getKey())->afterCommit();
        } catch (Throwable $exception) {
            Log::error('Could not enqueue billing Google Sheets sync.', [
                'billing_sheet_sync_id' => $sync->getKey(),
                'delivery_id' => $sync->delivery_id,
                'exception' => $exception,
            ]);
            $sync->forceFill(['outcome' => 'dispatch_failed', 'error_details' => 'Unable to enqueue Google Sheets sync.'])->save();
        }
    }

    private function finish(BillingSheetSync $sync, string $outcome, ?string $error, ?string $targetCell = null): void
    {
        $sync->forceFill([
            'outcome' => $outcome,
            'target_cell' => $targetCell ?? $sync->target_cell,
            'error_details' => $error,
            'synced_at' => in_array($outcome, ['synced', 'already_synced'], true) ? now() : $sync->synced_at,
        ])->save();
    }

    /** @param array<int, array<int, mixed>> $rows
     * @return array{header_row: int, bir_dr_column: int, package_code_column: int}|null
     */
    private function findHeaderColumns(array $rows, string $lotNumber): ?array
    {
        $maxRows = min(count($rows), (int) config('google_sheets.billing.header_scan_rows', 20));
        for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++) {
            $birColumns = [];
            $packageColumns = [];
            foreach ($rows[$rowIndex] as $columnIndex => $heading) {
                $normalized = preg_replace('/[^a-z0-9]/u', '', $this->normalizeCell($heading)) ?? '';
                $hasLot = str_contains($normalized, 'lot'.$lotNumber);
                if ($hasLot && str_contains($normalized, 'bir') && str_contains($normalized, 'dr')) {
                    $birColumns[] = $columnIndex;
                }
                if ($hasLot && str_contains($normalized, 'corepackagecode')) {
                    $packageColumns[] = $columnIndex;
                }
            }

            if (count($birColumns) === 1 && count($packageColumns) === 1) {
                return ['header_row' => $rowIndex, 'bir_dr_column' => $birColumns[0], 'package_code_column' => $packageColumns[0]];
            }
        }

        return null;
    }

    private function lotNumber(string $lotName): ?string
    {
        return preg_match('/\blot\s*[-#]?\s*(2|9|11)\b/i', $lotName, $matches) === 1 ? $matches[1] : null;
    }

    private function targetColumn(string $lotNumber): ?string
    {
        return ['2' => 'BF', '9' => 'BU', '11' => 'CI'][$lotNumber] ?? null;
    }

    private function normalizeCell(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function quotedTab(string $tab): string
    {
        return "'".str_replace("'", "''", $tab)."'";
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split(strtoupper($column)) as $character) {
            $index = ($index * 26) + ord($character) - 64;
        }

        return $index - 1;
    }
}
