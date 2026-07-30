<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MI_ProductType extends Model
{    
    use HasFactory;

    protected $table = 'mi_product_types';

    protected $fillable = [
        'sub_category_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    public function subCategory()
    {
        return $this->belongsTo(MI_SubCategory::class, 'sub_category_id');
    }

    public function collections()
    {
        return $this->hasMany(MI_Collection::class, 'product_type_id');
    }
}
