<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Psgc extends Model
{
    protected $table = 'psgc';

        protected $fillable = [
        'psgc_code',
        'correspondence_code',
        'name',
        'geographic_level',
        'parent_code',
        'region_code',
        'province_code',
        'city_code',
        'city_class',
        'income_classification',
        'urban_rural',
        'population',
        'status',
    ];
}