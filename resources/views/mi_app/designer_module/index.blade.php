<x-mi_app>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <style>
        .tx-console {
            --tx-bg: #F5F6F3;
            --tx-surface: #FFFFFF;
            --tx-ink: #171B1A;
            --tx-ink-soft: #616B66;
            --tx-ink-faint: #9AA39C;
            --tx-line: #E2E5DF;
            --tx-primary: #2F5D50;
            --tx-primary-ink: #FFFFFF;
            --tx-primary-soft: #E5EEE9;
            --tx-accent: #C7703C;
            --tx-accent-soft: #F5E7DB;
            --tx-danger: #B3432E;
            --tx-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --tx-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --tx-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
        }
        .tx-console.dark {
            --tx-bg: #12151A;
            --tx-surface: #191D22;
            --tx-ink: #EDEFEA;
            --tx-ink-soft: #9AA39C;
            --tx-ink-faint: #6B746E;
            --tx-line: #262B31;
            --tx-primary-soft: #1C2723;
        }

        .tx-display { font-family: var(--tx-font-display); letter-spacing: -0.01em; }
        .tx-mono { font-family: var(--tx-font-mono); letter-spacing: 0.02em; }

        .tx-shell { max-width: 78rem; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

        /* Header */
        .tx-header {
            display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: flex-end;
            justify-content: space-between; padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line); margin-bottom: 2rem;
        }
        .tx-title { font-size: 1.85rem; font-weight: 700; line-height: 1.15; }

        .tx-status-row { display: flex; align-items: center; gap: 0.65rem; margin-top: 0.65rem; flex-wrap: wrap; }
        .tx-status-pill {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.7rem; border-radius: 999px;
            font-size: 0.72rem; font-weight: 600; background: var(--tx-primary-soft); color: var(--tx-primary);
            border: 1px solid var(--tx-primary-soft);
        }
        .tx-status-dot { position: relative; display: flex; height: 0.5rem; width: 0.5rem; }
        .tx-status-dot-ping {
            position: absolute; inline-size: 100%; block-size: 100%; border-radius: 999px;
            background: var(--tx-primary); opacity: 0.6; animation: tx-ping 1.6s cubic-bezier(0,0,0.2,1) infinite;
        }
        .tx-status-dot-core { position: relative; display: inline-flex; border-radius: 999px; height: 0.5rem; width: 0.5rem; background: var(--tx-primary); }
        @keyframes tx-ping { 75%, 100% { transform: scale(2.2); opacity: 0; } }
        .tx-status-sep { color: var(--tx-ink-faint); font-size: 0.72rem; }
        .tx-status-synced { font-size: 0.72rem; color: var(--tx-ink-faint); }
        .tx-status-synced time { font-family: var(--tx-font-mono); color: var(--tx-ink-soft); }

        .tx-btn-add {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.7rem 1.35rem; background: var(--tx-primary); color: var(--tx-primary-ink);
            font-size: 0.85rem; font-weight: 600; border-radius: 999px; text-decoration: none;
            transition: all .18s ease; flex-shrink: 0;
        }
        .tx-btn-add:hover { transform: translateY(-1px); box-shadow: 0 12px 28px -12px var(--tx-primary); }

        /* Main content card */
        .tx-main-card { background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 22px; overflow: hidden; }
        .tx-toolbar-panel { padding: 1.35rem 1.5rem; border-bottom: 1px solid var(--tx-line); background: var(--tx-bg); }
        .tx-table-panel { position: relative; overflow-x: auto; }

        /* Loosely restyle common form controls that might come from the included partials,
           so search/filter inputs feel consistent even without editing those files directly. */
        .tx-main-card input[type="text"],
        .tx-main-card input[type="search"],
        .tx-main-card select {
            font-family: var(--tx-font-body);
            border-radius: 12px;
            border-color: var(--tx-line);
        }
        .tx-main-card input[type="text"]:focus,
        .tx-main-card input[type="search"]:focus,
        .tx-main-card select:focus {
            border-color: var(--tx-primary);
            box-shadow: 0 0 0 4px var(--tx-primary-soft);
            outline: none;
        }
        .tx-main-card table { font-family: var(--tx-font-body); }
        .tx-main-card thead th { font-family: var(--tx-font-display); font-size: 0.68rem; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-faint); }
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Header Section --}}
            <div class="tx-header">
                <div>
                    <h1 class="tx-title tx-display">Metroinc Centralized Database</h1>

                    {{-- Live Status Pill --}}
                    <div class="tx-status-row">
                        <span class="tx-status-pill">
                            <span class="tx-status-dot">
                                <span class="tx-status-dot-ping"></span>
                                <span class="tx-status-dot-core"></span>
                            </span>
                            Connected
                        </span>
                        <span class="tx-status-sep">•</span>
                        <span class="tx-status-synced">Synced : <time class="tx-mono">Just now</time></span>
                    </div>
                </div>

                {{-- Action Button --}}
                <a href="{{ route('mi_app.create') }}" class="tx-btn-add">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Add Product</span>
                </a>
            </div>

            {{-- Main Content Section --}}
            <div class="tx-main-card">

                {{-- Search & Filters Wrapper --}}
                <div class="tx-toolbar-panel">
                    @include('mi_app.designer_module.partials._search')
                </div>

                {{-- Data Table Wrapper --}}
                <div class="tx-table-panel">
                    @include('mi_app.designer_module.partials._table')
                </div>

            </div>

        </div>
    </div>
</x-mi_app>