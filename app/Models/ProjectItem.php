<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function project()
    {
         return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }
}