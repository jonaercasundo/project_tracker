<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAwardEvent extends Model
{
    protected $fillable = [
        'project_information_id',
        'project_id',
        'event_type',
        'actor_user_id',
        'event_date',
        'status_before',
        'reason',
        'project_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'project_snapshot' => 'array',
        ];
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }
}
