<?php

namespace App\Http\Controllers;

use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\New\Item;

class BiddingController extends Controller
{
    private function normalizeAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace([',', ' '], '', trim((string) $value));
    }

    private function normalizeText($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return trim((string) $value);
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

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
                'project_name' => $this->normalizeText($request->input('project_name')),
                'project_id' => $this->normalizeText($request->input('project_id')),
                'procuring_entity' => $this->normalizeText($request->input('procuring_entity')),
                'approved_budget_contract_abc' => $this->normalizeAmount($request->input('approved_budget_contract_abc')),
                'delivery_period' => $this->normalizeText($request->input('delivery_period')),
                'date_of_bid_opening' => $this->normalizeDate($request->input('date_of_bid_opening')),
                'prepared_by' => $this->normalizeText($request->input('prepared_by')),
                'prepared_date' => $this->normalizeDate($request->input('prepared_date')),
                'verified_by' => $this->normalizeText($request->input('verified_by')),
                'status' => $request->input('status') ?? 'Draft',
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
                    $quantity = $this->normalizeAmount($item['quantity'] ?? null);
                    $unitCost = $this->normalizeAmount($item['unit_cost'] ?? null);
                    $totalAmount = $this->normalizeAmount($item['total_amount'] ?? ($quantity * $unitCost));

                    $lot->items()->create([
                        'item_no'          => $index + 1,
                        'item_description' => $this->normalizeText($item['item_description'] ?? null),
                        'unit'             => $this->normalizeText($item['unit'] ?? null),
                        'quantity'         => $quantity,
                        'unit_cost'        => $unitCost,
                        'total_amount'     => $totalAmount,
                        'brand'            => $this->normalizeText($item['brand'] ?? null),
                        'remarks'          => $this->normalizeText($item['remarks'] ?? null),
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
        $bidding->load('lots.items');

        return view('finance.bidding.show', [
            'project' => $bidding
        ]);
    }

    public function edit(ProjectInformation $bidding)
    {
        $bidding->load('lots.items');

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

                'project_name' => $this->normalizeText($request->input('project_name')),
                'project_id' => $this->normalizeText($request->input('project_id')),
                'procuring_entity' => $this->normalizeText($request->input('procuring_entity')),
                'approved_budget_contract_abc' => $this->normalizeAmount($request->input('approved_budget_contract_abc')),
                'lot_no' => $this->normalizeText($request->input('lot_no')),
                'delivery_period' => $this->normalizeText($request->input('delivery_period')),
                'country' => $this->normalizeText($request->input('country')),
                'region' => $this->normalizeText($request->input('region')),
                'province' => $this->normalizeText($request->input('province')),
                'city_municipality' => $this->normalizeText($request->input('city_municipality')),
                'barangay' => $this->normalizeText($request->input('barangay')),
                'delivery_address' => $this->normalizeText($request->input('address') ?? $request->input('delivery_address')),
                'date_of_bid_opening' => $this->normalizeDate($request->input('date_of_bid_opening')),
                'notes_special_condition' => $this->normalizeText($request->input('notes_special_condition')),
                'prepared_by' => $this->normalizeText($request->input('prepared_by')),
                'prepared_date' => $this->normalizeDate($request->input('prepared_date')),
                'verified_by' => $this->normalizeText($request->input('verified_by')),
                'status' => $request->input('status'),

            ]);

            foreach ($bidding->lots as $lot) {
                $lot->items()->delete();
            }

            foreach ($request->items ?? [] as $item) {

                if (empty($item['item_description'])) {
                    continue;
                }
                $total = (float)($item['quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0);
                foreach ($request->lots as $lotIndex => $lotData) {

                    $lot = $bidding->lots()->updateOrCreate(
                        ['lot_no' => $lotData['lot_no']],
                        [
                            'country' => $lotData['country_code'] == 'PH' ? 'Philippines' : $lotData['country_code'],
                            'region' => $this->psgcName($lotData['region_code'] ?? null),
                            'province' => $this->psgcName($lotData['province_code'] ?? null),
                            'city_municipality' => $this->psgcName($lotData['city_code'] ?? null),
                            'barangay' => $this->psgcName($lotData['barangay_code'] ?? null),
                            'delivery_address' => $lotData['delivery_address'] ?? null,
                        ]
                    );

                    // reset items per lot
                    $lot->items()->delete();

                    foreach ($lotData['items'] ?? [] as $index => $item) {

                        if (empty($item['item_description'])) continue;

                        $lot->items()->create([
                            'item_no' => $index + 1,
                            'item_description' => $item['item_description'],
                            'unit' => $item['unit'],
                            'quantity' => (float) $item['quantity'],
                            'unit_cost' => (float) str_replace(',', '', $item['unit_cost']),
                            'total_amount' => (float) str_replace(',', '', $item['total_amount']),
                            'brand' => $item['brand'] ?? null,
                            'remarks' => $item['remarks'] ?? null,
                        ]);
                    }
                }
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