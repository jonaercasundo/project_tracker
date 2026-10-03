<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectDeliveryProgressService
{
    /** Completed is labelled Awarded in the project editor, so it remains active. */
    private const INACTIVE_PROJECT_STATUSES = ['Delivered', 'For Billing', 'For Collection', 'Collected', 'Cancelled', 'Dropped'];

    private const PACKAGE_STATUSES = ['pending', 'released', 'delivered', 'accepted', 'warehouse'];

    private const DELIVERY_STATUSES = ['pending', 'released', 'delivered', 'accepted', 'warehouse', 'mixed', 'cancelled', 'for approval'];

    /** @param array<string, mixed> $filters */
    private function projects(array $filters): Builder
    {
        $query = DB::table('projects as p');
        if ((bool) ($filters['active_only'] ?? true)) {
            $query->whereNotIn('p.status', self::INACTIVE_PROJECT_STATUSES);
        }
        if (isset($filters['project_id'])) {
            $query->where('p.project_id', $filters['project_id']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function deliveries(array $filters): Builder
    {
        $query = DB::table('deliveries as d')
            ->joinSub($this->projects($filters)->select('p.project_id'), 'selected_projects', 'selected_projects.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id');
        if (isset($filters['year'])) {
            $query->where('d.delivery_date', '>=', $filters['year'].'-01-01')
                ->where('d.delivery_date', '<', ((int) $filters['year'] + 1).'-01-01');
        }
        foreach (['region', 'division', 'municipality'] as $column) {
            if (isset($filters[$column])) {
                $query->where('s.'.$column, $filters[$column]);
            }
        }

        return $query;
    }

    /**
     * Count expected allocations, including absent/null status as pending. Each
     * delivery takes exactly one path: keystage when present, otherwise lot.
     *
     * @param  array<string, mixed>  $filters
     */
    private function receiptAllocations(array $filters, string $key): Builder
    {
        $query = $this->deliveries($filters)
            ->leftJoin('package as pk', 'pk.'.$key, '=', 'd.'.$key)
            ->leftJoin('package_status as ps', function (JoinClause $join): void {
                $join->on('ps.delivery_id', '=', 'd.delivery_id')->on('ps.package_id', '=', 'pk.package_id');
            })
            ->select('d.project_id')->selectRaw($this->receiptExpression('d').' as dr_no')
            ->selectRaw('COUNT(DISTINCT d.delivery_id) as delivery_rows_count, COUNT(pk.package_id) as total_packages_count, MAX(d.delivered_date) as last_delivery_date');
        if ($key === 'keystage_id') {
            $query->whereNotNull('d.keystage_id');
        } else {
            $query->whereNull('d.keystage_id');
        }
        foreach (self::PACKAGE_STATUSES as $status) {
            $query->selectRaw("COUNT(CASE WHEN pk.package_id IS NOT NULL AND COALESCE(ps.status, 'pending') = ? THEN 1 END) as {$status}_packages_count", [$status]);
        }
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $query->selectRaw("COUNT(DISTINCT CASE WHEN d.status = ? THEN d.delivery_id END) as {$column}_rows_count", [$status]);
        }

        return $this->groupReceipts($query, 'd');
    }

    private function receiptExpression(string $alias): string
    {
        return DB::connection()->getDriverName() === 'mysql' ? $alias.'.dr_no COLLATE utf8mb4_bin' : $alias.'.dr_no';
    }

    private function groupReceipts(Builder $query, string $alias): Builder
    {
        $query->groupBy($alias.'.project_id')->groupByRaw($this->receiptExpression($alias));
        if (DB::connection()->getDriverName() === 'mysql') {
            $query->groupByRaw('OCTET_LENGTH('.$alias.'.dr_no)');
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function receipts(array $filters): Builder
    {
        $allocations = $this->receiptAllocations($filters, 'keystage_id')
            ->unionAll($this->receiptAllocations($filters, 'lot_id'));
        $totals = DB::query()->fromSub($allocations, 'a')->select('a.project_id', 'a.dr_no')
            ->selectRaw('SUM(a.delivery_rows_count) as delivery_rows_count, SUM(a.total_packages_count) as total_packages_count, MAX(a.last_delivery_date) as last_delivery_date');
        foreach (self::PACKAGE_STATUSES as $status) {
            $totals->selectRaw("SUM(a.{$status}_packages_count) as {$status}_packages_count");
        }
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $totals->selectRaw("SUM(a.{$column}_rows_count) as {$column}_rows_count");
        }
        $this->groupReceipts($totals, 'a');

        $statusExpression = 'CASE WHEN r.total_packages_count > 0 THEN CASE';
        foreach (self::PACKAGE_STATUSES as $status) {
            $statusExpression .= " WHEN r.{$status}_packages_count = r.total_packages_count THEN '{$status}'";
        }
        $statusExpression .= " ELSE 'mixed' END ELSE CASE";
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $statusExpression .= " WHEN r.{$column}_rows_count = r.delivery_rows_count THEN '{$status}'";
        }
        $statusExpression .= " ELSE 'mixed' END END";
        $receipts = DB::query()->fromSub($totals, 'r')->select('r.*')->selectRaw($statusExpression.' as delivery_status');
        $query = DB::query()->fromSub($receipts, 'receipt')->select('receipt.*');
        if (isset($filters['delivery_status'])) {
            $query->where('receipt.delivery_status', $filters['delivery_status']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function recordsQuery(array $filters): Builder
    {
        $receipts = $this->receipts($filters);
        $deliveryCounts = DB::query()->fromSub($receipts, 'receipt')->select('receipt.project_id')
            ->selectRaw('COUNT(*) as total_deliveries_count, SUM(receipt.delivery_rows_count) as delivery_rows_count, SUM(receipt.total_packages_count) as total_packages_count, MAX(receipt.last_delivery_date) as last_delivery_date')
            ->selectRaw('COUNT(CASE WHEN receipt.total_packages_count > 0 AND receipt.delivered_packages_count + receipt.accepted_packages_count = receipt.total_packages_count THEN 1 WHEN receipt.total_packages_count = 0 AND receipt.delivered_rows_count + receipt.accepted_rows_count = receipt.delivery_rows_count THEN 1 END) as completed_deliveries_count')
            ->groupBy('receipt.project_id');
        foreach (self::PACKAGE_STATUSES as $status) {
            $deliveryCounts->selectRaw("SUM(receipt.{$status}_packages_count) as {$status}_packages_count");
        }
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $deliveryCounts->selectRaw("COUNT(CASE WHEN receipt.delivery_status = ? THEN 1 END) as {$column}_deliveries_count", [$status]);
        }
        $query = $this->projects($filters)
            ->leftJoinSub($deliveryCounts, 'progress', 'progress.project_id', '=', 'p.project_id')
            ->select(['p.project_id', 'p.project_name', 'p.ref_no', 'p.status as project_status', 'p.start_date', 'p.end_date', 'progress.last_delivery_date']);
        if (array_intersect(array_keys($filters), ['year', 'region', 'division', 'municipality', 'delivery_status'])) {
            $query->whereNotNull('progress.project_id');
        }
        foreach (array_merge(['total_deliveries_count', 'completed_deliveries_count', 'delivery_rows_count', 'total_packages_count'], array_map(fn (string $status): string => $status.'_packages_count', self::PACKAGE_STATUSES), array_map(fn (string $status): string => str_replace(' ', '_', $status).'_deliveries_count', self::DELIVERY_STATUSES)) as $column) {
            $query->selectRaw("COALESCE(progress.{$column}, 0) as {$column}");
        }

        return $query->orderBy('p.project_id');
    }

    /**
     * One project set and a fixed number of grouped queries; never query per project.
     * Billing receipts are deduplicated before joining, so multiple delivery rows
     * sharing a DR do not multiply billing counts.
     *
     * @param  array<string, mixed>  $filters
     * @return array{projects: Collection<int, array<string, mixed>>, summary: array<string, mixed>, definitions: array<string, string>}
     */
    public function report(array $filters): array
    {
        $records = $this->recordsQuery($filters)->get();
        $receipts = $this->deliveries($filters)->select('d.project_id')->selectRaw($this->receiptExpression('d').' as dr_no');
        $this->groupReceipts($receipts, 'd');
        if (isset($filters['delivery_status'])) {
            $receipts = $this->receipts($filters)->select('receipt.project_id', 'receipt.dr_no');
        }
        $billing = DB::query()->fromSub($receipts, 'receipt')
            ->join('billing_grouped as bg', function (JoinClause $join): void {
                $join->on('bg.dr_no', '=', 'receipt.dr_no');
                if (DB::connection()->getDriverName() === 'mysql') {
                    $join->whereRaw('OCTET_LENGTH(bg.dr_no) = OCTET_LENGTH(receipt.dr_no)');
                }
            })
            ->join('grouping as g', 'g.group_id', '=', 'bg.group_id')
            ->select('receipt.project_id')->selectRaw('COUNT(*) as billing_groups_count')
            ->selectRaw("COUNT(CASE WHEN g.status = 'for billing' THEN 1 END) as for_billing_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'billed' THEN 1 END) as billed_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'paid' THEN 1 END) as paid_groups_count")
            ->groupBy('receipt.project_id')->get()->keyBy('project_id');
        $projects = $records->map(function (object $record) use ($billing): array {
            $project = (array) $record;
            foreach ($project as $column => $value) {
                if (str_ends_with($column, '_count') || $column === 'project_id') {
                    $project[$column] = (int) $value;
                }
            }
            foreach (['billing_groups_count', 'for_billing_groups_count', 'billed_groups_count', 'paid_groups_count'] as $column) {
                $project[$column] = (int) ($billing->get($record->project_id)?->{$column} ?? 0);
            }
            $project['is_active'] = ! in_array(strtolower($project['project_status'] ?? ''), array_map('strtolower', self::INACTIVE_PROJECT_STATUSES), true);
            $project['completed_packages_count'] = $project['delivered_packages_count'] + $project['accepted_packages_count'];
            $project['remaining_packages_count'] = $project['total_packages_count'] - $project['completed_packages_count'];
            $project['remaining_deliveries_count'] = $project['total_deliveries_count'] - $project['completed_deliveries_count'] - $project['cancelled_deliveries_count'];
            $project['progress_basis'] = $project['total_packages_count'] > 0 ? 'package_allocations' : 'delivery_receipts';
            $project['delivery_progress_percent'] = $this->deliveryProgress($project);
            $project['billing_progress_percent'] = $this->percentage($project['billed_groups_count'] + $project['paid_groups_count'], $project['billing_groups_count']);

            return $project;
        });
        $summary = ['projects_count' => $projects->count(), 'active_projects_count' => $projects->where('is_active', true)->count()];
        foreach (array_keys($projects->first() ?? []) as $column) {
            if (str_ends_with($column, '_count')) {
                $summary[$column] = (int) $projects->sum($column);
            }
        }
        foreach (['total_deliveries_count', 'delivery_rows_count', 'total_packages_count', 'completed_packages_count', 'completed_deliveries_count', 'pending_deliveries_count', 'released_deliveries_count', 'delivered_deliveries_count', 'accepted_deliveries_count', 'cancelled_deliveries_count'] as $column) {
            $summary[$column] ??= 0;
        }
        $summary['progress_basis'] = $summary['total_packages_count'] > 0 ? 'package_allocations' : 'delivery_receipts';
        $summary['delivery_progress_percent'] = $this->deliveryProgress($summary);

        return ['projects' => $projects, 'summary' => $summary, 'definitions' => $this->definitions()];
    }

    /** @param array<string, mixed> $counts */
    private function deliveryProgress(array $counts): ?float
    {
        return $counts['total_packages_count'] > 0
            ? $this->percentage($counts['completed_packages_count'], $counts['total_packages_count'])
            : $this->percentage($counts['completed_deliveries_count'], $counts['total_deliveries_count'] - $counts['cancelled_deliveries_count']);
    }

    private function percentage(int $completed, int $total): ?float
    {
        return $total > 0 ? round($completed / $total * 100, 2) : null;
    }

    /** @return array<string, string> */
    public function definitions(): array
    {
        return [
            'deliveries' => 'Distinct exact DR receipts per project, rather than delivery rows. Receipt status is uniform expected package status, otherwise mixed; delivery row status is the fallback when no packages are defined.',
            'delivery_progress' => '(Delivered + accepted expected package allocations) / all expected allocations x 100. Missing/null package status is pending. Without packages, use delivered + accepted receipts / non-cancelled receipts. No denominator means unavailable.',
            'completed_deliveries' => 'Receipts whose expected allocations are all delivered or accepted, including mixed delivered/accepted receipts. Without package definitions, all receipt delivery rows must be delivered or accepted.',
            'billing_progress' => '(Billed + paid recorded billing groups) / all recorded billing groups matching exact DR receipts x 100. Paid is a later stage than billed; no groups means unavailable.',
            'year' => 'Scheduled delivery_date year, consistent with delivery tracking. Location and delivery-status filters restrict the receipts and their allocations counted.',
            'active_projects' => 'Excludes Delivered, For Billing, For Collection, Collected, Cancelled and Dropped projects. Completed is labelled Awarded in the existing project editor and remains active. Set active_only=0 to include all projects.',
            'last_delivery_date' => 'Latest recorded delivered_date in the filtered delivery rows; absent dates remain null.',
        ];
    }
}
