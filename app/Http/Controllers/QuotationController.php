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
        */
    
        $productImage = null;
    
    
        /*
        |--------------------------------------------------------------------------
        | Helper: Convert Image To Dompdf-Safe Base64
        |--------------------------------------------------------------------------
        */
    
        $convertImageForPdf = function ($imageData, $mimeType = null) {
    
            if (
                empty($imageData) ||
                !is_string($imageData)
            ) {
                return null;
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Detect Actual Image Type
            |--------------------------------------------------------------------------
            |
            | Do not completely trust Content-Type.
            |
            */
    
            $detectedMime = null;
    
            try {
    
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
    
                $detectedMime =
                    $finfo->buffer($imageData);
    
            } catch (\Throwable $e) {
    
                $detectedMime = null;
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Use Detected MIME When Available
            |--------------------------------------------------------------------------
            */
    
            $mimeType =
                $detectedMime
                ?: $mimeType;
    
    
            $mimeType = strtolower(
                trim(
                    explode(';', (string) $mimeType)[0]
                )
            );
    
    
            /*
            |--------------------------------------------------------------------------
            | JPEG
            |--------------------------------------------------------------------------
            */
    
            if (
                $mimeType === 'image/jpeg' ||
                $mimeType === 'image/jpg'
            ) {
    
                return
                    'data:image/jpeg;base64,' .
                    base64_encode($imageData);
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | PNG
            |--------------------------------------------------------------------------
            */
    
            if ($mimeType === 'image/png') {
    
                return
                    'data:image/png;base64,' .
                    base64_encode($imageData);
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Convert Other Image Formats
            |--------------------------------------------------------------------------
            */
    
            if (
                !function_exists('imagecreatefromstring') ||
                !function_exists('imagejpeg')
            ) {
    
                return null;
            }
    
    
            try {
    
                $source =
                    @imagecreatefromstring(
                        $imageData
                    );
    
    
                if ($source === false) {
                    return null;
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | Image Dimensions
                |--------------------------------------------------------------------------
                */
    
                $width =
                    imagesx($source);
    
                $height =
                    imagesy($source);
    
    
                if (
                    $width <= 0 ||
                    $height <= 0
                ) {
    
                    imagedestroy($source);
    
                    return null;
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | Create White Background
                |--------------------------------------------------------------------------
                |
                | Important for transparent WebP / PNG / GIF.
                |
                */
    
                $background =
                    imagecreatetruecolor(
                        $width,
                        $height
                    );
    
    
                $white =
                    imagecolorallocate(
                        $background,
                        255,
                        255,
                        255
                    );
    
    
                imagefill(
                    $background,
                    0,
                    0,
                    $white
                );
    
    
                /*
                |--------------------------------------------------------------------------
                | Copy Image
                |--------------------------------------------------------------------------
                */
    
                imagecopy(
                    $background,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $width,
                    $height
                );
    
    
                /*
                |--------------------------------------------------------------------------
                | Convert To JPEG
                |--------------------------------------------------------------------------
                */
    
                ob_start();
    
                imagejpeg(
                    $background,
                    null,
                    90
                );
    
                $jpegData =
                    ob_get_clean();
    
    
                /*
                |--------------------------------------------------------------------------
                | Cleanup
                |--------------------------------------------------------------------------
                */
    
                imagedestroy($source);
    
                imagedestroy($background);
    
    
                if (
                    $jpegData === false ||
                    empty($jpegData)
                ) {
    
                    return null;
                }
    
    
                return
                    'data:image/jpeg;base64,' .
                    base64_encode($jpegData);
    
    
            } catch (\Throwable $e) {
    
                return null;
            }
        };
    
    
        /*
        |--------------------------------------------------------------------------
        | Find A Valid Product Image
        |--------------------------------------------------------------------------
        */
    
        foreach ($product->images as $image) {
    
            /*
            |--------------------------------------------------------------------------
            | Uploaded Image
            |--------------------------------------------------------------------------
            */
    
            if (
                $image->image_type === 'upload' &&
                !empty($image->image_path)
            ) {
    
                $storagePath =
                    storage_path(
                        'app/public/' .
                        ltrim(
                            $image->image_path,
                            '/'
                        )
                    );
    
    
                /*
                |--------------------------------------------------------------------------
                | Check File Exists
                |--------------------------------------------------------------------------
                */
    
                if (
                    !is_file($storagePath) ||
                    !is_readable($storagePath)
                ) {
    
                    \Log::warning(
                        'Quotation image file not found',
                        [
                            'product_id' =>
                                $product->product_id,
    
                            'image_id' =>
                                $image->id ??
                                $image->product_image_id ??
                                null,
    
                            'path' =>
                                $storagePath,
                        ]
                    );
    
                    continue;
                }
    
    
                try {
    
                    $imageData =
                        file_get_contents(
                            $storagePath
                        );
    
    
                    if (
                        $imageData === false ||
                        empty($imageData)
                    ) {
    
                        continue;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | Convert Image
                    |--------------------------------------------------------------------------
                    */
    
                    $convertedImage =
                        $convertImageForPdf(
                            $imageData
                        );
    
    
                    if (
                        $convertedImage !== null
                    ) {
    
                        $productImage =
                            $convertedImage;
    
                        break;
                    }
    
    
                } catch (\Throwable $e) {
    
                    \Log::warning(
                        'Quotation image conversion failed',
                        [
                            'product_id' =>
                                $product->product_id,
    
                            'path' =>
                                $storagePath,
    
                            'error' =>
                                $e->getMessage(),
                        ]
                    );
    
                    continue;
                }
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | External Image / Google Drive
            |--------------------------------------------------------------------------
            */
    
            if (
                $image->image_type !== 'upload' &&
                !empty($image->image_url)
            ) {
    
                $imageUrl =
                    trim(
                        $image->image_url
                    );
    
    
                /*
                |--------------------------------------------------------------------------
                | Google Drive / UC URL
                |--------------------------------------------------------------------------
                */
    
                if (
                    preg_match(
                        '/drive\.google\.com\/uc\?.*id=([^&]+)/',
                        $imageUrl,
                        $matches
                    )
                ) {
    
                    $fileId =
                        $matches[1];
    
                    $imageUrl =
                        'https://drive.google.com/thumbnail?id=' .
                        $fileId .
                        '&sz=w1600';
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | Google Drive File URL
                |--------------------------------------------------------------------------
                */
    
                elseif (
                    preg_match(
                        '#drive\.google\.com/file/d/([^/]+)#',
                        $imageUrl,
                        $matches
                    )
                ) {
    
                    $fileId =
                        $matches[1];
    
                    $imageUrl =
                        'https://drive.google.com/thumbnail?id=' .
                        $fileId .
                        '&sz=w1600';
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | Google Drive Open URL
                |--------------------------------------------------------------------------
                */
    
                elseif (
                    preg_match(
                        '/drive\.google\.com\/open\?id=([^&]+)/',
                        $imageUrl,
                        $matches
                    )
                ) {
    
                    $fileId =
                        $matches[1];
    
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
    
                    $response =
                        Http::timeout(15)
                            ->withOptions([
                                'verify' => false,
                            ])
                            ->get($imageUrl);
    
    
                    if (
                        !$response->successful()
                    ) {
    
                        \Log::warning(
                            'Quotation external image download failed',
                            [
                                'product_id' =>
                                    $product->product_id,
    
                                'url' =>
                                    $imageUrl,
    
                                'status' =>
                                    $response->status(),
                            ]
                        );
    
                        continue;
                    }
    
    
                    $imageData =
                        $response->body();
    
    
                    if (
                        empty($imageData)
                    ) {
    
                        continue;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | Convert Image
                    |--------------------------------------------------------------------------
                    */
    
                    $convertedImage =
                        $convertImageForPdf(
                            $imageData,
                            $response->header(
                                'Content-Type'
                            )
                        );
    
    
                    if (
                        $convertedImage !== null
                    ) {
    
                        $productImage =
                            $convertedImage;
    
                        break;
                    }
    
    
                } catch (\Throwable $e) {
    
                    \Log::warning(
                        'Quotation external image error',
                        [
                            'product_id' =>
                                $product->product_id,
    
                            'url' =>
                                $imageUrl,
    
                            'error' =>
                                $e->getMessage(),
                        ]
                    );
    
                    continue;
                }
            }
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */
    
        $pdf =
            Pdf::loadView(
                'mi_app.public.quotation-pdf',
                [
                    'product' =>
                        $product,
    
                    'customer_name' =>
                        $validated['customer_name'],
    
                    'quantity' =>
                        $quantity,
    
                    'unit_price' =>
                        $unitPrice,
    
                    'subtotal' =>
                        $subtotal,
    
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
    
        $pdf->setPaper(
            'A4',
            'portrait'
        );
    
    
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
    
        return $pdf->download(
            $filename
        );
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