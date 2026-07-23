<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'it_assets';

    /**
     * The primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'asset_code',
        'qr_code',
        'asset_name',
        'category',
        'brand',
        'model',
        'serial_number',
        'specification',
        'purchase_date',
        'purchase_cost',
        'warranty_expiry',
        'status',
        'assigned_to',
        'department',
        'location',
        'remarks',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date'   => 'date',
        'purchase_cost'   => 'decimal:2',
        'warranty_expiry' => 'date',
    ];
}