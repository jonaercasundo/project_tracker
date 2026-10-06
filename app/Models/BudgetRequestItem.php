<?php

namespace App\Models;

use App\Services\MiFinancialAmount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRequestItem extends Model
{
    protected $fillable = [
        'budget_request_id', 'expense_category', 'particular',
        'budget_cash', 'budget_credit_card', 'budget_travel_agent', 'budget_total',
    ];

    protected $casts = ['budget_cash' => 'decimal:2', 'budget_credit_card' => 'decimal:2', 'budget_travel_agent' => 'decimal:2', 'budget_total' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->budget_total = MiFinancialAmount::sum([$item->budget_cash, $item->budget_credit_card, $item->budget_travel_agent]);
        });

        static::saved(fn (self $item) => $item->budgetRequest?->recalcTotals());
        static::deleted(fn (self $item) => $item->budgetRequest?->recalcTotals());
    }

    public function budgetRequest(): BelongsTo
    {
        return $this->belongsTo(BudgetRequest::class);
    }
}
