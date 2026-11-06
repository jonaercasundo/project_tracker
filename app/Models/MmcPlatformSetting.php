<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MmcPlatformSetting extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'base_url', 'read_key', 'configured_by'];

    protected $hidden = ['read_key'];

    protected function casts(): array
    {
        return [
            'read_key' => 'encrypted',
        ];
    }
}
