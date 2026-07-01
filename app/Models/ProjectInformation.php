<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectLot;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectInformation extends Model
{
    protected $table = 'project_information';

    protected $fillable = [
            'project_id',
            'project_name',
            'procuring_entity',
            'approved_budget_contract_abc',
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
    ];

    public function items()
    {
         return $this->hasMany(ProjectItem::class, 'lot_id');
    }
    public function lots(): HasMany
    {
        return $this->hasMany(ProjectLot::class, 'project_id');
    }
}