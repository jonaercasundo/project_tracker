<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MIProductVariant extends Model
{
    protected $table = 'mi_product_variants';

    protected $primaryKey = 'variant_id';

    protected $fillable = [
        'sequence', 'material', 'color', 'product_length', 'product_width', 'product_height',
        'product_depth', 'purchase_price', 'selling_price', 'sku',
    ];

    protected $casts = [
        'product_length' => 'decimal:2', 'product_width' => 'decimal:2',
        'product_height' => 'decimal:2', 'product_depth' => 'decimal:2',
        'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MI_Product::class, 'product_id', 'product_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(MIProductVariantInventory::class, 'variant_id', 'variant_id');
    }
}
