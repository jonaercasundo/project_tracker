<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quote_number }}</title>
    <style>
        /* dompdf has limited CSS support — keep this simple and table-based */
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a1a; margin: 0; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .header-table td { vertical-align: top; }
        .company-name { font-size: 20px; font-weight: bold; color: #1a1a1a; }
        .quote-label { font-size: 22px; font-weight: bold; color: #2C6E8C; text-align: right; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .meta-table td { padding: 4px 0; font-size: 11px; }
        .meta-label { color: #777; width: 110px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table th {
            background: #F4F6F7; text-align: left; padding: 8px 10px;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; color: #555;
            border-bottom: 2px solid #ddd;
        }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 12px; }
        .items-table .num { text-align: right; }
        .totals-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .totals-table td { padding: 6px 10px; font-size: 12px; }
        .totals-table .label { text-align: right; color: #555; }
        .totals-table .value { text-align: right; width: 120px; }
        .totals-table .grand-total .label,
        .totals-table .grand-total .value { font-size: 15px; font-weight: bold; color: #1a1a1a; border-top: 2px solid #1a1a1a; padding-top: 10px; }
        .footer-note { margin-top: 40px; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 12px; }
        .sku-chip { font-family: 'Courier New', monospace; font-size: 10px; color: #2C6E8C; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="company-name">
                {{-- Swap in your actual company name / logo <img> tag here --}}
                Metroinc
            </td>
            <td class="quote-label">QUOTATION</td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Quote No.</td>
            <td>{{ $quote_number }}</td>
            <td class="meta-label">Date Issued</td>
            <td>{{ $issued_at->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Customer</td>
            <td colspan="3">{{ $customer_name }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $product->item_name }}</td>
                <td class="sku-chip">{{ $product->sku ?? '—' }}</td>
                <td class="num">{{ $quantity }}</td>
                <td class="num">{{ number_format($unit_price, 2) }}</td>
                <td class="num">{{ number_format($unit_price * $quantity, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format($total, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="label">Total</td>
            <td class="value">{{ number_format($total, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This quotation is valid for 30 days from the date of issue. Prices are subject to change without prior notice.
    </div>

</body>
</html>
