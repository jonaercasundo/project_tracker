<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLifecycleTask extends Model
{
    protected $fillable = [
        'project_lifecycle_track_id', 'kind', 'title', 'status', 'due_at', 'owner_user_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(ProjectLifecycleTrack::class, 'project_lifecycle_track_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
