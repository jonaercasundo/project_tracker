<x-mi_app>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <style>
        /* =========================================================
           MI APP — TAXONOMY CONSOLE
        ========================================================= */

        .tx-console {
            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;
            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e2e8f0;
            --tx-line-strong: #cbd5e1;

            --tx-primary: #2563eb;
            --tx-primary-dark: #1d4ed8;
            --tx-primary-ink: #ffffff;
            --tx-primary-soft: #eff6ff;

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-lvl-1: #2563eb;
            --tx-lvl-1-soft: #eff6ff;

            --tx-lvl-2: #059669;
            --tx-lvl-2-soft: #ecfdf5;

            --tx-lvl-3: #7c3aed;
            --tx-lvl-3-soft: #f5f3ff;

            --tx-lvl-4: #d97706;
            --tx-lvl-4-soft: #fffbeb;

            --tx-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --tx-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --tx-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;

            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        .tx-console.dark {
            --tx-bg: #0f172a;
            --tx-surface: #111827;
            --tx-ink: #f8fafc;
            --tx-ink-soft: #94a3b8;
            --tx-ink-faint: #64748b;

            --tx-line: #1e293b;
            --tx-line-strong: #334155;

            --tx-primary: #3b82f6;
            --tx-primary-dark: #2563eb;
            --tx-primary-soft: #172554;

            --tx-success: #10b981;
            --tx-success-soft: #052e25;

            --tx-danger: #ef4444;
            --tx-danger-soft: #3b1212;

            --tx-lvl-1-soft: #172554;
            --tx-lvl-2-soft: #052e25;
            --tx-lvl-3-soft: #2e1065;
            --tx-lvl-4-soft: #451a03;
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
            letter-spacing: -0.015em;
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.02em;
        }

        .tx-shell {
            width: 100%;
            max-width: 90rem;
            margin: 0 auto;
            padding: 2rem 1.5rem 5rem;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .tx-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5rem;

            padding-bottom: 1.75rem;
            margin-bottom: 1.75rem;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-header-content {
            min-width: 0;
        }

        .tx-eyebrow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.4rem;

            margin-bottom: 0.65rem;

            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;

            color: var(--tx-ink-faint);
        }

        .tx-eyebrow a {
            color: var(--tx-ink-soft);
            text-decoration: none;
            transition: color .15s ease;
        }

        .tx-eyebrow a:hover {
            color: var(--tx-primary);
        }

        .tx-title {
            margin: 0;
            font-size: clamp(1.75rem, 3vw, 2.35rem);
            font-weight: 700;
            line-height: 1.08;
        }

        .tx-subtitle {
            max-width: 55rem;
            margin: 0.55rem 0 0;

            color: var(--tx-ink-soft);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .tx-header-actions {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-shrink: 0;
        }

        .tx-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;

            min-height: 2.65rem;
            padding: 0.65rem 1rem;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);
            color: var(--tx-ink);

            font-size: 0.8rem;
            font-weight: 600;

            text-decoration: none;

            transition:
                border-color .15s ease,
                color .15s ease,
                background .15s ease,
                transform .15s ease;
        }

        .tx-back:hover {
            border-color: var(--tx-primary);
            color: var(--tx-primary);
            background: var(--tx-primary-soft);
            transform: translateX(-2px);
        }

        /* =========================================================
           TAXONOMY LADDER
        ========================================================= */

        .tx-ladder {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));

            margin-bottom: 1.75rem;

            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .tx-rung {
            position: relative;

            display: flex;
            align-items: center;
            gap: 0.75rem;

            min-width: 0;
            padding: 1rem 1.1rem;

            border-right: 1px solid var(--tx-line);

            color: var(--tx-ink);
            text-decoration: none;

            transition:
                background .15s ease,
                color .15s ease;
        }

        .tx-rung:last-child {
            border-right: none;
        }

        .tx-rung:hover {
            background: var(--tx-primary-soft);
        }

        .tx-rung-dot {
            width: 0.65rem;
            height: 0.65rem;

            flex-shrink: 0;

            border-radius: 999px;

            background: var(--dot-color);
            box-shadow: 0 0 0 4px var(--dot-soft);
        }

        .tx-rung-content {
            min-width: 0;
        }

        .tx-rung-label {
            display: inline-block;

            font-size: 0.8rem;
            font-weight: 700;

            white-space: nowrap;
        }

        .tx-rung-sub {
            display: inline-block;
            margin-top: 0.15rem;

            color: var(--tx-ink-faint);

            font-size: 0.68rem;

            white-space: nowrap;
        }

        .tx-rung::after {
            content: '';

            position: absolute;
            right: -4px;
            top: 50%;

            width: 7px;
            height: 7px;

            transform: translateY(-50%) rotate(45deg);

            border-top: 1px solid var(--tx-line);
            border-right: 1px solid var(--tx-line);

            background: var(--tx-surface);

            z-index: 2;
        }

        .tx-rung:last-child::after {
            display: none;
        }

        .lvl-1 .tx-rung-dot {
            --dot-color: var(--tx-lvl-1);
            --dot-soft: var(--tx-lvl-1-soft);
        }

        .lvl-2 .tx-rung-dot {
            --dot-color: var(--tx-lvl-2);
            --dot-soft: var(--tx-lvl-2-soft);
        }

        .lvl-3 .tx-rung-dot {
            --dot-color: var(--tx-lvl-3);
            --dot-soft: var(--tx-lvl-3-soft);
        }

        .lvl-4 .tx-rung-dot {
            --dot-color: var(--tx-lvl-4);
            --dot-soft: var(--tx-lvl-4-soft);
        }

        /* =========================================================
           CARDS
        ========================================================= */

        .tx-card {
            margin-bottom: 1.35rem;

            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.035);

            scroll-margin-top: 1.5rem;
        }

        .tx-card:last-child {
            margin-bottom: 0;
        }

        .tx-card-head {
            display: flex;
            align-items: center;
            gap: 0.85rem;

            padding: 1.15rem 1.4rem;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-card-icon {
            width: 2.35rem;
            height: 2.35rem;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            font-family: var(--tx-font-mono);
            font-size: 0.72rem;
            font-weight: 700;
        }

        .tx-card-head-copy {
            min-width: 0;
        }

        .tx-card-head h2 {
            margin: 0;

            font-family: var(--tx-font-display);
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .tx-card-head p {
            margin: 0.2rem 0 0;

            color: var(--tx-ink-soft);

            font-size: 0.76rem;
            line-height: 1.45;
        }

        .tx-card-body {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));

            gap: 1.1rem;

            padding: 1.4rem;
        }

        @media (min-width: 768px) {
            .tx-card-body.cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .tx-card-body.cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        /* =========================================================
           LEVEL COLORS
        ========================================================= */

        .lvl-1 .tx-card-icon {
            background: var(--tx-lvl-1-soft);
            color: var(--tx-lvl-1);
        }

        .lvl-2 .tx-card-icon {
            background: var(--tx-lvl-2-soft);
            color: var(--tx-lvl-2);
        }

        .lvl-3 .tx-card-icon {
            background: var(--tx-lvl-3-soft);
            color: var(--tx-lvl-3);
        }

        .lvl-4 .tx-card-icon {
            background: var(--tx-lvl-4-soft);
            color: var(--tx-lvl-4);
        }

        /* =========================================================
           FORM FIELDS
        ========================================================= */

        .tx-field-group {
            min-width: 0;
        }

        .tx-label {
            display: block;

            margin-bottom: 0.45rem;

            color: var(--tx-ink-soft);

            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .tx-required {
            color: var(--tx-danger);
        }

        .tx-field {
            display: block;

            width: 100%;
            min-height: 2.65rem;

            padding: 0.68rem 0.85rem;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            outline: none;

            background: var(--tx-bg);
            color: var(--tx-ink);

            font-family: var(--tx-font-body);
            font-size: 0.82rem;

            transition:
                border-color .15s ease,
                box-shadow .15s ease,
                background .15s ease;
        }

        .tx-field::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-field:focus {
            border-color: var(--tx-primary);
            background: var(--tx-surface);

            box-shadow: 0 0 0 4px var(--tx-primary-soft);
        }

        .tx-field:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .tx-select-wrap {
            position: relative;
        }

        .tx-select-wrap select {
            appearance: none;
            padding-right: 2.4rem;
            cursor: pointer;
        }

        .tx-select-wrap svg {
            position: absolute;
            right: 0.8rem;
            top: 50%;

            width: 0.95rem;
            height: 0.95rem;

            transform: translateY(-50%);

            color: var(--tx-ink-faint);

            pointer-events: none;
        }

        .tx-help {
            margin-top: 0.35rem;

            color: var(--tx-ink-faint);

            font-size: 0.7rem;
            line-height: 1.4;
        }

        .tx-error {
            margin: 0.35rem 0 0;

            color: var(--tx-danger);

            font-size: 0.72rem;
            font-weight: 600;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .tx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;

            width: 100%;
            min-height: 2.65rem;

            padding: 0.65rem 1rem;

            border: 1px solid transparent;
            border-radius: 10px;

            background: var(--tx-primary);
            color: var(--tx-primary-ink);

            font-family: var(--tx-font-body);
            font-size: 0.8rem;
            font-weight: 700;

            cursor: pointer;
            text-decoration: none;

            transition:
                background .15s ease,
                border-color .15s ease,
                color .15s ease,
                transform .15s ease,
                box-shadow .15s ease;
        }

        .tx-btn:hover {
            background: var(--tx-primary-dark);
            box-shadow: 0 8px 20px -10px var(--tx-primary);
            transform: translateY(-1px);
        }

        .tx-btn-static {
            width: auto;
        }

        .tx-btn-edit {
            width: auto;

            min-height: 2.1rem;

            padding: 0.45rem 0.75rem;

            background: var(--tx-primary-soft);
            color: var(--tx-primary);

            font-size: 0.72rem;
        }

        .tx-btn-edit:hover {
            background: var(--tx-primary);
            color: #fff;

            box-shadow: none;
            transform: none;
        }

        .tx-btn-archive {
            width: auto;

            min-height: 2.1rem;

            padding: 0.45rem 0.75rem;

            background: var(--tx-danger-soft);
            color: var(--tx-danger);

            font-size: 0.72rem;
        }

        .tx-btn-archive:hover {
            background: var(--tx-danger);
            color: #fff;

            box-shadow: none;
            transform: none;
        }

        /* =========================================================
           TABLE HEADER
        ========================================================= */

        .tx-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 0.65rem;

            padding: 1rem 1.4rem;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-search {
            position: relative;

            flex: 1 1 20rem;
        }

        .tx-search svg {
            position: absolute;
            left: 0.85rem;
            top: 50%;

            width: 0.95rem;
            height: 0.95rem;

            transform: translateY(-50%);

            color: var(--tx-ink-faint);

            pointer-events: none;
        }

        .tx-search input.tx-field {
            padding-left: 2.35rem;
        }

        .tx-toolbar-select {
            flex: 0 1 13rem;
        }

        .tx-toolbar-reset {
            min-height: 2.65rem;

            padding: 0.65rem 0.9rem;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            background: var(--tx-surface);
            color: var(--tx-ink-soft);

            font-size: 0.76rem;
            font-weight: 700;

            cursor: pointer;

            transition:
                border-color .15s ease,
                background .15s ease,
                color .15s ease;
        }

        .tx-toolbar-reset:hover {
            border-color: var(--tx-danger);
            background: var(--tx-danger-soft);
            color: var(--tx-danger);
        }

        .tx-result-count {
            margin: 0;
            padding: 0.8rem 1.4rem 0;

            color: var(--tx-ink-faint);

            font-size: 0.7rem;
            font-weight: 600;
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .tx-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table.tx-table {
            width: 100%;
            min-width: 1050px;

            border-collapse: collapse;

            font-size: 0.78rem;
        }

        table.tx-table thead th {
            padding: 0.85rem 1rem;

            border-bottom: 1px solid var(--tx-line);

            background: var(--tx-bg);

            color: var(--tx-ink-faint);

            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-align: left;
            text-transform: uppercase;

            white-space: nowrap;
        }

        table.tx-table tbody td {
            padding: 0.85rem 1rem;

            border-bottom: 1px solid var(--tx-line);

            color: var(--tx-ink);

            vertical-align: middle;
        }

        table.tx-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.tx-table tbody tr:hover {
            background: var(--tx-bg);
        }

        /* =========================================================
           TABLE CELLS
        ========================================================= */

        .tx-code-badge {
            display: inline-flex;
            align-items: center;

            padding: 0.32rem 0.55rem;

            border: 1px solid var(--tx-line);
            border-radius: 7px;

            background: var(--tx-bg);
            color: var(--tx-ink);

            font-size: 0.67rem;
            font-weight: 600;

            white-space: nowrap;
        }

        .tx-cell-primary {
            display: flex;
            align-items: center;
            gap: 0.45rem;

            font-weight: 600;
        }

        .tx-swatch {
            width: 0.48rem;
            height: 0.48rem;

            flex-shrink: 0;

            border-radius: 999px;
        }

        .tx-dash {
            color: var(--tx-ink-faint);
        }

        .tx-crumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 0.3rem;

            color: var(--tx-ink-soft);

            font-size: 0.72rem;
            line-height: 1.5;
        }

        .tx-crumb .node {
            color: var(--tx-ink);
            font-weight: 500;
        }

        .tx-crumb .arrow {
            color: var(--tx-ink-faint);
        }

        .tx-actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;

            white-space: nowrap;
        }

        .tx-inline-form {
            display: inline-flex;
            margin: 0;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .tx-empty-state {
            padding: 3rem 1.5rem;

            text-align: center;

            color: var(--tx-ink-faint);

            font-size: 0.8rem;
        }

        .tx-empty-icon {
            width: 2.75rem;
            height: 2.75rem;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 0.75rem;

            border-radius: 12px;

            background: var(--tx-bg);
            color: var(--tx-ink-faint);
        }

        .tx-empty-state p {
            margin: 0;
        }

        /* =========================================================
           PAGINATION
        ========================================================= */

        .tx-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;

            gap: 1rem;

            padding: 1rem 1.4rem;

            border-top: 1px solid var(--tx-line);
        }

        .tx-pagination-info {
            color: var(--tx-ink-soft);

            font-size: 0.72rem;
        }

        .tx-pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tx-page-size.tx-field {
            width: auto;
            min-height: 2.2rem;

            padding: 0.45rem 0.7rem;

            font-size: 0.7rem;
        }

        .tx-page-btn {
            width: 2.2rem;
            height: 2.2rem;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 1px solid var(--tx-line);
            border-radius: 9px;

            background: var(--tx-surface);
            color: var(--tx-ink);

            cursor: pointer;

            transition:
                border-color .15s ease,
                background .15s ease,
                color .15s ease;
        }

        .tx-page-btn svg {
            width: 0.9rem;
            height: 0.9rem;
        }

        .tx-page-btn:hover:not(:disabled) {
            border-color: var(--tx-primary);
            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }

        .tx-page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .tx-page-current {
            min-width: 3.5rem;

            color: var(--tx-ink-soft);

            font-size: 0.72rem;
            font-weight: 700;

            text-align: center;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {
            .tx-shell {
                padding: 1.5rem 1rem 3rem;
            }

            .tx-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .tx-header-actions {
                width: 100%;
            }

            .tx-back {
                width: 100%;
            }

            .tx-ladder {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tx-rung {
                border-bottom: 1px solid var(--tx-line);
            }

            .tx-rung:nth-child(2) {
                border-right: none;
            }

            .tx-rung:nth-child(3),
            .tx-rung:nth-child(4) {
                border-bottom: none;
            }

            .tx-rung::after {
                display: none;
            }

            .tx-toolbar-select {
                flex: 1 1 12rem;
            }
        }

        @media (max-width: 640px) {
            .tx-shell {
                padding: 1rem 0.75rem 2.5rem;
            }

            .tx-header {
                padding-bottom: 1.25rem;
                margin-bottom: 1.25rem;
            }

            .tx-title {
                font-size: 1.65rem;
            }

            .tx-subtitle {
                font-size: 0.8rem;
            }

            .tx-ladder {
                grid-template-columns: 1fr;
                border-radius: 14px;
            }

            .tx-rung,
            .tx-rung:nth-child(2),
            .tx-rung:nth-child(3) {
                border-right: none;
                border-bottom: 1px solid var(--tx-line);
            }

            .tx-rung:last-child {
                border-bottom: none;
            }

            .tx-card {
                border-radius: 14px;
                margin-bottom: 1rem;
            }

            .tx-card-head {
                padding: 1rem;
            }

            .tx-card-body {
                padding: 1rem;
                gap: 0.9rem;
            }

            .tx-toolbar {
                padding: 0.9rem 1rem;
            }

            .tx-search,
            .tx-toolbar-select {
                flex: 1 1 100%;
            }

            .tx-toolbar-reset {
                width: 100%;
            }

            .tx-result-count {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .tx-pagination {
                align-items: flex-start;
                flex-direction: column;
                padding: 1rem;
            }

            .tx-pagination-controls {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>

    <div class="tx-console">

        <div class="tx-shell">

            {{-- =====================================================
                 PAGE HEADER
            ====================================================== --}}

            <div class="tx-header">

                <div class="tx-header-content">

                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.index') }}">
                            Product Database
                        </a>

                        <span>/</span>

                        <span>Settings</span>
                    </div>

                    <h1 class="tx-title tx-display">
                        Taxonomy Console
                    </h1>

                    <p class="tx-subtitle">
                        Build and maintain the product classification hierarchy:
                        Category, Sub Category, Sub Sub Category and Collection.
                        These structures are shared across the product database.
                    </p>

                </div>

                <div class="tx-header-actions">

                    <a
                        href="{{ route('mi_app.index') }}"
                        class="tx-back"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
                            />
                        </svg>

                        Back to Database
                    </a>

                </div>

            </div>


            {{-- =====================================================
                 TAXONOMY LADDER
            ====================================================== --}}

            <nav
                class="tx-ladder"
                aria-label="Taxonomy levels"
            >

                <a
                    href="#level-category"
                    class="tx-rung lvl-1"
                >
                    <span class="tx-rung-dot"></span>

                    <span class="tx-rung-content">
                        <span class="tx-rung-label tx-display">
                            Category
                        </span>

                        <br>

                        <span class="tx-rung-sub">
                            {{ $categories->count() }} defined
                        </span>
                    </span>
                </a>


                <a
                    href="#level-subcategory"
                    class="tx-rung lvl-2"
                >
                    <span class="tx-rung-dot"></span>

                    <span class="tx-rung-content">
                        <span class="tx-rung-label tx-display">
                            Sub Category
                        </span>

                        <br>

                        <span class="tx-rung-sub">
                            {{ $subCategories->count() }} defined
                        </span>
                    </span>
                </a>


                <a
                    href="#level-subsubcategory"
                    class="tx-rung lvl-3"
                >
                    <span class="tx-rung-dot"></span>

                    <span class="tx-rung-content">
                        <span class="tx-rung-label tx-display">
                            Sub Sub Category
                        </span>

                        <br>

                        <span class="tx-rung-sub">
                            {{ $productTypes->count() }} defined
                        </span>
                    </span>
                </a>


                <a
                    href="#level-collection"
                    class="tx-rung lvl-4"
                >
                    <span class="tx-rung-dot"></span>

                    <span class="tx-rung-content">
                        <span class="tx-rung-label tx-display">
                            Collection
                        </span>

                        <br>

                        <span class="tx-rung-sub">
                            Final taxonomy tier
                        </span>
                    </span>
                </a>

            </nav>


            {{-- =====================================================
                 SECTION 01 — CATEGORY
            ====================================================== --}}

            <div
                class="tx-card lvl-1"
                id="level-category"
            >

                <form
                    method="POST"
                    action="{{ route('mi_app.store') }}"
                    novalidate
                >

                    @csrf

                    <input
                        type="hidden"
                        name="entity_type"
                        value="category"
                    >

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            01
                        </span>

                        <div class="tx-card-head-copy">

                            <h2>
                                Category
                            </h2>

                            <p>
                                Top-level grouping and root of the product taxonomy.
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        <div class="tx-field-group">

                            <label
                                for="category_name"
                                class="tx-label"
                            >
                                Category Name
                                <span class="tx-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="category_name"
                                name="category_name"
                                value="{{ old('category_name') }}"
                                placeholder="e.g. Furniture"
                                required
                                class="tx-field"
                            >

                            @error('category_name')
                                <p class="tx-error">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div
                            class="tx-field-group"
                            style="align-self:end;"
                        >

                            <button
                                type="submit"
                                class="tx-btn"
                            >
                                Add Category
                            </button>

                        </div>

                    </div>

                </form>

            </div>


            {{-- =====================================================
                 SECTION 02 — SUB CATEGORY
            ====================================================== --}}

            <div
                class="tx-card lvl-2"
                id="level-subcategory"
            >

                <form
                    method="POST"
                    action="{{ route('mi_app.store') }}"
                    novalidate
                >

                    @csrf

                    <input
                        type="hidden"
                        name="entity_type"
                        value="sub_category"
                    >

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            02
                        </span>

                        <div class="tx-card-head-copy">

                            <h2>
                                Sub Category
                            </h2>

                            <p>
                                Groups products one level below the main Category.
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        <div class="tx-field-group">

                            <label
                                for="subcat_category_id"
                                class="tx-label"
                            >
                                Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="category_id"
                                    id="subcat_category_id"
                                    required
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach($categories as $category)

                                        <option
                                            value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}
                                        >
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('category_id')
                                <p class="tx-error">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div class="tx-field-group">

                            <label
                                for="sub_category_name"
                                class="tx-label"
                            >
                                Sub Category Name
                                <span class="tx-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="sub_category_name"
                                name="sub_category_name"
                                value="{{ old('sub_category_name') }}"
                                placeholder="e.g. Chairs"
                                required
                                class="tx-field"
                            >

                            @error('sub_category_name')
                                <p class="tx-error">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div
                            class="tx-field-group"
                            style="align-self:end;"
                        >

                            <button
                                type="submit"
                                class="tx-btn"
                            >
                                Add Sub Category
                            </button>

                        </div>

                    </div>

                </form>

            </div>


            {{-- =====================================================
                 SECTION 03 — SUB SUB CATEGORY
            ====================================================== --}}

            <div
                class="tx-card lvl-3"
                id="level-subsubcategory"
            >

                <form
                    method="POST"
                    action="{{ route('mi_app.store') }}"
                    novalidate
                >

                    @csrf

                    <input
                        type="hidden"
                        name="entity_type"
                        value="product_type"
                    >

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            03
                        </span>

                        <div class="tx-card-head-copy">

                            <h2>
                                Sub Sub Category
                            </h2>

                            <p>
                                The most specific product classification before Collection.
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        {{-- Category --}}

                        <div class="tx-field-group">

                            <label
                                for="ssc_category_id"
                                class="tx-label"
                            >
                                Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="category_id"
                                    id="ssc_category_id"
                                    required
                                    data-cascade-target="ssc_subcategory_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach($categories as $category)

                                        <option
                                            value="{{ $category->id }}"
                                        >
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                        </div>


                        {{-- Sub Category --}}

                        <div class="tx-field-group">

                            <label
                                for="ssc_subcategory_id"
                                class="tx-label"
                            >
                                Sub Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="sub_category_id"
                                    id="ssc_subcategory_id"
                                    required
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category First --
                                    </option>

                                    @foreach($subCategories as $subCategory)

                                        <option
                                            value="{{ $subCategory->id }}"
                                            data-parent="{{ $subCategory->category_id }}"
                                            disabled
                                        >
                                            {{ $subCategory->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                        </div>


                        {{-- Product Type --}}

                        <div class="tx-field-group">

                            <label
                                for="product_type_name"
                                class="tx-label"
                            >
                                Sub Sub Category Name
                                <span class="tx-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="product_type_name"
                                name="product_type_name"
                                value="{{ old('product_type_name') }}"
                                placeholder="e.g. Dining Chairs"
                                required
                                class="tx-field"
                            >

                        </div>


                        {{-- Submit --}}

                        <div
                            class="tx-field-group"
                            style="align-self:end;"
                        >

                            <button
                                type="submit"
                                class="tx-btn"
                            >
                                Add Sub Sub Category
                            </button>

                        </div>

                    </div>

                </form>

            </div>


            {{-- =====================================================
                 SECTION 04 — COLLECTION
            ====================================================== --}}

            <div
                class="tx-card lvl-4"
                id="level-collection"
            >

                <form
                    method="POST"
                    action="{{ route('mi_app.store') }}"
                    novalidate
                >

                    @csrf

                    <input
                        type="hidden"
                        name="entity_type"
                        value="collection"
                    >

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            04
                        </span>

                        <div class="tx-card-head-copy">

                            <h2>
                                Collection
                            </h2>

                            <p>
                                Final taxonomy tier, usually representing a seasonal or thematic product group.
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        {{-- Category --}}

                        <div class="tx-field-group">

                            <label
                                for="col_category_id"
                                class="tx-label"
                            >
                                Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="category_id"
                                    id="col_category_id"
                                    required
                                    data-cascade-target="col_subcategory_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach($categories as $category)

                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                        </div>


                        {{-- Sub Category --}}

                        <div class="tx-field-group">

                            <label
                                for="col_subcategory_id"
                                class="tx-label"
                            >
                                Sub Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="sub_category_id"
                                    id="col_subcategory_id"
                                    required
                                    data-cascade-target="col_subsubcategory_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category First --
                                    </option>

                                    @foreach($subCategories as $subCategory)

                                        <option
                                            value="{{ $subCategory->id }}"
                                            data-parent="{{ $subCategory->category_id }}"
                                            disabled
                                        >
                                            {{ $subCategory->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                        </div>


                        {{-- Sub Sub Category --}}

                        <div class="tx-field-group">

                            <label
                                for="col_subsubcategory_id"
                                class="tx-label"
                            >
                                Sub Sub Category
                                <span class="tx-required">*</span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    name="product_type_id"
                                    id="col_subsubcategory_id"
                                    required
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Sub Category First --
                                    </option>

                                    @foreach($productTypes as $productType)

                                        <option
                                            value="{{ $productType->id }}"
                                            data-parent="{{ $productType->sub_category_id }}"
                                            disabled
                                        >
                                            {{ $productType->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                        </div>


                        {{-- Collection Name --}}

                        <div class="tx-field-group">

                            <label
                                for="collection_name"
                                class="tx-label"
                            >
                                Collection Name
                                <span class="tx-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="collection_name"
                                name="collection_name"
                                value="{{ old('collection_name') }}"
                                placeholder="e.g. Spring 2026"
                                required
                                class="tx-field"
                            >

                        </div>

                    </div>


                    <div style="padding: 0 1.4rem 1.4rem;">

                        <button
                            type="submit"
                            class="tx-btn tx-btn-static"
                        >
                            Add Collection
                        </button>

                    </div>

                </form>

            </div>


            {{-- =====================================================
                 TAXONOMY STRUCTURE
            ====================================================== --}}

            <div class="tx-card">

                <div class="tx-card-head">

                    <span
                        class="tx-card-icon"
                        style="
                            background: var(--tx-bg);
                            color: var(--tx-ink-soft);
                            border: 1px solid var(--tx-line);
                        "
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 7h18M3 12h12M3 17h6"
                            />
                        </svg>

                    </span>

                    <div class="tx-card-head-copy">

                        <h2>
                            Taxonomy Structure
                        </h2>

                        <p>
                            Complete mapping from Category through Collection with generated taxonomy codes.
                        </p>

                    </div>

                </div>


                {{-- =================================================
                     SEARCH / FILTER TOOLBAR
                ================================================== --}}

                <div class="tx-toolbar">

                    <div class="tx-search">

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
                                d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>

                        <input
                            type="text"
                            id="tx-search-input"
                            class="tx-field"
                            placeholder="Search taxonomy, name or code..."
                            autocomplete="off"
                        >

                    </div>


                    <div class="tx-select-wrap tx-toolbar-select">

                        <select
                            id="tx-filter-category"
                            class="tx-field"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}">
                                    {{ $category->code }} — {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                        <svg
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>

                    </div>


                    <div class="tx-select-wrap tx-toolbar-select">

                        <select
                            id="tx-filter-level"
                            class="tx-field"
                        >

                            <option value="">
                                All Levels
                            </option>

                            <option value="1">
                                Category only
                            </option>

                            <option value="2">
                                Sub Category
                            </option>

                            <option value="3">
                                Sub Sub Category
                            </option>

                            <option value="4">
                                Collection
                            </option>

                        </select>

                        <svg
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>

                    </div>


                    <button
                        type="button"
                        id="tx-filter-reset"
                        class="tx-toolbar-reset"
                    >
                        Clear Filters
                    </button>

                </div>


                <p
                    id="tx-result-count"
                    class="tx-result-count"
                    aria-live="polite"
                ></p>


                {{-- =================================================
                     TAXONOMY TABLE
                ================================================== --}}

                <div class="tx-table-wrap">

                    <table
                        class="tx-table"
                        id="tx-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Taxonomy Code
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Sub Category
                                </th>

                                <th>
                                    Product Type
                                </th>

                                <th>
                                    Collection
                                </th>

                                <th>
                                    Hierarchy
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody id="tx-tbody">

                            @foreach($categories as $category)

                                {{-- =================================================
                                     LEVEL 1 — CATEGORY ONLY
                                ================================================== --}}

                                @if($category->subCategories->isEmpty())

                                    <tr
                                        data-level="1"
                                        data-cat="{{ $category->id }}"
                                        data-search="{{ strtolower($category->code . ' ' . $category->name) }}"
                                    >

                                        <td>
                                            <span class="tx-code-badge tx-mono">
                                                {{ $category->code }}
                                            </span>
                                        </td>

                                        <td>

                                            <span class="tx-cell-primary">

                                                <span
                                                    class="tx-swatch"
                                                    style="background: var(--tx-lvl-1);"
                                                ></span>

                                                {{ $category->name }}

                                            </span>

                                        </td>

                                        <td class="tx-dash">
                                            —
                                        </td>

                                        <td class="tx-dash">
                                            —
                                        </td>

                                        <td class="tx-dash">
                                            —
                                        </td>

                                        <td>

                                            <span class="tx-crumb">

                                                <span class="node">
                                                    {{ $category->name }}
                                                </span>

                                            </span>

                                        </td>

                                        <td>

                                            <div class="tx-actions">

                                                <a
                                                    href="{{ route('taxonomy.edit', ['type' => 'category', 'product' => $category->id]) }}"
                                                    class="tx-btn tx-btn-edit"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    action="{{ route('taxonomy.destroy', ['type' => 'category', 'product' => $category->id]) }}"
                                                    method="POST"
                                                    class="tx-inline-form"
                                                    onsubmit="return confirm('Archive this Category?');"
                                                >

                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="tx-btn tx-btn-archive"
                                                    >
                                                        Archive
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @endif


                                @foreach($category->subCategories as $subCategory)

                                    {{-- =================================================
                                         LEVEL 2 — SUB CATEGORY
                                    ================================================== --}}

                                    @if($subCategory->productTypes->isEmpty())

                                        <tr
                                            data-level="2"
                                            data-cat="{{ $category->id }}"
                                            data-search="{{ strtolower($category->code . ' ' . $subCategory->code . ' ' . $category->name . ' ' . $subCategory->name) }}"
                                        >

                                            <td>

                                                <span class="tx-code-badge tx-mono">
                                                    {{ $category->code }}-{{ $subCategory->code }}
                                                </span>

                                            </td>

                                            <td>
                                                {{ $category->name }}
                                            </td>

                                            <td>

                                                <span class="tx-cell-primary">

                                                    <span
                                                        class="tx-swatch"
                                                        style="background: var(--tx-lvl-2);"
                                                    ></span>

                                                    {{ $subCategory->name }}

                                                </span>

                                            </td>

                                            <td class="tx-dash">
                                                —
                                            </td>

                                            <td class="tx-dash">
                                                —
                                            </td>

                                            <td>

                                                <span class="tx-crumb">

                                                    <span class="node">
                                                        {{ $category->name }}
                                                    </span>

                                                    <span class="arrow">
                                                        →
                                                    </span>

                                                    <span class="node">
                                                        {{ $subCategory->name }}
                                                    </span>

                                                </span>

                                            </td>

                                            <td>

                                                <div class="tx-actions">

                                                    <a
                                                        href="{{ route('taxonomy.edit', ['type' => 'sub_category', 'product' => $subCategory->id]) }}"
                                                        class="tx-btn tx-btn-edit"
                                                    >
                                                        Edit
                                                    </a>

                                                    <form
                                                        action="{{ route('taxonomy.destroy', ['type' => 'sub_category', 'product' => $subCategory->id]) }}"
                                                        method="POST"
                                                        class="tx-inline-form"
                                                        onsubmit="return confirm('Archive this Sub Category?');"
                                                    >

                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            class="tx-btn tx-btn-archive"
                                                        >
                                                            Archive
                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    @endif


                                    @foreach($subCategory->productTypes as $productType)

                                        {{-- =================================================
                                             LEVEL 3 — PRODUCT TYPE
                                        ================================================== --}}

                                        @if($productType->collections->isEmpty())

                                            <tr
                                                data-level="3"
                                                data-cat="{{ $category->id }}"
                                                data-search="{{ strtolower($category->code . ' ' . $subCategory->code . ' ' . $productType->code . ' ' . $category->name . ' ' . $subCategory->name . ' ' . $productType->name) }}"
                                            >

                                                <td>

                                                    <span class="tx-code-badge tx-mono">
                                                        {{ $category->code }}-{{ $subCategory->code }}-{{ $productType->code }}
                                                    </span>

                                                </td>

                                                <td>
                                                    {{ $category->name }}
                                                </td>

                                                <td>
                                                    {{ $subCategory->name }}
                                                </td>

                                                <td>

                                                    <span class="tx-cell-primary">

                                                        <span
                                                            class="tx-swatch"
                                                            style="background: var(--tx-lvl-3);"
                                                        ></span>

                                                        {{ $productType->name }}

                                                    </span>

                                                </td>

                                                <td class="tx-dash">
                                                    —
                                                </td>

                                                <td>

                                                    <span class="tx-crumb">

                                                        <span class="node">
                                                            {{ $category->name }}
                                                        </span>

                                                        <span class="arrow">
                                                            →
                                                        </span>

                                                        <span class="node">
                                                            {{ $subCategory->name }}
                                                        </span>

                                                        <span class="arrow">
                                                            →
                                                        </span>

                                                        <span class="node">
                                                            {{ $productType->name }}
                                                        </span>

                                                    </span>

                                                </td>

                                                <td>

                                                    <div class="tx-actions">

                                                        <a
                                                            href="{{ route('taxonomy.edit', ['type' => 'product_type', 'product' => $productType->id]) }}"
                                                            class="tx-btn tx-btn-edit"
                                                        >
                                                            Edit
                                                        </a>

                                                        <form
                                                            action="{{ route('taxonomy.destroy', ['type' => 'product_type', 'product' => $productType->id]) }}"
                                                            method="POST"
                                                            class="tx-inline-form"
                                                            onsubmit="return confirm('Archive this Product Type?');"
                                                        >

                                                            @csrf
                                                            @method('DELETE')

                                                            <button
                                                                type="submit"
                                                                class="tx-btn tx-btn-archive"
                                                            >
                                                                Archive
                                                            </button>

                                                        </form>

                                                    </div>

                                                </td>

                                            </tr>

                                        @endif


                                        {{-- =================================================
                                             LEVEL 4 — COLLECTION
                                        ================================================== --}}

                                        @foreach($productType->collections as $collection)

                                            <tr
                                                data-level="4"
                                                data-cat="{{ $category->id }}"
                                                data-search="{{ strtolower($category->code . ' ' . $subCategory->code . ' ' . $productType->code . ' ' . $collection->code . ' ' . $category->name . ' ' . $subCategory->name . ' ' . $productType->name . ' ' . $collection->name) }}"
                                            >

                                                <td>

                                                    <span class="tx-code-badge tx-mono">
                                                        {{ $category->code }}-{{ $subCategory->code }}-{{ $productType->code }}-{{ $collection->code }}
                                                    </span>

                                                </td>

                                                <td>
                                                    {{ $category->name }}
                                                </td>

                                                <td>
                                                    {{ $subCategory->name }}
                                                </td>

                                                <td>
                                                    {{ $productType->name }}
                                                </td>

                                                <td>

                                                    <span class="tx-cell-primary">

                                                        <span
                                                            class="tx-swatch"
                                                            style="background: var(--tx-lvl-4);"
                                                        ></span>

                                                        {{ $collection->name }}

                                                    </span>

                                                </td>

                                                <td>

                                                    <span class="tx-crumb">

                                                        <span class="node">
                                                            {{ $category->name }}
                                                        </span>

                                                        <span class="arrow">
                                                            →
                                                        </span>

                                                        <span class="node">
                                                            {{ $subCategory->name }}
                                                        </span>

                                                        <span class="arrow">
                                                            →
                                                        </span>

                                                        <span class="node">
                                                            {{ $productType->name }}
                                                        </span>

                                                        <span class="arrow">
                                                            →
                                                        </span>

                                                        <span class="node">
                                                            {{ $collection->name }}
                                                        </span>

                                                    </span>

                                                </td>

                                                <td>

                                                    <div class="tx-actions">

                                                        <a
                                                            href="{{ route('taxonomy.edit', ['type' => 'collection', 'product' => $collection->id]) }}"
                                                            class="tx-btn tx-btn-edit"
                                                        >
                                                            Edit
                                                        </a>

                                                        <form
                                                            action="{{ route('taxonomy.destroy', ['type' => 'collection', 'product' => $collection->id]) }}"
                                                            method="POST"
                                                            class="tx-inline-form"
                                                            onsubmit="return confirm('Archive this Collection?');"
                                                        >

                                                            @csrf
                                                            @method('DELETE')

                                                            <button
                                                                type="submit"
                                                                class="tx-btn tx-btn-archive"
                                                            >
                                                                Archive
                                                            </button>

                                                        </form>

                                                    </div>

                                                </td>

                                            </tr>

                                        @endforeach

                                    @endforeach

                                @endforeach

                            @endforeach

                        </tbody>

                    </table>


                    {{-- EMPTY STATE --}}

                    <div
                        id="tx-empty-state"
                        class="tx-empty-state"
                        hidden
                    >

                        <div class="tx-empty-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>

                        </div>

                        <p>
                            No taxonomy rows match your search or filters.
                        </p>

                    </div>

                </div>


                {{-- =================================================
                     PAGINATION
                ================================================== --}}

                <div
                    class="tx-pagination"
                    id="tx-pagination"
                >

                    <div class="tx-pagination-info">

                        Showing
                        <strong id="tx-page-from">0</strong>
                        –
                        <strong id="tx-page-to">0</strong>

                        of

                        <strong id="tx-page-total">0</strong>

                    </div>


                    <div class="tx-pagination-controls">

                        <select
                            id="tx-page-size"
                            class="tx-field tx-page-size"
                        >

                            <option value="10">
                                10 / page
                            </option>

                            <option
                                value="25"
                                selected
                            >
                                25 / page
                            </option>

                            <option value="50">
                                50 / page
                            </option>

                            <option value="100">
                                100 / page
                            </option>

                        </select>


                        <button
                            type="button"
                            id="tx-prev-page"
                            class="tx-page-btn"
                            aria-label="Previous page"
                        >

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
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>

                        </button>


                        <span class="tx-page-current">

                            <span id="tx-page-num">
                                1
                            </span>

                            /

                            <span id="tx-page-count">
                                1
                            </span>

                        </span>


                        <button
                            type="button"
                            id="tx-next-page"
                            class="tx-page-btn"
                            aria-label="Next page"
                        >

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
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =============================================================
         CASCADING DROPDOWN LOGIC
    ============================================================= --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            function resetSelect(select, placeholder) {

                if (!select) {
                    return;
                }

                select.value = '';

                Array.from(select.options).forEach(function (option) {

                    if (!option.value) {
                        option.disabled = false;
                        option.hidden = false;
                        return;
                    }

                    option.disabled = true;
                    option.hidden = true;

                });

                if (placeholder) {
                    select.options[0].textContent = placeholder;
                }

            }


            function populateChild(parentSelect) {

                var targetId = parentSelect.getAttribute('data-cascade-target');

                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);

                if (!target) {
                    return;
                }

                var selectedParent = parentSelect.value;

                target.value = '';

                Array.from(target.options).forEach(function (option) {

                    if (!option.value) {

                        option.disabled = false;
                        option.hidden = false;

                        return;
                    }

                    var parentId = option.getAttribute('data-parent');

                    var belongs =
                        selectedParent !== '' &&
                        parentId === selectedParent;

                    option.disabled = !belongs;
                    option.hidden = !belongs;

                });

                var nextTargetId =
                    target.getAttribute('data-cascade-target');

                if (nextTargetId) {

                    var nextTarget =
                        document.getElementById(nextTargetId);

                    if (nextTarget) {

                        resetSelect(
                            nextTarget,
                            '-- Select Sub Category First --'
                        );

                    }

                }

            }


            document
                .querySelectorAll('[data-cascade-target]')
                .forEach(function (parentSelect) {

                    parentSelect.addEventListener(
                        'change',
                        function () {

                            populateChild(parentSelect);

                        }
                    );

                });


            /*
             * Initialize all cascading dropdowns.
             *
             * This ensures that child options are disabled/hidden
             * when the page first loads instead of only after
             * the user changes the parent dropdown.
             */

            document
                .querySelectorAll('[data-cascade-target]')
                .forEach(function (parentSelect) {

                    var targetId =
                        parentSelect.getAttribute('data-cascade-target');

                    var target =
                        document.getElementById(targetId);

                    if (!target) {
                        return;
                    }

                    resetSelect(
                        target,
                        '-- Select Category First --'
                    );

                });

        });

    </script>


    {{-- =============================================================
         TAXONOMY TABLE
         SEARCH + FILTER + PAGINATION
    ============================================================= --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            var tbody =
                document.getElementById('tx-tbody');

            if (!tbody) {
                return;
            }


            var allRows =
                Array.from(
                    tbody.querySelectorAll('tr')
                );


            var searchInput =
                document.getElementById('tx-search-input');

            var categoryFilter =
                document.getElementById('tx-filter-category');

            var levelFilter =
                document.getElementById('tx-filter-level');

            var resetBtn =
                document.getElementById('tx-filter-reset');

            var pageSizeSelect =
                document.getElementById('tx-page-size');

            var prevBtn =
                document.getElementById('tx-prev-page');

            var nextBtn =
                document.getElementById('tx-next-page');

            var pageNumEl =
                document.getElementById('tx-page-num');

            var pageCountEl =
                document.getElementById('tx-page-count');

            var pageFromEl =
                document.getElementById('tx-page-from');

            var pageToEl =
                document.getElementById('tx-page-to');

            var pageTotalEl =
                document.getElementById('tx-page-total');

            var resultCountEl =
                document.getElementById('tx-result-count');

            var emptyState =
                document.getElementById('tx-empty-state');

            var table =
                document.getElementById('tx-table');

            var paginationBar =
                document.getElementById('tx-pagination');


            var currentPage = 1;

            var pageSize =
                parseInt(
                    pageSizeSelect.value,
                    10
                );


            function getFilteredRows() {

                var term =
                    searchInput.value
                        .trim()
                        .toLowerCase();

                var category =
                    categoryFilter.value;

                var level =
                    levelFilter.value;


                return allRows.filter(function (row) {

                    var searchData =
                        (
                            row.getAttribute('data-search') || ''
                        ).toLowerCase();

                    var rowCategory =
                        row.getAttribute('data-cat');

                    var rowLevel =
                        row.getAttribute('data-level');


                    if (
                        term &&
                        searchData.indexOf(term) === -1
                    ) {
                        return false;
                    }


                    if (
                        category &&
                        rowCategory !== category
                    ) {
                        return false;
                    }


                    if (
                        level &&
                        rowLevel !== level
                    ) {
                        return false;
                    }


                    return true;

                });

            }


            function render() {

                var filtered =
                    getFilteredRows();

                var total =
                    filtered.length;

                var pageCount =
                    Math.max(
                        1,
                        Math.ceil(
                            total / pageSize
                        )
                    );


                if (currentPage > pageCount) {
                    currentPage = pageCount;
                }

                if (currentPage < 1) {
                    currentPage = 1;
                }


                allRows.forEach(function (row) {

                    row.style.display = 'none';

                });


                var start =
                    (currentPage - 1) * pageSize;

                var end =
                    Math.min(
                        start + pageSize,
                        total
                    );


                filtered
                    .slice(start, end)
                    .forEach(function (row) {

                        row.style.display = '';

                    });


                pageNumEl.textContent =
                    currentPage;

                pageCountEl.textContent =
                    pageCount;

                pageFromEl.textContent =
                    total === 0
                        ? 0
                        : start + 1;

                pageToEl.textContent =
                    end;

                pageTotalEl.textContent =
                    total;


                prevBtn.disabled =
                    currentPage <= 1;

                nextBtn.disabled =
                    currentPage >= pageCount;


                var hasFilter =
                    searchInput.value.trim() ||
                    categoryFilter.value ||
                    levelFilter.value;


                resultCountEl.textContent =
                    hasFilter

                        ? total +
                          ' matching row' +
                          (total === 1 ? '' : 's') +
                          ' of ' +
                          allRows.length +
                          ' total'

                        : total +
                          ' row' +
                          (total === 1 ? '' : 's') +
                          ' total';


                var isEmpty =
                    total === 0;


                emptyState.hidden =
                    !isEmpty;


                table.style.display =
                    isEmpty
                        ? 'none'
                        : '';


                paginationBar.style.display =
                    isEmpty
                        ? 'none'
                        : '';

            }


            searchInput.addEventListener(
                'input',
                function () {

                    currentPage = 1;

                    render();

                }
            );


            categoryFilter.addEventListener(
                'change',
                function () {

                    currentPage = 1;

                    render();

                }
            );


            levelFilter.addEventListener(
                'change',
                function () {

                    currentPage = 1;

                    render();

                }
            );


            pageSizeSelect.addEventListener(
                'change',
                function () {

                    pageSize =
                        parseInt(
                            pageSizeSelect.value,
                            10
                        );

                    currentPage = 1;

                    render();

                }
            );


            resetBtn.addEventListener(
                'click',
                function () {

                    searchInput.value = '';

                    categoryFilter.value = '';

                    levelFilter.value = '';

                    currentPage = 1;

                    render();

                }
            );


            prevBtn.addEventListener(
                'click',
                function () {

                    if (currentPage > 1) {

                        currentPage--;

                        render();

                    }

                }
            );


            nextBtn.addEventListener(
                'click',
                function () {

                    currentPage++;

                    render();

                }
            );


            render();

        });

    </script>

</x-mi_app>