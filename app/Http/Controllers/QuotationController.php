<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * Generate and download a PDF quotation for a single product.
     */
    public function download(Request $request, MI_Product $product)
    {
        $validated = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:150',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Product Price
        |--------------------------------------------------------------------------
        |
        | Your MI_Product model contains purchase_cost, not price.
        |
        */

        $unitPrice = (float) ($product->purchase_cost ?? 0);

        $quantity = (int) $validated['quantity'];

        $total = $unitPrice * $quantity;


        /*
        |--------------------------------------------------------------------------
        | Quote Number
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView('mi_app.quotation-pdf', [

            'product' => $product,

            'customer_name' => $validated['customer_name'],

            'quantity' => $quantity,

            'unit_price' => $unitPrice,

            'total' => $total,

            'quote_number' => $quoteNumber,

            'issued_at' => now(),

        ]);


        /*
        |--------------------------------------------------------------------------
        | PDF Filename
        |--------------------------------------------------------------------------
        */

        $filename =
            'quotation-' .
            ($product->sku ?? $product->product_id) .
            '.pdf';


        return $pdf->download($filename);
    }
}