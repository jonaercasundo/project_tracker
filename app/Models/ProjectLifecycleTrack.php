<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLifecycleTrack extends Model
{
    protected $fillable = [
        'company_id', 'project_id', 'project_information_id', 'legacy_lot_id', 'bidding_lot_id',
        'scope_key', 'current_stage', 'outcome', 'owner_user_id', 'next_action', 'next_action_due_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['next_action_due_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }

    public function legacyLot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'legacy_lot_id', 'lot_id');
    }

    public function biddingLot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'bidding_lot_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectLifecycleEvent::class)->latest('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectLifecycleTask::class);
    }
}
