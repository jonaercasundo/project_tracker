<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Liquidation Report LIQ-{{ str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>

    /* ==============================================================
       DOMPDF-SAFE STYLES
       No flexbox / grid / external fonts / JS — tables + block only.
    =============================================================== */

    @page {
        margin: 22px 26px 34px 26px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        color: #1e293b;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 10.5px;
        line-height: 1.5;
    }

    .mono {
        font-family: "DejaVu Sans Mono", monospace;
    }

    .muted {
        color: #64748b;
    }

    .faint {
        color: #94a3b8;
    }

    /* ============================================================
       HEADER
    ============================================================ */

    .doc-header {
        width: 100%;
        border-bottom: 2px solid #1e293b;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }

    .doc-header table {
        width: 100%;
        border-collapse: collapse;
    }

    .doc-header td {
        vertical-align: top;
        padding: 0;
    }

    .doc-title {
        font-size: 19px;
        font-weight: bold;
        color: #0f172a;
        margin: 0 0 4px 0;
    }

    .doc-subtitle {
        font-size: 10px;
        color: #64748b;
    }

    /* ============================================================
       SECTION TITLES
    ============================================================ */

    .section-title {
        font-size: 12px;
        font-weight: bold;
        color: #0f172a;
        margin: 18px 0 8px 0;
        padding-bottom: 4px;
        border-bottom: 1px solid #cbd5e1;
    }

    /* ============================================================
       SUMMARY CARDS (table-based, since no flexbox)
    ============================================================ */

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }

    .summary-table td {
        width: 25%;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 8px 10px;
        vertical-align: top;
    }

    .summary-label {
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #94a3b8;
        margin-bottom: 3px;
    }

    .summary-value {
        font-size: 13px;
        font-weight: bold;
        color: #0f172a;
    }

    .summary-sub {
        font-size: 9px;
        color: #64748b;
        margin-top: 2px;
    }

    /* ============================================================
       INFO TABLE
    ============================================================ */

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table td {
        border: 1px solid #e2e8f0;
        padding: 7px 10px;
        width: 25%;
        vertical-align: top;
    }

    .info-label {
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #94a3b8;
        margin-bottom: 3px;
    }

    .info-value {
        font-size: 10.5px;
        font-weight: bold;
        color: #334155;
    }

    /* ============================================================
       EXPENSE TABLE
    ============================================================ */

    .expense-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }

    .expense-table th {
        background: #1e293b;
        color: #ffffff;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .3px;
        padding: 6px 6px;
        text-align: left;
        border: 1px solid #1e293b;
    }

    .expense-table td {
        font-size: 9px;
        padding: 6px 6px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
    }

    .expense-table tr:nth-child(even) td {
        background: #f8fafc;
    }

    .text-right {
        text-align: right;
    }

    .col-no    { width: 3%; }
    .col-ref   { width: 10%; }
    .col-date  { width: 8%; }
    .col-req   { width: 11%; }
    .col-payee { width: 11%; }
    .col-type  { width: 11%; }
    .col-acct  { width: 11%; }
    .col-vnd   { width: 12%; }
    .col-usd   { width: 10%; }
    .col-rmk   { width: 13%; }

    /* ============================================================
       TOTALS
    ============================================================ */

    .totals-wrap {
        width: 100%;
        margin-top: 16px;
    }

    .totals-table {
        width: 260px;
        margin-left: auto;
        border-collapse: collapse;
    }

    .totals-table td {
        padding: 5px 0;
        font-size: 10px;
        color: #64748b;
    }

    .totals-table td.val {
        text-align: right;
        font-family: "DejaVu Sans Mono", monospace;
        font-weight: bold;
        color: #0f172a;
    }

    .totals-table tr.final td {
        border-top: 1px solid #cbd5e1;
        padding-top: 8px;
        font-size: 12px;
        font-weight: bold;
        color: #334155;
    }

    .totals-table tr.final td.val {
        font-size: 13px;
    }

    /* ============================================================
       EMPTY STATE
    ============================================================ */

    .empty-state {
        padding: 30px 0;
        text-align: center;
        color: #94a3b8;
        font-size: 10px;
        border: 1px dashed #cbd5e1;
    }

    /* ============================================================
       RECEIPT PAGES (each receipt on its own page)
    ============================================================ */

    .receipt-page-item {
        padding-top: 4px;
    }

    .receipt-page-label {
        font-size: 11px;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .receipt-page-sub {
        font-size: 9px;
        color: #64748b;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #cbd5e1;
    }

    .receipt-page-image {
        display: block;
        margin: 0 auto;
        max-width: 100%;
        max-height: 640px;
        border: 1px solid #e2e8f0;
    }

    .receipt-missing {
        padding: 40px 0;
        text-align: center;
        color: #94a3b8;
        font-size: 10px;
        border: 1px dashed #cbd5e1;
    }

    /* ============================================================
       FOOTER
    ============================================================ */

    .doc-footer {
        margin-top: 24px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
        font-size: 8px;
        color: #94a3b8;
        text-align: center;
    }

</style>
</head>
<body>

@php

    $totalVnd      = (float) ($liquidation->total_vnd ?? 0);
    $totalUsd      = (float) ($liquidation->total_usd ?? 0);
    $cashOnHandVnd = (float) ($liquidation->cash_on_hand_vnd ?? 0);
    $cashOnHandUsd = (float) ($liquidation->cash_on_hand_usd ?? 0);
    $pcf           = (float) ($liquidation->pcf_amount ?? 0);

    $pcfUsd = $liquidation->exchange_rate > 0
        ? $pcf / (float) $liquidation->exchange_rate
        : 0;

    $expenseCount = $liquidation->items?->count() ?? 0;

    $reportId = 'LIQ-' . str_pad($liquidation->id, 6, '0', STR_PAD_LEFT);

@endphp

{{-- ==================================================================
     HEADER
=================================================================== --}}

<div class="doc-header">
    <table>
        <tr>
            <td style="width: 100%;">
                <div class="doc-title">{{ $liquidation->title }}</div>
                <div class="doc-subtitle">
                    {{ $reportId }}
                    @if($liquidation->date_prepared)
                        &middot; Prepared {{ $liquidation->date_prepared->format('M d, Y') }}
                    @endif
                    @if($liquidation->company?->name)
                        &middot; {{ $liquidation->company->name }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ==================================================================
     SUMMARY CARDS
=================================================================== --}}

<table class="summary-table">
    <tr>
        <td>
            <div class="summary-label">Total Liquidated</div>
            <div class="summary-value">&#8363;{{ number_format($totalVnd, 0) }}</div>
            <div class="summary-sub">${{ number_format($totalUsd, 2) }}</div>
        </td>
        <td>
            <div class="summary-label">PCF Amount</div>
            <div class="summary-value">&#8363;{{ number_format($pcf, 0) }}</div>
            <div class="summary-sub">${{ number_format($pcfUsd, 2) }}</div>
        </td>
        <td>
            <div class="summary-label">Cash on Hand</div>
            <div class="summary-value">&#8363;{{ number_format($cashOnHandVnd, 0) }}</div>
            <div class="summary-sub">${{ number_format($cashOnHandUsd, 2) }}</div>
        </td>
        <td>
            <div class="summary-label">Expense Lines</div>
            <div class="summary-value">{{ $expenseCount }}</div>
            <div class="summary-sub">{{ $expenseCount === 1 ? 'Expense item' : 'Expense items' }}</div>
        </td>
    </tr>
</table>

{{-- ==================================================================
     REPORT INFORMATION
=================================================================== --}}

<div class="section-title">Report Information</div>

<table class="info-table">
    <tr>
        <td>
            <div class="info-label">Report ID</div>
            <div class="info-value mono">{{ $reportId }}</div>
        </td>
        <td>
            <div class="info-label">Date Prepared</div>
            <div class="info-value">
                {{ $liquidation->date_prepared ? $liquidation->date_prepared->format('F d, Y') : '—' }}
            </div>
        </td>
        <td>
            <div class="info-label">Prepared By</div>
            <div class="info-value">{{ $liquidation->preparer?->name ?? '—' }}</div>
        </td>
        <td>
            <div class="info-label">Company</div>
            <div class="info-value">{{ $liquidation->company?->name ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="info-label">Exchange Rate</div>
            <div class="info-value mono">
                &#8363;{{ number_format((float) ($liquidation->exchange_rate ?? 0), 2) }} / USD
            </div>
        </td>
        <td>
            <div class="info-label">Expense Lines</div>
            <div class="info-value mono">{{ $expenseCount }}</div>
        </td>
        <td>
            <div class="info-label">Created</div>
            <div class="info-value">
                {{ $liquidation->created_at ? $liquidation->created_at->format('M d, Y h:i A') : '—' }}
            </div>
        </td>
        <td>
            <div class="info-label">Last Updated</div>
            <div class="info-value">
                {{ $liquidation->updated_at ? $liquidation->updated_at->format('M d, Y h:i A') : '—' }}
            </div>
        </td>
    </tr>
</table>

{{-- ==================================================================
     EXPENSE DETAILS
=================================================================== --}}

<div class="section-title">
    Expense Details
    <span class="faint" style="font-weight: normal; float: right; font-size: 9px;">
        {{ $expenseCount }} {{ $expenseCount === 1 ? 'expense' : 'expenses' }}
    </span>
</div>

@if($expenseCount)

    <table class="expense-table">
        <thead>
            <tr>
                <th class="col-no">#</th>
                <th class="col-ref">Ref No.</th>
                <th class="col-date">Date</th>
                <th class="col-req">Requested By</th>
                <th class="col-payee">Payee</th>
                <th class="col-type">Expense Type</th>
                <th class="col-acct">Account / Buyer</th>
                <th class="col-vnd text-right">Amount VND</th>
                <th class="col-usd text-right">Amount USD</th>
                <th class="col-rmk">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liquidation->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="mono">{{ $item->ref_no ?: '—' }}</td>
                    <td class="mono">{{ $item->item_date ? $item->item_date->format('m/d/Y') : '—' }}</td>
                    <td>{{ $item->requested_by ?: '—' }}</td>
                    <td>{{ $item->payee ?: '—' }}</td>
                    <td>{{ $item->expense_type ?: '—' }}</td>
                    <td>{{ $item->account_buyer ?: '—' }}</td>
                    <td class="mono text-right">&#8363;{{ number_format((float) $item->amount_vnd, 0) }}</td>
                    <td class="mono text-right">${{ number_format((float) $item->amount_usd, 2) }}</td>
                    <td>{{ $item->remarks ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ==============================================================
         TOTALS
    =============================================================== --}}

    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td>Total Expenses</td>
                <td class="val">&#8363;{{ number_format($totalVnd, 0) }}</td>
            </tr>
            <tr>
                <td>USD Equivalent</td>
                <td class="val">${{ number_format($totalUsd, 2) }}</td>
            </tr>
            <tr>
                <td>PCF Amount</td>
                <td class="val">&#8363;{{ number_format($pcf, 0) }}</td>
            </tr>
            <tr>
                <td>PCF USD Equivalent</td>
                <td class="val">${{ number_format($pcfUsd, 2) }}</td>
            </tr>
            <tr class="final">
                <td>Cash on Hand</td>
                <td class="val">&#8363;{{ number_format($cashOnHandVnd, 0) }}</td>
            </tr>
        </table>
    </div>

@else

    <div class="empty-state">No expense items found.</div>

@endif

{{-- ==================================================================
     RECEIPT ATTACHMENTS (dedicated page per receipt)
=================================================================== --}}

@php
    $itemsWithReceipts = $liquidation->items
        ->filter(fn ($item) => (bool) $item->receipt_image)
        ->values();
@endphp

@if($itemsWithReceipts->isNotEmpty())

    @foreach($itemsWithReceipts as $index => $item)

        <div style="page-break-before: always;"></div>

        @php
            $receiptRelativePath = ltrim($item->receipt_image, '/');
            $receiptFullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($receiptRelativePath);
        @endphp

        <div class="receipt-page-item">

            <div class="receipt-page-label">
                Receipt — {{ $item->ref_no ?: 'Expense Item' }}
            </div>

            <div class="receipt-page-sub">
                {{ $reportId }}
                @if($item->item_date)
                    &middot; {{ $item->item_date->format('M d, Y') }}
                @endif
                @if($item->payee)
                    &middot; {{ $item->payee }}
                @endif
            </div>

            @if(file_exists($receiptFullPath))
                <img
                    src="{{ $receiptFullPath }}"
                    class="receipt-page-image"
                    alt="Receipt for {{ $item->ref_no }}"
                >
            @else
                <div class="receipt-missing">Receipt file not found.</div>
            @endif

        </div>

    @endforeach

@endif

{{-- ==================================================================
     FOOTER
=================================================================== --}}

<div class="doc-footer">
    Generated {{ now()->format('M d, Y h:i A') }} &middot; {{ $reportId }}
</div>

</body>
</html>