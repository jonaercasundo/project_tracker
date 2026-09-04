<x-mi_app>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>


    @php

        /*
        |--------------------------------------------------------------------------
        | Basic report data
        |--------------------------------------------------------------------------
        */

        $liquidationId = $report->id;

        $currentUserId = auth()->id();

        $currentUserName = auth()->user()?->name ?? 'Unknown User';

        $reportTitle = old(
            'report_title',
            $report->title ?? ''
        );

        $datePrepared = old(
            'date_prepared',
            $report->date_prepared
                ? \Carbon\Carbon::parse($report->date_prepared)->format('Y-m-d')
                : ''
        );

        $exchangeRate = old(
            'exchange_rate',
            $report->exchange_rate ?? ''
        );

        $pcfAmount = old(
            'pcf_amount',
            $report->pcf_amount ?? ''
        );

        $status = $report->status ?? 'Pending';


        /*
        |--------------------------------------------------------------------------
        | Expense classifications
        |--------------------------------------------------------------------------
        */

        $expenseClassifications = [

            'Transportation & Travel' => [

                'Transportation and Travel'
                    => 'Transportation and travel related expenses.',

                'HO - Transportation and Travel'
                    => 'Head Office transportation and travel expenses.',
            ],


            'R&D / Samples' => [

                'RND Expense'
                    => 'Research and development related expenses.',

                'Courier fees'
                    => 'Courier and delivery charges.',
            ],


            'Utilities & Communication' => [

                'Utilities'
                    => 'Utilities and related operating expenses.',

                'Communication'
                    => 'Communication expenses.',

                'HO - Communication'
                    => 'Head Office communication expenses.',
            ],


            'Entertainment & Meals' => [

                'Entertainment Expense'
                    => 'Business entertainment expenses.',

                'Meal Employee'
                    => 'Employee meal expenses.',
            ],


            'Repairs & Maintenance' => [

                'Repairs & Maintenance'
                    => 'Repair and maintenance expenses.',
            ],


            'Services & Operations' => [

                'Outsourcing services'
                    => 'Outsourced services and operational support.',
            ],


            'Marketing & Office' => [

                'Advertising & Promotions'
                    => 'Advertising and promotional expenses.',

                'Stationery & office supplies'
                    => 'Office stationery and supplies.',
            ],

        ];


        /*
        |--------------------------------------------------------------------------
        | Requested By / Account-Buyer preset options
        |--------------------------------------------------------------------------
        |
        | Each field also supports a manual "Other" entry for names/accounts
        | that aren't in the preset list below.
        |
        */

        $requestedByOptions = [
            'Maizen Tabobo',
            'Rose Ann Neis',
            'Arthur Ballais',
            'Marco Juan',
        ];

        $accountBuyerOptions = [
            'Action',
            'TJX',
            'World Market',
        ];


        /*
        |--------------------------------------------------------------------------
        | Existing items
        |--------------------------------------------------------------------------
        */

        $existingItems = old('items');


        if ($existingItems === null) {

            $existingItems = $report->items
                ->map(function ($item) {

                    return [

                        'id' => $item->id,

                        'line_no' => $item->line_no,

                        'ref_no' => $item->ref_no,

                        'item_date' => $item->item_date
                            ? \Carbon\Carbon::parse($item->item_date)->format('Y-m-d')
                            : '',

                        'requested_by' => $item->requested_by,

                        'requested_by_name'
                            => $item->requestedBy?->name,

                        'payee' => $item->payee,

                        'expense_type'
                            => $item->expense_type,

                        'account_buyer'
                            => $item->account_buyer,

                        'remarks'
                            => $item->remarks,

                        'amount_vnd'
                            => $item->amount_vnd,

                        'amount_usd'
                            => $item->amount_usd,

                        'receipt_image'
                            => $item->receipt_image,
                    ];

                })
                ->toArray();
        }


        /*
        |--------------------------------------------------------------------------
        | Guarantee one empty row
        |--------------------------------------------------------------------------
        */

        if (empty($existingItems)) {

            $existingItems = [[

                'id' => null,

                'line_no' => 1,

                'ref_no' => '',

                'item_date' => $datePrepared,

                'requested_by' => $currentUserId,

                'requested_by_name'
                    => $currentUserName,

                'payee' => '',

                'expense_type' => '',

                'account_buyer' => '',

                'remarks' => '',

                'amount_vnd' => '',

                'amount_usd' => '',

                'receipt_image' => null,

            ]];
        }

    @endphp


    <style>

        /* ================================================================
           ROOT
        ================================================================ */

        :root {

            --liq-primary: #2563eb;
            --liq-primary-dark: #1d4ed8;

            --liq-success: #16a34a;
            --liq-danger: #dc2626;
            --liq-warning: #d97706;

            --liq-text: #111827;
            --liq-muted: #64748b;

            --liq-border: #e2e8f0;
            --liq-border-dark: #cbd5e1;

            --liq-bg: #f1f5f9;
            --liq-card: #ffffff;

            --liq-soft-blue: #eff6ff;
            --liq-soft-green: #f0fdf4;
            --liq-soft-red: #fef2f2;
            --liq-soft-orange: #fff7ed;

            --liq-radius: 16px;

            --liq-shadow:
                0 10px 30px rgba(15, 23, 42, .06);

        }


        /* ================================================================
           PAGE
        ================================================================ */

        .liq-page {

            min-height: 100vh;

            padding: 30px;

            background:
                linear-gradient(
                    180deg,
                    #f8fafc 0%,
                    #eef2f7 100%
                );

            font-family: Inter, sans-serif;

            color: var(--liq-text);

        }


        .liq-container {

            width: 100%;

            max-width: 1550px;

            margin: 0 auto;

        }


        /* ================================================================
           HEADER
        ================================================================ */

        .liq-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 24px;

        }


        .liq-header-left {

            display: flex;

            align-items: center;

            gap: 15px;

        }


        .liq-header-icon {

            width: 52px;

            height: 52px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: white;

            box-shadow:
                0 8px 20px rgba(37, 99, 235, .20);

        }


        .liq-header-icon svg {

            width: 25px;

            height: 25px;

        }


        .liq-title {

            margin: 0;

            font-family: "Space Grotesk", sans-serif;

            font-size: 27px;

            font-weight: 700;

            letter-spacing: -.5px;

        }


        .liq-subtitle {

            margin-top: 4px;

            color: var(--liq-muted);

            font-size: 13px;

        }


        .liq-header-actions {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;

        }


        /* ================================================================
           BUTTONS
        ================================================================ */

        .liq-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            border: 0;

            border-radius: 10px;

            padding: 10px 14px;

            font-family: Inter, sans-serif;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform .15s ease,
                box-shadow .15s ease,
                background .15s ease;

        }


        .liq-btn:hover {

            transform: translateY(-1px);

        }


        .liq-btn svg {

            width: 16px;

            height: 16px;

        }


        .liq-btn-secondary {

            background: white;

            color: #334155;

            border: 1px solid var(--liq-border);

        }


        .liq-btn-secondary:hover {

            background: #f8fafc;

            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);

        }


        .liq-btn-primary {

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            box-shadow:
                0 5px 15px rgba(37, 99, 235, .20);

        }


        .liq-btn-primary:hover {

            box-shadow:
                0 8px 20px rgba(37, 99, 235, .25);

        }


        .liq-btn-danger {

            color: #b91c1c;

            background: #fee2e2;

        }


        .liq-btn-danger:hover {

            background: #fecaca;

        }


        /* ================================================================
           STATUS
        ================================================================ */

        .liq-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 800;

            background: #fef3c7;

            color: #92400e;

        }


        .liq-status-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: currentColor;

        }


        /* ================================================================
           ALERTS
        ================================================================ */

        .liq-alert {

            padding: 14px 16px;

            border-radius: 12px;

            margin-bottom: 18px;

            font-size: 13px;

        }


        .liq-alert-danger {

            color: #991b1b;

            background: #fef2f2;

            border: 1px solid #fecaca;

        }


        /* ================================================================
           CARD
        ================================================================ */

        .liq-card {

            background: var(--liq-card);

            border: 1px solid var(--liq-border);

            border-radius: var(--liq-radius);

            box-shadow: var(--liq-shadow);

            margin-bottom: 20px;

            overflow: hidden;

        }


        .liq-card-header {

            padding: 17px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid var(--liq-border);

            background: rgba(255,255,255,.85);

        }


        .liq-card-header-left {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .liq-card-icon {

            width: 34px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: var(--liq-soft-blue);

            color: var(--liq-primary);

        }


        .liq-card-icon svg {

            width: 17px;

            height: 17px;

        }


        .liq-card-title {

            margin: 0;

            font-family: "Space Grotesk", sans-serif;

            font-size: 16px;

            font-weight: 700;

        }


        .liq-card-description {

            margin-top: 2px;

            font-size: 11px;

            color: var(--liq-muted);

        }


        .liq-card-body {

            padding: 20px;

        }


        /* ================================================================
           FORM
        ================================================================ */

        .liq-grid {

            display: grid;

            grid-template-columns:
                repeat(12, minmax(0, 1fr));

            gap: 15px;

        }


        .liq-col-3 {

            grid-column: span 3;

        }


        .liq-col-4 {

            grid-column: span 4;

        }


        .liq-col-6 {

            grid-column: span 6;

        }


        .liq-col-12 {

            grid-column: span 12;

        }


        .liq-label {

            display: flex;

            align-items: center;

            gap: 4px;

            margin-bottom: 6px;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .04em;

            color: #475569;

        }


        .liq-required {

            color: var(--liq-danger);

        }


        .liq-input,
        .liq-select,
        .liq-textarea {

            width: 100%;

            box-sizing: border-box;

            border: 1px solid var(--liq-border-dark);

            border-radius: 9px;

            padding: 10px 11px;

            background: white;

            color: #0f172a;

            font-family: Inter, sans-serif;

            font-size: 13px;

            outline: none;

            transition:
                border .15s ease,
                box-shadow .15s ease;

        }


        .liq-input:focus,
        .liq-select:focus,
        .liq-textarea:focus {

            border-color: var(--liq-primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .09);

        }


        .liq-input[readonly] {

            background: #f8fafc;

            color: #64748b;

        }


        .liq-textarea {

            min-height: 68px;

            resize: vertical;

        }


        .liq-mono {

            font-family:
                "JetBrains Mono",
                monospace;

        }


        .liq-help {

            margin-top: 5px;

            color: #94a3b8;

            font-size: 10px;

        }


        /* ================================================================
           REPORT META
        ================================================================ */

        .report-meta {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;

            margin-top: 18px;

        }


        .meta-box {

            padding: 11px 13px;

            border-radius: 10px;

            border: 1px solid var(--liq-border);

            background: #f8fafc;

        }


        .meta-label {

            font-size: 9px;

            text-transform: uppercase;

            font-weight: 800;

            color: #94a3b8;

        }


        .meta-value {

            margin-top: 4px;

            font-size: 12px;

            font-weight: 700;

            color: #334155;

        }


        /* ================================================================
           EXPENSE
        ================================================================ */

        .expense-item {

            margin-bottom: 14px;

            border: 1px solid var(--liq-border);

            border-radius: 14px;

            overflow: hidden;

            background: white;

            transition:
                box-shadow .15s ease,
                border .15s ease;

            animation: expenseIn .2s ease;

        }


        .expense-item:hover {

            border-color: #cbd5e1;

            box-shadow:
                0 8px 20px rgba(15, 23, 42, .05);

        }


        @keyframes expenseIn {

            from {

                opacity: 0;

                transform: translateY(5px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }


        .expense-header {

            min-height: 45px;

            padding: 8px 13px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            background:
                linear-gradient(
                    90deg,
                    #f8fafc,
                    #ffffff
                );

            border-bottom: 1px solid var(--liq-border);

        }


        .expense-title {

            display: flex;

            align-items: center;

            gap: 9px;

            font-family: "Space Grotesk", sans-serif;

            font-size: 13px;

            font-weight: 700;

        }


        .expense-number {

            width: 27px;

            height: 27px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #dbeafe;

            color: #1d4ed8;

            font-family:
                "JetBrains Mono",
                monospace;

            font-size: 11px;

            font-weight: 800;

        }


        .expense-id {

            color: #94a3b8;

            font-family:
                "JetBrains Mono",
                monospace;

            font-size: 9px;

            font-weight: 500;

        }


        .expense-body {

            padding: 14px;

        }


        .expense-row {

            display: grid;

            grid-template-columns:
                1fr
                1fr
                1.2fr
                1.2fr;

            gap: 11px;

            margin-bottom: 11px;

        }


        .expense-row:last-child {

            margin-bottom: 0;

        }


        .expense-row.row-2 {

            grid-template-columns:
                1.3fr
                1.1fr
                1fr
                1fr;

        }


        .expense-row.row-3 {

            grid-template-columns:
                1.15fr
                1.15fr
                1.5fr
                1fr;

        }


        .expense-field {

            min-width: 0;

        }


        /* ================================================================
           RECEIPT
        ================================================================ */

        .receipt-box {

            min-height: 70px;

            padding: 8px;

            border: 1px dashed #cbd5e1;

            border-radius: 9px;

            background: #f8fafc;

        }


        .receipt-input {

            width: 100%;

            font-size: 11px;

            color: #64748b;

        }


        .receipt-input::file-selector-button {

            margin-right: 7px;

            padding: 5px 8px;

            border: 0;

            border-radius: 6px;

            background: #e2e8f0;

            color: #334155;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;

        }


        .receipt-preview {

            width: 100%;

            height: 85px;

            margin-top: 7px;

            display: none;

            object-fit: contain;

            border-radius: 7px;

            border: 1px solid var(--liq-border);

            background: white;

        }


        .existing-receipt {

            display: flex;

            align-items: center;

            gap: 6px;

            margin-top: 7px;

            padding: 6px 8px;

            border-radius: 7px;

            background: var(--liq-soft-green);

            color: #166534;

            font-size: 10px;

            font-weight: 700;

        }


        .ocr-status {

            display: none;

            margin-top: 6px;

            padding: 5px 7px;

            border-radius: 6px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 10px;

        }


        /* ================================================================
           AMOUNT SUMMARY
        ================================================================ */

        .row-summary {

            min-height: 68px;

            padding: 10px;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc,
                    #f1f5f9
                );

            border: 1px solid var(--liq-border);

        }


        .row-summary-line {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 10px;

        }


        .row-summary-label {

            color: #94a3b8;

            font-size: 9px;

            text-transform: uppercase;

            font-weight: 800;

        }


        .row-summary-value {

            font-family:
                "JetBrains Mono",
                monospace;

            font-size: 12px;

            font-weight: 700;

        }


        .row-summary-divider {

            margin: 7px 0;

            border-top: 1px solid #e2e8f0;

        }


        /* ================================================================
           TOTALS
        ================================================================ */

        .totals-panel {

            display: grid;

            grid-template-columns:
                1.4fr 1fr 1fr;

            gap: 10px;

            margin-top: 17px;

            padding: 13px;

            border: 1px solid var(--liq-border);

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc,
                    #eef2ff
                );

        }


        .total-box {

            padding: 7px 10px;

        }


        .total-box + .total-box {

            border-left: 1px solid #dbe3ef;

        }


        .total-label {

            color: #64748b;

            font-size: 10px;

            text-transform: uppercase;

            font-weight: 800;

        }


        .total-value {

            margin-top: 4px;

            font-family:
                "JetBrains Mono",
                monospace;

            font-size: 17px;

            font-weight: 700;

        }


        /* ================================================================
           SUMMARY
        ================================================================ */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 12px;

        }


        .summary-card {

            position: relative;

            padding: 15px;

            border: 1px solid var(--liq-border);

            border-radius: 12px;

            background: white;

            overflow: hidden;

        }


        .summary-card::after {

            content: "";

            position: absolute;

            right: -15px;

            top: -15px;

            width: 60px;

            height: 60px;

            border-radius: 50%;

            background: #eff6ff;

        }


        .summary-card.green::after {

            background: #f0fdf4;

        }


        .summary-card.orange::after {

            background: #fff7ed;

        }


        .summary-card.red::after {

            background: #fef2f2;

        }


        .summary-label {

            position: relative;

            z-index: 1;

            color: #64748b;

            font-size: 10px;

            text-transform: uppercase;

            font-weight: 800;

        }


        .summary-value {

            position: relative;

            z-index: 1;

            margin-top: 7px;

            font-family:
                "JetBrains Mono",
                monospace;

            font-size: 18px;

            font-weight: 700;

        }


        .summary-sub {

            position: relative;

            z-index: 1;

            margin-top: 4px;

            color: #94a3b8;

            font-size: 10px;

        }


        /* ================================================================
           FOOTER
        ================================================================ */

        .liq-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 17px 20px;

            background: #f8fafc;

            border-top: 1px solid var(--liq-border);

        }


        .last-updated {

            color: #94a3b8;

            font-size: 10px;

        }


        .footer-actions {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        /* ================================================================
           RESPONSIVE
        ================================================================ */

        @media (max-width: 1200px) {

            .expense-row,
            .expense-row.row-2,
            .expense-row.row-3 {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .summary-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .report-meta {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 800px) {

            .liq-page {

                padding: 15px;

            }


            .liq-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .liq-header-actions {

                width: 100%;

            }


            .liq-header-actions .liq-btn {

                flex: 1;

            }


            .liq-col-3,
            .liq-col-4,
            .liq-col-6,
            .liq-col-12 {

                grid-column: span 12;

            }


            .expense-row,
            .expense-row.row-2,
            .expense-row.row-3 {

                grid-template-columns: 1fr;

            }


            .summary-grid {

                grid-template-columns: 1fr;

            }


            .report-meta {

                grid-template-columns: 1fr;

            }


            .totals-panel {

                grid-template-columns: 1fr;

            }


            .total-box + .total-box {

                border-left: 0;

                border-top: 1px solid #dbe3ef;

            }


            .liq-footer {

                flex-direction: column;

                align-items: stretch;

            }


            .footer-actions {

                width: 100%;

            }


            .footer-actions .liq-btn {

                flex: 1;

            }

        }

    </style>


    <div class="liq-page">

        <div class="liq-container">


            {{-- =========================================================
                 HEADER
            ========================================================== --}}

            <div class="liq-header">

                <div class="liq-header-left">

                    <div class="liq-header-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 3v18"/>
                            <path d="M5 8h14"/>
                            <path d="M5 16h14"/>
                        </svg>

                    </div>


                    <div>

                        <h1 class="liq-title">
                            Edit Liquidation Report
                        </h1>

                        <div class="liq-subtitle">

                            Update and review liquidation
                            <span class="liq-mono">
                                #{{ $liquidationId }}
                            </span>

                            <span style="margin:0 7px;">
                                •
                            </span>

                            <span class="liq-status">

                                <span class="liq-status-dot"></span>

                                {{ $status }}

                            </span>

                        </div>

                    </div>

                </div>


                <div class="liq-header-actions">

                    <a
                        href="{{ route('liquidation.show', $liquidationId) }}"
                        class="liq-btn liq-btn-secondary"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>

                        View Report

                    </a>


                    <a
                        href="{{ route('liquidation.index') }}"
                        class="liq-btn liq-btn-secondary"
                    >

                        Back to List

                    </a>

                </div>

            </div>


            {{-- =========================================================
                 ERRORS
            ========================================================== --}}

            @if(session('error'))

                <div class="liq-alert liq-alert-danger">

                    {{ session('error') }}

                </div>

            @endif


            @if($errors->any())

                <div class="liq-alert liq-alert-danger">

                    <strong>
                        Please correct the following:
                    </strong>

                    <ul style="margin:7px 0 0 18px;">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- =========================================================
                 FORM
            ========================================================== --}}

            <form
                action="{{ route('liquidation.update', $liquidationId) }}"
                method="POST"
                enctype="multipart/form-data"
                id="liquidationForm"
            >

                @csrf

                @method('PUT')


                {{-- =====================================================
                     REPORT INFORMATION
                ====================================================== --}}

                <div class="liq-card">

                    <div class="liq-card-header">

                        <div class="liq-card-header-left">

                            <div class="liq-card-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M4 4h16v16H4z"/>
                                    <path d="M8 8h8"/>
                                    <path d="M8 12h8"/>
                                    <path d="M8 16h5"/>
                                </svg>

                            </div>

                            <div>

                                <h2 class="liq-card-title">
                                    Liquidation Information
                                </h2>

                                <div class="liq-card-description">
                                    Basic information and cash advance details
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="liq-card-body">

                        <div class="liq-grid">


                            {{-- TITLE --}}

                            <div class="liq-col-6">

                                <label class="liq-label">

                                    Report Title

                                    <span class="liq-required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    name="report_title"
                                    class="liq-input"
                                    value="{{ $reportTitle }}"
                                    placeholder="Enter liquidation report title"
                                    required
                                >

                            </div>


                            {{-- DATE --}}

                            <div class="liq-col-3">

                                <label class="liq-label">

                                    Date Prepared

                                    <span class="liq-required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    name="date_prepared"
                                    class="liq-input"
                                    value="{{ $datePrepared }}"
                                    required
                                >

                            </div>


                            {{-- RATE --}}

                            <div class="liq-col-3">

                                <label class="liq-label">

                                    Exchange Rate

                                    <span class="liq-required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    name="exchange_rate"
                                    id="exchangeRate"
                                    class="liq-input liq-mono"
                                    value="{{ $exchangeRate }}"
                                    required
                                >

                                <div class="liq-help">
                                    VND per USD
                                </div>

                            </div>


                            {{-- PCF --}}

                            <div class="liq-col-4">

                                <label class="liq-label">
                                    PCF / Cash Advance
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="pcf_amount"
                                    id="pcfAmount"
                                    class="liq-input liq-mono"
                                    value="{{ $pcfAmount }}"
                                    placeholder="0.00"
                                >

                                <div class="liq-help">
                                    Optional cash advance in VND.
                                </div>

                            </div>


                            {{-- PREPARED BY --}}

                            <div class="liq-col-4">

                                <label class="liq-label">
                                    Prepared By
                                </label>

                                <input
                                    type="text"
                                    class="liq-input"
                                    value="{{ $report->preparer?->name ?? $currentUserName }}"
                                    readonly
                                >

                            </div>


                            {{-- COMPANY --}}

                            <div class="liq-col-4">

                                <label class="liq-label">
                                    Company
                                </label>

                                <input
                                    type="text"
                                    class="liq-input"
                                    value="{{ $report->company?->name ?? '—' }}"
                                    readonly
                                >

                            </div>

                        </div>


                        {{-- REPORT META --}}

                        <div class="report-meta">

                            <div class="meta-box">

                                <div class="meta-label">
                                    Report ID
                                </div>

                                <div class="meta-value liq-mono">
                                    #{{ $liquidationId }}
                                </div>

                            </div>


                            <div class="meta-box">

                                <div class="meta-label">
                                    Status
                                </div>

                                <div class="meta-value">
                                    {{ $status }}
                                </div>

                            </div>


                            <div class="meta-box">

                                <div class="meta-label">
                                    Expenses
                                </div>

                                <div
                                    class="meta-value"
                                    id="expenseCount"
                                >
                                    {{ count($existingItems) }}
                                    item(s)
                                </div>

                            </div>


                            <div class="meta-box">

                                <div class="meta-label">
                                    Last Updated
                                </div>

                                <div class="meta-value">

                                    {{ $report->updated_at?->format('M d, Y h:i A') ?? '—' }}

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     EXPENSE DETAILS
                ====================================================== --}}

                <div class="liq-card">

                    <div class="liq-card-header">

                        <div class="liq-card-header-left">

                            <div class="liq-card-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M4 4h16v16H4z"/>
                                    <path d="M8 8h8"/>
                                    <path d="M8 12h5"/>
                                    <path d="M8 16h8"/>
                                </svg>

                            </div>

                            <div>

                                <h2 class="liq-card-title">
                                    Expense Details
                                </h2>

                                <div class="liq-card-description">
                                    Review each expense, receipt and amount.
                                </div>

                            </div>

                        </div>


                        <button
                            type="button"
                            class="liq-btn liq-btn-primary"
                            id="addExpenseBtn"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 5v14"/>
                                <path d="M5 12h14"/>
                            </svg>

                            Add Expense

                        </button>

                    </div>


                    <div class="liq-card-body">

                        <div id="expenseContainer">


                            @foreach($existingItems as $index => $item)

                                @php

                                    $itemDate = old(
                                        "items.$index.item_date",
                                        $item['item_date'] ?? $datePrepared
                                    );

                                    $requestedByValue = old(
                                        "items.$index.requested_by",
                                        $item['requested_by_name'] ?? $item['requested_by'] ?? $currentUserName
                                    );

                                    $requestedByIsKnown = in_array($requestedByValue, $requestedByOptions, true);

                                    $accountBuyerValue = old(
                                        "items.$index.account_buyer",
                                        $item['account_buyer'] ?? ''
                                    );

                                    $accountBuyerIsKnown = in_array($accountBuyerValue, $accountBuyerOptions, true);

                                @endphp


                                <div
                                    class="expense-item"
                                    data-index="{{ $index }}"
                                >


                                    {{-- EXPENSE HEADER --}}

                                    <div class="expense-header">

                                        <div class="expense-title">

                                            <span class="expense-number">
                                                {{ $index + 1 }}
                                            </span>

                                            <span>
                                                Expense {{ $index + 1 }}
                                            </span>

                                            @if(!empty($item['id']))

                                                <span class="expense-id">
                                                    #{{ $item['id'] }}
                                                </span>

                                            @endif

                                        </div>


                                        <button
                                            type="button"
                                            class="liq-btn liq-btn-danger remove-expense"
                                        >

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                            </svg>

                                            Remove

                                        </button>

                                    </div>


                                    <div class="expense-body">


                                        {{-- =================================================
                                             ROW 1
                                        ================================================== --}}

                                        <div class="expense-row">


                                            {{-- REF --}}

                                            <div class="expense-field">

                                                <label class="liq-label">

                                                    Reference No.

                                                    <span class="liq-required">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="text"
                                                    name="items[{{ $index }}][ref_no]"
                                                    class="liq-input liq-mono ref-no"
                                                    value="{{ old("items.$index.ref_no", $item['ref_no'] ?? '') }}"
                                                    placeholder="LF-YYYYMMDD-001"
                                                    required
                                                >

                                            </div>


                                            {{-- DATE --}}

                                            <div class="expense-field">

                                                <label class="liq-label">

                                                    Expense Date

                                                    <span class="liq-required">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="date"
                                                    name="items[{{ $index }}][item_date]"
                                                    class="liq-input item-date"
                                                    value="{{ $itemDate }}"
                                                    required
                                                >

                                            </div>


                                            {{-- REQUESTED BY --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Requested By
                                                </label>

                                                <div class="other-toggle-container">

                                                    <select
                                                        name="items[{{ $index }}][requested_by]"
                                                        class="liq-select other-toggle-select"
                                                        data-field="requested_by"
                                                        required
                                                    >

                                                        <option value="">
                                                            Select requested by
                                                        </option>

                                                        @foreach($requestedByOptions as $name)

                                                            <option
                                                                value="{{ $name }}"
                                                                @selected($requestedByValue === $name)
                                                            >
                                                                {{ $name }}
                                                            </option>

                                                        @endforeach

                                                        <option
                                                            value="__other__"
                                                            @selected($requestedByValue !== '' && !$requestedByIsKnown)
                                                        >
                                                            Other (Manual Entry)
                                                        </option>

                                                    </select>

                                                    <input
                                                        type="text"
                                                        class="liq-input other-toggle-input"
                                                        placeholder="Enter name"
                                                        value="{{ !$requestedByIsKnown ? $requestedByValue : '' }}"
                                                        style="margin-top:8px; display:{{ !$requestedByIsKnown ? 'block' : 'none' }};"
                                                    >

                                                </div>

                                            </div>


                                            {{-- PAYEE --}}

                                            <div class="expense-field">

                                                <label class="liq-label">

                                                    Payee

                                                    <span class="liq-required">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="text"
                                                    name="items[{{ $index }}][payee]"
                                                    class="liq-input payee"
                                                    value="{{ old("items.$index.payee", $item['payee'] ?? '') }}"
                                                    placeholder="Person / company paid"
                                                    required
                                                >

                                            </div>

                                        </div>


                                        {{-- =================================================
                                             ROW 2
                                        ================================================== --}}

                                        <div class="expense-row row-2">


                                            {{-- CLASSIFICATION --}}

                                            <div class="expense-field">

                                                <label class="liq-label">

                                                    Expense Classification

                                                    <span class="liq-required">
                                                        *
                                                    </span>

                                                </label>

                                                <select
                                                    name="items[{{ $index }}][expense_type]"
                                                    class="liq-select expense-type"
                                                    required
                                                >

                                                    <option value="">
                                                        Select classification
                                                    </option>


                                                    @foreach($expenseClassifications as $group => $types)

                                                        <optgroup
                                                            label="{{ $group }}"
                                                        >

                                                            @foreach($types as $type => $description)

                                                                <option
                                                                    value="{{ $type }}"
                                                                    @selected(
                                                                        old(
                                                                            "items.$index.expense_type",
                                                                            $item['expense_type'] ?? ''
                                                                        ) === $type
                                                                    )
                                                                >

                                                                    {{ $type }}

                                                                </option>

                                                            @endforeach

                                                        </optgroup>

                                                    @endforeach

                                                </select>

                                            </div>


                                            {{-- ACCOUNT --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Account / Buyer
                                                </label>

                                                <div class="other-toggle-container">

                                                    <select
                                                        name="items[{{ $index }}][account_buyer]"
                                                        class="liq-select other-toggle-select"
                                                        data-field="account_buyer"
                                                        required
                                                    >

                                                        <option value="">
                                                            Select account / buyer
                                                        </option>

                                                        @foreach($accountBuyerOptions as $account)

                                                            <option
                                                                value="{{ $account }}"
                                                                @selected($accountBuyerValue === $account)
                                                            >
                                                                {{ $account }}
                                                            </option>

                                                        @endforeach

                                                        <option
                                                            value="__other__"
                                                            @selected($accountBuyerValue !== '' && !$accountBuyerIsKnown)
                                                        >
                                                            Other (Manual Entry)
                                                        </option>

                                                    </select>

                                                    <input
                                                        type="text"
                                                        class="liq-input other-toggle-input"
                                                        placeholder="Enter buyer / account"
                                                        value="{{ ($accountBuyerValue !== '' && !$accountBuyerIsKnown) ? $accountBuyerValue : '' }}"
                                                        style="margin-top:8px; display:{{ ($accountBuyerValue !== '' && !$accountBuyerIsKnown) ? 'block' : 'none' }};"
                                                    >

                                                </div>

                                            </div>


                                            {{-- VND --}}

                                            <div class="expense-field">

                                                <label class="liq-label">

                                                    Amount VND

                                                    <span class="liq-required">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    name="items[{{ $index }}][amount_vnd]"
                                                    class="liq-input liq-mono amount-vnd"
                                                    value="{{ old("items.$index.amount_vnd", $item['amount_vnd'] ?? '') }}"
                                                    placeholder="0.00"
                                                    required
                                                >

                                            </div>


                                            {{-- USD --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Amount USD
                                                </label>

                                                <input
                                                    type="text"
                                                    class="liq-input liq-mono amount-usd"
                                                    readonly
                                                    placeholder="$0.00"
                                                >

                                            </div>

                                        </div>


                                        {{-- =================================================
                                             ROW 3
                                        ================================================== --}}

                                        <div class="expense-row row-3">


                                            {{-- RECEIPT --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Receipt
                                                </label>

                                                <div class="receipt-box">

                                                    <input
                                                        type="file"
                                                        name="items[{{ $index }}][receipt_image]"
                                                        class="receipt-input"
                                                        accept="image/*"
                                                    >


                                                    @if(!empty($item['receipt_image']))

                                                        <div class="existing-receipt">

                                                            ✓ Existing receipt attached

                                                        </div>

                                                    @endif


                                                    <img
                                                        class="receipt-preview"
                                                        alt="Receipt preview"
                                                    >


                                                    <div class="ocr-status">
                                                        Scanning receipt...
                                                    </div>

                                                </div>

                                            </div>


                                            {{-- OCR --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    OCR / Reference
                                                </label>

                                                <input
                                                    type="text"
                                                    class="liq-input ocr-reference"
                                                    value="{{ old("items.$index.ref_no", $item['ref_no'] ?? '') }}"
                                                    placeholder="Detected reference number"
                                                >

                                                <div class="liq-help">
                                                    Upload a receipt to scan its reference number.
                                                </div>

                                            </div>


                                            {{-- REMARKS --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Remarks
                                                </label>

                                                <textarea
                                                    name="items[{{ $index }}][remarks]"
                                                    class="liq-textarea"
                                                    placeholder="Additional remarks..."
                                                >{{ old("items.$index.remarks", $item['remarks'] ?? '') }}</textarea>

                                            </div>


                                            {{-- SUMMARY --}}

                                            <div class="expense-field">

                                                <label class="liq-label">
                                                    Expense Summary
                                                </label>

                                                <div class="row-summary">

                                                    <div class="row-summary-line">

                                                        <span class="row-summary-label">
                                                            VND
                                                        </span>

                                                        <span
                                                            class="row-summary-value row-vnd"
                                                        >
                                                            0.00
                                                        </span>

                                                    </div>


                                                    <div class="row-summary-divider"></div>


                                                    <div class="row-summary-line">

                                                        <span class="row-summary-label">
                                                            USD
                                                        </span>

                                                        <span
                                                            class="row-summary-value row-usd"
                                                        >
                                                            $0.00
                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        {{-- =====================================================
                             TOTALS
                        ====================================================== --}}

                        <div class="totals-panel">


                            <div class="total-box">

                                <div class="total-label">
                                    Total Expenses
                                </div>

                                <div
                                    class="total-value"
                                    style="font-size:14px;"
                                >
                                    Overall liquidation expenses
                                </div>

                            </div>


                            <div class="total-box">

                                <div class="total-label">
                                    Total VND
                                </div>

                                <div
                                    id="grandTotalVnd"
                                    class="total-value"
                                >
                                    0.00
                                </div>

                            </div>


                            <div class="total-box">

                                <div class="total-label">
                                    Total USD
                                </div>

                                <div
                                    id="grandTotalUsd"
                                    class="total-value"
                                >
                                    $0.00
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     SUMMARY
                ====================================================== --}}

                <div class="liq-card">

                    <div class="liq-card-header">

                        <div class="liq-card-header-left">

                            <div class="liq-card-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"/>
                                    <path d="M7 16l4-5 3 3 5-7"/>
                                </svg>

                            </div>

                            <div>

                                <h2 class="liq-card-title">
                                    Liquidation Summary
                                </h2>

                                <div class="liq-card-description">
                                    Real-time calculation based on your expenses.
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="liq-card-body">

                        <div class="summary-grid">


                            {{-- PCF --}}

                            <div class="summary-card">

                                <div class="summary-label">
                                    PCF / Cash Advance
                                </div>

                                <div
                                    id="summaryPcf"
                                    class="summary-value"
                                >
                                    ₫0.00
                                </div>

                                <div class="summary-sub">
                                    Cash received
                                </div>

                            </div>


                            {{-- EXPENSES --}}

                            <div class="summary-card orange">

                                <div class="summary-label">
                                    Total Expenses
                                </div>

                                <div
                                    id="summaryExpenses"
                                    class="summary-value"
                                >
                                    ₫0.00
                                </div>

                                <div class="summary-sub">
                                    Total liquidation amount
                                </div>

                            </div>


                            {{-- CASH --}}

                            <div class="summary-card green">

                                <div class="summary-label">
                                    Cash on Hand
                                </div>

                                <div
                                    id="summaryCash"
                                    class="summary-value"
                                >
                                    ₫0.00
                                </div>

                                <div class="summary-sub">
                                    Remaining balance
                                </div>

                            </div>


                            {{-- RATE --}}

                            <div class="summary-card">

                                <div class="summary-label">
                                    Exchange Rate
                                </div>

                                <div
                                    id="summaryRate"
                                    class="summary-value"
                                >
                                    0.0000
                                </div>

                                <div class="summary-sub">
                                    VND per USD
                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =====================================================
                         FOOTER
                    ====================================================== --}}

                    <div class="liq-footer">

                        <div class="last-updated">

                            Last updated:

                            <strong>
                                {{ $report->updated_at?->format('M d, Y h:i A') ?? '—' }}
                            </strong>

                        </div>


                        <div class="footer-actions">

                            <a
                                href="{{ route('liquidation.index') }}"
                                class="liq-btn liq-btn-secondary"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="liq-btn liq-btn-primary"
                                id="saveBtn"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M5 4h11l3 3v13H5z"/>
                                    <path d="M8 4v6h8V4"/>
                                    <path d="M8 20v-6h8v6"/>
                                </svg>

                                Save Changes

                            </button>

                        </div>

                    </div>

                </div>


            </form>

        </div>

    </div>


    {{-- ================================================================
         JAVASCRIPT
    ================================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {


                const container =
                    document.getElementById(
                        'expenseContainer'
                    );


                const addExpenseBtn =
                    document.getElementById(
                        'addExpenseBtn'
                    );


                const form =
                    document.getElementById(
                        'liquidationForm'
                    );


                const exchangeRateInput =
                    document.getElementById(
                        'exchangeRate'
                    );


                const pcfAmountInput =
                    document.getElementById(
                        'pcfAmount'
                    );


                const currentUserId =
                    @json($currentUserId);


                const currentUserName =
                    @json($currentUserName);


                const classificationData =
                    @json($expenseClassifications);


                const requestedByOptions =
                    @json($requestedByOptions);


                const accountBuyerOptions =
                    @json($accountBuyerOptions);


                /* =========================================================
                   NUMBER
                ========================================================== */

                function number(value) {

                    const parsed =
                        parseFloat(value);

                    return Number.isFinite(parsed)
                        ? parsed
                        : 0;

                }


                /* =========================================================
                   MONEY
                ========================================================== */

                function money(value) {

                    return number(value)
                        .toLocaleString(
                            'en-US',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );

                }


                function usd(value) {

                    return '$' + money(value);

                }


                function escapeHtml(value) {

                    return String(value ?? '')

                        .replace(
                            /&/g,
                            '&amp;'
                        )

                        .replace(
                            /</g,
                            '&lt;'
                        )

                        .replace(
                            />/g,
                            '&gt;'
                        )

                        .replace(
                            /"/g,
                            '&quot;'
                        )

                        .replace(
                            /'/g,
                            '&#039;'
                        );

                }


                function getExchangeRate() {

                    return number(
                        exchangeRateInput?.value
                    );

                }


                /* =========================================================
                   OTHER / MANUAL-ENTRY TOGGLE
                   (used by Requested By and Account / Buyer dropdowns)
                ========================================================== */

                function getRowIndex(el) {

                    const row =
                        el.closest('.expense-item');

                    return row
                        ? (parseInt(row.dataset.index, 10) || 0)
                        : 0;

                }


                function bindOtherToggle(toggleContainer) {

                    const select =
                        toggleContainer.querySelector(
                            '.other-toggle-select'
                        );

                    const otherInput =
                        toggleContainer.querySelector(
                            '.other-toggle-input'
                        );

                    if (!select || !otherInput) {

                        return;

                    }

                    const field =
                        select.dataset.field;

                    function sync() {

                        const idx =
                            getRowIndex(select);

                        const fieldName =
                            `items[${idx}][${field}]`;

                        if (select.value === '__other__') {

                            select.removeAttribute('name');

                            otherInput.name = fieldName;
                            otherInput.style.display = 'block';
                            otherInput.required = true;

                        } else {

                            select.name = fieldName;

                            otherInput.removeAttribute('name');
                            otherInput.style.display = 'none';
                            otherInput.required = false;

                        }

                    }

                    select.addEventListener(
                        'change',
                        function () {

                            otherInput.value = '';

                            sync();

                        }
                    );

                    sync();

                }


                function bindOtherToggles(scope) {

                    scope
                        .querySelectorAll(
                            '.other-toggle-container'
                        )
                        .forEach(bindOtherToggle);

                }


                function buildOtherToggleOptions(options) {

                    let html =
                        '';

                    options.forEach(
                        function (value) {

                            html +=

                                '<option value="' +
                                escapeHtml(value) +
                                '">' +
                                escapeHtml(value) +
                                '</option>';

                        }
                    );

                    html +=
                        '<option value="__other__">Other (Manual Entry)</option>';

                    return html;

                }


                /* =========================================================
                   CALCULATE ROW
                ========================================================== */

                function calculateRow(item) {

                    const vndInput =
                        item.querySelector(
                            '.amount-vnd'
                        );


                    const usdInput =
                        item.querySelector(
                            '.amount-usd'
                        );


                    const rowVnd =
                        item.querySelector(
                            '.row-vnd'
                        );


                    const rowUsd =
                        item.querySelector(
                            '.row-usd'
                        );


                    const vnd =
                        number(
                            vndInput?.value
                        );


                    const rate =
                        getExchangeRate();


                    const converted =
                        rate > 0
                            ? vnd / rate
                            : 0;


                    if (usdInput) {

                        usdInput.value =
                            converted > 0
                                ? converted.toFixed(2)
                                : '';

                    }


                    if (rowVnd) {

                        rowVnd.textContent =
                            money(vnd);

                    }


                    if (rowUsd) {

                        rowUsd.textContent =
                            usd(converted);

                    }


                    return {

                        vnd,

                        usd: converted

                    };

                }


                /* =========================================================
                   TOTALS
                ========================================================== */

                function calculateTotals() {

                    let totalVnd = 0;

                    let totalUsd = 0;


                    const items =
                        container.querySelectorAll(
                            '.expense-item'
                        );


                    items.forEach(
                        function (item) {

                            const result =
                                calculateRow(item);

                            totalVnd +=
                                result.vnd;

                            totalUsd +=
                                result.usd;

                        }
                    );


                    const grandVnd =
                        document.getElementById(
                            'grandTotalVnd'
                        );


                    const grandUsd =
                        document.getElementById(
                            'grandTotalUsd'
                        );


                    if (grandVnd) {

                        grandVnd.textContent =
                            money(totalVnd);

                    }


                    if (grandUsd) {

                        grandUsd.textContent =
                            usd(totalUsd);

                    }


                    const pcf =
                        number(
                            pcfAmountInput?.value
                        );


                    const cash =
                        pcf - totalVnd;


                    const summaryPcf =
                        document.getElementById(
                            'summaryPcf'
                        );


                    const summaryExpenses =
                        document.getElementById(
                            'summaryExpenses'
                        );


                    const summaryCash =
                        document.getElementById(
                            'summaryCash'
                        );


                    const summaryRate =
                        document.getElementById(
                            'summaryRate'
                        );


                    if (summaryPcf) {

                        summaryPcf.textContent =
                            '₫' + money(pcf);

                    }


                    if (summaryExpenses) {

                        summaryExpenses.textContent =
                            '₫' + money(totalVnd);

                    }


                    if (summaryCash) {

                        summaryCash.textContent =
                            '₫' + money(cash);

                    }


                    if (summaryRate) {

                        summaryRate.textContent =
                            getExchangeRate()
                                ? money(getExchangeRate())
                                : '0.0000';

                    }


                    /*
                     | Update expense count
                     */

                    const expenseCount =
                        document.getElementById(
                            'expenseCount'
                        );


                    if (expenseCount) {

                        expenseCount.textContent =
                            items.length + ' item(s)';

                    }

                }


                /* =========================================================
                   RENUMBER
                ========================================================== */

                function renumberExpenses() {

                    const items =
                        container.querySelectorAll(
                            '.expense-item'
                        );


                    items.forEach(
                        function (item, index) {

                            item.dataset.index =
                                index;


                            const number =
                                item.querySelector(
                                    '.expense-number'
                                );


                            if (number) {

                                number.textContent =
                                    index + 1;

                            }


                            const title =
                                item.querySelector(
                                    '.expense-title > span:nth-child(2)'
                                );


                            if (title) {

                                title.textContent =
                                    'Expense ' +
                                    (index + 1);

                            }


                            item.querySelectorAll(
                                '[name]'
                            ).forEach(
                                function (field) {

                                    const name =
                                        field.getAttribute(
                                            'name'
                                        );


                                    if (!name) {

                                        return;

                                    }


                                    field.setAttribute(

                                        'name',

                                        name.replace(

                                            /items\[\d+\]/,

                                            'items[' +
                                            index +
                                            ']'

                                        )

                                    );

                                }
                            );

                        }
                    );

                }


                /* =========================================================
                   CLASSIFICATION OPTIONS
                ========================================================== */

                function buildClassificationOptions() {

                    let html =
                        '<option value="">Select classification</option>';


                    Object.entries(
                        classificationData
                    ).forEach(
                        function ([group, types]) {

                            html +=
                                '<optgroup label="' +
                                escapeHtml(group) +
                                '">';


                            Object.entries(types)
                                .forEach(
                                    function ([type]) {

                                        html +=

                                            '<option value="' +
                                            escapeHtml(type) +
                                            '">' +
                                            escapeHtml(type) +
                                            '</option>';

                                    }
                                );


                            html +=
                                '</optgroup>';

                        }
                    );


                    return html;

                }


                /* =========================================================
                   CREATE EXPENSE
                ========================================================== */

                function createExpense() {

                    const index =
                        container.querySelectorAll(
                            '.expense-item'
                        ).length;


                    const wrapper =
                        document.createElement(
                            'div'
                        );


                    wrapper.className =
                        'expense-item';


                    wrapper.dataset.index =
                        index;


                    const defaultDate =
                        document.querySelector(
                            '[name="date_prepared"]'
                        )?.value || '';


                    wrapper.innerHTML = `

                        <div class="expense-header">

                            <div class="expense-title">

                                <span class="expense-number">
                                    ${index + 1}
                                </span>

                                <span>
                                    Expense ${index + 1}
                                </span>

                                <span class="expense-id">
                                    NEW
                                </span>

                            </div>

                            <button
                                type="button"
                                class="liq-btn liq-btn-danger remove-expense"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4h8v2"/>
                                    <path d="M19 6l-1 14H6L5 6"/>
                                </svg>

                                Remove

                            </button>

                        </div>


                        <div class="expense-body">


                            <div class="expense-row">

                                <div class="expense-field">

                                    <label class="liq-label">
                                        Reference No.
                                        <span class="liq-required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="items[${index}][ref_no]"
                                        class="liq-input liq-mono ref-no"
                                        placeholder="LF-YYYYMMDD-001"
                                        required
                                    >

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Expense Date
                                        <span class="liq-required">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        name="items[${index}][item_date]"
                                        class="liq-input item-date"
                                        value="${escapeHtml(defaultDate)}"
                                        required
                                    >

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Requested By
                                    </label>

                                    <div class="other-toggle-container">

                                        <select
                                            name="items[${index}][requested_by]"
                                            class="liq-select other-toggle-select"
                                            data-field="requested_by"
                                            required
                                        >

                                            <option value="">Select requested by</option>

                                            ${buildOtherToggleOptions(requestedByOptions)}

                                        </select>

                                        <input
                                            type="text"
                                            class="liq-input other-toggle-input"
                                            placeholder="Enter name"
                                            style="margin-top:8px; display:none;"
                                        >

                                    </div>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Payee
                                        <span class="liq-required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="items[${index}][payee]"
                                        class="liq-input payee"
                                        placeholder="Person / company paid"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="expense-row row-2">

                                <div class="expense-field">

                                    <label class="liq-label">
                                        Expense Classification
                                        <span class="liq-required">*</span>
                                    </label>

                                    <select
                                        name="items[${index}][expense_type]"
                                        class="liq-select expense-type"
                                        required
                                    >

                                        ${buildClassificationOptions()}

                                    </select>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Account / Buyer
                                    </label>

                                    <div class="other-toggle-container">

                                        <select
                                            name="items[${index}][account_buyer]"
                                            class="liq-select other-toggle-select"
                                            data-field="account_buyer"
                                            required
                                        >

                                            <option value="">Select account / buyer</option>

                                            ${buildOtherToggleOptions(accountBuyerOptions)}

                                        </select>

                                        <input
                                            type="text"
                                            class="liq-input other-toggle-input"
                                            placeholder="Enter buyer / account"
                                            style="margin-top:8px; display:none;"
                                        >

                                    </div>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Amount VND
                                        <span class="liq-required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="items[${index}][amount_vnd]"
                                        class="liq-input liq-mono amount-vnd"
                                        placeholder="0.00"
                                        required
                                    >

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Amount USD
                                    </label>

                                    <input
                                        type="text"
                                        class="liq-input liq-mono amount-usd"
                                        readonly
                                        placeholder="$0.00"
                                    >

                                </div>

                            </div>


                            <div class="expense-row row-3">

                                <div class="expense-field">

                                    <label class="liq-label">
                                        Receipt
                                    </label>

                                    <div class="receipt-box">

                                        <input
                                            type="file"
                                            name="items[${index}][receipt_image]"
                                            class="receipt-input"
                                            accept="image/*"
                                        >

                                        <img
                                            class="receipt-preview"
                                            alt="Receipt preview"
                                        >

                                        <div class="ocr-status">
                                            Scanning receipt...
                                        </div>

                                    </div>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        OCR / Reference
                                    </label>

                                    <input
                                        type="text"
                                        class="liq-input ocr-reference"
                                        placeholder="Detected reference number"
                                    >

                                    <div class="liq-help">
                                        OCR can detect a receipt/reference number.
                                    </div>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Remarks
                                    </label>

                                    <textarea
                                        name="items[${index}][remarks]"
                                        class="liq-textarea"
                                        placeholder="Additional remarks..."
                                    ></textarea>

                                </div>


                                <div class="expense-field">

                                    <label class="liq-label">
                                        Expense Summary
                                    </label>

                                    <div class="row-summary">

                                        <div class="row-summary-line">

                                            <span class="row-summary-label">
                                                VND
                                            </span>

                                            <span class="row-summary-value row-vnd">
                                                0.00
                                            </span>

                                        </div>


                                        <div class="row-summary-divider"></div>


                                        <div class="row-summary-line">

                                            <span class="row-summary-label">
                                                USD
                                            </span>

                                            <span class="row-summary-value row-usd">
                                                $0.00
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    `;


                    container.appendChild(
                        wrapper
                    );


                    bindOtherToggles(wrapper);


                    renumberExpenses();

                    calculateTotals();

                }


                /* =========================================================
                   ADD
                ========================================================== */

                addExpenseBtn?.addEventListener(
                    'click',
                    createExpense
                );


                /* =========================================================
                   REMOVE
                ========================================================== */

                container.addEventListener(
                    'click',
                    function (event) {

                        const button =
                            event.target.closest(
                                '.remove-expense'
                            );


                        if (!button) {

                            return;

                        }


                        const items =
                            container.querySelectorAll(
                                '.expense-item'
                            );


                        if (items.length <= 1) {

                            alert(
                                'At least one expense is required.'
                            );

                            return;

                        }


                        const item =
                            button.closest(
                                '.expense-item'
                            );


                        if (item) {

                            item.remove();

                        }


                        renumberExpenses();

                        calculateTotals();

                    }
                );


                /* =========================================================
                   AMOUNT INPUT
                ========================================================== */

                container.addEventListener(
                    'input',
                    function (event) {

                        if (
                            event.target.classList.contains(
                                'amount-vnd'
                            )
                        ) {

                            calculateTotals();

                        }

                    }
                );


                exchangeRateInput?.addEventListener(
                    'input',
                    calculateTotals
                );


                pcfAmountInput?.addEventListener(
                    'input',
                    calculateTotals
                );


                /* =========================================================
                   RECEIPT + OCR
                ========================================================== */

                container.addEventListener(
                    'change',
                    async function (event) {

                        const input =
                            event.target.closest(
                                '.receipt-input'
                            );


                        if (!input) {

                            return;

                        }


                        const file =
                            input.files?.[0];


                        if (!file) {

                            return;

                        }


                        const item =
                            input.closest(
                                '.expense-item'
                            );


                        if (!item) {

                            return;

                        }


                        const preview =
                            item.querySelector(
                                '.receipt-preview'
                            );


                        const status =
                            item.querySelector(
                                '.ocr-status'
                            );


                        const ocrReference =
                            item.querySelector(
                                '.ocr-reference'
                            );


                        /* Preview */

                        if (preview) {

                            const reader =
                                new FileReader();


                            reader.onload =
                                function (e) {

                                    preview.src =
                                        e.target.result;

                                    preview.style.display =
                                        'block';

                                };


                            reader.readAsDataURL(
                                file
                            );

                        }


                        /* OCR unavailable */

                        if (
                            typeof Tesseract ===
                            'undefined'
                        ) {

                            return;

                        }


                        if (status) {

                            status.style.display =
                                'block';

                            status.textContent =
                                'Scanning receipt...';

                        }


                        try {

                            const result =
                                await Tesseract.recognize(
                                    file,
                                    'eng'
                                );


                            const text =
                                result?.data?.text || '';


                            const patterns = [

                                /\bLF[-\s]?\d{6,20}\b/i,

                                /\bREF(?:ERENCE)?[\s:#-]*([A-Z0-9-]{5,30})\b/i,

                                /\b(?:OR|O\.R\.|RECEIPT)[\s:#-]*([A-Z0-9-]{5,30})\b/i,

                            ];


                            let detected = '';


                            for (
                                const pattern of patterns
                            ) {

                                const match =
                                    text.match(
                                        pattern
                                    );


                                if (match) {

                                    detected =
                                        match[1] ||
                                        match[0];

                                    break;

                                }

                            }


                            if (
                                detected &&
                                ocrReference
                            ) {

                                detected =
                                    detected.trim();


                                ocrReference.value =
                                    detected;


                                const refNo =
                                    item.querySelector(
                                        '.ref-no'
                                    );


                                if (
                                    refNo &&
                                    !refNo.value.trim()
                                ) {

                                    refNo.value =
                                        detected;

                                }

                            }


                            if (status) {

                                status.textContent =
                                    detected

                                        ? '✓ Reference number detected.'

                                        : 'Receipt scanned. No reference number detected.';

                            }

                        } catch (error) {

                            console.error(
                                'OCR error:',
                                error
                            );


                            if (status) {

                                status.textContent =
                                    'Unable to scan this receipt.';

                            }

                        }

                    }
                );


                /* =========================================================
                   SUBMIT
                ========================================================== */

                form?.addEventListener(
                    'submit',
                    function () {

                        const saveBtn =
                            document.getElementById(
                                'saveBtn'
                            );


                        if (saveBtn) {

                            saveBtn.disabled =
                                true;

                            saveBtn.style.opacity =
                                '.65';

                            saveBtn.style.cursor =
                                'not-allowed';

                            saveBtn.innerHTML = `

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 2v4"/>
                                    <path d="M12 18v4"/>
                                    <path d="M4.93 4.93l2.83 2.83"/>
                                    <path d="M16.24 16.24l2.83 2.83"/>
                                    <path d="M2 12h4"/>
                                    <path d="M18 12h4"/>
                                </svg>

                                Saving...

                            `;

                        }

                    }
                );


                /* =========================================================
                   INITIAL BIND + CALCULATION
                ========================================================== */

                bindOtherToggles(container);


                container
                    .querySelectorAll(
                        '.expense-item'
                    )
                    .forEach(
                        function (item) {

                            calculateRow(item);

                        }
                    );


                calculateTotals();

            });

    </script>


</x-mi_app>