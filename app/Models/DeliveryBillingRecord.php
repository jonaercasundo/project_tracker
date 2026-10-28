<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBillingRecord extends Model
{
    public const PENDING_BILLING = 'pending_billing';

    public const FOR_BILLING = 'for_billing';

    public const BILLED = 'billed';

    public const ON_HOLD = 'on_hold';

    public const REVOKED = 'revoked';

    protected $table = 'delivery_billing_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'billing_amount' => 'decimal:2',
            'submitted_for_billing_at' => 'datetime',
            'confirmed_billed_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_id', 'delivery_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BillingBatch::class, 'billing_batch_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DeliveryBillingHistory::class, 'delivery_billing_record_id');
    }
}
