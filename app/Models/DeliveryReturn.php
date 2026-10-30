<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReturn extends Model
{
    protected $table = 'delivery_returns';

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_type', 'project_id', 'lot_id', 'school_id', 'delivery_id', 'package_id', 'dr_no',
        'item_id', 'quantity_returned', 'status', 'reason', 'remarks', 'monitoring_date',
        'received_date', 'received_by', 'received_warehouse_id', 'reported_by_user_id',
    ];

    protected function casts(): array
    {
        return ['monitoring_date' => 'date', 'received_date' => 'date'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_id', 'delivery_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'received_warehouse_id', 'warehouse_id');
    }
}
