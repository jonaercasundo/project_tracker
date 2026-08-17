<?php

namespace App\Http\Controllers;

use App\Models\Product;

class PublicProductController extends Controller
{
    /**
     * The page a customer lands on after scanning a product's QR code.
     * No auth required - this is the public-facing mobile view.
     */
    public function show(Product $product)
    {
        $product->load(['category', 'subCategory', 'collection']);

        return view('public.product-show', [
            'product' => $product,
        ]);
    }
}
