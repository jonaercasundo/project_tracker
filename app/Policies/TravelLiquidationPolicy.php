<?php

namespace App\Policies;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\User;

class TravelLiquidationPolicy
{
    public function view(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation) && (new BudgetRequestPolicy)->view($user, $liquidation->budgetRequest)
            && (int) $liquidation->liquidated_by === (int) $user->getKey();
    }

    public function update(User $user, Liquidation $liquidation): bool
    {
        return $this->view($user, $liquidation) && in_array($liquidation->status, ['draft', 'submitted', 'returned_for_revision'], true)
            && $liquidation->noted_at === null && $liquidation->approved_at === null && ! $liquidation->settlement()->exists();
    }

    public function delete(User $user, Liquidation $liquidation): bool
    {
        return $this->view($user, $liquidation) && $liquidation->status === 'draft'
            && $liquidation->submitted_at === null && $liquidation->noted_at === null && $liquidation->approved_at === null
            && ! $liquidation->settlement()->exists();
    }

    public function noteByAccounting(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation) && $user->hasRole('accounting') && $user->can('mi.liquidation.review');
    }

    public function approve(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation) && $user->canApproveMI('mi.travel.approve')
            && (int) $liquidation->liquidated_by !== (int) $user->getKey();
    }

    public function create(User $user, BudgetRequest $budgetRequest): bool
    {
        return (new BudgetRequestPolicy)->view($user, $budgetRequest);
    }

    public function viewProcessing(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation)
            && (($user->hasRole('accounting') && $user->can('mi.liquidation.view')) || $user->canApproveMI('mi.travel.approve'));
    }

    public function recordSettlement(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation) && $user->hasRole('accounting')
            && $user->can('mi.travel.settle') && config('mi_financial.settlement_recording_enabled');
    }

    public function close(User $user, Liquidation $liquidation): bool
    {
        return $this->companyMatches($user, $liquidation) && $user->can('mi.travel.close')
            && config('mi_financial.closure_enabled');
    }

    public function submit(User $user, Liquidation $liquidation): bool
    {
        return $this->view($user, $liquidation);
    }

    public function cancel(User $user, Liquidation $liquidation): bool
    {
        return false;
    }

    public function returnForCorrection(User $user, Liquidation $liquidation): bool
    {
        return $this->approve($user, $liquidation);
    }

    public function reject(User $user, Liquidation $liquidation): bool
    {
        return $this->approve($user, $liquidation);
    }

    private function companyMatches(User $user, Liquidation $liquidation): bool
    {
        return $liquidation->company_id !== null
            && (int) $liquidation->company_id === (int) $liquidation->budgetRequest->company_id
            && (new BudgetRequestPolicy)->companyMatches($user, $liquidation->budgetRequest);
    }
}
