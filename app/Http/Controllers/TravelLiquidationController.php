<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // composer require barryvdh/laravel-dompdf
class TravelLiquidationController extends Controller
{
    public function index(Request $request)
    {
        $liquidations = Liquidation::with('budgetRequest')
            ->when(! $request->user()->hasRole('admin'), fn ($q) => $q->where('liquidated_by', $request->user()->id))
            ->latest()
            ->paginate(15);

        return view('mi_app.liquidations.index', compact('liquidations'));
    }

    /** GET /liquidation/create?budget_request={id} */
    public function create(Request $request)
    {
        $budgetRequest = BudgetRequest::with('items')->findOrFail($request->query('budget_request'));

        abort_unless($budgetRequest->status === 'in_progress', 422, 'Budget must be marked as received before it can be liquidated.');
        abort_if($budgetRequest->liquidation()->exists(), 422, 'This budget request already has a liquidation.');

        return view('mi_app.liquidations.create', compact('budgetRequest'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'budget_request_id' => 'required|exists:budget_requests,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.budget_request_item_id' => 'nullable|exists:budget_request_items,id',
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.actual_cash' => 'nullable|numeric|min:0',
            'items.*.actual_credit_card' => 'nullable|numeric|min:0',
            'items.*.actual_travel_agent' => 'nullable|numeric|min:0',
            'items.*.receipt_attached' => 'required|in:Yes,No,N/A',
            'items.*.remarks' => 'nullable|string',
        ]);

        $budgetRequest = BudgetRequest::findOrFail($data['budget_request_id']);
        abort_unless($budgetRequest->status === 'in_progress', 422, 'Budget must be marked as received before it can be liquidated.');

        $liquidation = DB::transaction(function () use ($data, $budgetRequest, $request) {
            $liquidation = Liquidation::create([
                'budget_request_id' => $budgetRequest->id,
                'liquidated_by' => $request->user()->id,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $liquidation->items()->create([
                    'budget_request_item_id' => $row['budget_request_item_id'] ?? null,
                    'expense_category' => $row['expense_category'],
                    'particular' => $row['particular'],
                    'actual_cash' => $row['actual_cash'] ?? 0,
                    'actual_credit_card' => $row['actual_credit_card'] ?? 0,
                    'actual_travel_agent' => $row['actual_travel_agent'] ?? 0,
                    'receipt_attached' => $row['receipt_attached'],
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }

            $liquidation->refresh();
            $liquidation->recalcTotals(); // <-- automated balance check against budget_total
            $liquidation->submit();

            return $liquidation;
        });

        return redirect()->route('liquidation.show', $liquidation)
            ->with('status', 'Liquidation submitted for review.');
    }

    public function show(Liquidation $liquidation)
    {
        $liquidation->load('items', 'budgetRequest.items', 'liquidatedBy');

        return view('mi_app.liquidations.show', compact('liquidation'));
    }

    public function edit($id)
    {
        $liquidation = Liquidation::with('items', 'budgetRequest.items')->findOrFail($id);
        abort_unless($liquidation->status === 'draft', 403, 'Only a draft liquidation can be edited.');

        return view('mi_app.liquidations.edit', compact('liquidation'));
    }

    public function update(Request $request, Liquidation $liquidation)
    {
        abort_unless(in_array($liquidation->status, ['draft', 'submitted']), 403, 'This liquidation can no longer be edited.');

        $data = $request->validate([
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:liquidation_items,id',
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.actual_cash' => 'nullable|numeric|min:0',
            'items.*.actual_credit_card' => 'nullable|numeric|min:0',
            'items.*.actual_travel_agent' => 'nullable|numeric|min:0',
            'items.*.receipt_attached' => 'required|in:Yes,No,N/A',
        ]);

        DB::transaction(function () use ($data, $liquidation) {
            foreach ($data['items'] as $row) {
                $liquidation->items()->updateOrCreate(
                    ['id' => $row['id'] ?? null],
                    [
                        'expense_category' => $row['expense_category'],
                        'particular' => $row['particular'],
                        'actual_cash' => $row['actual_cash'] ?? 0,
                        'actual_credit_card' => $row['actual_credit_card'] ?? 0,
                        'actual_travel_agent' => $row['actual_travel_agent'] ?? 0,
                        'receipt_attached' => $row['receipt_attached'],
                    ]
                );
            }

            $liquidation->update(['remarks' => $data['remarks'] ?? null]);
            $liquidation->recalcTotals(); // re-run the balance check after every edit
        });

        return redirect()->route('liquidation.show', $liquidation)->with('status', 'Liquidation updated.');
    }

    public function destroy(Liquidation $liquidation)
    {
        abort_unless($liquidation->status === 'draft', 403, 'Only a draft liquidation can be deleted.');
        $liquidation->delete();

        return redirect()->route('liquidation.index')->with('status', 'Liquidation deleted.');
    }

    public function downloadPdf(Liquidation $liquidation)
    {
        $liquidation->load('items', 'budgetRequest.items', 'liquidatedBy');

        $pdf = Pdf::loadView('mi_app.liquidations.pdf', compact('liquidation'));

        return $pdf->download("{$liquidation->budgetRequest->control_id}-liquidation.pdf");
    }

    // --- Accounting review / balance-check sign-off ---

    public function noteByAccounting(Liquidation $liquidation)
    {
        abort_unless($liquidation->status === 'submitted', 422, 'Liquidation is not pending review.');
        $liquidation->noteByAccounting(Auth::user());

        return back()->with('status', 'Liquidation noted by accounting.');
    }

    public function approve(Liquidation $liquidation)
    {
        abort_unless($liquidation->status === 'noted', 422, 'Liquidation must be noted by accounting first.');
        $liquidation->approve(Auth::user()); // also flips the budget request to "liquidated"

        return back()->with('status', 'Liquidation approved and closed.');
    }
}
