<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;

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

        if (Schema::hasTable('mi_product_variants')) {
            $product->load('variants');
        } else {
            $product->setRelation('variants', new EloquentCollection);
        }

        return view('mi_app.public.show', [
            'product' => $product,
            'selectedVariant' => null,
        ]);
    }
}
