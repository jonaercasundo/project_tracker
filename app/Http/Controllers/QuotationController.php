<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuotationController extends Controller
{
    /**
     * Generate and download a multi-product quotation PDF.
     */
    public function download(Request $request)
    {
        $quotation = $this->buildQuotation($request);

        $pdf = Pdf::loadView(
            'mi_app.public.quotation-pdf',
            $quotation
        );

        $pdf->setPaper('A4', 'portrait');

        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'isPhpEnabled'         => false,
            'defaultFont'          => 'DejaVu Sans',
        ]);

        $filename = 'quotation-' . $quotation['quote_number'] . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate a printable multi-product quotation.
     */
    public function print(Request $request)
    {
        $quotation = $this->buildQuotation($request);

        return view(
            'mi_app.public.print',
            $quotation
        );
    }

    /**
     * Build quotation data for multiple products.
     *
     * The browser sends:
     *
     * products = [
     *     {
     *         "id": 1,
     *         "quantity": 2
     *     },
     *     {
     *         "id": 5,
     *         "quantity": 1
     *     }
     * ]
     */
    private function buildQuotation(Request $request): array
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

            'products' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Decode Products
        |--------------------------------------------------------------------------
        */

        $productsInput = json_decode(
            $validated['products'],
            true
        );

        if (
            !is_array($productsInput) ||
            empty($productsInput)
        ) {
            abort(
                422,
                'Please add at least one product to the quotation.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Product Structure
        |--------------------------------------------------------------------------
        */

        $cleanProducts = collect($productsInput)
            ->map(function ($item) {

                if (!is_array($item)) {
                    return null;
                }

                $id = (int) ($item['id'] ?? 0);

                $quantity = max(
                    1,
                    (int) ($item['quantity'] ?? 1)
                );

                if ($id <= 0) {
                    return null;
                }

                return [
                    'id'       => $id,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values();

        if ($cleanProducts->isEmpty()) {
            abort(
                422,
                'No valid products were submitted.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Product IDs
        |--------------------------------------------------------------------------
        */

        $productIds = $cleanProducts
            ->pluck('id')
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Load Products From Database
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Price, product name, SKU, images, etc. are retrieved
        | from the database.
        |
        | We DO NOT trust the browser for pricing.
        |
        */

        $products = MI_Product::with([
            'category',
            'subCategory',
            'productType',
            'collection',
            'images',
        ])
            ->whereIn(
                'product_id',
                $productIds
            )
            ->get()
            ->keyBy('product_id');

        /*
        |--------------------------------------------------------------------------
        | Make Sure All Products Exist
        |--------------------------------------------------------------------------
        */

        foreach ($cleanProducts as $item) {

            if (!$products->has($item['id'])) {

                abort(
                    404,
                    'Product ID ' .
                    $item['id'] .
                    ' was not found.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build Quotation Items
        |--------------------------------------------------------------------------
        |
        | Preserve the exact order from localStorage.
        |
        */

        $quotationItems = $cleanProducts
            ->map(function ($item) use ($products) {

                $product = $products->get(
                    $item['id']
                );

                $quantity = (int) $item['quantity'];

                $unitPrice = (float) (
                    $product->price ?? 0
                );

                $subtotal = $unitPrice * $quantity;

                /*
                |--------------------------------------------------------------------------
                | Product Image
                |--------------------------------------------------------------------------
                */

                $productImage =
                    $this->getProductImageForPdf(
                        $product
                    );

                return (object) [
                    'product'      => $product,
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => $subtotal,
                    'product_image'=> $productImage,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $total = $quotationItems->sum(
            'subtotal'
        );

        /*
        |--------------------------------------------------------------------------
        | Quote Number
        |--------------------------------------------------------------------------
        */

        $quoteNumber =
            'Q-' .
            now()->format('Ymd-His');

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customerName =
            trim(
                $validated['customer_name']
            );

        /*
        |--------------------------------------------------------------------------
        | Issued Date
        |--------------------------------------------------------------------------
        */

        $issuedAt = now();

        /*
        |--------------------------------------------------------------------------
        | Return Complete Quotation Data
        |--------------------------------------------------------------------------
        */

        return [
            'customer_name'  => $customerName,
            'quotationItems' => $quotationItems,
            'total'          => $total,
            'quote_number'   => $quoteNumber,
            'issued_at'      => $issuedAt,
        ];
    }

    /**
     * Get a Dompdf-safe image for a product.
     *
     * Returns:
     *
     * data:image/jpeg;base64,...
     *
     * or
     *
     * data:image/png;base64,...
     *
     * or null.
     */
    private function getProductImageForPdf(
        MI_Product $product
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | No Images
        |--------------------------------------------------------------------------
        */

        if (
            !$product->relationLoaded('images') ||
            $product->images->isEmpty()
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Try Every Product Image
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

                if (
                    !is_file($storagePath) ||
                    !is_readable($storagePath)
                ) {

                    Log::warning(
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

                    $converted =
                        $this->convertImageForPdf(
                            $imageData
                        );

                    if ($converted !== null) {
                        return $converted;
                    }

                } catch (\Throwable $e) {

                    Log::warning(
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
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | External Image
            |--------------------------------------------------------------------------
            */

            if (
                !empty($image->image_url)
            ) {

                $imageUrl =
                    $this->convertGoogleDriveUrl(
                        trim(
                            $image->image_url
                        )
                    );

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

                        Log::warning(
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

                    $converted =
                        $this->convertImageForPdf(
                            $imageData,
                            $response->header(
                                'Content-Type'
                            )
                        );

                    if ($converted !== null) {
                        return $converted;
                    }

                } catch (\Throwable $e) {

                    Log::warning(
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
                }
            }
        }

        return null;
    }

    /**
     * Convert Google Drive URLs into thumbnail URLs.
     */
    private function convertGoogleDriveUrl(
        string $url
    ): string {

        /*
        |--------------------------------------------------------------------------
        | /file/d/FILE_ID/
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '#drive\.google\.com/file/d/([^/]+)#',
                $url,
                $matches
            )
        ) {

            return
                'https://drive.google.com/thumbnail?id=' .
                $matches[1] .
                '&sz=w1600';
        }

        /*
        |--------------------------------------------------------------------------
        | /open?id=FILE_ID
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '#drive\.google\.com/open\?id=([^&]+)#',
                $url,
                $matches
            )
        ) {

            return
                'https://drive.google.com/thumbnail?id=' .
                $matches[1] .
                '&sz=w1600';
        }

        /*
        |--------------------------------------------------------------------------
        | /uc?id=FILE_ID
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '#drive\.google\.com/uc\?.*id=([^&]+)#',
                $url,
                $matches
            )
        ) {

            return
                'https://drive.google.com/thumbnail?id=' .
                $matches[1] .
                '&sz=w1600';
        }

        return $url;
    }

    /**
     * Convert image binary data to a Dompdf-safe Base64 image.
     */
    private function convertImageForPdf(
        $imageData,
        $mimeType = null
    ): ?string {

        if (
            empty($imageData) ||
            !is_string($imageData)
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Detect Actual MIME Type
        |--------------------------------------------------------------------------
        */

        $detectedMime = null;

        try {

            $finfo =
                new \finfo(
                    FILEINFO_MIME_TYPE
                );

            $detectedMime =
                $finfo->buffer(
                    $imageData
                );

        } catch (\Throwable $e) {

            $detectedMime = null;
        }

        $mimeType =
            $detectedMime ?: $mimeType;

        $mimeType =
            strtolower(
                trim(
                    explode(
                        ';',
                        (string) $mimeType
                    )[0]
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
                base64_encode(
                    $imageData
                );
        }

        /*
        |--------------------------------------------------------------------------
        | PNG
        |--------------------------------------------------------------------------
        */

        if (
            $mimeType === 'image/png'
        ) {

            return
                'data:image/png;base64,' .
                base64_encode(
                    $imageData
                );
        }

        /*
        |--------------------------------------------------------------------------
        | GIF / WEBP / Other Formats
        |--------------------------------------------------------------------------
        */

        if (
            !function_exists(
                'imagecreatefromstring'
            ) ||
            !function_exists(
                'imagejpeg'
            )
        ) {
            return null;
        }

        try {

            $source =
                @imagecreatefromstring(
                    $imageData
                );

            if (
                $source === false
            ) {
                return null;
            }

            $width =
                imagesx($source);

            $height =
                imagesy($source);

            if (
                $width <= 0 ||
                $height <= 0
            ) {

                imagedestroy(
                    $source
                );

                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | White Background
            |--------------------------------------------------------------------------
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
            | Copy Source
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

            imagedestroy(
                $source
            );

            imagedestroy(
                $background
            );

            if (
                empty($jpegData)
            ) {
                return null;
            }

            return
                'data:image/jpeg;base64,' .
                base64_encode(
                    $jpegData
                );

        } catch (\Throwable $e) {

            Log::warning(
                'Quotation image processing error',
                [
                    'error' =>
                        $e->getMessage(),
                ]
            );

            return null;
        }
    }
}