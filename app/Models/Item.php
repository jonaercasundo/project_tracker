<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item'; // change if your table is named items

    protected $primaryKey = 'item_id'; // change if your PK is different

    public $timestamps = false; // set true if you have created_at and updated_at

    protected $guarded = [];
}