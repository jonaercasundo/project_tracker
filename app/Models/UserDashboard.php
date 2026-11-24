<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboard extends Model
{
    protected $primaryKey = 'dashboard_id';

    protected $fillable = [
        'user_id',
        'name',
        'widgets',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'widgets' => 'array',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
