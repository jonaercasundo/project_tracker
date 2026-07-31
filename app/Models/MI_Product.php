<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MI_Product extends Model
{
    use HasFactory;

    protected $table = 'mi_products';

    protected $primaryKey = 'product_id';
    protected $casts = [
        'materials' => 'array',
        'color' => 'array',
    ];
    protected $fillable = [

        // SKU Information
        'sku',
        'item_code',

        // Product Information
        'item_name',
        'description',

        // Taxonomy
        'category_id',
        'sub_category_id',
        'product_type_id',
        'collection_id',

        // Product Details
        'type_of_sample',
        'classification',
        'designed_by',

        // Attributes
        'materials',
        'type',
        'color',

        // Product Dimensions
        'product_height',
        'product_width',
        'product_length',
        'product_depth',

        // Packaging Dimensions
        'carton_height',
        'carton_width',
        'carton_length',
        'carton_depth',

        // Costing
        'purchase_cost',

        // Files
        'gdrive_link',
        'product_file',

        // Status
        'status',
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