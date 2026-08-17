<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;

class PublicProductController extends Controller
{
    /**
     * The page a customer lands on after scanning a product's QR code.
     * No auth required - this is the public-facing mobile view.
     */
    public function show(MI_Product $product)
    {
        // TODO: confirm the actual "published/active" value for `status`
        // on your model, then uncomment the line below:
        // abort_if($product->status !== 'active', 404);

        $product->load(['category', 'subCategory', 'collection', 'images']);

        return view('public.show', [
            'product' => $product,
        ]);
    }
}
