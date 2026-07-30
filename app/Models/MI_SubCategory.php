<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MI_SubCategory extends Model
{
    use HasFactory;

    protected $table = 'mi_sub_categories';

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(MI_Category::class, 'category_id');
    }

    public function productTypes()
    {
        return $this->hasMany(MI_ProductType::class, 'sub_category_id');
    }
}
