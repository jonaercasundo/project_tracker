<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MI_LiquidationItem extends Model
{
    protected $table = 'mi_liquidation_items';

    protected $fillable = [
        'liquidation_id',
        'line_no',
        'ref_no',
        'item_date',
        'requested_by',
        'payee',
        'expense_type',
        'account_buyer',
        'remarks',
        'amount_vnd',
        'receipt_image',
    ];

    protected $casts = [
        'item_date'  => 'date',
        'amount_vnd' => 'decimal:2',
    ];

    /**
     * Liquidation report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(
            MI_Liquidation::class,
            'liquidation_id'
        );
    }

    /**
     * Requested by user.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    /**
     * Calculate USD amount using the report exchange rate.
     */
    public function getAmountUsdAttribute(): float
    {
        $rate = (float) ($this->report?->exchange_rate ?? 0);

        if ($rate <= 0) {
            return 0;
        }

        return round(
            (float) $this->amount_vnd / $rate,
            2
        );
    }

    /**
     * Determine whether a receipt exists.
     */
    public function getHasReceiptAttribute(): bool
    {
        return !empty($this->receipt_image);
    }
}