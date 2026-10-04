<?php

namespace App\Models;

use Database\Factories\BiddingDeliveryAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiddingDeliveryAddress extends Model
{
    /** @use HasFactory<BiddingDeliveryAddressFactory> */
    use HasFactory;

    protected $table = 'delivery_address';

    protected $fillable = [
        'project_id',
        'lot_id',
        'delivery_address',
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
        'delivery_address_region',
        'delivery_address_province',
        'delivery_address_municipality',
        'delivery_address_barangay',
        'delivery_address_otherInformation',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'lot_id');
    }

    public function keystages(): HasMany
    {
        return $this->hasMany(BiddingKeyStage::class, 'delivery_address_id');
    }
}
