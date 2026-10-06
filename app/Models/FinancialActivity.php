<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class FinancialActivity extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['metadata' => 'array', 'amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Financial activities are append-only.'));
        static::deleting(fn () => throw new LogicException('Financial activities are append-only.'));
    }
}
