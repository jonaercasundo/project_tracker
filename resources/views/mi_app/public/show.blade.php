<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->item_name }} — Product Details</title>

    <style>
        :root {
            --pd-primary: #2C6E8C;
            --pd-primary-soft: #E4EEF5;
            --pd-ink: #1a1a1a;
            --pd-ink-faint: #888;
            --pd-line: #e5e5e5;
            --pd-bg: #f7f8f9;
            --pd-danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI',
                Roboto, sans-serif;
            background: var(--pd-bg);
            color: var(--pd-ink);
        }

        .pd-wrap {
            max-width: 480px;
            margin: 0 auto;
            padding: 1.25rem 1rem 3rem;
        }

        .pd-card {
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .pd-image-wrap {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--pd-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .pd-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .pd-image-placeholder {
            color: var(--pd-ink-faint);
            font-size: 0.8rem;
        }

        .pd-image-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 6px 16px -6px rgba(0,0,0,0.3);
            color: var(--pd-ink);
        }

        .pd-image-nav.prev {
            left: 0.6rem;
        }

        .pd-image-nav.next {
            right: 0.6rem;
        }

        .pd-image-nav svg {
            width: 1.1rem;
            height: 1.1rem;
        }

        .pd-image-counter {
            position: absolute;
            bottom: 0.6rem;
            right: 0.6rem;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
        }

        .pd-body {
            padding: 1.25rem;
        }

        .pd-ref-chip {
            display: inline-flex;
            font-family: 'SF Mono', Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--pd-primary);
            background: var(--pd-primary-soft);
            padding: 0.3rem 0.6rem;
            border-radius: 7px;
            margin-bottom: 0.6rem;
        }

        .pd-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 0.4rem;
        }

        .pd-taxo {
            font-size: 0.8rem;
            color: var(--pd-ink-faint);
            margin: 0 0 0.75rem;
        }

        .pd-description {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--pd-ink);
            margin: 0 0 0.9rem;
        }

        .pd-price {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .pd-price .unit {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--pd-ink-faint);
        }

        .pd-no-price {
            font-size: 0.85rem;
            color: var(--pd-ink-faint);
            font-style: italic;
        }

        .pd-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--pd-line);
        }

        .pd-spec-item {
            font-size: 0.78rem;
        }

        .pd-spec-label {
            color: var(--pd-ink-faint);
            display: block;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.1rem;
        }

        .pd-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--pd-ink-faint);
            margin: 0 0 0.75rem;
        }

        .pd-field {
            margin-bottom: 0.9rem;
        }

        .pd-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .pd-field input {
            width: 100%;
            padding: 0.65rem 0.8rem;
            font-size: 0.9rem;
            border: 1px solid var(--pd-line);
            border-radius: 8px;
            font-family: inherit;
        }

        .pd-field input:focus {
            outline: none;
            border-color: var(--pd-primary);
        }

        /* ================================
           QUOTATION CART
        ================================= */

        .pd-cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .pd-cart-count {
            background: var(--pd-primary-soft);
            color: var(--pd-primary);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
        }

        .pd-cart-empty {
            text-align: center;
            padding: 1rem 0;
            color: var(--pd-ink-faint);
            font-size: 0.8rem;
        }

        .pd-cart-item {
            display: flex;
            gap: 0.7rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--pd-line);
        }

        .pd-cart-item:last-child {
            border-bottom: none;
        }

        .pd-cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .pd-cart-item-name {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .pd-cart-item-ref {
            font-size: 0.68rem;
            color: var(--pd-ink-faint);
            font-family: monospace;
            margin-bottom: 0.3rem;
        }

        .pd-cart-item-price {
            font-size: 0.72rem;
            color: var(--pd-ink-faint);
        }

        .pd-cart-item-controls {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.4rem;
        }

        .pd-qty-btn {
            width: 25px;
            height: 25px;
            border: 1px solid var(--pd-line);
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
        }

        .pd-qty-value {
            width: 30px;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .pd-remove {
            border: none;
            background: transparent;
            color: var(--pd-danger);
            font-size: 0.68rem;
            cursor: pointer;
            margin-left: 0.4rem;
        }

        .pd-cart-item-total {
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
            text-align: right;
        }

        .pd-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.9rem;
            margin-top: 0.4rem;
            border-top: 1px dashed var(--pd-line);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .pd-total-row span.amt {
            font-size: 1.1rem;
        }

        .pd-btn-row {
            display: flex;
            gap: 0.6rem;
            margin-top: 1.1rem;
        }

        .pd-btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.75rem 1rem;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .pd-btn svg {
            width: 1rem;
            height: 1rem;
        }

        .pd-btn.primary {
            background: var(--pd-primary);
            color: #fff;
        }

        .pd-btn.primary:hover {
            opacity: 0.92;
        }

        .pd-btn.secondary {
            background: var(--pd-bg);
            color: var(--pd-ink);
            border: 1px solid var(--pd-line);
        }

        .pd-btn.secondary:hover {
            background: var(--pd-line);
        }

        .pd-btn.full {
            width: 100%;
            flex: none;
        }

        .pd-btn.added {
            background: #166534;
        }

        .pd-note {
            font-size: 0.72rem;
            color: var(--pd-ink-faint);
            text-align: center;
            margin-top: 0.75rem;
        }

        .pd-footer {
            text-align: center;
            font-size: 0.72rem;
            color: var(--pd-ink-faint);
            margin-top: 1.5rem;
        }

        #pdPrintSheet {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #pdPrintSheet,
            #pdPrintSheet * {
                visibility: visible;
            }

            #pdPrintSheet {
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

@php

    $convertImageUrl = function ($url) {

        if (empty($url)) {
            return null;
        }

        if (preg_match('/drive\.google\.com\/uc\?.*id=([^&]+)/', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }

        if (preg_match('#drive\.google\.com/file/d/([^/]+)#', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }

        if (preg_match('/drive\.google\.com\/open\?id=([^&]+)/', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }

        return trim($url);
    };

    $pdImages = $product->images
        ->map(function ($image) use ($convertImageUrl) {

            return [
                'url' => $image->image_type === 'upload'
                    ? asset('storage/' . $image->image_path)
                    : $convertImageUrl($image->image_url),
            ];

        })
        ->filter(fn ($img) => !empty($img['url']))
        ->values();

    $pdRef = $product->sku ?: ('PID-' . $product->product_id);

@endphp


<div class="pd-wrap">

    {{-- =========================================================
         PRODUCT DETAILS
    ========================================================== --}}

    <div class="pd-card">

        <div class="pd-image-wrap">

            @if($pdImages->count())

                <img
                    id="pdGalleryImg"
                    src="{{ $pdImages[0]['url'] }}"
                    alt="{{ $product->item_name }}"
                >

                @if($pdImages->count() > 1)

                    <button
                        type="button"
                        class="pd-image-nav prev"
                        onclick="pdPrevImage()"
                        aria-label="Previous image"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="pd-image-nav next"
                        onclick="pdNextImage()"
                        aria-label="Next image"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <span
                        class="pd-image-counter"
                        id="pdGalleryCounter"
                    >
                        1 / {{ $pdImages->count() }}
                    </span>

                @endif

            @else

                <span class="pd-image-placeholder">
                    No image available
                </span>

            @endif

        </div>


        <div class="pd-body">

            <span class="pd-ref-chip">
                {{ $pdRef }}
            </span>

            <h1 class="pd-title">
                {{ $product->item_name }}
            </h1>

            <p class="pd-taxo">
                {{
                    collect([
                        $product->category->name ?? null,
                        $product->subCategory->name ?? null,
                        $product->collection->name ?? null
                    ])
                    ->filter()
                    ->implode(' · ')
                    ?: 'Uncategorized'
                }}
            </p>

            @if($product->description)

                <p class="pd-description">
                    {{ $product->description }}
                </p>

            @endif


            @if(!is_null($product->price ?? null))

                <div class="pd-price">
                    ${{ number_format($product->price, 2) }}
                    <span class="unit">/ unit</span>
                </div>

            @else

                <div class="pd-no-price">
                    Price available upon request
                </div>

            @endif


            @if(
                $product->product_height ||
                $product->product_width ||
                $product->product_length ||
                $product->product_depth
            )

                <div class="pd-specs">

                    @if($product->product_height)
                        <div class="pd-spec-item">
                            <span class="pd-spec-label">Height</span>
                            {{ $product->product_height }} cm
                        </div>
                    @endif

                    @if($product->product_width)
                        <div class="pd-spec-item">
                            <span class="pd-spec-label">Width</span>
                            {{ $product->product_width }} cm
                        </div>
                    @endif

                    @if($product->product_length)
                        <div class="pd-spec-item">
                            <span class="pd-spec-label">Length</span>
                            {{ $product->product_length }} cm
                        </div>
                    @endif

                    @if($product->product_depth)
                        <div class="pd-spec-item">
                            <span class="pd-spec-label">Depth</span>
                            {{ $product->product_depth }} cm
                        </div>
                    @endif

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
         ADD CURRENT PRODUCT
    ========================================================== --}}

    @if(!is_null($product->price ?? null))

        <div class="pd-card">

            <div class="pd-body">

                <p class="pd-section-title">
                    Add Product
                </p>

                <div class="pd-field">

                    <label for="pdCurrentQuantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="pdCurrentQuantity"
                        value="1"
                        min="1"
                        inputmode="numeric"
                    >

                </div>


                <button
                    type="button"
                    id="pdAddProductBtn"
                    class="pd-btn primary full"
                    onclick="pdAddCurrentProduct()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 4v16m8-8H4"/>
                    </svg>

                    Add to Quotation
                </button>


                <button
                    type="button"
                    class="pd-btn secondary full"
                    style="margin-top:0.6rem;"
                    onclick="pdScanAnotherProduct()"
                >
                    Scan Another Product
                </button>

            </div>

        </div>

    @endif


    {{-- =========================================================
         QUOTATION CART
    ========================================================== --}}

    <div class="pd-card">

        <div class="pd-body">

            <div class="pd-cart-header">

                <p class="pd-section-title" style="margin:0;">
                    Quotation
                </p>

                <span
                    class="pd-cart-count"
                    id="pdCartCount"
                >
                    0 Products
                </span>

            </div>


            <div id="pdCartItems">

                <div class="pd-cart-empty">
                    No products added yet.
                </div>

            </div>


            <div class="pd-total-row">

                <span>
                    Estimated Total
                </span>

                <span
                    class="amt"
                    id="pdCartTotal"
                >
                    $0.00
                </span>

            </div>


            <div
                class="pd-field"
                style="margin-top:1rem;"
            >

                <label for="pdCustomerName">
                    Customer Name
                </label>

                <input
                    type="text"
                    id="pdCustomerName"
                    placeholder="e.g. Juan Dela Cruz"
                >

            </div>


            <div class="pd-btn-row">

                <button
                    type="button"
                    class="pd-btn secondary"
                    onclick="pdPrintQuote()"
                >
                    Print
                </button>

                <button
                    type="button"
                    class="pd-btn primary"
                    onclick="pdDownloadQuotation()"
                >
                    Download PDF
                </button>

            </div>


            <button
                type="button"
                class="pd-btn secondary full"
                style="margin-top:0.6rem;"
                onclick="pdClearQuotation()"
            >
                Clear Quotation
            </button>


            <p class="pd-note">
                Quotation is valid for 30 days from the date issued.
            </p>

        </div>

    </div>


    <p class="pd-footer">
        Scanned from product tag &middot; {{ $pdRef }}
    </p>

</div>


{{-- =========================================================
     HIDDEN PRINT SHEET
========================================================== --}}

<div id="pdPrintSheet">

    <h2 style="margin:0 0 4px;">
        Product Quotation
    </h2>

    <p
        style="
            font-family:monospace;
            color:#666;
            margin:0 0 16px;
        "
    >
        Customer:
        <span id="pdPrintCustomer"></span>
    </p>


    <table
        style="
            width:100%;
            border-collapse:collapse;
            font-size:13px;
        "
    >

        <thead>

            <tr style="border-bottom:2px solid #000;">

                <th style="padding:8px 0;text-align:left;">
                    Product
                </th>

                <th style="padding:8px 0;text-align:center;">
                    Qty
                </th>

                <th style="padding:8px 0;text-align:right;">
                    Unit Price
                </th>

                <th style="padding:8px 0;text-align:right;">
                    Total
                </th>

            </tr>

        </thead>

        <tbody id="pdPrintItems"></tbody>

        <tfoot>

            <tr style="border-top:2px solid #000;">

                <td
                    colspan="3"
                    style="
                        padding:10px 0;
                        font-weight:bold;
                    "
                >
                    TOTAL
                </td>

                <td
                    id="pdPrintTotal"
                    style="
                        padding:10px 0;
                        text-align:right;
                        font-weight:bold;
                    "
                ></td>

            </tr>

        </tfoot>

    </table>

</div>


@if($pdImages->count() > 1)

<script>

    const pdGalleryImages =
        @json($pdImages->pluck('url'));

    let pdCurrentImage = 0;

    const pdGalleryImg =
        document.getElementById('pdGalleryImg');

    const pdGalleryCounter =
        document.getElementById('pdGalleryCounter');


    function pdUpdateGallery()
    {
        pdGalleryImg.src =
            pdGalleryImages[pdCurrentImage];

        pdGalleryCounter.textContent =
            (pdCurrentImage + 1)
            + ' / '
            + pdGalleryImages.length;
    }


    function pdNextImage()
    {
        pdCurrentImage =
            (pdCurrentImage + 1)
            % pdGalleryImages.length;

        pdUpdateGallery();
    }


    function pdPrevImage()
    {
        pdCurrentImage =
            (pdCurrentImage - 1 + pdGalleryImages.length)
            % pdGalleryImages.length;

        pdUpdateGallery();
    }

</script>

@endif


@if(!is_null($product->price ?? null))

<script>
    /*
    |--------------------------------------------------------------------------
    | Laravel Data
    |--------------------------------------------------------------------------
    */

    const PD_CONFIG = @json([
        'product' => [
            'id'       => (int) $product->product_id,
            'sku'      => $pdRef,
            'name'     => $product->item_name,
            'price'    => (float) $product->price,
        ],

        'downloadUrl' => route(
            'mi_app.quotation.download',
            $product->product_id
        ),

        'printUrl' => route(
            'mi_app.quotation.print',
            $product->product_id
        ),

        'csrfToken' => csrf_token(),

        'cartKey' => 'mi_product_quotation_cart',

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | Change this to your actual QR scanner route.
        |--------------------------------------------------------------------------
        */

        'scannerUrl' => url('/mi-app/scan'),
    ]);


    /*
    |--------------------------------------------------------------------------
    | CURRENT PRODUCT
    |--------------------------------------------------------------------------
    */

    const PD_CURRENT_PRODUCT =
        PD_CONFIG.product;


    /*
    |--------------------------------------------------------------------------
    | ROUTES
    |--------------------------------------------------------------------------
    */

    const PD_DOWNLOAD_URL =
        PD_CONFIG.downloadUrl;

    const PD_PRINT_URL =
        PD_CONFIG.printUrl;


    /*
    |--------------------------------------------------------------------------
    | LOCAL STORAGE KEY
    |--------------------------------------------------------------------------
    */

    const PD_CART_KEY =
        PD_CONFIG.cartKey;


    /*
    |--------------------------------------------------------------------------
    | GET CART
    |--------------------------------------------------------------------------
    */

    function pdGetCart()
    {
        try {

            const stored =
                localStorage.getItem(PD_CART_KEY);

            if (!stored) {
                return [];
            }

            const cart =
                JSON.parse(stored);

            return Array.isArray(cart)
                ? cart
                : [];

        } catch (error) {

            console.error(
                'Unable to read quotation cart:',
                error
            );

            return [];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE CART
    |--------------------------------------------------------------------------
    */

    function pdSaveCart(cart)
    {
        localStorage.setItem(
            PD_CART_KEY,
            JSON.stringify(cart)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT CURRENCY
    |--------------------------------------------------------------------------
    */

    function pdFormatCurrency(amount)
    {
        return '$' + Number(amount).toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADD CURRENT PRODUCT
    |--------------------------------------------------------------------------
    */

    function pdAddCurrentProduct()
    {
        const quantityInput =
            document.getElementById(
                'pdCurrentQuantity'
            );

        const quantity =
            Math.max(
                1,
                parseInt(
                    quantityInput.value || '1',
                    10
                )
            );


        let cart =
            pdGetCart();


        const existingIndex =
            cart.findIndex(
                item =>
                    Number(item.id)
                    === Number(PD_CURRENT_PRODUCT.id)
            );


        if (existingIndex >= 0) {

            cart[existingIndex].quantity =
                Number(
                    cart[existingIndex].quantity
                ) + quantity;

        } else {

            cart.push({

                id:
                    Number(
                        PD_CURRENT_PRODUCT.id
                    ),

                sku:
                    PD_CURRENT_PRODUCT.sku,

                name:
                    PD_CURRENT_PRODUCT.name,

                price:
                    Number(
                        PD_CURRENT_PRODUCT.price
                    ),

                quantity:
                    quantity

            });

        }


        pdSaveCart(cart);

        pdRenderCart();


        const button =
            document.getElementById(
                'pdAddProductBtn'
            );


        if (button) {

            const originalText =
                button.innerHTML;


            button.innerHTML =
                '✓ Added to Quotation';

            button.classList.add('added');


            setTimeout(
                function () {

                    button.innerHTML =
                        originalText;

                    button.classList.remove(
                        'added'
                    );

                },
                1200
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | RENDER CART
    |--------------------------------------------------------------------------
    */

    function pdRenderCart()
    {
        const cart =
            pdGetCart();


        const container =
            document.getElementById(
                'pdCartItems'
            );


        const count =
            document.getElementById(
                'pdCartCount'
            );


        const totalElement =
            document.getElementById(
                'pdCartTotal'
            );


        if (!container || !count || !totalElement) {
            return;
        }


        if (!cart.length) {

            container.innerHTML = `
                <div class="pd-cart-empty">
                    No products added yet.
                </div>
            `;

            count.textContent =
                '0 Products';

            totalElement.textContent =
                '$0.00';

            return;
        }


        let total = 0;

        let html = '';


        cart.forEach(
            function (item, index) {

                const itemTotal =
                    Number(item.price)
                    * Number(item.quantity);


                total += itemTotal;


                html += `

                    <div class="pd-cart-item">

                        <div class="pd-cart-item-info">

                            <div class="pd-cart-item-name">
                                ${pdEscapeHtml(item.name)}
                            </div>

                            <div class="pd-cart-item-ref">
                                ${pdEscapeHtml(item.sku || '')}
                            </div>

                            <div class="pd-cart-item-price">
                                ${pdFormatCurrency(item.price)}
                                / unit
                            </div>

                            <div class="pd-cart-item-controls">

                                <button
                                    type="button"
                                    class="pd-qty-btn"
                                    onclick="pdChangeQuantity(${index}, -1)"
                                >
                                    −
                                </button>

                                <span class="pd-qty-value">
                                    ${item.quantity}
                                </span>

                                <button
                                    type="button"
                                    class="pd-qty-btn"
                                    onclick="pdChangeQuantity(${index}, 1)"
                                >
                                    +
                                </button>

                                <button
                                    type="button"
                                    class="pd-remove"
                                    onclick="pdRemoveProduct(${index})"
                                >
                                    Remove
                                </button>

                            </div>

                        </div>

                        <div class="pd-cart-item-total">
                            ${pdFormatCurrency(itemTotal)}
                        </div>

                    </div>

                `;

            }
        );


        container.innerHTML =
            html;


        count.textContent =
            cart.length
            + (
                cart.length === 1
                    ? ' Product'
                    : ' Products'
            );


        totalElement.textContent =
            pdFormatCurrency(total);
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE QUANTITY
    |--------------------------------------------------------------------------
    */

    function pdChangeQuantity(index, change)
    {
        let cart =
            pdGetCart();


        if (!cart[index]) {
            return;
        }


        cart[index].quantity =
            Number(cart[index].quantity)
            + Number(change);


        if (cart[index].quantity <= 0) {

            cart.splice(index, 1);

        }


        pdSaveCart(cart);

        pdRenderCart();
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE PRODUCT
    |--------------------------------------------------------------------------
    */

    function pdRemoveProduct(index)
    {
        let cart =
            pdGetCart();


        if (!cart[index]) {
            return;
        }


        cart.splice(index, 1);


        pdSaveCart(cart);

        pdRenderCart();
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR QUOTATION
    |--------------------------------------------------------------------------
    */

    function pdClearQuotation()
    {
        if (!confirm(
            'Clear all products from this quotation?'
        )) {
            return;
        }


        localStorage.removeItem(
            PD_CART_KEY
        );


        pdRenderCart();
    }


    /*
    |--------------------------------------------------------------------------
    | SCAN ANOTHER PRODUCT
    |--------------------------------------------------------------------------
    */

    function pdScanAnotherProduct()
    {
        window.location.href =
            PD_CONFIG.scannerUrl;
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER NAME
    |--------------------------------------------------------------------------
    */

    function pdGetCustomerName()
    {
        const input =
            document.getElementById(
                'pdCustomerName'
            );


        if (!input) {
            return '';
        }


        return input.value.trim();
    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function pdEscapeHtml(value)
    {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE QUOTATION FORM
    |--------------------------------------------------------------------------
    */

    function pdCreateQuotationForm(action)
    {
        const cart =
            pdGetCart();


        if (!cart.length) {

            alert(
                'Please add at least one product to the quotation.'
            );

            return null;
        }


        const customerName =
            pdGetCustomerName();


        if (!customerName) {

            alert(
                'Please enter the customer name.'
            );

            const customerInput =
                document.getElementById(
                    'pdCustomerName'
                );


            if (customerInput) {
                customerInput.focus();
            }


            return null;
        }


        const form =
            document.createElement('form');


        form.method =
            'POST';


        form.action =
            action;


        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

        const csrf =
            document.createElement('input');


        csrf.type =
            'hidden';

        csrf.name =
            '_token';

        csrf.value =
            PD_CONFIG.csrfToken;


        form.appendChild(csrf);


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        const customer =
            document.createElement('input');


        customer.type =
            'hidden';

        customer.name =
            'customer_name';

        customer.value =
            customerName;


        form.appendChild(customer);


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        const products =
            document.createElement('input');


        products.type =
            'hidden';

        products.name =
            'products';


        /*
        |--------------------------------------------------------------------------
        | Only send ID + quantity.
        |
        | Do NOT trust price/name from localStorage.
        |--------------------------------------------------------------------------
        */

        const cleanCart =
            cart.map(
                function (item) {

                    return {

                        id:
                            Number(item.id),

                        quantity:
                            Math.max(
                                1,
                                Number(item.quantity)
                            )

                    };

                }
            );


        products.value =
            JSON.stringify(cleanCart);


        form.appendChild(products);


        return form;
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD QUOTATION
    |--------------------------------------------------------------------------
    */

    function pdDownloadQuotation()
    {
        const form =
            pdCreateQuotationForm(
                PD_DOWNLOAD_URL
            );


        if (!form) {
            return;
        }


        document.body.appendChild(form);

        form.submit();

        form.remove();
    }


    /*
    |--------------------------------------------------------------------------
    | PRINT QUOTATION
    |--------------------------------------------------------------------------
    */

    function pdPrintQuote()
    {
        const form =
            pdCreateQuotationForm(
                PD_PRINT_URL
            );


        if (!form) {
            return;
        }


        form.target =
            '_blank';


        document.body.appendChild(form);

        form.submit();

        form.remove();
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            pdRenderCart();

        }
    );

</script>

@endif
</body>
</html>
