<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MI_Category extends Model
{
    use HasFactory;

    protected $table = 'mi_categories';

    protected $fillable = [
        'code', 'name', 'description', 'is_active'
    ];

    public function subCategories()
    {
        return $this->hasMany(MI_SubCategory::class, 'category_id');
    }
}
