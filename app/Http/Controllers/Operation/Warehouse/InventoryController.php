<?php

namespace App\Http\Controllers\Operation\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Inventory;   // TODO: confirm model name/namespace
use App\Models\Project;     // TODO: confirm model name/namespace
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display the Inventory List page.
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['item', 'warehouse']);

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Search by related item's name
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%");
            });
        }

        // Filter by inventory status
        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->input('inventory_status'));
        }

        $inventories = $query
            ->orderByDesc('inventory_id')
            ->paginate(20)
            ->withQueryString();

        $projects = Project::orderBy('project_name')->get();

        return view('operation.warehouse.inventory.index', [
            'inventories' => $inventories,
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new inventory record.
     */
    public function create()
    {
        $projects = Project::orderBy('project_name')->get();

        return view('operation.warehouse.inventory.create', [
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created inventory record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,item_id'],
            'warehouse_id' => ['required', 'exists:warehouses,warehouse_id'],
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'qty' => ['required', 'numeric', 'min:0'],
            'inventory_status' => ['required', 'in:For Approval,Approved'],
        ]);

        $inventory = Inventory::create($validated);

        return redirect()
            ->route('inventory.show', $inventory->inventory_id)
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display a single inventory record.
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['item', 'warehouse']);

        return view('operation.warehouse.inventory.show', [
            'inventory' => $inventory,
        ]);
    }

    /**
     * Show the form for editing an inventory record.
     */
    public function edit(Inventory $inventory)
    {
        $inventory->load(['item', 'warehouse']);
        $projects = Project::orderBy('project_name')->get();

        return view('operation.warehouse.inventory.edit', [
            'inventory' => $inventory,
            'projects' => $projects,
        ]);
    }

    /**
     * Update an inventory record.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,warehouse_id'],
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'qty' => ['required', 'numeric', 'min:0'],
            'inventory_status' => ['required', 'in:For Approval,Approved'],
        ]);

        $inventory->update($validated);

        return redirect()
            ->route('inventory.show', $inventory->inventory_id)
            ->with('success', 'Inventory item updated successfully.');
    }
}