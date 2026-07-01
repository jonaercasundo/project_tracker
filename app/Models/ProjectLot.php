<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLot extends Model
{
    protected $table = 'lots';

    protected $fillable = [
        'project_id',
        'lot_no',
        'country',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'delivery_address',
        'notes_special_condition',
    ];

    /**
     * Parent Project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_id');
    }

    /**
     * Items under this lot
     */
    public function items()
    {
        return $this->hasMany(ProjectItem::class, 'lot_id');
    }
}