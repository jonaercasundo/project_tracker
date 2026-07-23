<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryHistory extends Model
{
    protected $table = 'delivery_history';

    protected $fillable = [

        'package_status_id',

        'user_id',

        'status',

        'remarks',

        'latitude',

        'longitude',

        'accuracy',

        'distance_from_school'

    ];
}
