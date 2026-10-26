<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MI_Color extends Model
{
    protected $table = 'mi_colors';

    protected $fillable = [
        'color_name',
        'normalized_name',
        'is_active',
    ];
}
