<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectInformation extends Model
{
    protected $table = 'project_information';

    protected $fillable = [
        'project_id',
        'project_code',
        'reference_no',
        'project_name',
        'procuring_entity',
        'approved_budget_contract_abc',
        'contract_amount',
        'start_date',
        'end_date',
        'notice_of_award_date',
        'lot_no',
        'delivery_period',
        'country',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'delivery_address',
        'date_of_bid_opening',
        'notes_special_condition',
        'prepared_by',
        'prepared_date',
        'verified_by',
        'status',
        'pre_bid_conf',
        'calculated_total',
    ];

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectItem::class, ProjectLot::class, 'project_id', 'lot_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ProjectLot::class, 'project_id');
    }

    protected function casts(): array
    {
        return [
            'approved_budget_contract_abc' => 'decimal:2',
            'contract_amount' => 'decimal:2',
            'calculated_total' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'notice_of_award_date' => 'date',
        ];
    }

    public function implementationProject(): HasOne
    {
        return $this->hasOne(Project::class, 'project_information_id');
    }

    public function awardEvents(): HasMany
    {
        return $this->hasMany(ProjectAwardEvent::class, 'project_information_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(BiddingDocumentFolder::class, 'project_information_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BiddingDocument::class, 'project_information_id');
    }

    public function keyStages(): HasMany
    {
        return $this->hasMany(BiddingKeyStage::class, 'project_id');
    }
}
