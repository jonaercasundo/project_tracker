<?php

namespace App\Services;

use App\Jobs\SyncBillingSheetDate;
use App\Models\BillingSheetSync;
use App\Models\Delivery;
use App\Models\DeliveryBillingHistory;
use App\Models\DeliveryBillingRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingSheetSyncService
{
    public function __construct(private GoogleSheetsBillingConfiguration $configuration) {}

    /**
     * Queue a sheet reconciliation row for every package in the delivery's lot.
     */
    public function queueForTransition(Delivery $delivery, DeliveryBillingRecord $billingRecord, DeliveryBillingHistory $history): void
    {
        $timezone = (string) config('google_sheets.billing.business_timezone', config('app.timezone', 'Asia/Manila'));
        $transitionAt = $billingRecord->submitted_for_billing_at?->timezone($timezone)
            ?? $history->created_at?->timezone($timezone)
            ?? now($timezone);
        $settings = $this->configuration->current();
        $spreadsheetId = $settings['spreadsheet_id'];
        $tab = $settings['sheet_tab'];
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
            ], $supportedLot && $billingRecord->billing_batch_id === null);
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
        $rows = $sheets->values($range, $sync->spreadsheet_id);
        $headers = $this->findHeaderColumns($rows, $sync->lot_number);
        if ($headers === null) {
            $this->finish($sync, 'reconciliation_error', 'Could not identify a unique BIR DR header for Lot '.$sync->lot_number.'.');

            return;
        }

        $matches = [];
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex < $headers['header_row']) {
                continue;
            }

            if ($this->normalizeCell($row[$headers['bir_dr_column']] ?? '') === $this->normalizeCell($sync->bir_dr)) {
                $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
            }
        }

        if (count($matches) === 0) {
            $this->finish($sync, 'no_match', 'No sheet row matched BIR DR '.$sync->bir_dr.' for Lot '.$sync->lot_number.'.');

            return;
        }

        if (count($matches) > 1) {
            $this->finish($sync, 'duplicate_match', 'Multiple sheet rows matched BIR DR '.$sync->bir_dr.' for Lot '.$sync->lot_number.'.');

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

        $sheets->updateCell($this->quotedTab($sync->sheet_tab).'!'.$cell, $sync->transition_date->toDateString(), $sync->spreadsheet_id);
        $this->finish($sync, 'synced', null, $cell);
    }

    /**
     * Validate every pending package row in a confirmed batch before writing any target cells.
     *
     * @return array{status: string, message: string, synced_count: int, issues: array<int, array{delivery_id: int, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}>}
     */
    public function syncBatch(int $batchId, GoogleSheetsApiClient $sheets): array
    {
        $entryIssues = $this->ensureBatchSyncEntries($batchId);
        $historyIds = $this->batchConfirmationHistoryIds($batchId);
        $syncs = BillingSheetSync::query()
            ->whereIn('delivery_billing_history_id', $historyIds)
            ->orderBy('id')
            ->get();

        if ($syncs->isEmpty()) {
            if ($entryIssues !== []) {
                return [
                    'status' => 'error',
                    'message' => 'Google Sheets reconciliation entries could not be created for this batch. Billing records remain confirmed and available for review.',
                    'synced_count' => 0,
                    'issues' => $entryIssues,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'No Google Sheets reconciliation entries were found for this batch. Billing records remain confirmed and available for review.',
                'synced_count' => 0,
                'issues' => [],
            ];
        }

        if ($entryIssues !== []) {
            $pendingSyncs = $syncs->reject(fn (BillingSheetSync $sync): bool => in_array($sync->outcome, ['synced', 'already_synced'], true))->values();
            $this->markBatchFailure($pendingSyncs, 'batch_blocked', 'No sheet cells were written because the batch audit entries could not be fully prepared.');
            $result = $this->batchSyncFailure($syncs, 'Google Sheets reconciliation entries could not be prepared for every confirmed record; no sheet cells were written.');
            $result['issues'] = array_merge($result['issues'], $entryIssues);

            return $result;
        }

        $pendingSyncs = $syncs->reject(fn (BillingSheetSync $sync): bool => in_array($sync->outcome, ['synced', 'already_synced'], true))->values();
        if ($pendingSyncs->isEmpty()) {
            return $this->batchSyncSuccess($syncs, 0);
        }

        $pendingSyncs->each(fn (BillingSheetSync $sync) => $sync->forceFill([
            'attempts' => $sync->attempts + 1,
            'error_details' => null,
        ])->save());

        try {
            $configuration = $sheets->configurationStatus();
        } catch (Throwable $exception) {
            Log::error('Unable to read Google Sheets configuration for confirmed billing batch.', [
                'billing_batch_id' => $batchId,
                'billing_sheet_sync_ids' => $pendingSyncs->pluck('id')->all(),
                'exception' => $exception,
            ]);
            $issue = 'Google Sheets configuration could not be read. Billing records remain confirmed and available for review.';
            $this->markBatchFailure($pendingSyncs, 'configuration_missing', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        if (! $configuration['configured']) {
            $issue = $configuration['reason'] ?? 'Google Sheets is not configured.';
            $this->markBatchFailure($pendingSyncs, 'configuration_missing', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        $sheetTargets = $pendingSyncs->map(fn (BillingSheetSync $sync): string => $sync->spreadsheet_id.'|'.$sync->sheet_tab)->unique();
        if ($sheetTargets->count() !== 1) {
            $issue = 'The batch contains multiple Google Sheets targets. Confirm the batch again only after its saved sheet settings are reviewed.';
            $this->markBatchFailure($pendingSyncs, 'configuration_mismatch', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        $invalidSyncs = collect();
        foreach ($pendingSyncs as $sync) {
            if ($sync->outcome === 'not_applicable') {
                $invalidSyncs->push([$sync, 'reconciliation_error', $sync->error_details ?? 'This lot does not have a configured target column.']);

                continue;
            }

            $expectedColumn = $sync->lot_number === null ? null : $this->targetColumn($sync->lot_number);
            if ($sync->lot_number === null || $sync->bir_dr === null || trim($sync->bir_dr) === ''
                || $sync->package_code === null || trim($sync->package_code) === ''
                || $expectedColumn === null || $sync->target_cell !== $expectedColumn) {
                $invalidSyncs->push([$sync, 'reconciliation_error', 'The BIR DR, Core Package Code, lot, or unique target column could not be validated.']);
            }
        }

        if ($invalidSyncs->isNotEmpty()) {
            $invalidSyncs->each(fn (array $failure) => $this->finish($failure[0], $failure[1], $failure[2]));
            $invalidIds = $invalidSyncs->map(fn (array $failure): int => (int) $failure[0]->getKey())->all();
            $blockedSyncs = $pendingSyncs->reject(fn (BillingSheetSync $sync): bool => in_array((int) $sync->getKey(), $invalidIds, true));
            $this->markBatchFailure($blockedSyncs, 'batch_blocked', 'No sheet cells were written because another DR/package code in this batch failed validation.');

            return $this->batchSyncFailure($syncs, 'One or more DR/package codes failed validation; no sheet cells were written.');
        }

        $pendingSyncs->each(fn (BillingSheetSync $sync) => $sync->forceFill(['outcome' => 'syncing'])->save());

        $firstSync = $pendingSyncs->first();
        $range = $this->quotedTab($firstSync->sheet_tab).'!'.config('google_sheets.billing.read_range', 'A:CI');
        try {
            $rows = $sheets->values($range, $firstSync->spreadsheet_id);
        } catch (Throwable $exception) {
            Log::error('Unable to read Google Sheets rows for confirmed billing batch.', [
                'billing_batch_id' => $batchId,
                'billing_sheet_sync_ids' => $pendingSyncs->pluck('id')->all(),
                'exception' => $exception,
            ]);
            $issue = 'Google Sheets could not be read. No sheet cells were changed; retry reconciliation after checking the connection.';
            $this->markBatchFailure($pendingSyncs, 'api_failed', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        $headersByLot = [];
        $plans = [];
        $issues = [];
        foreach ($pendingSyncs as $sync) {
            $lotNumber = (string) $sync->lot_number;
            $headersByLot[$lotNumber] ??= $this->findHeaderColumns($rows, $lotNumber);
            $headers = $headersByLot[$lotNumber];
            if ($headers === null) {
                $issues[] = [$sync, 'reconciliation_error', 'Could not identify one unique BIR DR header for Lot '.$lotNumber.'.'];

                continue;
            }

            $matches = [];
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headers['header_row']) {
                    continue;
                }

                if ($this->normalizeCell($row[$headers['bir_dr_column']] ?? '') === $this->normalizeCell($sync->bir_dr)) {
                    $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
                }
            }

            if (count($matches) !== 1) {
                $outcome = count($matches) === 0 ? 'no_match' : 'duplicate_match';
                $message = count($matches) === 0
                    ? 'No sheet row matched BIR DR '.$sync->bir_dr.' for Lot '.$lotNumber.'.'
                    : 'Multiple sheet rows matched BIR DR '.$sync->bir_dr.' for Lot '.$lotNumber.'.';
                $issues[] = [$sync, $outcome, $message];

                continue;
            }

            $cell = $sync->target_cell.$matches[0]['row'];
            $existingValue = trim((string) ($matches[0]['values'][$this->columnIndex($sync->target_cell)] ?? ''));
            if ($existingValue !== '' && $existingValue !== $sync->transition_date->toDateString()) {
                $issues[] = [$sync, 'conflict', 'Target cell '.$cell.' already contains another value; it was left unchanged.'];

                continue;
            }

            $plans[] = ['sync' => $sync, 'cell' => $cell, 'value' => $sync->transition_date->toDateString(), 'already_synced' => $existingValue !== ''];
        }

        $plansByCell = collect($plans)->groupBy('cell');
        foreach ($plansByCell as $cell => $cellPlans) {
            if ($cellPlans->pluck('value')->unique()->count() > 1) {
                foreach ($cellPlans as $plan) {
                    $issues[] = [$plan['sync'], 'ambiguous_write', 'Multiple confirmed records map to '.$cell.' with different values.'];
                }
            }
        }

        if ($issues !== []) {
            foreach ($issues as [$sync, $outcome, $message]) {
                $this->finish($sync, $outcome, $message);
            }
            $issueIds = collect($issues)->map(fn (array $issue): int => (int) $issue[0]->getKey())->all();
            $blockedSyncs = $pendingSyncs->reject(fn (BillingSheetSync $sync): bool => in_array((int) $sync->getKey(), $issueIds, true));
            $this->markBatchFailure($blockedSyncs, 'batch_blocked', 'No sheet cells were written because another DR/package code in this batch needs correction.');

            return $this->batchSyncFailure($syncs, 'One or more DR/package codes were unmatched, ambiguous, or conflicted; no sheet cells were written.');
        }

        $updates = [];
        foreach ($plansByCell as $cell => $cellPlans) {
            if (! $cellPlans->first()['already_synced']) {
                $updates[] = [
                    'range' => $this->quotedTab($firstSync->sheet_tab).'!'.$cell,
                    'majorDimension' => 'ROWS',
                    'values' => [[$cellPlans->first()['value']]],
                ];
            }
        }

        try {
            $sheets->updateCells($updates, $firstSync->spreadsheet_id);
        } catch (Throwable $exception) {
            Log::error('Google Sheets batch update failed after preflight.', [
                'billing_batch_id' => $batchId,
                'billing_sheet_sync_ids' => $pendingSyncs->pluck('id')->all(),
                'exception' => $exception,
            ]);
            $issue = 'Google Sheets could not apply the validated batch update. Retry reconciliation; billing records remain confirmed.';
            $this->markBatchFailure($pendingSyncs, 'api_failed', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        foreach ($plans as $plan) {
            $this->finish($plan['sync'], $plan['already_synced'] ? 'already_synced' : 'synced', null, $plan['cell']);
        }

        $updatedSyncs = BillingSheetSync::query()->whereIn('id', $syncs->pluck('id'))->orderBy('id')->get();

        return $this->batchSyncSuccess($updatedSyncs, count($updates));
    }

    /** @return array<int, int> */
    private function batchConfirmationHistoryIds(int $batchId): array
    {
        $billingRecordIds = DB::table('delivery_billing_records')
            ->where('billing_batch_id', $batchId)
            ->pluck('id');

        return DB::table('delivery_billing_histories')
            ->whereIn('delivery_billing_record_id', $billingRecordIds)
            ->where('new_status', DeliveryBillingRecord::FOR_BILLING)
            ->orderByDesc('id')
            ->get(['id', 'delivery_billing_record_id'])
            ->unique('delivery_billing_record_id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /** @return array<int, array{delivery_id: int, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}> */
    private function ensureBatchSyncEntries(int $batchId): array
    {
        $billingRecords = DeliveryBillingRecord::query()->where('billing_batch_id', $batchId)->get();
        if ($billingRecords->isEmpty()) {
            return [];
        }

        $billingRecordIds = $billingRecords->pluck('id');
        $historyByBillingRecord = DeliveryBillingHistory::query()
            ->whereIn('delivery_billing_record_id', $billingRecordIds)
            ->where('new_status', DeliveryBillingRecord::FOR_BILLING)
            ->orderByDesc('id')
            ->get()
            ->unique('delivery_billing_record_id')
            ->keyBy('delivery_billing_record_id');
        $deliveriesById = Delivery::query()
            ->whereIn('delivery_id', $billingRecords->pluck('delivery_id'))
            ->get()
            ->keyBy('delivery_id');
        $issues = [];

        foreach ($billingRecords as $billingRecord) {
            $delivery = $deliveriesById->get($billingRecord->delivery_id);
            $history = $historyByBillingRecord->get($billingRecord->getKey());
            if ($delivery === null || $history === null) {
                $issues[] = [
                    'delivery_id' => (int) $billingRecord->delivery_id,
                    'dr_no' => $delivery?->dr_no,
                    'package_code' => null,
                    'lot_number' => null,
                    'reason' => $delivery === null
                        ? 'The confirmed delivery record could not be loaded to prepare its sheet audit entry.'
                        : 'No For Billing history entry exists for this confirmed delivery.',
                ];

                continue;
            }

            try {
                $this->queueForTransition($delivery, $billingRecord, $history);
            } catch (Throwable $exception) {
                Log::error('Could not prepare Google Sheets reconciliation entries for a confirmed billing batch.', [
                    'billing_batch_id' => $batchId,
                    'delivery_id' => $delivery->delivery_id,
                    'billing_record_id' => $billingRecord->getKey(),
                    'exception' => $exception,
                ]);
                $issues[] = [
                    'delivery_id' => (int) $delivery->delivery_id,
                    'dr_no' => $delivery->dr_no,
                    'package_code' => null,
                    'lot_number' => null,
                    'reason' => 'The reconciliation audit entry could not be created. Check the billing sheet configuration and retry.',
                ];
            }
        }

        return $issues;
    }

    /** @param Collection<int, BillingSheetSync> $syncs
     * @return array{status: string, message: string, synced_count: int, issues: array<int, array{delivery_id: int, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}>}
     */
    private function batchSyncSuccess(Collection $syncs, int $updatedCellCount): array
    {
        return [
            'status' => 'success',
            'message' => $updatedCellCount > 0
                ? 'Updated '.$updatedCellCount.' Google Sheet cell(s).'
                : 'All matching Google Sheet values were already synchronized; no duplicate writes were made.',
            'synced_count' => $syncs->whereIn('outcome', ['synced', 'already_synced'])->count(),
            'issues' => [],
        ];
    }

    /** @param Collection<int, BillingSheetSync> $allSyncs
     * @return array{status: string, message: string, synced_count: int, issues: array<int, array{delivery_id: int, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}>}
     */
    private function batchSyncFailure(Collection $allSyncs, string $message): array
    {
        $updatedSyncs = BillingSheetSync::query()->whereIn('id', $allSyncs->pluck('id'))->orderBy('id')->get();
        $issues = $updatedSyncs
            ->filter(fn (BillingSheetSync $sync): bool => ! in_array($sync->outcome, ['synced', 'already_synced'], true))
            ->map(fn (BillingSheetSync $sync): array => [
                'delivery_id' => (int) $sync->delivery_id,
                'dr_no' => $sync->bir_dr,
                'package_code' => $sync->package_code,
                'lot_number' => $sync->lot_number,
                'reason' => $sync->error_details ?? 'This package row needs reconciliation.',
            ])
            ->values()
            ->all();

        return [
            'status' => 'error',
            'message' => $message,
            'synced_count' => $updatedSyncs->whereIn('outcome', ['synced', 'already_synced'])->count(),
            'issues' => $issues,
        ];
    }

    /** @param Collection<int, BillingSheetSync> $syncs */
    private function markBatchFailure(Collection $syncs, string $outcome, string $message): void
    {
        foreach ($syncs as $sync) {
            $this->finish($sync, $outcome, $message);
        }
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
     * @return array{header_row: int, bir_dr_column: int}|null
     */
    private function findHeaderColumns(array $rows, string $lotNumber): ?array
    {
        $maxRows = min(count($rows), (int) config('google_sheets.billing.header_scan_rows', 20));
        $headerCandidates = [];
        for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++) {
            $birColumns = [];
            foreach ($rows[$rowIndex] as $columnIndex => $heading) {
                $normalized = preg_replace('/[^a-z0-9]/u', '', $this->normalizeCell($heading)) ?? '';
                $hasLot = str_contains($normalized, 'lot'.$lotNumber);
                if ($hasLot && str_contains($normalized, 'bir') && str_contains($normalized, 'dr')) {
                    $birColumns[] = $columnIndex;
                }
            }

            if ($birColumns !== []) {
                if (count($birColumns) !== 1) {
                    return null;
                }

                $headerCandidates[] = ['header_row' => $rowIndex, 'bir_dr_column' => $birColumns[0]];
            }
        }

        return count($headerCandidates) === 1 ? $headerCandidates[0] : null;
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
