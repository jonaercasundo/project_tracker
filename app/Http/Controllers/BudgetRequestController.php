<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class BudgetRequestController extends Controller
{
    public function index(Request $request)
    {
        $budgetRequests = BudgetRequest::with('employee')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when(! $request->user()->hasRole('admin'), fn ($q) => $q->where('employee_id', $request->user()->id))
            ->latest()
            ->paginate(15);

        return view('mi_app.budget_requests.index', compact('budgetRequests'));
    }

    public function create()
    {
        return view('mi_app.budget_requests.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department' => 'required|string|max:100',
            'objectives' => 'nullable|string',
            'travel_date_from' => 'nullable|date',
            'travel_date_to' => 'nullable|date|after_or_equal:travel_date_from',
            'place' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.budget_cash' => 'nullable|numeric|min:0',
            'items.*.budget_credit_card' => 'nullable|numeric|min:0',
            'items.*.budget_travel_agent' => 'nullable|numeric|min:0',
        ]);

        $budgetRequest = DB::transaction(function () use ($data, $request) {
            $budgetRequest = BudgetRequest::create([
                'employee_id' => $request->user()->id,
                'department' => $data['department'],
                'objectives' => $data['objectives'] ?? null,
                'travel_date_from' => $data['travel_date_from'] ?? null,
                'travel_date_to' => $data['travel_date_to'] ?? null,
                'place' => $data['place'] ?? null,
                'country' => $data['country'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'budget_requested',
            ]);

            foreach ($data['items'] as $row) {
                $cash = $row['budget_cash'] ?? 0;
                $cc = $row['budget_credit_card'] ?? 0;
                $agent = $row['budget_travel_agent'] ?? 0;

                $budgetRequest->items()->create([
                    'expense_category' => $row['expense_category'],
                    'particular' => $row['particular'],
                    'budget_cash' => $cash,
                    'budget_credit_card' => $cc,
                    'budget_travel_agent' => $agent,
                    'budget_total' => $cash + $cc + $agent,
                ]);
            }

            $budgetRequest->recalcTotals();

            return $budgetRequest;
        });

        return redirect()->route('mi_app.budget_requests.show', $budgetRequest)
            ->with('status', "Budget request {$budgetRequest->control_id} submitted.");
    }

    public function show(BudgetRequest $budgetRequest)
    {
        $budgetRequest->load('items', 'employee', 'liquidation.items');

        return view('mi_app.budget_requests.show', compact('budgetRequest'));
    }

    public function edit(BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'budget_requested', 403, 'Only a pending request can be edited.');
        $budgetRequest->load('items');

        return view('mi_app.budget_requests.edit', compact('budgetRequest'));
    }

    public function update(Request $request, BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'budget_requested', 403, 'Only a pending request can be edited.');

        $data = $request->validate([
            'department' => 'required|string|max:100',
            'objectives' => 'nullable|string',
            'travel_date_from' => 'nullable|date',
            'travel_date_to' => 'nullable|date|after_or_equal:travel_date_from',
            'place' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $budgetRequest->update($data);

        return redirect()->route('mi_app.budget_requests.show', $budgetRequest)->with('status', 'Budget request updated.');
    }

    public function destroy(BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'budget_requested', 403, 'Only a pending request can be deleted.');
        $budgetRequest->delete();

        return redirect()->route('mi_app.budget_requests.index')->with('status', 'Budget request deleted.');
    }

    // --- Workflow actions, one per box in the diagram ---

    public function approve(Request $request, BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'budget_requested', 422, 'Request is not pending approval.');
        $budgetRequest->approve(Auth::user());

        return back()->with('status', "{$budgetRequest->control_id} approved.");
    }

    public function noteByAccounting(Request $request, BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'approved', 422, 'Request must be approved before accounting can process it.');
        $budgetRequest->noteByAccounting(Auth::user());

        return back()->with('status', "{$budgetRequest->control_id} noted by accounting.");
    }

    public function release(Request $request, BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'approved', 422, 'Request must be approved before releasing funds.');
        $budgetRequest->release(Auth::user());

        return back()->with('status', "Budget released for {$budgetRequest->control_id}.");
    }

    public function markReceived(Request $request, BudgetRequest $budgetRequest)
    {
        abort_unless($budgetRequest->status === 'released', 422, 'Budget has not been released yet.');
        abort_unless($budgetRequest->employee_id === Auth::id(), 403);

        $budgetRequest->markReceived();

        return back()->with('status', 'Marked as received. You can now file a liquidation.');
    }
}
