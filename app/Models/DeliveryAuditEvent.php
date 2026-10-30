<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAuditEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_at' => 'datetime', 'metadata' => 'array'];
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }
}
