<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Package;

class PackageStatus extends Model
{
    protected $table = 'package_status';

    protected $primaryKey = 'package_status_id';

    public $timestamps = false;

    protected $fillable = [
        'delivery_id',
        'package_id',
        'status',
        'remarks',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id', 'delivery_id');
    }
}
