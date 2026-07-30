<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubCategory extends Model
{
    use HasFactory;

    protected $table = 'sub_sub_categories';

    protected $fillable = [
        'sub_category_id',
        'sub_sub_category_name',
    ];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function collections()
    {
        return $this->hasMany(Collection::class, 'sub_sub_category_id');
    }
}