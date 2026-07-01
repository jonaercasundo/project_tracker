<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectItem extends Model
{
    protected $fillable = [
        'project_information_id',
        'item_no',
        'item_description',
        'unit',
        'quantity',
        'unit_cost',
        'total_amount',
        'brand',
        'remarks',
    ];

    public function lot()
    {
        return $this->belongsTo(ProjectLot::class, 'lot_id');
    }
}