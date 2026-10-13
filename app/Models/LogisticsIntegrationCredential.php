<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsIntegrationCredential extends Model
{
    protected $fillable = [
        'integration_id',
        'key_id',
        'secret',
        'status',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'created_by',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
