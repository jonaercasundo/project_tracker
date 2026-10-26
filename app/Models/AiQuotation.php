<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuotation extends Model
{
    protected $fillable = [
        'quote_number', 'idempotency_key', 'conversation_id', 'user_id', 'company_id',
        'customer_name', 'currency', 'items', 'total', 'status', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['items' => 'array', 'confirmed_at' => 'datetime', 'total' => 'decimal:2'];
    }
}
