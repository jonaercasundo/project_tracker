<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * Generate and stream a downloadable PDF quotation for a single product.
     *
     * Expects: customer_name (string), quantity (integer, min 1)
     */
    public function download(Request $request, Product $product)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'quantity'      => ['required', 'integer', 'min:1'],
        ]);

        $unitPrice = (float) ($product->price ?? 0);
        $quantity  = (int) $validated['quantity'];
        $total     = $unitPrice * $quantity;

        $pdf = Pdf::loadView('mi_app.quotation-pdf', [
            'product'       => $product,
            'customer_name' => $validated['customer_name'],
            'quantity'      => $quantity,
            'unit_price'    => $unitPrice,
            'total'         => $total,
            'quote_number'  => 'Q-' . now()->format('Ymd') . '-' . str_pad((string) $product->product_id, 4, '0', STR_PAD_LEFT),
            'issued_at'     => now(),
        ]);

        $filename = 'quotation-' . ($product->sku ?? $product->product_id) . '.pdf';

        return $pdf->download($filename);
    }
}
