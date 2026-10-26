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
            'collection' => $product->collection?->name,
            'materials' => $product->materials,
            'colors' => $product->color,
        ];

        if ($includeSellingPrice) {
            $presentedProduct['price'] = $product->price;
            $presentedProduct['currency'] = 'PHP';
            $presentedProduct['pricing_basis'] = 'MI product selling price';
        }

        return $presentedProduct;
    }
}
