<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Services\MIApprovalReview;
use App\Services\MiFinancialAmount;
use App\Services\MiFinancialWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetRequestController extends Controller
{
    public function index(Request $request): View
    {
        $budgetRequests = BudgetRequest::with('employee')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->where('employee_id', $request->user()->getKey())
            ->where('company_id', $request->user()->currentCompany()->getKey())
            ->latest()
            ->paginate(15);

        return view('mi_app.budget_requests.index', compact('budgetRequests'));
    }

    public function create(): View
    {
        Gate::authorize('create', BudgetRequest::class);

        return view('mi_app.budget_requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', BudgetRequest::class);
        $data = $request->validate([
            'department' => 'required|string|max:100',
            'objectives' => 'nullable|string',
            'travel_date_from' => 'nullable|date',
            'travel_date_to' => 'nullable|date|after_or_equal:travel_date_from',
            'place' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*' => 'required|array',
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.budget_cash' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.budget_credit_card' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.budget_travel_agent' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        $budgetRequest = DB::transaction(function () use ($data, $request) {
            $budgetRequest = BudgetRequest::create([
                'employee_id' => $request->user()->getKey(),
                'company_id' => $request->user()->currentCompany()->getKey(),
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
                    'budget_total' => MiFinancialAmount::sum([$cash, $cc, $agent]),
                ]);
            }

            $budgetRequest->recalcTotals();
            app(MiFinancialWorkflowService::class)->submitBudget($budgetRequest, $request->user());

            return $budgetRequest;
        });

        return redirect()->route('budget_requests.show', $budgetRequest)
            ->with('status', "Budget request {$budgetRequest->control_id} submitted.");
    }

    public function show(BudgetRequest $budgetRequest): View
    {
        Gate::authorize('view', $budgetRequest);
        $budgetRequest->load('items', 'employee', 'company', 'activities', 'releases', 'liquidation.items');

        return view('mi_app.budget_requests.show', compact('budgetRequest'));
    }

    public function edit(BudgetRequest $budgetRequest): View
    {
        Gate::authorize('update', $budgetRequest);
        $budgetRequest->load('items');

        return view('mi_app.budget_requests.edit', compact('budgetRequest'));
    }

    public function update(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        Gate::authorize('update', $budgetRequest);

        $data = $request->validate([
            'department' => 'required|string|max:100',
            'objectives' => 'nullable|string',
            'travel_date_from' => 'nullable|date',
            'travel_date_to' => 'nullable|date|after_or_equal:travel_date_from',
            'place' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($budgetRequest, $data): void {
            $budgetRequest = BudgetRequest::query()->whereKey($budgetRequest->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $budgetRequest);
            $before = $budgetRequest->only(array_keys($data));
            $budgetRequest->update($data);
            app(MiFinancialWorkflowService::class)->activity($budgetRequest, auth()->user(), 'budget_metadata_updated', $budgetRequest->status,
                ['metadata' => ['before' => $before, 'after' => $data]]);
        });

        return redirect()->route('budget_requests.show', $budgetRequest)->with('status', 'Budget request updated.');
    }

    public function destroy(BudgetRequest $budgetRequest): RedirectResponse
    {
        DB::transaction(function () use ($budgetRequest): void {
            $budgetRequest = BudgetRequest::query()->whereKey($budgetRequest->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('delete', $budgetRequest);
            app(MiFinancialWorkflowService::class)->activity($budgetRequest, auth()->user(), 'archived', $budgetRequest->status);
            $budgetRequest->delete();
        });

        return redirect()->route('budget_requests.index')->with('status', 'Budget request deleted.');
    }

    public function approve(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        Gate::authorize('approve', $budgetRequest);
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:2000']]);
        app(MiFinancialWorkflowService::class)->approveBudget($budgetRequest, $request->user(), $data['remarks'] ?? null,
            app(MIApprovalReview::class)->reviewedVersion($request, $budgetRequest));

        return back()->with('status', "{$budgetRequest->control_id} approved.");
    }

    public function noteByAccounting(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->noteBudget($budgetRequest, $request->user());

        return back()->with('status', "{$budgetRequest->control_id} noted by accounting.");
    }

    public function release(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->releaseBudget($budgetRequest, $request->user(), $request->only(['amount', 'currency', 'exchange_rate', 'payment_method', 'reference_no', 'note']));

        return back()->with('status', "Budget released for {$budgetRequest->control_id}.");
    }

    public function markReceived(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->confirmReceipt($budgetRequest, $request->user());

        return back()->with('status', 'Marked as received. You can now file a liquidation.');
    }

    public function resubmit(Request $request, BudgetRequest $budgetRequest): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->resubmitBudget($budgetRequest, $request->user());

        return back()->with('status', 'Budget resubmitted for approval.');
    }

    public function processing(Request $request, BudgetRequest $budgetRequest): View
    {
        Gate::authorize('viewProcessing', $budgetRequest);
        app(MIApprovalReview::class)->mark($request, $budgetRequest);
        $budgetRequest->load('items', 'employee', 'company', 'activities', 'releases', 'liquidation.items');

        return view('mi_app.budget_requests.show', compact('budgetRequest'));
    }
}
