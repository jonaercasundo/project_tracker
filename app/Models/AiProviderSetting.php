<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderSetting extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'provider', 'api_key', 'model', 'enabled', 'per_user_requests_per_minute',
        'monthly_token_limit', 'configured_by',
        'research_provider', 'anthropic_api_key', 'anthropic_model',
    ];

    protected $hidden = ['api_key', 'anthropic_api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'anthropic_api_key' => 'encrypted',
            'enabled' => 'boolean',
            'per_user_requests_per_minute' => 'integer',
            'monthly_token_limit' => 'integer',
        ];
    }
}
