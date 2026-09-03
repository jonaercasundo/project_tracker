
<x-mi_app>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>

    <style>
        /* =========================================================
           PRODUCT DASHBOARD
        ========================================================= */

        .tx-console {
            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;

            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e2e8f0;
            --tx-line-dark: #cbd5e1;

            --tx-primary: #2563eb;
            --tx-primary-dark: #1d4ed8;
            --tx-primary-soft: #eff6ff;
            --tx-primary-ink: #ffffff;

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-warning: #d97706;
            --tx-warning-soft: #fffbeb;

            --tx-lvl-1: #2563eb;
            --tx-lvl-1-soft: #eff6ff;

            --tx-lvl-2: #0891b2;
            --tx-lvl-2-soft: #ecfeff;

            --tx-lvl-3: #7c3aed;
            --tx-lvl-3-soft: #f5f3ff;

            --tx-lvl-4: #d97706;
            --tx-lvl-4-soft: #fffbeb;

            --tx-font-display:
                'Space Grotesk',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-body:
                'Inter',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-mono:
                'JetBrains Mono',
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
            letter-spacing: -0.015em;
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.02em;
        }


        /* =========================================================
           SHELL
        ========================================================= */

        .tx-shell {
            width: 100%;
            max-width: 1450px;

            margin: 0 auto;

            padding: 28px 28px 60px;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .tx-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            gap: 24px;

            padding-bottom: 24px;
            margin-bottom: 24px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-header-main {
            min-width: 0;
        }

        .tx-eyebrow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 7px;

            margin-bottom: 9px;

            color: var(--tx-ink-faint);

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tx-eyebrow-separator {
            opacity: .5;
        }

        .tx-eyebrow-current {
            color: var(--tx-primary);
        }

        .tx-title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 11px;
        }

        .tx-title {
            margin: 0;

            color: var(--tx-ink);

            font-family: var(--tx-font-display);

            font-size: clamp(28px, 3vw, 36px);
            font-weight: 700;

            line-height: 1.08;
        }

        .tx-live {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 5px 9px;

            border: 1px solid #bbf7d0;
            border-radius: 999px;

            background: var(--tx-success-soft);
            color: var(--tx-success);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .tx-live-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: currentColor;
        }

        .tx-subtitle {
            max-width: 700px;

            margin: 8px 0 0;

            color: var(--tx-ink-soft);

            font-size: 13px;
            line-height: 1.6;
        }

        .tx-header-actions {
            display: flex;
            align-items: center;

            gap: 9px;

            flex-shrink: 0;
        }

        .tx-btn-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            min-height: 42px;

            padding: 10px 17px;

            border: 1px solid var(--tx-primary);
            border-radius: 10px;

            background: var(--tx-primary);
            color: var(--tx-primary-ink);

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            transition:
                transform .15s ease,
                background .15s ease,
                box-shadow .15s ease;
        }

        .tx-btn-add:hover {
            background: var(--tx-primary-dark);

            transform: translateY(-1px);

            box-shadow:
                0 10px 24px -12px rgba(37, 99, 235, .8);
        }

        .tx-btn-add svg {
            width: 16px;
            height: 16px;
        }


        /* =========================================================
           KPI GRID
        ========================================================= */

        .tx-kpi-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;

            margin-bottom: 20px;
        }

        @media (min-width: 900px) {
            .tx-kpi-grid {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }
        }

        .tx-kpi-card {
            position: relative;

            min-width: 0;

            padding: 19px 20px;

            border: 1px solid var(--tx-line);
            border-radius: 15px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03),
                0 8px 22px rgba(15, 23, 42, .025);

            transition:
                transform .16s ease,
                border-color .16s ease,
                box-shadow .16s ease;
        }

        .tx-kpi-card:hover {
            border-color: var(--tx-line-dark);

            transform: translateY(-2px);

            box-shadow:
                0 3px 7px rgba(15, 23, 42, .035),
                0 14px 28px rgba(15, 23, 42, .045);
        }

        .tx-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;

            margin-bottom: 16px;
        }

        .tx-kpi-icon {
            width: 37px;
            height: 37px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;
        }

        .tx-kpi-icon svg {
            width: 17px;
            height: 17px;
        }

        .tx-kpi-trend {
            padding: 4px 8px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .04em;
            text-transform: uppercase;

            white-space: nowrap;
        }

        .tx-kpi-trend.up {
            background: var(--tx-lvl-1-soft);
            color: var(--tx-lvl-1);
        }

        .tx-kpi-trend.flat {
            background: #f1f5f9;
            color: var(--tx-ink-soft);
        }

        .tx-kpi-value {
            margin: 0;

            color: var(--tx-ink);

            font-family: var(--tx-font-display);

            font-size: 28px;
            font-weight: 700;

            line-height: 1;
        }

        .tx-kpi-label {
            margin: 7px 0 0;

            color: var(--tx-ink-soft);

            font-size: 11px;
            font-weight: 500;
        }

        .k1 .tx-kpi-icon {
            background: var(--tx-lvl-1-soft);
            color: var(--tx-lvl-1);
        }

        .k2 .tx-kpi-icon {
            background: var(--tx-lvl-2-soft);
            color: var(--tx-lvl-2);
        }

        .k3 .tx-kpi-icon {
            background: var(--tx-lvl-3-soft);
            color: var(--tx-lvl-3);
        }

        .k4 .tx-kpi-icon {
            background: var(--tx-lvl-4-soft);
            color: var(--tx-lvl-4);
        }


        /* =========================================================
           CARDS
        ========================================================= */

        .tx-card {
            min-width: 0;

            margin-bottom: 20px;

            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03),
                0 8px 20px rgba(15, 23, 42, .02);
        }

        .tx-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 16px;

            padding: 17px 20px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-card-head-left {
            display: flex;
            align-items: center;

            gap: 11px;

            min-width: 0;
        }

        .tx-card-icon {
            width: 36px;
            height: 36px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;
        }

        .tx-card-icon svg {
            width: 17px;
            height: 17px;
        }

        .tx-card-title-wrap {
            min-width: 0;
        }

        .tx-card-head h2 {
            margin: 0;

            color: var(--tx-ink);

            font-family: var(--tx-font-display);

            font-size: 15px;
            font-weight: 600;

            line-height: 1.2;
        }

        .tx-card-head p {
            margin: 3px 0 0;

            color: var(--tx-ink-soft);

            font-size: 11px;
            line-height: 1.4;
        }

        .tx-card-link {
            flex-shrink: 0;

            color: var(--tx-primary);

            font-size: 11px;
            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;
        }

        .tx-card-link:hover {
            text-decoration: underline;
        }

        .tx-card-body {
            padding: 20px;
        }


        /* =========================================================
           ANALYTICS
        ========================================================= */

        .tx-analytics-row {
            display: grid;

            grid-template-columns: 1fr;

            gap: 20px;
        }

        @media (min-width: 1024px) {
            .tx-analytics-row {
                grid-template-columns: .9fr 1.25fr;
            }
        }

        .tx-chart-wrap {
            position: relative;

            width: 100%;
            height: 240px;
        }

        .tx-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;

            height: 100%;

            color: var(--tx-ink-faint);

            font-size: 11px;
        }

        .tx-legend {
            display: flex;
            flex-wrap: wrap;

            gap: 8px 16px;

            margin-top: 15px;
        }

        .tx-legend-item {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            color: var(--tx-ink-soft);

            font-size: 10px;
        }

        .tx-legend-dot {
            width: 7px;
            height: 7px;

            flex-shrink: 0;

            border-radius: 50%;
        }

        .tx-legend-count {
            color: var(--tx-ink);

            font-family: var(--tx-font-mono);

            font-size: 10px;
            font-weight: 600;
        }


        /* =========================================================
           TAXONOMY OVERVIEW
        ========================================================= */

        .tx-ladder {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .tx-rung {
            display: flex;
            align-items: center;

            gap: 11px;

            min-width: 0;

            padding: 18px 20px;

            border-right: 1px solid var(--tx-line);

            color: var(--tx-ink);

            text-decoration: none;

            transition: background .15s ease;
        }

        .tx-rung:last-child {
            border-right: none;
        }

        .tx-rung:hover {
            background: var(--tx-bg);
        }

        .tx-rung-dot {
            width: 9px;
            height: 9px;

            flex-shrink: 0;

            border-radius: 50%;
        }

        .tx-rung-content {
            min-width: 0;
        }

        .tx-rung-value {
            margin: 0;

            color: var(--tx-ink);

            font-family: var(--tx-font-display);

            font-size: 20px;
            font-weight: 700;

            line-height: 1;
        }

        .tx-rung-label {
            margin: 5px 0 0;

            color: var(--tx-ink-faint);

            font-size: 10px;
            font-weight: 600;

            line-height: 1.25;
        }


        /* =========================================================
           RECENT PRODUCTS
        ========================================================= */

        .tx-table-scroll {
            width: 100%;

            overflow-x: auto;

            scrollbar-width: thin;
        }

        .tx-recent-table {
            width: 100%;

            min-width: 720px;

            border-collapse: collapse;

            font-size: 11px;
        }

        .tx-recent-table thead th {
            padding: 0 0 10px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-display);

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .06em;

            text-align: left;

            text-transform: uppercase;
        }

        .tx-recent-table thead th:not(:first-child),
        .tx-recent-table tbody td:not(:first-child) {
            padding-left: 16px;
        }

        .tx-recent-table tbody td {
            padding: 11px 0;

            border-top: 1px solid var(--tx-line);

            color: var(--tx-ink-soft);

            vertical-align: middle;
        }

        .tx-recent-table tbody tr {
            transition: background .12s ease;
        }

        .tx-recent-table tbody tr:hover {
            background: #fafbfd;
        }

        .tx-recent-item {
            display: inline-block;

            max-width: 280px;

            overflow: hidden;

            color: var(--tx-ink);

            font-weight: 600;

            text-decoration: none;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .tx-recent-item:hover {
            color: var(--tx-primary);
        }

        .tx-recent-sku {
            display: inline-block;

            padding: 4px 7px;

            border-radius: 6px;

            background: var(--tx-primary-soft);
            color: var(--tx-primary);

            font-family: var(--tx-font-mono);

            font-size: 9px;
            font-weight: 600;
        }

        .tx-recent-taxo {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            max-width: 190px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .tx-recent-taxo .dot {
            width: 6px;
            height: 6px;

            flex-shrink: 0;

            border-radius: 50%;

            background: var(--tx-lvl-1);
        }

        .tx-status-badge {
            display: inline-flex;
            align-items: center;

            padding: 4px 8px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 700;

            white-space: nowrap;
        }

        .tx-status-badge.Available {
            background: var(--tx-success-soft);
            color: var(--tx-success);
        }

        .tx-status-badge.Assigned {
            background: var(--tx-lvl-2-soft);
            color: var(--tx-lvl-2);
        }

        .tx-status-badge.Repair {
            background: var(--tx-warning-soft);
            color: var(--tx-warning);
        }

        .tx-status-badge.Disposed {
            background: var(--tx-danger-soft);
            color: var(--tx-danger);
        }

        .tx-added {
            color: var(--tx-ink-faint);

            font-size: 10px;

            white-space: nowrap;
        }

        .tx-empty-note {
            margin: 0;

            padding: 38px 15px;

            color: var(--tx-ink-faint);

            font-size: 11px;

            text-align: center;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .tx-shell {
                padding: 24px 18px 50px;
            }

            .tx-header {
                align-items: flex-start;
            }

            .tx-ladder {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .tx-rung:nth-child(2) {
                border-right: none;
            }

            .tx-rung:nth-child(-n + 2) {
                border-bottom: 1px solid var(--tx-line);
            }
        }


        @media (max-width: 680px) {

            .tx-shell {
                padding: 18px 13px 40px;
            }

            .tx-header {
                flex-direction: column;

                gap: 15px;

                padding-bottom: 18px;
                margin-bottom: 18px;
            }

            .tx-header-actions {
                width: 100%;
            }

            .tx-btn-add {
                width: 100%;
            }

            .tx-title {
                font-size: 27px;
            }

            .tx-subtitle {
                font-size: 11px;
            }

            .tx-kpi-grid {
                gap: 9px;

                margin-bottom: 14px;
            }

            .tx-kpi-card {
                padding: 15px;

                border-radius: 13px;
            }

            .tx-kpi-icon {
                width: 32px;
                height: 32px;
            }

            .tx-kpi-value {
                font-size: 23px;
            }

            .tx-kpi-label {
                font-size: 9px;
            }

            .tx-kpi-trend {
                display: none;
            }

            .tx-card {
                margin-bottom: 14px;

                border-radius: 14px;
            }

            .tx-card-head {
                align-items: flex-start;

                padding: 14px;
            }

            .tx-card-head p {
                font-size: 9px;
            }

            .tx-card-link {
                display: none;
            }

            .tx-card-body {
                padding: 14px;
            }

            .tx-chart-wrap {
                height: 210px;
            }

            .tx-ladder {
                grid-template-columns: 1fr;
            }

            .tx-rung {
                border-right: none;
                border-bottom: 1px solid var(--tx-line);
            }

            .tx-rung:last-child {
                border-bottom: none;
            }

            .tx-rung:nth-child(2) {
                border-right: none;
            }
        }
    </style>


    <div class="tx-console">

        <div class="tx-shell">


            {{-- =====================================================
                 HEADER
            ====================================================== --}}

            <header class="tx-header">

                <div class="tx-header-main">

                    <div class="tx-eyebrow">

                        <span>
                            Product Database
                        </span>

                        <span class="tx-eyebrow-separator">
                            /
                        </span>

                        <span class="tx-eyebrow-current">
                            Dashboard
                        </span>

                    </div>


                    <div class="tx-title-row">

                        <h1 class="tx-title">
                            Product Dashboard
                        </h1>

                        <span class="tx-live">
                            <span class="tx-live-dot"></span>
                            Live
                        </span>

                    </div>


                    <p class="tx-subtitle">
                        Monitor your product catalog, taxonomy structure,
                        product status, and recent database activity from one place.
                    </p>

                </div>


                <div class="tx-header-actions">

                    <a
                        href="{{ route('mi_app.create') }}"
                        class="tx-btn-add"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
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

                        Add Product

                    </a>

                </div>

            </header>


            {{-- =====================================================
                 KPI CARDS
            ====================================================== --}}

            <section class="tx-kpi-grid">


                {{-- Total Products --}}

                <div class="tx-kpi-card k1">

                    <div class="tx-kpi-top">

                        <span class="tx-kpi-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20.25 7.5l-8.25 4.5L3.75 7.5M20.25 7.5l-8.25-4.5L3.75 7.5m16.5 0v9l-8.25 4.5m0-13.5v13.5m0-13.5L3.75 7.5m8.25 13.5L3.75 16.5v-9"
                                />
                            </svg>

                        </span>

                        <span class="tx-kpi-trend up">
                            Total
                        </span>

                    </div>


                    <p class="tx-kpi-value">
                        {{ $stats['total_products'] ?? 0 }}
                    </p>

                    <p class="tx-kpi-label">
                        Total Products
                    </p>

                </div>


                {{-- Active Products --}}

                <div class="tx-kpi-card k2">

                    <div class="tx-kpi-top">

                        <span class="tx-kpi-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>

                        </span>

                        <span class="tx-kpi-trend flat">
                            Available
                        </span>

                    </div>


                    <p class="tx-kpi-value">
                        {{ $stats['active_products'] ?? 0 }}
                    </p>

                    <p class="tx-kpi-label">
                        Active Products
                    </p>

                </div>


                {{-- Categories --}}

                <div class="tx-kpi-card k3">

                    <div class="tx-kpi-top">

                        <span class="tx-kpi-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.25 4.5l7.5 7.5-7.5 7.5"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 4.5l7.5 7.5-7.5 7.5"
                                />
                            </svg>

                        </span>

                        <span class="tx-kpi-trend flat">
                            Taxonomy
                        </span>

                    </div>


                    <p class="tx-kpi-value">
                        {{ $stats['total_categories'] ?? 0 }}
                    </p>

                    <p class="tx-kpi-label">
                        Categories
                    </p>

                </div>


                {{-- Collections --}}

                <div class="tx-kpi-card k4">

                    <div class="tx-kpi-top">

                        <span class="tx-kpi-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 6h.008v.008H6V6z"
                                />
                            </svg>

                        </span>

                        <span class="tx-kpi-trend flat">
                            Taxonomy
                        </span>

                    </div>


                    <p class="tx-kpi-value">
                        {{ $stats['total_collections'] ?? 0 }}
                    </p>

                    <p class="tx-kpi-label">
                        Collections
                    </p>

                </div>

            </section>


            {{-- =====================================================
                 TAXONOMY OVERVIEW
            ====================================================== --}}

            <section class="tx-card">

                <div class="tx-card-head">

                    <div class="tx-card-head-left">

                        <span
                            class="tx-card-icon"
                            style="
                                background: var(--tx-line);
                                color: var(--tx-ink-soft);
                            "
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 7h18M3 12h12M3 17h6"
                                />
                            </svg>

                        </span>


                        <div class="tx-card-title-wrap">

                            <h2>
                                Taxonomy Overview
                            </h2>

                            <p>
                                Counts across each level of the product hierarchy
                            </p>

                        </div>

                    </div>


                    <a
                        href="{{ route('mi_app.settings') }}"
                        class="tx-card-link"
                    >
                        Open Settings →
                    </a>

                </div>


                <div class="tx-ladder">


                    {{-- Category --}}

                    <a
                        href="{{ route('mi_app.settings') }}#level-category"
                        class="tx-rung"
                    >

                        <span
                            class="tx-rung-dot"
                            style="background: var(--tx-lvl-1);"
                        ></span>


                        <div class="tx-rung-content">

                            <p class="tx-rung-value">
                                {{ $taxonomyCounts['categories'] ?? 0 }}
                            </p>

                            <p class="tx-rung-label">
                                Categories
                            </p>

                        </div>

                    </a>


                    {{-- Sub Category --}}

                    <a
                        href="{{ route('mi_app.settings') }}#level-subcategory"
                        class="tx-rung"
                    >

                        <span
                            class="tx-rung-dot"
                            style="background: var(--tx-lvl-2);"
                        ></span>


                        <div class="tx-rung-content">

                            <p class="tx-rung-value">
                                {{ $taxonomyCounts['sub_categories'] ?? 0 }}
                            </p>

                            <p class="tx-rung-label">
                                Sub Categories
                            </p>

                        </div>

                    </a>


                    {{-- Product Type --}}

                    <a
                        href="{{ route('mi_app.settings') }}#level-subsubcategory"
                        class="tx-rung"
                    >

                        <span
                            class="tx-rung-dot"
                            style="background: var(--tx-lvl-3);"
                        ></span>


                        <div class="tx-rung-content">

                            <p class="tx-rung-value">
                                {{ $taxonomyCounts['product_types'] ?? 0 }}
                            </p>

                            <p class="tx-rung-label">
                                Sub Sub Categories
                            </p>

                        </div>

                    </a>


                    {{-- Collection --}}

                    <a
                        href="{{ route('mi_app.settings') }}#level-collection"
                        class="tx-rung"
                    >

                        <span
                            class="tx-rung-dot"
                            style="background: var(--tx-lvl-4);"
                        ></span>


                        <div class="tx-rung-content">

                            <p class="tx-rung-value">
                                {{ $taxonomyCounts['collections'] ?? 0 }}
                            </p>

                            <p class="tx-rung-label">
                                Collections
                            </p>

                        </div>

                    </a>

                </div>

            </section>


            {{-- =====================================================
                 RECENT PRODUCTS
            ====================================================== --}}

            <section class="tx-card">

                <div class="tx-card-head">

                    <div class="tx-card-head-left">

                        <span
                            class="tx-card-icon"
                            style="
                                background: var(--tx-warning-soft);
                                color: var(--tx-warning);
                            "
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6h4.5"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20.25 12a8.25 8.25 0 11-16.5 0 8.25 8.25 0 0116.5 0z"
                                />
                            </svg>

                        </span>


                        <div class="tx-card-title-wrap">

                            <h2>
                                Recently Added
                            </h2>

                            <p>
                                Latest products entered into the database
                            </p>

                        </div>

                    </div>


                    <a
                        href="{{ route('mi_app.index') }}"
                        class="tx-card-link"
                    >
                        View all products →
                    </a>

                </div>


                <div
                    class="tx-card-body"
                    style="padding-top: 14px;"
                >

                    @if(isset($recentProducts) && count($recentProducts))

                        <div class="tx-table-scroll">

                            <table class="tx-recent-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Item
                                        </th>

                                        <th>
                                            SKU
                                        </th>

                                        <th>
                                            Category
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Added
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($recentProducts as $product)

                                        <tr>

                                            <td>

                                                <a
                                                    href="{{ route('mi_app.show', $product->product_id) }}"
                                                    class="tx-recent-item"
                                                    title="{{ $product->item_name }}"
                                                >
                                                    {{ $product->item_name }}
                                                </a>

                                            </td>


                                            <td>

                                                @if($product->sku)

                                                    <span class="tx-recent-sku">
                                                        {{ $product->sku }}
                                                    </span>

                                                @else

                                                    <span
                                                        style="
                                                            color: var(--tx-ink-faint);
                                                        "
                                                    >
                                                        —
                                                    </span>

                                                @endif

                                            </td>


                                            <td>

                                                <span class="tx-recent-taxo">

                                                    <span class="dot"></span>

                                                    {{ $product->category->name ?? '—' }}

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="tx-status-badge {{ $product->classification }}"
                                                >
                                                    {{ $product->classification }}
                                                </span>

                                            </td>


                                            <td>

                                                <span class="tx-added">

                                                    {{ optional($product->created_at)->diffForHumans() ?? '—' }}

                                                </span>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <p class="tx-empty-note">

                            No products yet — once you add products,
                            the most recent products will appear here.

                        </p>

                    @endif

                </div>

            </section>

        </div>

    </div>


    {{-- =============================================================
         CHARTS
    ============================================================= --}}

    <script>

        (function () {

            'use strict';


            /* =====================================================
               CHART PALETTE
            ===================================================== */

            const palette = [
                '#2563EB',
                '#0891B2',
                '#7C3AED',
                '#D97706',
                '#64748B',
                '#DC2626'
            ];


            /* =====================================================
               CLASSIFICATION BREAKDOWN
            ===================================================== */

            const classificationData =
                @json($classificationBreakdown ?? []);

            const classLabels =
                Object.keys(classificationData);

            const classValues =
                Object.values(classificationData);

            const classificationCanvas =
                document.getElementById(
                    'classificationChart'
                );

            const classificationLegend =
                document.getElementById(
                    'classificationLegend'
                );


            if (
                classificationCanvas &&
                classLabels.length
            ) {

                classLabels.forEach(
                    function (label, index) {

                        const item =
                            document.createElement('span');

                        item.className =
                            'tx-legend-item';


                        const dot =
                            document.createElement('span');

                        dot.className =
                            'tx-legend-dot';

                        dot.style.background =
                            palette[
                                index %
                                palette.length
                            ];


                        const labelText =
                            document.createTextNode(
                                label + ' '
                            );


                        const count =
                            document.createElement('span');

                        count.className =
                            'tx-legend-count';

                        count.textContent =
                            classValues[index];


                        item.appendChild(dot);
                        item.appendChild(labelText);
                        item.appendChild(count);

                        classificationLegend.appendChild(item);

                    }
                );


                new Chart(
                    classificationCanvas,
                    {
                        type: 'doughnut',

                        data: {

                            labels: classLabels,

                            datasets: [
                                {
                                    data: classValues,

                                    backgroundColor: palette,

                                    borderWidth: 0,

                                    hoverOffset: 5
                                }
                            ]
                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            cutout: '70%',

                            animation: {
                                duration: 650
                            },

                            plugins: {

                                legend: {
                                    display: false
                                },

                                tooltip: {

                                    padding: 10,

                                    titleFont: {
                                        family: 'Inter',
                                        weight: '600'
                                    },

                                    bodyFont: {
                                        family: 'Inter'
                                    }
                                }
                            }
                        }
                    }
                );

            } else if (classificationLegend) {

                classificationLegend.innerHTML =
                    '<span class="tx-empty-note">' +
                    'No classification data yet.' +
                    '</span>';

            }


            /* =====================================================
               CATEGORY DISTRIBUTION
            ===================================================== */

            const categoryData =
                @json($categoryBreakdown ?? []);

            const catLabels =
                categoryData.map(
                    function (category) {
                        return category.name;
                    }
                );

            const catValues =
                categoryData.map(
                    function (category) {
                        return category.count;
                    }
                );

            const categoryCanvas =
                document.getElementById(
                    'categoryChart'
                );


            if (
                categoryCanvas &&
                catLabels.length
            ) {

                new Chart(
                    categoryCanvas,
                    {
                        type: 'bar',

                        data: {

                            labels: catLabels,

                            datasets: [
                                {
                                    data: catValues,

                                    backgroundColor:
                                        '#0891B2',

                                    borderRadius: 6,

                                    borderSkipped: false,

                                    maxBarThickness: 22,

                                    hoverBackgroundColor:
                                        '#2563EB'
                                }
                            ]
                        },

                        options: {

                            indexAxis: 'y',

                            responsive: true,

                            maintainAspectRatio: false,

                            animation: {
                                duration: 650
                            },

                            plugins: {

                                legend: {
                                    display: false
                                },

                                tooltip: {

                                    padding: 10,

                                    titleFont: {
                                        family: 'Inter',
                                        weight: '600'
                                    },

                                    bodyFont: {
                                        family: 'Inter'
                                    }
                                }
                            },

                            scales: {

                                x: {

                                    beginAtZero: true,

                                    grid: {
                                        color: '#E2E8F0'
                                    },

                                    border: {
                                        display: false
                                    },

                                    ticks: {

                                        color: '#94A3B8',

                                        font: {
                                            family: 'Inter',
                                            size: 10
                                        },

                                        precision: 0
                                    }
                                },

                                y: {

                                    grid: {
                                        display: false
                                    },

                                    border: {
                                        display: false
                                    },

                                    ticks: {

                                        color: '#111827',

                                        font: {
                                            family: 'Inter',
                                            size: 10,
                                            weight: '600'
                                        }
                                    }
                                }
                            }
                        }
                    }
                );

            } else if (categoryCanvas) {

                const parent =
                    categoryCanvas.parentElement;

                categoryCanvas.style.display =
                    'none';


                const message =
                    document.createElement('div');

                message.className =
                    'tx-chart-empty';

                message.textContent =
                    'No category data yet.';

                parent.appendChild(message);

            }

        })();

    </script>

</x-mi_app>
