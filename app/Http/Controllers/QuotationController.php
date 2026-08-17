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
        | Load Product Relationships
        |--------------------------------------------------------------------------
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
        | "price" is the customer selling/quotation price.
        | "purchase_cost" should remain internal.
        |
        */

        $unitPrice = (float) ($product->price ?? 0);

        $quantity = (int) $validated['quantity'];

        $total = $unitPrice * $quantity;


        /*
        |--------------------------------------------------------------------------
        | Select Product Image
        |--------------------------------------------------------------------------
        |
        | Priority:
        |
        | 1. Primary image
        | 2. First available image
        |
        */

        $productImage = null;

        $selectedImage = $product->images
            ->firstWhere('is_primary', true);

        if (!$selectedImage) {
            $selectedImage = $product->images->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare Image For Dompdf
        |--------------------------------------------------------------------------
        */

        if ($selectedImage) {

            /*
            |----------------------------------------------------------------------
            | Uploaded Image
            |----------------------------------------------------------------------
            */

            if (
                $selectedImage->image_type === 'upload' &&
                !empty($selectedImage->image_path)
            ) {

                $imagePath = public_path(
                    'storage/' . $selectedImage->image_path
                );

                if (file_exists($imagePath)) {
                    $productImage = $imagePath;
                }
            }


            /*
            |----------------------------------------------------------------------
            | Image URL
            |----------------------------------------------------------------------
            */

            elseif (
                $selectedImage->image_type === 'url' &&
                !empty($selectedImage->image_url)
            ) {

                $url = trim($selectedImage->image_url);


                /*
                | Google Drive: uc?id=...
                */

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
                }


                /*
                | Google Drive: file/d/...
                */

                elseif (
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
                }


                /*
                | Google Drive: open?id=...
                */

                elseif (
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
                }


                /*
                | Normal image URL
                */

                else {
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
                'product'       => $product,
                'customer_name' => $validated['customer_name'],
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
                'total'         => $total,
                'quote_number'  => $quoteNumber,
                'issued_at'     => now(),
                'product_image' => $productImage,
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
        | Filename
        |--------------------------------------------------------------------------
        */

        $filename =
            'quotation-' .
            ($product->sku ?? $product->product_id) .
            '.pdf';


        return $pdf->download($filename);
    }
}