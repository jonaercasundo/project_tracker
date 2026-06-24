<?php

namespace App\Models;
use App\Models\Package;
use Illuminate\Database\Eloquent\Model;

class Keystage extends Model
{
    protected $table = 'keystage';
    protected $primaryKey = 'keystage_id';

    public $timestamps = false;

    protected $fillable = [
        'keystage_num',
        'description',
        'lot_id',
    ];

    /*
    |---------------------------------------
    | Relationships (optional but useful)
    |---------------------------------------
    */

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id', 'lot_id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'keystage_id', 'keystage_id');
    }
    public function packages()
    {
        return $this->hasMany(
            Package::class,
            'keystage_id',
            'keystage_id'
        );
    }
}