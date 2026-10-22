<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmcPplSourceLink extends Model
{
    protected $fillable = [
        'company_id', 'ppl_form_id', 'project_information_id', 'project_id', 'bidding_lot_id', 'legacy_lot_id',
        'match_status', 'match_origin', 'matching_identifiers', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'matching_identifiers' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function pplForm(): BelongsTo
    {
        return $this->belongsTo(PplForm::class, 'ppl_form_id');
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function biddingLot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'bidding_lot_id');
    }

    public function legacyLot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'legacy_lot_id', 'lot_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_id');
    }
}
