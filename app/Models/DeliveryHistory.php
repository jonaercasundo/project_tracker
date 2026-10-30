<?php

namespace App\Models;

use App\Services\DeliveryAuditTrailService;
use Illuminate\Database\Eloquent\Model;

class DeliveryHistory extends Model
{
    protected $table = 'delivery_history';

    protected $fillable = [

        'package_status_id',
        'user_id',
        'status',
        'previous_status',
        'remarks',
        'latitude',
        'longitude',
        'accuracy',
        'distance_from_school',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $history) => app(DeliveryAuditTrailService::class)->recordDeliveryHistoryById((int) $history->getKey()));
    }
}
