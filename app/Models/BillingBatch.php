<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingBatch extends Model
{
    protected $table = 'billing_batches';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selection_filters' => 'array',
            'confirmed_at' => 'datetime',
            'confirmation_date' => 'date',
        ];
    }

    public function deliveryBillingRecords(): HasMany
    {
        return $this->hasMany(DeliveryBillingRecord::class, 'billing_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_id');
    }
}
