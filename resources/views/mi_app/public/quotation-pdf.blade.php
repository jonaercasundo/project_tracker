<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <title>Quotation {{ $quote_number }}</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | DOMPDF-SAFE DESIGN
        |--------------------------------------------------------------------------
        */

        @page {
            margin: 35px 40px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
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
        | Meta Information
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
        | Section Headers
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
        | Product Information
        |--------------------------------------------------------------------------
        */

        .product-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .product-info-table td {
            vertical-align: top;
        }

        .product-image-cell {
            width: 175px;
            padding-right: 20px;
        }

        .product-image {
            width: 160px;
            height: 160px;
            object-fit: contain;
            border: 1px solid #ddd;
            padding: 6px;
        }

        .no-image {
            width: 160px;
            height: 160px;
            border: 1px solid #ddd;
            background: #F7F8F8;
            text-align: center;
            vertical-align: middle;
            color: #999;
            font-size: 9px;
        }

        .product-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .product-sku {
            font-family: 'Courier New', monospace;
            color: #2C6E8C;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .description {
            color: #555;
            line-height: 1.5;
            font-size: 10px;
        }


        /*
        |--------------------------------------------------------------------------
        | Product Details
        |--------------------------------------------------------------------------
        */

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .details-table td {
            width: 25%;
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .detail-label {
            display: block;
            color: #888;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 10px;
            font-weight: bold;
            color: #222;
        }


        /*
        |--------------------------------------------------------------------------
        | Materials / Colors
        |--------------------------------------------------------------------------
        */

        .tags-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .tags-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .tag-label {
            color: #777;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .tag {
            display: inline-block;
            background: #F4F6F7;
            border: 1px solid #ddd;
            color: #444;
            padding: 4px 7px;
            margin-right: 4px;
            margin-bottom: 4px;
            font-size: 8px;
        }


        /*
        |--------------------------------------------------------------------------
        | Dimensions
        |--------------------------------------------------------------------------
        */

        .dimensions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .dimensions-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .dimension-box {
            border: 1px solid #ddd;
            padding: 10px;
        }

        .dimension-title {
            font-size: 10px;
            font-weight: bold;
            color: #2C6E8C;
            margin-bottom: 8px;
        }

        .dimension-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dimension-table td {
            width: auto;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
            font-size: 9px;
        }

        .dimension-table td:last-child {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | Items Table
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
        }

        .items-table .num {
            text-align: right;
        }

        .sku-chip {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #2C6E8C;
        }


        /*
        |--------------------------------------------------------------------------
        | Totals
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
        | Footer
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
                METROINC
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
{{-- PRODUCT INFORMATION --}}
{{-- ========================================================================= --}}

<div class="section-title">
    Product Information
</div>

<table class="product-info-table">

    <tr>

        {{-- Product Image --}}
        <td class="product-image-cell">

            @if(!empty($product_image))

                <img
                    src="{{ $product_image }}"
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


        {{-- Product Details --}}
        <td>

            <div class="product-name">
                {{ $product->item_name }}
            </div>

            <div class="product-sku">
                SKU: {{ $product->sku ?? '—' }}
            </div>

            @if(!empty($product->description))

                <div class="description">
                    {{ $product->description }}
                </div>

            @else

                <div class="description">
                    Product quotation for
                    {{ $product->item_name }}.
                </div>

            @endif

        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- PRODUCT CLASSIFICATION --}}
{{-- ========================================================================= --}}

<div class="section-title">
    Product Classification
</div>


<table class="details-table">

    <tr>

        <td>

            <span class="detail-label">
                Category
            </span>

            <span class="detail-value">
                {{ $product->category->name ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Sub Category
            </span>

            <span class="detail-value">
                {{ $product->subCategory->name ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Product Type
            </span>

            <span class="detail-value">
                {{ $product->productType->name ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Collection
            </span>

            <span class="detail-value">
                {{ $product->collection->name ?? '—' }}
            </span>

        </td>

    </tr>


    <tr>

        <td>

            <span class="detail-label">
                Type of Sample
            </span>

            <span class="detail-value">
                {{ $product->type_of_sample ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Classification
            </span>

            <span class="detail-value">
                {{ $product->classification ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Designed By
            </span>

            <span class="detail-value">
                {{ $product->designed_by ?? '—' }}
            </span>

        </td>


        <td>

            <span class="detail-label">
                Product Type
            </span>

            <span class="detail-value">
                {{ $product->type ?? '—' }}
            </span>

        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- MATERIALS AND COLORS --}}
{{-- ========================================================================= --}}

<div class="section-title">
    Materials &amp; Colors
</div>


<table class="tags-table">

    <tr>

        <td>

            <div class="tag-label">
                Materials
            </div>

            @php
                $materials = $product->materials ?? [];
            @endphp

            @forelse($materials as $material)

                <span class="tag">
                    {{ $material }}
                </span>

            @empty

                <span style="font-size:9px;color:#999;">
                    No materials specified
                </span>

            @endforelse

        </td>


        <td>

            <div class="tag-label">
                Colors
            </div>

            @php
                $colors = $product->color ?? [];
            @endphp

            @forelse($colors as $color)

                <span class="tag">
                    {{ $color }}
                </span>

            @empty

                <span style="font-size:9px;color:#999;">
                    No colors specified
                </span>

            @endforelse

        </td>

    </tr>

</table>


{{-- ========================================================================= --}}
{{-- DIMENSIONS --}}
{{-- ========================================================================= --}}

<div class="section-title">
    Product &amp; Packaging Dimensions
</div>


<table class="dimensions-table">

    <tr>

        {{-- Product Dimensions --}}
        <td>

            <div class="dimension-box">

                <div class="dimension-title">
                    Product Dimensions
                </div>

                <table class="dimension-table">

                    <tr>
                        <td>Height</td>
                        <td>
                            {{ $product->product_height ?? '—' }}
                            {{ $product->product_height !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Width</td>
                        <td>
                            {{ $product->product_width ?? '—' }}
                            {{ $product->product_width !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Length</td>
                        <td>
                            {{ $product->product_length ?? '—' }}
                            {{ $product->product_length !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Depth</td>
                        <td>
                            {{ $product->product_depth ?? '—' }}
                            {{ $product->product_depth !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                </table>

            </div>

        </td>


        {{-- Carton Dimensions --}}
        <td>

            <div class="dimension-box">

                <div class="dimension-title">
                    Carton Dimensions
                </div>

                <table class="dimension-table">

                    <tr>
                        <td>Height</td>
                        <td>
                            {{ $product->carton_height ?? '—' }}
                            {{ $product->carton_height !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Width</td>
                        <td>
                            {{ $product->carton_width ?? '—' }}
                            {{ $product->carton_width !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Length</td>
                        <td>
                            {{ $product->carton_length ?? '—' }}
                            {{ $product->carton_length !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                    <tr>
                        <td>Depth</td>
                        <td>
                            {{ $product->carton_depth ?? '—' }}
                            {{ $product->carton_depth !== null ? 'cm' : '' }}
                        </td>
                    </tr>

                </table>

            </div>

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
                <strong>
                    {{ $product->item_name }}
                </strong>
            </td>

            <td class="sku-chip">
                {{ $product->sku ?? '—' }}
            </td>

            <td class="num">
                {{ number_format($quantity) }}
            </td>

            <td class="num">
                ₱{{ number_format((float) $unit_price, 2) }}
            </td>

            <td class="value">
                ₱{{ number_format((float) $subtotal, 2) }}
            </td>

        </tr>

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
            ₱{{ number_format($total, 2) }}
        </td>

    </tr>


    <tr class="grand-total">

        <td class="label">
            TOTAL
        </td>

        <td class="value">
            ₱{{ number_format((float) $total, 2) }}
        </td>

    </tr>

</table>


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

    Thank you for your interest in METROINC products.

</div>


</body>
</html>