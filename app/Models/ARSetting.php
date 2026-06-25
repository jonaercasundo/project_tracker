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
        protected $fillable = [
        'project_name',
        'company',
        'client',
        'ar_company_footer',
        'ar_address_footer',
        'display_label',
        'display_school_id',
        'label_school_id',
        'label_municipality',
        'label_division',
        'label_region',
        'ar_logo',
        'ar_contact_footer',
    ];
}