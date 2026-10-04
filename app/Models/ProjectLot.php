<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLot extends Model
{
    protected $table = 'lots';

    protected $fillable = [
        'project_id',
        'lot_no',
        'country',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'delivery_address',
        'notes_special_condition',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
    ];

    /**
     * Parent Project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'lot_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(BiddingDeliveryAddress::class, 'lot_id');
    }

    public function legacyItems(): HasMany
    {
        return $this->items()->whereNull('keystage_id');
    }
}
