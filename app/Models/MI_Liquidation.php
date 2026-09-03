<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MI_Liquidation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mi_liquidations';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'title',
        'date_prepared',
        'exchange_rate',
        'pcf_amount',
        'company_id',
        'prepared_by',
        'status',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'date_prepared' => 'date',
        'exchange_rate' => 'decimal:4',
        'pcf_amount'    => 'decimal:2',
    ];

    /**
     * Liquidation expense items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            MI_LiquidationItem::class,
            'liquidation_id'
        )->orderBy('line_no');
    }

    /**
     * Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id',
            'company_id'
        );
    }

    /**
     * User who prepared the liquidation.
     *
     * Your users table uses user_id as the primary key.
     */
    public function preparer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'prepared_by',
            'user_id'
        );
    }

    /**
     * Total liquidation amount in VND.
     */
    public function getTotalVndAttribute(): float
    {
        return round(
            (float) $this->items->sum(
                fn ($item) => (float) $item->amount_vnd
            ),
            2
        );
    }

    /**
     * Total liquidation amount in USD.
     *
     * USD is calculated from the report exchange rate.
     */
    public function getTotalUsdAttribute(): float
    {
        $rate = (float) $this->exchange_rate;

        if ($rate <= 0) {
            return 0.0;
        }

        return round(
            $this->total_vnd / $rate,
            2
        );
    }

    /**
     * Cash remaining after liquidation in VND.
     */
    public function getCashOnHandVndAttribute(): float
    {
        $pcfAmount = (float) ($this->pcf_amount ?? 0);

        return round(
            $pcfAmount - $this->total_vnd,
            2
        );
    }

    /**
     * Cash remaining after liquidation in USD.
     */
    public function getCashOnHandUsdAttribute(): float
    {
        $rate = (float) $this->exchange_rate;

        if ($rate <= 0) {
            return 0.0;
        }

        return round(
            $this->cash_on_hand_vnd / $rate,
            2
        );
    }

    /**
     * Determine whether the liquidation has a positive balance.
     */
    public function getHasCashOnHandAttribute(): bool
    {
        return $this->cash_on_hand_vnd > 0;
    }

    /**
     * Determine whether the liquidation has been fully consumed.
     */
    public function getIsFullyLiquidatedAttribute(): bool
    {
        return $this->cash_on_hand_vnd == 0.0;
    }

    /**
     * Determine whether the liquidation exceeded the PCF amount.
     */
    public function getIsOverLiquidatedAttribute(): bool
    {
        return $this->cash_on_hand_vnd < 0;
    }
}