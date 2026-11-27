<?php

namespace App\Models;

use Database\Factories\MmcBiometricConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MmcBiometricConnection extends Model
{
    /** @use HasFactory<MmcBiometricConnectionFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'device_ip',
        'tcp_port',
        'device_id',
        'device_serial',
        'comm_key',
    ];

    protected $hidden = ['comm_key'];

    protected function casts(): array
    {
        return [
            'comm_key' => 'encrypted',
        ];
    }
}
