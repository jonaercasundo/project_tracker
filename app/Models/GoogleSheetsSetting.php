<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetsSetting extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'spreadsheet_id',
        'sheet_tab',
        'service_account_json',
        'enabled',
        'configured_by',
    ];

    protected $hidden = ['service_account_json'];

    protected function casts(): array
    {
        return [
            'service_account_json' => 'encrypted',
            'enabled' => 'boolean',
        ];
    }
}
