<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MI_Product_Image extends Model
{
    protected $table = 'mi_product_images';

    protected $fillable = [
        'product_id',
        'image_type',
        'image_path',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    protected $casts = ['is_primary' => 'boolean', 'sort_order' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            MI_Product::class,
            'product_id',
            'product_id'
        );
    }
}
