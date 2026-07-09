<?php

namespace App\Http\Controllers;
use App\Models\Inventory;
use App\Models\Project;

use Illuminate\Http\Request;

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
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}