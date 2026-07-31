<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MI_Product_Image extends Model
{

    protected $table = 'mi_product_images';


    protected $fillable = [
        'product_id',
        'image_type',
        'image_path',
        'image_url',
        'is_primary',
        'sort_order'
    ];


    public function product()
    {
        return $this->belongsTo(
            MI_Product::class,
            'product_id',
            'product_id'
        );
    }

}