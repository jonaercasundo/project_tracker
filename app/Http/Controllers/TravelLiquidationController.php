<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Services\MiFinancialWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// composer require barryvdh/laravel-dompdf
class TravelLiquidationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'noted', 'approved', 'closed'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $userId = $request->user()->getKey();
        $companyId = $request->user()->currentCompany()->getKey();
        $query = Liquidation::with('budgetRequest')
            ->where('liquidated_by', $userId)
            ->where('company_id', $companyId)
            ->whereHas('budgetRequest', fn ($query) => $query->where('employee_id', $userId)->where('company_id', $companyId));
        $statusCounts = (clone $query)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $liquidations = $query
            ->when($search !== '', fn ($query) => $query->whereHas('budgetRequest', fn ($budget) => $budget->where(function ($budget) use ($search): void {
                $budget->where('control_id', 'like', '%'.$search.'%')->orWhere('department', 'like', '%'.$search.'%');
            })))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();

        return view('mi_app.liquidations.index', compact('liquidations', 'statusCounts', 'search', 'status'));
    }

    /** GET /travel_liquidation/create?budget_request={id} */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->filled('budget_request')) {
            return redirect()->route('budget_requests.index')->with('status', 'Choose a received budget request to file a liquidation.');
        }

        $request->validate(['budget_request' => 'required|integer|exists:budget_requests,id']);
        $budgetRequest = BudgetRequest::with('items')->findOrFail($request->query('budget_request'));
        Gate::authorize('create', [Liquidation::class, $budgetRequest]);

        abort_unless($budgetRequest->status === 'in_progress', 422, 'Budget must be marked as received before it can be liquidated.');
        abort_if($budgetRequest->liquidation()->exists(), 422, 'This budget request already has a liquidation.');

        return view('mi_app.liquidations.create', compact('budgetRequest'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeReceipts($request);
        $data = $request->validate([
            'budget_request_id' => 'required|exists:budget_requests,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*' => 'required|array',
            'items.*.budget_request_item_id' => ['nullable', 'integer', Rule::exists('budget_request_items', 'id')->where('budget_request_id', $request->input('budget_request_id'))],
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.actual_cash' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.actual_credit_card' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.actual_travel_agent' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.receipt_attached' => 'required|in:yes,no,n_a',
            'items.*.remarks' => 'nullable|string',
        ]);

        $budgetRequest = BudgetRequest::findOrFail($data['budget_request_id']);
        Gate::authorize('create', [Liquidation::class, $budgetRequest]);

        $liquidation = DB::transaction(function () use ($data, $budgetRequest, $request) {
            $budgetRequest = BudgetRequest::query()->whereKey($budgetRequest->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('create', [Liquidation::class, $budgetRequest]);
            abort_unless($budgetRequest->status === 'in_progress', 422, 'Budget must be marked as received before it can be liquidated.');
            abort_if($budgetRequest->liquidation()->exists(), 422, 'This budget request already has a liquidation.');
            $liquidation = Liquidation::create([
                'budget_request_id' => $budgetRequest->id,
                'company_id' => $budgetRequest->company_id,
                'liquidated_by' => $request->user()->getKey(),
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
            app(MiFinancialWorkflowService::class)->submitLiquidation($liquidation, $request->user());

            return $liquidation;
        });

        return redirect()->route('travel_liquidation.show', $liquidation)
            ->with('status', 'Liquidation submitted for review.');
    }

    public function show(Liquidation $liquidation): View
    {
        Gate::authorize('view', $liquidation);
        $liquidation->load('items.budgetRequestItem', 'budgetRequest.items', 'budgetRequest.releases', 'budgetRequest.activities', 'budgetRequest.approver', 'budgetRequest.accountant', 'budgetRequest.releaser', 'company', 'activities', 'settlement', 'liquidatedBy');

        return view('mi_app.liquidations.show', compact('liquidation'));
    }

    public function edit(Liquidation $liquidation): View
    {
        Gate::authorize('update', $liquidation);
        $liquidation->load('items', 'budgetRequest.items');
        abort_unless($liquidation->status === 'draft', 403, 'Only a draft liquidation can be edited.');

        $budgetRequest = $liquidation->budgetRequest;

        return view('mi_app.liquidations.create', compact('liquidation', 'budgetRequest'));
    }

    public function update(Request $request, Liquidation $liquidation): RedirectResponse
    {
        Gate::authorize('update', $liquidation);
        $this->normalizeReceipts($request);

        $data = $request->validate([
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*' => 'required|array',
            'items.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('liquidation_items', 'id')->where('liquidation_id', $liquidation->getKey())],
            'items.*.expense_category' => 'required|string|max:100',
            'items.*.particular' => 'required|string|max:255',
            'items.*.actual_cash' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.actual_credit_card' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.actual_travel_agent' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'items.*.receipt_attached' => 'required|in:yes,no,n_a',
        ]);

        DB::transaction(function () use ($data, $liquidation): void {
            $liquidation = Liquidation::query()->whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $liquidation);
            $before = $liquidation->items()->get()->toArray();
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
            app(MiFinancialWorkflowService::class)->activity($liquidation, auth()->user(), 'liquidation_updated', $liquidation->status, ['metadata' => ['before_items' => $before, 'after_items' => $liquidation->items()->get()->toArray()]]);
        });

        return redirect()->route('travel_liquidation.show', $liquidation)->with('status', 'Liquidation updated.');
    }

    public function destroy(Liquidation $liquidation): RedirectResponse
    {
        DB::transaction(function () use ($liquidation): void {
            $liquidation = Liquidation::query()->whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('delete', $liquidation);
            app(MiFinancialWorkflowService::class)->activity($liquidation, auth()->user(), 'archived', $liquidation->status);
            $liquidation->delete();
        });

        return redirect()->route('travel_liquidation.index')->with('status', 'Liquidation deleted.');
    }

    public function downloadPdf(Liquidation $liquidation): Response
    {
        Gate::authorize('view', $liquidation);
        $liquidation->load('items.budgetRequestItem', 'budgetRequest.items', 'budgetRequest.releases', 'budgetRequest.activities', 'budgetRequest.approver', 'budgetRequest.accountant', 'budgetRequest.releaser', 'company', 'activities', 'settlement', 'liquidatedBy');

        $pdf = Pdf::loadView('mi_app.liquidations.pdf', compact('liquidation'));

        return $pdf->download("{$liquidation->budgetRequest->control_id}-liquidation.pdf");
    }

    public function noteByAccounting(Request $request, Liquidation $liquidation): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->reviewLiquidation($liquidation, $request->user());

        return back()->with('status', 'Liquidation noted by accounting.');
    }

    public function approve(Request $request, Liquidation $liquidation): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->approveLiquidation($liquidation, $request->user());

        return back()->with('status', 'Liquidation approved. Settlement and closure are separate actions.');
    }

    public function recordSettlement(Request $request, Liquidation $liquidation): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->recordSettlement($liquidation, $request->user(), $request->all());

        return back()->with('status', 'Settlement evidence recorded.');
    }

    public function close(Request $request, Liquidation $liquidation): RedirectResponse
    {
        app(MiFinancialWorkflowService::class)->closeLiquidation($liquidation, $request->user());

        return back()->with('status', 'Liquidation closed.');
    }

    public function processing(Liquidation $liquidation): View
    {
        Gate::authorize('viewProcessing', $liquidation);
        $liquidation->load('items.budgetRequestItem', 'budgetRequest.items', 'budgetRequest.releases', 'budgetRequest.activities', 'budgetRequest.approver', 'budgetRequest.accountant', 'budgetRequest.releaser', 'company', 'activities', 'settlement', 'liquidatedBy');

        return view('mi_app.liquidations.show', compact('liquidation'));
    }

    private function normalizeReceipts(Request $request): void
    {
        $items = $request->input('items');
        if (! is_array($items)) {
            return;
        }
        foreach ($items as &$item) {
            if (is_array($item) && is_string($item['receipt_attached'] ?? null)) {
                $item['receipt_attached'] = match ($item['receipt_attached']) {
                    'Yes' => 'yes', 'No' => 'no', 'N/A' => 'n_a', default => $item['receipt_attached'],
                };
            }
        }
        $request->merge(['items' => $items]);
    }
}
