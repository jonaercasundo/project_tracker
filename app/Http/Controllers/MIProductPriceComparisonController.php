<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MIProductPriceComparisonController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['prohibited'],
            'image_url' => ['prohibited'],
            'image_file' => ['prohibited'],
            'product_name' => ['required', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:300'],
            'max_results' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json([
            'message' => 'Price comparison search is not configured. Configure an approved product-search provider that returns verifiable product offers.',
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
