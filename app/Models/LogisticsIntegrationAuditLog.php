<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsIntegrationAuditLog extends Model
{
    protected $table = 'logistics_integration_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'integration_id',
        'integration_reference',
        'actor_user_id',
        'action',
        'target_type',
        'target_reference',
        'details',
        'ip_address',
        'created_at',
    ];

    protected $hidden = ['details'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }
}
