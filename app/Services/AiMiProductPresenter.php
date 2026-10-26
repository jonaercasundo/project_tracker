<?php

namespace App\Services;

use App\Models\MI_Product;
use Illuminate\Support\Facades\Storage;

class AiMiProductPresenter
{
    /** @return array<string, mixed> */
    public function present(MI_Product $product): array
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

        return [
            'id' => $product->product_id,
            'name' => $product->item_name,
            'sku' => $product->sku ?: $product->item_code,
            'code' => $product->item_code,
            'price' => $product->price,
            'currency' => 'PHP',
            'pricing_basis' => 'MI product selling price',
            'image' => $imageUrl,
            'category_id' => $product->category_id,
            'sub_category_id' => $product->sub_category_id,
            'materials' => $product->materials,
            'colors' => $product->color,
        ];
    }
}
