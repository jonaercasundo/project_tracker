<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use App\Services\AccountingWorkspace;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountingWorkspaceController extends Controller
{
    public function __construct(private AccountingWorkspace $workspace) {}

    public function dashboard(Request $request): View
    {
        Gate::authorize('mi.accounting.dashboard.view');
        $filters = $this->filters($request);
        $base = $this->workspace->filtered($request->user(), $filters);
        $counters = $this->workspace->counters($request->user());
        $rows = $this->workspace->queue(clone $base, 'tasks')->orderBy('waiting_since')->paginate(15)->withQueryString();
        $budgetQueue = $this->workspace->queue(clone $base, 'budgets')->paginate(10, ['*'], 'budgets_page')->withQueryString();
        $workflowQueues = ['Budget requests awaiting approval' => (clone $base)->where('type', 'budget')->where('status', 'budget_requested')->count()];
        $ordinary = MI_Liquidation::where('company_id', $request->user()->currentCompany()->getKey());
        $totalLiquidatedVnd = BigDecimal::zero();
        if ($request->user()->can('mi.liquidation.view')) {
            foreach (MI_LiquidationItem::whereIn('liquidation_id', (clone $ordinary)->select('id'))->cursor() as $item) {
                $totalLiquidatedVnd = $totalLiquidatedVnd->plus($item->amount_vnd);
            }
        }
        $totalLiquidatedVnd = (string) $totalLiquidatedVnd->toScale(2);
        $pendingCount = $request->user()->can('mi.liquidation.view') ? (clone $ordinary)->where('status', 'Pending')->count() : 0;

        return view('accounting.dashboard', $this->data($request, $filters) + compact('rows', 'counters', 'budgetQueue', 'workflowQueues', 'totalLiquidatedVnd', 'pendingCount'));
    }

    public function index(Request $request, string $section): View
    {
        abort_unless(isset(AccountingWorkspace::SECTIONS[$section]), 404);
        Gate::authorize(AccountingWorkspace::SECTIONS[$section][1]);
        $filters = $this->filters($request);
        $query = $this->workspace->filtered($request->user(), $filters);
        $rows = $this->workspace->queue($query, $section)->orderByDesc('actionable')->orderBy('waiting_since')->paginate(20)->withQueryString();
        $documents = null;
        $activities = null;
        $payments = null;
        $settlements = null;
        $expenses = null;
        $companyId = $request->user()->currentCompany()->getKey();
        $eligible = $this->workspace->filtered($request->user(), $filters);
        if ($section === 'documents') {
            $documents = MI_LiquidationItem::with('report.preparer')->whereIn('liquidation_id', (clone $eligible)->where('type', 'ordinary')->select('id'))
                ->whereNotNull('receipt_image')->where('receipt_image', '!=', '')->orderByDesc('id')->paginate(20, ['*'], 'documents_page')->withQueryString();
        }
        if ($section === 'expenses') {
            $ordinaryItems = DB::table('mi_liquidation_items as i')->join('mi_liquidations as o', 'o.id', '=', 'i.liquidation_id')
                ->whereIn('o.id', (clone $eligible)->where('type', 'ordinary')->select('id'))
                ->selectRaw("'ordinary' as type, i.id, o.id as parent_id, o.title as reference, i.item_date as expense_date, i.expense_type as category, i.payee as description, i.amount_vnd as amount, 'VND' as currency, CASE WHEN i.receipt_image IS NOT NULL AND i.receipt_image != '' THEN 'File reference recorded' ELSE 'Missing receipt' END as receipt");
            $travelItems = DB::table('liquidation_items as i')->join('liquidations as l', 'l.id', '=', 'i.liquidation_id')->join('budget_requests as b', 'b.id', '=', 'l.budget_request_id')
                ->whereIn('l.id', (clone $eligible)->where('type', 'travel')->select('id'))
                ->selectRaw("'travel' as type, i.id, l.id as parent_id, b.control_id as reference, NULL as expense_date, i.expense_category as category, i.particular as description, i.actual_total as amount, 'Unspecified' as currency, i.receipt_attached as receipt");
            $expenses = DB::query()->fromSub($ordinaryItems->unionAll($travelItems), 'expenses')->orderByDesc('id')->paginate(30, ['*'], 'expenses_page')->withQueryString();
        }
        if ($section === 'audit') {
            $activities = DB::table('financial_activities as a')->where('a.company_id', $companyId)->where(function ($query) use ($companyId): void {
                foreach (['budget_requests' => BudgetRequest::class, 'liquidations' => Liquidation::class, 'mi_liquidations' => MI_Liquidation::class] as $type => $model) {
                    $query->orWhere(function ($q) use ($companyId, $type, $model): void {
                        $q->where('a.record_type', $type)->whereIn('a.record_id', $model::withTrashed()->where('company_id', $companyId)->select('id'));
                        if ($type === 'liquidations') {
                            $q->whereIn('a.record_id', Liquidation::withTrashed()->whereHas('budgetRequest', fn ($b) => $b->withTrashed()->where('company_id', $companyId))->select('id'));
                        }
                    });
                }
            })->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('a.created_at', '>=', $v))
                ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('a.created_at', '<=', $v))
                ->orderByDesc('a.id')->paginate(30, ['a.*'], 'activity_page')->withQueryString();
        }
        if (in_array($section, ['transactions', 'reports'], true)) {
            $payments = $this->workspace->payments($request->user())->join('budget_requests as b', 'b.id', '=', 'r.budget_request_id')
                ->when(array_filter($filters), fn ($q) => $q->whereIn('b.id', (clone $eligible)->where('type', 'budget')->select('id')))
                ->select('r.*', 'b.control_id')->orderByDesc('r.id')->paginate(20, ['*'], 'payments_page')->withQueryString();
            $settlements = DB::table('financial_settlements as s')->where('s.company_id', $companyId)
                ->whereIn('s.liquidation_id', Liquidation::withTrashed()->where('company_id', $companyId)
                    ->whereHas('budgetRequest', fn ($q) => $q->withTrashed()->where('company_id', $companyId))->select('id'))
                ->when(array_filter($filters), fn ($q) => $q->whereIn('s.liquidation_id', (clone $eligible)->where('type', 'travel')->select('id')))
                ->orderByDesc('s.id')->paginate(20, ['s.*'], 'settlements_page')->withQueryString();
        }

        return view('accounting.workspace', $this->data($request, $filters) + compact('section', 'rows', 'documents', 'activities', 'payments', 'settlements', 'expenses'));
    }

    public function budget(BudgetRequest $budgetRequest): View
    {
        Gate::authorize('viewProcessing', $budgetRequest);
        $budgetRequest->load(['employee', 'company', 'items', 'liquidation',
            'activities' => fn ($q) => $q->where('company_id', $budgetRequest->company_id),
            'releases' => fn ($q) => $q->where('company_id', $budgetRequest->company_id),
        ]);

        return view('accounting.detail', ['budget' => $budgetRequest, 'travel' => null]);
    }

    public function travel(Liquidation $liquidation): View
    {
        Gate::authorize('viewProcessing', $liquidation);
        $liquidation->load(['budgetRequest.employee', 'budgetRequest.company', 'budgetRequest.items', 'budgetRequest.liquidation', 'items',
            'budgetRequest.activities' => fn ($q) => $q->where('company_id', $liquidation->company_id),
            'budgetRequest.releases' => fn ($q) => $q->where('company_id', $liquidation->company_id),
            'activities' => fn ($q) => $q->where('company_id', $liquidation->company_id),
            'settlement' => fn ($q) => $q->where('company_id', $liquidation->company_id),
        ]);

        return view('accounting.detail', ['budget' => $liquidation->budgetRequest, 'travel' => $liquidation]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'employee' => ['nullable', 'integer'], 'department' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'], 'workflow_status' => ['nullable', Rule::in(['budget_requested', 'approved', 'released', 'in_progress', 'liquidated', 'closed', 'cancelled', 'draft', 'submitted', 'noted', 'rejected', 'returned_for_revision', 'Pending', 'Approved', 'Rejected'])],
            'type' => ['nullable', Rule::in(['budget', 'travel', 'ordinary'])],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount_min' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/D'],
            'amount_max' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/D', 'gte:amount_min'],
        ]);
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function data(Request $request, array $filters): array
    {
        $user = $request->user();
        $queueEmployees = DB::query()->fromSub($this->workspace->records($user)->select('employee_id', 'employee')->distinct(), 'employees')
            ->selectRaw('employee_id as user_id, employee as name')->orderBy('name')->get();
        $summary = collect();
        if ($user->can('mi.financial-reports.view')) {
            foreach ($this->workspace->payments($user, true)->select('r.currency', 'r.amount')->cursor() as $payment) {
                $total = $summary->get($payment->currency, (object) ['currency' => $payment->currency, 'amount' => '0.00', 'count' => 0]);
                $total->amount = (string) BigDecimal::of($total->amount)->plus((string) $payment->amount)->toScale(2);
                $total->count++;
                $summary->put($payment->currency, $total);
            }
        }

        return compact('filters', 'queueEmployees', 'summary') + ['workflowCompany' => $user->currentCompany()];
    }
}
