<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $table = 'lot';

    protected $primaryKey = 'lot_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'lot_name',
        'project_id',
        'contract_no'
    ];

    public function project()
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'project_id'
        );
    }

    public function deliveries()
    {
        return $this->hasMany(
            Delivery::class,
            'lot_id',
            'lot_id'
        );
    }
}