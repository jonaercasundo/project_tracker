<x-project_app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- HEADER BLOCK --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Project Dashboard</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">
                Monitor projects, deliveries, warehouse inventory, and logistics from one centralized dashboard.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @can('billing.records.view')
                <a href="{{ route('billing.dashboard') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Billing desk</a>
            @endcan
            <div class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-bold text-slate-600 select-none">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Operational
            </div>
        </div>
    </div>

    {{-- STATS MATRIX CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Projects</p>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $totalProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-blue-50 border border-blue-100 text-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Phase</p>
                <h2 class="text-2xl font-black text-amber-600 tracking-tight">{{ $pendingProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-amber-50 border border-amber-100 text-amber-600 rounded-xl flex items-center justify-center shadow-sm shadow-amber-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Delivered Assets</p>
                <h2 class="text-2xl font-black text-green-600 tracking-tight">{{ $deliveredProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-green-50 border border-green-100 text-green-600 rounded-xl flex items-center justify-center shadow-sm shadow-green-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">System Cluster</p>
                <h2 class="text-2xl font-black text-emerald-600 tracking-tight">Operational</h2>
            </div>
            <div class="w-10 h-10 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shadow-sm shadow-emerald-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 .621-.504 1.125-1.125 1.125H4.875c-.621 0-1.125-.504-1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125h14.25c.621 0 1.125.504 1.125 1.125v1.5zM6 12h.008v.008H6V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25H6v.008h.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM11.25 12h.008v.008h-.008V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.008v.008h-.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 12h.008v.008H16.5V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.008v.008h-.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                </svg>
            </div>
        </div>

    </div>

    {{-- PERSONAL DASHBOARDS --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="personal-dashboard-title">
        <header class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="personal-dashboard-title" class="text-lg font-extrabold text-slate-900">{{ $dashboard->name }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">Choose the metrics and charts shown on each of your dashboards.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('projects.dashboard', ['dashboard_id' => $dashboard->dashboard_id]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Refresh</a>
                <details class="relative">
                    <summary class="cursor-pointer list-none rounded-lg bg-blue-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-800">＋ New dashboard</summary>
                    <form method="POST" action="{{ route('projects.dashboards.store') }}" class="absolute right-0 z-20 mt-2 flex w-72 gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                        @csrf
                        <label class="sr-only" for="new-dashboard-name">Dashboard name</label>
                        <input id="new-dashboard-name" name="name" required maxlength="80" placeholder="Dashboard name" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <button class="rounded-lg bg-blue-900 px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">Add</button>
                    </form>
                </details>
            </div>
        </header>

        @if($errors->any())
            <div role="alert" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-xs font-semibold text-rose-800">{{ $errors->first() }}</div>
        @endif

        <nav class="flex items-center gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 px-4 pt-3" aria-label="Your dashboards">
            @foreach($dashboards as $dashboardTab)
                <div class="flex shrink-0 items-center rounded-t-lg border border-b-0 {{ $dashboardTab->dashboard_id === $dashboard->dashboard_id ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}">
                    <a href="{{ route('projects.dashboard', ['dashboard_id' => $dashboardTab->dashboard_id]) }}" @if($dashboardTab->dashboard_id === $dashboard->dashboard_id) aria-current="page" @endif class="px-3 py-2 text-xs font-bold">{{ $dashboardTab->name }}</a>
                    @if(! $dashboardTab->is_default)
                        <form method="POST" action="{{ route('projects.dashboards.destroy', $dashboardTab->dashboard_id) }}" onsubmit="return confirm('Delete this dashboard?')" class="pr-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded px-1 text-sm leading-none {{ $dashboardTab->dashboard_id === $dashboard->dashboard_id ? 'text-blue-100 hover:text-white' : 'text-slate-400 hover:text-rose-600' }}" aria-label="Delete {{ $dashboardTab->name }}">×</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </nav>

        <form id="dashboard-widget-layout" method="POST" action="{{ route('projects.dashboards.update', $dashboard->dashboard_id) }}">
            @csrf
            @method('PATCH')
            <div class="grid grid-cols-1 gap-4 bg-blue-50/70 p-4 lg:grid-cols-2">
                @foreach($dashboardWidgets as $widgetKey => $widgetSettings)
                    @php
                        $widget = $widgetCatalog[$widgetKey];
                        $widgetRows = $widget['kind'] === 'chart' ? ($widgetValues[$widgetKey] ?? []) : [];
                        $chartMaximum = max([1, ...array_column($widgetRows, 'value')]);
                        $chartTotal = array_sum(array_column($widgetRows, 'value'));
                        $chartPalette = ['#2563eb', '#059669', '#7c3aed', '#d97706', '#db2777', '#0891b2', '#4f46e5'];
                        $chartStops = [];
                        $chartCursor = 0;
                        foreach ($widgetRows as $rowIndex => $row) {
                            $chartStart = $chartCursor;
                            $chartCursor += $chartTotal > 0 ? ($row['value'] / $chartTotal) * 100 : 0;
                            $chartStops[] = $chartPalette[$rowIndex % count($chartPalette)].' '.$chartStart.'% '.$chartCursor.'%';
                        }
                        $chartBackground = $chartStops === [] ? '#e2e8f0' : 'conic-gradient('.implode(', ', $chartStops).')';
                    @endphp
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <header class="flex flex-wrap items-center justify-between gap-3 bg-blue-100/70 px-4 py-3">
                            <h3 class="text-sm font-bold text-slate-800">{{ $widget['label'] }}</h3>
                            @if($widget['kind'] === 'chart')
                                <label class="sr-only" for="chart-{{ $widgetKey }}">Chart type for {{ $widget['label'] }}</label>
                                <select id="chart-{{ $widgetKey }}" name="charts[{{ $widgetKey }}]" form="dashboard-widget-layout" onchange="this.form.requestSubmit()" class="rounded-lg border-slate-300 py-1.5 pl-2 pr-7 text-xs text-slate-700 focus:border-blue-600 focus:ring-blue-600">
                                    @foreach(['doughnut' => 'Doughnut', 'bar' => 'Bar', 'column' => 'Column'] as $chartValue => $chartLabel)
                                        <option value="{{ $chartValue }}" @selected(($widgetSettings['chart'] ?? 'bar') === $chartValue)>{{ $chartLabel }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </header>
                        @if($widget['kind'] === 'metric')
                            <div class="flex min-h-40 flex-col justify-center px-5 py-6">
                                <p class="text-4xl font-black tabular-nums text-slate-900">{{ number_format($widgetValues[$widgetKey] ?? 0) }}</p>
                                <p class="mt-1 text-xs text-slate-500">Current system total</p>
                            </div>
                        @elseif(($widgetSettings['chart'] ?? 'bar') === 'doughnut')
                            <div class="flex min-h-40 items-center gap-6 px-5 py-5">
                                <div class="relative h-28 w-28 shrink-0 rounded-full" style="background: {{ $chartBackground }}">
                                    <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white text-center">
                                        <span class="text-lg font-black tabular-nums text-slate-900">{{ number_format($chartTotal) }}</span>
                                        <span class="text-[10px] text-slate-500">Total</span>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    @forelse($widgetRows as $rowIndex => $row)
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $chartPalette[$rowIndex % count($chartPalette)] }}"></span>
                                            <span class="min-w-0 flex-1 truncate text-slate-600">{{ $row['label'] }}</span>
                                            <span class="font-bold tabular-nums text-slate-800">{{ number_format($row['value']) }}</span>
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-500">No status data is available yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <div class="min-h-40 px-5 py-5 {{ ($widgetSettings['chart'] ?? 'bar') === 'column' ? 'flex items-end justify-around gap-4' : 'space-y-3' }}">
                                @forelse($widgetRows as $rowIndex => $row)
                                    @if(($widgetSettings['chart'] ?? 'bar') === 'column')
                                        <div class="flex h-28 min-w-16 flex-col items-center justify-end gap-1 border-b border-slate-200 px-1 text-center">
                                            <span class="text-[11px] font-bold tabular-nums text-slate-700">{{ number_format($row['value']) }}</span>
                                            <div class="w-8 rounded-t-md" style="height: {{ max(3, ($row['value'] / $chartMaximum) * 66) }}%; background: {{ $chartPalette[$rowIndex % count($chartPalette)] }}"></div>
                                            <span class="w-full truncate text-[10px] text-slate-500" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                        </div>
                                    @else
                                        <div>
                                            <div class="mb-1 flex justify-between gap-3 text-xs">
                                                <span class="truncate text-slate-600">{{ $row['label'] }}</span>
                                                <span class="font-bold tabular-nums text-slate-800">{{ number_format($row['value']) }}</span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full" style="width: {{ ($row['value'] / $chartMaximum) * 100 }}%; background: {{ $chartPalette[$rowIndex % count($chartPalette)] }}"></div></div>
                                        </div>
                                    @endif
                                @empty
                                    <p class="py-10 text-center text-xs text-slate-500">No status data is available yet.</p>
                                @endforelse
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <details class="border-t border-slate-200 px-4 py-3">
                <summary class="cursor-pointer text-xs font-bold text-blue-800">Choose widgets</summary>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($widgetCatalog as $widgetKey => $widget)
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs hover:bg-slate-50">
                            <input type="checkbox" name="widgets[]" value="{{ $widgetKey }}" form="dashboard-widget-layout" @checked(isset($dashboardWidgets[$widgetKey])) class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                            <span><span class="block font-bold text-slate-700">{{ $widget['label'] }}</span><span class="mt-0.5 block text-slate-500">{{ $widget['kind'] === 'metric' ? 'Current total' : 'Status breakdown chart' }}</span></span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" form="dashboard-widget-layout" class="rounded-lg bg-blue-900 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800">Save widgets</button>
                </div>
            </details>
        </form>
    </section>

    {{-- INTERACTIVE NAVIGATION ENGINE DRIVERS --}}
    <div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 px-1 select-none">Application Core Control Gateways</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <a href="{{ route('projects.index') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-blue-500 hover:shadow-md hover:shadow-blue-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-blue-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-blue-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 0a9.015 9.015 0 018.447 5.7M12 3a9.015 9.015 0 00-8.447 5.7m16.894 0A9.04 9.04 0 0112 12.75c-2.32 0-4.43-.649-6.22-1.766M21 12.25a9.04 9.04 0 01-9 6.25c-2.32 0-4.43-.649-6.22-1.766"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-blue-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-blue-600 transition-colors">Projects</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Initialize, monitor development, and review master timelines.</p>
                </div>
            </a>

            <a href="{{ route('deliveries.tracking') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-indigo-500 hover:shadow-md hover:shadow-indigo-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375M9 18h3.375m7.5-13.5v13.5c0 .621-.504 1.125-1.125 1.125H3.75A1.125 1.125 0 012.625 18V4.5c0-.621.504-1.125 1.125-1.125h5.25M16.5 5.25h.008v.008H16.5V5.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 10.5h.008v.008H16.5V10.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 15h.008v.008H16.5V15zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12 3v3.375c0 .621.504 1.125 1.125 1.125h3.375m-.151-5.114a48.412 48.412 0 012.198 2.198m-2.198-2.198A48.214 48.214 0 0116.5 3"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-indigo-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">Fulfillment Deliveries</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Track drop-off milestones and pipeline handshakes.</p>
                </div>
            </a>

            <a href="{{ route('inventory.history') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-emerald-500 hover:shadow-md hover:shadow-emerald-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-emerald-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h12M6 10h12m-12 4h12m-12 4h12M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-emerald-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-emerald-600 transition-colors">Warehouse Inventory</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Audit item stock counts, lot tracking, and material distribution.</p>
                </div>
            </a>

            <a href="{{ route('logistics-channel.index') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-cyan-500 hover:shadow-md hover:shadow-cyan-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-cyan-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-cyan-50 text-cyan-700 rounded-xl flex items-center justify-center transition-colors group-hover:bg-cyan-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h11.25v10.125H3zM14.25 10.5h3.3l3.45 3.75v3.375h-6.75zM7.125 20.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zm10.5 0a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zM14.25 14.25h6.75"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-cyan-600 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-cyan-700 transition-colors">Logistics &amp; Fleet</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Manage delivery partners, assigned regions, and shipment tracking.</p>
                </div>
            </a>

        </div>
    </div>
</div>
</x-project_app-layout>
