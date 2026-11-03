<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MIProductVariantInventoryMovement extends Model
{
    protected $table = 'mi_product_variant_inventory_movements';

    protected $fillable = ['variant_id', 'warehouse_id', 'movement_type', 'quantity_change', 'quantity_after', 'changed_by', 'remarks'];
}
