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
        // If the scanned value is a full tracker URL, extract the trailing
        // path segment (e.g. "https://tracker.metro-mobilia.com/p/20" -> "20")
        // before attempting any lookup.
        $code = $this->extractCodeFromInput($code);

        if ($code === '') {
            return null;
        }

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

    /**
     * Normalize a scanned value into a bare product code.
     *
     * Handles two input shapes:
     *  - A bare code/SKU typed or scanned directly, e.g. "MI-1042"
     *  - A full tracker URL, e.g. "https://tracker.metro-mobilia.com/p/20",
     *    from which the trailing path segment ("20") is extracted.
     */
    private function extractCodeFromInput(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        // Only attempt URL parsing if this actually looks like a URL —
        // avoids mangling a bare SKU that happens to contain a slash.
        if (filter_var($input, FILTER_VALIDATE_URL)) {

            $path = parse_url($input, PHP_URL_PATH) ?? '';
            $segments = array_values(array_filter(explode('/', $path)));

            if (!empty($segments)) {
                return trim(end($segments));
            }

            return '';
        }

        return $input;
    }
}