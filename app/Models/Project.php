<?php

namespace App\Models;

use App\Models\ARSetting;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'project_id';
    protected $table = 'projects';

    public $incrementing = true;
    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'project_name',
        'project_code',
        'ref_no',
        'agency',
        'contract_amount',
        'start_date',
        'keystage',
        'ABC',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function arSetting()
    {
        return $this->hasOne(
            ARSetting::class,
            'project_id',
            'project_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            \App\Models\New\Item::class,
            'project_id',
            'project_id'
        );
    }

    public function lots()
    {
        return $this->hasMany(
            ProjectLot::class,
            'project_id',
            'project_id'
        );
    }
}
