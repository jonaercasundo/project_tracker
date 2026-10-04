<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectItem extends Model
{
    protected $fillable = [
        'lot_id',
        'item_no',
        'item_description',
        'unit',
        'quantity',
        'unit_cost',
        'total_amount',
        'brand',
        'remarks',
        'keystage_id',
        'catalog_item_id',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'lot_id');
    }

    public function keyStage(): BelongsTo
    {
        return $this->belongsTo(BiddingKeyStage::class, 'keystage_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\New\Item::class, 'catalog_item_id');
    }

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total_amount' => 'decimal:2'];
    }
}
