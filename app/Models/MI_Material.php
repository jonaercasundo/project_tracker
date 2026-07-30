<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MI_Material extends Model
{
    use HasFactory;

    protected $table = 'mi_materials';

    protected $fillable = [
        'material_name',
        'is_active',
    ];
}