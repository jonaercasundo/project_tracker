<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\School;
use App\Models\Project;
use App\Models\Keystage;
use App\Models\PackageStatus;

class Delivery extends Model
{
    protected $table = 'deliveries';

    protected $primaryKey = 'delivery_id';

    public $timestamps = false; // set true if your table has created_at/updated_at

    protected $guarded = [];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function keystage()
    {
        return $this->belongsTo(Keystage::class, 'keystage_id');
    }

    public function packageStatuses()
    {
        return $this->hasMany(PackageStatus::class, 'delivery_id');
    }
}