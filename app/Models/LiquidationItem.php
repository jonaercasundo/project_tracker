<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationItem extends Model
{
    protected $fillable = [
        'liquidation_id', 'budget_request_item_id', 'expense_category', 'particular',
        'actual_cash', 'actual_credit_card', 'actual_travel_agent', 'actual_total',
        'receipt_attached', 'remarks',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Total is always derived from the three payment-method columns, never typed directly.
            $item->actual_total = $item->actual_cash + $item->actual_credit_card + $item->actual_travel_agent;
        });

        static::saved(fn (self $item) => $item->liquidation?->recalcTotals());
        static::deleted(fn (self $item) => $item->liquidation?->recalcTotals());
    }

    public function liquidation()
    {
        return $this->belongsTo(Liquidation::class);
    }

    public function budgetRequestItem()
    {
        return $this->belongsTo(BudgetRequestItem::class);
    }
}
