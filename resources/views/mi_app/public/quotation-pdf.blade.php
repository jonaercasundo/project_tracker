<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Quotation {{ $quote_number }}</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | DOMPDF-SAFE QUOTATION DESIGN
        |--------------------------------------------------------------------------
        */

        @page {
            margin: 35px 40px 45px 40px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header-table {
            width: 100%;
            margin-bottom: 22px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .company-subtitle {
            margin-top: 4px;
            font-size: 9px;
            color: #777;
        }

        .quote-label {
            font-size: 22px;
            font-weight: bold;
            color: #2C6E8C;
            text-align: right;
        }

        .quote-number {
            margin-top: 5px;
            font-size: 9px;
            color: #777;
            text-align: right;
        }

        /*
        |--------------------------------------------------------------------------
        | META INFORMATION
        |--------------------------------------------------------------------------
        */

        .meta-table {
            width: 100%;
            margin-bottom: 22px;
        }

        .meta-table td {
            padding: 5px 0;
            font-size: 10px;
            vertical-align: top;
        }

        .meta-label {
            color: #777;
            width: 75px;
            font-weight: bold;
        }

        .meta-value {
            color: #1a1a1a;
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
            border-bottom: 1px solid #DDE3E6;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT INFORMATION
        |--------------------------------------------------------------------------
        */

        .product-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .product-table td {
            vertical-align: top;
        }

        .product-image-cell {
            width: 175px;
            padding-right: 18px;
        }

        .product-image {
            width: 165px;
            height: 145px;
            object-fit: contain;
            border: 1px solid #E2E5E7;
            background: #F7F8F9;
        }

        .no-image {
            width: 165px;
            height: 145px;
            background: #F4F6F7;
            border: 1px solid #E2E5E7;
            text-align: center;
            vertical-align: middle;
            color: #999;
            font-size: 9px;
        }

        .product-info {
            padding-left: 4px;
        }

        .product-name {
            font-size: 17px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .sku {
            font-family: "Courier New", monospace;
            font-size: 9px;
            color: #2C6E8C;
            margin-bottom: 10px;
        }

        .description {
            font-size: 10px;
            line-height: 1.5;
            color: #555;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT DETAILS
        |--------------------------------------------------------------------------
        */

        .details-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .details-table td {
            width: 25%;
            padding: 7px 8px;
            border-bottom: 1px solid #EEEEEE;
            vertical-align: top;
        }

        .detail-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #888;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 10px;
            color: #222;
        }

        /*
        |--------------------------------------------------------------------------
        | MATERIALS / COLORS
        |--------------------------------------------------------------------------
        */

        .attribute-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .attribute-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .attribute-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #777;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .chip {
            display: inline-block;
            padding: 4px 7px;
            margin: 0 4px 4px 0;
            background: #F4F6F7;
            border: 1px solid #E1E5E7;
            font-size: 8px;
            color: #444;
        }

        /*
        |--------------------------------------------------------------------------
        | DIMENSIONS
        |--------------------------------------------------------------------------
        */

        .dimensions-table {
            width: 100%;
            margin-bottom: 22px;
        }

        .dimension-box {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .dimension-box:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .dimension-inner {
            width: 100%;
            border: 1px solid #E1E5E7;
            background: #FAFBFB;
        }

        .dimension-header {
            background: #F4F6F7;
            padding: 7px 9px;
            font-size: 9px;
            font-weight: bold;
            color: #555;
        }

        .dimension-inner td {
            padding: 6px 9px;
            border-bottom: 1px solid #EEEEEE;
            font-size: 9px;
        }

        .dimension-inner tr:last-child td {
            border-bottom: none;
        }

        .dimension-label {
            color: #777;
        }

        .dimension-value {
            text-align: right;
            font-family: "Courier New", monospace;
            font-weight: bold;
            color: #222;
        }

        /*
        |--------------------------------------------------------------------------
        | QUOTATION ITEMS
        |--------------------------------------------------------------------------
        */

        .items-table {
            width: 100%;
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
            border-bottom: 2px solid #D9DDE0;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #EEEEEE;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table .num {
            text-align: right;
        }

        .item-name {
            font-weight: bold;
            font-size: 10px;
        }

        .item-description {
            color: #777;
            font-size: 8px;
            margin-top: 3px;
        }

        .sku-chip {
            font-family: "Courier New", monospace;
            font-size: 9px;
            color: #2C6E8C;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        .totals-table {
            width: 100%;
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
        | TERMS
        |--------------------------------------------------------------------------
        */

        .terms-box {
            margin-top: 28px;
            padding: 12px;
            background: #F8F9FA;
            border: 1px solid #E5E7E9;
        }

        .terms-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 5px;
        }

        .terms-text {
            font-size: 9px;
            line-height: 1.5;
            color: #777;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer-note {
            margin-top: 25px;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #EEEEEE;
            padding-top: 10px;
            text-align: center;
        }

        .page-break {
            page-break-before: always;
        }

    </style>
</head>

<body>

{{-- ===================================================================== --}}
{{-- HEADER --}}
{{-- ===================================================================== --}}

<table class="header-table">
    <tr>

        <td>

            <div class="company-name">
                METROINC
            </div>

            <div class="company-subtitle">
                Furniture &amp; Interior Products
            </div>

        </td>

        <td>

            <div class="quote-label">
                QUOTATION
            </div>

            <div class="quote-number">
                {{ $quote_number }}
            </div>

        </td>

    </tr>
</table>


{{-- ===================================================================== --}}
{{-- QUOTATION META --}}
{{-- ===================================================================== --}}

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

        <td class="meta-value" colspan="3">
            {{ $customer_name }}
        </td>

    </tr>

    <tr>

        <td class="meta-label">
            Valid Until
        </td>

        <td class="meta-value" colspan="3">
            {{ $issued_at->copy()->addDays(30)->format('F j, Y') }}
        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- PRODUCT --}}
{{-- ===================================================================== --}}

<div class="section-title">
    Product Information
</div>

@php

    /*
    |--------------------------------------------------------------------------
    | Find the first usable image
    |--------------------------------------------------------------------------
    */

    $productImage = null;

    if ($product->relationLoaded('images')) {

        foreach ($product->images as $image) {

            if ($image->image_type === 'upload' && !empty($image->image_path)) {

                $productImage = public_path(
                    'storage/' . $image->image_path
                );

                if (file_exists($productImage)) {
                    break;
                }

                $productImage = null;
            }

            if (
                $image->image_type === 'url' &&
                !empty($image->image_url)
            ) {

                $productImage = $image->image_url;

                break;
            }
        }
    }

@endphp


<table class="product-table">

    <tr>

        <td class="product-image-cell">

            @if($productImage)

                @if(filter_var($productImage, FILTER_VALIDATE_URL))

                    <img
                        src="{{ $productImage }}"
                        class="product-image"
                    >

                @else

                    <img
                        src="{{ $productImage }}"
                        class="product-image"
                    >

                @endif

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


        <td class="product-info">

            <div class="product-name">
                {{ $product->item_name }}
            </div>

            <div class="sku">
                SKU: {{ $product->sku ?? '—' }}
            </div>

            @if(!empty($product->description))

                <div class="description">
                    {{ $product->description }}
                </div>

            @else

                <div class="description">
                    Product information and specifications are provided
                    based on the current product database record.
                </div>

            @endif

        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- PRODUCT DETAILS --}}
{{-- ===================================================================== --}}

