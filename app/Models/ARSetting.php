<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ARSetting extends Model
{
    protected $table = 'AR_settings';

    protected $primaryKey = 'setting_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}