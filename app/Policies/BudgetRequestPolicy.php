<?php

namespace App\Policies;

use App\Models\BudgetRequest;
use App\Models\User;

class BudgetRequestPolicy
{
    public function view(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->companyMatches($user, $budgetRequest) && $user->hasMIUserAccess() && (int) $budgetRequest->employee_id === (int) $user->getKey();
    }

    public function update(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->view($user, $budgetRequest) && in_array($budgetRequest->status, ['budget_requested', 'returned_for_revision'], true)
            && $budgetRequest->approved_at === null && $budgetRequest->noted_at === null
            && $budgetRequest->released_at === null && $budgetRequest->received_at === null
            && ! $budgetRequest->releases()->exists() && ! $budgetRequest->liquidation()->exists();
    }

    public function delete(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->update($user, $budgetRequest) && ! $budgetRequest->liquidation()->exists()
            && $budgetRequest->approved_at === null && $budgetRequest->noted_at === null
            && $budgetRequest->released_at === null && $budgetRequest->received_at === null;
    }

    public function approve(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->companyMatches($user, $budgetRequest)
            && $user->canApproveMI('mi.budget.approve')
            && (int) $budgetRequest->employee_id !== (int) $user->getKey();
    }

    public function noteByAccounting(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->companyMatches($user, $budgetRequest) && $user->hasRole('accounting') && $user->can('mi.budget.review');
    }

    public function release(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->companyMatches($user, $budgetRequest) && $user->hasRole('accounting') && $user->can('mi.budget.release');
    }

    public function markReceived(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->view($user, $budgetRequest);
    }

    public function create(User $user): bool
    {
        return $this->hasMIContext($user) && $user->hasMIUserAccess();
    }

    public function viewProcessing(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->companyMatches($user, $budgetRequest)
            && (($user->hasRole('accounting') && $user->can('mi.budget.view')) || $user->canApproveMI('mi.budget.approve'));
    }

    public function submit(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->view($user, $budgetRequest);
    }

    public function cancel(User $user, BudgetRequest $budgetRequest): bool
    {
        return false;
    }

    public function reject(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->approve($user, $budgetRequest);
    }

    public function returnForCorrection(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->approve($user, $budgetRequest);
    }

    public function companyMatches(User $user, BudgetRequest $budgetRequest): bool
    {
        return $this->hasMIContext($user) && $budgetRequest->company_id !== null
            && (int) $budgetRequest->company_id === (int) $user->currentCompany()->getKey();
    }

    private function hasMIContext(User $user): bool
    {
        $company = $user->currentCompany();

        return $company?->code === 'MI' && $company->is_active;
    }
}
