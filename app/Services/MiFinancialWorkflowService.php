<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\FinancialActivity;
use App\Models\FinancialSettlement;
use App\Models\Liquidation;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MiFinancialWorkflowService
{
    /** @param array<string, mixed> $details */
    public function activity(Model $record, User $actor, string $event, ?string $previousStatus = null, array $details = []): FinancialActivity
    {
        return FinancialActivity::create([
            'company_id' => $record->company_id,
            'record_type' => $record->getTable(), 'record_id' => $record->getKey(),
            'event' => $event, 'previous_status' => $previousStatus, 'new_status' => $record->status,
            'actor_user_id' => $actor->getKey(), 'actor_name_snapshot' => $actor->name,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'amount' => $details['amount'] ?? null, 'currency' => $details['currency'] ?? null,
            'reference_no' => $details['reference_no'] ?? null, 'note' => $details['note'] ?? null,
            'metadata' => $details['metadata'] ?? null,
        ]);
    }

    public function submitBudget(BudgetRequest $budget, User $actor): void
    {
        DB::transaction(function () use ($budget, $actor): void {
            $budget = BudgetRequest::whereKey($budget->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $budget);
            abort_unless($budget->status === 'budget_requested' && ! $budget->activities()->where('event', 'budget_submitted')->exists(), 422);
            $this->activity($budget, $actor, 'budget_created');
            $this->activity($budget, $actor, 'budget_submitted', null, ['amount' => $budget->budget_total]);
        });
    }

    public function approveBudget(BudgetRequest $budget, User $actor, ?string $remarks = null, ?string $reviewedVersion = null): void
    {
        $this->decide($budget, $actor, 'approve', $remarks, $reviewedVersion);
    }

    public function noteBudget(BudgetRequest $budget, User $actor): void
    {
        $this->budgetTransition($budget, $actor, 'noteByAccounting', 'approved', 'accounting_noted');
    }

    /** @param array<string, mixed> $payment */
    public function releaseBudget(BudgetRequest $budget, User $actor, array $payment = []): void
    {
        DB::transaction(function () use ($budget, $actor, $payment): void {
            $budget = BudgetRequest::whereKey($budget->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('release', $budget);
            abort_unless($budget->status === 'approved' && $budget->noted_at && $budget->noted_by && ! $budget->released_at, 422, 'Accounting note is required; release cannot be repeated.');
            $details = [];
            $event = 'release_confirmed_unquantified';
            if ($payment !== [] || config('mi_financial.release_recording_enabled')) {
                abort_unless(config('mi_financial.release_recording_enabled'), 422, 'Release payment rules need business confirmation.');
                $details = $this->validatePayment($payment);
                abort_if(BigDecimal::of($details['amount'])->isGreaterThan(config('mi_financial.release_maximum')), 422, 'Configured release limit exceeded.');
                $budget->releases()->create($details + [
                    'company_id' => $budget->company_id, 'released_by' => $actor->getKey(), 'released_at' => now(),
                ]);
                $event = 'funds_released';
            }
            $budget->release($actor);
            $this->activity($budget, $actor, $event, 'approved', $details);
        });
    }

    public function confirmReceipt(BudgetRequest $budget, User $actor): void
    {
        $this->budgetTransition($budget, $actor, 'markReceived', 'released', 'funds_received');
    }

    public function submitLiquidation(Liquidation $liquidation, User $actor): void
    {
        $this->travelTransition($liquidation, $actor, 'submit', $liquidation->status === 'returned_for_revision' ? 'returned_for_revision' : 'draft', 'liquidation_submitted', function (Liquidation $locked) use ($actor): void {
            abort_if($locked->submitted_at !== null, 422);
            $locked->recalcTotals();
            $this->activity($locked, $actor, 'liquidation_created');
            $locked->submit();
        });
    }

    public function reviewLiquidation(Liquidation $liquidation, User $actor): void
    {
        $this->travelTransition($liquidation, $actor, 'noteByAccounting', 'submitted', 'liquidation_reviewed', function (Liquidation $locked) use ($actor): void {
            abort_if($locked->noted_at !== null, 422);
            $locked->noteByAccounting($actor);
        });
    }

    public function approveLiquidation(Liquidation $liquidation, User $actor, ?string $remarks = null, ?string $reviewedVersion = null): void
    {
        $this->decide($liquidation, $actor, 'approve', $remarks, $reviewedVersion);
    }

    /** @param array<string, mixed> $payment */
    public function recordSettlement(Liquidation $liquidation, User $actor, array $payment): void
    {
        $this->travelTransition($liquidation, $actor, 'recordSettlement', 'approved', 'settlement_recorded', function (Liquidation $locked) use ($actor, $payment): array {
            abort_if($locked->settlement()->exists(), 422, 'Settlement evidence already exists.');
            $data = Validator::make($payment, [
                'employee_return_amount' => $this->amountRules(), 'company_reimbursement_amount' => $this->amountRules(),
                'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', Rule::in(config('mi_financial.currencies'))],
                'settlement_method' => 'required|string|max:255', 'reference_no' => 'required|string|max:255', 'note' => 'nullable|string',
            ])->validate();
            $releases = $locked->budgetRequest->releases()->get();
            abort_unless($releases->isNotEmpty() && $releases->every(fn ($release) => $release->currency === $data['currency'] && (int) $release->company_id === (int) $locked->company_id), 422, 'Verified same-currency release evidence is required.');
            $released = MiFinancialAmount::sum($releases->pluck('amount'));
            $returned = $data['employee_return_amount'];
            $reimbursed = $data['company_reimbursement_amount'];
            $balance = FinancialSettlement::balance($released, $locked->actual_total, $returned, $reimbursed);
            abort_if(BigDecimal::of($balance)->abs()->isGreaterThan(MiFinancialAmount::MAX_AMOUNT), 422);
            $settlement = $locked->settlement()->create($data + [
                'company_id' => $locked->company_id, 'released_amount' => $released,
                'expense_amount' => $locked->actual_total, 'settlement_amount' => MiFinancialAmount::sum([$returned, $reimbursed]),
                'outstanding_balance' => $balance, 'settled_by' => $actor->getKey(), 'settled_at' => now(),
            ]);

            return ['amount' => $settlement->settlement_amount, 'currency' => $settlement->currency,
                'reference_no' => $settlement->reference_no, 'note' => $settlement->note,
                'metadata' => ['outstanding_balance' => $balance, 'released_amount' => $released, 'expense_amount' => $locked->actual_total]];

        });
    }

    public function closeLiquidation(Liquidation $liquidation, User $actor): void
    {
        $this->travelTransition($liquidation, $actor, 'close', 'approved', 'closed', function (Liquidation $locked) use ($actor): void {
            abort_unless($locked->settlement && BigDecimal::of($locked->settlement->outstanding_balance)->isZero(), 422, 'Verified settlement with no outstanding balance is required.');
            abort_unless($locked->budgetRequest->status === 'in_progress', 422);
            $locked->update(['status' => 'closed']);
            $locked->budgetRequest->update(['status' => 'liquidated']);
            $this->activity($locked->budgetRequest, $actor, 'budget_liquidated', 'in_progress');
        });
    }

    public function decide(BudgetRequest|Liquidation $record, User $actor, string $action, ?string $remarks = null, ?string $reviewedVersion = null): void
    {
        $details = Validator::make(['action' => $action, 'remarks' => $remarks], [
            'action' => ['required', Rule::in(['approve', 'reject', 'return'])],
            'remarks' => [$action === 'approve' ? 'nullable' : 'required', 'string', 'max:2000'],
        ])->validate();
        DB::transaction(function () use ($record, $actor, $action, $details, $reviewedVersion): void {
            $isBudget = $record instanceof BudgetRequest;
            $budget = BudgetRequest::whereKey($isBudget ? $record->getKey() : $record->budget_request_id)->lockForUpdate()->firstOrFail();
            $locked = $isBudget ? $budget : Liquidation::whereKey($record->getKey())->lockForUpdate()->firstOrFail();
            if (! $isBudget) {
                $locked->setRelation('budgetRequest', $budget);
            }
            Gate::forUser($actor)->authorize(match ($action) { 'return' => 'returnForCorrection', default => $action }, $locked);
            $previous = $isBudget ? 'budget_requested' : 'noted';
            abort_unless($locked->status === $previous && $locked->approved_at === null, 422, 'Invalid or stale workflow action.');
            if (! $isBudget) {
                abort_unless($budget->status === 'in_progress', 422, 'The parent budget must be in progress.');
            }
            if ($reviewedVersion !== null) {
                abort_unless(hash_equals($reviewedVersion, MIApprovalReview::fingerprint($locked)), 422, 'The transaction changed. Review its details again.');
            }
            $newStatus = match ($action) { 'approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned_for_revision' };
            $metadata = ['reviewed_version' => $reviewedVersion];
            if ($action === 'approve') {
                $locked->update(['status' => $newStatus, 'approved_by' => $actor->getKey(), 'approved_at' => now()]);
            } else {
                $changes = ['status' => $newStatus];
                if (! $isBudget && $action === 'return') {
                    $metadata += $locked->only(['submitted_at', 'noted_at', 'noted_by']);
                    $changes += ['submitted_at' => null, 'noted_at' => null, 'noted_by' => null];
                }
                $locked->update($changes);
            }
            $event = ($isBudget ? 'budget_' : 'liquidation_').match ($action) { 'approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned_for_revision' };
            $this->activity($locked, $actor, $event, $previous, [
                'note' => $details['remarks'] ?? null, 'amount' => $isBudget ? $locked->budget_total : $locked->actual_total,
                'metadata' => $metadata,
            ]);
        });
    }

    public function rejectBudget(BudgetRequest $budget, User $actor, string $remarks): void
    {
        $this->decide($budget, $actor, 'reject', $remarks);
    }

    public function returnLiquidation(Liquidation $liquidation, User $actor, string $remarks): void
    {
        $this->decide($liquidation, $actor, 'return', $remarks);
    }

    public function resubmitBudget(BudgetRequest $budget, User $actor): void
    {
        DB::transaction(function () use ($budget, $actor): void {
            $locked = BudgetRequest::whereKey($budget->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $locked);
            abort_unless($locked->status === 'returned_for_revision' && $locked->approved_at === null, 422);
            $locked->update(['status' => 'budget_requested']);
            $this->activity($locked, $actor, 'budget_resubmitted', 'returned_for_revision');
        });
    }

    private function budgetTransition(BudgetRequest $budget, User $actor, string $action, string $status, string $event): void
    {
        DB::transaction(function () use ($budget, $actor, $action, $status, $event): void {
            $budget = BudgetRequest::whereKey($budget->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize($action, $budget);
            abort_unless($budget->status === $status, 422, 'Invalid or stale workflow action.');
            if ($action === 'noteByAccounting') {
                abort_if($budget->noted_at !== null, 422);
            }
            if ($action === 'approve') {
                abort_if($budget->approved_at !== null, 422);
            }
            if ($action === 'markReceived') {
                abort_if($budget->received_at !== null, 422);
                $budget->markReceived();
            } else {
                $budget->{$action}($actor);
            }
            $this->activity($budget, $actor, $event, $status, $action === 'approve' ? ['amount' => $budget->budget_total] : []);
        });
    }

    private function travelTransition(Liquidation $liquidation, User $actor, string $ability, string $status, string $event, callable $change): void
    {
        DB::transaction(function () use ($liquidation, $actor, $ability, $status, $event, $change): void {
            $budget = BudgetRequest::whereKey($liquidation->budget_request_id)->lockForUpdate()->firstOrFail();
            $locked = Liquidation::whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
            $locked->setRelation('budgetRequest', $budget);
            Gate::forUser($actor)->authorize($ability, $locked);
            abort_unless($locked->status === $status, 422, 'Invalid or stale workflow action.');
            $details = $change($locked);
            $this->activity($locked, $actor, $event, $status, is_array($details) ? $details : []);
        });
    }

    /** @return array<int, string> */
    private function amountRules(): array
    {
        return ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'];
    }

    /** @param array<string, mixed> $payment @return array<string, mixed> */
    private function validatePayment(array $payment): array
    {
        return Validator::make($payment, [
            'amount' => $this->amountRules(), 'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', Rule::in(config('mi_financial.currencies'))],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0', 'max:99999999.9999', 'regex:/^\d+(\.\d{1,4})?$/'],
            'payment_method' => 'required|string|max:255', 'reference_no' => 'required|string|max:255', 'note' => 'nullable|string',
        ])->validate();
    }
}
