<?php

namespace App\Services;

use App\Exceptions\BillingBatchEligibilityException;
use App\Models\BillingBatch;
use App\Models\Delivery;
use App\Models\DeliveryBillingRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingBatchService
{
    public function __construct(
        private DeliveryBillingEligibilityService $eligibility,
        private DeliveryBillingService $billing
    ) {}

    /**
     * @param  array<int, int>  $deliveryIds
     * @param  array<string, mixed>|null  $selectionFilters
     * @param  array<int, string>|null  $selectionGroups
     * @return array{batch: BillingBatch, created: bool}
     */
    public function confirm(
        array $deliveryIds,
        string $selectionMethod,
        string $idempotencyKey,
        User $actor,
        ?array $selectionFilters = null,
        ?array $selectionGroups = null
    ): array {
        $deliveryIds = array_values(array_unique(array_map('intval', $deliveryIds)));
        sort($deliveryIds);
        $selectionData = [
            'method' => $selectionMethod,
            'delivery_ids' => $deliveryIds,
            'filters' => $selectionFilters,
        ];
        if ($selectionGroups !== null) {
            sort($selectionGroups);
            $selectionData['groups'] = array_values(array_unique($selectionGroups));
        }
        $selectionHash = hash('sha256', json_encode($selectionData, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($deliveryIds, $selectionMethod, $idempotencyKey, $actor, $selectionFilters, $selectionHash): array {
            $existing = BillingBatch::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                return $this->existingResult($existing, $selectionHash);
            }

            $deliveries = Delivery::query()
                ->whereIn('delivery_id', $deliveryIds)
                ->orderBy('delivery_id')
                ->lockForUpdate()
                ->get();

            $existing = BillingBatch::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                return $this->existingResult($existing, $selectionHash);
            }

            $billingRecordRows = DeliveryBillingRecord::query()
                ->whereIn('delivery_id', $deliveryIds)
                ->orderBy('delivery_id')
                ->lockForUpdate()
                ->get();
            $billingRecordGroups = $billingRecordRows->groupBy('delivery_id');
            $duplicateBillingRecords = $billingRecordGroups->filter(fn ($records): bool => $records->count() > 1);
            if ($duplicateBillingRecords->isNotEmpty()) {
                $rejected = [];
                foreach ($duplicateBillingRecords as $deliveryId => $records) {
                    $delivery = $deliveries->firstWhere('delivery_id', (int) $deliveryId);
                    $rejected[] = [
                        'delivery_id' => (int) $deliveryId,
                        'dr_no' => (string) ($delivery?->dr_no ?? $deliveryId),
                        'reason' => 'Multiple billing ledger records already exist for this delivery. Review billing record IDs: '.$records->pluck('id')->implode(', ').'.',
                    ];
                }

                throw new BillingBatchEligibilityException(
                    $rejected,
                    'Duplicate billing ledger records require a separate reviewed cleanup. No delivery was added to a billing batch.',
                    409,
                );
            }

            if ($deliveryIds !== [] && $billingRecordRows->count() === count($deliveryIds)
                && $billingRecordRows->every(fn (DeliveryBillingRecord $record): bool => $record->status === DeliveryBillingRecord::FOR_BILLING)
                && $billingRecordRows->pluck('billing_batch_id')->filter()->unique()->count() === 1) {
                $existingBatch = BillingBatch::query()->lockForUpdate()->find((int) $billingRecordRows->first()->billing_batch_id);
                if ($existingBatch) {
                    return ['batch' => $existingBatch, 'created' => false];
                }
            }

            $billingRecords = $billingRecordRows->keyBy('delivery_id');
            $rejected = [];

            foreach ($deliveryIds as $deliveryId) {
                $delivery = $deliveries->firstWhere('delivery_id', $deliveryId);
                if (! $delivery) {
                    $rejected[] = ['delivery_id' => $deliveryId, 'dr_no' => (string) $deliveryId, 'reason' => 'Delivery record no longer exists'];

                    continue;
                }

                $assessment = $this->eligibility->assess((string) $delivery->status, $billingRecords->get($deliveryId)?->status);
                if (! $assessment['eligible']) {
                    $rejected[] = ['delivery_id' => $deliveryId, 'dr_no' => (string) $delivery->dr_no, 'reason' => $assessment['reason']];
                }
            }

            if ($rejected !== []) {
                throw new BillingBatchEligibilityException(
                    $rejected,
                    'The selected deliveries changed or are not eligible. No delivery was added to a billing batch.',
                    409
                );
            }

            if ($deliveryIds === []) {
                throw new BillingBatchEligibilityException([], 'Select at least one eligible delivery before confirming.');
            }

            $batch = BillingBatch::query()->create([
                'batch_ref' => $this->newReference(),
                'status' => 'for_billing',
                'selection_method' => $selectionMethod,
                'selection_hash' => $selectionHash,
                'idempotency_key' => $idempotencyKey,
                'selection_filters' => $selectionFilters,
                'created_by' => $actor->getKey(),
                'created_by_name' => $actor->name,
                'confirmed_by' => $actor->getKey(),
                'confirmed_by_name' => $actor->name,
                'confirmed_at' => now(),
            ]);

            foreach ($deliveryIds as $deliveryId) {
                $this->billing->markForBilling($deliveryId, $actor, (int) $batch->getKey());
            }

            return ['batch' => $batch->refresh(), 'created' => true];
        }, 3);
    }

    private function existingResult(BillingBatch $batch, string $selectionHash): array
    {
        if (! hash_equals($batch->selection_hash, $selectionHash)) {
            throw new BillingBatchEligibilityException([], 'This confirmation key was already used for a different selection.', 409);
        }

        return ['batch' => $batch, 'created' => false];
    }

    private function newReference(): string
    {
        return 'BILL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
