<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MIApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('mi.budget.approve') || $user->can('mi.travel.approve'), 403);
        $companyId = $user->currentCompany()->getKey();
        $budgets = BudgetRequest::where('company_id', $companyId)->where('employee_id', '!=', $user->getKey())
            ->where('status', 'budget_requested')->whereNull('approved_at')->with('employee')
            ->when(! $user->can('mi.budget.approve'), fn ($q) => $q->whereRaw('1 = 0'))
            ->oldest()->paginate(15, ['*'], 'budgets_page');
        $travel = Liquidation::where('company_id', $companyId)->where('liquidated_by', '!=', $user->getKey())
            ->whereHas('budgetRequest', fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'noted')->whereNull('approved_at')->with('budgetRequest.employee')
            ->when(! $user->can('mi.travel.approve'), fn ($q) => $q->whereRaw('1 = 0'))
            ->oldest()->paginate(15, ['*'], 'travel_page');

        return view('mi_app.approvals', compact('budgets', 'travel'));
    }
}
