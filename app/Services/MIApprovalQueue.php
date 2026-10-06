<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MIApprovalQueue
{
    /** @return array{budgets: Builder, travel: Builder} */
    public function queries(User $user): array
    {
        $companyId = $user->currentCompany()->getKey();

        return [
            'budgets' => BudgetRequest::where('company_id', $companyId)->where('employee_id', '!=', $user->getKey())
                ->when(! $user->canApproveMI('mi.budget.approve'), fn (Builder $query) => $query->whereRaw('1 = 0')),
            'travel' => Liquidation::where('company_id', $companyId)->where('liquidated_by', '!=', $user->getKey())
                ->whereHas('budgetRequest', fn (Builder $query) => $query->where('company_id', $companyId))
                ->whereIn('status', ['noted', 'approved', 'closed', 'rejected', 'returned_for_revision'])
                ->when(! $user->canApproveMI('mi.travel.approve'), fn (Builder $query) => $query->whereRaw('1 = 0')),
        ];
    }

    public function pendingCount(User $user): int
    {
        $queries = $this->queries($user);

        return $this->status($queries['budgets'], 'budgets', 'pending')->count()
            + $this->status($queries['travel'], 'travel', 'pending')->count();
    }

    public function status(Builder $query, string $type, string $status): Builder
    {
        if ($status === 'pending') {
            $query->where('status', $type === 'budgets' ? 'budget_requested' : 'noted')->whereNull('approved_at');
            if ($type === 'travel') {
                $query->whereHas('budgetRequest', fn (Builder $budget) => $budget->where('status', 'in_progress'));
            }
        } elseif ($status === 'approved') {
            $query->whereIn('status', $type === 'budgets' ? ['approved', 'released', 'in_progress', 'liquidated', 'closed'] : ['approved', 'closed']);
        } elseif ($status !== 'all') {
            $query->where('status', $status === 'returned' ? 'returned_for_revision' : 'rejected');
        }

        return $query;
    }
}
