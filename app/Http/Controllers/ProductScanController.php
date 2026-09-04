<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductScanController extends Controller
{
    /**
     * Handle a scanned product tag/QR code and show the product detail page.
     *
     * Expected usage:
     *
     * GET /mi-app/scan?code=SOME-SKU-OR-ID
     *
     * If no code is present, show a scan-entry page (e.g. camera scanner UI)
     * instead of immediately failing — this lets the route also serve as
     * the landing page a "Scan Another Product" button redirects to.
     */
    public function scan(Request $request)
    {
        $code = $request->query('code');

        /*
        |--------------------------------------------------------------------------
        | No Code Provided
        |--------------------------------------------------------------------------
        |
        | Show the scanner entry page rather than a 404 — this route is also
        | the target of pdScanAnotherProduct() in product-detail.blade.php,
        | which navigates here with no code at all.
        |
        */

        if (empty($code)) {

            return view('mi_app.public.scan');
        }

        $code = trim((string) $code);

        /*
        |--------------------------------------------------------------------------
        | Resolve Product
        |--------------------------------------------------------------------------
        |
        | Adjust this lookup to match how your tags actually encode product
        | identity. As written, it tries an exact SKU match first (the
        | common case for printed/engraved tags), then falls back to a
        | numeric product_id (in case a QR code encodes the raw ID instead).
        |
        */

        $product = $this->resolveProductFromCode($code);

        if (!$product) {

            Log::info(
                'Product scan lookup failed',
                [
                    'code' => $code,
                ]
            );

            return view('mi_app.public.scan', [
                'error' => "No product found for code \"{$code}\".",
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Render Product Detail
        |--------------------------------------------------------------------------
        */

        return view('mi_app.public.product-detail', [
            'product' => $product,
        ]);
    }

    /**
     * Resolve a scanned code to a product, trying SKU first, then
     * numeric product ID.
     */
    private function resolveProductFromCode(string $code): ?MI_Product
    {
        $query = MI_Product::with([
            'category',
            'subCategory',
            'productType',
            'collection',
            'images',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Try SKU Match
        |--------------------------------------------------------------------------
        */

        $product = (clone $query)
            ->where('sku', $code)
            ->first();

        if ($product) {
            return $product;
        }

        /*
        |--------------------------------------------------------------------------
        | Try Numeric Product ID
        |--------------------------------------------------------------------------
        |
        | Only attempted if the code is purely numeric, to avoid a wasted
        | query (and to avoid accidentally matching "0" for garbage input).
        |
        */

        if (ctype_digit($code)) {

            $product = (clone $query)
                ->where('product_id', (int) $code)
                ->first();

            if ($product) {
                return $product;
            }
        }

        return null;
    }
}