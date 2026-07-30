<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MI_Collection extends Model
{
    use HasFactory;

    protected $table = 'mi_collections';

    protected $fillable = [
        'product_type_id',
        'code',
        'name',
        'description',
        'is_active'
    ];

    public function productType()
    {
        return $this->belongsTo(MI_ProductType::class, 'product_type_id');
    }
}
