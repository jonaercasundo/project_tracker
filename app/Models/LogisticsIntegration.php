<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsIntegration extends Model
{
    protected $fillable = [
        'integration_id',
        'name',
        'authentication_method',
        'webhook_url',
        'enabled_event_types',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['enabled_event_types' => 'array'];
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(LogisticsIntegrationCredential::class, 'integration_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'logistics_integration_projects', 'integration_id', 'project_id', 'id', 'project_id')
            ->withTimestamps();
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'logistics_integration_schools', 'integration_id', 'school_id', 'id', 'school_id')
            ->withPivot('project_id')
            ->withTimestamps();
    }
}
