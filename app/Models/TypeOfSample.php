<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfSample extends Model
{
    use HasFactory;

    protected $table = 'type_of_samples';

    protected $fillable = [
        'type_of_sample_name',
    ];
}