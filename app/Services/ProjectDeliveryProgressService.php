<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
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
        if (isset($filters['project_ids'])) {
            $query->whereIn('p.project_id', $filters['project_ids']);
        }
        if (isset($filters['status'])) {
            $query->where('p.status', $filters['status']);
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
        if (isset($filters['lot_id'])) {
            $query->where('d.lot_id', $filters['lot_id']);
        }

        return $query;
    }

    /**
     * Select expected allocations, including absent/null status as pending. Each
     * delivery takes exactly one path: keystage when present, otherwise lot.
     *
     * @param  array<string, mixed>  $filters
     */
    private function allocations(array $filters, string $key, bool $includeStatus = true): Builder
    {
        $query = $this->deliveries($filters)
            ->join('package as pk', 'pk.'.$key, '=', 'd.'.$key)
            ->select(['d.project_id', 'd.delivery_id', 'd.package_qty', 'pk.package_id'])
            ->selectRaw($this->receiptExpression('d').' as dr_no');
        if ($includeStatus) {
            $latestStatuses = DB::table('package_status as history')
                ->joinSub($this->deliveries($filters)->select('d.delivery_id'), 'status_deliveries', 'status_deliveries.delivery_id', '=', 'history.delivery_id')
                ->select(['history.delivery_id', 'history.package_id'])
                ->selectRaw('MAX(history.package_status_id) as package_status_id')
                ->groupBy('history.delivery_id', 'history.package_id');
            $query->leftJoinSub($latestStatuses, 'latest_status', function (JoinClause $join): void {
                $join->on('latest_status.delivery_id', '=', 'd.delivery_id')->on('latest_status.package_id', '=', 'pk.package_id');
            })->leftJoin('package_status as ps', 'ps.package_status_id', '=', 'latest_status.package_status_id')
                ->selectRaw("COALESCE(ps.status, 'pending') as package_status");
        }
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
    private function packageAllocations(array $filters, bool $includeStatus = true): Builder
    {
        return $this->allocations($filters, 'keystage_id', $includeStatus)->unionAll($this->allocations($filters, 'lot_id', $includeStatus));
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
            ->select(['d.project_id', 'd.delivery_id', 'd.status', 'd.delivered_date', 'd.accepted_date', 'd.created_at'])
            ->selectRaw($this->receiptExpression('d').' as dr_no');
        $rows = DB::query()->fromSub($deliveries, 'dr')->select('dr.project_id', 'dr.dr_no')
            ->selectRaw('COUNT(*) as delivery_rows_count, MAX(dr.delivered_date) as last_delivery_date, MAX(dr.accepted_date) as last_accepted_date, MAX(dr.created_at) as last_dr_date');
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
            ->selectRaw('COUNT(*) as total_deliveries_count, SUM(receipt.delivery_rows_count) as delivery_rows_count, MAX(receipt.last_delivery_date) as last_delivery_date, MAX(receipt.last_accepted_date) as last_accepted_date, MAX(receipt.last_dr_date) as last_dr_date')
            ->selectRaw('COUNT(CASE WHEN receipt.total_packages_count > 0 AND receipt.delivered_packages_count + receipt.accepted_packages_count = receipt.total_packages_count THEN 1 WHEN receipt.total_packages_count = 0 AND receipt.delivered_rows_count + receipt.accepted_rows_count = receipt.delivery_rows_count THEN 1 END) as completed_deliveries_count')
            ->groupBy('receipt.project_id');
        foreach (self::DELIVERY_STATUSES as $status) {
            $column = str_replace(' ', '_', $status);
            $deliveryCounts->selectRaw("COUNT(CASE WHEN receipt.delivery_status = ? THEN 1 END) as {$column}_deliveries_count", [$status]);
        }
        $deliveryCounts->selectRaw('SUM(receipt.total_packages_count) as total_packages_count');
        foreach (self::PACKAGE_STATUSES as $status) {
            $deliveryCounts->selectRaw("SUM(receipt.{$status}_packages_count) as {$status}_packages_count");
        }

        return $deliveryCounts;
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
            ->selectRaw('MAX(bg.created_at) as last_billing_date')
            ->selectRaw("COUNT(CASE WHEN g.status = 'for billing' THEN 1 END) as for_billing_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'billed' THEN 1 END) as billed_groups_count")
            ->selectRaw("COUNT(CASE WHEN g.status = 'paid' THEN 1 END) as paid_groups_count")
            ->groupBy('receipt.project_id');
    }

    /** Each join is already reduced to at most one row per project. @param array<string, mixed> $filters */
    public function recordsQuery(array $filters): Builder
    {
        $query = $this->projects($filters)
            ->leftJoinSub($this->deliveryStats($filters), 'delivery_stats', 'delivery_stats.project_id', '=', 'p.project_id')
            ->leftJoinSub($this->billingStats($filters), 'billing_stats', 'billing_stats.project_id', '=', 'p.project_id')
            ->select(['p.project_id', 'p.project_name', 'p.ref_no', 'p.status as project_status', 'p.start_date', 'p.end_date', 'delivery_stats.last_delivery_date', 'delivery_stats.last_accepted_date', 'delivery_stats.last_dr_date', 'billing_stats.last_billing_date']);
        if (! (bool) ($filters['compact'] ?? false)) {
            $lotNames = $this->deliveries($filters)
                ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
                ->select('d.project_id')->selectRaw('GROUP_CONCAT(DISTINCT l.lot_name) as lot_names')
                ->groupBy('d.project_id');
            $query->leftJoinSub($lotNames, 'project_lots', 'project_lots.project_id', '=', 'p.project_id')->addSelect('project_lots.lot_names');
        }
        if (array_intersect(array_keys($filters), ['year', 'region', 'division', 'municipality', 'delivery_status', 'lot_id'])) {
            $query->whereNotNull('delivery_stats.project_id');
        }
        foreach (array_merge(['total_deliveries_count', 'completed_deliveries_count', 'delivery_rows_count'], array_map(fn (string $status): string => str_replace(' ', '_', $status).'_deliveries_count', self::DELIVERY_STATUSES)) as $column) {
            $query->selectRaw("COALESCE(delivery_stats.{$column}, 0) as {$column}");
        }
        foreach (array_merge(['total_packages_count'], array_map(fn (string $status): string => $status.'_packages_count', self::PACKAGE_STATUSES)) as $column) {
            $query->selectRaw("COALESCE(delivery_stats.{$column}, 0) as {$column}");
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
            $query->orderByRaw('(COALESCE(delivery_stats.delivered_packages_count, 0) + COALESCE(delivery_stats.accepted_packages_count, 0)) * 1.0 / NULLIF(delivery_stats.total_packages_count, 0) '.$direction);
        } elseif (isset($sortColumns[$sort])) {
            $query->orderBy($sortColumns[$sort], $direction);
        }

        return $query->orderBy('p.project_id');
    }

    /** @param array<string, mixed> $filters */
    public function warehouseItemsQuery(array $filters): Builder
    {
        $projectFilters = array_intersect_key($filters, array_flip(['project_id', 'project_ids', 'active_only', 'search', 'status']));
        $requirements = DB::query()->fromSub($this->packageAllocations($projectFilters, false), 'allocation')
            ->join('package_content as content', 'content.package_id', '=', 'allocation.package_id')
            ->select(['allocation.project_id', 'content.item_id'])
            ->selectRaw('SUM(CASE WHEN allocation.package_qty > 0 THEN allocation.package_qty * CASE WHEN content.qty > 0 THEN content.qty ELSE 1 END ELSE 0 END) as required_quantity')
            ->selectRaw('SUM(CASE WHEN allocation.package_qty > 0 THEN 0 ELSE 1 END) as missing_quantity_count')
            ->groupBy('allocation.project_id', 'content.item_id');
        $history = DB::table('inventory_history as h')->select('h.item_id')
            ->join('item as history_item', 'history_item.item_id', '=', 'h.item_id')
            ->joinSub($this->projects($projectFilters)->select('p.project_id'), 'history_projects', 'history_projects.project_id', '=', 'history_item.project_id')
            ->selectRaw("SUM(CASE WHEN h.change_type = 'stock_in' THEN 1.0 * h.new_qty - h.old_qty ELSE 0 END) as stock_in_quantity")
            ->selectRaw("SUM(CASE WHEN h.change_type = 'stock_out' THEN 1.0 * h.old_qty - h.new_qty ELSE 0 END) as stock_out_quantity")
            ->selectRaw("SUM(CASE WHEN h.change_type = 'insert' THEN 1.0 * h.new_qty - h.old_qty ELSE 0 END) as opening_quantity")
            ->selectRaw('SUM(1.0 * h.new_qty - h.old_qty) as history_balance')
            ->selectRaw('MAX(h.changed_at) as last_inventory_date')
            ->groupBy('h.item_id');
        $balances = DB::table('inventory as inv')->select('inv.item_id')
            ->join('item as balance_item', 'balance_item.item_id', '=', 'inv.item_id')
            ->joinSub($this->projects($projectFilters)->select('p.project_id'), 'balance_projects', 'balance_projects.project_id', '=', 'balance_item.project_id')
            ->selectRaw('SUM(inv.qty) as current_stock')->groupBy('inv.item_id');

        return DB::table('item as i')
            ->joinSub($this->projects($projectFilters)->select('p.project_id'), 'inventory_projects', 'inventory_projects.project_id', '=', 'i.project_id')
            ->leftJoinSub($requirements, 'requirements', function (JoinClause $join): void {
                $join->on('requirements.project_id', '=', 'i.project_id')->on('requirements.item_id', '=', 'i.item_id');
            })
            ->leftJoinSub($history, 'history', 'history.item_id', '=', 'i.item_id')
            ->leftJoinSub($balances, 'balance', 'balance.item_id', '=', 'i.item_id')
            ->select(['i.project_id', 'i.item_id', 'i.item_name', 'i.unit', 'history.last_inventory_date'])
            ->selectRaw('COALESCE(requirements.required_quantity, 0) as required, COALESCE(requirements.missing_quantity_count, 0) as missing_quantity_count')
            ->selectRaw('COALESCE(history.stock_in_quantity, 0) as stock_in, COALESCE(history.stock_out_quantity, 0) as stock_out, COALESCE(history.opening_quantity, 0) as opening')
            ->selectRaw('COALESCE(history.history_balance, 0) as history_balance, COALESCE(balance.current_stock, 0) as available')
            ->orderBy('i.item_id');
    }

    /** @param array<string, mixed> $filters */
    private function warehouseItemTotals(array $filters): Builder
    {
        $unitExpression = "CASE WHEN unit IS NULL OR LENGTH(unit) = 0 OR (LENGTH(unit) = 1 AND unit = '0') THEN 'Unit unspecified' ELSE unit END";
        if ($this->usesMysql()) {
            $unitExpression .= ' COLLATE utf8mb4_bin';
        }

        return DB::query()->fromSub($this->warehouseItemsQuery($filters)->reorder(), 'item_totals')
            ->select('item_totals.*')
            ->selectRaw($unitExpression.' as quantity_unit')
            ->selectRaw('CASE WHEN available < 0 THEN 0 WHEN available > required THEN required ELSE available END as covered')
            ->selectRaw('CASE WHEN missing_quantity_count = 0 AND required > 0 THEN ROUND(100.0 * CASE WHEN available < 0 THEN 0 WHEN available > required THEN required ELSE available END / required, 2) ELSE NULL END as percent')
            ->selectRaw('available - history_balance as balance_difference');
    }

    /** Return only one summary row per project/unit, never inventory item records. @param array<string, mixed> $filters */
    public function warehouseSummaryQuery(array $filters): Builder
    {
        $query = DB::query()->fromSub($this->warehouseItemTotals($filters), 'w')
            ->select('w.project_id', 'w.quantity_unit as unit')
            ->selectRaw('SUM(w.missing_quantity_count) as missing_quantity_count, MAX(w.last_inventory_date) as last_inventory_date')
            ->selectRaw('SUM(CASE WHEN ABS(w.balance_difference) > 0.00001 THEN 1 ELSE 0 END) as balance_mismatch_count')
            ->selectRaw('SUM(CASE WHEN w.available < 0 OR w.stock_in < 0 OR w.stock_out < 0 THEN 1 ELSE 0 END) as negative_quantity_count')
            ->groupBy('w.project_id', 'w.quantity_unit')->orderByRaw('MIN(w.item_id)');
        if ($this->usesMysql()) {
            $query->groupByRaw('OCTET_LENGTH(w.quantity_unit)');
        }
        foreach (['required', 'stock_in', 'stock_out', 'opening', 'history_balance', 'available', 'covered', 'balance_difference'] as $column) {
            $query->selectRaw('SUM(w.'.$column.') as '.$column);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function detailRecords(array $filters, string $section, int $perPage, int $page): LengthAwarePaginator
    {
        abort_unless($this->projects($filters)->exists(), 404);
        if ($section === 'warehouse') {
            $query = $this->warehouseItemTotals($filters)->orderBy('item_totals.item_id');
        } elseif ($section === 'stock-out') {
            $query = DB::table('inventory_history as h')->join('item as i', 'i.item_id', '=', 'h.item_id')
                ->where('i.project_id', $filters['project_id'])->where('h.change_type', 'stock_out')
                ->select(['h.history_id', 'i.item_name', 'i.unit', 'h.warehouse_id', 'h.old_qty', 'h.new_qty', 'h.changed_at'])
                ->selectRaw('1.0 * h.old_qty - h.new_qty as stock_out')->orderByDesc('h.history_id');
        } elseif ($section === 'delivered') {
            $query = DB::query()->fromSub($this->packageAllocations($filters), 'a')
                ->whereIn('a.package_status', ['delivered', 'accepted'])->select('a.*');
            if (isset($filters['delivery_status'])) {
                $query->joinSub($this->receipts($filters)->select('receipt.project_id', 'receipt.dr_no'), 'selected_receipts', function (JoinClause $join): void {
                    $this->matchReceipt($join, 'a', 'selected_receipts');
                });
            }
            $query->orderBy('a.delivery_id')->orderBy('a.package_id');
        } elseif ($section === 'billing') {
            $receipts = isset($filters['delivery_status'])
                ? $this->receipts($filters)->select('receipt.project_id', 'receipt.dr_no')
                : DB::query()->fromSub($this->receiptRows($filters), 'dr')->select('dr.project_id', 'dr.dr_no');
            $query = DB::query()->fromSub($receipts, 'receipt')->join('billing_grouped as bg', function (JoinClause $join): void {
                $join->on('bg.dr_no', '=', 'receipt.dr_no');
                if ($this->usesMysql()) {
                    $join->whereRaw('OCTET_LENGTH(bg.dr_no) = OCTET_LENGTH(receipt.dr_no)');
                }
            })->join('grouping as g', 'g.group_id', '=', 'bg.group_id')
                ->select(['receipt.dr_no', 'bg.group_id', 'g.status', 'bg.created_at'])->orderBy('receipt.dr_no')->orderBy('bg.group_id');
        } else {
            $query = $this->receipts($filters)->orderBy('receipt.dr_no');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /** @param Collection<int, object> $rows @return array<string, mixed> */
    private function warehouseReadiness(Collection $rows): array
    {
        $items = $rows->map(function (object $row): array {
            $item = (array) $row;
            foreach (['project_id', 'missing_quantity_count', 'balance_mismatch_count', 'negative_quantity_count'] as $key) {
                $item[$key] = (int) $item[$key];
            }
            foreach (['required', 'stock_in', 'stock_out', 'opening', 'history_balance', 'available', 'covered', 'balance_difference'] as $key) {
                $item[$key] = (float) $item[$key];
            }

            return $item;
        });
        $units = $items->groupBy(fn (array $item): string => $item['unit'] ?: 'Unit unspecified')->map(function (Collection $items, string $unit): array {
            $totals = ['unit' => $unit];
            foreach (['required', 'stock_in', 'stock_out', 'opening', 'history_balance', 'available', 'covered', 'balance_difference'] as $key) {
                $totals[$key] = $items->sum($key);
            }
            $totals['requirements_complete'] = (int) $items->sum('missing_quantity_count') === 0;
            $totals['percent'] = $totals['requirements_complete'] && $totals['required'] > 0 ? round($totals['covered'] / $totals['required'] * 100, 2) : null;
            $totals['stock_out_percent'] = $totals['requirements_complete'] && $totals['required'] > 0 ? round($totals['stock_out'] / $totals['required'] * 100, 2) : null;

            return $totals;
        })->values();
        $totals = $units->count() === 1 ? $units->first() : [
            'unit' => $units->isEmpty() ? 'item units' : 'mixed item units',
            'required' => $units->isEmpty() ? 0 : null, 'stock_in' => $units->isEmpty() ? 0 : null,
            'stock_out' => $units->isEmpty() ? 0 : null, 'opening' => $units->isEmpty() ? 0 : null,
            'available' => $units->isEmpty() ? 0 : null, 'covered' => $units->isEmpty() ? 0 : null,
            'history_balance' => $units->isEmpty() ? 0 : null, 'balance_difference' => $units->isEmpty() ? 0 : null,
            'percent' => null, 'stock_out_percent' => null, 'requirements_complete' => $units->isEmpty(),
        ];
        $flags = [];
        if ($items->sum('balance_mismatch_count') > 0) {
            $flags[] = 'Inventory balances differ from recorded history; inspect opening stock or unlogged adjustments.';
        }
        if ($items->contains(fn (array $item): bool => $item['missing_quantity_count'] > 0)) {
            $flags[] = 'Some DR item requirements lack a positive package quantity.';
        }
        if ($items->sum('negative_quantity_count') > 0) {
            $flags[] = 'Inventory contains negative balances or reversed stock transaction quantities.';
        }
        if ($totals['stock_out_percent'] !== null && $totals['stock_out_percent'] > 100) {
            $flags[] = 'Recorded Stock Out exceeds planned DR item requirements; inspect repeated releases or requirement changes.';
        }

        return $totals + ['current_stock' => $totals['available'], 'scope' => 'Whole-project item inventory and lifetime history; requirements use all project DR allocations.', 'by_unit' => $units->all(), 'items' => [], 'integrity_flags' => $flags];
    }

    /**
     * Page project identifiers before calculating their allocations or stock totals.
     * The monitoring UI retains report(); the API uses this bounded variant.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<string, mixed>, pagination: array<string, int|bool>}
     */
    public function apiReport(array $filters): array
    {
        if (isset($filters['project_id'])) {
            abort_unless(DB::table('projects')->where('project_id', $filters['project_id'])->exists(), 404, 'Project not found.');
            $report = $this->report($filters);
            $count = $report['projects']->count();
            $pagination = ['current_page' => 1, 'per_page' => 1, 'total' => $count, 'last_page' => 1, 'has_more' => false];
        } else {
            $query = $this->projects($filters)->select('p.project_id');
            if (isset($filters['delivery_status'])) {
                $query->whereIn('p.project_id', $this->receipts($filters)->select('receipt.project_id'));
            } elseif (array_intersect(array_keys($filters), ['year', 'lot_id', 'region', 'division', 'municipality'])) {
                $query->whereIn('p.project_id', $this->deliveries($filters)->select('d.project_id'));
            }
            $sort = $filters['sort'] ?? null;
            $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            if (in_array($sort, ['progress', 'total_drs', 'total_dr_packages', 'last_delivery'], true)) {
                $query = $this->recordsQuery($filters);
            } elseif (in_array($sort, ['project', 'end_date'], true)) {
                $query->orderBy($sort === 'project' ? 'p.project_name' : 'p.end_date', $direction);
            }
            $page = $query->orderBy('p.project_id')->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1));
            $report = $this->report([...$filters, 'project_ids' => $page->pluck('project_id')->all()]);
            $pagination = ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'last_page' => $page->lastPage(), 'has_more' => $page->hasMorePages()];
        }

        return ['data' => $report, 'pagination' => $pagination];
    }

    /** @param array<string, mixed> $project @return array<string, mixed> */
    private function compactApiProject(array $project): array
    {
        return Arr::only($project, [
            'project_id', 'project_name', 'ref_no', 'project_status', 'start_date', 'end_date',
            'total_deliveries_count', 'completed_deliveries_count', 'pending_deliveries_count',
            'total_packages_count', 'delivered_packages_count', 'accepted_packages_count', 'remaining_packages_count',
            'delivery_progress_percent', 'billing_progress_percent', 'last_activity',
        ]);
    }

    /**
     * One project set and a fixed number of grouped queries; never query per project.
     * Billing receipts are deduplicated before joining, so multiple delivery rows
     * sharing a DR do not multiply billing counts.
     *
     * @param  array<string, mixed>  $filters
     * @return array{projects: Collection<int, array<string, mixed>>, summary: array<string, mixed>, definitions?: array<string, string>}
     */
    public function report(array $filters): array
    {
        $compact = (bool) ($filters['compact'] ?? false);
        $records = $this->recordsQuery($filters)->get();
        $warehouseRows = $compact ? collect() : $this->warehouseSummaryQuery($filters)->get()->groupBy('project_id');
        $projects = $records->map(function (object $record) use ($warehouseRows, $filters, $compact): array {
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
            if ($compact) {
                $project['last_activity'] = $this->lastActivity($project);

                return $project;
            }
            $project['warehouse_readiness'] = $this->warehouseReadiness($warehouseRows->get($project['project_id'], collect()));
            $project['last_inventory_date'] = $warehouseRows->get($project['project_id'], collect())->max('last_inventory_date');
            $project['operational_pipeline'] = $this->pipeline($project, $project['warehouse_readiness']);
            $project['pipeline'] = $project['operational_pipeline'];
            $project['overall_progress'] = $this->operationalProgress($project['pipeline']);
            if (array_intersect(array_keys($filters), ['year', 'region', 'division', 'municipality', 'delivery_status', 'lot_id'])) {
                $project['overall_progress'] = null;
            }
            $project['last_activity'] = $this->lastActivity($project);
            $project['data_integrity_flags'] = array_merge($this->integrityFlags($project['pipeline']), $project['warehouse_readiness']['integrity_flags']);

            return $project;
        });
        $summary = ['projects_count' => $projects->count(), 'active_projects_count' => $projects->where('is_active', true)->count()];
        foreach (array_keys($projects->first() ?? []) as $column) {
            if (str_ends_with($column, '_count')) {
                $summary[$column] = (int) $projects->sum($column);
            }
        }
        foreach (['total_deliveries_count', 'delivery_rows_count', 'total_packages_count', 'total_package_allocations_count', 'completed_packages_count', 'remaining_packages_count', 'pending_packages_count', 'released_packages_count', 'delivered_packages_count', 'accepted_packages_count', 'completed_deliveries_count', 'pending_deliveries_count', 'released_deliveries_count', 'delivered_deliveries_count', 'accepted_deliveries_count', 'cancelled_deliveries_count', 'billing_groups_count', 'for_billing_groups_count', 'billed_groups_count', 'paid_groups_count'] as $column) {
            $summary[$column] ??= 0;
        }
        $summary['progress_basis'] = $summary['total_packages_count'] > 0 ? 'package_allocations' : 'no_package_allocations';
        $summary['delivery_progress_percent'] = $this->deliveryProgress($summary);
        if ($compact) {
            $summary['billing_progress_percent'] = $this->percentage($summary['billed_groups_count'] + $summary['paid_groups_count'], $summary['billing_groups_count']);

            return ['projects' => $projects->map(fn (array $project): array => $this->compactApiProject($project)), 'summary' => $summary];
        }
        $selectedWarehouseRows = $warehouseRows->only($projects->pluck('project_id')->all())->flatten(1);
        $summary['warehouse_readiness'] = $this->warehouseReadiness($selectedWarehouseRows);
        $summary['pipeline'] = $this->pipeline($summary, $summary['warehouse_readiness']);
        $summary['operational_pipeline'] = $summary['pipeline'];
        $summary['overall_progress'] = $this->operationalProgress($summary['pipeline']);
        if (array_intersect(array_keys($filters), ['year', 'region', 'division', 'municipality', 'delivery_status', 'lot_id'])) {
            $summary['overall_progress'] = null;
        }

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

    /** @param array<string, mixed> $counts @return array<string, array<string, mixed>> */
    private function pipeline(array $counts, array $warehouse): array
    {
        $billingEntered = $counts['for_billing_groups_count'] + $counts['billed_groups_count'] + $counts['paid_groups_count'];

        return [
            'dr' => $this->stage($counts['total_deliveries_count'], $counts['total_deliveries_count'], 'DR receipts') + ['label' => 'DR', 'context' => 'Recorded receipts; the workflow baseline', 'source' => 'deliveries grouped by exact project_id + dr_no'],
            'stock_out' => [
                'label' => 'Stock Out', 'completed' => $warehouse['stock_out'], 'total' => $warehouse['required'], 'percent' => $warehouse['stock_out_percent'],
                'unit' => $warehouse['unit'], 'available' => $warehouse['stock_out'] !== null,
                'context' => 'Recorded project item quantities released from warehouse; whole-project history',
                'source' => 'inventory_history stock_out quantity (old_qty - new_qty), project via item.project_id',
            ],
            'delivered' => $this->stage($counts['completed_packages_count'], $counts['total_packages_count'], 'DR package allocations') + [
                'label' => 'Delivered', 'context' => 'Delivered + accepted allocations; accepted is a later delivery stage',
                'source' => 'package_status.status by delivery_id + package_id',
            ],
            'billing' => $this->stage($billingEntered, $counts['billing_groups_count'], 'DR/group records') + [
                'label' => 'Billing', 'context' => 'Entered billing: for billing + billed + paid',
                'source' => 'billing_grouped joined to grouping and exact DR receipts',
            ],
            'billed' => $this->stage($counts['billed_groups_count'], $counts['billing_groups_count'], 'DR/group records') + [
                'label' => 'Billed', 'context' => 'Current billed status; paid is shown separately in details',
                'source' => 'grouping.status = billed for matched billing_grouped records',
                'milestone_percent' => $this->percentage($counts['billed_groups_count'] + $counts['paid_groups_count'], $counts['billing_groups_count']),
            ],
        ];
    }

    /** @return array{completed: int, total: int, percent: ?float, unit: string, available: bool} */
    private function stage(int $completed, int $total, string $unit): array
    {
        return ['completed' => $completed, 'total' => $total, 'percent' => $this->percentage($completed, $total), 'unit' => $unit, 'available' => true];
    }

    /** @param array<string, array<string, mixed>> $pipeline */
    private function operationalProgress(array $pipeline): ?float
    {
        $percentages = [$pipeline['stock_out']['percent'], $pipeline['delivered']['percent'], $pipeline['billing']['percent'], $pipeline['billed']['milestone_percent']];
        if (in_array(null, $percentages, true)) {
            return null;
        }

        return round(array_sum($percentages) / count($percentages), 2);
    }

    /** @param array<string, mixed> $project @return ?array{type: string, at: string, source: string} */
    private function lastActivity(array $project): ?array
    {
        $latest = null;
        foreach ([
            'last_dr_date' => ['DR recorded', 'deliveries.created_at'],
            'last_delivery_date' => ['Delivered', 'deliveries.delivered_date'],
            'last_accepted_date' => ['Accepted', 'deliveries.accepted_date'],
            'last_billing_date' => ['Billing recorded', 'billing_grouped.created_at'],
            'last_inventory_date' => ['Inventory activity', 'inventory_history.changed_at'],
        ] as $column => [$type, $source]) {
            if (($project[$column] ?? null) !== null && ($latest === null || strtotime($project[$column]) > strtotime($latest['at']))) {
                $latest = ['type' => $type, 'at' => $project[$column], 'source' => $source];
            }
        }

        return $latest;
    }

    /** @param array<string, array<string, mixed>> $pipeline @return list<string> */
    private function integrityFlags(array $pipeline): array
    {
        $flags = [];
        if ($pipeline['billed']['completed'] > $pipeline['billing']['completed']) {
            $flags[] = 'Billed records exceed records that entered billing.';
        }
        if ($pipeline['billing']['completed'] < $pipeline['billing']['total']) {
            $flags[] = 'Some billing records have statuses outside for billing, billed and paid.';
        }

        return $flags;
    }

    /** @return array<string, string> */
    public function definitions(): array
    {
        return [
            'pipeline' => 'Warehouse readiness is separate from DR -> Stock Out -> Delivered -> Billing -> Billed. Stock Out uses project item transaction quantities, delivery uses DR package allocations, and billing uses matched DR/group records. Stage units are not interchangeable.',
            'stock_in' => 'SUM(new_qty - old_qty) for inventory_history.change_type = stock_in, grouped by item then project through item.project_id. Manual inventory insert deltas are reported separately as opening stock. Stock In does not require a DR link.',
            'stock_out' => 'SUM(old_qty - new_qty) for inventory_history.change_type = stock_out, project via item.project_id. The QR workflow releases package contents and records content.qty x delivery.package_qty as item quantities. History stores item, warehouse, batch_no and changed_at, but no DR/package reference; these are whole-project item releases, not inferred DR releases.',
            'warehouse_readiness' => 'Current stock is SUM(inventory.qty), exactly the balance used by Inventory and the scanner. History net is SUM(new_qty - old_qty), including insert/update adjustments; disagreements are flagged. Required item quantity is SUM(package_content.qty x deliveries.package_qty) over expected project DR allocations; non-positive content.qty uses 1, matching the scanner. Availability covers at most the required quantity of each item. Readiness = sum covered item quantities / sum required quantities x 100 within a single item unit; mixed units remain separate. Missing delivery package quantities make readiness unavailable. Warehouse inventory/history always use the whole project, while DR filters restrict delivery/billing.',
            'operational_progress' => 'Normalized quantity ratios, each weighted 25%: recorded Stock Out item quantity / planned DR item quantity; delivered + accepted DR allocations / total allocations; for billing + billed + paid records / recorded DR/group records; billed + paid records / recorded DR/group records. DR creation is the baseline. Warehouse readiness is not a completion stage. Mixed stock units, missing quantities or zero denominators make overall progress unavailable. A DR year/location/status filter also makes overall unavailable because project-wide inventory history cannot be assigned to that DR subset.',
            'pipeline_billed' => 'Billed stage shows exact current billed records; paid stays separate. The normalized final billing milestone includes paid because it is a later billing stage. Billing entry includes all three recorded billing statuses. Unrecorded billing is unknown.',
            'last_activity' => 'Latest recorded deliveries.created_at, delivered_date, accepted_date, billing_grouped.created_at or project inventory_history.changed_at. Billing recorded is the DR/group link date, not an invented billed-status update timestamp. Inventory activity is attributed via the existing item project relationship.',
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
