<?php

namespace App\Models;

use App\Services\DeliveryAuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBillingHistory extends Model
{
    public $timestamps = false;

    protected $table = 'delivery_billing_histories';

    protected static function booted(): void
    {
        static::created(fn (self $history) => app(DeliveryAuditTrailService::class)->recordBillingHistoryById((int) $history->getKey()));
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function billingRecord(): BelongsTo
    {
        return $this->belongsTo(DeliveryBillingRecord::class, 'delivery_billing_record_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'user_id');
    }
}
