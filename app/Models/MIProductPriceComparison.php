<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MIProductPriceComparison extends Model
{
    protected $table = 'mi_product_price_comparisons';

    protected static function booted(): void
    {
        static::saving(function (self $comparison): void {
            $comparison->dedupe_hash = hash('sha256', trim($comparison->product_link));
        });
    }

    protected $fillable = [
        'product_id', 'store_name', 'product_name', 'photo_url', 'photo_path',
        'price', 'currency', 'product_link',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MI_Product::class, 'product_id', 'product_id');
    }
}
