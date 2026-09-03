<x-mi_app>

    {{-- =========================================================
        FONTS
    ========================================================== --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <style>
        /* =========================================================
           ROOT
        ========================================================== */

        .tx-console {
            --tx-primary: #2563eb;
            --tx-primary-dark: #1d4ed8;
            --tx-primary-soft: #eff6ff;

            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;
            --tx-soft: #f1f5f9;

            --tx-line: #e2e8f0;
            --tx-line-dark: #cbd5e1;

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-warning: #b45309;
            --tx-warning-soft: #fffbeb;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-font-display: 'Space Grotesk',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-body: 'Inter',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-mono: 'JetBrains Mono',
                ui-monospace,
                SFMono-Regular,
                monospace;

            min-height: 100vh;
            background: var(--tx-bg);
            color: var(--tx-ink);
            font-family: var(--tx-font-body);
        }

        .tx-console *,
        .tx-console *::before,
        .tx-console *::after {
            box-sizing: border-box;
        }

        .tx-display {
            font-family: var(--tx-font-display);
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
        }

        /* =========================================================
           SHELL
        ========================================================== */

        .tx-shell {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        /* =========================================================
           HEADER
        ========================================================== */

        .tx-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;

            margin-bottom: 22px;
        }

        .tx-header-copy {
            min-width: 0;
        }

        .tx-eyebrow {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 7px;

            color: var(--tx-ink-faint);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tx-eyebrow-divider {
            color: var(--tx-line-dark);
        }

        .tx-title {
            margin: 0;

            color: var(--tx-ink);
            font-family: var(--tx-font-display);
            font-size: 28px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -.02em;
        }

        .tx-subtitle {
            max-width: 760px;
            margin: 7px 0 0;

            color: var(--tx-ink-soft);
            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================================================
           BUTTONS
        ========================================================== */

        .tx-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            min-height: 40px;
            padding: 0 16px;

            border: 1px solid var(--tx-primary);
            border-radius: 9px;

            background: var(--tx-primary);
            color: #ffffff;

            font-size: 13px;
            font-weight: 700;
            text-decoration: none;

            cursor: pointer;

            transition:
                background-color .15s ease,
                border-color .15s ease,
                transform .15s ease,
                box-shadow .15s ease;
        }

        .tx-btn-primary:hover {
            background: var(--tx-primary-dark);
            border-color: var(--tx-primary-dark);
            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 8px 18px rgba(37, 99, 235, .18);
        }

        /* =========================================================
           ALERTS
        ========================================================== */

        .tx-alert {
            margin-bottom: 18px;
            padding: 13px 15px;

            border-radius: 10px;

            font-size: 13px;
            line-height: 1.5;
        }

        .tx-alert-error {
            border: 1px solid #fecaca;
            background: var(--tx-danger-soft);
            color: #991b1b;
        }

        .tx-alert-success {
            border: 1px solid #a7f3d0;
            background: var(--tx-success-soft);
            color: #047857;
        }

        .tx-alert strong {
            font-weight: 700;
        }

        .tx-alert ul {
            margin: 7px 0 0 18px;
            padding: 0;
        }

        /* =========================================================
           STATS
        ========================================================== */

        .tx-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;

            margin-bottom: 18px;
        }

        .tx-stat {
            min-width: 0;

            padding: 16px 18px;

            border: 1px solid var(--tx-line);
            border-radius: 14px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03);
        }

        .tx-stat-label {
            color: var(--tx-ink-faint);

            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .tx-stat-value {
            margin-top: 5px;

            overflow: hidden;

            color: var(--tx-ink);

            font-family: var(--tx-font-mono);
            font-size: 20px;
            font-weight: 600;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* =========================================================
           FILTER BAR
        ========================================================== */

        .tx-filter-bar {
            margin-bottom: 18px;
            padding: 14px;

            border: 1px solid var(--tx-line);
            border-radius: 14px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03);
        }

        .tx-filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
        }

        .tx-search-wrap {
            position: relative;

            flex: 1 1 360px;
            min-width: 220px;
        }

        .tx-search-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;

            width: 16px;
            height: 16px;

            color: var(--tx-ink-faint);

            transform: translateY(-50%);
            pointer-events: none;
        }

        .tx-field {
            width: 100%;
            min-height: 40px;

            border: 1px solid var(--tx-line-dark);
            border-radius: 9px;

            outline: none;

            background: var(--tx-bg);
            color: var(--tx-ink);

            font-family: var(--tx-font-body);
            font-size: 13px;

            padding: 9px 12px;

            transition:
                border-color .15s ease,
                background-color .15s ease,
                box-shadow .15s ease;
        }

        .tx-field:focus {
            border-color: var(--tx-primary);
            background: #ffffff;

            box-shadow:
                0 0 0 3px var(--tx-primary-soft);
        }

        .tx-search-wrap .tx-field {
            padding-left: 38px;
        }

        .tx-select-sm {
            flex: 0 1 180px;
        }

        .tx-filter-submit {
            min-height: 40px;
            padding: 0 17px;

            border: 1px solid var(--tx-ink);
            border-radius: 9px;

            background: var(--tx-ink);
            color: #ffffff;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            transition:
                transform .15s ease,
                background-color .15s ease;
        }

        .tx-filter-submit:hover {
            background: #000000;
            transform: translateY(-1px);
        }

        .tx-filter-clear {
            display: inline-flex;
            align-items: center;

            min-height: 40px;
            padding: 0 9px;

            color: var(--tx-ink-soft);

            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .tx-filter-clear:hover {
            color: var(--tx-primary);
        }

        /* =========================================================
           TABLE CARD
        ========================================================== */

        .tx-table-card {
            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03);
        }

        .tx-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table.tx-table {
            width: 100%;
            min-width: 1120px;

            border-collapse: collapse;

            font-size: 12px;
        }

        table.tx-table thead th {
            padding: 12px 14px;

            border-bottom: 1px solid var(--tx-line);

            background: var(--tx-bg);

            color: var(--tx-ink-faint);

            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            text-align: left;
            text-transform: uppercase;

            white-space: nowrap;
        }

        table.tx-table tbody td {
            padding: 13px 14px;

            border-bottom: 1px solid var(--tx-line);

            color: var(--tx-ink);

            vertical-align: middle;
        }

        table.tx-table tbody tr {
            transition: background-color .12s ease;
        }

        table.tx-table tbody tr:hover {
            background: #f8fafc;
        }

        table.tx-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           REPORT
        ========================================================== */

        .tx-report {
            min-width: 190px;
        }

        .tx-item-name {
            max-width: 260px;

            overflow: hidden;

            color: var(--tx-ink);

            font-size: 13px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tx-item-sub {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-size: 10px;
        }

        .tx-report-id {
            display: inline-flex;
            align-items: center;

            margin-top: 6px;
            padding: 3px 6px;

            border: 1px solid var(--tx-line);
            border-radius: 6px;

            background: var(--tx-bg);

            color: var(--tx-ink-soft);

            font-family: var(--tx-font-mono);
            font-size: 9px;
            font-weight: 600;
        }

        /* =========================================================
           PERSON / COMPANY
        ========================================================== */

        .tx-person {
            min-width: 135px;
        }

        .tx-person-name {
            color: var(--tx-ink);

            font-size: 12px;
            font-weight: 600;
        }

        .tx-person-sub {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-mono);
            font-size: 9px;
        }

        /* =========================================================
           VALUES
        ========================================================== */

        .tx-value {
            color: var(--tx-ink);

            font-family: var(--tx-font-mono);
            font-size: 12px;
            font-weight: 600;
        }

        .tx-value-sub {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-mono);
            font-size: 10px;
        }

        .tx-rate {
            color: var(--tx-ink);

            font-family: var(--tx-font-mono);
            font-size: 11px;
            font-weight: 600;
        }

        /* =========================================================
           STATUS
        ========================================================== */

        .tx-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .tx-badge-dot {
            width: 6px;
            height: 6px;

            border-radius: 999px;

            flex: 0 0 auto;
        }

        .badge-pending {
            background: var(--tx-warning-soft);
            color: var(--tx-warning);
        }

        .badge-pending .tx-badge-dot {
            background: var(--tx-warning);
        }

        .badge-approved {
            background: var(--tx-success-soft);
            color: var(--tx-success);
        }

        .badge-approved .tx-badge-dot {
            background: var(--tx-success);
        }

        .badge-rejected {
            background: var(--tx-danger-soft);
            color: var(--tx-danger);
        }

        .badge-rejected .tx-badge-dot {
            background: var(--tx-danger);
        }

        .badge-default {
            background: var(--tx-soft);
            color: var(--tx-ink-soft);
        }

        .badge-default .tx-badge-dot {
            background: var(--tx-ink-faint);
        }

        /* =========================================================
           ACTIONS
        ========================================================== */

        .tx-row-actions {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tx-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 32px;
            height: 32px;

            border: 1px solid var(--tx-line-dark);
            border-radius: 8px;

            background: #ffffff;
            color: var(--tx-ink-soft);

            text-decoration: none;

            cursor: pointer;

            transition:
                border-color .15s ease,
                background-color .15s ease,
                color .15s ease,
                transform .15s ease;
        }

        .tx-icon-btn:hover {
            border-color: var(--tx-primary);
            background: var(--tx-primary-soft);
            color: var(--tx-primary);

            transform: translateY(-1px);
        }

        .tx-icon-btn.danger:hover {
            border-color: #fecaca;
            background: var(--tx-danger-soft);
            color: var(--tx-danger);
        }

        /* =========================================================
           TABLE FOOTER
        ========================================================== */

        .tx-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;

            padding: 13px 15px;

            border-top: 1px solid var(--tx-line);
        }

        .tx-count {
            color: var(--tx-ink-faint);

            font-size: 11px;
        }

        .tx-pagination {
            font-size: 12px;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================== */

        .tx-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            min-height: 360px;

            padding: 50px 25px;

            text-align: center;
        }

        .tx-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 58px;
            height: 58px;

            margin-bottom: 13px;

            border-radius: 14px;

            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }

        .tx-empty-title {
            margin: 0;

            color: var(--tx-ink);

            font-size: 14px;
            font-weight: 700;
        }

        .tx-empty-sub {
            max-width: 450px;

            margin: 6px 0 0;

            color: var(--tx-ink-faint);

            font-size: 12px;
            line-height: 1.6;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1050px) {

            .tx-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tx-header {
                align-items: flex-start;
            }
        }

        @media (max-width: 700px) {

            .tx-shell {
                padding: 20px 15px 45px;
            }

            .tx-header {
                flex-direction: column;
                align-items: stretch;
            }

            .tx-title {
                font-size: 23px;
            }

            .tx-subtitle {
                font-size: 13px;
            }

            .tx-btn-primary {
                width: 100%;
            }

            .tx-stats {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .tx-stat {
                padding: 13px 14px;
            }

            .tx-stat-value {
                font-size: 16px;
            }

            .tx-filter-form {
                align-items: stretch;
            }

            .tx-search-wrap,
            .tx-select-sm {
                flex: 1 1 100%;
                width: 100%;
            }

            .tx-filter-submit {
                flex: 1;
            }

            .tx-filter-clear {
                justify-content: center;
                width: 100%;
            }

            .tx-table-footer {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 450px) {

            .tx-stats {
                grid-template-columns: 1fr;
            }

            .tx-eyebrow {
                font-size: 9px;
            }

            .tx-title {
                font-size: 21px;
            }
        }
    </style>


    <div class="tx-console">

        <div class="tx-shell">

            {{-- =====================================================
                PAGE HEADER
            ====================================================== --}}

            <div class="tx-header">

                <div class="tx-header-copy">

                    <div class="tx-eyebrow">
                        <span>Liquidation</span>
                        <span class="tx-eyebrow-divider">/</span>
                        <span>All Reports</span>
                    </div>

                    <h1 class="tx-title">
                        Liquidation Reports
                    </h1>

                    <p class="tx-subtitle">
                        Manage petty cash liquidation reports, expense
                        documentation, receipt records, budget reconciliation,
                        and cash-on-hand balances.
                    </p>

                </div>


                <a
                    href="{{ route('liquidation.create') }}"
                    class="tx-btn-primary"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>

                    New Liquidation

                </a>

            </div>


            {{-- =====================================================
                ALERTS
            ====================================================== --}}

            @if ($errors->any())

                <div class="tx-alert tx-alert-error">

                    <strong>
                        Unable to process the liquidation.
                    </strong>

                    <ul>

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            @if (session('error'))

                <div class="tx-alert tx-alert-error">
                    {{ session('error') }}
                </div>

            @endif


            @if (session('success'))

                <div class="tx-alert tx-alert-success">
                    {{ session('success') }}
                </div>

            @endif


            {{-- =====================================================
                QUICK STATS
            ====================================================== --}}

            <div class="tx-stats">

                <div class="tx-stat">

                    <div class="tx-stat-label">
                        Total Reports
                    </div>

                    <div class="tx-stat-value">
                        {{ $reports->total() }}
                    </div>

                </div>


                <div class="tx-stat">

                    <div class="tx-stat-label">
                        Pending
                    </div>

                    <div class="tx-stat-value">
                        {{ $pendingCount }}
                    </div>

                </div>


                <div class="tx-stat">

                    <div class="tx-stat-label">
                        Approved
                    </div>

                    <div class="tx-stat-value">
                        {{ $approvedCount }}
                    </div>

                </div>


                <div class="tx-stat">

                    <div class="tx-stat-label">
                        Total Liquidated
                    </div>

                    <div class="tx-stat-value">

                        ₫{{ number_format((float) $totalVnd, 0) }}

                    </div>

                </div>

            </div>


            {{-- =====================================================
                FILTER BAR
            ====================================================== --}}

            <div class="tx-filter-bar">

                <form
                    method="GET"
                    action="{{ route('liquidation.index') }}"
                    class="tx-filter-form"
                >

                    {{-- SEARCH --}}

                    <div class="tx-search-wrap">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"
                            />
                        </svg>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search report title or ID..."
                            class="tx-field"
                        >

                    </div>


                    {{-- STATUS --}}

                    <select
                        name="status"
                        class="tx-field tx-select-sm"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        @foreach (['Pending', 'Approved', 'Rejected'] as $statusOption)

                            <option
                                value="{{ $statusOption }}"
                                {{ request('status') === $statusOption ? 'selected' : '' }}
                            >
                                {{ $statusOption }}
                            </option>

                        @endforeach

                    </select>


                    {{-- FILTER --}}

                    <button
                        type="submit"
                        class="tx-filter-submit"
                    >
                        Filter
                    </button>


                    {{-- CLEAR --}}

                    @if(request()->filled('search') || request()->filled('status'))

                        <a
                            href="{{ route('liquidation.index') }}"
                            class="tx-filter-clear"
                        >
                            Clear filters
                        </a>

                    @endif

                </form>

            </div>


            {{-- =====================================================
                REPORT TABLE
            ====================================================== --}}

            <div class="tx-table-card">

                @if($reports->count() > 0)

                    <div class="tx-table-scroll">

                        <table class="tx-table">

                            <thead>

                                <tr>

                                    <th>
                                        Report
                                    </th>

                                    <th>
                                        Date Prepared
                                    </th>

                                    <th>
                                        Prepared By
                                    </th>

                                    <th>
                                        Company
                                    </th>

                                    <th>
                                        Rate
                                    </th>

                                    <th>
                                        Total Liquidated
                                    </th>

                                    <th>
                                        Cash on Hand
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach($reports as $report)

                                    @php

                                        $status = strtolower(
                                            trim($report->status ?? 'pending')
                                        );

                                        $statusClass = match ($status) {

                                            'pending' =>
                                                'badge-pending',

                                            'approved' =>
                                                'badge-approved',

                                            'rejected' =>
                                                'badge-rejected',

                                            default =>
                                                'badge-default',
                                        };


                                        $totalVndReport =
                                            (float) ($report->total_vnd ?? 0);


                                        $totalUsdReport =
                                            (float) ($report->total_usd ?? 0);


                                        $pcfAmount =
                                            $report->pcf_amount !== null
                                                ? (float) $report->pcf_amount
                                                : null;


                                        $cashOnHandVnd =
                                            (float) (
                                                $report->cash_on_hand_vnd ?? 0
                                            );


                                        $cashOnHandUsd =
                                            (float) (
                                                $report->cash_on_hand_usd ?? 0
                                            );

                                    @endphp


                                    <tr>

                                        {{-- =================================================
                                            REPORT
                                        ================================================== --}}

                                        <td class="tx-report">

                                            <div class="tx-item-name">
                                                {{ $report->title }}
                                            </div>

                                            <div class="tx-item-sub">

                                                {{ $report->items->count() }}

                                                {{
                                                    $report->items->count() === 1
                                                        ? 'line item'
                                                        : 'line items'
                                                }}

                                            </div>

                                            <span class="tx-report-id">

                                                LIQ-{{
                                                    str_pad(
                                                        $report->id,
                                                        6,
                                                        '0',
                                                        STR_PAD_LEFT
                                                    )
                                                }}

                                            </span>

                                        </td>


                                        {{-- =================================================
                                            DATE
                                        ================================================== --}}

                                        <td class="tx-mono">

                                            @if($report->date_prepared)

                                                {{ $report->date_prepared->format('M d, Y') }}

                                            @else

                                                —

                                            @endif

                                        </td>


                                        {{-- =================================================
                                            PREPARED BY
                                        ================================================== --}}

                                        <td class="tx-person">

                                            @if($report->preparer)

                                                <div class="tx-person-name">
                                                    {{ $report->preparer->name }}
                                                </div>

                                                <div class="tx-person-sub">
                                                    USER #{{ $report->preparer->user_id }}
                                                </div>

                                            @else

                                                <span class="tx-item-sub">
                                                    Unknown User
                                                </span>

                                            @endif

                                        </td>


                                        {{-- =================================================
                                            COMPANY
                                        ================================================== --}}

                                        <td class="tx-person">

                                            @if($report->company)

                                                <div class="tx-person-name">
                                                    {{ $report->company->name }}
                                                </div>

                                                @if($report->company->code)

                                                    <div class="tx-person-sub">
                                                        {{ $report->company->code }}
                                                    </div>

                                                @endif

                                            @else

                                                <span class="tx-item-sub">
                                                    No company
                                                </span>

                                            @endif

                                        </td>


                                        {{-- =================================================
                                            EXCHANGE RATE
                                        ================================================== --}}

                                        <td>

                                            <span class="tx-rate">

                                                {{
                                                    number_format(
                                                        (float) ($report->exchange_rate ?? 0),
                                                        2
                                                    )
                                                }}

                                            </span>

                                        </td>


                                        {{-- =================================================
                                            TOTAL LIQUIDATED
                                        ================================================== --}}

                                        <td>

                                            <div class="tx-value">

                                                ₫{{
                                                    number_format(
                                                        $totalVndReport,
                                                        0
                                                    )
                                                }}

                                            </div>

                                            <div class="tx-value-sub">

                                                ${{
                                                    number_format(
                                                        $totalUsdReport,
                                                        2
                                                    )
                                                }}

                                            </div>

                                        </td>




                                        {{-- =================================================
                                            CASH ON HAND
                                        ================================================== --}}

                                        <td>

                                            <div class="tx-value">

                                                ₫{{
                                                    number_format(
                                                        $cashOnHandVnd,
                                                        0
                                                    )
                                                }}

                                            </div>

                                            <div class="tx-value-sub">

                                                ${{
                                                    number_format(
                                                        $cashOnHandUsd,
                                                        2
                                                    )
                                                }}

                                            </div>

                                        </td>


                                        {{-- =================================================
                                            STATUS
                                        ================================================== --}}

                                        <td>

                                            <span
                                                class="tx-badge {{ $statusClass }}"
                                            >

                                                <span class="tx-badge-dot"></span>

                                                {{ ucfirst($status) }}

                                            </span>

                                        </td>


                                        {{-- =================================================
                                            ACTIONS
                                        ================================================== --}}

                                        <td>

                                            <div class="tx-row-actions">

                                                {{-- VIEW --}}

                                                <a
                                                    href="{{ route('liquidation.show', $report->id) }}"
                                                    class="tx-icon-btn"
                                                    title="View liquidation"
                                                    aria-label="View liquidation"
                                                >

                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
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
                                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                                        />

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                        />

                                                    </svg>

                                                </a>


                                                {{-- EDIT --}}

                                                <a
                                                    href="{{ route('liquidation.edit', $report->id) }}"
                                                    class="tx-icon-btn"
                                                    title="Edit liquidation"
                                                    aria-label="Edit liquidation"
                                                >

                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
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

                                                </a>


                                                {{-- DELETE --}}

                                                <form
                                                    method="POST"
                                                    action="{{ route('liquidation.destroy', $report->id) }}"
                                                    onsubmit="return confirm('Delete this liquidation report? This action cannot be undone.');"
                                                >

                                                    @csrf

                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="tx-icon-btn danger"
                                                        title="Delete liquidation"
                                                        aria-label="Delete liquidation"
                                                    >

                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
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
                                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397M4.772 5.79c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                                            />

                                                        </svg>

                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- =================================================
                        PAGINATION
                    ================================================== --}}

                    @if($reports->hasPages())

                        <div class="tx-table-footer">

                            <span class="tx-count">

                                Showing
                                {{ $reports->firstItem() }}
                                –
                                {{ $reports->lastItem() }}
                                of
                                {{ $reports->total() }}

                            </span>

                            <div class="tx-pagination">

                                {{ $reports->links() }}

                            </div>

                        </div>

                    @endif


                @else

                    {{-- =================================================
                        EMPTY STATE
                    ================================================== --}}

                    <div class="tx-empty">

                        <div class="tx-empty-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="26"
                                height="26"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                                />

                            </svg>

                        </div>


                        @if(request()->filled('search') || request()->filled('status'))

                            <p class="tx-empty-title">
                                No matching liquidation reports
                            </p>

                            <p class="tx-empty-sub">
                                No reports matched your current search
                                or status filter. Try changing your
                                search criteria.
                            </p>

                            <a
                                href="{{ route('liquidation.index') }}"
                                class="tx-btn-primary"
                                style="margin-top: 14px;"
                            >
                                Clear Filters
                            </a>

                        @else

                            <p class="tx-empty-title">
                                No liquidation reports found
                            </p>

                            <p class="tx-empty-sub">
                                Once you create a liquidation report,
                                it will appear here with its expenses,
                                receipts, PCF balance, cash-on-hand,
                                and approval status.
                            </p>

                            <a
                                href="{{ route('liquidation.create') }}"
                                class="tx-btn-primary"
                                style="margin-top: 14px;"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 4.5v15m7.5-7.5h-15"
                                    />

                                </svg>

                                Create First Report

                            </a>

                        @endif

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-mi_app>