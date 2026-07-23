<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageContent extends Model
{
    protected $table = 'package_content';
    protected $fillable = [
        'package_id',
        'item_id',
        'qty',
        'qty_teachers_manual',
    ];
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
