<?php

namespace App\Http\Controllers;

use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\New\Item;

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
        $catalogItems = Item::where('active', 1)
            ->orderBy('item_name')
            ->get();

        return view('finance.bidding.create', compact('catalogItems'));
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

            'lots' => 'required|array|min:1',
            'lots.*.lot_no' => 'required|string|max:50',

            'lots.*.items' => 'nullable|array',
            'lots.*.items.*.item_description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {

            $project = ProjectInformation::create([
                'project_name' => $request->project_name,
                'project_id' => $request->project_id,
                'procuring_entity' => $request->procuring_entity,
                'approved_budget_contract_abc' => $request->approved_budget_contract_abc,
                'delivery_period' => $request->delivery_period,
                'date_of_bid_opening' => $request->date_of_bid_opening,
                'prepared_by' => $request->prepared_by,
                'prepared_date' => $request->prepared_date,
                'verified_by' => $request->verified_by,
                'status' => $request->status ?? 'Draft',
            ]);

            foreach ($request->lots as $lotData) {

                $lot = $project->lots()->create([
                    'lot_no'                  => $lotData['lot_no'],
                    'country' => $lotData['country_code'] == 'PH'
                        ? 'Philippines'
                        : $lotData['country_code'],
                    'region' => $this->psgcName($lotData['region_code'] ?? null),
                    'province' => $this->psgcName($lotData['province_code'] ?? null),
                    'city_municipality' => $this->psgcName($lotData['city_code'] ?? null),
                    'barangay' => $this->psgcName($lotData['barangay_code'] ?? null),
                    'delivery_address' => $lotData['delivery_address'] ?? null,
                ]);

                foreach ($lotData['items'] ?? [] as $index => $item) {

                    if (empty($item['item_description'])) {
                        continue;
                    }
                    $quantity = (float)($item['quantity'] ?? 0);
                    $unitCost = (float)str_replace(',', '', $item['unit_cost'] ?? 0);
                    $total_amount = (float)str_replace(',', '', $item['total_amount'] ?? 0);
                    $lot->items()->create([
                        'item_no'          => $index + 1,
                        'item_description' => $item['item_description'],
                        'unit'             => $item['unit'],
                        'quantity'         => $quantity,
                        'unit_cost'        => $unitCost,
                        'total_amount'     => $total_amount,
                        'brand'            => $item['brand'],
                        'remarks'          => $item['remarks'],
                    ]);
                }
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
                'approved_budget_contract_abc' => str_replace(',', '', $request->approved_budget_contract_abc),
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
                $total = (float)($item['quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0);
                $bidding->items()->create([

                    'item_no' => $item['item_no'],
                    'item_description' => $item['item_description'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_amount' => $total,
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