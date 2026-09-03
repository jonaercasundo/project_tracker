<x-mi_app>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
    rel="stylesheet"
>

<style>
    /* ============================================================
       LIQUIDATION SHOW
    ============================================================ */
.liq-receipt-preview {
    width: 52px;
    height: 52px;

    flex: 0 0 52px;

    object-fit: cover;

    border: 1px solid #dbe3ed;
    border-radius: 8px;

    background: white;

    cursor: pointer;

    transition:
        transform .15s ease,
        box-shadow .15s ease;
}

.liq-receipt-preview:hover {
    transform: scale(1.05);

    box-shadow:
        0 5px 15px rgba(15, 23, 42, .12);
}
    .liq-show {
        --liq-primary: #111827;
        --liq-secondary: #64748b;
        --liq-border: #e2e8f0;
        --liq-bg: #f8fafc;
        --liq-card: #ffffff;
        --liq-soft: #f1f5f9;

        --liq-blue: #2563eb;
        --liq-blue-dark: #1d4ed8;
        --liq-blue-soft: #eff6ff;

        --liq-green: #059669;
        --liq-green-soft: #ecfdf5;

        --liq-red: #dc2626;
        --liq-red-soft: #fff1f2;

        --liq-yellow: #a16207;
        --liq-yellow-soft: #fefce8;

        min-height: 100vh;
        background: var(--liq-bg);
        color: var(--liq-primary);

        font-family: "Inter", sans-serif;
    }

    .liq-show *,
    .liq-show *::before,
    .liq-show *::after {
        box-sizing: border-box;
    }

    .liq-display {
        font-family: "Space Grotesk", sans-serif;
    }

    .liq-mono {
        font-family: "JetBrains Mono", monospace;
    }

    .liq-container {
        width: min(100%, 1450px);
        margin: 0 auto;
        padding: 28px 24px 55px;
    }

    /* ============================================================
       HEADER
    ============================================================ */

    .liq-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;

        margin-bottom: 25px;
    }

    .liq-header-left {
        min-width: 0;
    }

    .liq-breadcrumb {
        display: flex;
        align-items: center;
        gap: 7px;

        margin-bottom: 8px;

        color: #94a3b8;

        font-size: 11px;
        font-weight: 700;

        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .liq-breadcrumb a {
        color: #64748b;
        text-decoration: none;
    }

    .liq-breadcrumb a:hover {
        color: var(--liq-blue);
    }

    .liq-title {
        margin: 0;

        color: var(--liq-primary);

        font-family: "Space Grotesk", sans-serif;

        font-size: 28px;
        line-height: 1.15;
        font-weight: 700;

        letter-spacing: -.5px;
    }

    .liq-subtitle {
        margin: 6px 0 0;

        color: var(--liq-secondary);

        font-size: 13px;
        line-height: 1.5;
    }

    .liq-header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;

        gap: 9px;

        flex-wrap: wrap;
    }

    /* ============================================================
       BUTTONS
    ============================================================ */

    .liq-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;

        min-height: 40px;

        padding: 9px 14px;

        border: 1px solid #cbd5e1;
        border-radius: 9px;

        background: white;
        color: #475569;

        font-size: 12px;
        font-weight: 800;

        text-decoration: none;

        cursor: pointer;

        transition: .15s ease;
    }

    .liq-btn:hover {
        border-color: #94a3b8;
        background: #f8fafc;

        transform: translateY(-1px);
    }

    .liq-btn-primary {
        border-color: var(--liq-blue);
        background: var(--liq-blue);
        color: white;
    }

    .liq-btn-primary:hover {
        border-color: var(--liq-blue-dark);
        background: var(--liq-blue-dark);
        color: white;
    }

    .liq-btn-danger {
        border-color: #fecaca;
        background: var(--liq-red-soft);
        color: var(--liq-red);
    }

    .liq-btn-danger:hover {
        border-color: #fca5a5;
        background: #fee2e2;
    }

    /* ============================================================
       ALERT
    ============================================================ */

    .liq-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;

        margin-bottom: 20px;

        padding: 12px 15px;

        border-radius: 10px;

        font-size: 12px;
        font-weight: 600;
        line-height: 1.5;
    }

    .liq-alert-success {
        border: 1px solid #a7f3d0;
        background: var(--liq-green-soft);
        color: #047857;
    }

    .liq-alert-error {
        border: 1px solid #fecaca;
        background: var(--liq-red-soft);
        color: #991b1b;
    }

    /* ============================================================
       SUMMARY CARDS
    ============================================================ */

    .liq-summary {
        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 15px;

        margin-bottom: 20px;
    }

    .liq-summary-card {
        min-width: 0;

        padding: 17px;

        border: 1px solid var(--liq-border);
        border-radius: 14px;

        background: white;

        box-shadow:
            0 4px 20px rgba(15, 23, 42, .035);
    }

    .liq-summary-label {
        color: #94a3b8;

        font-size: 10px;
        font-weight: 800;

        letter-spacing: .55px;
        text-transform: uppercase;
    }

    .liq-summary-value {
        margin-top: 7px;

        color: #0f172a;

        font-family: "JetBrains Mono", monospace;

        font-size: 18px;
        font-weight: 800;
    }

    .liq-summary-sub {
        margin-top: 4px;

        color: #64748b;

        font-family: "JetBrains Mono", monospace;

        font-size: 11px;
    }

    /* ============================================================
       STATUS
    ============================================================ */

    .liq-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;

        margin-top: 8px;

        padding: 7px 11px;

        border-radius: 999px;

        font-size: 11px;
        font-weight: 800;
    }

    .liq-status-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;
    }

    .liq-status-pending {
        background: var(--liq-yellow-soft);
        color: var(--liq-yellow);
    }

    .liq-status-pending .liq-status-dot {
        background: var(--liq-yellow);
    }

    .liq-status-approved {
        background: var(--liq-green-soft);
        color: #047857;
    }

    .liq-status-approved .liq-status-dot {
        background: var(--liq-green);
    }

    .liq-status-rejected {
        background: var(--liq-red-soft);
        color: #b91c1c;
    }

    .liq-status-rejected .liq-status-dot {
        background: var(--liq-red);
    }

    .liq-status-default {
        background: var(--liq-soft);
        color: #64748b;
    }

    .liq-status-default .liq-status-dot {
        background: #94a3b8;
    }

    /* ============================================================
       CARD
    ============================================================ */

    .liq-card {
        margin-bottom: 20px;

        overflow: hidden;

        border: 1px solid var(--liq-border);
        border-radius: 16px;

        background: white;

        box-shadow:
            0 4px 20px rgba(15, 23, 42, .035);
    }

    .liq-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 17px 20px;

        background: #f8fafc;

        border-bottom: 1px solid var(--liq-border);
    }

    .liq-card-title {
        margin: 0;

        color: #1e293b;

        font-family: "Space Grotesk", sans-serif;

        font-size: 16px;
        font-weight: 700;
    }

    .liq-card-description {
        margin-top: 3px;

        color: #64748b;

        font-size: 11px;
    }

    /* ============================================================
       REPORT INFORMATION
    ============================================================ */

    .liq-info-grid {
        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 0;
    }

    .liq-info-item {
        min-width: 0;

        padding: 17px 20px;

        border-right: 1px solid var(--liq-border);
        border-bottom: 1px solid var(--liq-border);
    }

    .liq-info-item:nth-child(4n) {
        border-right: none;
    }

    .liq-info-item:nth-last-child(-n + 4) {
        border-bottom: none;
    }

    .liq-info-label {
        margin-bottom: 6px;

        color: #94a3b8;

        font-size: 10px;
        font-weight: 800;

        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .liq-info-value {
        color: #334155;

        font-size: 13px;
        font-weight: 600;

        line-height: 1.5;

        word-break: break-word;
    }

    .liq-info-value.mono {
        font-family: "JetBrains Mono", monospace;
        font-size: 12px;
    }

    /* ============================================================
       EXPENSE SECTION
    ============================================================ */

    .liq-expense-list {
        display: flex;
        flex-direction: column;
        gap: 14px;

        padding: 18px;
    }

    /* ============================================================
       EXPENSE ITEM
    ============================================================ */

    .liq-expense-item {
        overflow: hidden;

        border: 1px solid #dbe3ed;
        border-radius: 14px;

        background: white;

        transition: .15s ease;
    }

    .liq-expense-item:hover {
        border-color: #cbd5e1;

        box-shadow:
            0 5px 18px rgba(15, 23, 42, .045);
    }

    .liq-expense-top {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 12px 16px;

        background: #f8fafc;

        border-bottom: 1px solid #e2e8f0;
    }

    .liq-expense-heading {
        display: flex;
        align-items: center;

        gap: 10px;

        min-width: 0;
    }

    .liq-expense-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 30px;
        height: 30px;

        flex: 0 0 30px;

        border-radius: 8px;

        background: #e2e8f0;
        color: #334155;

        font-family: "JetBrains Mono", monospace;

        font-size: 11px;
        font-weight: 800;
    }

    .liq-expense-heading-text {
        min-width: 0;
    }

    .liq-expense-title {
        color: #1e293b;

        font-size: 13px;
        font-weight: 800;
    }

    .liq-expense-ref {
        margin-top: 2px;

        color: #64748b;

        font-family: "JetBrains Mono", monospace;

        font-size: 10px;
        font-weight: 600;
    }

    .liq-expense-body {
        padding: 16px;
    }

    /* ============================================================
       3 ROW EXPENSE INFORMATION
    ============================================================ */

    .liq-expense-row {
        display: grid;

        gap: 14px;

        margin-bottom: 14px;
    }

    .liq-expense-row:last-child {
        margin-bottom: 0;
    }

    /*
     * ROW 1
     * Ref / Date / Requested By / Payee
     */

    .liq-row-identification {
        grid-template-columns:
            minmax(180px, .8fr)
            minmax(150px, .7fr)
            minmax(190px, 1fr)
            minmax(200px, 1fr);
    }

    /*
     * ROW 2
     * Expense Type / Account Buyer
     */

    .liq-row-classification {
        grid-template-columns:
            minmax(250px, 1.25fr)
            minmax(250px, 1fr);
    }

    /*
     * ROW 3
     * VND / USD / Remarks
     */

    .liq-row-amount {
        grid-template-columns:
            minmax(170px, .7fr)
            minmax(170px, .7fr)
            minmax(280px, 1.6fr);
    }

    .liq-field {
        min-width: 0;
    }

    .liq-field-label {
        margin-bottom: 6px;

        color: #94a3b8;

        font-size: 10px;
        font-weight: 800;

        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .liq-field-value {
        min-height: 40px;

        display: flex;
        align-items: center;

        padding: 9px 11px;

        border: 1px solid #e2e8f0;
        border-radius: 9px;

        background: #f8fafc;

        color: #334155;

        font-size: 12px;
        font-weight: 600;

        line-height: 1.45;

        word-break: break-word;
    }

    .liq-field-value.mono {
        color: #334155;

        font-family: "JetBrains Mono", monospace;

        font-size: 11px;
    }

    .liq-field-value.multiline {
        align-items: flex-start;
        min-height: 40px;
    }

    .liq-empty {
        color: #94a3b8;
        font-weight: 500;
    }

    /* ============================================================
       AMOUNTS
    ============================================================ */

    .liq-amount-box {
        min-height: 40px;

        display: flex;
        align-items: center;

        padding: 8px 11px;

        border: 1px solid #e2e8f0;
        border-radius: 9px;

        background: #f8fafc;
    }

    .liq-amount-vnd {
        color: #0f172a;

        font-family: "JetBrains Mono", monospace;

        font-size: 13px;
        font-weight: 800;
    }

    .liq-amount-usd {
        margin-left: auto;

        color: #64748b;

        font-family: "JetBrains Mono", monospace;

        font-size: 11px;
        font-weight: 700;
    }

    /* ============================================================
       RECEIPT
    ============================================================ */

    .liq-receipt {
        display: flex;
        align-items: center;

        gap: 12px;

        margin-top: 14px;

        padding: 11px 13px;

        border: 1px solid #e2e8f0;
        border-radius: 10px;

        background: #f8fafc;
    }

    .liq-receipt-preview {
        width: 52px;
        height: 52px;

        flex: 0 0 52px;

        object-fit: cover;

        border: 1px solid #dbe3ed;
        border-radius: 8px;

        background: white;
    }

    .liq-receipt-info {
        min-width: 0;
    }

    .liq-receipt-title {
        color: #334155;

        font-size: 11px;
        font-weight: 800;
    }

    .liq-receipt-sub {
        margin-top: 3px;

        color: #94a3b8;

        font-family: "JetBrains Mono", monospace;

        font-size: 9px;
    }

    .liq-receipt-link {
        display: inline-flex;

        margin-top: 5px;

        color: var(--liq-blue);

        font-size: 10px;
        font-weight: 800;

        text-decoration: none;
    }

    .liq-receipt-link:hover {
        text-decoration: underline;
    }

    .liq-no-receipt {
        display: flex;
        align-items: center;
        gap: 8px;

        margin-top: 14px;

        padding: 10px 12px;

        border: 1px dashed #cbd5e1;
        border-radius: 9px;

        color: #94a3b8;

        background: #fafafa;

        font-size: 10px;
        font-weight: 600;
    }

    /* ============================================================
       TOTALS
    ============================================================ */

    .liq-totals {
        display: flex;
        justify-content: flex-end;

        padding: 18px 20px;

        border-top: 1px solid var(--liq-border);

        background: #f8fafc;
    }

    .liq-total-box {
        width: min(100%, 350px);
    }

    .liq-total-row {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 7px 0;

        color: #64748b;

        font-size: 12px;
    }

    .liq-total-row strong {
        color: #0f172a;

        font-family: "JetBrains Mono", monospace;

        font-size: 12px;
    }

    .liq-total-final {
        margin-top: 6px;
        padding-top: 12px;

        border-top: 1px solid #cbd5e1;

        color: #334155;

        font-size: 13px;
        font-weight: 800;
    }

    .liq-total-final strong {
        font-size: 15px;
    }

    /* ============================================================
       EMPTY STATE
    ============================================================ */

    .liq-empty-state {
        padding: 50px 25px;

        text-align: center;

        color: #94a3b8;

        font-size: 12px;
    }

    /* ============================================================
       BOTTOM ACTIONS
    ============================================================ */

    .liq-bottom-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        margin-top: 20px;
    }

    .liq-bottom-right {
        display: flex;
        align-items: center;

        gap: 9px;
    }

    .liq-bottom-right form {
        margin: 0;
    }

    /* ============================================================
       RESPONSIVE
    ============================================================ */

    @media (max-width: 1100px) {

        .liq-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .liq-info-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .liq-info-item:nth-child(4n) {
            border-right: 1px solid var(--liq-border);
        }

        .liq-info-item:nth-child(2n) {
            border-right: none;
        }

        .liq-info-item:nth-last-child(-n + 4) {
            border-bottom: 1px solid var(--liq-border);
        }

        .liq-info-item:nth-last-child(-n + 2) {
            border-bottom: none;
        }

        .liq-row-identification {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .liq-row-classification {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .liq-row-amount {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .liq-row-amount .liq-field:last-child {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 700px) {

        .liq-container {
            padding: 18px 14px 40px;
        }

        .liq-header {
            flex-direction: column;
        }

        .liq-header-actions {
            width: 100%;
        }

        .liq-header-actions .liq-btn {
            flex: 1;
        }

        .liq-title {
            font-size: 23px;
        }

        .liq-summary {
            grid-template-columns: 1fr;
        }

        .liq-info-grid {
            grid-template-columns: 1fr;
        }

        .liq-info-item,
        .liq-info-item:nth-child(2n),
        .liq-info-item:nth-child(4n) {
            border-right: none;
            border-bottom: 1px solid var(--liq-border);
        }

        .liq-info-item:last-child {
            border-bottom: none;
        }

        .liq-expense-list {
            padding: 12px;
        }

        .liq-expense-body {
            padding: 13px;
        }

        .liq-row-identification,
        .liq-row-classification,
        .liq-row-amount {
            grid-template-columns: 1fr;
        }

        .liq-row-amount .liq-field:last-child {
            grid-column: auto;
        }

        .liq-expense-top {
            align-items: flex-start;
        }

        .liq-totals {
            padding: 15px;
        }

        .liq-bottom-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .liq-bottom-right {
            width: 100%;
        }

        .liq-bottom-right .liq-btn,
        .liq-bottom-right form {
            flex: 1;
        }

        .liq-bottom-right form .liq-btn {
            width: 100%;
        }

        .liq-receipt {
            align-items: flex-start;
        }
    }
</style>


@php

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim($liquidation->status ?? 'pending')
    );

    $statusClass = match ($status) {

        'pending' =>
            'liq-status-pending',

        'approved' =>
            'liq-status-approved',

        'rejected' =>
            'liq-status-rejected',

        default =>
            'liq-status-default',
    };


    /*
    |--------------------------------------------------------------------------
    | TOTALS
    |--------------------------------------------------------------------------
    */

    $totalVnd =
        (float) ($liquidation->total_vnd ?? 0);

    $totalUsd =
        (float) ($liquidation->total_usd ?? 0);

    $cashOnHandVnd =
        (float) ($liquidation->cash_on_hand_vnd ?? 0);

    $cashOnHandUsd =
        (float) ($liquidation->cash_on_hand_usd ?? 0);

    $expenseCount =
        $liquidation->items?->count() ?? 0;

@endphp


<div class="liq-show">

    <div class="liq-container">


        {{-- ========================================================
             HEADER
        ========================================================= --}}

        <div class="liq-header">

            <div class="liq-header-left">

                <div class="liq-breadcrumb">

                    <a href="{{ route('liquidation.index') }}">
                        Liquidation
                    </a>

                    <span>/</span>

                    <span>Report Details</span>

                </div>


                <h1 class="liq-title">
                    {{ $liquidation->title }}
                </h1>


                <p class="liq-subtitle">

                    LIQ-{{
                        str_pad(
                            $liquidation->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        )
                    }}

                    @if($liquidation->date_prepared)

                        · Prepared
                        {{ $liquidation->date_prepared->format('M d, Y') }}

                    @endif

                </p>

            </div>


            <div class="liq-header-actions">

                <a
                    href="{{ route('liquidation.index') }}"
                    class="liq-btn"
                >

                    <svg
                        width="15"
                        height="15"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    Back

                </a>


                <a
                    href="{{ route('liquidation.edit', $liquidation->id) }}"
                    class="liq-btn liq-btn-primary"
                >

                    <svg
                        width="15"
                        height="15"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                        />
                    </svg>

                    Edit Report

                </a>

            </div>

        </div>


        {{-- ========================================================
             FLASH MESSAGES
        ========================================================= --}}

        @if(session('success'))

            <div class="liq-alert liq-alert-success">

                <span>✓</span>

                <span>
                    {{ session('success') }}
                </span>

            </div>

        @endif


        @if(session('error'))

            <div class="liq-alert liq-alert-error">

                <span>!</span>

                <span>
                    {{ session('error') }}
                </span>

            </div>

        @endif


        {{-- ========================================================
             SUMMARY
        ========================================================= --}}

        <div class="liq-summary">


            {{-- TOTAL LIQUIDATED --}}

            <div class="liq-summary-card">

                <div class="liq-summary-label">
                    Total Liquidated
                </div>

                <div class="liq-summary-value">

                    ₫{{ number_format($totalVnd, 0) }}

                </div>

                <div class="liq-summary-sub">

                    ${{ number_format($totalUsd, 2) }}

                </div>

            </div>


            {{-- CASH ON HAND --}}

            <div class="liq-summary-card">

                <div class="liq-summary-label">
                    Cash on Hand
                </div>

                <div class="liq-summary-value">

                    ₫{{ number_format($cashOnHandVnd, 0) }}

                </div>

                <div class="liq-summary-sub">

                    ${{ number_format($cashOnHandUsd, 2) }}

                </div>

            </div>


            {{-- EXPENSE COUNT --}}

            <div class="liq-summary-card">

                <div class="liq-summary-label">
                    Expense Lines
                </div>

                <div class="liq-summary-value">

                    {{ $expenseCount }}

                </div>

                <div class="liq-summary-sub">

                    {{ $expenseCount === 1 ? 'Expense item' : 'Expense items' }}

                </div>

            </div>


            {{-- STATUS --}}

            <div class="liq-summary-card">

                <div class="liq-summary-label">
                    Status
                </div>

                <span class="liq-status {{ $statusClass }}">

                    <span class="liq-status-dot"></span>

                    {{ ucfirst($status) }}

                </span>

            </div>

        </div>


        {{-- ========================================================
             REPORT INFORMATION
        ========================================================= --}}

        <div class="liq-card">

            <div class="liq-card-header">

                <div>

                    <h2 class="liq-card-title">
                        Report Information
                    </h2>

                    <div class="liq-card-description">
                        Basic information and accounting details
                    </div>

                </div>

            </div>


            <div class="liq-info-grid">


                {{-- REPORT ID --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Report ID
                    </div>

                    <div class="liq-info-value mono">

                        LIQ-{{
                            str_pad(
                                $liquidation->id,
                                6,
                                '0',
                                STR_PAD_LEFT
                            )
                        }}

                    </div>

                </div>


                {{-- DATE PREPARED --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Date Prepared
                    </div>

                    <div class="liq-info-value">

                        @if($liquidation->date_prepared)

                            {{ $liquidation->date_prepared->format('F d, Y') }}

                        @else

                            <span class="liq-empty">—</span>

                        @endif

                    </div>

                </div>


                {{-- PREPARED BY --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Prepared By
                    </div>

                    <div class="liq-info-value">

                        {{ $liquidation->preparer?->name ?? '—' }}

                    </div>

                </div>


                {{-- COMPANY --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Company
                    </div>

                    <div class="liq-info-value">

                        {{ $liquidation->company?->name ?? '—' }}

                    </div>

                </div>


                {{-- EXCHANGE RATE --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Exchange Rate
                    </div>

                    <div class="liq-info-value mono">

                        ₫{{
                            number_format(
                                (float) ($liquidation->exchange_rate ?? 0),
                                2
                            )
                        }}

                        / USD

                    </div>

                </div>


                {{-- EXPENSE LINES --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Expense Lines
                    </div>

                    <div class="liq-info-value mono">

                        {{ $expenseCount }}

                    </div>

                </div>


                {{-- CREATED --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Created
                    </div>

                    <div class="liq-info-value">

                        @if($liquidation->created_at)

                            {{ $liquidation->created_at->format('M d, Y h:i A') }}

                        @else

                            <span class="liq-empty">—</span>

                        @endif

                    </div>

                </div>


                {{-- UPDATED --}}

                <div class="liq-info-item">

                    <div class="liq-info-label">
                        Last Updated
                    </div>

                    <div class="liq-info-value">

                        @if($liquidation->updated_at)

                            {{ $liquidation->updated_at->format('M d, Y h:i A') }}

                        @else

                            <span class="liq-empty">—</span>

                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================
             EXPENSE DETAILS
        ========================================================= --}}

        <div class="liq-card">

            <div class="liq-card-header">

                <div>

                    <h2 class="liq-card-title">
                        Expense Details
                    </h2>

                    <div class="liq-card-description">
                        Individual liquidation expense items
                    </div>

                </div>


                <div
                    class="liq-mono"
                    style="font-size:11px;color:#64748b;"
                >

                    {{ $expenseCount }}

                    {{ $expenseCount === 1 ? 'expense' : 'expenses' }}

                </div>

            </div>


            @if($expenseCount)


                <div class="liq-expense-list">


                    @foreach($liquidation->items as $index => $item)


                        <div class="liq-expense-item">


                            {{-- ==================================================
                                 EXPENSE HEADER
                            =================================================== --}}

                            <div class="liq-expense-top">

                                <div class="liq-expense-heading">

                                    <div class="liq-expense-number">

                                        {{ $index + 1 }}

                                    </div>


                                    <div class="liq-expense-heading-text">

                                        <div class="liq-expense-title">
                                            Expense Item
                                        </div>

                                        <div class="liq-expense-ref">

                                            {{ $item->ref_no }}

                                        </div>

                                    </div>

                                </div>


                                <div
                                    class="liq-mono"
                                    style="font-size:10px;color:#94a3b8;"
                                >

                                    Line
                                    {{ $item->line_no ?? ($index + 1) }}

                                </div>

                            </div>


                            <div class="liq-expense-body">


                                {{-- ==================================================
                                     ROW 1
                                =================================================== --}}

                                <div class="liq-expense-row liq-row-identification">


                                    {{-- REF NO --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Ref No.
                                        </div>

                                        <div class="liq-field-value mono">

                                            {{ $item->ref_no ?: '—' }}

                                        </div>

                                    </div>


                                    {{-- DATE --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Date
                                        </div>

                                        <div class="liq-field-value mono">

                                            @if($item->item_date)

                                                {{ $item->item_date->format('M d, Y') }}

                                            @else

                                                <span class="liq-empty">—</span>

                                            @endif

                                        </div>

                                    </div>


                                    {{-- REQUESTED BY --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Requested By
                                        </div>

                                        <div class="liq-field-value">

                                            {{ $item->requested_by ?: '—' }}

                                        </div>

                                    </div>


                                    {{-- PAYEE --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Payee
                                        </div>

                                        <div class="liq-field-value">

                                            {{ $item->payee ?: '—' }}

                                        </div>

                                    </div>

                                </div>


                                {{-- ==================================================
                                     ROW 2
                                =================================================== --}}

                                <div class="liq-expense-row liq-row-classification">


                                    {{-- EXPENSE TYPE --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Expense Type
                                        </div>

                                        <div class="liq-field-value">

                                            {{ $item->expense_type ?: '—' }}

                                        </div>

                                    </div>


                                    {{-- ACCOUNT / BUYER --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Account / Buyer
                                        </div>

                                        <div class="liq-field-value">

                                            {{ $item->account_buyer ?: '—' }}

                                        </div>

                                    </div>

                                </div>


                                {{-- ==================================================
                                     ROW 3
                                =================================================== --}}

                                <div class="liq-expense-row liq-row-amount">


                                    {{-- VND --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Amount VND
                                        </div>

                                        <div class="liq-amount-box">

                                            <span class="liq-amount-vnd">

                                                ₫{{
                                                    number_format(
                                                        (float) $item->amount_vnd,
                                                        0
                                                    )
                                                }}

                                            </span>

                                        </div>

                                    </div>


                                    {{-- USD --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Amount USD
                                        </div>

                                        <div class="liq-amount-box">

                                            <span class="liq-amount-vnd">

                                                ${{
                                                    number_format(
                                                        (float) $item->amount_usd,
                                                        2
                                                    )
                                                }}

                                            </span>

                                        </div>

                                    </div>


                                    {{-- REMARKS --}}

                                    <div class="liq-field">

                                        <div class="liq-field-label">
                                            Remarks
                                        </div>

                                        <div class="liq-field-value multiline">

                                            @if($item->remarks)

                                                {{ $item->remarks }}

                                            @else

                                                <span class="liq-empty">
                                                    No remarks
                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                </div>


                                {{-- ==================================================
                                     RECEIPT
                                =================================================== --}}

@if($item->receipt_image)

    @php
        $receiptPath = ltrim($item->receipt_image, '/');
        $receiptUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($receiptPath);
    @endphp

    <div class="liq-receipt">

        {{-- Receipt Preview --}}
        <a
            href="{{ $receiptUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            title="Open receipt"
        >
            <img
                src="{{ $receiptUrl }}"
                alt="Receipt for {{ $item->ref_no }}"
                class="liq-receipt-preview"
            >
        </a>


        <div class="liq-receipt-info">

            <div class="liq-receipt-title">
                Receipt Attached
            </div>

            <div class="liq-receipt-sub">
                {{ $item->ref_no }}
            </div>

            <a
                href="{{ $receiptUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="liq-receipt-link"
            >
                View Full Receipt →
            </a>

        </div>

    </div>

@else

    <div class="liq-no-receipt">

        <span>📎</span>

        <span>
            No receipt image attached for this expense.
        </span>

    </div>

@endif


                            </div>

                        </div>


                    @endforeach


                </div>


                {{-- ========================================================
                     TOTALS
                ========================================================= --}}

                <div class="liq-totals">

                    <div class="liq-total-box">


                        <div class="liq-total-row">

                            <span>
                                Total Expenses
                            </span>

                            <strong>

                                ₫{{ number_format($totalVnd, 0) }}

                            </strong>

                        </div>


                        <div class="liq-total-row">

                            <span>
                                USD Equivalent
                            </span>

                            <strong>

                                ${{ number_format($totalUsd, 2) }}

                            </strong>

                        </div>


                        <div class="liq-total-row liq-total-final">

                            <span>
                                Cash on Hand
                            </span>

                            <strong>

                                ₫{{ number_format($cashOnHandVnd, 0) }}

                            </strong>

                        </div>

                    </div>

                </div>


            @else


                <div class="liq-empty-state">

                    No expense items found.

                </div>


            @endif

        </div>


        {{-- ========================================================
             BOTTOM ACTIONS
        ========================================================= --}}

        <div class="liq-bottom-actions">


            <a
                href="{{ route('liquidation.index') }}"
                class="liq-btn"
            >

                ← Back to Reports

            </a>


            <div class="liq-bottom-right">


                <a
                    href="{{ route('liquidation.edit', $liquidation->id) }}"
                    class="liq-btn liq-btn-primary"
                >

                    Edit Report

                </a>


                <form
                    method="POST"
                    action="{{ route('liquidation.destroy', $liquidation->id) }}"
                    onsubmit="return confirm('Delete this liquidation report? This action cannot be undone.');"
                >

                    @csrf

                    @method('DELETE')


                    <button
                        type="submit"
                        class="liq-btn liq-btn-danger"
                    >

                        Delete

                    </button>

                </form>


            </div>

        </div>


    </div>

</div>

</x-mi_app>
