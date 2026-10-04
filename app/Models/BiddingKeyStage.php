<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiddingKeyStage extends Model
{
    /** @use HasFactory<\Database\Factories\BiddingKeyStageFactory> */
    use HasFactory;

    protected $table = 'keystages';

    protected $fillable = ['project_id', 'lot_id', 'delivery_address_id', 'name', 'package_no'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'lot_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(BiddingDeliveryAddress::class, 'delivery_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'keystage_id');
    }
}
