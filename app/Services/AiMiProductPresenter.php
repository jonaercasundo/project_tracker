<?php

namespace App\Services;

use App\Models\MI_Product;
use Illuminate\Support\Facades\Storage;

class AiMiProductPresenter
{
    /** @return array<string, mixed> */
    public function present(MI_Product $product, bool $includeSellingPrice = false): array
    {
        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $imageUrl = null;
        if ($image?->image_type === 'upload' && $image->image_path) {
            $imageUrl = Storage::disk('public')->url($image->image_path);
        } elseif ($image?->image_url) {
            $scheme = parse_url($image->image_url, PHP_URL_SCHEME);
            if (in_array($scheme, ['https', 'http'], true)) {
                $imageUrl = $image->image_url;
            }
        }

        $presentedProduct = [
            'id' => $product->product_id,
            'name' => $product->item_name,
            'sku' => $product->sku ?: $product->item_code,
            'code' => $product->item_code,
            'image' => $imageUrl,
            'category_id' => $product->category_id,
            'category' => $product->category?->name,
            'sub_category_id' => $product->sub_category_id,
            'sub_category' => $product->subCategory?->name,
            'collection' => $product->collection?->name,
            'materials' => $product->materials,
            'colors' => $product->color,
            'specifications' => [
                'description' => $product->description,
                'type' => $product->type,
                'product_type' => $product->productType?->name,
                'classification' => $product->classification,
                'designer' => $product->designed_by,
                'country_of_production' => $product->country_of_production,
                'dimensions' => [
                    'height' => $product->product_height,
                    'width' => $product->product_width,
                    'length' => $product->product_length,
                    'depth' => $product->product_depth,
                ],
            ],
            'url' => route('mi_app.show', $product->getKey()),
        ];

        if ($includeSellingPrice) {
            $presentedProduct['price'] = $product->price;
            $presentedProduct['currency'] = 'PHP';
            $presentedProduct['pricing_basis'] = 'MI product selling price';
        }

        return $presentedProduct;
    }
}
