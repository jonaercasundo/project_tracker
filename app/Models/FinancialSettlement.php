<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;

class FinancialSettlement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'released_amount' => 'decimal:2', 'expense_amount' => 'decimal:2',
        'employee_return_amount' => 'decimal:2', 'company_reimbursement_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2', 'outstanding_balance' => 'decimal:2', 'settled_at' => 'datetime',
    ];

    /** Reconcile explicitly recorded payments; never infer that a payment occurred. */
    public static function balance(string $released, string $expenses, string $returned, string $reimbursed): string
    {
        return (string) BigDecimal::of($released)->plus($reimbursed)->minus($expenses)->minus($returned)->toScale(2);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Settlement evidence cannot be overwritten.'));
        static::deleting(fn () => throw new \LogicException('Settlement evidence cannot be deleted.'));
    }
}
