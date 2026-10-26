<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MI_Material extends Model
{
    use HasFactory;

    protected $table = 'mi_materials';

    protected $fillable = [
        'material_name',
        'normalized_name',
        'is_active',
    ];
}
