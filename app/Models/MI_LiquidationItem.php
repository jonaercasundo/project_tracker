<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
        'item_date' => 'date',
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
        $rate = $this->report?->exchange_rate;

        if (! $rate || (float) $rate <= 0) {
            return 0;
        }

        return BigDecimal::of($this->amount_vnd)->dividedBy($rate, 2, RoundingMode::HALF_UP)->toFloat();
    }

    /**
     * Determine whether a receipt exists.
     */
    public function getHasReceiptAttribute(): bool
    {
        return ! empty($this->receipt_image);
    }
}
