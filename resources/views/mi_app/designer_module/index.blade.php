<x-mi_app>
    {{-- =========================================================
        METROINC CENTRALIZED DATABASE
        Product / Designer Module Index
        ========================================================= --}}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <style>
        /* =========================================================
           DESIGN SYSTEM
           ========================================================= */

        .tx-console {
            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;
            --tx-surface-soft: #f8fafc;

            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e2e8f0;
            --tx-line-soft: #eef2f7;

            --tx-primary: #2563eb;
            --tx-primary-hover: #1d4ed8;
            --tx-primary-soft: #eff6ff;
            --tx-primary-ink: #ffffff;

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-warning: #d97706;
            --tx-warning-soft: #fffbeb;

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
                Menlo,
                Monaco,
                Consolas,
                monospace;

            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
            min-height: 100%;
        }

        /* =========================================================
           DARK MODE
           ========================================================= */

        .tx-console.dark {
            --tx-bg: #0f172a;
            --tx-surface: #111827;
            --tx-surface-soft: #172033;

            --tx-ink: #f8fafc;
            --tx-ink-soft: #94a3b8;
            --tx-ink-faint: #64748b;

            --tx-line: #273449;
            --tx-line-soft: #1e293b;

            --tx-primary: #3b82f6;
            --tx-primary-hover: #60a5fa;
            --tx-primary-soft: #172554;

            --tx-success: #10b981;
            --tx-success-soft: #052e24;

            --tx-danger: #ef4444;
            --tx-danger-soft: #3b1212;

            --tx-warning: #f59e0b;
            --tx-warning-soft: #3b2a0b;
        }

        /* =========================================================
           GLOBAL
           ========================================================= */

        .tx-console *,
        .tx-console *::before,
        .tx-console *::after {
            box-sizing: border-box;
        }

        .tx-display {
            font-family: var(--tx-font-display);
            letter-spacing: -0.02em;
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.01em;
        }

        /* =========================================================
           PAGE SHELL
           ========================================================= */

        .tx-shell {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 28px 24px 60px;
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
            margin-bottom: 22px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-header-content {
            min-width: 0;
        }

        .tx-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 8px;

            font-family: var(--tx-font-display);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;

            color: var(--tx-primary);
        }

        .tx-eyebrow-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--tx-success);
            box-shadow: 0 0 0 4px var(--tx-success-soft);
        }

        .tx-title {
            margin: 0;

            font-family: var(--tx-font-display);
            font-size: 30px;
            line-height: 1.15;
            font-weight: 700;

            color: var(--tx-ink);
        }

        .tx-subtitle {
            margin: 7px 0 0;

            max-width: 700px;

            font-size: 13px;
            line-height: 1.6;

            color: var(--tx-ink-soft);
        }

        /* =========================================================
           CONNECTION STATUS
           ========================================================= */

        .tx-status-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;

            margin-top: 13px;
        }

        .tx-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 6px 10px;

            border: 1px solid #d1fae5;
            border-radius: 999px;

            background: var(--tx-success-soft);
            color: var(--tx-success);

            font-size: 11px;
            font-weight: 700;
        }

        .tx-status-dot {
            position: relative;

            display: inline-flex;

            width: 7px;
            height: 7px;
        }

        .tx-status-dot-ping {
            position: absolute;
            inset: 0;

            width: 100%;
            height: 100%;

            border-radius: 999px;

            background: var(--tx-success);
            opacity: .55;

            animation: tx-ping 1.6s
                cubic-bezier(0, 0, .2, 1)
                infinite;
        }

        .tx-status-dot-core {
            position: relative;

            display: inline-flex;

            width: 7px;
            height: 7px;

            border-radius: 999px;

            background: var(--tx-success);
        }

        @keyframes tx-ping {
            75%,
            100% {
                transform: scale(2.3);
                opacity: 0;
            }
        }

        .tx-status-sep {
            color: var(--tx-ink-faint);
            font-size: 11px;
        }

        .tx-status-synced {
            color: var(--tx-ink-faint);
            font-size: 11px;
        }

        .tx-status-synced time {
            color: var(--tx-ink-soft);
        }

        /* =========================================================
           PRIMARY BUTTON
           ========================================================= */

        .tx-btn-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            flex-shrink: 0;

            min-height: 42px;
            padding: 0 17px;

            border: 1px solid var(--tx-primary);
            border-radius: 11px;

            background: var(--tx-primary);
            color: var(--tx-primary-ink);

            font-family: var(--tx-font-body);
            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .08);

            transition:
                background .18s ease,
                border-color .18s ease,
                transform .18s ease,
                box-shadow .18s ease;
        }

        .tx-btn-add:hover {
            background: var(--tx-primary-hover);
            border-color: var(--tx-primary-hover);

            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 10px 24px rgba(37, 99, 235, .18);
        }

        .tx-btn-add:active {
            transform: translateY(0);
        }

        .tx-btn-add svg {
            width: 16px;
            height: 16px;
        }

        /* =========================================================
           MAIN CARD
           ========================================================= */

        .tx-main-card {
            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03),
                0 8px 30px rgba(15, 23, 42, .035);
        }

        /* =========================================================
           TOOLBAR
           ========================================================= */

        .tx-toolbar-panel {
            padding: 16px 18px;

            border-bottom: 1px solid var(--tx-line);

            background: var(--tx-surface-soft);
        }

        .tx-toolbar-panel form {
            width: 100%;
        }

        /* =========================================================
           SEARCH / FILTER CONTROLS
           ========================================================= */

        .tx-main-card input[type="text"],
        .tx-main-card input[type="search"],
        .tx-main-card input[type="email"],
        .tx-main-card input[type="number"],
        .tx-main-card select {
            min-height: 40px;

            font-family: var(--tx-font-body);
            font-size: 13px;

            color: var(--tx-ink);

            background: var(--tx-surface);

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            outline: none;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .tx-main-card input[type="text"]:focus,
        .tx-main-card input[type="search"]:focus,
        .tx-main-card input[type="email"]:focus,
        .tx-main-card input[type="number"]:focus,
        .tx-main-card select:focus {
            border-color: var(--tx-primary);

            box-shadow:
                0 0 0 4px var(--tx-primary-soft);

            outline: none;
        }

        /* =========================================================
           TABLE AREA
           ========================================================= */

        .tx-table-panel {
            position: relative;

            width: 100%;

            overflow-x: auto;
            overflow-y: hidden;

            background: var(--tx-surface);

            scrollbar-width: thin;
            scrollbar-color: var(--tx-line) transparent;
        }

        .tx-table-panel::-webkit-scrollbar {
            height: 8px;
        }

        .tx-table-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        .tx-table-panel::-webkit-scrollbar-thumb {
            background: var(--tx-line);
            border-radius: 999px;
        }

        /* =========================================================
           TABLE
           ========================================================= */

        .tx-main-card table {
            width: 100%;

            border-collapse: separate;
            border-spacing: 0;

            font-family: var(--tx-font-body);
            font-size: 13px;

            color: var(--tx-ink);
        }

        .tx-main-card thead th {
            padding: 12px 15px;

            border-bottom: 1px solid var(--tx-line);

            background: var(--tx-surface-soft);

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-display);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;

            white-space: nowrap;
        }

        .tx-main-card tbody td {
            padding: 14px 15px;

            border-bottom: 1px solid var(--tx-line-soft);

            vertical-align: middle;

            color: var(--tx-ink-soft);

            white-space: nowrap;
        }

        .tx-main-card tbody tr {
            background: var(--tx-surface);

            transition:
                background .15s ease;
        }

        .tx-main-card tbody tr:hover {
            background: var(--tx-primary-soft);
        }

        .tx-main-card tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================================================
           TABLE TEXT HELPERS
           ========================================================= */

        .tx-table-primary {
            color: var(--tx-ink);
            font-weight: 600;
        }

        .tx-table-secondary {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-size: 11px;
        }

        .tx-table-code {
            display: inline-flex;
            align-items: center;

            padding: 3px 6px;

            border: 1px solid var(--tx-line);

            border-radius: 6px;

            background: var(--tx-surface-soft);

            color: var(--tx-ink-soft);

            font-family: var(--tx-font-mono);
            font-size: 10px;
            font-weight: 600;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */

        @media (max-width: 900px) {

            .tx-shell {
                padding: 22px 16px 45px;
            }

            .tx-header {
                align-items: flex-start;
                flex-direction: column;

                padding-bottom: 20px;
            }

            .tx-btn-add {
                width: 100%;
            }

            .tx-title {
                font-size: 26px;
            }
        }

        @media (max-width: 600px) {

            .tx-shell {
                padding: 18px 12px 35px;
            }

            .tx-title {
                font-size: 23px;
            }

            .tx-subtitle {
                font-size: 12px;
            }

            .tx-main-card {
                border-radius: 13px;
            }

            .tx-toolbar-panel {
                padding: 13px;
            }

            .tx-main-card thead th {
                padding: 10px 12px;
            }

            .tx-main-card tbody td {
                padding: 12px;
            }
        }

        /* =========================================================
           ACCESSIBILITY
           ========================================================= */

        .tx-btn-add:focus-visible {
            outline: 3px solid var(--tx-primary-soft);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {

            .tx-status-dot-ping {
                animation: none;
            }

            .tx-btn-add,
            .tx-main-card tbody tr {
                transition: none;
            }
        }
    </style>


    <div class="tx-console">

        <div class="tx-shell">

            {{-- =====================================================
                HEADER
                ===================================================== --}}

            <header class="tx-header">

                <div class="tx-header-content">

                    <div class="tx-eyebrow">
                        <span class="tx-eyebrow-dot"></span>
                        Metroinc Application
                    </div>

                    <h1 class="tx-title tx-display">
                        Metroinc Centralized Database
                    </h1>

                    <p class="tx-subtitle">
                        Manage and maintain the centralized product and
                        designer database for Metroinc operations.
                    </p>

                    {{-- Connection Status --}}
                    <div class="tx-status-row">

                        <span class="tx-status-pill">

                            <span class="tx-status-dot">
                                <span class="tx-status-dot-ping"></span>
                                <span class="tx-status-dot-core"></span>
                            </span>

                            Connected

                        </span>

                        <span class="tx-status-sep">
                            •
                        </span>

                        <span class="tx-status-synced">
                            Synced:
                            <time class="tx-mono">
                                Just now
                            </time>
                        </span>

                    </div>

                </div>


                {{-- =================================================
                    ADD PRODUCT
                    ================================================= --}}

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

                    <span>
                        Add Product
                    </span>

                </a>

            </header>


            {{-- =====================================================
                MAIN CONTENT
                ===================================================== --}}

            <main class="tx-main-card">

                {{-- =================================================
                    SEARCH / FILTER AREA
                    ================================================= --}}

                <section class="tx-toolbar-panel">

                    @include(
                        'mi_app.designer_module.partials._search'
                    )

                </section>


                {{-- =================================================
                    DATA TABLE
                    ================================================= --}}

                <section class="tx-table-panel">

                    @include(
                        'mi_app.designer_module.partials._table'
                    )

                </section>

            </main>

        </div>

    </div>

</x-mi_app>