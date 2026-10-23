<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DeliveryBillingEligibilityService
{
    /**
     * Build the delivery review query with the billing and proof indicators.
     *
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        $proofs = DB::table('package_status as proof_status')
            ->join('delivery_proofs as proof', 'proof.package_status_id', '=', 'proof_status.package_status_id')
            ->select('proof_status.delivery_id')
            ->distinct();

        return DB::table('deliveries as d')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->leftJoinSub($proofs, 'pod', 'pod.delivery_id', '=', 'd.delivery_id')
            ->select([
                'd.delivery_id',
                'd.dr_no',
                'd.project_id',
                'p.project_name',
                'd.lot_id',
                'l.lot_name',
                'd.school_id',
                's.school_name',
                's.division',
                'd.delivery_date',
                'd.status as delivery_status',
                'billing.status as billing_status',
                'billing.billing_batch_id',
            ])
            ->selectRaw('CASE WHEN pod.delivery_id IS NULL THEN 0 ELSE 1 END as pod_available')
            ->when(isset($filters['year']), fn (Builder $query) => $query->whereYear('d.delivery_date', $filters['year']))
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['lot_id']), fn (Builder $query) => $query->where('d.lot_id', $filters['lot_id']))
            ->when(isset($filters['division']), fn (Builder $query) => $query->where('s.division', $filters['division']))
            ->when(isset($filters['school_id']), fn (Builder $query) => $query->where('d.school_id', $filters['school_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']))
            ->when(isset($filters['q']), function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('d.dr_no', 'like', $term)
                        ->orWhere('s.school_name', 'like', $term)
                        ->orWhere('p.project_name', 'like', $term)
                        ->orWhere('l.lot_name', 'like', $term);
                });
            });
    }

    /**
     * @return array{eligible: bool, reason: string}
     */
    public function assess(string $deliveryStatus, ?string $billingStatus): array
    {
        if ($deliveryStatus === 'cancelled') {
            return ['eligible' => false, 'reason' => 'Cancelled'];
        }

        if (! in_array($deliveryStatus, ['delivered', 'accepted'], true)) {
            return ['eligible' => false, 'reason' => 'Not Yet Delivered'];
        }

        return match ($billingStatus) {
            'for_billing' => ['eligible' => false, 'reason' => 'Already For Billing'],
            'billed' => ['eligible' => false, 'reason' => 'Already Billed'],
            'on_hold' => ['eligible' => false, 'reason' => 'On Hold'],
            default => ['eligible' => true, 'reason' => 'Eligible'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function present(object $delivery): array
    {
        $assessment = $this->assess((string) $delivery->delivery_status, $delivery->billing_status);
        $billingStatus = $delivery->billing_status ?: 'pending_billing';

        return [
            'delivery_id' => (int) $delivery->delivery_id,
            'dr_no' => (string) $delivery->dr_no,
            'project_id' => (int) $delivery->project_id,
            'project_name' => (string) ($delivery->project_name ?? 'Unknown project'),
            'lot_id' => $delivery->lot_id === null ? null : (int) $delivery->lot_id,
            'lot_name' => (string) ($delivery->lot_name ?? '—'),
            'school_id' => (string) $delivery->school_id,
            'school_name' => (string) ($delivery->school_name ?? 'Unknown school'),
            'division' => (string) ($delivery->division ?? '—'),
            'delivery_date' => (string) $delivery->delivery_date,
            'delivery_status' => (string) $delivery->delivery_status,
            'pod_available' => (bool) $delivery->pod_available,
            'billing_status' => (string) $billingStatus,
            'eligible' => $assessment['eligible'],
            'remarks' => $assessment['reason'],
        ];
    }
}
