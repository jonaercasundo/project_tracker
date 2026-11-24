<x-project_app-layout>
<div id="project-dashboard" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

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

    @if($dashboardFiltersApplied)
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
    @endif

    {{-- DASHBOARD DATA FILTERS --}}
    <form method="GET" action="{{ route('projects.dashboard') }}" data-dashboard-async class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <input type="hidden" name="dashboard_id" value="{{ $dashboard->dashboard_id }}">
        <input type="hidden" name="filters_applied" value="1">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div class="lg:col-span-3">
                <label for="dashboard-search" class="mb-1 block text-xs font-semibold text-slate-600">Search</label>
                <input id="dashboard-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search DR No., School, Project..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <x-searchable-project-filter
                :projects="$projects"
                :selected="$filters['project'] ?? ''"
                name="project"
                id="dashboard-project"
                input-id="dashboard-project-input"
                all-label="All Projects"
                rounded="rounded-xl"
                wrapper-class="min-w-0 space-y-1 text-xs font-semibold text-slate-600 lg:col-span-3"
                label-class="mb-1 block text-xs font-semibold text-slate-600"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-200 pt-4 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="dashboard-lot" class="mb-1 block text-xs font-semibold text-slate-600">Lot</label>
                <select id="dashboard-lot" name="lot" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Lots</option>
                    @foreach($lots as $lotOption)
                        <option value="{{ $lotOption->lot_id }}" @selected(($filters['lot'] ?? '') == $lotOption->lot_id)>{{ $lotOption->lot_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-region" class="mb-1 block text-xs font-semibold text-slate-600">Region</label>
                <select id="dashboard-region" name="region" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Regions</option>
                    @foreach($regions as $regionOption)
                        <option value="{{ $regionOption }}" @selected(($filters['region'] ?? '') === $regionOption)>{{ $regionOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-division" class="mb-1 block text-xs font-semibold text-slate-600">Division</label>
                <select id="dashboard-division" name="division" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Divisions</option>
                    @foreach($divisions as $divisionOption)
                        <option value="{{ $divisionOption }}" @selected(($filters['division'] ?? '') === $divisionOption)>{{ $divisionOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-municipality" class="mb-1 block text-xs font-semibold text-slate-600">Municipality</label>
                <select id="dashboard-municipality" name="municipality" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Municipalities</option>
                    @foreach($municipalities as $municipalityOption)
                        <option value="{{ $municipalityOption }}" @selected(($filters['municipality'] ?? '') === $municipalityOption)>{{ $municipalityOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-provider" class="mb-1 block text-xs font-semibold text-slate-600">Assigned Logistics</label>
                <select id="dashboard-provider" name="assigned_logistics" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Assigned Logistics</option>
                    <option value="unassigned" @selected(($filters['assigned_logistics'] ?? '') === 'unassigned')>Unassigned</option>
                    @foreach($assignedProviders as $providerOption)
                        <option value="{{ $providerOption->logistic_id }}" @selected(($filters['assigned_logistics'] ?? '') == $providerOption->logistic_id)>{{ $providerOption->logistic_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-logistics-status" class="mb-1 block text-xs font-semibold text-slate-600">Logistics Status</label>
                <select id="dashboard-logistics-status" name="logistics_status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Logistics Statuses</option>
                    <option value="pending" @selected(($filters['logistics_status'] ?? '') === 'pending')>Pending Logistics</option>
                    <option value="logistics_received" @selected(($filters['logistics_status'] ?? '') === 'logistics_received')>Logistics Received</option>
                    <option value="delivered" @selected(($filters['logistics_status'] ?? '') === 'delivered')>Logistics Delivered</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
            @if($dashboardFiltersApplied)
                <p class="text-xs text-slate-500">{{ number_format($filteredDeliveryCount) }} delivery records match these filters.</p>
            @else
                <p class="text-xs text-slate-500">Choose at least one filter, then apply to display dashboard data.</p>
            @endif
            <div class="flex items-center gap-2">
                <a data-dashboard-nav="context" href="{{ route('projects.dashboard', ['dashboard_id' => $dashboard->dashboard_id]) }}" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800">Apply Filters</button>
            </div>
        </div>
    </form>

    {{-- PERSONAL DASHBOARDS --}}
    <section x-data="{
        creating: false,
        activeIndex: 0,
        selected: @js(array_keys($dashboardWidgets)),
        selectedCharts: @js(collect($dashboardWidgets)->map(fn ($settings) => $settings['chart'] ?? 'bar')->all()),
        widgetDefinitions: @js($widgetCatalog),
        createAction: @js(route('projects.dashboards.store', request()->query())),
        updateAction: @js(route('projects.dashboards.update', array_merge([$dashboard->dashboard_id], request()->query()))),
        deleteAction: @js(route('projects.dashboards.destroy', $dashboard->dashboard_id)),
        canDelete: @js(! $dashboard->is_default),
        tabName: @js($dashboard->name),
        openEditor(create) {
            this.creating = create;
            this.tabName = create ? '' : @js($dashboard->name);
            this.selected = create ? ['operational_workflow'] : @js(array_keys($dashboardWidgets));
            this.activeIndex = 0;
            this.selectedCharts = create ? {} : @js(collect($dashboardWidgets)->map(fn ($settings) => $settings['chart'] ?? 'bar')->all());
            this.$refs.form.action = create ? this.createAction : this.updateAction;
            this.$refs.method.disabled = create;
            this.$refs.dialog.showModal();
        },
        toggleWidget(key, checked) {
            this.selected = checked ? [...this.selected, key] : this.selected.filter((selectedKey) => selectedKey !== key);
            this.activeIndex = Math.max(0, Math.min(this.activeIndex, this.selected.length - 1));
            if (checked && this.widgetDefinitions[key].kind === 'chart' && ! this.selectedCharts[key]) {
                this.selectedCharts[key] = 'bar';
            }
        },
        moveWidget(index, direction) {
            const target = index + direction;
            if (target < 0 || target >= this.selected.length) return;
            const [widget] = this.selected.splice(index, 1);
            this.selected.splice(target, 0, widget);
            this.activeIndex = target;
        }
    }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="personal-dashboard-title">
        <header class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="personal-dashboard-title" class="text-lg font-extrabold text-slate-900">{{ $dashboard->name }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">Choose the metrics and charts shown on each of your dashboards.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a data-dashboard-nav="refresh" href="{{ route('projects.dashboard', array_merge(request()->query(), ['dashboard_id' => $dashboard->dashboard_id])) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Refresh</a>
                <button type="button" @click="openEditor(false)" class="rounded-lg bg-blue-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-800">Choose widgets</button>
            </div>
        </header>

        @if($errors->any())
            <div role="alert" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-xs font-semibold text-rose-800">{{ $errors->first() }}</div>
        @endif

        <nav class="flex items-center gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 px-4 pt-3" aria-label="Your dashboards">
            @foreach($dashboards as $dashboardTab)
                <div class="flex shrink-0 items-center rounded-t-lg border border-b-0 {{ $dashboardTab->dashboard_id === $dashboard->dashboard_id ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}">
                    <a data-dashboard-nav="tab" href="{{ route('projects.dashboard', array_merge(request()->query(), ['dashboard_id' => $dashboardTab->dashboard_id])) }}" @if($dashboardTab->dashboard_id === $dashboard->dashboard_id) aria-current="page" @endif class="px-3 py-2 text-xs font-bold">{{ $dashboardTab->name }}</a>
                </div>
            @endforeach
            <button type="button" @click="openEditor(true)" aria-label="Add dashboard" class="flex h-8 w-9 shrink-0 items-center justify-center rounded-t-lg border border-b-0 border-slate-200 bg-white text-lg font-semibold text-blue-800 transition hover:bg-blue-50">+</button>
        </nav>

        @if($dashboardFiltersApplied)
        <div>
            <div class="grid grid-cols-1 gap-4 bg-blue-50/70 p-4">
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
                        </header>
                        @if($widget['kind'] === 'metric')
                            <div class="flex min-h-40 flex-col justify-center px-5 py-6">
                                <p class="text-4xl font-black tabular-nums text-slate-900">{{ number_format($widgetValues[$widgetKey] ?? 0) }}</p>
                                <p class="mt-1 text-xs text-slate-500">Current system total</p>
                            </div>
                        @elseif($widget['kind'] === 'pipeline')
                            <div class="px-4 py-5">
                                @include('operation.delivery.partials.monitoring-pipeline', ['project' => $widgetValues[$widgetKey]])
                            </div>
                        @elseif($widget['kind'] === 'billing_status')
                            @php
                                $billingWidget = $widgetValues[$widgetKey];
                                $billingMetrics = [
                                    ['key' => 'total', 'label' => 'Total schools', 'value' => $billingWidget['summary']['total'], 'description' => 'Unique schools with applicable DRs'],
                                    ['key' => 'processed', 'label' => 'Processed', 'value' => $billingWidget['summary']['processed'], 'description' => 'Encoded for every applicable lot'],
                                    ['key' => 'remaining', 'label' => 'Remaining', 'value' => $billingWidget['summary']['remaining'], 'description' => 'Missing a date for an applicable lot'],
                                    ['key' => 'processed_today', 'label' => 'Processed today', 'value' => $billingWidget['summary']['processed_today'], 'description' => 'Schools with an SAP date today'],
                                ];
                                $billingProgress = $billingWidget['progress'];
                            @endphp
                            <div class="space-y-3 p-3 sm:p-4">
                                @if(! empty($widgetValues['billing_document_status_notice']))
                                    <p role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">Billing sheet data is unavailable: {{ $widgetValues['billing_document_status_notice'] }}</p>
                                @endif
                                <div class="grid grid-cols-2 gap-2 xl:grid-cols-4">
                                    @foreach($billingMetrics as $metric)
                                        <section class="flex min-h-28 flex-col justify-between rounded-xl border border-slate-200 bg-white p-3 {{ $metric['key'] === 'processed' ? '!border-slate-800 !bg-slate-950' : '' }}">
                                            <div>
                                                <p class="text-[9px] font-bold uppercase tracking-wide {{ $metric['key'] === 'processed' ? '!text-slate-300' : 'text-slate-500' }}">{{ $metric['label'] }}</p>
                                                <p class="mt-2 font-mono text-2xl font-black tabular-nums {{ $metric['key'] === 'processed' ? '!text-white' : 'text-slate-950' }}">{{ $metric['value'] === null ? '—' : number_format($metric['value']) }}</p>
                                            </div>
                                            <p class="mt-2 text-[9px] leading-3 {{ $metric['key'] === 'processed' ? '!text-slate-300' : 'text-slate-500' }}">{{ $metric['description'] }}</p>
                                        </section>
                                    @endforeach
                                </div>

                                @if($billingProgress)
                                    @php
                                        $todayPosition = min(100, max(0, $billingProgress['elapsed_percentage']));
                                        $todayLabelTransform = $todayPosition <= 10 ? 'translateX(0)' : ($todayPosition >= 90 ? 'translateX(-100%)' : 'translateX(-50%)');
                                        $cutoffDate = \Carbon\CarbonImmutable::parse($billingProgress['cutoff_date']);
                                        $startDate = \Carbon\CarbonImmutable::parse($billingProgress['start_date']);
                                        $timelineSpanDays = max(0, (int) $startDate->diffInDays($cutoffDate));
                                        $timelineFirstThird = $startDate->addDays(intdiv($timelineSpanDays, 3));
                                        $timelineSecondThird = $startDate->addDays(intdiv($timelineSpanDays * 2, 3));
                                        $forecastTone = in_array($billingProgress['pace_status'], ['On track', 'Target met'], true) ? 'text-emerald-700' : 'text-rose-600';
                                        $progressStatus = match ($billingProgress['pace_status']) {
                                            'On track' => 'ON TRACK',
                                            'Target met' => 'TARGET MET',
                                            'Cutoff passed' => 'CUTOFF PASSED',
                                            'Cutoff is today' => 'CUTOFF TODAY',
                                            default => 'DELAYED',
                                        };
                                        $historicalForecastLabel = $billingProgress['remaining'] === 0
                                            ? 'COMPLETED'
                                            : ($billingProgress['historical_forecast_finish_date']
                                                ? strtoupper(\Carbon\CarbonImmutable::parse($billingProgress['historical_forecast_finish_date'])->format('M d, Y'))
                                                : 'NOT ENOUGH HISTORY');
                                    @endphp
                                    <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" aria-label="Billing document cutoff progress">
                                        <header class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <h4 class="text-xs font-black uppercase tracking-wide text-slate-700">
                                                {{ strtoupper($cutoffDate->format('M d')) }} cutoff
                                                @if($billingProgress['days_remaining'] > 0)<span class="px-1 text-slate-400">·</span>{{ $billingProgress['days_remaining'] }} days left
                                                @elseif($billingProgress['pace_status'] === 'Cutoff is today')<span class="px-1 text-slate-400">·</span>Cutoff today
                                                @else<span class="px-1 text-slate-400">·</span>Cutoff passed @endif
                                            </h4>
                                            <p class="text-[10px] font-black uppercase tracking-wide {{ $forecastTone }} sm:text-right">{{ $billingProgress['pace_assessment'] }}</p>
                                        </header>
                                        <div aria-label="Cutoff progress timeline">
                                            <div class="relative mb-1 h-4">
                                                <span class="absolute top-0 whitespace-nowrap text-[8px] font-extrabold tracking-wide text-slate-700" style="left: {{ $todayPosition }}%; transform: {{ $todayLabelTransform }}">TODAY · {{ strtoupper(\Carbon\CarbonImmutable::parse($billingProgress['today'])->format('M d')) }}</span>
                                            </div>
                                            <div class="relative h-3 overflow-visible border border-slate-400 bg-slate-200" role="img" aria-label="{{ number_format($billingProgress['percentage'], 1) }} percent completed by cutoff">
                                                <div class="absolute inset-y-0 left-0 bg-emerald-500" style="width: {{ min(100, max(0, $billingProgress['percentage'])) }}%"></div>
                                                @if($todayPosition > $billingProgress['percentage'])<div class="absolute inset-y-0 bg-rose-600" style="left: {{ $billingProgress['percentage'] }}%; width: {{ $todayPosition - $billingProgress['percentage'] }}%"></div>@endif
                                                <span class="absolute inset-y-[-3px] z-10 border-l-[3px] border-slate-800" style="left: {{ $todayPosition }}%" aria-hidden="true"></span>
                                                <span class="absolute inset-y-[-2px] right-0 border-l-2 border-rose-500" aria-hidden="true"></span>
                                            </div>
                                            <div class="flex justify-between gap-2 text-[8px] font-semibold text-slate-500"><span>{{ $startDate->format('M d') }}</span><span>{{ $timelineFirstThird->format('M d') }}</span><span>{{ $timelineSecondThird->format('M d') }}</span><span>{{ $cutoffDate->format('M d') }}</span></div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 border-t border-slate-200 pt-3 sm:grid-cols-4">
                                            <div><p class="text-[8px] font-bold uppercase text-slate-500">Progress</p><p class="font-mono text-sm font-black tabular-nums">{{ number_format($billingProgress['percentage'], 1) }}%</p></div>
                                            <div><p class="text-[8px] font-bold uppercase text-slate-500">Needed per day</p><p class="font-mono text-sm font-black tabular-nums">{{ $billingProgress['required_per_day'] === null ? 'N/A' : number_format($billingProgress['required_per_day'], 0) }}</p></div>
                                            <div><p class="text-[8px] font-bold uppercase text-slate-500">Progress status</p><p class="text-xs font-black {{ $forecastTone }}">{{ $progressStatus }}</p></div>
                                            <div><p class="text-[8px] font-bold uppercase text-slate-500">Estimated completion</p><p class="text-xs font-black">{{ $historicalForecastLabel }}</p><p class="text-[8px] text-slate-500">At historical pace</p></div>
                                        </div>
                                        @if($billingProgress['data_limitation'])<p class="text-[9px] leading-4 text-amber-800">{{ $billingProgress['data_limitation'] }}</p>@endif
                                    </section>
                                @endif
                            </div>
                        @elseif($widget['kind'] === 'summary')
                            <div class="grid min-h-40 grid-cols-2 gap-3 px-4 py-5">
                                @foreach($widgetValues[$widgetKey] ?? [] as $summaryRow)
                                    <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                        <p class="text-[11px] font-medium text-slate-500">{{ $summaryRow['label'] }}</p>
                                        <p class="mt-1 text-xl font-black tabular-nums text-slate-900">{{ $summaryRow['value'] === null ? '—' : number_format((int) $summaryRow['value']) }}</p>
                                    </div>
                                @endforeach
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
                        <footer class="border-t border-slate-100 px-4 py-2">
                            <a href="{{ route($widget['route']) }}" class="text-xs font-semibold text-blue-700 underline decoration-blue-300 underline-offset-2 hover:text-blue-900">Open module</a>
                        </footer>
                    </article>
                @endforeach
            </div>

        </div>
        @else
            <div class="border-b border-slate-200 bg-blue-50/70 px-5 py-12 text-center">
                <p class="text-sm font-bold text-slate-700">Your dashboard is ready</p>
                <p class="mt-1 text-xs text-slate-500">Choose one or more filters above and select Apply Filters to load dashboard totals and widgets.</p>
            </div>
        @endif

        <dialog x-ref="dialog" @click.self="$refs.dialog.close()" aria-labelledby="dashboard-editor-title" class="w-[min(920px,calc(100%-2rem))] max-w-none overflow-hidden rounded-xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/50">
            <header class="bg-blue-800 px-5 py-3 text-white">
                <h2 id="dashboard-editor-title" class="text-base font-extrabold">Choose dashboard widgets</h2>
            </header>
            <form x-ref="form" method="POST" action="{{ route('projects.dashboards.update', array_merge([$dashboard->dashboard_id], request()->query())) }}" data-dashboard-async class="space-y-4 p-5">
                @csrf
                <input x-ref="method" type="hidden" name="_method" value="PATCH">
                <div>
                    <label for="dashboard-tab-name" class="mb-1 block text-sm font-bold text-slate-700">Tab name</label>
                    <input id="dashboard-tab-name" x-model="tabName" name="name" required maxlength="80" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                </div>

                <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <h3 class="mb-1 text-sm font-bold text-slate-700">Available widgets</h3>
                        <div class="h-64 space-y-2 overflow-y-auto rounded-lg border border-slate-300 p-2">
                            @foreach([
                                'Deliveries' => ['operational_workflow'],
                                'Billing' => ['billing_document_status'],
                                'Inventory' => ['inventory_monitoring'],
                            ] as $category => $widgetKeys)
                                <details open class="rounded-md">
                                    <summary class="cursor-pointer px-1 py-1 text-sm font-bold text-slate-800">{{ $category }}</summary>
                                    <div class="space-y-1 border-l border-blue-200 pl-3">
                                        @foreach($widgetKeys as $widgetKey)
                                            <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm text-slate-700 hover:bg-blue-50">
                                                <input type="checkbox" :checked="selected.includes('{{ $widgetKey }}')" @change="toggleWidget('{{ $widgetKey }}', $event.target.checked)" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                                <span>{{ $widgetCatalog[$widgetKey]['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-1 text-sm font-bold text-slate-700">Selected widgets</h3>
                        <div class="h-64 overflow-y-auto rounded-lg border border-slate-300 p-2">
                            <template x-for="(widgetKey, index) in selected" :key="widgetKey">
                                <div @click="activeIndex = index" role="option" :aria-selected="activeIndex === index" :class="activeIndex === index ? 'bg-slate-300' : 'bg-slate-100'" class="mb-1 flex cursor-pointer items-center gap-2 rounded px-2 py-2">
                                    <input type="hidden" name="widgets[]" :value="widgetKey">
                                    <span class="min-w-0 flex-1 truncate text-sm text-slate-800" x-text="widgetDefinitions[widgetKey].label"></span>
                                    <select x-show="widgetDefinitions[widgetKey].kind === 'chart'" :name="'charts[' + widgetKey + ']'" x-model="selectedCharts[widgetKey]" class="rounded border-slate-300 py-1 pl-2 pr-6 text-xs focus:border-blue-600 focus:ring-blue-600" :aria-label="'Chart type for ' + widgetDefinitions[widgetKey].label">
                                        <option value="doughnut">Doughnut</option>
                                        <option value="bar">Bar</option>
                                        <option value="column">Column</option>
                                    </select>
                                </div>
                            </template>
                            <p x-show="selected.length === 0" class="p-3 text-sm text-slate-500">Choose at least one widget.</p>
                        </div>
                    </div>

                    <div class="flex flex-row justify-center gap-2 md:flex-col md:pt-7">
                        <button type="button" @click="moveWidget(activeIndex, -1)" :disabled="activeIndex === 0 || selected.length === 0" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-blue-800 hover:bg-blue-50 disabled:opacity-40" aria-label="Move selected widget up">&uarr;</button>
                        <button type="button" @click="moveWidget(activeIndex, 1)" :disabled="activeIndex >= selected.length - 1" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-blue-800 hover:bg-blue-50 disabled:opacity-40" aria-label="Move selected widget down">&darr;</button>
                    </div>
                </div>
                @if($errors->any())
                    <p role="alert" class="text-xs font-semibold text-rose-700">{{ $errors->first() }}</p>
                @endif
            </form>

            @if(! $dashboard->is_default)
                <form id="delete-dashboard-tab" method="POST" action="{{ route('projects.dashboards.destroy', array_merge([$dashboard->dashboard_id], request()->query())) }}" data-dashboard-async data-confirm="Delete this dashboard tab?">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-blue-50 px-5 py-3">
                <div>
                    @if(! $dashboard->is_default)
                        <button type="submit" form="delete-dashboard-tab" x-show="!creating && canDelete" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Delete tab</button>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$refs.dialog.close()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="button" @click="$refs.form.requestSubmit()" :disabled="selected.length === 0" class="rounded-lg bg-blue-800 px-4 py-2 text-xs font-bold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-50">Apply</button>
                </div>
            </footer>
        </dialog>
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
<script>
    (() => {
        const rootSelector = '#project-dashboard';

        const tabs = new Map();
        const requests = new Map();
        let navigation;
        let mutation;
        let activeTab = createTab(document.querySelector(rootSelector), window.location.href);
        tabs.set(cacheKey(activeTab.url), activeTab);
        window.history.replaceState({ ...window.history.state, projectDashboardUrl: activeTab.url }, '');

        window.history.scrollRestoration = 'manual';
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });

        function cacheKey(url) {
            const target = new URL(url);
            for (const [name, value] of [...target.searchParams]) {
                if (value === '') {
                    target.searchParams.delete(name, value);
                }
            }
            target.searchParams.sort();
            return target.pathname + target.search;
        }

        function filterKey(url) {
            const target = new URL(url);
            target.searchParams.delete('dashboard_id');
            return cacheKey(target);
        }

        function createTab(root, url) {
            const activeLink = root.querySelector('[data-dashboard-nav="tab"][aria-current="page"]');
            return { root, url: activeLink?.href || url, initialized: root.isConnected, scroll: null, scrolledElements: [] };
        }

        function clearTabs() {
            for (const request of requests.values()) {
                request.controller.abort();
            }
            requests.clear();
            for (const tab of tabs.values()) {
                if (tab !== activeTab && tab.initialized) {
                    window.Alpine?.destroyTree(tab.root);
                }
            }
            tabs.clear();
        }

        function displayTab(tab, url, pushHistory, replacing = false) {
            if (tab !== activeTab) {
                const previous = activeTab;
                previous.scroll = [window.scrollX, window.scrollY];
                previous.scrolledElements = [...previous.root.querySelectorAll('*')]
                    .filter(element => element.scrollLeft || element.scrollTop)
                    .map(element => [element, element.scrollLeft, element.scrollTop]);
                const scroll = cacheKey(tab.url) === cacheKey(previous.url) ? previous.scroll : (tab.scroll ?? previous.scroll);
                const swap = () => {
                    if (tabs.get(cacheKey(previous.url)) !== previous) {
                        window.Alpine?.destroyTree(previous.root);
                    }
                    previous.root.replaceWith(tab.root);
                    for (const [element, left, top] of tab.scrolledElements) {
                        element.scrollLeft = left;
                        element.scrollTop = top;
                    }
                    if (!tab.initialized) {
                        window.Alpine?.initTree(tab.root);
                        tab.initialized = true;
                    }
                };
                if (window.Alpine) {
                    window.Alpine.mutateDom(swap);
                } else {
                    swap();
                }
                activeTab = tab;
                window.scrollTo(...scroll);
            }

            if (pushHistory && window.location.href !== url) {
                window.history[replacing ? 'replaceState' : 'pushState']({ projectDashboardUrl: tab.url }, '', url);
            }
        }

        function showLoading(root, controller, loadingMessage) {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.dataset.dashboardLoading = '';
            loadingOverlay.className = 'absolute inset-0 z-[200] flex items-start justify-center rounded-2xl bg-white/65 px-4 pt-24 backdrop-blur-[2px]';
            loadingOverlay.innerHTML = `<div class="w-full max-w-sm rounded-xl border border-blue-100 bg-white p-5 text-center shadow-xl" role="status" aria-live="polite">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                    <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" transform="translate(0 0)"></path></svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-800">${loadingMessage}</p>
                <p class="mt-1 text-xs text-slate-500">Your dashboard is updating with the selected data.</p>
                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-blue-100"><div class="h-full w-1/3 animate-pulse rounded-full bg-blue-600"></div></div>
                <button type="button" data-cancel-dashboard-request class="pointer-events-auto mt-4 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            </div>`;
            loadingOverlay.querySelector('[data-cancel-dashboard-request]').addEventListener('click', () => controller.abort());
            const backgroundElements = Array.from(root.children);
            backgroundElements.forEach(element => {
                element.inert = true;
            });
            root.append(loadingOverlay);
            root.setAttribute('aria-busy', 'true');

            return () => {
                loadingOverlay.remove();
                backgroundElements.forEach(element => {
                    element.inert = false;
                });
                root.removeAttribute('aria-busy');
            };
        }

        async function fetchTab(url, options, controller) {
            const response = await fetch(url, {
                ...options,
                cache: 'no-store',
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const html = await response.text();
            const root = new DOMParser().parseFromString(html, 'text/html').querySelector(rootSelector);
            if (!response.ok || !root) {
                throw new Error('The dashboard could not be updated. Please try again.');
            }
            return createTab(document.adoptNode(root), response.url);
        }

        function requestTab(url, key, options, mode) {
            const controller = new AbortController();
            const request = { controller, promise: fetchTab(url, options, controller) };
            if (mode !== 'context') {
                requests.set(key, request);
                const finish = () => {
                    if (requests.get(key) === request) {
                        requests.delete(key);
                    }
                };
                request.promise.then(tab => {
                    if (requests.get(key) === request && !controller.signal.aborted) {
                        const resolvedKey = cacheKey(tab.url);
                        const previous = tabs.get(resolvedKey);
                        if (previous && previous !== activeTab && previous.initialized) {
                            window.Alpine?.destroyTree(previous.root);
                        }
                        tab.scroll = previous?.scroll ?? null;
                        tabs.set(resolvedKey, tab);
                    }
                    finish();
                }, finish);
            }
            return request;
        }

        async function navigateDashboard(url, { mode = 'tab', options = {}, pushHistory = true, loadingMessage = 'Loading dashboard...' } = {}) {
            const key = cacheKey(url);
            if (mode === 'context' && options.method !== 'POST' && filterKey(url) === filterKey(activeTab.url)) {
                mode = 'tab';
            }
            const cached = tabs.get(key);
            const current = navigation;
            if (current?.key === key && current.mode === mode && !current.request.controller.signal.aborted) {
                return;
            }
            current?.stopLoading?.();
            if (current?.mode === 'context') {
                current.request.controller.abort();
            }
            navigation = null;
            activeTab.root.querySelector('[data-dashboard-request-error]')?.remove();

            if (mode === 'tab' && cached) {
                displayTab(cached, url, pushHistory);
            }
            const pending = mode === 'context' ? null : requests.get(key);
            if (mode === 'tab' && cached && !pending) {
                return;
            }

            const request = pending && !pending.controller.signal.aborted ? pending : requestTab(url, key, options, mode);
            const next = { key, mode, request };
            navigation = next;
            if (mode !== 'tab' || !cached) {
                next.stopLoading = showLoading(activeTab.root, request.controller, loadingMessage);
            }

            try {
                const tab = await request.promise;
                if (navigation !== next || request.controller.signal.aborted) {
                    return;
                }

                if (mode === 'context') {
                    clearTabs();
                    tabs.set(cacheKey(tab.url), tab);
                }
                next.stopLoading?.();
                next.stopLoading = null;
                displayTab(tab, tab.url, pushHistory, mode === 'refresh');
            } catch (error) {
                if (navigation !== next || request.controller.signal.aborted || error.name === 'AbortError') {
                    return;
                }

                const message = document.createElement('p');
                message.dataset.dashboardRequestError = '';
                message.setAttribute('role', 'alert');
                message.className = 'mx-auto max-w-7xl px-4 pt-3 text-sm font-semibold text-rose-700';
                message.textContent = error.message || 'The dashboard could not be updated. Please try again.';
                activeTab.root.prepend(message);
            } finally {
                if (navigation === next) {
                    next.stopLoading?.();
                    navigation = null;
                }
            }
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-dashboard-async]');
            if (!form) {
                return;
            }

            event.preventDefault();
            if (mutation || (form.dataset.confirm && !window.confirm(form.dataset.confirm))) {
                return;
            }

            form.closest('dialog')?.close();

            const submitter = event.submitter;
            if (submitter) {
                submitter.disabled = true;
            }

            const method = (form.method || 'GET').toUpperCase();
            const formData = new FormData(form);
            const url = new URL(form.action, window.location.href);
            const options = { method };
            const loadingMessage = method === 'GET' ? 'Applying filters...' : (form.id === 'delete-dashboard-tab' ? 'Deleting dashboard...' : 'Saving dashboard widgets...');

            if (method === 'GET') {
                for (const [key, value] of formData.entries()) {
                    url.searchParams.set(key, value.toString());
                }
            } else {
                options.body = formData;
            }

            const update = navigateDashboard(url.href, { mode: 'context', options, loadingMessage });
            if (method !== 'GET') {
                mutation = update;
            }
            update.finally(() => {
                if (mutation === update) {
                    mutation = null;
                }
                if (submitter) {
                    submitter.disabled = false;
                }
            });
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-dashboard-nav]');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            if (!mutation) {
                navigateDashboard(link.href, { mode: link.dataset.dashboardNav });
            }
        });

        window.addEventListener('popstate', async (event) => {
            const location = window.location.href;
            const url = event.state?.projectDashboardUrl || location;
            if (mutation) {
                await mutation;
            }
            if (window.location.href === location) {
                const mode = filterKey(url) === filterKey(activeTab.url) ? 'tab' : 'context';
                navigateDashboard(url, { mode, pushHistory: false });
            }
        });
    })();
</script>
</x-project_app-layout>
