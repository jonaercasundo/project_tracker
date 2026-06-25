<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table = 'school';

    protected $primaryKey = 'school_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

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