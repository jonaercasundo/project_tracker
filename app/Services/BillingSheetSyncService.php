<?php

namespace App\Services;

use App\Jobs\SyncBillingSheetDate;
use App\Models\BillingBatch;
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
     * Fill missing billing details for project rows whose billing cycle is already recorded in the sheet.
     *
     * @param  array{date_received: string, received_by: string, inspection_1: string, inspection_2: string, date_checking: string, date_encoding: string}  $fields
     * @return array{status: string, message: string, synced_count: int, skipped_count: int, issues: array<int, string>}
     */
    public function syncProjectSpreadsheet(
        int $projectId,
        string $checkedBy,
        array $fields,
        GoogleSheetsApiClient $sheets
    ): array {
        $configuration = $sheets->configurationStatus();
        if (! $configuration['configured']) {
            return $this->projectSpreadsheetFailure($configuration['reason'] ?? 'Google Sheets is not configured.');
        }

        $settings = $this->configuration->current();
        $spreadsheetId = $settings['spreadsheet_id'];
        $tab = $settings['sheet_tab'];

        try {
            $rows = $sheets->values($this->quotedTab($tab).'!'.config('google_sheets.billing.batch_read_range', 'A:CT'), $spreadsheetId);
        } catch (Throwable $exception) {
            Log::error('Google Sheets project sync read failed.', ['project_id' => $projectId, 'exception' => $exception]);

            return $this->projectSpreadsheetFailure('Google Sheets could not be read. No cells were changed; check the connection and retry.');
        }

        $deliveries = DB::table('deliveries as delivery')
            ->join('package_status as package_status', 'package_status.delivery_id', '=', 'delivery.delivery_id')
            ->join('package as package', 'package.package_id', '=', 'package_status.package_id')
            ->leftJoin('lot as lot', 'lot.lot_id', '=', 'package.lot_id')
            ->where('delivery.project_id', $projectId)
            ->whereNotNull('package.package_num')
            ->select([
                'delivery.delivery_id',
                'delivery.school_id',
                'delivery.dr_no',
                'package.package_num as package_code',
                'lot.lot_name',
            ])
            ->distinct()
            ->orderBy('delivery.delivery_id')
            ->get();

        $headersByLot = [];
        $plansByRow = [];
        $issues = [];
        $skippedCount = 0;
        foreach ($deliveries as $delivery) {
            $lotNumber = $this->lotNumber((string) $delivery->lot_name);
            if (! in_array($lotNumber, ['2', '9', '11'], true)) {
                continue;
            }

            $headersByLot[$lotNumber] ??= $this->findBillingHeaderColumns($rows, $lotNumber);
            $headers = $headersByLot[$lotNumber];
            $identifier = 'DR '.$delivery->dr_no.' / package '.$delivery->package_code.' / Lot '.$lotNumber;
            if ($headers === null) {
                $issues[] = $identifier.': worksheet headers could not be identified uniquely.';

                continue;
            }

            $matches = [];
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headers['header_row']) {
                    continue;
                }
                if ($this->exactCell($row[$headers['school_id_column']] ?? '') === $this->exactCell($delivery->school_id)
                    && $this->exactCell($row[$headers['package_code_column']] ?? '') === $this->exactCell($delivery->package_code)) {
                    $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
                }
            }

            if (count($matches) !== 1) {
                $issues[] = $identifier.': '.(count($matches) === 0 ? 'no exact School ID and package-code row matched.' : 'multiple exact School ID and package-code rows matched.');

                continue;
            }

            $match = $matches[0];
            if ($this->exactCell($match['values'][$headers['destination_columns']['status']] ?? '') === '') {
                $skippedCount++;

                continue;
            }

            $rowKey = $lotNumber.'|'.$match['row'];
            $plansByRow[$rowKey] ??= [
                'identifier' => $identifier,
                'lot_number' => $lotNumber,
                'row' => $match['row'],
                'row_values' => $match['values'],
                'headers' => $headers,
                'delivery_ids' => [],
            ];
            $plansByRow[$rowKey]['delivery_ids'][(int) $delivery->delivery_id] = (int) $delivery->delivery_id;
        }

        if ($issues !== []) {
            return $this->projectSpreadsheetFailure('Spreadsheet sync stopped because one or more DR/package rows need reconciliation. No cells were changed.', $issues, $skippedCount);
        }

        if ($plansByRow === []) {
            return [
                'status' => 'success',
                'message' => 'No rows with an existing Lot billing status were found to sync.',
                'synced_count' => 0,
                'skipped_count' => $skippedCount,
                'issues' => [],
            ];
        }

        $amountsByDelivery = $this->deliveryTotals(collect($plansByRow)->flatMap(fn (array $plan): array => array_values($plan['delivery_ids']))->unique()->values()->all());
        $updatesByCell = [];
        foreach ($plansByRow as $plan) {
            $fieldsForRow = [
                'checked_by' => $checkedBy,
                'date_received' => $fields['date_received'],
                'received_by' => $fields['received_by'],
                'inspection_1' => $fields['inspection_1'],
                'inspection_2' => $fields['inspection_2'],
                'date_checking' => $fields['date_checking'],
                'date_encoding' => $fields['date_encoding'],
                'total_amount' => $this->formatCents(collect($plan['delivery_ids'])
                    ->sum(fn (int $deliveryId): int => $this->amountToCents($amountsByDelivery[$deliveryId] ?? '0.00'))),
            ];

            foreach ($fieldsForRow as $field => $value) {
                $columnIndex = $plan['headers']['destination_columns'][$field];
                $cell = $this->columnName($columnIndex).$plan['row'];
                $existingValue = $plan['row_values'][$columnIndex] ?? '';
                if ($this->cellValueMatches($field, $existingValue, $value)) {
                    continue;
                }
                if ($this->exactCell($existingValue) !== '') {
                    $issues[] = $plan['identifier'].': '.$cell.' already has a different value; it was left unchanged.';

                    continue;
                }
                $updatesByCell[$cell] = $value;
            }
        }

        if ($issues !== []) {
            return $this->projectSpreadsheetFailure('Spreadsheet sync stopped because existing cell values conflict with the entered details. No cells were changed.', $issues, $skippedCount);
        }

        $updates = [];
        foreach ($updatesByCell as $cell => $value) {
            $updates[] = [
                'range' => $this->quotedTab($tab).'!'.$cell,
                'majorDimension' => 'ROWS',
                'values' => [[$value]],
            ];
        }

        try {
            if ($updates !== []) {
                $sheets->updateCells($updates, $spreadsheetId);
            }
        } catch (Throwable $exception) {
            Log::error('Google Sheets project sync write failed.', ['project_id' => $projectId, 'exception' => $exception]);

            return $this->projectSpreadsheetFailure('Google Sheets could not apply the spreadsheet update. No tracker workflow data was changed; check the sheet and retry.', [], $skippedCount);
        }

        return [
            'status' => 'success',
            'message' => 'Spreadsheet details synchronized for '.count($plansByRow).' DR/package row(s); existing billing status and 3PL received dates were preserved.',
            'synced_count' => count($plansByRow),
            'skipped_count' => $skippedCount,
            'issues' => [],
        ];
    }

    /** @param array<int, string> $issues
     * @return array{status: string, message: string, synced_count: int, skipped_count: int, issues: array<int, string>}
     */
    private function projectSpreadsheetFailure(string $message, array $issues = [], int $skippedCount = 0): array
    {
        return ['status' => 'error', 'message' => $message, 'synced_count' => 0, 'skipped_count' => $skippedCount, 'issues' => $issues];
    }

    /**
     * Queue a sheet reconciliation row for every package in the delivery's lot.
     */
    public function queueForTransition(
        Delivery $delivery,
        DeliveryBillingRecord $billingRecord,
        DeliveryBillingHistory $history,
        ?string $confirmationDate = null
    ): void {
        $timezone = (string) config('google_sheets.billing.business_timezone', config('app.timezone', 'Asia/Manila'));
        $transitionAt = $billingRecord->submitted_for_billing_at?->timezone($timezone)
            ?? $history->created_at?->timezone($timezone)
            ?? now($timezone);
        $transitionDate = $confirmationDate ?? $transitionAt->toDateString();
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
                'package.package_num as package_code',
                'package.lot_id',
                'lot.lot_name',
            ]);

        if ($packages->isEmpty()) {
            $this->createAndDispatch([
                'delivery_billing_history_id' => $history->getKey(),
                'delivery_id' => $delivery->delivery_id,
                'school_id' => $delivery->school_id,
                'billing_batch_id' => $billingRecord->billing_batch_id,
                'authenticated_user_id' => $history->changed_by,
                'authenticated_user_name' => $history->changed_by_name,
                'sync_version' => $billingRecord->billing_batch_id === null ? 1 : 2,
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
                'transition_date' => $transitionDate,
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
                'school_id' => $delivery->school_id,
                'billing_batch_id' => $billingRecord->billing_batch_id,
                'authenticated_user_id' => $history->changed_by,
                'authenticated_user_name' => $history->changed_by_name,
                'sync_version' => $billingRecord->billing_batch_id === null ? 1 : 2,
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
                'transition_date' => $transitionDate,
                'idempotency_key' => hash('sha256', 'billing-history:'.$history->getKey().':package-status:'.$package->package_status_id),
                'outcome' => $supportedLot ? 'queued' : 'not_applicable',
                'error_details' => $supportedLot ? null : 'Only Lots 2, 9, and 11 are configured for the target sheet.',
            ], $supportedLot && $billingRecord->billing_batch_id === null);
        }
    }

    public function sync(BillingSheetSync $sync, GoogleSheetsApiClient $sheets): void
    {
        $isRevoked = DB::table('delivery_billing_records')
            ->where('delivery_id', $sync->delivery_id)
            ->where('status', DeliveryBillingRecord::REVOKED)
            ->exists();
        if ($isRevoked) {
            $this->finish($sync, 'revoked', 'The billing row was revoked; no Google Sheets update was attempted.');

            return;
        }

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

            if ($this->normalizeCell($row[$headers['bir_dr_column']] ?? '') === $this->normalizeCell($sync->bir_dr)
                && $this->normalizeCell($row[$headers['package_code_column']] ?? '') !== '') {
                $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
            }
        }

        if (count($matches) === 0) {
            $this->finish($sync, 'no_match', 'No sheet row matched BIR DR '.$sync->bir_dr.' with a Core Package Code for Lot '.$sync->lot_number.'.');

            return;
        }

        if (count($matches) > 1) {
            $this->finish($sync, 'duplicate_match', 'Multiple sheet rows matched BIR DR '.$sync->bir_dr.' with a Core Package Code for Lot '.$sync->lot_number.'.');

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
            if ($entryIssues === []) {
                return [
                    'status' => 'success',
                    'message' => 'No active billing rows remain in this batch; revoked rows were excluded from Google Sheets sync.',
                    'synced_count' => 0,
                    'issues' => [],
                ];
            }

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

        $pendingSyncs = $syncs->reject(fn (BillingSheetSync $sync): bool => $this->isBatchSyncComplete($sync))->values();
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

            if ($sync->lot_number === null || $sync->bir_dr === null || trim($sync->bir_dr) === ''
                || $sync->school_id === null || trim($sync->school_id) === ''
                || $sync->package_code === null || trim($sync->package_code) === ''
                || ! in_array($sync->lot_number, ['2', '9', '11'], true)) {
                $invalidSyncs->push([$sync, 'reconciliation_error', 'The DR, school ID, exact package code, or supported Lot number could not be validated.']);
            }
        }

        if ($invalidSyncs->isNotEmpty()) {
            $invalidSyncs->each(fn (array $failure) => $this->finish($failure[0], $failure[1], $failure[2], validationErrors: [$failure[2]]));
            $invalidIds = $invalidSyncs->map(fn (array $failure): int => (int) $failure[0]->getKey())->all();
            $blockedSyncs = $pendingSyncs->reject(fn (BillingSheetSync $sync): bool => in_array((int) $sync->getKey(), $invalidIds, true));
            $this->markBatchFailure($blockedSyncs, 'batch_blocked', 'No sheet cells were written because another DR/package code in this batch failed validation.');

            return $this->batchSyncFailure($syncs, 'One or more DR/package codes failed validation; no sheet cells were written.');
        }

        $pendingSyncs->each(fn (BillingSheetSync $sync) => $sync->forceFill(['outcome' => 'syncing'])->save());

        $firstSync = $pendingSyncs->first();
        $range = $this->quotedTab($firstSync->sheet_tab).'!'.config('google_sheets.billing.batch_read_range', 'A:CT');
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

        $batch = BillingBatch::query()->find($batchId);
        if ($batch === null) {
            $issue = 'The billing batch could not be loaded. No sheet cells were written.';
            $this->markBatchFailure($pendingSyncs, 'reconciliation_error', $issue);

            return $this->batchSyncFailure($syncs, $issue);
        }

        $confirmedByName = trim((string) ($batch->confirmed_by_name ?: $firstSync->authenticated_user_name));
        $confirmedById = $batch->confirmed_by ?: $firstSync->authenticated_user_id;
        $missingStatusSyncs = $pendingSyncs->filter(function (BillingSheetSync $sync) use ($batch): bool {
            return ! in_array($batch->google_sheet_fields[$sync->lot_number]['status'] ?? null, ['1st Billing', '2nd Billing', '3rd Billing'], true);
        });
        if ($missingStatusSyncs->isNotEmpty()) {
            $this->markBatchFailure($missingStatusSyncs, 'reconciliation_error', 'Select a valid billing cycle (1st, 2nd, or 3rd Billing) for every Lot before syncing.');
            $blockedSyncs = $pendingSyncs->reject(fn (BillingSheetSync $sync): bool => $missingStatusSyncs->contains('id', $sync->getKey()));
            $this->markBatchFailure($blockedSyncs, 'batch_blocked', 'No sheet cells were written because a selected Lot has no billing cycle.');

            return $this->batchSyncFailure($syncs, 'A billing cycle is missing for one or more Lots; no sheet cells were written.');
        }

        $headersByLot = [];
        $plansByRow = [];
        $issues = [];
        foreach ($pendingSyncs as $sync) {
            $lotNumber = (string) $sync->lot_number;
            $headersByLot[$lotNumber] ??= $this->findBillingHeaderColumns($rows, $lotNumber);
            $headers = $headersByLot[$lotNumber];
            if ($headers === null) {
                $issues[] = [$sync, 'reconciliation_error', 'Could not identify a unique SchoolID, Core Package Code, or required billing header for Lot '.$lotNumber.'.'];

                continue;
            }

            $matches = [];
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headers['header_row']) {
                    continue;
                }

                if ($this->exactCell($row[$headers['school_id_column']] ?? '') === $this->exactCell($sync->school_id)
                    && $this->exactCell($row[$headers['package_code_column']] ?? '') === $this->exactCell($sync->package_code)) {
                    $matches[] = ['row' => $rowIndex + 1, 'values' => $row];
                }
            }

            if (count($matches) !== 1) {
                $outcome = count($matches) === 0 ? 'no_match' : 'duplicate_match';
                $message = count($matches) === 0
                    ? 'No sheet row matched School ID '.$sync->school_id.' and exact package code '.$sync->package_code.' for Lot '.$lotNumber.'.'
                    : 'Multiple sheet rows matched School ID '.$sync->school_id.' and exact package code '.$sync->package_code.' for Lot '.$lotNumber.'.';
                $issues[] = [$sync, $outcome, $message];

                continue;
            }

            $match = $matches[0];
            if ($confirmedByName === '') {
                $issues[] = [$sync, 'reconciliation_error', 'The authenticated confirmer name was not recorded in the billing batch.'];

                continue;
            }

            $destinationCells = [];
            foreach ($headers['destination_columns'] as $field => $columnIndex) {
                $destinationCells[$field] = $this->columnName($columnIndex).$match['row'];
            }

            $sync->forceFill([
                'billing_batch_id' => $batchId,
                'school_id' => $sync->school_id,
                'authenticated_user_id' => $confirmedById,
                'authenticated_user_name' => $confirmedByName,
                'destination_cells' => $destinationCells,
            ])->save();

            $rowKey = $lotNumber.'|'.$match['row'];
            $plansByRow[$rowKey] ??= [
                'lot_number' => $lotNumber,
                'row' => $match['row'],
                'row_values' => $match['values'],
                'destination_columns' => $headers['destination_columns'],
                'delivery_ids' => [],
                'syncs' => [],
            ];
            $plansByRow[$rowKey]['delivery_ids'][(int) $sync->delivery_id] = (int) $sync->delivery_id;
            $plansByRow[$rowKey]['syncs'][] = $sync;
        }

        $deliveryAmountsById = $this->deliveryTotals($pendingSyncs->pluck('delivery_id')->unique()->map(fn (mixed $id): int => (int) $id)->all());
        $updatesByCell = [];
        $writtenAmounts = [];
        foreach ($plansByRow as $plan) {
            $lotFields = $batch->google_sheet_fields[$plan['lot_number']] ?? [];
            $values = [
                'status' => $lotFields['status'] ?? null,
                'checked_by' => $confirmedByName,
                'date_received' => $lotFields['date_received'] ?? null,
                'received_by' => $lotFields['received_by'] ?? null,
                'inspection_1' => $lotFields['inspection_1'] ?? null,
                'inspection_2' => $lotFields['inspection_2'] ?? null,
                'date_checking' => $lotFields['date_checking'] ?? null,
                'date_encoding' => $lotFields['date_encoding'] ?? null,
                'total_amount' => null,
            ];
            $amountCents = 0;
            $hasAmount = false;
            $lineAmountsByDelivery = [];
            foreach ($plan['delivery_ids'] as $deliveryId) {
                $amount = $deliveryAmountsById[$deliveryId] ?? null;
                if ($amount !== null) {
                    $lineAmountCents = $this->amountToCents($amount);
                    $lineAmountsByDelivery[$deliveryId] = $this->formatCents($lineAmountCents);
                    $amountCents += $lineAmountCents;
                    $hasAmount = true;
                }
            }
            if ($hasAmount) {
                $values['total_amount'] = $this->formatCents($amountCents);
            }

            $writtenDeliveryIds = [];
            foreach ($plan['syncs'] as $sync) {
                $deliveryId = (int) $sync->delivery_id;
                $writtenAmounts[(int) $sync->getKey()] = isset($writtenDeliveryIds[$deliveryId])
                    ? null
                    : ($lineAmountsByDelivery[$deliveryId] ?? null);
                $writtenDeliveryIds[$deliveryId] = true;
            }

            foreach ($values as $field => $value) {
                if ($value === null) {
                    continue;
                }

                $columnIndex = $plan['destination_columns'][$field];
                $cell = $this->columnName($columnIndex).$plan['row'];
                $existingValue = $plan['row_values'][$columnIndex] ?? '';
                if ($this->cellValueMatches($field, $existingValue, $value)) {
                    continue;
                }
                if ($this->exactCell($existingValue) !== '') {
                    foreach ($plan['syncs'] as $sync) {
                        $issues[] = [$sync, 'conflict', 'Target cell '.$cell.' for '.$field.' already contains another value; it was left unchanged.'];
                    }

                    continue;
                }

                $updatesByCell[$cell] = $value;
            }
        }

        if ($issues !== []) {
            foreach ($issues as [$sync, $outcome, $message]) {
                $this->finish($sync, $outcome, $message, validationErrors: [$message]);
            }
            $issueIds = collect($issues)->map(fn (array $issue): int => (int) $issue[0]->getKey())->all();
            $blockedSyncs = $pendingSyncs->reject(fn (BillingSheetSync $sync): bool => in_array((int) $sync->getKey(), $issueIds, true));
            $this->markBatchFailure($blockedSyncs, 'batch_blocked', 'No sheet cells were written because another DR/package mapping or destination cell needs correction.');

            return $this->batchSyncFailure($syncs, 'One or more DR/package mappings or destination cells need correction; no sheet cells were written.');
        }

        $updates = [];
        foreach ($updatesByCell as $cell => $value) {
            $updates[] = [
                'range' => $this->quotedTab($firstSync->sheet_tab).'!'.$cell,
                'majorDimension' => 'ROWS',
                'values' => [[$value]],
            ];
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

        foreach ($plansByRow as $plan) {
            foreach ($plan['syncs'] as $sync) {
                $sync->forceFill(['written_amount' => $writtenAmounts[(int) $sync->getKey()] ?? null])->save();
                $sync->forceFill(['sync_version' => 2])->save();
                $this->finish($sync, $updates === [] ? 'already_synced' : 'synced', null, validationErrors: []);
            }
        }

        $updatedSyncs = BillingSheetSync::query()->whereIn('id', $syncs->pluck('id'))->orderBy('id')->get();

        return $this->batchSyncSuccess($updatedSyncs, count($updates));
    }

    /** @return array<int, int> */
    private function batchConfirmationHistoryIds(int $batchId): array
    {
        $billingRecordIds = DB::table('delivery_billing_records')
            ->where('billing_batch_id', $batchId)
            ->where('status', '!=', DeliveryBillingRecord::REVOKED)
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

    /** @param array<int, int> $deliveryIds
     * @return array<int, string>
     */
    private function deliveryTotals(array $deliveryIds): array
    {
        if ($deliveryIds === []) {
            return [];
        }

        $lotAllocations = DB::table('deliveries as d')
            ->leftJoin('package as package', 'package.lot_id', '=', 'd.lot_id')
            ->whereIn('d.delivery_id', $deliveryIds)
            ->whereNull('d.keystage_id')
            ->select(['d.delivery_id', 'd.package_qty', 'package.package_id']);
        $keystageAllocations = DB::table('deliveries as d')
            ->leftJoin('package as package', 'package.keystage_id', '=', 'd.keystage_id')
            ->whereIn('d.delivery_id', $deliveryIds)
            ->whereNotNull('d.keystage_id')
            ->select(['d.delivery_id', 'd.package_qty', 'package.package_id']);
        $allocations = $lotAllocations->unionAll($keystageAllocations);

        return DB::query()->fromSub($allocations, 'allocation')
            ->leftJoin('package_content as content', 'content.package_id', '=', 'allocation.package_id')
            ->leftJoin('item as item', 'item.item_id', '=', 'content.item_id')
            ->select('allocation.delivery_id')
            ->selectRaw('SUM(COALESCE(item.price, 0) * CASE WHEN content.qty > 0 THEN content.qty ELSE 1 END * CASE WHEN allocation.package_qty > 0 THEN allocation.package_qty ELSE 1 END) as amount')
            ->groupBy('allocation.delivery_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->delivery_id => $this->formatCents($this->amountToCents($row->amount))])
            ->all();
    }

    /** @return array<int, array{delivery_id: int, package_id: ?int, school_id: ?string, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}> */
    private function ensureBatchSyncEntries(int $batchId): array
    {
        $billingRecords = DeliveryBillingRecord::query()
            ->with('batch')
            ->where('billing_batch_id', $batchId)
            ->where('status', '!=', DeliveryBillingRecord::REVOKED)
            ->get();
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
                    'package_id' => null,
                    'school_id' => $delivery?->school_id,
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
                $this->queueForTransition(
                    $delivery,
                    $billingRecord,
                    $history,
                    $billingRecord->batch?->confirmation_date?->toDateString()
                );
            } catch (Throwable $exception) {
                Log::error('Could not prepare Google Sheets reconciliation entries for a confirmed billing batch.', [
                    'billing_batch_id' => $batchId,
                    'delivery_id' => $delivery->delivery_id,
                    'billing_record_id' => $billingRecord->getKey(),
                    'exception' => $exception,
                ]);
                $issues[] = [
                    'delivery_id' => (int) $delivery->delivery_id,
                    'package_id' => null,
                    'school_id' => $delivery->school_id,
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
     * @return array{status: string, message: string, synced_count: int, issues: array<int, array{delivery_id: int, package_id: ?int, school_id: ?string, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}>}
     */
    private function batchSyncSuccess(Collection $syncs, int $updatedCellCount): array
    {
        return [
            'status' => 'success',
            'message' => $updatedCellCount > 0
                ? 'Billing confirmation was synchronized to '.$updatedCellCount.' Google Sheet cell(s); delivery and inventory states were not changed.'
                : 'All matching Google Sheet values were already synchronized; no duplicate writes were made.',
            'synced_count' => $syncs->filter(fn (BillingSheetSync $sync): bool => $this->isBatchSyncComplete($sync))->count(),
            'issues' => [],
        ];
    }

    /** @param Collection<int, BillingSheetSync> $allSyncs
     * @return array{status: string, message: string, synced_count: int, issues: array<int, array{delivery_id: int, package_id: ?int, school_id: ?string, dr_no: ?string, package_code: ?string, lot_number: ?string, reason: string}>}
     */
    private function batchSyncFailure(Collection $allSyncs, string $message): array
    {
        $updatedSyncs = BillingSheetSync::query()->whereIn('id', $allSyncs->pluck('id'))->orderBy('id')->get();
        $issues = $updatedSyncs
            ->filter(fn (BillingSheetSync $sync): bool => ! $this->isBatchSyncComplete($sync))
            ->map(fn (BillingSheetSync $sync): array => [
                'delivery_id' => (int) $sync->delivery_id,
                'package_id' => $sync->package_id === null ? null : (int) $sync->package_id,
                'school_id' => $sync->school_id,
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
            'synced_count' => $updatedSyncs->filter(fn (BillingSheetSync $sync): bool => $this->isBatchSyncComplete($sync))->count(),
            'issues' => $issues,
        ];
    }

    /** @param Collection<int, BillingSheetSync> $syncs */
    private function markBatchFailure(Collection $syncs, string $outcome, string $message): void
    {
        foreach ($syncs as $sync) {
            $this->finish($sync, $outcome, $message, validationErrors: [$message]);
        }
    }

    private function isBatchSyncComplete(BillingSheetSync $sync): bool
    {
        return in_array($sync->outcome, ['synced', 'already_synced'], true)
            && (int) $sync->sync_version >= 2;
    }

    /** @param array<string, mixed> $attributes */
    private function createAndDispatch(array $attributes, bool $dispatch): void
    {
        $sync = BillingSheetSync::query()->firstOrCreate(
            ['idempotency_key' => $attributes['idempotency_key']],
            $attributes
        );

        $isUnsynced = ! in_array($sync->outcome, ['synced', 'already_synced'], true)
            || (int) $sync->sync_version < (int) ($attributes['sync_version'] ?? 1);
        $hasPackageStatus = $attributes['package_status_id'] !== null;
        $mappingChanged = $sync->lot_id !== $attributes['lot_id']
            || $sync->lot_number !== $attributes['lot_number']
            || $sync->target_cell !== $attributes['target_cell']
            || trim((string) $sync->bir_dr) !== trim((string) $attributes['bir_dr'])
            || trim((string) $sync->package_code) !== trim((string) $attributes['package_code'])
            || $sync->school_id !== ($attributes['school_id'] ?? null)
            || (int) $sync->sync_version !== (int) ($attributes['sync_version'] ?? 1)
            || $sync->billing_batch_id !== ($attributes['billing_batch_id'] ?? null)
            || $sync->authenticated_user_id !== ($attributes['authenticated_user_id'] ?? null)
            || $sync->authenticated_user_name !== ($attributes['authenticated_user_name'] ?? null);

        if (! $sync->wasRecentlyCreated && $hasPackageStatus && $isUnsynced && $mappingChanged) {
            $sync->forceFill($attributes)->save();
        }

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

    private function finish(
        BillingSheetSync $sync,
        string $outcome,
        ?string $error,
        ?string $targetCell = null,
        ?array $validationErrors = null
    ): void {
        $attributes = [
            'outcome' => $outcome,
            'target_cell' => $targetCell ?? $sync->target_cell,
            'error_details' => $error,
            'synced_at' => in_array($outcome, ['synced', 'already_synced'], true) ? now() : $sync->synced_at,
        ];
        if ($validationErrors !== null) {
            $attributes['validation_errors'] = $validationErrors;
        }
        $sync->forceFill($attributes)->save();
    }

    /** @param array<int, array<int, mixed>> $rows
     * @return array{header_row: int, bir_dr_column: int, package_code_column: int}|null
     */
    private function findHeaderColumns(array $rows, string $lotNumber): ?array
    {
        $maxRows = min(count($rows), (int) config('google_sheets.billing.header_scan_rows', 20));
        $headerCandidates = [];
        for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++) {
            $birColumns = [];
            $packageColumns = [];
            $allPackageColumns = [];
            foreach ($rows[$rowIndex] as $columnIndex => $heading) {
                $normalized = preg_replace('/[^a-z0-9]/u', '', $this->normalizeCell($heading)) ?? '';
                $hasLot = str_contains($normalized, 'lot'.$lotNumber);
                if (str_contains($normalized, 'corepackagecode')) {
                    $allPackageColumns[] = $columnIndex;
                    if ($hasLot) {
                        $packageColumns[] = $columnIndex;
                    }
                }
                if (str_contains($normalized, 'bir')
                    && str_contains($normalized, 'dr')
                    && ! str_contains($normalized, 'printed')) {
                    $birColumns[] = ['column' => $columnIndex, 'has_lot' => $hasLot];
                }
            }

            if ($packageColumns === []) {
                continue;
            }

            if (count($packageColumns) !== 1) {
                return null;
            }

            $packageColumn = $packageColumns[0];
            $lotBirColumns = array_values(array_filter(
                $birColumns,
                fn (array $column): bool => $column['has_lot']
            ));

            if ($lotBirColumns === []) {
                $nextPackageColumn = collect($allPackageColumns)
                    ->first(fn (int $column): bool => $column > $packageColumn) ?? PHP_INT_MAX;
                $lotBirColumns = array_values(array_filter(
                    $birColumns,
                    fn (array $column): bool => ! $column['has_lot']
                        && $column['column'] > $packageColumn
                        && $column['column'] < $nextPackageColumn
                ));
            }

            if (count($lotBirColumns) !== 1) {
                return null;
            }

            $headerCandidates[] = [
                'header_row' => $rowIndex,
                'bir_dr_column' => $lotBirColumns[0]['column'],
                'package_code_column' => $packageColumn,
            ];
        }

        return count($headerCandidates) === 1 ? $headerCandidates[0] : null;
    }

    private function lotNumber(string $lotName): ?string
    {
        $lotName = trim($lotName);

        if (preg_match('/^(2|9|11)$/', $lotName, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/\blot\s*[-#]?\s*(2|9|11)\b/i', $lotName, $matches) === 1 ? $matches[1] : null;
    }

    private function targetColumn(string $lotNumber): ?string
    {
        return ['2' => 'BF', '9' => 'BU', '11' => 'CI'][$lotNumber] ?? null;
    }

    /** @param array<int, array<int, mixed>> $rows
     * @return array{header_row: int, school_id_column: int, package_code_column: int, destination_columns: array<string, int>}|null
     */
    private function findBillingHeaderColumns(array $rows, string $lotNumber): ?array
    {
        $lotSuffix = 'lot'.$lotNumber;
        $maxRows = min(count($rows), (int) config('google_sheets.billing.header_scan_rows', 20));
        $candidates = [];

        for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++) {
            $headers = $rows[$rowIndex] ?? [];
            $schoolColumns = [];
            $packageColumns = [];
            $billingSectionStarts = [];
            foreach ($headers as $columnIndex => $heading) {
                $normalized = $this->normalizedHeader($heading);
                if ($normalized === 'schoolid') {
                    $schoolColumns[] = $columnIndex;
                }
                if ($normalized === 'corepackagecode'.$lotSuffix) {
                    $packageColumns[] = $columnIndex;
                }
                if (str_contains($normalized, 'status') && str_contains($normalized, 'billing') && str_contains($normalized, $lotSuffix)) {
                    $billingSectionStarts[] = $columnIndex;
                }
            }

            if (count($schoolColumns) !== 1 || count($packageColumns) !== 1 || count($billingSectionStarts) !== 1) {
                continue;
            }

            $statusColumn = $billingSectionStarts[0];
            $nextSectionColumn = collect($headers)
                ->keys()
                ->filter(fn (int $column): bool => $column > $statusColumn
                    && str_contains($this->normalizedHeader($headers[$column] ?? ''), 'status')
                    && str_contains($this->normalizedHeader($headers[$column] ?? ''), 'billing')
                    && preg_match('/lot(?:2|9|11)/', $this->normalizedHeader($headers[$column] ?? '')) === 1)
                ->min() ?? count($headers);
            $sectionEnd = $nextSectionColumn;
            $sectionHeaders = [];
            foreach ($headers as $columnIndex => $heading) {
                if ($columnIndex >= $statusColumn && $columnIndex < $sectionEnd) {
                    $sectionHeaders[$columnIndex] = $this->normalizedHeader($heading);
                }
            }

            $requestedHeaders = [
                'status' => fn (string $header): bool => str_contains($header, 'status')
                    && str_contains($header, 'billing') && str_contains($header, $lotSuffix),
                'checked_by' => fn (string $header): bool => $header === 'checkedby',
                'date_received' => fn (string $header): bool => $header === 'datereceived'.$lotSuffix,
                'received_by' => fn (string $header): bool => $header === 'receivedby'.$lotSuffix,
                'inspection_1' => fn (string $header): bool => $header === 'inspection1'.$lotSuffix,
                'inspection_2' => fn (string $header): bool => $header === 'inspection2'.$lotSuffix,
                'date_checking' => fn (string $header): bool => in_array($header, ['datechecking'.$lotSuffix, 'datecheking'.$lotSuffix], true),
                'date_encoding' => fn (string $header): bool => $header === 'dateencoding'.$lotSuffix,
                'total_amount' => fn (string $header): bool => $header === 'totalamount'.$lotSuffix,
            ];
            $destinationColumns = [];
            foreach ($requestedHeaders as $field => $matchesHeader) {
                $matchedColumns = [];
                foreach ($sectionHeaders as $columnIndex => $normalized) {
                    if ($matchesHeader($normalized)) {
                        $matchedColumns[] = $columnIndex;
                    }
                }
                if (count($matchedColumns) !== 1) {
                    $destinationColumns = [];

                    break;
                }
                $destinationColumns[$field] = $matchedColumns[0];
            }

            if (count($destinationColumns) === count($requestedHeaders)) {
                $candidates[] = [
                    'header_row' => $rowIndex,
                    'school_id_column' => $schoolColumns[0],
                    'package_code_column' => $packageColumns[0],
                    'destination_columns' => $destinationColumns,
                ];
            }
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function normalizedHeader(mixed $value): string
    {
        return preg_replace('/[^a-z0-9]/u', '', mb_strtolower(trim((string) $value))) ?? '';
    }

    private function exactCell(mixed $value): string
    {
        return trim((string) $value);
    }

    private function columnName(int $columnIndex): string
    {
        $column = '';
        for ($number = $columnIndex + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $column = chr(65 + (($number - 1) % 26)).$column;
        }

        return $column;
    }

    private function amountToCents(mixed $amount): int
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', trim((string) $amount))) ?? '';
        if (! is_numeric($normalized)) {
            return 0;
        }

        return (int) round((float) $normalized * 100);
    }

    private function formatCents(int $amountCents): string
    {
        $negative = $amountCents < 0;
        $absolute = abs($amountCents);

        return ($negative ? '-' : '').intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function cellValueMatches(string $field, mixed $existingValue, string $expectedValue): bool
    {
        if ($field !== 'total_amount') {
            return $this->exactCell($existingValue) === $expectedValue;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', trim((string) $existingValue))) ?? '';

        return $normalized !== '' && is_numeric($normalized)
            && $this->amountToCents($normalized) === $this->amountToCents($expectedValue);
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
