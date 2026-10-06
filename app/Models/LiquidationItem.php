<?php

namespace App\Models;

use App\Services\MiFinancialAmount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidationItem extends Model
{
    protected $fillable = [
        'liquidation_id', 'budget_request_item_id', 'expense_category', 'particular',
        'actual_cash', 'actual_credit_card', 'actual_travel_agent', 'actual_total',
        'receipt_attached', 'remarks',
    ];

    protected $casts = ['actual_cash' => 'decimal:2', 'actual_credit_card' => 'decimal:2', 'actual_travel_agent' => 'decimal:2', 'actual_total' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Total is always derived from the three payment-method columns, never typed directly.
            $item->actual_total = MiFinancialAmount::sum([$item->actual_cash, $item->actual_credit_card, $item->actual_travel_agent]);
        });

        static::saved(fn (self $item) => $item->liquidation?->recalcTotals());
        static::deleted(fn (self $item) => $item->liquidation?->recalcTotals());
    }

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(Liquidation::class);
    }

    public function budgetRequestItem(): BelongsTo
    {
        return $this->belongsTo(BudgetRequestItem::class);
    }
}
