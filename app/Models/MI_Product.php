<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MI_Product extends Model
{
    use HasFactory;

    protected $table = 'mi_products';

    protected $primaryKey = 'product_id';

    protected $fillable = [

        'item_name',
        'description',

        'category_id',
        'sub_category_id',
        'product_type_id',
        'collection_id',

        'type_of_sample',
        'classification',
        'designed_by',

        'materials',
        'type',
        'color',

        'product_height',
        'product_width',
        'product_length',
        'product_depth',

        'carton_height',
        'carton_width',
        'carton_length',
        'carton_depth',

        'purchase_cost',

        'product_file',

        'status',

    ];

    protected $casts = [

        'materials' => 'array',
        'color' => 'array',

        'product_height' => 'decimal:2',
        'product_width' => 'decimal:2',
        'product_length' => 'decimal:2',
        'product_depth' => 'decimal:2',

        'carton_height' => 'decimal:2',
        'carton_width' => 'decimal:2',
        'carton_length' => 'decimal:2',
        'carton_depth' => 'decimal:2',

        'purchase_cost' => 'decimal:2',

    ];

    /**
     * Category
     */
    public function category()
    {
        return $this->belongsTo(MI_Category::class);
    }

    /**
     * Sub Category
     */
    public function subCategory()
    {
        return $this->belongsTo(MI_SubCategory::class);
    }

    /**
     * Product Type
     */
    public function productType()
    {
        return $this->belongsTo(MI_ProductType::class);
    }

    /**
     * Collection
     */
    public function collection()
    {
        return $this->belongsTo(MI_Collection::class);
    }
}