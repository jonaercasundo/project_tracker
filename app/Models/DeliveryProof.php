<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryProof extends Model
{
    public function packageStatus()
    {
        return $this->belongsTo(
            PackageStatus::class,
            'package_status_id',
            'package_status_id'
        );
    }
}
