<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRequestItem extends Model
{
    protected $fillable = [
        'budget_request_id', 'expense_category', 'particular',
        'budget_cash', 'budget_credit_card', 'budget_travel_agent', 'budget_total',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $item) => $item->budgetRequest?->recalcTotals());
        static::deleted(fn (self $item) => $item->budgetRequest?->recalcTotals());
    }

    public function budgetRequest()
    {
        return $this->belongsTo(BudgetRequest::class);
    }
}
