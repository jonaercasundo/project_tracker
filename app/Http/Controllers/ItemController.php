<?php

namespace App\Http\Controllers;

use App\Models\New\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('item_id', 'like', "%{$search}%")
                ->orWhere('item_name', 'like', "%{$search}%")
                ->orWhere('code_prefix', 'like', "%{$search}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('operation.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = DB::table('projects')
            ->where('status', 'Active')
            ->orderBy('project_name')
            ->get();

        return view('operation.create', compact('projects'));
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'item_name'  => ['required', 'string', 'max:255'],
            'code_prefix'=> ['required', 'string', 'max:20', 'unique:items,code_prefix'],
            'active'     => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {

            Item::create([
                'project_id' => $validated['project_id'],
                'item_name'  => $validated['item_name'],
                'code_prefix'=> strtoupper($validated['code_prefix']),
                'active'     => $validated['active'],
            ]);

        });

        return redirect()
            ->route('items.index')
            ->with('success', 'Item created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        $item->loadMissing('project');

        return view('operation.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        $projects = DB::table('projects')
            ->where('status', 'Active')
            ->orderBy('project_name')
            ->get();

        return view('operation.edit', compact(
            'item',
            'projects'
        ));
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'item_name'  => ['required', 'string', 'max:255'],
            'code_prefix'=> ['required', 'string', 'max:20', 'unique:items,code_prefix,' . $item->id],
            'active'     => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $item) {

            $item->update([
                'project_id' => $validated['project_id'],
                'item_name'  => $validated['item_name'],
                'code_prefix'=> strtoupper($validated['code_prefix']),
                'active'     => $validated['active'],
            ]);

        });

        return redirect()
            ->route('items.index')
            ->with('success', 'Item updated successfully.');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Item $item)
    {
        DB::transaction(function () use ($item) {
            $item->delete();
        });

        return redirect()
            ->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }
}