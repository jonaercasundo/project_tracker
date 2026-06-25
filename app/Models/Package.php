<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'package';
    protected $primaryKey = 'package_id';

    public $timestamps = false;
    public function packageContent()
    {
        return $this->hasMany(PackageContent::class, 'package_id');
    }
}
