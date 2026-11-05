<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-100/60 sm:flex-row sm:items-center sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Project Module / Reports &amp; Analytics</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">Billing Document Status</h1>
                <p class="mt-1 text-sm font-medium text-slate-500">
                    School-based billing document processing for {{ $selectedProject?->project_name ?? 'the selected project' }}, across LOT 2, LOT 9, and LOT 11.
                </p>
            </div>
            <div class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right">
                <p class="inline-flex items-center gap-2 text-[11px] font-bold text-slate-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600" aria-hidden="true"></span>
                    <time id="billing-status-updated">Updated {{ $snapshotAt->format('M d, Y · g:i A') }}</time>
                </p>
                <p class="mt-0.5 text-[10px] font-medium text-slate-400">Updates live when billing data changes</p>
            </div>
        </header>

        <form method="GET" action="{{ route('projects.reports.analytics') }}" class="grid min-w-0 grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_220px] sm:items-end sm:p-5" aria-label="Project report filters">
            <div class="min-w-0">
                <x-searchable-project-filter
                    :projects="$projects"
                    :selected="$selectedProjectId"
                    name="project_id"
                    id="billing-project-filter"
                    :truncate-selected="false"
                    :submit-on-change="true"
                    wrapper-class="min-w-0 space-y-1 text-xs font-semibold text-slate-600"
                    label-class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500"
                />
            </div>
            <div class="min-w-0 space-y-1 text-xs font-semibold text-slate-600">
                <label for="report-cutoff-date" class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Cutoff Date</label>
                <input id="report-cutoff-date" name="cutoff_date" type="date" value="{{ $cutoffDate->toDateString() }}" onchange="this.form.requestSubmit()" class="h-[42px] w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </form>

        <div id="billing-status-refresh-error" role="alert" @if (! $summary['source_error']) hidden @endif class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-black">Billing sheet data is unavailable</p>
            <p id="billing-status-refresh-error-message" class="mt-1">{{ $summary['source_error'] }}</p>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Billing document processing metrics">
            @php
                $summaryCards = [
                    ['key' => 'total', 'label' => 'Total Schools', 'value' => $summary['total'], 'description' => 'Unique schools with applicable DRs across supported lots', 'color' => 'blue', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
                    ['key' => 'processed', 'label' => 'Processed', 'value' => $summary['processed'], 'description' => 'Schools encoded for every applicable lot', 'color' => 'cyan', 'icon' => 'M9 12l2 2 4-4m5-3v6a8 8 0 1 1-3-6'],
                    ['key' => 'remaining', 'label' => 'Remaining', 'value' => $summary['remaining'], 'description' => 'Schools missing a date for an applicable lot', 'color' => 'amber', 'icon' => 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['key' => 'processed_today', 'label' => 'Processed Today', 'value' => $summary['processed_today'], 'description' => 'Schools with an applicable SAP date today · '.config('app.timezone'), 'color' => 'blue', 'icon' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Zm3 11 2 2 4-4'],
                ];
                $toneClasses = [
                    'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                    'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                ];
            @endphp
            @foreach ($summaryCards as $card)
                <article class="flex min-h-[168px] flex-col justify-between rounded-2xl border border-slate-200/70 p-5 shadow-sm shadow-slate-100/50 {{ $card['label'] === 'Processed' ? 'border-slate-800 bg-slate-950 text-white shadow-slate-900/10' : 'bg-white' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['label'] }}</p>
                            <p class="mt-3 font-mono text-3xl font-bold leading-none tracking-tight tabular-nums sm:text-4xl {{ $card['label'] === 'Processed' ? 'text-white' : 'text-slate-950' }}"><span data-billing-metric="{{ $card['key'] }}">{{ $card['value'] === null ? '—' : number_format($card['value']) }}</span></p>
                        </div>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['label'] === 'Processed' ? 'bg-cyan-400/10 text-cyan-300 ring-cyan-300/20' : $toneClasses[$card['color']] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-xs leading-5 {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($drProgressReport)
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="dr-progress-title">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-700">Delivery Receipt Progress</p>
                        <h2 id="dr-progress-title" class="mt-1 text-lg font-extrabold tracking-tight text-slate-950">Actual progress against cutoff</h2>
                        <p class="mt-1 text-sm text-slate-500">Cutoff: <time datetime="{{ $drProgressReport['cutoff_date'] }}" class="font-semibold text-slate-700">{{ \Carbon\CarbonImmutable::parse($drProgressReport['cutoff_date'])->format('M d, Y') }}</time>
                            @if ($drProgressReport['days_remaining'] > 0)
                                <span class="mx-1 text-slate-300">·</span>{{ $drProgressReport['days_remaining'] }} days remaining
                            @elseif ($drProgressReport['pace_status'] === 'Cutoff passed')
                                <span class="mx-1 text-slate-300">·</span>Cutoff has passed
                            @endif
                        </p>
                        <p class="mt-2 max-w-3xl text-xs leading-5 text-slate-500">Scope: {{ $drProgressReport['creation_definition'] }} Actual completion: {{ $drProgressReport['completion_definition'] }}</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-bold {{ $drProgressReport['pace_status'] === 'On track' || $drProgressReport['pace_status'] === 'Target met' ? 'bg-emerald-50 text-emerald-700' : ($drProgressReport['pace_status'] === 'At this pace, cutoff will be missed' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600') }}">{{ $drProgressReport['pace_status'] }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">DRs in scope</p><p class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-950">{{ number_format($drProgressReport['total_drs']) }}</p></article>
                    <article class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4"><p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Actual completed</p><p class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-950">{{ number_format($drProgressReport['completed']) }} <span class="text-sm font-semibold text-slate-500">({{ number_format($drProgressReport['percentage'], 1) }}%)</span></p></article>
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Remaining</p><p class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-950">{{ number_format($drProgressReport['remaining']) }}</p></article>
                    <article class="rounded-xl border border-amber-100 bg-amber-50/60 p-4"><p class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Needed per day</p><p class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-950">{{ $drProgressReport['required_per_day'] === null ? '—' : number_format($drProgressReport['required_per_day'], 2) }}</p></article>
                    <article class="col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:col-span-1"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Recent pace · last 7 days</p><p class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-950">{{ number_format($drProgressReport['recent_pace'], 2) }} <span class="text-sm font-semibold text-slate-500">/ day</span></p></article>
                </div>

                @if ($drProgressReport['data_limitation'])
                    <p role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-5 text-amber-900">{{ $drProgressReport['data_limitation'] }}</p>
                @endif

                <div class="rounded-xl border border-slate-200 p-3 sm:p-4">
                    <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-semibold text-slate-600" aria-label="Chart legend">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-orange-500" aria-hidden="true"></span>DRs created</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-600" aria-hidden="true"></span>Actual progress</span>
                        @if ($drProgressReport['cutoff_date'] > $drProgressReport['today'])
                            <span class="inline-flex items-center gap-2"><span class="h-4 border-l-2 border-dashed border-rose-500" aria-hidden="true"></span>Cutoff target</span>
                        @endif
                    </div>
                    <div class="relative h-64 w-full sm:h-80" role="img" aria-label="Timeline comparing cumulative DRs created and actual DR progress">
                        <canvas id="dr-progress-chart" aria-hidden="true"></canvas>
                    </div>
                    <div class="mt-2 flex flex-wrap justify-between gap-x-3 gap-y-1 text-[11px] text-slate-500"><span>Report start: {{ \Carbon\CarbonImmutable::parse($drProgressReport['start_date'])->format('M d, Y') }}</span><span>Actual data through {{ \Carbon\CarbonImmutable::parse($drProgressReport['cutoff_date'] > $drProgressReport['today'] ? $drProgressReport['today'] : $drProgressReport['cutoff_date'])->format('M d, Y') }}</span></div>
                </div>
            </section>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
            <script>
                (() => {
                    const report = @json($drProgressReport);
                    const canvas = document.getElementById('dr-progress-chart');
                    const points = report.timeline || [];
                    if (!canvas || points.length === 0 || typeof Chart === 'undefined') return;
                    const todayIndex = points.findIndex(point => point.date >= report.today);
                    const cutoffIndex = points.findIndex(point => point.date >= report.cutoff_date);
                    const markers = [{index: todayIndex, label: 'TODAY', color: '#475569'}];
                    if (report.cutoff_date > report.today) {
                        markers.push({index: cutoffIndex, label: 'CUTOFF', color: '#e11d48'});
                    }
                    const markerPlugin = {
                        id: 'drReportMarkers',
                        afterDatasetsDraw(chart) {
                            const {ctx, chartArea, scales: {x}} = chart;
                            markers.filter(marker => marker.index >= 0).forEach(marker => {
                                const markerX = x.getPixelForValue(marker.index);
                                ctx.save();
                                ctx.strokeStyle = marker.color;
                                ctx.lineWidth = marker.label === 'CUTOFF' ? 2 : 1.5;
                                ctx.setLineDash(marker.label === 'CUTOFF' ? [5, 5] : [3, 4]);
                                ctx.beginPath();
                                ctx.moveTo(markerX, chartArea.top);
                                ctx.lineTo(markerX, chartArea.bottom);
                                ctx.stroke();
                                ctx.setLineDash([]);
                                ctx.fillStyle = marker.color;
                                ctx.font = '700 10px sans-serif';
                                ctx.textBaseline = 'top';
                                const labelX = Math.min(markerX + 5, chartArea.right - ctx.measureText(marker.label).width);
                                ctx.fillText(marker.label, Math.max(chartArea.left, labelX), chartArea.top + 4);
                                ctx.restore();
                            });
                        },
                    };
                    new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: points.map(point => point.date),
                            datasets: [
                                {label: 'DRs created', data: points.map(point => point.created), borderColor: '#f97316', backgroundColor: '#f97316', borderWidth: 3, pointRadius: 0, pointHitRadius: 8, tension: 0, stepped: 'before'},
                                {label: 'Actual progress', data: points.map(point => point.actual), borderColor: '#059669', backgroundColor: '#059669', borderWidth: 3, pointRadius: 0, pointHitRadius: 8, tension: 0, stepped: 'before'},
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {mode: 'index', intersect: false},
                            plugins: {legend: {display: false}},
                            scales: {
                                x: {grid: {display: false}, ticks: {color: '#64748b', maxTicksLimit: 8, maxRotation: 0, callback(value) {
                                    const date = new Date(`${this.getLabelForValue(value)}T00:00:00`);
                                    return date.toLocaleDateString(undefined, {month: 'short', day: 'numeric'});
                                }}},
                                y: {beginAtZero: true, ticks: {color: '#64748b', precision: 0}, grid: {color: '#e2e8f0'}},
                            },
                        },
                        plugins: [markerPlugin],
                    });
                })();
            </script>
        @endif

        @if ($selectedProjectId === null)
            <p class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-600">No projects are available for this report.</p>
        @endif
    </div>
</x-project_app-layout>
