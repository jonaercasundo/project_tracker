<x-mi_app>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>

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
            --tx-lvl-1: #2F5D50;
            --tx-lvl-1-soft: #E5EEE9;
            --tx-lvl-2: #35618C;
            --tx-lvl-2-soft: #E3EBF2;
            --tx-lvl-3: #7A4F98;
            --tx-lvl-3-soft: #ECE4F1;
            --tx-lvl-4: #C7703C;
            --tx-lvl-4-soft: #F5E7DB;
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
            --tx-lvl-1-soft: #1C2723;
            --tx-lvl-2-soft: #1A222B;
            --tx-lvl-3-soft: #221C29;
            --tx-lvl-4-soft: #2A2019;
        }

        .tx-display { font-family: var(--tx-font-display); letter-spacing: -0.01em; }
        .tx-mono { font-family: var(--tx-font-mono); letter-spacing: 0.02em; }
        .tx-shell { max-width: 82rem; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

        /* Header */
        .tx-header {
            display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: flex-end;
            justify-content: space-between; padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line); margin-bottom: 2rem;
        }
        .tx-eyebrow { display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--tx-ink-faint); margin-bottom: 0.6rem; }
        .tx-title { font-size: 1.85rem; font-weight: 700; line-height: 1.15; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.9rem; margin-top: 0.45rem; }
        .tx-btn-add {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.7rem 1.35rem; background: var(--tx-primary); color: var(--tx-primary-ink);
            font-size: 0.85rem; font-weight: 600; border-radius: 999px; text-decoration: none;
            transition: all .18s ease; flex-shrink: 0;
        }
        .tx-btn-add:hover { transform: translateY(-1px); box-shadow: 0 12px 28px -12px var(--tx-primary); }

        /* KPI cards */
        .tx-kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1.1rem; margin-bottom: 1.75rem; }
        @media (min-width: 900px) { .tx-kpi-grid { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .tx-kpi-card {
            background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 18px;
            padding: 1.35rem 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;
        }
        .tx-kpi-top { display: flex; align-items: center; justify-content: space-between; }
        .tx-kpi-icon { width: 2.1rem; height: 2.1rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .tx-kpi-icon svg { width: 1.05rem; height: 1.05rem; }
        .tx-kpi-trend { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 999px; }
        .tx-kpi-trend.up { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .tx-kpi-trend.flat { background: var(--tx-line); color: var(--tx-ink-soft); }
        .tx-kpi-value { font-family: var(--tx-font-display); font-size: 1.9rem; font-weight: 700; }
        .tx-kpi-label { font-size: 0.78rem; color: var(--tx-ink-soft); font-weight: 500; }

        .k1 .tx-kpi-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .k2 .tx-kpi-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .k3 .tx-kpi-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }
        .k4 .tx-kpi-icon { background: var(--tx-lvl-4-soft); color: var(--tx-lvl-4); }

        /* Cards */
        .tx-card { background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 20px; margin-bottom: 1.5rem; overflow: hidden; }
        .tx-card-head { display: flex; align-items: center; justify-content: space-between; gap: 0.85rem; padding: 1.35rem 1.75rem; border-bottom: 1px solid var(--tx-line); }
        .tx-card-head-left { display: flex; align-items: center; gap: 0.85rem; }
        .tx-card-icon { width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center; border-radius: 10px; flex-shrink: 0; }
        .tx-card-icon svg { width: 1.05rem; height: 1.05rem; }
        .tx-card-head h2 { font-family: var(--tx-font-display); font-size: 1.02rem; font-weight: 600; }
        .tx-card-head p { font-size: 0.78rem; color: var(--tx-ink-soft); margin-top: 0.15rem; }
        .tx-card-body { padding: 1.75rem; }
        .tx-card-link { font-size: 0.78rem; font-weight: 600; color: var(--tx-primary); text-decoration: none; white-space: nowrap; }
        .tx-card-link:hover { text-decoration: underline; }

        /* Two-column analytics row */
        .tx-analytics-row { display: grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap: 1.5rem; }
        @media (min-width: 1024px) { .tx-analytics-row { grid-template-columns: 1fr 1.3fr; } }
        .tx-chart-wrap { position: relative; height: 240px; }
        .tx-legend { display: flex; flex-wrap: wrap; gap: 0.6rem 1.1rem; margin-top: 1.25rem; }
        .tx-legend-item { display: flex; align-items: center; gap: 0.45rem; font-size: 0.78rem; color: var(--tx-ink-soft); }
        .tx-legend-dot { width: 0.55rem; height: 0.55rem; border-radius: 999px; flex-shrink: 0; }
        .tx-legend-count { font-weight: 700; color: var(--tx-ink); font-family: var(--tx-font-mono); }

        /* Taxonomy ladder (reused pattern) */
        .tx-ladder { display: flex; flex-wrap: wrap; }
        .tx-rung {
            flex: 1 1 12rem; display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem;
            text-decoration: none; color: var(--tx-ink); border-right: 1px solid var(--tx-line); border-bottom: 1px solid var(--tx-line);
            transition: background .15s ease;
        }
        .tx-rung:hover { background: var(--tx-bg); }
        .tx-rung-dot { width: 0.65rem; height: 0.65rem; border-radius: 999px; flex-shrink: 0; }
        .tx-rung-value { font-family: var(--tx-font-display); font-size: 1.15rem; font-weight: 700; }
        .tx-rung-label { font-size: 0.72rem; color: var(--tx-ink-faint); font-weight: 600; }

        /* Recent products table */
        .tx-recent-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .tx-recent-table thead th {
            text-align: left; font-family: var(--tx-font-display); font-size: 0.66rem; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-faint);
            padding: 0 0 0.75rem;
        }
        .tx-recent-table tbody td { padding: 0.75rem 0; border-top: 1px solid var(--tx-line); vertical-align: middle; }
        .tx-recent-item { font-weight: 600; color: var(--tx-ink); text-decoration: none; }
        .tx-recent-item:hover { color: var(--tx-primary); }
        .tx-recent-sku { font-family: var(--tx-font-mono); font-size: 0.75rem; color: var(--tx-primary); background: var(--tx-primary-soft); padding: 0.25rem 0.55rem; border-radius: 7px; }
        .tx-recent-taxo { display: flex; align-items: center; gap: 0.45rem; color: var(--tx-ink-soft); }
        .tx-recent-taxo .dot { width: 0.4rem; height: 0.4rem; border-radius: 999px; background: var(--tx-lvl-1); flex-shrink: 0; }
        .tx-status-badge { font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 999px; white-space: nowrap; }
        .tx-status-badge.Available { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .tx-status-badge.Assigned { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .tx-status-badge.Repair { background: var(--tx-accent-soft); color: var(--tx-accent); }
        .tx-status-badge.Disposed { background: #F5E4E0; color: var(--tx-danger); }

        .tx-empty-note { text-align: center; padding: 2.5rem 1rem; color: var(--tx-ink-faint); font-size: 0.85rem; }
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Header --}}
            <div class="tx-header">
                <div>
                    <div class="tx-eyebrow">
                        <span>Product Database</span>
                        <span>/</span>
                        <span>Dashboard</span>
                    </div>
                    <h1 class="tx-title tx-display">Dashboard</h1>
                    <p class="tx-subtitle">An overview of your catalog, taxonomy, and recent activity.</p>
                </div>
                <a href="{{ route('mi_app.create') }}" class="tx-btn-add">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Product
                </a>
            </div>

            {{-- KPI cards --}}
            <div class="tx-kpi-grid">
                <div class="tx-kpi-card k1">
                    <div class="tx-kpi-top">
                        <span class="tx-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5L3.75 7.5M20.25 7.5l-8.25-4.5L3.75 7.5m16.5 0v9l-8.25 4.5m0-13.5v13.5m0-13.5L3.75 7.5m8.25 13.5L3.75 16.5v-9" /></svg>
                        </span>
                        <span class="tx-kpi-trend up">Total</span>
                    </div>
                    <div>
                        <p class="tx-kpi-value">{{ $stats['total_products'] ?? 0 }}</p>
                        <p class="tx-kpi-label">Total Products</p>
                    </div>
                </div>

                <div class="tx-kpi-card k2">
                    <div class="tx-kpi-top">
                        <span class="tx-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="tx-kpi-trend flat">Available</span>
                    </div>
                    <div>
                        <p class="tx-kpi-value">{{ $stats['active_products'] ?? 0 }}</p>
                        <p class="tx-kpi-label">Active Products</p>
                    </div>
                </div>

                <div class="tx-kpi-card k3">
                    <div class="tx-kpi-top">
                        <span class="tx-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </span>
                        <span class="tx-kpi-trend flat">Taxonomy</span>
                    </div>
                    <div>
                        <p class="tx-kpi-value">{{ $stats['total_categories'] ?? 0 }}</p>
                        <p class="tx-kpi-label">Categories</p>
                    </div>
                </div>

                <div class="tx-kpi-card k4">
                    <div class="tx-kpi-top">
                        <span class="tx-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /></svg>
                        </span>
                        <span class="tx-kpi-trend flat">Taxonomy</span>
                    </div>
                    <div>
                        <p class="tx-kpi-value">{{ $stats['total_collections'] ?? 0 }}</p>
                        <p class="tx-kpi-label">Collections</p>
                    </div>
                </div>
            </div>

            {{-- Analytics row --}}
            <div class="tx-analytics-row">

                {{-- Classification breakdown --}}
                <div class="tx-card">
                    <div class="tx-card-head">
                        <div class="tx-card-head-left">
                            <span class="tx-card-icon" style="background: var(--tx-primary-soft); color: var(--tx-primary);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                            </span>
                            <div>
                                <h2>Classification Breakdown</h2>
                                <p>Products grouped by current status</p>
                            </div>
                        </div>
                    </div>
                    <div class="tx-card-body">
                        <div class="tx-chart-wrap">
                            <canvas id="classificationChart"></canvas>
                        </div>
                        <div class="tx-legend" id="classificationLegend"></div>
                    </div>
                </div>

                {{-- Category distribution --}}
                <div class="tx-card">
                    <div class="tx-card-head">
                        <div class="tx-card-head-left">
                            <span class="tx-card-icon" style="background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </span>
                            <div>
                                <h2>Products by Category</h2>
                                <p>Where inventory is concentrated</p>
                            </div>
                        </div>
                        <a href="{{ route('mi_app.settings') }}" class="tx-card-link">Manage taxonomy →</a>
                    </div>
                    <div class="tx-card-body">
                        <div class="tx-chart-wrap">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Taxonomy ladder --}}
            <div class="tx-card">
                <div class="tx-card-head">
                    <div class="tx-card-head-left">
                        <span class="tx-card-icon" style="background: var(--tx-line); color: var(--tx-ink-soft);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h12M3 17h6" /></svg>
                        </span>
                        <div>
                            <h2>Taxonomy Overview</h2>
                            <p>Counts across each level of the hierarchy</p>
                        </div>
                    </div>
                    <a href="{{ route('mi_app.settings') }}" class="tx-card-link">Open Settings →</a>
                </div>
                <div class="tx-ladder">
                    <a href="{{ route('mi_app.settings') }}#level-category" class="tx-rung">
                        <span class="tx-rung-dot" style="background: var(--tx-lvl-1);"></span>
                        <div>
                            <p class="tx-rung-value">{{ $taxonomyCounts['categories'] ?? 0 }}</p>
                            <p class="tx-rung-label">Categories</p>
                        </div>
                    </a>
                    <a href="{{ route('mi_app.settings') }}#level-subcategory" class="tx-rung">
                        <span class="tx-rung-dot" style="background: var(--tx-lvl-2);"></span>
                        <div>
                            <p class="tx-rung-value">{{ $taxonomyCounts['sub_categories'] ?? 0 }}</p>
                            <p class="tx-rung-label">Sub Categories</p>
                        </div>
                    </a>
                    <a href="{{ route('mi_app.settings') }}#level-subsubcategory" class="tx-rung">
                        <span class="tx-rung-dot" style="background: var(--tx-lvl-3);"></span>
                        <div>
                            <p class="tx-rung-value">{{ $taxonomyCounts['product_types'] ?? 0 }}</p>
                            <p class="tx-rung-label">Sub Sub Categories</p>
                        </div>
                    </a>
                    <a href="{{ route('mi_app.settings') }}#level-collection" class="tx-rung" style="border-right: none;">
                        <span class="tx-rung-dot" style="background: var(--tx-lvl-4);"></span>
                        <div>
                            <p class="tx-rung-value">{{ $taxonomyCounts['collections'] ?? 0 }}</p>
                            <p class="tx-rung-label">Collections</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Recent products --}}
            <div class="tx-card">
                <div class="tx-card-head">
                    <div class="tx-card-head-left">
                        <span class="tx-card-icon" style="background: var(--tx-accent-soft); color: var(--tx-accent);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div>
                            <h2>Recently Added</h2>
                            <p>Latest products entered into the database</p>
                        </div>
                    </div>
                    <a href="{{ route('mi_app.index') }}" class="tx-card-link">View all products →</a>
                </div>
                <div class="tx-card-body" style="padding-top: 0.5rem;">
                    @if(isset($recentProducts) && count($recentProducts))
                        <table class="tx-recent-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentProducts as $product)
                                    <tr>
                                        <td>
                                            <a href="{{ route('mi_app.show', $product->product_id) }}" class="tx-recent-item">
                                                {{ $product->item_name }}
                                            </a>
                                        </td>
                                        <td><span class="tx-recent-sku">{{ $product->sku ?? '—' }}</span></td>
                                        <td>
                                            <span class="tx-recent-taxo">
                                                <span class="dot"></span>
                                                {{ $product->category->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tx-status-badge {{ $product->classification }}">{{ $product->classification }}</span>
                                        </td>
                                        <td style="color: var(--tx-ink-faint); font-size: 0.78rem;">
                                            {{ optional($product->created_at)->diffForHumans() ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="tx-empty-note">No products yet — once you add products, the most recent ones will show up here.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        (function () {
            var palette = ['#2F5D50', '#35618C', '#7A4F98', '#C7703C', '#9AA39C', '#B3432E'];

            // Classification breakdown (doughnut)
            var classificationData = @json($classificationBreakdown ?? []);
            var classLabels = Object.keys(classificationData);
            var classValues = Object.values(classificationData);
            var classLegend = document.getElementById('classificationLegend');

            if (classLabels.length) {
                classLabels.forEach(function (label, i) {
                    var item = document.createElement('span');
                    item.className = 'tx-legend-item';
                    item.innerHTML = '<span class="tx-legend-dot" style="background:' + palette[i % palette.length] + '"></span>' +
                        label + ' <span class="tx-legend-count">' + classValues[i] + '</span>';
                    classLegend.appendChild(item);
                });

                new Chart(document.getElementById('classificationChart'), {
                    type: 'doughnut',
                    data: {
                        labels: classLabels,
                        datasets: [{
                            data: classValues,
                            backgroundColor: palette,
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: { legend: { display: false } }
                    }
                });
            } else {
                classLegend.innerHTML = '<span class="tx-empty-note">No classification data yet.</span>';
            }

            // Category distribution (horizontal bar)
            var categoryData = @json($categoryBreakdown ?? []);
            var catLabels = categoryData.map(function (c) { return c.name; });
            var catValues = categoryData.map(function (c) { return c.count; });

            if (catLabels.length) {
                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: catLabels,
                        datasets: [{
                            data: catValues,
                            backgroundColor: '#35618C',
                            borderRadius: 6,
                            maxBarThickness: 22,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: '#E2E5DF' }, ticks: { color: '#9AA39C', font: { family: 'Inter' } } },
                            y: { grid: { display: false }, ticks: { color: '#171B1A', font: { family: 'Inter', weight: 600 } } }
                        }
                    }
                });
            }
        })();
    </script>
</x-mi_app>