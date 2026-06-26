<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Package;

class PackageStatus extends Model
{
    protected $table = 'package_status';
    protected $primaryKey = 'package_status_id';

    public $timestamps = false;

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
