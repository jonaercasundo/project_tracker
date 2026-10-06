<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRelease extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'exchange_rate' => 'decimal:4', 'released_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Release evidence cannot be overwritten.'));
        static::deleting(fn () => throw new \LogicException('Release evidence cannot be deleted.'));
    }
}
