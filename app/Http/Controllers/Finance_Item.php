<?php

namespace App\Http\Controllers;

use App\Models\New\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Finance_Item extends Controller
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

        return view('finance.item.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('finance.item.create');
    }

    /**
     * Store a newly created resource.
     */
   public function store(Request $request)
{
    $validated = $request->validate([
        'item_name'       => ['required', 'string', 'max:255'],
        'code_prefix'     => ['required', 'string', 'max:20'],
        'unit'            => ['nullable', 'string', 'max:50'],
        'description'     => ['nullable', 'string'],
        'price'           => ['nullable', 'numeric'],
        'supplier_price'  => ['nullable', 'numeric'],
        'active'          => ['nullable', 'boolean'],
    ]);

    $validated['active'] = $request->boolean('active');
    $validated['code_prefix'] = strtoupper($validated['code_prefix']);

    Item::create($validated);

    return redirect()
        ->route('items.index')
        ->with('success', 'Item created successfully.');
}

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return view('finance.item.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        return view('finance.item.edit', compact('item'));
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'item_name'       => ['required', 'string', 'max:255'],
            'code_prefix'     => ['required', 'string', 'max:20'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'description'     => ['nullable', 'string'],
            'price'           => ['nullable', 'numeric'],
            'supplier_price'  => ['nullable', 'numeric'],
            'active'          => ['nullable', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['code_prefix'] = strtoupper($validated['code_prefix']);

        $item->update($validated);

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
