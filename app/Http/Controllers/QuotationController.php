<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    /**
     * Generate and download a PDF quotation for a single product.
     */
    public function download(Request $request, MI_Product $product)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
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
        | Load all relationships needed by the quotation.
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
        | Price
        |--------------------------------------------------------------------------
        |
        | Use purchase_cost as requested for the quotation.
        |
        */

        $quantity = (int) $validated['quantity'];

        $unitPrice = (float) ($product->price ?? 0);
        
        $subtotal = $unitPrice * $quantity;
        
        $total = $subtotal;


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
        | Product Image
        |--------------------------------------------------------------------------
        |
        | Dompdf works best with a Base64 image.
        |
        | We take the first available product image.
        |
        */

        $productImage = null;

        foreach ($product->images as $image) {

            /*
            |--------------------------------------------------------------------------
            | Uploaded Image
            |--------------------------------------------------------------------------
            */

            if (
                isset($image->image_type) &&
                $image->image_type === 'upload' &&
                !empty($image->image_path)
            ) {

                $storagePath = storage_path(
                    'app/public/' . ltrim($image->image_path, '/')
                );

                if (file_exists($storagePath)) {

                    $mimeType = mime_content_type($storagePath);

                    $imageData = file_get_contents($storagePath);

                    if ($imageData !== false) {

                        $productImage =
                            'data:' .
                            $mimeType .
                            ';base64,' .
                            base64_encode($imageData);

                        break;
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | External Image / Google Drive
            |--------------------------------------------------------------------------
            */

            if (
                isset($image->image_type) &&
                $image->image_type !== 'upload' &&
                !empty($image->image_url)
            ) {

                $imageUrl = trim($image->image_url);


                /*
                |--------------------------------------------------------------------------
                | Convert Google Drive URL
                |--------------------------------------------------------------------------
                */

                if (
                    preg_match(
                        '/drive\.google\.com\/uc\?.*id=([^&]+)/',
                        $imageUrl,
                        $matches
                    )
                ) {

                    $fileId = $matches[1];

                    $imageUrl =
                        'https://drive.google.com/thumbnail?id=' .
                        $fileId .
                        '&sz=w1600';
                }


                elseif (
                    preg_match(
                        '#drive\.google\.com/file/d/([^/]+)#',
                        $imageUrl,
                        $matches
                    )
                ) {

                    $fileId = $matches[1];

                    $imageUrl =
                        'https://drive.google.com/thumbnail?id=' .
                        $fileId .
                        '&sz=w1600';
                }


                elseif (
                    preg_match(
                        '/drive\.google\.com\/open\?id=([^&]+)/',
                        $imageUrl,
                        $matches
                    )
                ) {

                    $fileId = $matches[1];

                    $imageUrl =
                        'https://drive.google.com/thumbnail?id=' .
                        $fileId .
                        '&sz=w1600';
                }


                /*
                |--------------------------------------------------------------------------
                | Download External Image
                |--------------------------------------------------------------------------
                */

                try {

                    $response = Http::timeout(15)
                        ->withOptions([
                            'verify' => false,
                        ])
                        ->get($imageUrl);

                    if ($response->successful()) {

                        $contentType =
                            $response->header('Content-Type');

                        /*
                        |--------------------------------------------------------------------------
                        | Make sure the response is actually an image
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $contentType &&
                            str_starts_with(
                                strtolower($contentType),
                                'image/'
                            )
                        ) {

                            $productImage =
                                'data:' .
                                $contentType .
                                ';base64,' .
                                base64_encode(
                                    $response->body()
                                );

                            break;
                        }
                    }

                } catch (\Throwable $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Ignore image failure
                    |--------------------------------------------------------------------------
                    |
                    | The quotation will still generate even if
                    | an external image cannot be downloaded.
                    |
                    */
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'mi_app.public.quotation-pdf',
            [
                'product' => $product,
                'customer_name' => $validated['customer_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'total' => $total,
                'quote_number' => $quoteNumber,
                'issued_at' => now(),
                'product_image' => $productImage,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | PDF Settings
        |--------------------------------------------------------------------------
        */

        $pdf->setPaper('A4', 'portrait');

        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Filename
        |--------------------------------------------------------------------------
        */

        $filename =
            'quotation-' .
            ($product->sku ?? $product->product_id) .
            '.pdf';


        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return $pdf->download($filename);
    }
    public function print(Request $request, $product)
    {
        $product = MI_Product::with([
            'category',
            'subCategory',
            'productType',
            'collection',
            'images',
        ])->findOrFail($product);
    
        $quantity = max(
            1,
            (int) $request->input('quantity', 1)
        );
    
        $customer_name = trim(
            $request->input('customer_name', '')
        );
    
        $unit_price = (float) $product->price;
    
        $subtotal = $unit_price * $quantity;
    
        $total = $subtotal;
    
        $quote_number = 'Q-' . now()->format('YmdHis');
    
        $issued_at = now();
    
    
        /*
        |--------------------------------------------------------------------------
        | PRODUCT IMAGE
        |--------------------------------------------------------------------------
        */
    
        $product_image = null;
    
        $image = $product->images->first();
    
        if ($image) {
    
            /*
            |--------------------------------------------------------------------------
            | Uploaded image
            |--------------------------------------------------------------------------
            */
    
            if (
                $image->image_type === 'upload'
                && !empty($image->image_path)
            ) {
    
                $product_image = public_path(
                    'storage/' . $image->image_path
                );
    
            }
    
            /*
            |--------------------------------------------------------------------------
            | External image / Google Drive
            |--------------------------------------------------------------------------
            */
    
            elseif (!empty($image->image_url)) {
    
                $url = trim($image->image_url);
    
                // Google Drive: /file/d/FILE_ID/
                if (
                    preg_match(
                        '#drive\.google\.com/file/d/([^/]+)#',
                        $url,
                        $matches
                    )
                ) {
    
                    $product_image =
                        'https://drive.google.com/thumbnail?id='
                        . $matches[1]
                        . '&sz=w1600';
    
                }
    
                // Google Drive: ?id=FILE_ID
                elseif (
                    preg_match(
                        '/drive\.google\.com\/open\?id=([^&]+)/',
                        $url,
                        $matches
                    )
                ) {
    
                    $product_image =
                        'https://drive.google.com/thumbnail?id='
                        . $matches[1]
                        . '&sz=w1600';
    
                }
    
                // Google Drive: uc?id=FILE_ID
                elseif (
                    preg_match(
                        '/drive\.google\.com\/uc\?.*id=([^&]+)/',
                        $url,
                        $matches
                    )
                ) {
    
                    $product_image =
                        'https://drive.google.com/thumbnail?id='
                        . $matches[1]
                        . '&sz=w1600';
    
                }
    
                // Normal external image URL
                else {
    
                    $product_image = $url;
    
                }
            }
        }
    
    
        return view(
            'mi_app.public.print',
            compact(
                'product',
                'quantity',
                'customer_name',
                'unit_price',
                'subtotal',
                'total',
                'quote_number',
                'issued_at',
                'product_image'
            )
        );
    }
}