<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Item;
use App\Models\Project;
use App\Models\Warehouse;
use App\Services\BillingOperationalSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with('item');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('item', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('project_id')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->inventory_status);
        }

        $inventories = $query->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $projects = Project::orderBy('project_name')->get();

        return view('operation.warehouse.inventory.index', compact('inventories', 'projects'));
    }

    public function operation_index(Request $request): View
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'integer', Rule::exists(Warehouse::class, 'warehouse_id')],
        ]);

        $query = Inventory::with(['item', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('item', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('project_id')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->inventory_status);
        }

        $inventories = $query->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $projects = Project::orderBy('project_name')->get();

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('inventory.index', compact('inventories', 'projects', 'warehouses'));
    }

    public function balances(Request $request, BillingOperationalSyncService $warehouseRouting): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', Rule::exists(Warehouse::class, 'warehouse_id')],
        ]);

        $company = $request->user()->currentCompany();
        $projectsQuery = Project::query()->orderBy('project_name');

        if ($company && Schema::hasColumn('projects', 'company_id')) {
            $projectsQuery->where(function ($query) use ($company): void {
                $query->whereNull('company_id')->orWhere('company_id', $company->getKey());
            });
        }

        $projects = $projectsQuery->get(['project_id', 'project_name', 'project_code', 'ref_no']);

        if ($request->filled('project_id')) {
            abort_unless($projects->contains('project_id', $request->integer('project_id')), 404);
        }

        $transactionTotals = DB::table('inventory_history')
            ->whereIn('change_type', ['stock_in', 'stock_out'])
            ->select(['item_id', 'warehouse_id'])
            ->selectRaw("SUM(CASE WHEN change_type = 'stock_in' THEN new_qty - old_qty ELSE 0 END) as stock_in_quantity")
            ->selectRaw("SUM(CASE WHEN change_type = 'stock_out' THEN old_qty - new_qty ELSE 0 END) as stock_out_quantity")
            ->groupBy('item_id', 'warehouse_id');

        $currentBalances = DB::table('inventory')
            ->select(['item_id', 'warehouse_id'])
            ->groupBy('item_id', 'warehouse_id');

        $lotAllocations = DB::table('deliveries as dr')
            ->join('package as packages', 'packages.lot_id', '=', 'dr.lot_id')
            ->whereNull('dr.keystage_id')
            ->select(['dr.delivery_id', 'dr.project_id', 'dr.package_qty', 'dr.school_id', 'dr.dr_no', 'dr.logistics_location_id', 'packages.package_id']);

        $keystageAllocations = DB::table('deliveries as dr')
            ->join('package as packages', 'packages.keystage_id', '=', 'dr.keystage_id')
            ->whereNotNull('dr.keystage_id')
            ->select(['dr.delivery_id', 'dr.project_id', 'dr.package_qty', 'dr.school_id', 'dr.dr_no', 'dr.logistics_location_id', 'packages.package_id']);

        $inventoryItemIds = DB::table('inventory')->select('item_id')->distinct();
        $actualDrAllocations = DB::query()
            ->fromSub($keystageAllocations->unionAll($lotAllocations), 'allocations')
            ->join('package_content as contents', 'contents.package_id', '=', 'allocations.package_id')
            ->join('item as allocated_items', function ($join): void {
                $join->on('allocated_items.item_id', '=', 'contents.item_id')
                    ->on('allocated_items.project_id', '=', 'allocations.project_id');
            })
            ->leftJoin('school as schools', 'schools.school_id', '=', 'allocations.school_id')
            ->leftJoin('logistics_location as locations', 'locations.logistics_location_id', '=', 'allocations.logistics_location_id')
            ->whereIn('contents.item_id', $inventoryItemIds)
            ->when($request->filled('project_id'), fn ($query) => $query->where('allocations.project_id', $request->integer('project_id')))
            ->select([
                'allocations.project_id',
                'contents.item_id',
                'locations.warehouse_id',
                'schools.region as school_region',
            ])
            ->selectRaw('MIN(allocations.dr_no) as dr_no')
            ->selectRaw('SUM(CASE WHEN allocations.package_qty > 0 THEN allocations.package_qty * (CASE WHEN contents.qty > 0 THEN contents.qty ELSE 1 END) ELSE 0 END) as actual_dr_quantity')
            ->groupBy('allocations.project_id', 'contents.item_id', 'locations.warehouse_id', 'schools.region');

        $actualDrByItemWarehouse = [];
        $unmappedActualDrItems = [];
        $warehouseByDelivery = [];

        foreach ($actualDrAllocations->get() as $allocation) {
            $warehouseCacheKey = $allocation->warehouse_id !== null
                ? 'location:'.$allocation->warehouse_id
                : 'school-region:'.trim((string) $allocation->school_region);

            if (! array_key_exists($warehouseCacheKey, $warehouseByDelivery)) {
                try {
                    $warehouseByDelivery[$warehouseCacheKey] = $warehouseRouting->resolveWarehouseForDelivery($allocation);
                } catch (\RuntimeException) {
                    $warehouseByDelivery[$warehouseCacheKey] = null;
                }
            }

            $actualDrQuantity = (int) $allocation->actual_dr_quantity;
            $itemKey = $allocation->project_id.':'.$allocation->item_id;
            $warehouseId = $warehouseByDelivery[$warehouseCacheKey];

            if ($warehouseId === null) {
                $unmappedActualDrItems[$itemKey] = true;

                continue;
            }

            $key = $itemKey.':'.$warehouseId;
            $actualDrByItemWarehouse[$key] = ($actualDrByItemWarehouse[$key] ?? 0) + $actualDrQuantity;
        }

        $query = DB::query()
            ->fromSub($currentBalances, 'balances')
            ->join('item as items', 'items.item_id', '=', 'balances.item_id')
            ->leftJoin('warehouse as warehouses', 'warehouses.warehouse_id', '=', 'balances.warehouse_id')
            ->leftJoinSub($transactionTotals, 'transactions', function ($join): void {
                $join->on('transactions.item_id', '=', 'balances.item_id')
                    ->on('transactions.warehouse_id', '=', 'balances.warehouse_id');
            })
            ->select([
                'items.item_id',
                'items.item_name',
                'items.project_id',
                'warehouses.warehouse_id',
                'warehouses.warehouse_name',
            ])
            ->selectRaw('COALESCE(transactions.stock_in_quantity, 0) as stock_in_quantity')
            ->selectRaw('COALESCE(transactions.stock_out_quantity, 0) as stock_out_quantity')
            ->selectRaw('(COALESCE(transactions.stock_in_quantity, 0) - COALESCE(transactions.stock_out_quantity, 0)) as stock_on_hand_quantity');

        if ($request->filled('project_id')) {
            $query->where('items.project_id', $request->integer('project_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('balances.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('items.item_name', 'like', "%{$search}%")
                    ->orWhere('warehouses.warehouse_name', 'like', "%{$search}%");
            });
        }

        $filteredBalances = clone $query;
        $filteredBalanceKeys = DB::query()
            ->fromSub(clone $filteredBalances, 'filtered_balances')
            ->select(['project_id', 'item_id', 'warehouse_id'])
            ->distinct()
            ->get();

        $warehouseSummaries = DB::query()
            ->fromSub(clone $filteredBalances, 'filtered_balances')
            ->select(['warehouse_id', 'warehouse_name'])
            ->selectRaw('SUM(stock_in_quantity) as stock_in_quantity')
            ->selectRaw('SUM(stock_out_quantity) as stock_out_quantity')
            ->selectRaw('SUM(stock_on_hand_quantity) as stock_on_hand_quantity')
            ->groupBy('warehouse_id', 'warehouse_name')
            ->orderBy('warehouse_name')
            ->get();

        $itemWarehouseKey = static fn (int|string|null $projectId, int|string|null $itemId, int|string|null $warehouseId): string => ($projectId ?? 'unassigned').':'.($itemId ?? 'unassigned').':'.($warehouseId ?? 'unassigned');
        $percentageOf = static function (float|int|string|null $quantity, float|int|string|null $denominator): ?float {
            if ($denominator === null) {
                return null;
            }

            $denominator = (float) $denominator;

            return $denominator === 0.0 ? 0.0 : ((float) $quantity / $denominator) * 100;
        };

        foreach ($warehouseSummaries as $summary) {
            $warehouseKeys = $filteredBalanceKeys->where('warehouse_id', $summary->warehouse_id);
            $summary->actual_dr_mapping_unavailable = $warehouseKeys->contains(fn ($row): bool => isset($unmappedActualDrItems[$row->project_id.':'.$row->item_id]));
            $summary->actual_dr_quantity = $summary->actual_dr_mapping_unavailable
                ? null
                : $warehouseKeys->sum(fn ($row): int => $actualDrByItemWarehouse[$itemWarehouseKey($row->project_id, $row->item_id, $row->warehouse_id)] ?? 0);
            $summary->stock_in_percentage = $percentageOf($summary->stock_in_quantity, $summary->actual_dr_quantity);
            $summary->stock_out_percentage = $percentageOf($summary->stock_out_quantity, $summary->stock_in_quantity);
            $summary->stock_on_hand_percentage = $percentageOf($summary->stock_on_hand_quantity, $summary->actual_dr_quantity);
        }

        $hasUnmappedActualDr = $warehouseSummaries->contains('actual_dr_mapping_unavailable', true);

        $inventories = $query
            ->orderBy('items.item_name')
            ->orderBy('warehouses.warehouse_name')
            ->paginate(25)
            ->withQueryString();

        $inventories->getCollection()->transform(function ($inventory) use ($percentageOf, $actualDrByItemWarehouse, $unmappedActualDrItems, $itemWarehouseKey, &$hasUnmappedActualDr) {
            $itemKey = $inventory->project_id.':'.$inventory->item_id;
            $inventory->actual_dr_mapping_unavailable = isset($unmappedActualDrItems[$itemKey]);
            $inventory->actual_dr_quantity = $inventory->actual_dr_mapping_unavailable
                ? null
                : ($actualDrByItemWarehouse[$itemWarehouseKey($inventory->project_id, $inventory->item_id, $inventory->warehouse_id)] ?? 0);
            $hasUnmappedActualDr = $hasUnmappedActualDr || $inventory->actual_dr_mapping_unavailable;
            $inventory->stock_in_percentage = $percentageOf($inventory->stock_in_quantity, $inventory->actual_dr_quantity);
            $inventory->stock_out_percentage = $percentageOf($inventory->stock_out_quantity, $inventory->stock_in_quantity);
            $inventory->stock_on_hand_percentage = $percentageOf($inventory->stock_on_hand_quantity, $inventory->actual_dr_quantity);

            return $inventory;
        });

        $warehouses = Warehouse::query()->orderBy('warehouse_name')->get();

        return view('inventory.balances', compact('inventories', 'projects', 'warehouses', 'warehouseSummaries', 'hasUnmappedActualDr'));
    }

    public function create()
    {
        $items = Item::orderBy('item_name')->get();

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('inventory.create', [
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:item,item_id',
            'warehouse_id' => 'required|exists:warehouse,warehouse_id',
            'qty' => 'required|integer|min:0',
            'inventory_status' => 'required',
            'remarks' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {

            // Create inventory
            $inventory = Inventory::create([

                'item_id' => $request->item_id,

                'warehouse_id' => $request->warehouse_id,

                'qty' => $request->qty,

                'inventory_status' => $request->inventory_status,

            ]);
            // Create inventory history
            InventoryHistory::create([

                'inventory_id' => $inventory->inventory_id,

                'item_id' => $request->item_id,

                'warehouse_id' => $request->warehouse_id,

                'old_qty' => 0,

                'new_qty' => $request->qty,

                'changed_by' => Auth::user()->name ?? 'System',
                'changed_by_user_id' => Auth::id(),
                'changed_at' => now(),

                'remarks' => $request->remarks,

                'change_type' => 'insert',

            ]);

            DB::commit();

            return redirect()
                ->route('inventory.index')
                ->with('success', 'Inventory added successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());

        }
    }

    public function show($id)
    {
        $inventory = Inventory::with('item')->findOrFail($id);

        return view('inventory.show', compact('inventory'));
    }

    public function edit($id)
    {
        $inventory = Inventory::with('item')->findOrFail($id);

        return view('inventory.edit', compact('inventory'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|integer|min:0',
            'inventory_status' => 'required',
        ]);

        $inventory = Inventory::findOrFail($id);

        $inventory->update([
            'qty' => $request->qty,
            'inventory_status' => $request->inventory_status,
        ]);

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory updated successfully.');
    }

    public function summary(Request $request)
    {
        $query = Inventory::with([
            'item',
            'warehouse',
        ]);

        // Search Item
        if ($request->filled('search')) {

            $search = $request->search;

            $query->whereHas('item', function ($q) use ($search) {

                $q->where('item_name', 'like', "%{$search}%");

            });

        }

        // Warehouse Filter
        if ($request->filled('warehouse_id')) {

            $query->where(
                'warehouse_id',
                $request->warehouse_id
            );

        }

        // Status Filter
        if ($request->filled('inventory_status')) {

            $query->where(
                'inventory_status',
                $request->inventory_status
            );

        }

        $inventories = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('warehouse_name')
            ->get();

        $statuses = Inventory::select('inventory_status')
            ->distinct()
            ->pluck('inventory_status');

        return view('inventory.summary', compact(
            'inventories',
            'warehouses',
            'statuses'
        ));
    }

    public function history(Request $request)
    {
        $company = $request->user()->currentCompany();
        $projectsQuery = Project::query()->orderBy('project_name');

        if ($company && Schema::hasColumn('projects', 'company_id')) {
            $projectsQuery->where(function ($query) use ($company): void {
                $query->whereNull('company_id')->orWhere('company_id', $company->getKey());
            });
        }

        $projects = $projectsQuery->get(['project_id', 'project_name', 'project_code', 'ref_no']);

        if ($request->filled('project_id')) {
            $validated = $request->validate([
                'project_id' => ['required', 'integer'],
            ]);
            abort_unless($projects->contains('project_id', (int) $validated['project_id']), 404);
        } else {
            $histories = null;
            $warehouses = Warehouse::orderBy('warehouse_name')->get();

            return view('inventory.history', compact(
                'histories',
                'warehouses',
                'projects'
            ));
        }

        $batchExpr = "IFNULL(batch_no, CONCAT('IND-', history_id))";

        $query = InventoryHistory::query()
            ->select([
                DB::raw("$batchExpr AS batch_key"),

                DB::raw('MIN(history_id) AS first_history_id'),
                DB::raw('MAX(history_id) AS last_history_id'),

                DB::raw('MAX(history_id) AS history_id'),
                DB::raw('MAX(batch_no) AS batch_no'),
                DB::raw('MAX(item_id) AS item_id'),
                DB::raw('MAX(warehouse_id) AS warehouse_id'),
                DB::raw('MAX(change_type) AS change_type'),
                DB::raw('MAX(changed_by) AS changed_by'),
                DB::raw('MAX(remarks) AS remarks'),
                DB::raw('MAX(changed_at) AS changed_at'),
            ])
            ->groupBy(
                DB::raw($batchExpr),
                'item_id'
            )
            ->with([
                'item',
                'warehouse',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('changed_by', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($item) use ($search) {
                        $item->where('item_name', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('change_type')) {
            $query->where('change_type', $request->change_type);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('project_id')) {
            $projectId = (int) $request->input('project_id');
            $query->whereHas('item', function ($itemQuery) use ($projectId): void {
                $itemQuery->where('project_id', $projectId);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date_to);
        }

        $histories = $query
            ->orderByDesc('changed_at')
            ->paginate(50)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Load first & last history
        |--------------------------------------------------------------------------
        */

        $ids = collect();

        foreach ($histories as $history) {
            $ids->push($history->first_history_id);
            $ids->push($history->last_history_id);
        }

        $historyMap = InventoryHistory::whereIn('history_id', $ids)
            ->get()
            ->keyBy('history_id');

        $histories->getCollection()->transform(function ($history) use ($historyMap) {

            $first = $historyMap[$history->first_history_id] ?? null;
            $last = $historyMap[$history->last_history_id] ?? null;

            if ($first && $last) {
                $history->old_qty = $first->old_qty;
                $history->new_qty = $last->new_qty;
                $history->qty_change = $last->new_qty - $first->old_qty;
            }

            return $history;
        });

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('inventory.history', compact(
            'histories',
            'warehouses',
            'projects'
        ));
    }

    public function destroy($id)
    {
        //
    }
}
