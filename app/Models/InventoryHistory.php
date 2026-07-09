<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{
        protected $table = 'inventory_history';

    protected $primaryKey = 'history_id';

    public $timestamps = false;

    protected $fillable = [
        'inventory_id',
        'item_id',
        'warehouse_id',
        'old_qty',
        'new_qty',
        'changed_at',
        'changed_by',
        'remarks',
        'change_type',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'inventory_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}
