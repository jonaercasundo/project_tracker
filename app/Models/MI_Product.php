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

    public function getMaterialsAttribute($value)
    {
        return $this->decodeArrayAttribute($value);
    }

    public function setMaterialsAttribute($value)
    {
        $this->attributes['materials'] = $this->encodeArrayAttribute($value);
    }

    public function getColorAttribute($value)
    {
        return $this->decodeArrayAttribute($value);
    }

    public function setColorAttribute($value)
    {
        $this->attributes['color'] = $this->encodeArrayAttribute($value);
    }

    private function decodeArrayAttribute($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(function ($item) {
                return trim((string) $item);
            }, $value), function ($item) {
                return $item !== '';
            }));
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter(array_map(function ($item) {
                    return trim((string) $item);
                }, $decoded), function ($item) {
                    return $item !== '';
                }));
            }

            return [$value];
        }

        return [(string) $value];
    }

    private function encodeArrayAttribute($value): string
    {
        if (!is_array($value)) {
            $value = $value ? [$value] : [];
        }

        $normalized = array_values(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, $value), function ($item) {
            return $item !== '';
        }));

        return json_encode($normalized);
    }

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
    public function images()
    {
        return $this->hasMany(
            MI_Product_Image::class,
            'product_id',
            'product_id'
        )
        ->orderBy('sort_order');
    }
}