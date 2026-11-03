<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MIProductVariantInventory extends Model
{
    protected $table = 'mi_product_variant_inventory';

    protected $fillable = ['variant_id', 'warehouse_id', 'quantity'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}
