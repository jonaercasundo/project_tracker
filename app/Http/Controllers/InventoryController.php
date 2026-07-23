<?php

namespace App\Http\Controllers;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\InventoryHistory;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        $inventories = $query->latest('created_at')->get();

        $projects = \App\Models\Project::orderBy('project_name')->get();

        return view('inventory.index', compact('inventories', 'projects'));
    }

    public function create()
    {
        $items = Item::orderBy('item_name')->get();

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('inventory.create', [
            'items' => $items,
            'warehouses' => $warehouses
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
            'inventory_status' => 'required'
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
        'warehouse'
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


    $warehouses = \App\Models\Warehouse::orderBy('warehouse_name')
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
    $query = InventoryHistory::query()
        ->select([
            DB::raw('IFNULL(batch_no, CONCAT("IND-", id)) as batch_key'),
            DB::raw('MAX(id) as id'),
            DB::raw('MAX(batch_no) as batch_no'),
            DB::raw('MAX(item_id) as item_id'),
            DB::raw('MAX(warehouse_id) as warehouse_id'),
            DB::raw('MAX(change_type) as change_type'),
            DB::raw('MAX(changed_by) as changed_by'),
            DB::raw('MAX(remarks) as remarks'),
            DB::raw('MAX(changed_at) as changed_at'),
            DB::raw('SUM(quantity_change) as quantity_change'),
        ])
        ->with([
            'item',
            'warehouse',
        ]);

    // Search
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

    // Change Type
    if ($request->filled('change_type')) {
        $query->where('change_type', $request->change_type);
    }

    // Warehouse
    if ($request->filled('warehouse_id')) {
        $query->where('warehouse_id', $request->warehouse_id);
    }

    // Date From
    if ($request->filled('date_from')) {
        $query->whereDate('changed_at', '>=', $request->date_from);
    }

    // Date To
    if ($request->filled('date_to')) {
        $query->whereDate('changed_at', '<=', $request->date_to);
    }

    $histories = $query
        ->groupBy(DB::raw('IFNULL(batch_no, CONCAT("IND-", id))'))
        ->orderByDesc(DB::raw('MAX(changed_at)'))
        ->paginate(50)
        ->withQueryString();

    $warehouses = Warehouse::orderBy('warehouse_name')->get();

    return view('inventory.history', compact(
        'histories',
        'warehouses'
    ));
}
    public function destroy($id)
    {
        //
    }
}