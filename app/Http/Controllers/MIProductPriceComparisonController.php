<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MIProductPriceComparisonController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['required', Rule::in(['name', 'image'])],
            'product_name' => ['required_if:method,name', 'nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:300'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image_file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'max_results' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($request->input('method') === 'image' && ! $request->filled('image_url') && ! $request->hasFile('image_file')) {
            return response()->json(['message' => 'Choose an image file or enter an image URL to search by image.', 'results' => []], 422);
        }

        return response()->json([
            'message' => 'Price comparison search is not configured. Configure an approved product-search provider that supports product offers and image-based search.',
            'results' => [],
        ], 503);
    }

    public function store(Request $request, MI_Product $product): JsonResponse
    {
        Gate::authorize('update', $product);
        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1', 'max:20'],
            'results.*' => ['required', 'array:store_name,product_name,photo_url,price,currency,product_link'],
            'results.*.store_name' => ['required', 'string', 'max:255'],
            'results.*.product_name' => ['required', 'string', 'max:255'],
            'results.*.photo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'results.*.price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'results.*.currency' => ['required', 'alpha', 'size:3'],
            'results.*.product_link' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $saved = DB::transaction(function () use ($product, $validated): array {
            $comparisons = [];
            foreach ($validated['results'] as $result) {
                $comparisons[] = $product->priceComparisons()->firstOrCreate(
                    ['product_link' => $result['product_link']],
                    [...$result, 'currency' => strtoupper($result['currency'])],
                );
            }

            return $comparisons;
        });

        return response()->json(['message' => 'Selected comparisons saved.', 'comparisons' => $saved], 201);
    }
}
