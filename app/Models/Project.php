<?php

namespace App\Models;
use App\Models\ARSetting;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'project_id';
    protected $table = 'projects';
    public $incrementing = true;
    protected $keyType = 'int';

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
    
}
