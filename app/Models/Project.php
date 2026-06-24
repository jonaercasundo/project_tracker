<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'project_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    public function arSettings()
    {
        return $this->hasOne(ArSetting::class, 'project_id', 'project_id');
    }
}
