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
        $query = Item::query()
            ->leftJoin('projects', 'projects.project_id', '=', 'items.project_id')
            ->select(
                'items.*',
                'projects.project_name'
            );

        // Search
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('items.item_id', 'like', "%{$search}%")
                ->orWhere('items.item_name', 'like', "%{$search}%")
                ->orWhere('items.code_prefix', 'like', "%{$search}%")
                ->orWhere('projects.project_name', 'like', "%{$search}%");

            });
        }

        // Project Filter
        if ($request->filled('project')) {
            $query->where('items.project_id', $request->project);
        }

        // Active Filter
        if ($request->filled('active')) {
            $query->where('items.active', $request->active);
        }

        $items = $query
            ->orderBy('items.created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $projects = DB::table('projects')
            ->orderBy('project_name')
            ->get();

        return view('operation.index', compact(
            'items',
            'projects'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        //
    }
}
