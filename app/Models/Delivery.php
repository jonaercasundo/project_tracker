<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\School;
use App\Models\Project;
use App\Models\Keystage;
use App\Models\PackageStatus;
use App\Models\Item;

class Delivery extends Model
{
    protected $table = 'deliveries';

    protected $primaryKey = 'delivery_id';

    public $timestamps = false; // set true if your table has created_at/updated_at

    protected $guarded = [];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function keystage()
    {
        return $this->belongsTo(Keystage::class, 'keystage_id', 'keystage_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id', 'lot_id');
    }

    public function packageStatuses()
    {
        return $this->hasMany(PackageStatus::class, 'delivery_id', 'delivery_id');
    }
    public function items()
    {
        return $this->hasMany(
            Item::class,
            'project_id', // foreign key in item table
            'project_id'  // local key in deliveries table
        );
    }
}