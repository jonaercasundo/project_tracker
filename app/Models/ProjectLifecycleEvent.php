<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLifecycleEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_lifecycle_track_id', 'actor_user_id', 'event_type', 'from_stage', 'to_stage', 'outcome', 'note', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(ProjectLifecycleTrack::class, 'project_lifecycle_track_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }
}
