<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Liquidation extends Model
{
    protected $fillable = [
        'budget_request_id', 'liquidated_by', 'status',
        'actual_total', 'variance', 'submitted_at',
        'noted_by', 'noted_at', 'approved_by', 'approved_at', 'remarks',
    ];

    protected $casts = [
        'actual_total' => 'decimal:2',
        'variance' => 'decimal:2',
        'submitted_at' => 'datetime',
        'noted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Amounts within this tolerance count as "balanced" (avoids float rounding false positives)
    const BALANCE_TOLERANCE = 0.01;

    public function budgetRequest()
    {
        return $this->belongsTo(BudgetRequest::class);
    }

    public function liquidatedBy()
    {
        return $this->belongsTo(User::class, 'liquidated_by');
    }

    public function items()
    {
        return $this->hasMany(LiquidationItem::class);
    }

    /**
     * The automated balance check from the diagram: recompute the actual total
     * from line items and compare it against the budget request's approved total.
     * Call this after any liquidation item is added/edited/removed.
     */
    public function recalcTotals(): void
    {
        $actualTotal = $this->items()->sum('actual_total');
        $budgetTotal = (float) $this->budgetRequest->budget_total;

        $this->update([
            'actual_total' => $actualTotal,
            'variance' => $budgetTotal - $actualTotal,
        ]);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->variance) <= self::BALANCE_TOLERANCE;
    }

    public function isOverBudget(): bool
    {
        return (float) $this->variance < -self::BALANCE_TOLERANCE;
    }

    public function isUnderBudget(): bool
    {
        return (float) $this->variance > self::BALANCE_TOLERANCE;
    }

    public function percentVariance(): float
    {
        $budgetTotal = (float) $this->budgetRequest->budget_total;
        if ($budgetTotal == 0.0) {
            return 0.0;
        }

        return (float) $this->variance / $budgetTotal;
    }

    public function submit(): void
    {
        $this->update(['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function noteByAccounting(User $accountant): void
    {
        $this->update(['status' => 'noted', 'noted_by' => $accountant->id, 'noted_at' => now()]);
    }

    /**
     * Approving/closing a liquidation also flips the parent budget request to
     * "liquidated", closing the loop shown in the diagram.
     */
    public function approve(User $approver): void
    {
        $this->update(['status' => 'closed', 'approved_by' => $approver->id, 'approved_at' => now()]);
        $this->budgetRequest->update(['status' => 'liquidated']);
    }
}
