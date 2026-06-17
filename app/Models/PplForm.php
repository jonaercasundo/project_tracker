<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PplForm extends Model
{
    protected $table = 'ppl_forms';

    protected $fillable = [
        'project_code',
        'lot_number',
        'project_title',
        'project_id_no',
        'region',
        'bid_opening',
        'abc',
        'bidder',
        'authorized_signatory',
    ];
}
