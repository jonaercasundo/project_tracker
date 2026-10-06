<?php

namespace App\Models;

use App\Services\MiFinancialAmount;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Liquidation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
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

    public function budgetRequest(): BelongsTo
    {
        return $this->belongsTo(BudgetRequest::class);
    }

    public function liquidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liquidated_by');
    }

    public function items(): HasMany
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
        $actualTotal = MiFinancialAmount::sum($this->items()->pluck('actual_total'));
        $budgetTotal = $this->budgetRequest->budget_total;

        $this->update([
            'actual_total' => $actualTotal,
            'variance' => (string) BigDecimal::of($budgetTotal)->minus($actualTotal)->toScale(2),
        ]);
    }

    public function isBalanced(): bool
    {
        return BigDecimal::of($this->variance)->abs()->isLessThanOrEqualTo('0.01');
    }

    public function isOverBudget(): bool
    {
        return BigDecimal::of($this->variance)->isLessThan('-0.01');
    }

    public function isUnderBudget(): bool
    {
        return BigDecimal::of($this->variance)->isGreaterThan('0.01');
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
        $this->update(['status' => 'noted', 'noted_by' => $accountant->getKey(), 'noted_at' => now()]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(FinancialActivity::class, 'record_id')->where('record_type', $this->getTable())->orderBy('id');
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(FinancialSettlement::class);
    }
}
