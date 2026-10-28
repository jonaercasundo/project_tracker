<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBillingRecord;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $filters = array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
        $deliveries = $this->deliveredDeliveries($filters);
        $summary = (clone $deliveries)
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status IS NULL OR billing.status <> 'revoked' THEN d.delivery_id END) as total")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.delivery_id IS NULL OR billing.status = 'pending_billing' THEN d.delivery_id END) as ready")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'for_billing' THEN d.delivery_id END) as for_billing")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'billed' THEN d.delivery_id END) as billed")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'on_hold' THEN d.delivery_id END) as blocked")
            ->first();

        $total = (int) $summary->total;
        $ready = (int) $summary->ready;
        $forBilling = (int) $summary->for_billing;
        $billed = (int) $summary->billed;
        $blocked = (int) $summary->blocked;

        $totals = [
            'total' => $total,
            'ready' => $ready,
            'for_billing' => $forBilling,
            'billed' => $billed,
            'blocked' => $blocked,
            'active_batches' => $this->activeBatchCount($filters),
            'completion_percentage' => $total > 0 ? round(($billed / $total) * 100, 1) : 0.0,
        ];

        $projects = DB::table('projects as p')
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('deliveries as d')
                    ->whereColumn('d.project_id', 'p.project_id');
            })
            ->orderBy('p.project_name')
            ->get(['p.project_id', 'p.project_name', 'p.project_code', 'p.ref_no']);

        $projectPerformance = $this->projectPerformance($filters);
        $statusDistribution = $this->statusDistribution($totals);

        return view('billing.dashboard', [
            'totals' => $totals,
            'filters' => $filters,
            'projects' => $projects,
            'projectPerformance' => $projectPerformance,
            'statusDistribution' => $statusDistribution,
            'activityTrend' => $this->activityTrend($filters),
            'byDate' => $this->deliveryVolumeByDate($filters),
            'assignedPersonnel' => $this->assignedPersonnel($filters),
            'recentBatches' => $this->recentBatches($filters),
            'returnedBatches' => $this->returnedBatches($filters),
            'returnedBatchCount' => $this->returnedBatchCount($filters),
            'recentConfirmations' => $this->recentConfirmations($filters),
            'recentActivity' => $this->recentActivity($filters),
            'blockedDeliveries' => $this->blockedDeliveries($filters),
            'missingPodCount' => $this->missingPodCount($filters),
            'lastUpdated' => now(),
            'statuses' => [
                DeliveryBillingRecord::PENDING_BILLING => 'Ready for Billing',
                DeliveryBillingRecord::FOR_BILLING => 'For Billing',
                DeliveryBillingRecord::BILLED => 'Billed',
                DeliveryBillingRecord::ON_HOLD => 'On Hold',
                DeliveryBillingRecord::REVOKED => 'Revoked',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function deliveredDeliveries(array $filters): Builder
    {
        return DB::table('deliveries as d')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->whereIn('d.status', ['delivered', 'accepted'])
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function activeBatchCount(array $filters): int
    {
        return $this->batchDeliveryScope($filters)
            ->where('batch.status', '!=', 'billed')
            ->distinct()
            ->count('batch.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function projectPerformance(array $filters): Collection
    {
        $projects = $this->deliveredDeliveries($filters)
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->groupBy('p.project_id', 'p.project_name', 'p.project_code', 'p.ref_no')
            ->select('p.project_id', 'p.project_name', 'p.project_code', 'p.ref_no')
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status IS NULL OR billing.status <> 'revoked' THEN d.delivery_id END) as total")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.delivery_id IS NULL OR billing.status = 'pending_billing' THEN d.delivery_id END) as ready")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'for_billing' THEN d.delivery_id END) as for_billing")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'billed' THEN d.delivery_id END) as billed")
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'on_hold' THEN d.delivery_id END) as blocked")
            ->orderByDesc('total')
            ->orderBy('p.project_name')
            ->limit(12)
            ->get();

        return $projects->map(function (object $project): object {
            $project->completion_percentage = (int) $project->total > 0
                ? round(((int) $project->billed / (int) $project->total) * 100, 1)
                : 0;

            return $project;
        });
    }

    /**
     * @param  array<string, int|float>  $totals
     * @return array<int, array{label: string, value: int, percent: float, color: string}>
     */
    private function statusDistribution(array $totals): array
    {
        $states = [
            ['label' => 'Ready for Billing', 'key' => 'ready', 'color' => '#2563EB'],
            ['label' => 'For Billing', 'key' => 'for_billing', 'color' => '#7C3AED'],
            ['label' => 'Billed', 'key' => 'billed', 'color' => '#16A34A'],
            ['label' => 'On Hold', 'key' => 'blocked', 'color' => '#DC2626'],
        ];

        return array_map(function (array $state) use ($totals): array {
            $value = (int) $totals[$state['key']];

            return [
                'label' => $state['label'],
                'value' => $value,
                'percent' => (int) $totals['total'] > 0 ? round(($value / (int) $totals['total']) * 100, 1) : 0.0,
                'color' => $state['color'],
            ];
        }, $states);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{date: string, submitted: int, billed: int}>
     */
    private function activityTrend(array $filters): array
    {
        $submissions = $this->eventCountsByDate($filters, 'submitted_for_billing_at');
        $confirmations = $this->eventCountsByDate($filters, 'confirmed_billed_at');
        $dates = array_unique(array_merge(array_keys($submissions), array_keys($confirmations)));
        rsort($dates);
        $dates = array_slice($dates, 0, 14);
        sort($dates);

        return array_map(fn (string $date): array => [
            'date' => $date,
            'submitted' => $submissions[$date] ?? 0,
            'billed' => $confirmations[$date] ?? 0,
        ], $dates);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function eventCountsByDate(array $filters, string $timestampColumn): array
    {
        return $this->deliveredDeliveries($filters)
            ->whereNotNull('billing.'.$timestampColumn)
            ->selectRaw('DATE(billing.'.$timestampColumn.') as event_date')
            ->selectRaw('COUNT(DISTINCT d.delivery_id) as total')
            ->groupBy('event_date')
            ->orderByDesc('event_date')
            ->limit(14)
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->event_date => (int) $row->total])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function deliveryVolumeByDate(array $filters): Collection
    {
        return $this->deliveredDeliveries($filters)
            ->select('d.delivery_date')
            ->selectRaw('COUNT(DISTINCT d.delivery_id) as total')
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'billed' THEN d.delivery_id END) as billed")
            ->groupBy('d.delivery_date')
            ->orderByDesc('d.delivery_date')
            ->limit(14)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function assignedPersonnel(array $filters): Collection
    {
        return $this->deliveredDeliveries($filters)
            ->join('users as submitter', 'submitter.user_id', '=', 'billing.submitted_for_billing_by')
            ->groupBy('submitter.user_id', 'submitter.name')
            ->select('submitter.user_id', 'submitter.name')
            ->selectRaw('COUNT(DISTINCT d.delivery_id) as submitted_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN billing.status = 'billed' THEN d.delivery_id END) as billed_count")
            ->orderByDesc('submitted_count')
            ->limit(10)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function recentBatches(array $filters): Collection
    {
        return $this->batchDeliveryScope($filters)
            ->groupBy('batch.id', 'batch.batch_ref', 'batch.status', 'batch.created_at', 'batch.created_by_name')
            ->select([
                'batch.id',
                'batch.batch_ref',
                'batch.status',
                'batch.created_at',
                'batch.created_by_name',
            ])
            ->selectRaw('COUNT(DISTINCT d.dr_no) as dr_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT p.project_name) as project_names')
            ->orderByDesc('batch.created_at')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function batchDeliveryScope(array $filters): Builder
    {
        return DB::table('billing_batches as batch')
            ->join('delivery_billing_records as billing', 'billing.billing_batch_id', '=', 'batch.id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->where('billing.status', '!=', DeliveryBillingRecord::REVOKED)
            ->whereIn('d.status', ['delivered', 'accepted'])
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnedBatchCount(array $filters): int
    {
        return $this->batchDeliveryScope($filters)
            ->where('batch.status', 'returned_for_correction')
            ->distinct()
            ->count('batch.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function returnedBatches(array $filters): Collection
    {
        return $this->batchDeliveryScope($filters)
            ->where('batch.status', 'returned_for_correction')
            ->groupBy('batch.id', 'batch.batch_ref', 'batch.created_at', 'batch.created_by_name')
            ->select(['batch.id', 'batch.batch_ref', 'batch.created_at', 'batch.created_by_name'])
            ->selectRaw('COUNT(DISTINCT d.dr_no) as dr_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT p.project_name) as project_names')
            ->orderByDesc('batch.created_at')
            ->limit(6)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function recentConfirmations(array $filters): Collection
    {
        return DB::table('delivery_billing_histories as history')
            ->join('delivery_billing_records as billing', 'billing.id', '=', 'history.delivery_billing_record_id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('users as actor', 'actor.user_id', '=', 'history.changed_by')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'history.invoice_id')
            ->where('history.new_status', DeliveryBillingRecord::BILLED)
            ->whereIn('d.status', ['delivered', 'accepted'])
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']))
            ->orderByDesc('history.created_at')
            ->orderByDesc('history.id')
            ->limit(6)
            ->get([
                'd.delivery_id',
                'd.dr_no',
                'p.project_name',
                'invoice.invoice_no',
                'history.created_at',
                DB::raw('COALESCE(history.changed_by_name, actor.name) as actor_name'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function recentActivity(array $filters): Collection
    {
        return DB::table('delivery_billing_histories as history')
            ->join('delivery_billing_records as billing', 'billing.id', '=', 'history.delivery_billing_record_id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
            ->leftJoin('users as actor', 'actor.user_id', '=', 'history.changed_by')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'history.invoice_id')
            ->whereIn('d.status', ['delivered', 'accepted'])
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']))
            ->orderByDesc('history.created_at')
            ->orderByDesc('history.id')
            ->limit(8)
            ->get([
                'history.previous_status',
                'history.new_status',
                'history.remarks',
                'history.created_at',
                DB::raw('COALESCE(history.changed_by_name, actor.name) as actor_name'),
                'd.delivery_id',
                'd.dr_no',
                'invoice.invoice_no',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function blockedDeliveries(array $filters): Collection
    {
        return $this->deliveredDeliveries($filters)
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->where('billing.status', DeliveryBillingRecord::ON_HOLD)
            ->orderByDesc('d.delivery_date')
            ->orderByDesc('d.delivery_id')
            ->limit(8)
            ->get(['d.delivery_id', 'd.dr_no', 'd.delivery_date', 'p.project_name', 'billing.billing_remarks']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function missingPodCount(array $filters): int
    {
        $proofs = DB::table('package_status as proof_status')
            ->join('delivery_proofs as proof', 'proof.package_status_id', '=', 'proof_status.package_status_id')
            ->select('proof_status.delivery_id')
            ->distinct();

        return $this->deliveredDeliveries($filters)
            ->leftJoinSub($proofs, 'pod', 'pod.delivery_id', '=', 'd.delivery_id')
            ->whereNull('pod.delivery_id')
            ->count('d.delivery_id');
    }
}
