<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * Generate and download a quotation PDF for a single product.
     */
    public function download(Request $request, MI_Product $product)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'quantity'      => ['required', 'integer', 'min:1'],
        ]);

        // Load product images
        $product->load([
            'images',
            'category',
            'subCategory',
            'productType',
            'collection',
        ]);

        $unitPrice = (float) ($product->price ?? $product->purchase_cost ?? 0);

        $quantity = (int) $validated['quantity'];

        $total = $unitPrice * $quantity;

        /*
        |--------------------------------------------------------------------------
        | Get ONE product image
        |--------------------------------------------------------------------------
        */
        $productImage = null;

        $firstImage = $product->images
            ->sortBy('sort_order')
            ->first();

        if ($firstImage) {

            if ($firstImage->image_type === 'upload' && $firstImage->image_path) {

                $imagePath = storage_path(
                    'app/public/' . $firstImage->image_path
                );

                if (file_exists($imagePath)) {
                    $productImage = $imagePath;
                }

            } elseif ($firstImage->image_type === 'url' && $firstImage->image_url) {

                $productImage = $firstImage->image_url;
            }
        }

        $quoteNumber =
            'Q-' .
            now()->format('Ymd') .
            '-' .
            str_pad(
                (string) $product->product_id,
                4,
                '0',
                STR_PAD_LEFT
            );

        $pdf = Pdf::loadView('mi_app.quotation-pdf', [

            'product'       => $product,

            'customer_name' => $validated['customer_name'],

            'quantity'      => $quantity,

            'unit_price'    => $unitPrice,

            'total'         => $total,

            'quote_number'  => $quoteNumber,

            'issued_at'     => now(),

            'product_image' => $productImage,

        ]);

        $filename =
            'quotation-' .
            ($product->sku ?? $product->product_id) .
            '.pdf';

        return $pdf->download($filename);
    }
}