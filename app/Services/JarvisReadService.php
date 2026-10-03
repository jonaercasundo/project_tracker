<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class JarvisReadService
{
    /** @param array<string, mixed> $filters */
    public function projects(array $filters): Builder
    {
        $query = DB::table('projects as p');
        $this->equals($query, $filters, ['project_id' => 'p.project_id', 'status' => 'p.status', 'agency' => 'p.agency', 'ref_no' => 'p.ref_no']);
        if (isset($filters['year'])) {
            $query->whereYear('p.created_at', $filters['year']);
        }
        $this->search($query, $filters, ['p.project_name', 'p.project_code', 'p.ref_no']);
        if (isset($filters['has_pending_deliveries'])) {
            $pending = $this->pendingDeliveries()->whereColumn('pd.project_id', 'p.project_id');
            $query->whereExists($pending, 'and', ! (bool) $filters['has_pending_deliveries']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function projectRecords(array $filters): Builder
    {
        return $this->projects($filters)
            ->select(['p.project_id', 'p.project_code', 'p.ref_no', 'p.project_name', 'p.agency', 'p.status', 'p.contract_amount', 'p.start_date', 'p.end_date', 'p.created_at'])
            ->selectSub(DB::table('lot')->whereColumn('lot.project_id', 'p.project_id')->selectRaw('COUNT(*)'), 'lots_count')
            ->selectSub(DB::table('deliveries')->whereColumn('deliveries.project_id', 'p.project_id')->selectRaw('COUNT(*)'), 'delivery_rows_count')
            ->selectSub($this->pendingDeliveries()->whereColumn('pd.project_id', 'p.project_id')->selectRaw('COUNT(*)'), 'pending_delivery_rows_count');
    }

    /**
     * A pending delivery has a pending row or an expected pending package.
     * Missing/null package status is pending, as in DeliveryController.
     */
    private function pendingDeliveries(): Builder
    {
        return DB::table('deliveries as pd')->where('pd.status', '!=', 'cancelled')
            ->where(function (Builder $query): void {
                $query->where('pd.status', 'pending')
                    ->orWhereExists($this->allocations('pd')->whereRaw("COALESCE(ps.status, 'pending') = ?", ['pending']));
            });
    }

    /**
     * Match package definitions by keystage when present, otherwise by lot.
     * Only select allocations; never call DeliveryController::generate, which writes.
     */
    private function allocations(string $deliveryAlias = 'd'): Builder
    {
        return DB::table('package as pk')
            ->leftJoin('package_status as ps', function (JoinClause $join) use ($deliveryAlias): void {
                $join->on('ps.package_id', '=', 'pk.package_id')
                    ->on('ps.delivery_id', '=', $deliveryAlias.'.delivery_id');
            })
            ->where(function (Builder $query) use ($deliveryAlias): void {
                $query->where(function (Builder $query) use ($deliveryAlias): void {
                    $query->whereNotNull($deliveryAlias.'.keystage_id')->whereColumn('pk.keystage_id', $deliveryAlias.'.keystage_id');
                })->orWhere(function (Builder $query) use ($deliveryAlias): void {
                    $query->whereNull($deliveryAlias.'.keystage_id')->whereColumn('pk.lot_id', $deliveryAlias.'.lot_id');
                });
            });
    }

    /** @param array<string, mixed> $filters */
    public function deliveries(array $filters): Builder
    {
        $query = DB::table('deliveries as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('logistics_location as ll', 'll.logistics_location_id', '=', 'd.logistics_location_id');
        $this->equals($query, $filters, [
            'project_id' => 'd.project_id', 'lot_id' => 'd.lot_id',
            'delivery_id' => 'd.delivery_id', 'delivery_status' => 'd.status',
            'warehouse_id' => 'll.warehouse_id', 'region' => 's.region',
            'division' => 's.division', 'municipality' => 's.municipality',
        ]);
        $dateColumn = 'd.'.($filters['date_field'] ?? 'delivery_date');
        if (isset($filters['year'])) {
            $query->whereYear($dateColumn, $filters['year']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }
        $this->search($query, $filters, ['d.dr_no', 'p.project_name', 'p.ref_no', 's.school_name']);
        if (isset($filters['package_status'])) {
            $query->whereExists($this->allocations()->whereRaw("COALESCE(ps.status, 'pending') = ?", [$filters['package_status']]));
        }
        if (isset($filters['billing_status'])) {
            $billing = $this->billingGroups();
            if ($filters['billing_status'] === 'unknown') {
                $query->whereNotExists($billing);
            } else {
                $query->whereExists($billing->where('bg_status.status', $filters['billing_status']));
            }
        }

        return $query;
    }

    /**
     * Billing uses receipt numbers, not delivery IDs. Compare strings exactly;
     * MySQL numeric coercion would incorrectly associate "3502-X" with 3502.
     */
    private function billingGroups(): Builder
    {
        $query = DB::table('billing_grouped as bg')
            ->join('grouping as bg_status', 'bg_status.group_id', '=', 'bg.group_id');
        $this->matchReceiptNumber($query, 'bg');

        return $query;
    }

    /** The indexed billing column stays bare; length also distinguishes trailing spaces. */
    private function matchReceiptNumber(Builder|JoinClause $query, string $billingAlias): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $query->whereRaw($billingAlias.'.dr_no = d.dr_no COLLATE utf8mb4_bin')
                ->whereRaw('OCTET_LENGTH('.$billingAlias.'.dr_no) = OCTET_LENGTH(d.dr_no)');
        } else {
            $query->whereColumn($billingAlias.'.dr_no', 'd.dr_no');
        }
    }

    /**
     * Aggregate both allocation paths separately so package key indexes remain usable.
     *
     * @param  list<int>|null  $deliveryIds
     */
    private function packageCounts(?array $deliveryIds): Builder
    {
        $byKeystage = $this->packageCountsBy('keystage_id')->whereNotNull('ad.keystage_id');
        $byLot = $this->packageCountsBy('lot_id')->whereNull('ad.keystage_id');

        if ($deliveryIds !== null) {
            $byKeystage->whereIn('ad.delivery_id', $deliveryIds);
            $byLot->whereIn('ad.delivery_id', $deliveryIds);
        }

        return $byKeystage->unionAll($byLot);
    }

    private function packageCountsBy(string $column): Builder
    {
        return DB::table('deliveries as ad')
            ->join('package as pk', 'pk.'.$column, '=', 'ad.'.$column)
            ->leftJoin('package_status as ps', function (JoinClause $join): void {
                $join->on('ps.delivery_id', '=', 'ad.delivery_id')->on('ps.package_id', '=', 'pk.package_id');
            })
            ->select('ad.delivery_id')
            ->selectRaw('COUNT(*) as package_allocations_count')
            ->selectRaw("COUNT(CASE WHEN COALESCE(ps.status, 'pending') = 'pending' THEN 1 END) as pending_packages_count")
            ->selectRaw("COUNT(CASE WHEN ps.status = 'released' THEN 1 END) as released_packages_count")
            ->selectRaw("COUNT(CASE WHEN ps.status = 'delivered' THEN 1 END) as delivered_packages_count")
            ->selectRaw("COUNT(CASE WHEN ps.status = 'accepted' THEN 1 END) as accepted_packages_count")
            ->selectRaw("COUNT(CASE WHEN ps.status = 'warehouse' THEN 1 END) as warehouse_packages_count")
            ->groupBy('ad.delivery_id');
    }

    private function billingCounts(): Builder
    {
        $query = DB::table('billing_grouped as bg')
            ->join('grouping as bg_status', 'bg_status.group_id', '=', 'bg.group_id')
            ->select('bg.dr_no')
            ->selectRaw('COUNT(*) as billing_groups_count')
            ->selectRaw("COUNT(CASE WHEN bg_status.status = 'billed' THEN 1 END) as billed_groups_count")
            ->selectRaw("COUNT(CASE WHEN bg_status.status = 'paid' THEN 1 END) as paid_groups_count")
            ->selectRaw("COUNT(CASE WHEN bg_status.status = 'for billing' THEN 1 END) as for_billing_groups_count")
            ->groupBy('bg.dr_no');

        if (DB::connection()->getDriverName() === 'mysql') {
            $query->groupByRaw('OCTET_LENGTH(bg.dr_no)');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $deliveryIds
     */
    public function deliveryRecords(array $filters, ?array $deliveryIds = null): Builder
    {
        $query = $this->deliveries($filters)
            ->leftJoinSub($this->packageCounts($deliveryIds), 'package_counts', 'package_counts.delivery_id', '=', 'd.delivery_id')
            ->leftJoinSub($this->billingCounts(), 'billing_counts', function (JoinClause $join): void {
                $this->matchReceiptNumber($join, 'billing_counts');
            })->select([
                'd.delivery_id', 'd.project_id', 'p.project_name', 'p.ref_no', 'd.dr_no',
                'd.lot_id', 'd.keystage_id', 'd.status as delivery_status', 'd.delivery_date',
                'd.delivered_date', 'd.accepted_date', 'd.package_qty', 'd.received_qty',
                'd.school_id', 's.school_name', 's.region', 's.division', 's.municipality',
                'll.warehouse_id',
            ]);

        if ($deliveryIds !== null) {
            $query->whereIn('d.delivery_id', $deliveryIds);
        }

        foreach (['package_allocations_count', 'pending_packages_count', 'released_packages_count', 'delivered_packages_count', 'accepted_packages_count', 'warehouse_packages_count'] as $column) {
            $query->selectRaw('COALESCE(package_counts.'.$column.', 0) as '.$column);
        }
        foreach (['billing_groups_count', 'billed_groups_count', 'paid_groups_count', 'for_billing_groups_count'] as $column) {
            $query->selectRaw('COALESCE(billing_counts.'.$column.', 0) as '.$column);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function packages(array $filters): Builder
    {
        $query = $this->deliveries($filters)
            ->join('package as pk', function (JoinClause $join): void {
                $join->on(function (JoinClause $join): void {
                    $join->whereNotNull('d.keystage_id')->on('pk.keystage_id', '=', 'd.keystage_id');
                })->orOn(function (JoinClause $join): void {
                    $join->whereNull('d.keystage_id')->on('pk.lot_id', '=', 'd.lot_id');
                });
            })
            ->leftJoin('package_status as ps', function (JoinClause $join): void {
                $join->on('ps.delivery_id', '=', 'd.delivery_id')->on('ps.package_id', '=', 'pk.package_id');
            });
        if (isset($filters['package_status'])) {
            $query->whereRaw("COALESCE(ps.status, 'pending') = ?", [$filters['package_status']]);
        }
        $this->equals($query, $filters, ['package_id' => 'pk.package_id']);

        return $query->select([
            'pk.package_id', 'pk.package_num', 'pk.lot_id', 'pk.keystage_id',
            'pk.length', 'pk.width', 'pk.height', 'd.delivery_id', 'd.project_id',
            'p.project_name', 'd.dr_no', 'd.package_qty', 'd.status as delivery_status',
            'd.school_id', 's.school_name', 's.region', 's.division', 's.municipality',
            'ps.package_status_id', 'ps.delivered_at',
        ])->selectRaw("COALESCE(ps.status, 'pending') as package_status")
            ->selectSub(DB::table('package_content as pc')->whereColumn('pc.package_id', 'pk.package_id')->selectRaw('COUNT(*)'), 'content_rows_count');
    }

    /** @param array<string, mixed> $filters */
    public function inventory(array $filters): Builder
    {
        $query = DB::table('inventory as inv')
            ->join('item as i', 'i.item_id', '=', 'inv.item_id')
            ->join('warehouse as w', 'w.warehouse_id', '=', 'inv.warehouse_id')
            ->leftJoin('projects as p', 'p.project_id', '=', 'i.project_id');
        $this->equals($query, $filters, [
            'project_id' => 'i.project_id', 'warehouse_id' => 'inv.warehouse_id',
            'item_id' => 'inv.item_id', 'inventory_status' => 'inv.inventory_status',
        ]);
        if (isset($filters['available_only']) && (bool) $filters['available_only']) {
            $query->where('inv.qty', '>', 0);
        }
        $this->search($query, $filters, ['i.item_name', 'w.warehouse_name', 'p.project_name']);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function inventoryRecords(array $filters): Builder
    {
        return $this->inventory($filters)->select([
            'inv.inventory_id', 'inv.item_id', 'i.item_name', 'i.unit', 'i.project_id',
            'p.project_name', 'inv.warehouse_id', 'w.warehouse_name', 'inv.qty',
            'inv.inventory_status', 'inv.created_at',
        ]);
    }

    /** @param array<string, mixed> $filters */
    public function warehouses(array $filters): Builder
    {
        $stock = $this->inventory($filters)->select('inv.warehouse_id')
            ->selectRaw("COUNT(*) as inventory_rows_count, COUNT(DISTINCT inv.item_id) as distinct_items_count, COALESCE(SUM(inv.qty), 0) as recorded_qty, COALESCE(SUM(CASE WHEN inv.inventory_status = 'Approved' THEN inv.qty ELSE 0 END), 0) as approved_qty")
            ->groupBy('inv.warehouse_id');
        $query = DB::table('warehouse as w')->leftJoinSub($stock, 'stock', 'stock.warehouse_id', '=', 'w.warehouse_id');
        $this->equals($query, $filters, ['warehouse_id' => 'w.warehouse_id']);
        if (array_intersect(array_keys($filters), ['project_id', 'item_id', 'inventory_status', 'available_only', 'search'])) {
            $query->whereNotNull('stock.warehouse_id');
        }

        return $query->select(['w.warehouse_id', 'w.warehouse_name', 'w.warehouse_address'])
            ->selectRaw('COALESCE(stock.inventory_rows_count, 0) as inventory_rows_count, COALESCE(stock.distinct_items_count, 0) as distinct_items_count, COALESCE(stock.recorded_qty, 0) as recorded_qty, COALESCE(stock.approved_qty, 0) as approved_qty');
    }

    /** @param array<string, mixed> $filters */
    public function lots(array $filters): Builder
    {
        $query = DB::table('lot as l')->join('projects as p', 'p.project_id', '=', 'l.project_id');
        $this->equals($query, $filters, ['project_id' => 'l.project_id', 'lot_id' => 'l.lot_id', 'lot_name' => 'l.lot_name']);
        $this->search($query, $filters, ['l.lot_name', 'l.contract_no', 'p.project_name']);

        return $query->select(['l.lot_id', 'l.lot_name', 'l.project_id', 'p.project_name', 'l.contract_no'])
            ->selectSub(DB::table('deliveries')->whereColumn('deliveries.lot_id', 'l.lot_id')->selectRaw('COUNT(*)'), 'delivery_rows_count');
    }

    /**
     * Operational package items are linked through package_content, not ProjectItem
     * (which belongs to the separate bidding lots table).
     *
     * @param  array<string, mixed>  $filters
     */
    public function masterlist(array $filters): Builder
    {
        if (($filters['source'] ?? 'catalog') === 'catalog') {
            $query = DB::table('items as i');
            $this->equals($query, $filters, ['catalog_project_id' => 'i.project_id', 'catalog_lot_id' => 'i.lot_id', 'active' => 'i.active', 'code_prefix' => 'i.code_prefix']);
            $this->search($query, $filters, ['i.item_id', 'i.item_name', 'i.code_prefix']);

            return $query->select(['i.id', 'i.item_id', 'i.code_prefix', 'i.item_name', 'i.description', 'i.unit', 'i.price', 'i.active', 'i.project_id as catalog_project_id', 'i.lot_id as catalog_lot_id']);
        }

        $query = DB::table('item as i');
        $this->equals($query, $filters, ['project_id' => 'i.project_id', 'item_id' => 'i.item_id']);
        $this->search($query, $filters, ['i.item_name']);
        if (isset($filters['lot_id']) || isset($filters['package_id'])) {
            $contents = DB::table('package_content as pc')
                ->join('package as pk', 'pk.package_id', '=', 'pc.package_id')
                ->leftJoin('keystage as k', 'k.keystage_id', '=', 'pk.keystage_id')
                ->whereColumn('pc.item_id', 'i.item_id');
            $this->equals($contents, $filters, ['package_id' => 'pk.package_id']);
            if (isset($filters['lot_id'])) {
                $contents->whereRaw('COALESCE(pk.lot_id, k.lot_id) = ?', [(int) $filters['lot_id']]);
            }
            $query->whereExists($contents);
        }

        return $query->select(['i.item_id', 'i.item_name', 'i.unit', 'i.project_id', 'i.price']);
    }

    /**
     * All summary queries use the same selected project set. Year is project
     * creation year here, rather than delivery year.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        $projects = $this->projects($filters);
        $projectIds = (clone $projects)->select('p.project_id');
        $deliveries = $this->deliveries([])->whereIn('d.project_id', $projectIds);
        $inventory = $this->inventory([])->whereIn('i.project_id', $projectIds);
        $packages = $this->packages([])->whereIn('d.project_id', $projectIds);
        $deliverySummary = (clone $deliveries)->selectRaw("COUNT(*) as delivery_rows_count, COUNT(DISTINCT d.dr_no) as delivery_receipts_count, COUNT(DISTINCT CASE WHEN d.status IN ('delivered', 'accepted') THEN d.school_id END) as schools_with_delivered_or_accepted_rows")->first();
        $inventorySummary = (clone $inventory)->selectRaw("COUNT(*) as inventory_rows_count, COALESCE(SUM(inv.qty), 0) as recorded_qty, COALESCE(SUM(CASE WHEN inv.inventory_status = 'Approved' THEN inv.qty ELSE 0 END), 0) as approved_qty")->first();
        $deliverySummary->schools_with_delivered_or_accepted_packages = (clone $deliveries)
            ->whereExists($this->allocations()->whereIn('ps.status', ['delivered', 'accepted']))
            ->distinct()->count('d.school_id');
        $inventorySummary->recorded_qty = (int) $inventorySummary->recorded_qty;
        $inventorySummary->approved_qty = (int) $inventorySummary->approved_qty;
        $packageCounts = (clone $packages)->select(DB::raw("COALESCE(ps.status, 'pending') as package_status"));
        $packageCounts = DB::query()->fromSub($packageCounts, 'allocations')->select('package_status')->selectRaw('COUNT(*) as count')->groupBy('package_status')->orderBy('package_status')->get();

        return [
            'projects_count' => (clone $projects)->count(),
            'projects_by_status' => (clone $projects)->select('p.status')->selectRaw('COUNT(*) as count')->groupBy('p.status')->orderBy('p.status')->get(),
            'projects_with_pending_deliveries_count' => (clone $projects)->whereExists($this->pendingDeliveries()->whereColumn('pd.project_id', 'p.project_id'))->count(),
            'deliveries' => $deliverySummary,
            'delivery_rows_by_status' => (clone $deliveries)->select('d.status')->selectRaw('COUNT(*) as count')->groupBy('d.status')->orderBy('d.status')->get(),
            'package_allocations_by_status' => $packageCounts,
            'inventory' => $inventorySummary,
        ];
    }

    /** @param array<string, mixed> $filters @param array<string, string> $columns */
    private function equals(Builder $query, array $filters, array $columns): void
    {
        foreach ($columns as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
    }

    /** @param array<string, mixed> $filters @param list<string> $columns */
    private function search(Builder $query, array $filters, array $columns): void
    {
        if (! isset($filters['search'])) {
            return;
        }

        $query->where(function (Builder $query) use ($filters, $columns): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', '%'.$filters['search'].'%');
            }
        });
    }
}
