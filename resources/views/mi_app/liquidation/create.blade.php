<x-mi_app>
    @php
        $saveError = $errors->first('error') ?: session('error');

        /*
        |--------------------------------------------------------------------------
        | Current user (Requested By is locked to whoever is logged in)
        |--------------------------------------------------------------------------
        */
        $currentUserName = optional(auth()->user())->name ?? 'Unknown User';

        /*
        |--------------------------------------------------------------------------
        | Default Items
        |--------------------------------------------------------------------------
        */
        $oldItems = old('items', [
            [
                'item_date'      => '',
                'payee'          => '',
                'expense_type'   => '',
                'account_buyer'  => '',
                'requested_by'   => $currentUserName,
                'amount_vnd'     => '',
                'remarks'        => '',
            ],
        ]);

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

        $presetAccountBuyerOptions = [
            'Action',
            'TJX',
            'World Market',
        ];

        /*
        |--------------------------------------------------------------------------
        | Expense Type Classifications
        |--------------------------------------------------------------------------
        */
        $expenseClassifications = [
            'Transportation & Travel' => [
                'Transportation and Travel' =>
                    'Local transportation and travel',
                'HO - Transportation and Travel' =>
                    'Requests made by overseas travellers',
            ],

            'R&D / Samples' => [
                'RND Expense' =>
                    'Purchase of samples, accessories, props below VND 2.6M',
                'Courier fees' =>
                    'Courier fees for samples',
            ],

            'Utilities & Communication' => [
                'Utilities' =>
                    'Power and water',
                'Communication' =>
                    'Local staff or office SIM cards',
                'HO - Communication' =>
                    'Requests made by overseas travellers',
            ],

            'Entertainment & Meals' => [
                'Entertainment Expense' =>
                    'Meals with or for buyers',
                'Meal Employee' =>
                    'Meals during factory visits / employee meals',
            ],

            'Repairs & Maintenance' => [
                'Repairs & Maintenance' =>
                    'Replacement parts, repairs and maintenance',
            ],

            'Services & Operations' => [
                'Outsourcing services' =>
                    'Cleaners and generic services',
            ],

            'Marketing & Office' => [
                'Advertising & Promotions' =>
                    'Advertising and promotional expenses',
                'Stationery & office supplies' =>
                    'Office supplies and stationery',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Optional dropdown data
        |--------------------------------------------------------------------------
        |
        | These can be passed from your controller later.
        |
        */
        $payeeOptions = collect($payeeOptions ?? [])
            ->filter()
            ->values();

        $accountBuyerOptions = $presetAccountBuyerOptions;
    @endphp


    <style>
        /* ============================================================
           LIQUIDATION FORM
        ============================================================ */

        .tx-page {
            --tx-primary: #111827;
            --tx-secondary: #64748b;
            --tx-border: #e2e8f0;
            --tx-bg: #f8fafc;
            --tx-card: #ffffff;
            --tx-soft: #f1f5f9;
            --tx-blue: #2563eb;
            --tx-green: #059669;
            --tx-red: #dc2626;

            min-height: 100vh;
            background: var(--tx-bg);
            padding: 28px;
            font-family: "Inter", sans-serif;
        }

        .tx-container {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ============================================================
           HEADER
        ============================================================ */

        .tx-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .tx-header-title {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--tx-primary);
            letter-spacing: -0.5px;
        }

        .tx-header-subtitle {
            margin-top: 5px;
            color: var(--tx-secondary);
            font-size: 14px;
        }

        .tx-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ============================================================
           CARD
        ============================================================ */

        .tx-card {
            background: var(--tx-card);
            border: 1px solid var(--tx-border);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .tx-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--tx-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .tx-card-title {
            font-family: "Space Grotesk", sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--tx-primary);
            margin: 0;
        }

        .tx-card-description {
            color: var(--tx-secondary);
            font-size: 12px;
            margin-top: 3px;
        }

        .tx-card-body {
            padding: 22px;
        }

        /* ============================================================
           FORM
        ============================================================ */

        .tx-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .tx-field {
            min-width: 0;
        }

        .tx-field-full {
            grid-column: 1 / -1;
        }

        .tx-label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .tx-label-required::after {
            content: " *";
            color: var(--tx-red);
        }

        .tx-input,
        .tx-select,
        .tx-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: white;
            color: #0f172a;
            font-size: 13px;
            padding: 10px 12px;
            outline: none;
            transition: 0.15s ease;
            box-sizing: border-box;
        }

        .tx-input:focus,
        .tx-select:focus,
        .tx-textarea:focus {
            border-color: var(--tx-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .tx-input[readonly] {
            background: #f8fafc;
            color: #475569;
        }

        .tx-textarea {
            min-height: 70px;
            resize: vertical;
        }

        .tx-help {
            margin-top: 5px;
            font-size: 11px;
            color: #94a3b8;
        }

        /* ============================================================
           READ-ONLY DISPLAY BOX (Requested By, etc.)
        ============================================================ */

        .tx-readonly-box {
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 9px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            box-sizing: border-box;
        }

        .tx-readonly-box svg {
            width: 14px;
            height: 14px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        /* ============================================================
           FOREX
        ============================================================ */

        .tx-forex-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 15px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--tx-border);
        }

        .tx-forex-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tx-forex-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 800;
        }

        .tx-forex-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .tx-forex-rate {
            font-family: "JetBrains Mono", monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .tx-forex-status {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ============================================================
           EXPENSE LIST
        ============================================================ */

        .tx-expense-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* ============================================================
           EXPENSE CARD
        ============================================================ */

        .tx-expense-card {
            border: 1px solid #dbe3ed;
            border-radius: 14px;
            background: white;
            overflow: hidden;
            transition: 0.15s ease;
        }

        .tx-expense-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.05);
        }

        .tx-expense-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .tx-expense-number {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tx-expense-index {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #e2e8f0;
            color: #334155;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: 800;
        }

        .tx-expense-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        .tx-expense-ref {
            margin-top: 2px;
            font-family: "JetBrains Mono", monospace;
            font-size: 11px;
            color: #64748b;
        }

        .tx-remove-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #dc2626;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.15s ease;
        }

        .tx-remove-btn:hover {
            background: #fee2e2;
        }

        /* ============================================================
           ROW LAYOUT
        ============================================================ */

        .tx-expense-body {
            padding: 16px;
        }

        .tx-expense-row {
            display: grid;
            gap: 14px;
            margin-bottom: 14px;
        }

        .tx-expense-row:last-child {
            margin-bottom: 0;
        }

        /* Row 1 */
        .tx-row-identification {
            grid-template-columns: minmax(180px, 0.8fr) minmax(160px, 0.8fr) minmax(200px, 1fr) minmax(200px, 1fr);
        }

        /* Row 2 */
        .tx-row-classification {
            grid-template-columns: minmax(250px, 1.4fr) minmax(220px, 1fr);
        }

        /* Row 3 - VND / USD / Remarks / Remove */
        .tx-row-amount {
            grid-template-columns: minmax(160px, 0.7fr) minmax(160px, 0.7fr) minmax(260px, 1.6fr) auto;
            align-items: end;
        }

        /* Row 4 - Receipt (own full-width row so it can never collide with Remarks) */
        .tx-row-receipt {
            grid-template-columns: 1fr;
        }

        .tx-row-label {
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        /* ============================================================
           REF BADGE
        ============================================================ */

        .tx-ref-box {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 9px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .tx-ref-value {
            font-family: "JetBrains Mono", monospace;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
        }

        .tx-copy-ref {
            border: 0;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            font-size: 11px;
            padding: 3px;
        }

        .tx-copy-ref:hover {
            color: #2563eb;
        }

        /* ============================================================
           USD FIELD
        ============================================================ */

        .tx-usd-wrapper {
            position: relative;
        }

        .tx-usd-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            z-index: 2;
        }

        .tx-usd-input {
            padding-left: 28px !important;
            font-family: "JetBrains Mono", monospace;
        }

        .tx-vnd-wrapper {
            position: relative;
        }

        .tx-vnd-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            z-index: 2;
        }

        .tx-vnd-input {
            padding-left: 32px !important;
            font-family: "JetBrains Mono", monospace;
        }

        /* ============================================================
           RECEIPT UPLOAD + OCR
        ============================================================ */

        .tx-receipt-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            row-gap: 8px;
            min-height: 40px;
        }

        .tx-receipt-input {
            display: none;
        }

        .tx-upload-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 8px 13px;
            border: 1px dashed #94a3b8;
            border-radius: 9px;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            transition: 0.15s ease;
            flex-shrink: 0;
        }

        .tx-upload-btn:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tx-receipt-preview {
            width: 42px;
            height: 42px;
            border-radius: 7px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            display: none;
            flex-shrink: 0;
        }

        .tx-receipt-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tx-receipt-status {
            font-size: 11px;
            color: #64748b;
            flex: 1 1 200px;
            min-width: 160px;
        }

        .tx-receipt-attached {
            color: #059669;
            font-weight: 700;
        }

        .tx-receipt-scanning {
            color: #2563eb;
            font-weight: 700;
        }

        .tx-receipt-scanning::after {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-left: 6px;
            border-radius: 999px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            animation: tx-ocr-spin 0.7s linear infinite;
            vertical-align: middle;
        }

        @keyframes tx-ocr-spin {
            to { transform: rotate(360deg); }
        }

        .tx-receipt-error {
            color: #dc2626;
            font-weight: 700;
        }

        /* ============================================================
           ADD BUTTON
        ============================================================ */

        .tx-add-expense {
            width: 100%;
            margin-top: 18px;
            padding: 13px;
            border: 1px dashed #94a3b8;
            border-radius: 10px;
            background: white;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.15s ease;
        }

        .tx-add-expense:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        /* ============================================================
           TOTAL
        ============================================================ */

        .tx-total-card {
            margin-top: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 17px;
        }

        .tx-total-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .tx-total-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 5px;
        }

        .tx-total-value {
            font-family: "JetBrains Mono", monospace;
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
        }

        /* ============================================================
           ACTIONS
        ============================================================ */

        .tx-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            padding-top: 5px;
        }

        .tx-btn {
            border-radius: 9px;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s ease;
        }

        .tx-btn-secondary {
            border: 1px solid #cbd5e1;
            background: white;
            color: #475569;
        }

        .tx-btn-secondary:hover {
            background: #f8fafc;
        }

        .tx-btn-primary {
            border: 1px solid #1d4ed8;
            background: #2563eb;
            color: white;
        }

        .tx-btn-primary:hover {
            background: #1d4ed8;
        }

        /* ============================================================
           ALERT
        ============================================================ */

        .tx-alert {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #991b1b;
            font-size: 12px;
            font-weight: 600;
        }

        /* ============================================================
           MOBILE
        ============================================================ */

        @media (max-width: 1100px) {
            .tx-row-identification {
                grid-template-columns: 1fr 1fr;
            }

            .tx-row-classification {
                grid-template-columns: 1fr 1fr;
            }

            .tx-row-amount {
                grid-template-columns: 1fr 1fr;
            }

            .tx-row-amount .tx-remove-container {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 700px) {
            .tx-page {
                padding: 15px;
            }

            .tx-header {
                flex-direction: column;
            }

            .tx-header-title {
                font-size: 23px;
            }

            .tx-grid {
                grid-template-columns: 1fr;
            }

            .tx-field-full {
                grid-column: auto;
            }

            .tx-card-body {
                padding: 15px;
            }

            .tx-row-identification,
            .tx-row-classification,
            .tx-row-amount {
                grid-template-columns: 1fr;
            }

            .tx-total-grid {
                grid-template-columns: 1fr;
            }

            .tx-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .tx-btn {
                text-align: center;
            }

            .tx-expense-header {
                align-items: flex-start;
            }

            .tx-remove-btn span {
                display: none;
            }
        }
    </style>


    <div class="tx-page">

        <div class="tx-container">

            {{-- =========================================================
                 HEADER
            ========================================================== --}}

            <div class="tx-header">

                <div>
                    <h1 class="tx-header-title">
                        Liquidation
                    </h1>

                    <div class="tx-header-subtitle">
                        New Liquidation Report
                    </div>
                </div>

                <div class="tx-header-badge">
                    ● Live Forex Conversion
                </div>

            </div>


            {{-- =========================================================
                 ERROR
            ========================================================== --}}

            @if($saveError)
                <div class="tx-alert">
                    {{ $saveError }}
                </div>
            @endif


            {{-- =========================================================
                 FORM
            ========================================================== --}}

            <form
                action="{{ route('liquidation.store') }}"
                method="POST"
                enctype="multipart/form-data"
                id="liquidationForm"
            >

                @csrf


                {{-- =====================================================
                     REPORT DETAILS
                ====================================================== --}}

                <div class="tx-card">

                    <div class="tx-card-header">
                        <div>
                            <div class="tx-card-title">
                                Report Details
                            </div>

                            <div class="tx-card-description">
                                Basic information for this liquidation report
                            </div>
                        </div>
                    </div>

                    <div class="tx-card-body">

                        <div class="tx-grid">

                        {{-- Report Title - Automatic --}}
                        <div class="tx-field">

                            <label class="tx-label tx-label-required">
                                Report Title
                            </label>

                            <input
                                type="text"
                                name="report_title"
                                id="report_title"
                                value="{{ old('report_title') }}"
                                class="tx-input"
                                readonly
                                required
                            >

                            <div class="tx-help">
                                Automatically generated from the Date Prepared.
                            </div>

                        </div>


                            {{-- Date Prepared --}}
                            <div class="tx-field">

                                <label class="tx-label tx-label-required">
                                    Date Prepared
                                </label>

                                <input
                                    type="date"
                                    name="date_prepared"
                                    id="date_prepared"
                                    value="{{ old('date_prepared', date('Y-m-d')) }}"
                                    class="tx-input"
                                    required
                                >

                            </div>


                            {{-- Forex --}}
                            <div class="tx-field tx-field-full">

                                <label class="tx-label">
                                    Forex
                                </label>

                                <div class="tx-forex-box">

                                    <div class="tx-forex-left">

                                        <div class="tx-forex-icon">
                                            $
                                        </div>

                                        <div>
                                            <div class="tx-forex-title">
                                                USD / VND
                                            </div>

                                            <div
                                                class="tx-forex-status"
                                                id="forexStatus"
                                            >
                                                Loading live exchange rate...
                                            </div>
                                        </div>

                                    </div>

                                    <div
                                        class="tx-forex-rate"
                                        id="forexDisplay"
                                    >
                                        Loading...
                                    </div>

                                </div>

                                <input
                                    type="hidden"
                                    name="exchange_rate"
                                    id="exchange_rate"
                                    value=""
                                >

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     EXPENSE ITEMS
                ====================================================== --}}

                <div class="tx-card">

                    <div class="tx-card-header">

                        <div>
                            <div class="tx-card-title">
                                Expense Line Items
                            </div>

                            <div class="tx-card-description">
                                Enter the date and VND amount, or upload a receipt to auto-scan them via OCR.
                            </div>
                        </div>

                        <div style="font-size:11px;color:#64748b;">
                            Receipts are matched using the Ref No.
                        </div>

                    </div>


                    <div class="tx-card-body">

                        <div
                            class="tx-expense-list"
                            id="expenseList"
                        >

                            @foreach($oldItems as $index => $item)

                                <div
                                    class="tx-expense-card expense-row"
                                    data-index="{{ $index }}"
                                >

                                    {{-- =================================================
                                         EXPENSE HEADER
                                    ================================================== --}}

                                    <div class="tx-expense-header">

                                        <div class="tx-expense-number">

                                            <div class="tx-expense-index">
                                                {{ $index + 1 }}
                                            </div>

                                            <div>

                                                <div class="tx-expense-title">
                                                    Expense Item
                                                </div>

                                                <div class="tx-expense-ref">
                                                    Ref No:
                                                    <span class="ref-number">
                                                        LF-{{ date('Ymd') }}-{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                </div>

                                            </div>

                                        </div>


                                        <button
                                            type="button"
                                            class="tx-remove-btn remove-row"
                                            title="Remove expense"
                                        >
                                            🗑
                                            <span>Remove</span>
                                        </button>

                                    </div>


                                    {{-- =================================================
                                         EXPENSE BODY
                                    ================================================== --}}

                                    <div class="tx-expense-body">


                                        {{-- =================================================
                                             ROW 1 - IDENTIFICATION
                                        ================================================== --}}

                                        <div class="tx-expense-row tx-row-identification">


                                            {{-- Ref No --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Ref No.
                                                </div>

                                                <div class="tx-ref-box">

                                                    <span class="ref-value tx-ref-value">
                                                        LF-{{ date('Ymd') }}-{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}
                                                    </span>

                                                    <button
                                                        type="button"
                                                        class="tx-copy-ref copy-ref"
                                                        title="Copy Ref No."
                                                    >
                                                        Copy
                                                    </button>

                                                </div>

                                                <input
                                                    type="hidden"
                                                    name="items[{{ $index }}][ref_no]"
                                                    class="ref-input"
                                                    value="LF-{{ date('Ymd') }}-{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}"
                                                >

                                            </div>


                                            {{-- Date --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Date
                                                </div>

                                                <input
                                                    type="date"
                                                    name="items[{{ $index }}][item_date]"
                                                    value="{{ $item['item_date'] ?? '' }}"
                                                    class="tx-input item-date"
                                                    required
                                                >

                                            </div>


                                            {{-- Requested By --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Requested By
                                                </div>

                                                @php
                                                    $requestedByValue = $item['requested_by'] ?? $currentUserName;
                                                    $requestedByIsKnown = in_array($requestedByValue, $requestedByOptions, true);
                                                @endphp

                                                <div class="other-toggle-container">

                                                    <select
                                                        name="items[{{ $index }}][requested_by]"
                                                        class="tx-select other-toggle-select"
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
                                                        class="tx-input other-toggle-input"
                                                        placeholder="Enter name"
                                                        value="{{ !$requestedByIsKnown ? $requestedByValue : '' }}"
                                                        style="margin-top:8px; display:{{ !$requestedByIsKnown ? 'block' : 'none' }};"
                                                    >

                                                </div>

                                            </div>


                                            {{-- Payee --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Payee
                                                </div>

                                                @if($payeeOptions->count())

                                                    <select
                                                        name="items[{{ $index }}][payee]"
                                                        class="tx-select"
                                                        required
                                                    >

                                                        <option value="">
                                                            Select payee
                                                        </option>

                                                        @foreach($payeeOptions as $payee)

                                                            <option
                                                                value="{{ $payee }}"
                                                                @selected(($item['payee'] ?? '') == $payee)
                                                            >
                                                                {{ $payee }}
                                                            </option>

                                                        @endforeach

                                                    </select>

                                                @else

                                                    <input
                                                        type="text"
                                                        name="items[{{ $index }}][payee]"
                                                        value="{{ $item['payee'] ?? '' }}"
                                                        class="tx-input"
                                                        placeholder="Enter payee"
                                                        required
                                                    >

                                                @endif

                                            </div>

                                        </div>


                                        {{-- =================================================
                                             ROW 2 - CLASSIFICATION
                                        ================================================== --}}

                                        <div class="tx-expense-row tx-row-classification">


                                            {{-- Expense Type --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Expense Type
                                                </div>

                                                <select
                                                    name="items[{{ $index }}][expense_type]"
                                                    class="tx-select expense-type-select"
                                                    required
                                                >

                                                    <option value="">
                                                        Select expense classification
                                                    </option>

                                                    @foreach($expenseClassifications as $classification => $types)

                                                        <optgroup
                                                            label="{{ $classification }}"
                                                        >

                                                            @foreach($types as $type => $description)

                                                                <option
                                                                    value="{{ $type }}"
                                                                    title="{{ $description }}"
                                                                    @selected(($item['expense_type'] ?? '') == $type)
                                                                >
                                                                    {{ $type }}
                                                                </option>

                                                            @endforeach

                                                        </optgroup>

                                                    @endforeach

                                                </select>

                                                <div class="tx-help">
                                                    Select the appropriate expense classification.
                                                </div>

                                            </div>


                                            {{-- Account / Buyer --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Account / Buyer
                                                </div>

                                                @php
                                                    $accountBuyerValue = $item['account_buyer'] ?? '';
                                                    $accountBuyerIsKnown = in_array($accountBuyerValue, $accountBuyerOptions, true);
                                                @endphp

                                                <div class="other-toggle-container">

                                                    <select
                                                        name="items[{{ $index }}][account_buyer]"
                                                        class="tx-select other-toggle-select"
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
                                                        class="tx-input other-toggle-input"
                                                        placeholder="Enter buyer / account"
                                                        value="{{ ($accountBuyerValue !== '' && !$accountBuyerIsKnown) ? $accountBuyerValue : '' }}"
                                                        style="margin-top:8px; display:{{ ($accountBuyerValue !== '' && !$accountBuyerIsKnown) ? 'block' : 'none' }};"
                                                    >

                                                </div>

                                                <div class="tx-help">
                                                    Use this field to identify who or which account should be charged.
                                                </div>

                                            </div>

                                        </div>


                                        {{-- =================================================
                                             ROW 3 - AMOUNT / REMARKS
                                        ================================================== --}}

                                        <div class="tx-expense-row tx-row-amount">


                                            {{-- Amount VND --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Amount VND
                                                </div>

                                                <div class="tx-vnd-wrapper">

                                                    <span class="tx-vnd-prefix">
                                                        ₫
                                                    </span>

                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        name="items[{{ $index }}][amount_vnd]"
                                                        value="{{ $item['amount_vnd'] ?? '' }}"
                                                        class="tx-input tx-vnd-input amount-vnd"
                                                        placeholder="0.00"
                                                        required
                                                    >

                                                </div>

                                            </div>


                                            {{-- Amount USD --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Amount USD
                                                </div>

                                                <div class="tx-usd-wrapper">

                                                    <span class="tx-usd-prefix">
                                                        $
                                                    </span>

                                                    <input
                                                        type="text"
                                                        name="items[{{ $index }}][amount_usd]"
                                                        value=""
                                                        class="tx-input tx-usd-input amount-usd"
                                                        placeholder="0.00"
                                                        readonly
                                                    >

                                                </div>

                                            </div>


                                            {{-- Remarks --}}
                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Remarks
                                                </div>

                                                <input
                                                    type="text"
                                                    name="items[{{ $index }}][remarks]"
                                                    value="{{ $item['remarks'] ?? '' }}"
                                                    class="tx-input"
                                                    placeholder="Enter remarks..."
                                                >

                                            </div>


                                            {{-- Remove --}}
                                            <div class="tx-remove-container">

                                                <button
                                                    type="button"
                                                    class="tx-remove-btn remove-row"
                                                >
                                                    🗑
                                                    <span>Remove</span>
                                                </button>

                                            </div>

                                        </div>


                                        {{-- =================================================
                                             ROW 4 - RECEIPT (full width, own row so it
                                             never collides with Remarks)
                                        ================================================== --}}

                                        <div class="tx-expense-row tx-row-receipt">

                                            <div class="tx-field">

                                                <div class="tx-row-label">
                                                    Receipt Image
                                                </div>

                                                <div class="tx-receipt-wrapper">

                                                    <label class="tx-upload-btn">

                                                        📷
                                                        <span>
                                                            Upload / Scan Receipt
                                                        </span>

                                                        <input
                                                            type="file"
                                                            name="items[{{ $index }}][receipt_image]"
                                                            class="receipt-file-input tx-receipt-input"
                                                            accept="image/jpeg,image/png,image/webp"
                                                            capture="environment"
                                                        >

                                                    </label>

                                                    <div class="tx-receipt-preview">
                                                        <img src="" alt="Receipt preview">
                                                    </div>

                                                    <div class="tx-receipt-status">
                                                        No receipt
                                                    </div>

                                                </div>

                                                <div class="tx-help">
                                                    Uploading a receipt automatically scans it with OCR to fill in the Date and Amount VND above — please double-check the values it fills in.
                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        {{-- Add Expense --}}
                        <button
                            type="button"
                            id="addExpense"
                            class="tx-add-expense"
                        >
                            ＋ Add Another Expense
                        </button>


                        {{-- =================================================
                             TOTALS
                        ================================================== --}}

                        <div class="tx-total-card">

                            <div class="tx-total-grid">

                                <div>

                                    <div class="tx-total-label">
                                        TOTAL VND
                                    </div>

                                    <div
                                        class="tx-total-value"
                                        id="totalVnd"
                                    >
                                        ₫ 0.00
                                    </div>

                                </div>


                                <div>

                                    <div class="tx-total-label">
                                        TOTAL USD
                                    </div>

                                    <div
                                        class="tx-total-value"
                                        id="totalUsd"
                                    >
                                        $ 0.00
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     FORM ACTIONS
                ====================================================== --}}

                <div class="tx-actions">

                    <a
                        href="{{ url()->previous() }}"
                        class="tx-btn tx-btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="tx-btn tx-btn-primary"
                        id="submitButton"
                    >
                        Save Liquidation Report
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ================================================================
         OCR ENGINE (Tesseract.js — runs entirely client-side)
    ================================================================= --}}

    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.4/dist/tesseract.min.js"></script>


    {{-- ================================================================
         JAVASCRIPT
    ================================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const CURRENT_USER_NAME = @json($currentUserName);

            const expenseList = document.getElementById('expenseList');
            const addExpenseButton = document.getElementById('addExpense');

            const datePrepared = document.getElementById('date_prepared');
            const reportTitle = document.getElementById('report_title');
            const forexDisplay = document.getElementById('forexDisplay');
            const forexStatus = document.getElementById('forexStatus');
            const exchangeRateInput = document.getElementById('exchange_rate');

            const totalVnd = document.getElementById('totalVnd');
            const totalUsd = document.getElementById('totalUsd');

            let exchangeRate = null;


            /* ============================================================
               ESCAPE HTML (used when injecting the user's name into
               dynamically-created rows)
            ============================================================ */

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str == null ? '' : str;
                return div.innerHTML;
            }


            /* ============================================================
               FORMAT MONEY
            ============================================================ */

            function formatNumber(number) {

                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number || 0);

            }


            /* ============================================================
               DATE PREFIX
            ============================================================ */

            function getDatePrefix() {

                let date = datePrepared.value;

                if (!date) {
                    date = new Date().toISOString().slice(0, 10);
                }

                return date.replaceAll('-', '');

            }
            /* ============================================================
            AUTOMATIC REPORT TITLE
            Format: September 2026 Report
            ============================================================ */

            function updateReportTitle() {

                if (!datePrepared || !reportTitle) {
                    return;
                }

                if (!datePrepared.value) {
                    reportTitle.value = '';
                    return;
                }

                const date = new Date(datePrepared.value + 'T00:00:00');

                if (isNaN(date.getTime())) {
                    reportTitle.value = '';
                    return;
                }

                const monthYear = date.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });

                reportTitle.value = `${monthYear} Report`;
            }

            /* ============================================================
               GENERATE REF NUMBER
            ============================================================ */

            function generateRefNumber(index) {

                const prefix = getDatePrefix();

                return `LF-${prefix}-${String(index + 1).padStart(3, '0')}`;

            }


            /* ============================================================
               RENUMBER EXPENSES
            ============================================================ */

            function renumberRows() {

                const rows = expenseList.querySelectorAll('.expense-row');

                rows.forEach((row, index) => {

                    row.dataset.index = index;

                    /* ---------------------------------------------
                       Expense number
                    --------------------------------------------- */

                    const indexElement =
                        row.querySelector('.tx-expense-index');

                    if (indexElement) {
                        indexElement.textContent = index + 1;
                    }


                    /* ---------------------------------------------
                       Ref number
                    --------------------------------------------- */

                    const refNumber = generateRefNumber(index);

                    const refDisplay =
                        row.querySelector('.ref-number');

                    const refValue =
                        row.querySelector('.ref-value');

                    const refInput =
                        row.querySelector('.ref-input');

                    if (refDisplay) {
                        refDisplay.textContent = refNumber;
                    }

                    if (refValue) {
                        refValue.textContent = refNumber;
                    }

                    if (refInput) {
                        refInput.value = refNumber;
                    }


                    /* ---------------------------------------------
                       Rename all inputs/selects/textareas/files
                    --------------------------------------------- */

                    row.querySelectorAll(
                        'input, select, textarea'
                    ).forEach(element => {

                        if (!element.name) {
                            return;
                        }

                        const match =
                            element.name.match(
                                /^items\[\d+\]\[(.+)\]$/
                            );

                        if (match) {

                            element.name =
                                `items[${index}][${match[1]}]`;

                        }

                    });

                });

                recalcAll();

            }


            /* ============================================================
               UPDATE USD
            ============================================================ */

            function updateRowUsd(row) {

                const vndInput =
                    row.querySelector('.amount-vnd');

                const usdInput =
                    row.querySelector('.amount-usd');

                if (!vndInput || !usdInput) {
                    return;
                }

                const vnd =
                    parseFloat(vndInput.value) || 0;

                if (!exchangeRate || exchangeRate <= 0) {

                    usdInput.value = '';

                    return;

                }

                const usd =
                    vnd / exchangeRate;

                usdInput.value =
                    usd.toFixed(2);

            }


            /* ============================================================
               RECALCULATE TOTALS
            ============================================================ */

            function recalcAll() {

                let vndTotal = 0;
                let usdTotal = 0;

                const rows =
                    expenseList.querySelectorAll('.expense-row');

                rows.forEach(row => {

                    updateRowUsd(row);

                    const vndInput =
                        row.querySelector('.amount-vnd');

                    const usdInput =
                        row.querySelector('.amount-usd');

                    const vnd =
                        parseFloat(vndInput?.value) || 0;

                    const usd =
                        parseFloat(usdInput?.value) || 0;

                    vndTotal += vnd;
                    usdTotal += usd;

                });

                totalVnd.textContent =
                    `₫ ${formatNumber(vndTotal)}`;

                totalUsd.textContent =
                    `$ ${formatNumber(usdTotal)}`;

            }


            /* ============================================================
               FETCH LIVE FOREX
            ============================================================ */

            async function fetchLiveForex() {

                forexStatus.textContent =
                    'Loading live exchange rate...';

                forexDisplay.textContent =
                    'Loading...';

                try {

                    const response =
                        await fetch(
                            'https://open.er-api.com/v6/latest/USD',
                            {
                                cache: 'no-store'
                            }
                        );

                    if (!response.ok) {
                        throw new Error('Forex request failed');
                    }

                    const data =
                        await response.json();

                    if (
                        data.result !== 'success' ||
                        !data.rates ||
                        !data.rates.VND
                    ) {
                        throw new Error('Invalid forex response');
                    }

                    exchangeRate =
                        parseFloat(data.rates.VND);

                    exchangeRateInput.value =
                        exchangeRate;

                    forexDisplay.textContent =
                        `1 USD = ₫ ${formatNumber(exchangeRate)}`;

                    forexStatus.textContent =
                        'Live USD/VND rate loaded';

                    recalcAll();

                } catch (error) {

                    console.error(
                        'Forex error:',
                        error
                    );

                    exchangeRate = null;

                    forexDisplay.textContent =
                        'Unavailable';

                    forexStatus.textContent =
                        'Unable to load live forex rate';

                    exchangeRateInput.value = '';

                }

            }


            /* ============================================================
               OCR — SCAN RECEIPT FOR DATE + AMOUNT
            ============================================================ */

            function extractDateFromText(text) {

                // yyyy-mm-dd / yyyy/mm/dd / yyyy.mm.dd
                let m = text.match(/(20\d{2})[\/\-.](0?[1-9]|1[0-2])[\/\-.](0?[1-9]|[12]\d|3[01])/);

                if (m) {
                    return `${m[1]}-${m[2].padStart(2, '0')}-${m[3].padStart(2, '0')}`;
                }

                // dd-mm-yyyy / dd/mm/yyyy / dd.mm.yyyy — the separator between
                // day and month is optional because OCR on compact bank-app
                // screenshots sometimes drops it (e.g. "24/08/2026" -> "2408/2026")
                m = text.match(/(0?[1-9]|[12]\d|3[01])[\/\-.]?(0?[1-9]|1[0-2])[\/\-.](20\d{2})/);

                if (m) {
                    return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`;
                }

                return null;

            }

            function extractAmountFromText(text) {

                let candidates = [];

                // Prefer amounts explicitly tagged with VND / ₫ / đ.
                // VN[DP0] also catches "VNP"/"VN0", which is how OCR
                // commonly misreads "VND" on compact bank-app screenshots.
                const taggedRegex = /(?:VN[DP0]|₫|đ)\s?([\d.,]{4,})/gi;
                let match;

                while ((match = taggedRegex.exec(text)) !== null) {
                    candidates.push(match[1]);
                }

                // Fall back to any thousands-formatted number on the receipt
                if (!candidates.length) {
                    candidates = text.match(/\b\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{1,2})?\b/g) || [];
                }

                if (!candidates.length) {
                    return null;
                }

                // Assume the largest number found is the total (safest heuristic
                // for a receipt, since line items are smaller than the total)
                let best = 0;

                candidates.forEach(raw => {

                    const normalized = raw
                        .replace(/[.,](?=\d{3}(\D|$))/g, '')
                        .replace(',', '.');

                    const value = parseFloat(normalized.replace(/[^\d.]/g, ''));

                    if (!isNaN(value) && value > best) {
                        best = value;
                    }

                });

                return best > 0 ? best.toFixed(2) : null;

            }

            async function scanReceipt(row, file) {

                const status = row.querySelector('.tx-receipt-status');
                const dateInput = row.querySelector('.item-date');
                const vndInput = row.querySelector('.amount-vnd');

                if (typeof Tesseract === 'undefined') {

                    if (status) {
                        status.textContent = 'OCR library failed to load — please enter date/amount manually.';
                        status.className = 'tx-receipt-status tx-receipt-error';
                    }

                    return;

                }

                if (status) {
                    status.textContent = 'Scanning receipt with OCR…';
                    status.className = 'tx-receipt-status tx-receipt-scanning';
                }

                try {

                    const { data } = await Tesseract.recognize(file, 'eng');
                    const text = data?.text || '';

                    const foundDate = extractDateFromText(text);
                    const foundAmount = extractAmountFromText(text);

                    const found = [];

                    if (foundDate && dateInput) {
                        dateInput.value = foundDate;
                        found.push('date');
                    }

                    if (foundAmount && vndInput) {
                        vndInput.value = foundAmount;
                        found.push('amount');
                    }

                    recalcAll();

                    if (status) {

                        status.className = 'tx-receipt-status tx-receipt-attached';

                        status.textContent = found.length
                            ? `Receipt attached — auto-filled ${found.join(' & ')} from the scan. Please verify.`
                            : 'Receipt attached — could not auto-detect date/amount, please enter manually.';

                    }

                } catch (error) {

                    console.error('OCR error:', error);

                    if (status) {
                        status.className = 'tx-receipt-status tx-receipt-error';
                        status.textContent = 'Receipt attached — OCR scan failed, please enter date/amount manually.';
                    }

                }

            }


            /* ============================================================
               ADD EXPENSE
            ============================================================ */

            function addExpense() {

                const currentRows =
                    expenseList.querySelectorAll('.expense-row');

                const index =
                    currentRows.length;

                const row =
                    document.createElement('div');

                row.className =
                    'tx-expense-card expense-row';

                row.dataset.index = index;


                row.innerHTML = `

                    <div class="tx-expense-header">

                        <div class="tx-expense-number">

                            <div class="tx-expense-index">
                                ${index + 1}
                            </div>

                            <div>

                                <div class="tx-expense-title">
                                    Expense Item
                                </div>

                                <div class="tx-expense-ref">
                                    Ref No:
                                    <span class="ref-number">
                                        ${generateRefNumber(index)}
                                    </span>
                                </div>

                            </div>

                        </div>

                        <button
                            type="button"
                            class="tx-remove-btn remove-row"
                            title="Remove expense"
                        >
                            🗑
                            <span>Remove</span>
                        </button>

                    </div>


                    <div class="tx-expense-body">


                        <!-- ROW 1 -->

                        <div class="tx-expense-row tx-row-identification">


                            <!-- REF -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Ref No.
                                </div>

                                <div class="tx-ref-box">

                                    <span class="ref-value tx-ref-value">
                                        ${generateRefNumber(index)}
                                    </span>

                                    <button
                                        type="button"
                                        class="tx-copy-ref copy-ref"
                                    >
                                        Copy
                                    </button>

                                </div>

                                <input
                                    type="hidden"
                                    name="items[${index}][ref_no]"
                                    class="ref-input"
                                    value="${generateRefNumber(index)}"
                                >

                            </div>


                            <!-- DATE -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Date
                                </div>

                                <input
                                    type="date"
                                    name="items[${index}][item_date]"
                                    class="tx-input item-date"
                                    required
                                >

                            </div>


                            <!-- REQUESTED BY -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Requested By
                                </div>

                                <div class="other-toggle-container">

                                    <select
                                        name="items[${index}][requested_by]"
                                        class="tx-select other-toggle-select"
                                        data-field="requested_by"
                                        required
                                    >

                                        <option value="">Select requested by</option>
                                        @foreach($requestedByOptions as $name)
                                            <option value="{{ $name }}">{{ $name }}</option>
                                        @endforeach
                                        <option value="__other__">Other (Manual Entry)</option>

                                    </select>

                                    <input
                                        type="text"
                                        class="tx-input other-toggle-input"
                                        placeholder="Enter name"
                                        style="margin-top:8px; display:none;"
                                    >

                                </div>

                            </div>


                            <!-- PAYEE -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Payee
                                </div>

                                <input
                                    type="text"
                                    name="items[${index}][payee]"
                                    class="tx-input"
                                    placeholder="Enter payee"
                                    required
                                >

                            </div>

                        </div>


                        <!-- ROW 2 -->

                        <div class="tx-expense-row tx-row-classification">


                            <!-- EXPENSE TYPE -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Expense Type
                                </div>

                                <select
                                    name="items[${index}][expense_type]"
                                    class="tx-select expense-type-select"
                                    required
                                >

                                    <option value="">
                                        Select expense classification
                                    </option>

                                    @foreach($expenseClassifications as $classification => $types)

                                        <optgroup
                                            label="{{ $classification }}"
                                        >

                                            @foreach($types as $type => $description)

                                                <option value="{{ $type }}">
                                                    {{ $type }}
                                                </option>

                                            @endforeach

                                        </optgroup>

                                    @endforeach

                                </select>

                            </div>


                            <!-- ACCOUNT / BUYER -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Account / Buyer
                                </div>

                                <div class="other-toggle-container">

                                    <select
                                        name="items[${index}][account_buyer]"
                                        class="tx-select other-toggle-select"
                                        data-field="account_buyer"
                                        required
                                    >

                                        <option value="">Select account / buyer</option>
                                        @foreach($accountBuyerOptions as $account)
                                            <option value="{{ $account }}">{{ $account }}</option>
                                        @endforeach
                                        <option value="__other__">Other (Manual Entry)</option>

                                    </select>

                                    <input
                                        type="text"
                                        class="tx-input other-toggle-input"
                                        placeholder="Enter buyer / account"
                                        style="margin-top:8px; display:none;"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- ROW 3 -->

                        <div class="tx-expense-row tx-row-amount">


                            <!-- VND -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Amount VND
                                </div>

                                <div class="tx-vnd-wrapper">

                                    <span class="tx-vnd-prefix">
                                        ₫
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="items[${index}][amount_vnd]"
                                        class="tx-input tx-vnd-input amount-vnd"
                                        placeholder="0.00"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- USD -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Amount USD
                                </div>

                                <div class="tx-usd-wrapper">

                                    <span class="tx-usd-prefix">
                                        $
                                    </span>

                                    <input
                                        type="text"
                                        name="items[${index}][amount_usd]"
                                        class="tx-input tx-usd-input amount-usd"
                                        placeholder="0.00"
                                        readonly
                                    >

                                </div>

                            </div>


                            <!-- REMARKS -->

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Remarks
                                </div>

                                <input
                                    type="text"
                                    name="items[${index}][remarks]"
                                    class="tx-input"
                                    placeholder="Enter remarks..."
                                >

                            </div>


                            <!-- REMOVE -->

                            <div class="tx-remove-container">

                                <button
                                    type="button"
                                    class="tx-remove-btn remove-row"
                                >
                                    🗑
                                    <span>Remove</span>
                                </button>

                            </div>

                        </div>


                        <!-- ROW 4 - RECEIPT (own full-width row) -->

                        <div class="tx-expense-row tx-row-receipt">

                            <div class="tx-field">

                                <div class="tx-row-label">
                                    Receipt Image
                                </div>

                                <div class="tx-receipt-wrapper">

                                    <label class="tx-upload-btn">

                                        📷
                                        <span>
                                            Upload / Scan Receipt
                                        </span>

                                        <input
                                            type="file"
                                            name="items[${index}][receipt_image]"
                                            class="receipt-file-input tx-receipt-input"
                                            accept="image/jpeg,image/png,image/webp"
                                            capture="environment"
                                        >

                                    </label>

                                    <div class="tx-receipt-preview">
                                        <img src="" alt="Receipt preview">
                                    </div>

                                    <div class="tx-receipt-status">
                                        No receipt
                                    </div>

                                </div>

                                <div class="tx-help">
                                    Uploading a receipt automatically scans it with OCR to fill in the Date and Amount VND above — please double-check the values it fills in.
                                </div>

                            </div>

                        </div>

                    </div>
                `;


                expenseList.appendChild(row);

                bindRow(row);

                renumberRows();

            }


            /* ============================================================
               REMOVE EXPENSE
            ============================================================ */

            function removeExpense(button) {

                const rows =
                    expenseList.querySelectorAll('.expense-row');

                if (rows.length <= 1) {

                    alert(
                        'At least one expense item is required.'
                    );

                    return;

                }

                const row =
                    button.closest('.expense-row');

                if (row) {
                    row.remove();
                }

                renumberRows();

            }


            /* ============================================================
               RECEIPT PREVIEW
            ============================================================ */

            function previewReceipt(input) {

                const row =
                    input.closest('.expense-row');

                if (!row) {
                    return;
                }

                const preview =
                    row.querySelector('.tx-receipt-preview');

                const previewImage =
                    preview?.querySelector('img');

                const status =
                    row.querySelector('.tx-receipt-status');

                if (!input.files || !input.files[0]) {

                    if (preview) {
                        preview.style.display = 'none';
                    }

                    if (status) {
                        status.textContent = 'No receipt';
                        status.className = 'tx-receipt-status';
                    }

                    return;
                }

                const file =
                    input.files[0];

                if (!file.type.startsWith('image/')) {

                    alert(
                        'Please upload an image file.'
                    );

                    input.value = '';

                    return;

                }

                const reader =
                    new FileReader();

                reader.onload =
                    function (event) {

                        if (previewImage) {
                            previewImage.src =
                                event.target.result;
                        }

                        if (preview) {
                            preview.style.display =
                                'block';
                        }

                    };

                reader.readAsDataURL(file);

            }


            /* ============================================================
               COPY REF NUMBER
            ============================================================ */

            async function copyRef(button) {

                const row =
                    button.closest('.expense-row');

                if (!row) {
                    return;
                }

                const ref =
                    row.querySelector('.ref-value')?.textContent
                    ?.trim();

                if (!ref) {
                    return;
                }

                try {

                    await navigator.clipboard.writeText(ref);

                    const original =
                        button.textContent;

                    button.textContent =
                        'Copied';

                    setTimeout(() => {
                        button.textContent =
                            original;
                    }, 1200);

                } catch (error) {

                    console.error(
                        'Unable to copy:',
                        error
                    );

                }

            }


            /* ============================================================
               OTHER / MANUAL-ENTRY TOGGLE
               (used by Requested By and Account / Buyer dropdowns)
            ============================================================ */

            function getRowIndex(el) {

                const row = el.closest('.expense-row');

                return row ? parseInt(row.dataset.index, 10) || 0 : 0;

            }

            function bindOtherToggle(container) {

                const select =
                    container.querySelector('.other-toggle-select');

                const otherInput =
                    container.querySelector('.other-toggle-input');

                if (!select || !otherInput) {
                    return;
                }

                const field = select.dataset.field;

                function sync() {

                    const idx = getRowIndex(select);
                    const fieldName = `items[${idx}][${field}]`;

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

                select.addEventListener('change', function () {
                    otherInput.value = '';
                    sync();
                });

                sync();

            }


            /* ============================================================
               BIND ROW
            ============================================================ */

            function bindRow(row) {

                row.querySelectorAll('.other-toggle-container')
                    .forEach(bindOtherToggle);


                const amountVnd =
                    row.querySelector('.amount-vnd');

                if (amountVnd) {

                    amountVnd.addEventListener(
                        'input',
                        function () {
                            recalcAll();
                        }
                    );

                }


                const receiptInput =
                    row.querySelector('.receipt-file-input');

                if (receiptInput) {

                    receiptInput.addEventListener(
                        'change',
                        function () {

                            previewReceipt(this);

                            if (this.files && this.files[0]) {
                                scanReceipt(row, this.files[0]);
                            }

                        }
                    );

                }


                row.querySelectorAll('.remove-row')
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            function () {
                                removeExpense(this);
                            }
                        );

                    });


                row.querySelectorAll('.copy-ref')
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            function () {
                                copyRef(this);
                            }
                        );

                    });

            }


            /* ============================================================
               DATE CHANGE
            ============================================================ */

            datePrepared.addEventListener(
                'change',
                function () {

                    updateReportTitle();
                    renumberRows();

                }
            );


            /* ============================================================
               ADD EXPENSE BUTTON
            ============================================================ */

            addExpenseButton.addEventListener(
                'click',
                addExpense
            );


            /* ============================================================
               INITIAL BIND
            ============================================================ */

            expenseList
                .querySelectorAll('.expense-row')
                .forEach(bindRow);

            /* ============================================================
            INITIAL REPORT TITLE
            ============================================================ */

            updateReportTitle();
            /* ============================================================
               INITIAL REF NUMBERS
            ============================================================ */

            renumberRows();


            /* ============================================================
               LOAD FOREX
            ============================================================ */

            fetchLiveForex();


            /* ============================================================
               FORM SUBMIT
            ============================================================ */

            document
                .getElementById('liquidationForm')
                .addEventListener(
                    'submit',
                    function (event) {

                        if (
                            !exchangeRate ||
                            exchangeRate <= 0
                        ) {

                            event.preventDefault();

                            alert(
                                'The live USD/VND forex rate is not available. Please wait for the forex rate to load before saving.'
                            );

                            return;

                        }


                        const rows =
                            expenseList.querySelectorAll(
                                '.expense-row'
                            );


                        if (rows.length === 0) {

                            event.preventDefault();

                            alert(
                                'Please add at least one expense item.'
                            );

                            return;

                        }


                        let invalid =
                            false;


                        rows.forEach(row => {

                            const amount =
                                parseFloat(
                                    row.querySelector(
                                        '.amount-vnd'
                                    )?.value
                                ) || 0;

                            if (amount <= 0) {
                                invalid = true;
                            }

                        });


                        if (invalid) {

                            event.preventDefault();

                            alert(
                                'Please enter a valid VND amount for every expense item.'
                            );

                            return;

                        }


                        /* Ensure final ref numbers */
                        renumberRows();


                        /* Prevent double submit */

                        const button =
                            document.getElementById(
                                'submitButton'
                            );

                        button.disabled = true;

                        button.textContent =
                            'Saving...';

                    }
                );

        });
    </script>

</x-mi_app>