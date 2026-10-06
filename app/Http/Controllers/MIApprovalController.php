<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\User;
use App\Services\MIApprovalQueue;
use App\Services\MIApprovalReview;
use App\Services\MiFinancialWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MIApprovalController extends Controller
{
    public function index(Request $request, MIApprovalQueue $queue): View
    {
        $user = $request->user();
        abort_unless($user->canAccessMIApprovals(), 403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'returned', 'all'])],
            'type' => ['nullable', Rule::in(['all', 'budget', 'travel'])],
            'requester' => ['nullable', 'integer', 'min:1'], 'department' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $filters['status'] = $filters['status'] ?? 'pending';
        $filters['type'] = $filters['type'] ?? 'all';
        $queries = $queue->queries($user);
        $summary = array_fill_keys(['pending', 'approved', 'rejected', 'returned', 'total'], 0);
        foreach ($queries as $type => $query) {
            $summary['total'] += (clone $query)->count();
            foreach (['pending', 'approved', 'rejected', 'returned'] as $status) {
                $summary[$status] += $queue->status(clone $query, $type, $status)->count();
            }
        }
        foreach ($queries as $type => $query) {
            $queue->status($query, $type, $filters['status']);
            if ($filters['type'] !== 'all' && $filters['type'] !== ($type === 'budgets' ? 'budget' : 'travel')) {
                $query->whereRaw('1 = 0');
            }
            $query->when($filters['requester'] ?? null, fn (Builder $query, int $id) => $query->where($type === 'budgets' ? 'employee_id' : 'liquidated_by', $id));
            $this->filterDetails($query, $type, $filters);
            $query->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
        }
        $budgets = $queries['budgets']->with('employee', 'activities')->oldest()->orderBy('id')->paginate(15, ['*'], 'budgets_page')->withQueryString();
        $travel = $queries['travel']->with('budgetRequest.employee', 'liquidatedBy', 'activities')->oldest()->orderBy('id')->paginate(15, ['*'], 'travel_page')->withQueryString();
        $requesters = User::whereHas('companies', fn (Builder $query) => $query->where('companies.company_id', $user->currentCompany()->getKey()))->orderBy('name')->get(['user_id', 'name']);

        return view('mi_app.approvals', compact('budgets', 'travel', 'summary', 'filters', 'requesters'));
    }

    public function decide(Request $request, string $type, int $recordId, MIApprovalReview $review, MiFinancialWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()->canAccessMIApprovals(), 403);
        $record = $type === 'budget' ? BudgetRequest::findOrFail($recordId) : Liquidation::findOrFail($recordId);
        Gate::authorize('approve', $record);
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'return'])],
            'remarks' => ['required_unless:action,approve', 'nullable', 'string', 'max:2000'],
        ]);
        $workflow->decide($record, $request->user(), $data['action'], $data['remarks'] ?? null, $review->reviewedVersion($request, $record));

        return back()->with('status', 'Approval decision recorded.');
    }

    /** @param array<string, mixed> $filters */
    private function filterDetails(Builder $query, string $type, array $filters): void
    {
        $filter = function (Builder $budget) use ($filters): void {
            $budget->when($filters['department'] ?? null, fn (Builder $query, string $department) => $query->where('department', $department));
            if ($search = trim($filters['search'] ?? '')) {
                $budget->where(function (Builder $query) use ($search): void {
                    $query->where('control_id', 'like', '%'.$search.'%')->orWhere('department', 'like', '%'.$search.'%')
                        ->orWhereHas('employee', fn (Builder $employee) => $employee->where('name', 'like', '%'.$search.'%'));
                });
            }
        };
        if ($type === 'budgets') {
            $filter($query);
        } else {
            $query->whereHas('budgetRequest', $filter);
        }
    }
}
