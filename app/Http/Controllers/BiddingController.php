<?php

namespace App\Http\Controllers;

use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiddingController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectInformation::query();

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('project_id', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('procuring_entity', 'like', "%{$search}%")
                    ->orWhere('lot_no', 'like', "%{$search}%");

            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query
            ->latest()
            ->paginate(10);

        return view('finance.bidding.index', compact('projects'));
    }

    public function create()
    {
        return view('finance.bidding.create');
    }
    private function psgcName($code)
    {
        return DB::table('psgc')
            ->where('psgc_code', $code)
            ->value('name') ?? $code;
    }
    public function store(Request $request)
    {
        $request->validate([

            'project_name' => 'required|string|max:255',
            'project_id' => 'required|string|max:255',
            'procuring_entity' => 'nullable|string|max:255',

        ]);

        DB::transaction(function () use ($request) {

            $project = ProjectInformation::create([

                'project_name' => $request->project_name,
                'project_id' => $request->project_id,
                'procuring_entity' => $request->procuring_entity,
                'approved_budget_contract_abc' => $request->approved_budget_contract_abc,
                'lot_no' => $request->lot_no,
                'delivery_period' => $request->delivery_period,
                'country' => $request->country,
                'region' => $this->psgcName($request->region),
                'province' => $this->psgcName($request->province),
                'city_municipality' => $this->psgcName($request->city),
                'barangay' => $this->psgcName($request->barangay),
                'delivery_address' => $request->address,
                'date_of_bid_opening' => $request->date_of_bid_opening,
                'notes_special_condition' => $request->notes_special_condition,
                'prepared_by' => $request->prepared_by,
                'prepared_date' => $request->prepared_date,
                'verified_by' => $request->verified_by,
                'status' => $request->status ?? 'Draft',

            ]);

            foreach ($request->items ?? [] as $item) {

                if (empty($item['item_description'])) {
                    continue;
                }

                $project->items()->create([

                    'item_no' => $item['item_no'],
                    'item_description' => $item['item_description'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_amount' => $item['total_amount'],
                    'brand' => $item['brand'],
                    'remarks' => $item['remarks'],

                ]);
            }
        });

        return redirect()
            ->route('bidding.index')
            ->with('success', 'Bidding document created successfully.');
    }

    public function show(ProjectInformation $bidding)
    {
        $bidding->load('items');

        return view('finance.bidding.show', [
            'project' => $bidding
        ]);
    }

    public function edit(ProjectInformation $bidding)
    {
        $bidding->load('items');

        return view('finance.bidding.edit', [
            'project' => $bidding
        ]);
    }

    public function update(Request $request, ProjectInformation $bidding)
    {
        $request->validate([

            'project_name' => 'required|string|max:255',
            'project_id' => 'required|string|max:255',

        ]);

        DB::transaction(function () use ($request, $bidding) {

            $bidding->update([

                'project_name' => $request->project_name,
                'project_id' => $request->project_id,
                'procuring_entity' => $request->procuring_entity,
                'approved_budget_contract_abc' => $request->approved_budget_contract_abc,
                'lot_no' => $request->lot_no,
                'delivery_period' => $request->delivery_period,
                'country' => $request->country,
                'region' => $request->region,
                'province' => $request->province,
                'city_municipality' => $request->city_municipality,
                'barangay' => $request->barangay,
                'delivery_address' => $request->address,
                'date_of_bid_opening' => $request->date_of_bid_opening,
                'notes_special_condition' => $request->notes_special_condition,
                'prepared_by' => $request->prepared_by,
                'prepared_date' => $request->prepared_date,
                'verified_by' => $request->verified_by,
                'status' => $request->status,

            ]);

            $bidding->items()->delete();

            foreach ($request->items ?? [] as $item) {

                if (empty($item['item_description'])) {
                    continue;
                }

                $bidding->items()->create([

                    'item_no' => $item['item_no'],
                    'item_description' => $item['item_description'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_amount' => $item['total_amount'],
                    'brand' => $item['brand'],
                    'remarks' => $item['remarks'],

                ]);
            }

        });

        return redirect()
            ->route('bidding.show', $bidding)
            ->with('success', 'Bidding document updated successfully.');
    }

    public function destroy(ProjectInformation $bidding)
    {
        DB::transaction(function () use ($bidding) {

            $bidding->items()->delete();

            $bidding->delete();

        });

        return redirect()
            ->route('bidding.index')
            ->with('success', 'Bidding document deleted successfully.');
    }
}