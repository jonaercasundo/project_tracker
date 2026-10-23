<?php

namespace App\Services;

use App\Exceptions\BillingTransitionException;
use App\Models\Delivery;
use App\Models\DeliveryBillingRecord;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeliveryBillingService
{
    public function markForBilling(int $deliveryId, User $actor, ?int $billingBatchId = null): DeliveryBillingRecord
    {
        return DB::transaction(function () use ($deliveryId, $actor, $billingBatchId): DeliveryBillingRecord {
            $delivery = $this->lockEligibleDelivery($deliveryId);
            $billingRecord = $this->lockBillingRecord($deliveryId);
            $currentStatus = $billingRecord?->status ?? DeliveryBillingRecord::PENDING_BILLING;

            if ($currentStatus !== DeliveryBillingRecord::PENDING_BILLING) {
                throw new BillingTransitionException('Only pending deliveries can be marked for billing.');
            }

            $billingRecord ??= new DeliveryBillingRecord(['delivery_id' => $delivery->delivery_id]);
            $billingRecord->status = DeliveryBillingRecord::FOR_BILLING;
            $billingRecord->status_before_hold = null;
            $billingRecord->submitted_for_billing_by = $actor->getKey();
            $billingRecord->submitted_for_billing_at = now();
            $billingRecord->billing_batch_id = $billingBatchId;
            $billingRecord->save();

            $this->recordTransition($billingRecord, $currentStatus, DeliveryBillingRecord::FOR_BILLING, $actor);

            return $billingRecord->refresh();
        }, 3);
    }

    /**
     * @param  array{invoice_no: string, invoice_date: string, amount: string|float|int, billing_amount?: string|float|null, billing_reference?: string|null, billing_remarks?: string|null}  $data
     */
    public function confirmBilled(int $deliveryId, array $data, User $actor): DeliveryBillingRecord
    {
        return DB::transaction(function () use ($deliveryId, $data, $actor): DeliveryBillingRecord {
            $delivery = $this->lockEligibleDelivery($deliveryId);
            $billingRecord = $this->lockBillingRecord($deliveryId);

            if (! $billingRecord || $billingRecord->status !== DeliveryBillingRecord::FOR_BILLING) {
                throw new BillingTransitionException('Only deliveries marked for billing can be confirmed as billed.');
            }

            DB::table('projects')
                ->where('project_id', $delivery->project_id)
                ->lockForUpdate()
                ->first();

            $invoice = Invoice::query()
                ->where('project_id', $delivery->project_id)
                ->where('invoice_no', $data['invoice_no'])
                ->lockForUpdate()
                ->first();

            if ($invoice && (
                $invoice->invoice_date?->toDateString() !== $data['invoice_date']
                || number_format((float) $invoice->amount, 2, '.', '') !== number_format((float) $data['amount'], 2, '.', '')
            )) {
                throw new BillingTransitionException(
                    'That invoice number already exists for this project with different invoice details.',
                    422
                );
            }

            if ($invoice?->status === 'Cancelled') {
                throw new BillingTransitionException('A cancelled invoice cannot be linked to a billed delivery.', 422);
            }

            $invoice ??= Invoice::query()->create([
                'project_id' => $delivery->project_id,
                'invoice_no' => $data['invoice_no'],
                'amount' => $data['amount'],
                'invoice_date' => $data['invoice_date'],
                'status' => 'Pending',
                'created_at' => now(),
            ]);

            $previousStatus = $billingRecord->status;
            $billingRecord->status = DeliveryBillingRecord::BILLED;
            $billingRecord->invoice_id = $invoice->invoice_id;
            $billingRecord->billing_amount = $data['billing_amount'] ?? null;
            $billingRecord->billing_reference = $data['billing_reference'] ?? null;
            $billingRecord->billing_remarks = $data['billing_remarks'] ?? null;
            $billingRecord->confirmed_billed_by = $actor->getKey();
            $billingRecord->confirmed_billed_at = now();
            $billingRecord->status_before_hold = null;
            $billingRecord->save();

            $this->recordTransition(
                $billingRecord,
                $previousStatus,
                DeliveryBillingRecord::BILLED,
                $actor,
                $invoice->invoice_id,
                $billingRecord->billing_remarks
            );
            $this->syncBatchStatus($billingRecord->billing_batch_id);

            return $billingRecord->refresh();
        }, 3);
    }

    public function placeOnHold(int $deliveryId, string $remarks, User $actor): DeliveryBillingRecord
    {
        return DB::transaction(function () use ($deliveryId, $remarks, $actor): DeliveryBillingRecord {
            $this->lockEligibleDelivery($deliveryId);
            $billingRecord = $this->lockBillingRecord($deliveryId);
            $currentStatus = $billingRecord?->status ?? DeliveryBillingRecord::PENDING_BILLING;

            if (! in_array($currentStatus, [DeliveryBillingRecord::PENDING_BILLING, DeliveryBillingRecord::FOR_BILLING], true)) {
                throw new BillingTransitionException('Only pending or for-billing deliveries can be placed on hold.');
            }

            $billingRecord ??= new DeliveryBillingRecord(['delivery_id' => $deliveryId]);
            $billingRecord->status_before_hold = $currentStatus;
            $billingRecord->status = DeliveryBillingRecord::ON_HOLD;
            $billingRecord->billing_remarks = $remarks;
            $billingRecord->save();

            $this->recordTransition($billingRecord, $currentStatus, DeliveryBillingRecord::ON_HOLD, $actor, null, $remarks);
            $this->syncBatchStatus($billingRecord->billing_batch_id);

            return $billingRecord->refresh();
        }, 3);
    }

    public function releaseHold(int $deliveryId, User $actor): DeliveryBillingRecord
    {
        return DB::transaction(function () use ($deliveryId, $actor): DeliveryBillingRecord {
            Delivery::query()->whereKey($deliveryId)->lockForUpdate()->firstOrFail();
            $billingRecord = $this->lockBillingRecord($deliveryId);

            if (! $billingRecord || $billingRecord->status !== DeliveryBillingRecord::ON_HOLD) {
                throw new BillingTransitionException('This delivery is not on hold.');
            }

            $previousStatus = $billingRecord->status;
            $nextStatus = $billingRecord->status_before_hold ?: DeliveryBillingRecord::PENDING_BILLING;
            $billingRecord->status = $nextStatus;
            $billingRecord->status_before_hold = null;
            $billingRecord->save();

            $this->recordTransition($billingRecord, $previousStatus, $nextStatus, $actor);
            $this->syncBatchStatus($billingRecord->billing_batch_id);

            return $billingRecord->refresh();
        }, 3);
    }

    private function lockEligibleDelivery(int $deliveryId): Delivery
    {
        $delivery = Delivery::query()->whereKey($deliveryId)->lockForUpdate()->firstOrFail();

        if (! in_array($delivery->status, ['delivered', 'accepted'], true)) {
            throw new BillingTransitionException('Only delivered or accepted deliveries are eligible for billing.', 422);
        }

        return $delivery;
    }

    private function lockBillingRecord(int $deliveryId): ?DeliveryBillingRecord
    {
        return DeliveryBillingRecord::query()
            ->where('delivery_id', $deliveryId)
            ->lockForUpdate()
            ->first();
    }

    private function recordTransition(
        DeliveryBillingRecord $billingRecord,
        string $previousStatus,
        string $newStatus,
        User $actor,
        ?int $invoiceId = null,
        ?string $remarks = null
    ): void {
        $billingRecord->histories()->create([
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'invoice_id' => $invoiceId,
            'changed_by' => $actor->getKey(),
            'changed_by_name' => $actor->name,
            'remarks' => $remarks,
            'created_at' => now(),
        ]);
    }

    private function syncBatchStatus(?int $batchId): void
    {
        if ($batchId === null) {
            return;
        }

        DB::table('billing_batches')->where('id', $batchId)->lockForUpdate()->first();
        $counts = DB::table('delivery_billing_records')
            ->where('billing_batch_id', $batchId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'billed' THEN 1 ELSE 0 END) as billed")
            ->selectRaw("SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold")
            ->first();

        $status = match (true) {
            (int) $counts->total > 0 && (int) $counts->billed === (int) $counts->total => 'billed',
            (int) $counts->on_hold > 0 => 'returned_for_correction',
            (int) $counts->billed > 0 => 'under_billing_review',
            default => 'for_billing',
        };

        DB::table('billing_batches')->where('id', $batchId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }
}
