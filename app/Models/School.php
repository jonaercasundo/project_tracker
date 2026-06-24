<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
        protected $table = 'school';

    protected $primaryKey = 'school_id';

    protected $fillable = [
        'school_name',
        'address',
        'contact_person',
        'contact',
        'municipality',
        'division',
        'region'
    ];
}
