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
        if (isset($filters['search'])) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('p.project_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('p.ref_no', 'like', '%'.$filters['search'].'%');
            });
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
     * Select expected allocations, including absent/null status as pending. Each
     * delivery takes exactly one path: keystage when present, otherwise lot.
     *
     * @param  array<string, mixed>  $filters
     */
    private function allocations(array $filters, string $key): Builder
    {
        $latestStatuses = DB::table('package_status as history')
            ->joinSub($this->deliveries($filters)->select('d.delivery_id'), 'status_deliveries', 'status_deliveries.delivery_id', '=', 'history.delivery_id')
            ->select(['history.delivery_id', 'history.package_id'])
            ->selectRaw('MAX(history.package_status_id) as package_status_id')
            ->groupBy('history.delivery_id', 'history.package_id');
        $query = $this->deliveries($filters)
            ->join('package as pk', 'pk.'.$key, '=', 'd.'.$key)
            ->leftJoinSub($latestStatuses, 'latest_status', function (JoinClause $join): void {
                $join->on('latest_status.delivery_id', '=', 'd.delivery_id')->on('latest_status.package_id', '=', 'pk.package_id');
            })
            ->leftJoin('package_status as ps', 'ps.package_status_id', '=', 'latest_status.package_status_id')
            ->select(['d.project_id', 'd.delivery_id', 'pk.package_id'])
            ->selectRaw($this->receiptExpression('d').' as dr_no')
            ->selectRaw("COALESCE(ps.status, 'pending') as package_status");
        if ($key === 'keystage_id') {
            $query->whereNotNull('d.keystage_id');
        } else {
            $query->whereNull('d.keystage_id');
        }

        return $query;
    }

    private function receiptExpression(string $alias): string
    {
        return $this->usesMysql() ? $alias.'.dr_no COLLATE utf8mb4_bin' : $alias.'.dr_no';
    }

    private function usesMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** Group only materialized receipt columns, whose collation was set before aggregation. */
    private function groupReceipts(Builder $query, string $alias): Builder
    {
        $query->groupBy($alias.'.project_id', $alias.'.dr_no');
        if ($this->usesMysql()) {
            $query->groupByRaw('OCTET_LENGTH('.$alias.'.dr_no)');
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function packageAllocations(array $filters): Builder
    {
        return $this->allocations($filters, 'keystage_id')->unionAll($this->allocations($filters, 'lot_id'));
    }

    private function countPackages(Builder $query, string $alias): Builder
    {
        $query->selectRaw('COUNT(*) as total_packages_count');
        foreach (self::PACKAGE_STATUSES as $status) {
            $query->selectRaw("COUNT(CASE WHEN {$alias}.package_status = ? THEN 1 END) as {$status}_packages_count", [$status]);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function receiptRows(array $filters): Builder
    {
        $deliveries = $this->deliveries($filters)
            ->select(['d.project_id', 'd.delivery_id', 'd.status', 'd.delivered_date'])
            ->selectRaw($this->receiptExpression('d').' as dr_no');
        $rows = DB::query()->fromSub($deliveries, 'dr')->select('dr.project_id', 'dr.dr_no')
            ->selectRaw('COUNT(*) as delivery_rows_count, MAX(dr.delivered_date) as last_delivery_date');
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $rows->selectRaw("COUNT(CASE WHEN dr.status = ? THEN 1 END) as {$column}_rows_count", [$status]);
        }

        return $this->groupReceipts($rows, 'dr');
    }

    private function matchReceipt(JoinClause $join, string $leftAlias, string $rightAlias): void
    {
        $join->on($leftAlias.'.project_id', '=', $rightAlias.'.project_id')
            ->on($leftAlias.'.dr_no', '=', $rightAlias.'.dr_no');
        if ($this->usesMysql()) {
            $join->whereRaw('OCTET_LENGTH('.$leftAlias.'.dr_no) = OCTET_LENGTH('.$rightAlias.'.dr_no)');
        }
    }

    /** @param array<string, mixed> $filters */
    private function receipts(array $filters): Builder
    {
        $packages = DB::query()->fromSub($this->packageAllocations($filters), 'a')->select('a.project_id', 'a.dr_no');
        $this->countPackages($packages, 'a');
        $this->groupReceipts($packages, 'a');
        $totals = DB::query()->fromSub($this->receiptRows($filters), 'dr')
            ->leftJoinSub($packages, 'pk', function (JoinClause $join): void {
                $this->matchReceipt($join, 'pk', 'dr');
            })->select('dr.*')->selectRaw('COALESCE(pk.total_packages_count, 0) as total_packages_count');
        foreach (self::PACKAGE_STATUSES as $status) {
            $totals->selectRaw("COALESCE(pk.{$status}_packages_count, 0) as {$status}_packages_count");
        }

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
    private function deliveryStats(array $filters): Builder
    {
        $receipts = $this->receipts($filters);
        $deliveryCounts = DB::query()->fromSub($receipts, 'receipt')->select('receipt.project_id')
            ->selectRaw('COUNT(*) as total_deliveries_count, SUM(receipt.delivery_rows_count) as delivery_rows_count, MAX(receipt.last_delivery_date) as last_delivery_date')
            ->selectRaw('COUNT(CASE WHEN receipt.total_packages_count > 0 AND receipt.delivered_packages_count + receipt.accepted_packages_count = receipt.total_packages_count THEN 1 WHEN receipt.total_packages_count = 0 AND receipt.delivered_rows_count + receipt.accepted_rows_count = receipt.delivery_rows_count THEN 1 END) as completed_deliveries_count')
            ->groupBy('receipt.project_id');
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $deliveryCounts->selectRaw("COUNT(CASE WHEN receipt.delivery_status = ? THEN 1 END) as {$column}_deliveries_count", [$status]);
        }

        return $deliveryCounts;
    }

    /** @param array<string, mixed> $filters */
    private function packageStats(array $filters): Builder
    {
        $query = DB::query()->fromSub($this->packageAllocations($filters), 'a');
        if (isset($filters['delivery_status'])) {
            $query->joinSub($this->receipts($filters)->select('receipt.project_id', 'receipt.dr_no'), 'selected_receipts', function (JoinClause $join): void {
                $this->matchReceipt($join, 'a', 'selected_receipts');
            });
        }

        return $this->countPackages($query->select('a.project_id'), 'a')->groupBy('a.project_id');
    }

    /** @param array<string, mixed> $filters */
    private function billingStats(array $filters): Builder
    {
        $receipts = isset($filters['delivery_status'])
            ? $this->receipts($filters)->select('receipt.project_id', 'receipt.dr_no')
            : DB::query()->fromSub($this->receiptRows($filters), 'dr')->select('dr.project_id', 'dr.dr_no');

        return DB::query()->fromSub($receipts, 'receipt')
            ->join('billing_grouped as bg', function (JoinClause $join): void {
                $join->on('bg.dr_no', '=', 'receipt.dr_no');
                if ($this->usesMysql()) {
                    $join->whereRaw('OCTET_LENGTH(bg.dr_no) = OCTET_LENGTH(receipt.dr_no)');
                }
            })
            ->join('grouping as g', 'g.group_id', '=', 'bg.group_id')
            ->select('receipt.project_id')->selectRaw('COUNT(*) as billing_groups_count')
            ->selectRaw("COUNT(CASE WHEN g.status = 'for billing' THEN 1 END) as for_billing_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'billed' THEN 1 END) as billed_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'paid' THEN 1 END) as paid_groups_count")
            ->groupBy('receipt.project_id');
    }

    /** Each join is already reduced to at most one row per project. @param array<string, mixed> $filters */
    public function recordsQuery(array $filters): Builder
    {
        $lotNames = $this->deliveries($filters)
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->select('d.project_id')
            ->selectRaw('GROUP_CONCAT(DISTINCT l.lot_name) as lot_names')
            ->groupBy('d.project_id');
        $query = $this->projects($filters)
            ->leftJoinSub($this->deliveryStats($filters), 'delivery_stats', 'delivery_stats.project_id', '=', 'p.project_id')
            ->leftJoinSub($this->packageStats($filters), 'package_stats', 'package_stats.project_id', '=', 'p.project_id')
            ->leftJoinSub($this->billingStats($filters), 'billing_stats', 'billing_stats.project_id', '=', 'p.project_id')
            ->leftJoinSub($lotNames, 'project_lots', 'project_lots.project_id', '=', 'p.project_id')
            ->select(['p.project_id', 'p.project_name', 'p.ref_no', 'p.status as project_status', 'p.start_date', 'p.end_date', 'delivery_stats.last_delivery_date', 'project_lots.lot_names']);
        if (array_intersect(array_keys($filters), ['year', 'region', 'division', 'municipality', 'delivery_status'])) {
            $query->whereNotNull('delivery_stats.project_id');
        }
        foreach (array_merge(['total_deliveries_count', 'completed_deliveries_count', 'delivery_rows_count'], array_map(fn (string $status): string => str_replace(' ', '_', $status).'_deliveries_count', self::DELIVERY_STATUSES)) as $column) {
            $query->selectRaw("COALESCE(delivery_stats.{$column}, 0) as {$column}");
        }
        foreach (array_merge(['total_packages_count'], array_map(fn (string $status): string => $status.'_packages_count', self::PACKAGE_STATUSES)) as $column) {
            $query->selectRaw("COALESCE(package_stats.{$column}, 0) as {$column}");
        }
        foreach (['billing_groups_count', 'for_billing_groups_count', 'billed_groups_count', 'paid_groups_count'] as $column) {
            $query->selectRaw("COALESCE(billing_stats.{$column}, 0) as {$column}");
        }

        $sortColumns = [
            'project' => 'p.project_name',
            'total_drs' => 'total_deliveries_count',
            'total_dr_packages' => 'total_packages_count',
            'last_delivery' => 'delivery_stats.last_delivery_date',
            'end_date' => 'p.end_date',
        ];
        $sort = $filters['sort'] ?? null;
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        if ($sort === 'progress') {
            $query->orderByRaw('(COALESCE(package_stats.delivered_packages_count, 0) + COALESCE(package_stats.accepted_packages_count, 0)) * 1.0 / NULLIF(package_stats.total_packages_count, 0) '.$direction);
        } elseif (isset($sortColumns[$sort])) {
            $query->orderBy($sortColumns[$sort], $direction);
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
        $projects = $records->map(function (object $record): array {
            $project = (array) $record;
            foreach ($project as $column => $value) {
                if (str_ends_with($column, '_count') || $column === 'project_id') {
                    $project[$column] = (int) $value;
                }
            }
            $project['is_active'] = ! in_array(strtolower($project['project_status'] ?? ''), array_map('strtolower', self::INACTIVE_PROJECT_STATUSES), true);
            $project['completed_packages_count'] = $project['delivered_packages_count'] + $project['accepted_packages_count'];
            $project['remaining_packages_count'] = $project['total_packages_count'] - $project['completed_packages_count'];
            $project['remaining_deliveries_count'] = $project['total_deliveries_count'] - $project['completed_deliveries_count'] - $project['cancelled_deliveries_count'];
            $project['total_package_allocations_count'] = $project['total_packages_count'];
            $project['progress_basis'] = $project['total_packages_count'] > 0 ? 'package_allocations' : 'no_package_allocations';
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
        foreach (['total_deliveries_count', 'delivery_rows_count', 'total_packages_count', 'total_package_allocations_count', 'completed_packages_count', 'remaining_packages_count', 'pending_packages_count', 'released_packages_count', 'delivered_packages_count', 'accepted_packages_count', 'completed_deliveries_count', 'pending_deliveries_count', 'released_deliveries_count', 'delivered_deliveries_count', 'accepted_deliveries_count', 'cancelled_deliveries_count'] as $column) {
            $summary[$column] ??= 0;
        }
        $summary['progress_basis'] = $summary['total_packages_count'] > 0 ? 'package_allocations' : 'no_package_allocations';
        $summary['delivery_progress_percent'] = $this->deliveryProgress($summary);

        return ['projects' => $projects, 'summary' => $summary, 'definitions' => $this->definitions()];
    }

    /** @param array<string, mixed> $counts */
    private function deliveryProgress(array $counts): ?float
    {
        return $this->percentage($counts['completed_packages_count'], $counts['total_packages_count']);
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
            'package_allocations' => 'Each delivery_id + package_id is one DR package allocation. Use delivery keystage when present, otherwise lot. Repeated package definitions across delivery records count as separate allocations. The newest package_status_id supplies the status if duplicate status rows exist; missing/null status is pending.',
            'delivery_progress' => '(Delivered + accepted DR package allocations) / total DR package allocations x 100. Zero allocations means unavailable, displayed as No package allocations.',
            'completed_deliveries' => 'Receipts whose expected allocations are all delivered or accepted, including mixed delivered/accepted receipts. Without package definitions, all receipt delivery rows must be delivered or accepted.',
            'billing_progress' => '(Billed + paid recorded billing groups) / all recorded billing groups matching exact DR receipts x 100. Paid is a later stage than billed; no groups means unavailable.',
            'year' => 'Scheduled delivery_date year, consistent with delivery tracking. Location and delivery-status filters restrict the receipts and their allocations counted.',
            'active_projects' => 'Excludes Delivered, For Billing, For Collection, Collected, Cancelled and Dropped projects. Completed is labelled Awarded in the existing project editor and remains active. Set active_only=0 to include all projects.',
            'last_delivery_date' => 'Latest recorded delivered_date in the filtered delivery rows; absent dates remain null.',
        ];
    }
}
