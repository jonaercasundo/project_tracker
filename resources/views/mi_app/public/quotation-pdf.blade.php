<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <title>Quotation {{ $quote_number }}</title>

    <style>

        @page {
            margin: 35px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .company-info {
            font-size: 9px;
            color: #777;
            line-height: 1.5;
            margin-top: 4px;
        }

        .quote-label {
            font-size: 22px;
            font-weight: bold;
            color: #2C6E8C;
            text-align: right;
        }

        .quote-subtitle {
            font-size: 9px;
            color: #777;
            text-align: right;
            margin-top: 3px;
        }

        /*
        |--------------------------------------------------------------------------
        | META
        |--------------------------------------------------------------------------
        */

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 10px;
        }

        .meta-label {
            color: #777;
            width: 90px;
        }

        .meta-value {
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | SECTION
        |--------------------------------------------------------------------------
        */

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #2C6E8C;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | QUOTATION SUMMARY
        |--------------------------------------------------------------------------
        */

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #F4F6F7;
            text-align: left;
            padding: 8px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #555;
            border-bottom: 2px solid #ddd;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table .num {
            text-align: right;
        }

        .sku-chip {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #2C6E8C;
        }

        .item-name {
            font-weight: bold;
            color: #222;
        }

        .item-description {
            color: #777;
            font-size: 8px;
            margin-top: 3px;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .totals-table td {
            padding: 6px 10px;
            font-size: 11px;
        }

        .totals-table .label {
            text-align: right;
            color: #555;
        }

        .totals-table .value {
            text-align: right;
            width: 120px;
        }

        .totals-table .grand-total .label,
        .totals-table .grand-total .value {
            font-size: 15px;
            font-weight: bold;
            color: #1a1a1a;
            border-top: 2px solid #1a1a1a;
            padding-top: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | INDIVIDUAL PRODUCT INFORMATION
        |--------------------------------------------------------------------------
        */

        .product-block {
            page-break-inside: avoid;
            margin-bottom: 22px;
        }

        .product-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .product-info-table td {
            vertical-align: top;
        }

        .product-image-cell {
            width: 145px;
            padding-right: 15px;
        }

        .product-image {
            width: 125px;
            height: 125px;
            object-fit: contain;
            border: 1px solid #ddd;
            padding: 5px;
        }

        .no-image {
            width: 125px;
            height: 125px;
            border: 1px solid #ddd;
            background: #F7F8F8;
            text-align: center;
            vertical-align: middle;
            color: #999;
            font-size: 9px;
        }

        .product-name {
            font-size: 15px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .product-sku {
            font-family: 'Courier New', monospace;
            color: #2C6E8C;
            font-size: 9px;
            margin-bottom: 7px;
        }

        .description {
            color: #555;
            line-height: 1.5;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT DETAILS
        |--------------------------------------------------------------------------
        */

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .details-table td {
            width: 25%;
            padding: 6px 7px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .detail-label {
            display: block;
            color: #888;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 9px;
            font-weight: bold;
            color: #222;
        }

        /*
        |--------------------------------------------------------------------------
        | MATERIALS / COLORS
        |--------------------------------------------------------------------------
        */

        .tags-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .tags-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .tag-label {
            color: #777;
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .tag {
            display: inline-block;
            background: #F4F6F7;
            border: 1px solid #ddd;
            color: #444;
            padding: 3px 6px;
            margin-right: 3px;
            margin-bottom: 3px;
            font-size: 7px;
        }

        /*
        |--------------------------------------------------------------------------
        | DIMENSIONS
        |--------------------------------------------------------------------------
        */

        .dimensions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .dimensions-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .dimension-box {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .dimension-title {
            font-size: 9px;
            font-weight: bold;
            color: #2C6E8C;
            margin-bottom: 5px;
        }

        .dimension-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dimension-table td {
            width: auto;
            padding: 3px 0;
            border-bottom: 1px solid #eee;
            font-size: 8px;
        }

        .dimension-table td:last-child {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT SEPARATOR
        |--------------------------------------------------------------------------
        */

        .product-separator {
            border-top: 1px dashed #ccc;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer-note {
            margin-top: 35px;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 12px;
            line-height: 1.5;
        }

        .validity {
            color: #555;
            font-weight: bold;
        }

    </style>

</head>

<body>


{{-- ========================================================================= --}}
{{-- HEADER --}}
{{-- ========================================================================= --}}

<table class="header-table">

    <tr>

        <td>

            <div class="company-name">
                METRO
            </div>

            <div class="company-info">
                Product &amp; Furniture Solutions<br>
                Product Quotation
            </div>

        </td>

        <td>

            <div class="quote-label">
                QUOTATION
            </div>

            <div class="quote-subtitle">
                Official Product Quotation
            </div>

        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- QUOTATION META --}}
{{-- ========================================================================= --}}

<table class="meta-table">

    <tr>

        <td class="meta-label">
            Quote No.
        </td>

        <td class="meta-value">
            {{ $quote_number }}
        </td>

        <td class="meta-label">
            Date Issued
        </td>

        <td class="meta-value">
            {{ $issued_at->format('F j, Y') }}
        </td>

    </tr>

    <tr>

        <td class="meta-label">
            Customer
        </td>

        <td colspan="3" class="meta-value">
            {{ $customer_name }}
        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- QUOTATION ITEMS --}}
{{-- ========================================================================= --}}

<div class="section-title">
    Quotation Details
</div>


<table class="items-table">

    <thead>

        <tr>

            <th style="width:35%;">
                Item
            </th>

            <th style="width:18%;">
                SKU
            </th>

            <th class="num" style="width:10%;">
                Qty
            </th>

            <th class="num" style="width:18%;">
                Unit Price
            </th>

            <th class="num" style="width:19%;">
                Subtotal
            </th>

        </tr>

    </thead>


    <tbody>

        @foreach($quotationItems as $item)

            @php

                $itemProduct =
                    $item->product
                    ?? $item;

                $itemQuantity =
                    (int) (
                        $item->quantity
                        ?? 1
                    );

                $itemUnitPrice =
                    (float) (
                        $item->unit_price
                        ?? $itemProduct->price
                        ?? 0
                    );

                $itemSubtotal =
                    $itemQuantity
                    * $itemUnitPrice;

            @endphp


            <tr>

                <td>

                    <div class="item-name">
                        {{ $itemProduct->item_name }}
                    </div>

                    @if(!empty($itemProduct->description))

                        <div class="item-description">
                            {{ \Illuminate\Support\Str::limit(
                                $itemProduct->description,
                                100
                            ) }}
                        </div>

                    @endif

                </td>


                <td class="sku-chip">
                    {{ $itemProduct->sku ?? '—' }}
                </td>


                <td class="num">
                    {{ number_format($itemQuantity) }}
                </td>


                <td class="num">
                    ${{ number_format(
                        $itemUnitPrice,
                        2
                    ) }}
                </td>


                <td class="num">
                    ${{ number_format(
                        $itemSubtotal,
                        2
                    ) }}
                </td>

            </tr>

        @endforeach

    </tbody>

</table>


{{-- ========================================================================= --}}
{{-- TOTALS --}}
{{-- ========================================================================= --}}

<table class="totals-table">

    <tr>

        <td class="label">
            Subtotal
        </td>

        <td class="value">
            ${{ number_format(
                (float) $total,
                2
            ) }}
        </td>

    </tr>


    <tr class="grand-total">

        <td class="label">
            TOTAL
        </td>

        <td class="value">
            ${{ number_format(
                (float) $total,
                2
            ) }}
        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- PRODUCT INFORMATION --}}
{{-- ========================================================================= --}}

<div style="margin-top:25px;">

    <div class="section-title">
        Product Information
    </div>


    @foreach($quotationItems as $index => $item)

        @php

            $itemProduct =
                $item->product
                ?? $item;

            $itemImage = null;

            /*
            |--------------------------------------------------------------------------
            | Resolve product image
            |--------------------------------------------------------------------------
            */

            if (
                isset($itemProduct->images)
                && $itemProduct->images->count()
            ) {

                $firstImage =
                    $itemProduct->images->first();

                if (
                    $firstImage->image_type
                    === 'upload'
                    && !empty($firstImage->image_path)
                ) {

                    $itemImage =
                        public_path(
                            'storage/'
                            . $firstImage->image_path
                        );

                } elseif (
                    !empty($firstImage->image_url)
                ) {

                    $itemImage =
                        $firstImage->image_url;

                }

            }

        @endphp


        <div class="product-block">

            <table class="product-info-table">

                <tr>

                    {{-- PRODUCT IMAGE --}}

                    <td class="product-image-cell">

                        @if(
                            !empty($itemImage)
                            && filter_var(
                                $itemImage,
                                FILTER_VALIDATE_URL
                            )
                        )

                            <img
                                src="{{ $itemImage }}"
                                class="product-image"
                                alt="Product Image"
                            >

                        @elseif(
                            !empty($itemImage)
                            && file_exists($itemImage)
                        )

                            <img
                                src="{{ $itemImage }}"
                                class="product-image"
                                alt="Product Image"
                            >

                        @else

                            <table class="no-image">

                                <tr>

                                    <td>
                                        No Image Available
                                    </td>

                                </tr>

                            </table>

                        @endif

                    </td>


                    {{-- PRODUCT DETAILS --}}

                    <td>

                        <div class="product-name">
                            {{ $itemProduct->item_name }}
                        </div>


                        <div class="product-sku">
                            SKU:
                            {{ $itemProduct->sku ?? '—' }}
                        </div>


                        @if(
                            !empty(
                                $itemProduct->description
                            )
                        )

                            <div class="description">
                                {{ $itemProduct->description }}
                            </div>

                        @else

                            <div class="description">
                                Product quotation for
                                {{ $itemProduct->item_name }}.
                            </div>

                        @endif

                    </td>

                </tr>

            </table>


            {{-- PRODUCT CLASSIFICATION --}}

            <table class="details-table">

                <tr>

                    <td>

                        <span class="detail-label">
                            Category
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->category->name ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Sub Category
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->subCategory->name ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Product Type
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->productType->name ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Collection
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->collection->name ?? '—' }}
                        </span>

                    </td>

                </tr>


                <tr>

                    <td>

                        <span class="detail-label">
                            Type of Sample
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->type_of_sample ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Classification
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->classification ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Designed By
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->designed_by ?? '—' }}
                        </span>

                    </td>


                    <td>

                        <span class="detail-label">
                            Type
                        </span>

                        <span class="detail-value">
                            {{ $itemProduct->type ?? '—' }}
                        </span>

                    </td>

                </tr>

            </table>


            {{-- MATERIALS / COLORS --}}

            <table class="tags-table">

                <tr>

                    <td>

                        <div class="tag-label">
                            Materials
                        </div>

                        @php
                            $materials =
                                $itemProduct->materials
                                ?? [];
                        @endphp


                        @forelse(
                            $materials
                            as $material
                        )

                            <span class="tag">
                                {{ $material }}
                            </span>

                        @empty

                            <span
                                style="
                                    font-size:8px;
                                    color:#999;
                                "
                            >
                                No materials specified
                            </span>

                        @endforelse

                    </td>


                    <td>

                        <div class="tag-label">
                            Colors
                        </div>

                        @php
                            $colors =
                                $itemProduct->color
                                ?? [];
                        @endphp


                        @forelse(
                            $colors
                            as $color
                        )

                            <span class="tag">
                                {{ $color }}
                            </span>

                        @empty

                            <span
                                style="
                                    font-size:8px;
                                    color:#999;
                                "
                            >
                                No colors specified
                            </span>

                        @endforelse

                    </td>

                </tr>

            </table>


            {{-- DIMENSIONS --}}

            <table class="dimensions-table">

                <tr>

                    {{-- PRODUCT DIMENSIONS --}}

                    <td>

                        <div class="dimension-box">

                            <div class="dimension-title">
                                Product Dimensions
                            </div>


                            <table class="dimension-table">

                                <tr>
                                    <td>Height</td>

                                    <td>
                                        {{ $itemProduct->product_height ?? '—' }}

                                        {{ $itemProduct->product_height !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Width</td>

                                    <td>
                                        {{ $itemProduct->product_width ?? '—' }}

                                        {{ $itemProduct->product_width !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Length</td>

                                    <td>
                                        {{ $itemProduct->product_length ?? '—' }}

                                        {{ $itemProduct->product_length !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Depth</td>

                                    <td>
                                        {{ $itemProduct->product_depth ?? '—' }}

                                        {{ $itemProduct->product_depth !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>

                            </table>

                        </div>

                    </td>


                    {{-- CARTON DIMENSIONS --}}

                    <td>

                        <div class="dimension-box">

                            <div class="dimension-title">
                                Carton Dimensions
                            </div>


                            <table class="dimension-table">

                                <tr>
                                    <td>Height</td>

                                    <td>
                                        {{ $itemProduct->carton_height ?? '—' }}

                                        {{ $itemProduct->carton_height !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Width</td>

                                    <td>
                                        {{ $itemProduct->carton_width ?? '—' }}

                                        {{ $itemProduct->carton_width !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Length</td>

                                    <td>
                                        {{ $itemProduct->carton_length ?? '—' }}

                                        {{ $itemProduct->carton_length !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td>Depth</td>

                                    <td>
                                        {{ $itemProduct->carton_depth ?? '—' }}

                                        {{ $itemProduct->carton_depth !== null
                                            ? 'cm'
                                            : ''
                                        }}
                                    </td>
                                </tr>

                            </table>

                        </div>

                    </td>

                </tr>

            </table>


            {{-- SEPARATOR --}}

            @if(!$loop->last)

                <div class="product-separator"></div>

            @endif

        </div>

    @endforeach

</div>


{{-- ========================================================================= --}}
{{-- FOOTER --}}
{{-- ========================================================================= --}}

<div class="footer-note">

    <span class="validity">
        Quotation Validity:
    </span>

    This quotation is valid for 30 days from the date of issue.

    Prices are subject to change without prior notice.

    <br><br>

    Thank you for your interest in METRO products.

</div>


</body>
</html>