<div class="section-title">
    Product Details
</div>

<table class="details-table">

    <tr>

        <td>
            <div class="detail-label">
                Category
            </div>

            <div class="detail-value">
                {{ optional($product->category)->name ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Sub Category
            </div>

            <div class="detail-value">
                {{ optional($product->subCategory)->name ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Product Type
            </div>

            <div class="detail-value">
                {{ optional($product->productType)->name ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Collection
            </div>

            <div class="detail-value">
                {{ optional($product->collection)->name ?? '—' }}
            </div>
        </td>

    </tr>


    <tr>

        <td>
            <div class="detail-label">
                Type of Sample
            </div>

            <div class="detail-value">
                {{ $product->type_of_sample ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Classification
            </div>

            <div class="detail-value">
                {{ $product->classification ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Designed By
            </div>

            <div class="detail-value">
                {{ $product->designed_by ?? '—' }}
            </div>
        </td>

        <td>
            <div class="detail-label">
                Product Type
            </div>

            <div class="detail-value">
                {{ $product->type ?? '—' }}
            </div>
        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- MATERIALS AND COLORS --}}
{{-- ===================================================================== --}}

<div class="section-title">
    Materials &amp; Colors
</div>

<table class="attribute-table">

    <tr>

        <td>

            <div class="attribute-title">
                Materials
            </div>

            @php
                $materials = $product->materials ?? [];
            @endphp

            @if(is_array($materials) && count($materials))

                @foreach($materials as $material)

                    <span class="chip">
                        {{ $material }}
                    </span>

                @endforeach

            @else

                <span class="chip">
                    Not specified
                </span>

            @endif

        </td>


        <td>

            <div class="attribute-title">
                Colors
            </div>

            @php
                $colors = $product->color ?? [];
            @endphp

            @if(is_array($colors) && count($colors))

                @foreach($colors as $color)

                    <span class="chip">
                        {{ $color }}
                    </span>

                @endforeach

            @else

                <span class="chip">
                    Not specified
                </span>

            @endif

        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- DIMENSIONS --}}
{{-- ===================================================================== --}}

<div class="section-title">
    Dimensions
</div>

<table class="dimensions-table">

    <tr>

        {{-- PRODUCT DIMENSIONS --}}

        <td class="dimension-box">

            <table class="dimension-inner">

                <tr>
                    <td colspan="2" class="dimension-header">
                        Product Dimensions
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Height
                    </td>

                    <td class="dimension-value">
                        {{ $product->product_height ?? '—' }}
                        {{ $product->product_height ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Width
                    </td>

                    <td class="dimension-value">
                        {{ $product->product_width ?? '—' }}
                        {{ $product->product_width ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Length
                    </td>

                    <td class="dimension-value">
                        {{ $product->product_length ?? '—' }}
                        {{ $product->product_length ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Depth
                    </td>

                    <td class="dimension-value">
                        {{ $product->product_depth ?? '—' }}
                        {{ $product->product_depth ? 'cm' : '' }}
                    </td>
                </tr>

            </table>

        </td>


        {{-- CARTON DIMENSIONS --}}

        <td class="dimension-box">

            <table class="dimension-inner">

                <tr>
                    <td colspan="2" class="dimension-header">
                        Carton Dimensions
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Height
                    </td>

                    <td class="dimension-value">
                        {{ $product->carton_height ?? '—' }}
                        {{ $product->carton_height ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Width
                    </td>

                    <td class="dimension-value">
                        {{ $product->carton_width ?? '—' }}
                        {{ $product->carton_width ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Length
                    </td>

                    <td class="dimension-value">
                        {{ $product->carton_length ?? '—' }}
                        {{ $product->carton_length ? 'cm' : '' }}
                    </td>
                </tr>

                <tr>
                    <td class="dimension-label">
                        Depth
                    </td>

                    <td class="dimension-value">
                        {{ $product->carton_depth ?? '—' }}
                        {{ $product->carton_depth ? 'cm' : '' }}
                    </td>
                </tr>

            </table>

        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- QUOTATION ITEMS --}}
{{-- ===================================================================== --}}

<div class="section-title">
    Quotation
</div>

<table class="items-table">

    <thead>

        <tr>

            <th>
                Item
            </th>

            <th>
                SKU
            </th>

            <th class="num">
                Qty
            </th>

            <th class="num">
                Unit Price
            </th>

            <th class="num">
                Subtotal
            </th>

        </tr>

    </thead>


    <tbody>

        <tr>

            <td>

                <div class="item-name">
                    {{ $product->item_name }}
                </div>

                @if(!empty($product->type_of_sample))

                    <div class="item-description">
                        {{ $product->type_of_sample }}
                    </div>

                @endif

            </td>


            <td class="sku-chip">
                {{ $product->sku ?? '—' }}
            </td>


            <td class="num">
                {{ $quantity }}
            </td>


            <td class="num">
                {{ number_format($unit_price, 2) }}
            </td>


            <td class="num">
                {{ number_format($unit_price * $quantity, 2) }}
            </td>

        </tr>

    </tbody>

</table>


{{-- ===================================================================== --}}
{{-- TOTALS --}}
{{-- ===================================================================== --}}

<table class="totals-table">

    <tr>

        <td class="label">
            Subtotal
        </td>

        <td class="value">
            {{ number_format($total, 2) }}
        </td>

    </tr>


    <tr class="grand-total">

        <td class="label">
            Total
        </td>

        <td class="value">
            {{ number_format($total, 2) }}
        </td>

    </tr>

</table>


{{-- ===================================================================== --}}
{{-- TERMS --}}
{{-- ===================================================================== --}}

<div class="terms-box">

    <div class="terms-title">
        Terms &amp; Conditions
    </div>

    <div class="terms-text">

        This quotation is valid for 30 days from the date of issue.
        Prices are subject to change without prior notice.
        Product availability is subject to confirmation at the time of order.
        Final specifications may be confirmed prior to production or delivery.

    </div>

</div>


{{-- ===================================================================== --}}
{{-- FOOTER --}}
{{-- ===================================================================== --}}

<div class="footer-note">

    METROINC &nbsp;|&nbsp;
    Quotation {{ $quote_number }} &nbsp;|&nbsp;
    Generated {{ $issued_at->format('F j, Y g:i A') }}

</div>


</body>
</html>