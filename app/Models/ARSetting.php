<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ARSetting extends Model
{
    protected $table = 'AR_settings';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}