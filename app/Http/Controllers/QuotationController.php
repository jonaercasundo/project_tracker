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
        /*
        |--------------------------------------------------------------------------
        | Validate Customer Information
        |--------------------------------------------------------------------------
        */

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
        | Load Product Information
        |--------------------------------------------------------------------------
        |
        | Load the same product relationships used by the public product page.
        | This also loads the product images so the quotation PDF can display
        | the primary/first available image.
        |
        */

        $product->load([
            'category',
            'subCategory',
            'productType',
            'collection',
            'images',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Product Price
        |--------------------------------------------------------------------------
        |
        | MI_Product uses purchase_cost as the current price field.
        |
        */

        $unitPrice = (float) ($product->purchase_cost ?? 0);

        $quantity = (int) $validated['quantity'];

        $total = $unitPrice * $quantity;


        /*
        |--------------------------------------------------------------------------
        | Product Image
        |--------------------------------------------------------------------------
        |
        | Prefer the primary image.
        | If there is no primary image, use the first available image.
        |
        */

        $productImage = null;

        $primaryImage = $product->images
            ->firstWhere('is_primary', true);

        $selectedImage = $primaryImage
            ?? $product->images->first();

        if ($selectedImage) {

            if ($selectedImage->image_type === 'upload') {

                if (!empty($selectedImage->image_path)) {

                    $productImage = public_path(
                        'storage/' . $selectedImage->image_path
                    );

                    /*
                    |------------------------------------------------------------------
                    | Make sure the file actually exists.
                    |------------------------------------------------------------------
                    */

                    if (!file_exists($productImage)) {
                        $productImage = null;
                    }
                }

            } elseif ($selectedImage->image_type === 'url') {

                /*
                |------------------------------------------------------------------
                | Google Drive images
                |------------------------------------------------------------------
                */

                $url = trim((string) $selectedImage->image_url);

                if (
                    preg_match(
                        '/drive\.google\.com\/uc\?.*id=([^&]+)/',
                        $url,
                        $matches
                    )
                ) {
                    $productImage =
                        'https://drive.google.com/thumbnail?id=' .
                        $matches[1] .
                        '&sz=w1200';

                } elseif (
                    preg_match(
                        '#drive\.google\.com/file/d/([^/]+)#',
                        $url,
                        $matches
                    )
                ) {
                    $productImage =
                        'https://drive.google.com/thumbnail?id=' .
                        $matches[1] .
                        '&sz=w1200';

                } elseif (
                    preg_match(
                        '/drive\.google\.com\/open\?id=([^&]+)/',
                        $url,
                        $matches
                    )
                ) {
                    $productImage =
                        'https://drive.google.com/thumbnail?id=' .
                        $matches[1] .
                        '&sz=w1200';

                } else {

                    $productImage = $url;
                }
            }
        }


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

        $pdf = Pdf::loadView(
            'mi_app.public.quotation-pdf',
            [
                'product' => $product,

                'customer_name' =>
                    $validated['customer_name'],

                'quantity' =>
                    $quantity,

                'unit_price' =>
                    $unitPrice,

                'total' =>
                    $total,

                'quote_number' =>
                    $quoteNumber,

                'issued_at' =>
                    now(),

                'product_image' =>
                    $productImage,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | PDF Settings
        |--------------------------------------------------------------------------
        */

        $pdf->setPaper('A4', 'portrait');


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