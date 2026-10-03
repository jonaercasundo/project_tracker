<x-project_app-layout>
    <div class="space-y-6" id="delivery-monitoring" data-endpoint="{{ route('deliveries.monitoring') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operations / Delivery Monitoring</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Project Delivery Progress</h1>
                <p class="mt-2 text-sm text-slate-500">Monitor delivery, package, and billing progress across active projects.</p>
            </div>
            <span class="text-xs text-slate-500">Live Tracker records · <span id="monitoring-project-count">{{ $report['summary']['projects_count'] }}</span> projects</span>
        </div>

        <div id="monitoring-results" aria-live="polite">
            @include('operation.delivery.partials.monitoring-results', ['report' => $report])
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="filter-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="filter-heading" class="text-sm font-bold text-slate-900">Filter progress</h2>
                <p class="text-xs text-slate-500">Year applies to the scheduled delivery date.</p>
            </div>
            <form id="monitoring-filters" method="GET" action="{{ route('deliveries.monitoring') }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="monitoring-year" class="mb-1 block text-xs font-semibold text-slate-600">Year</label>
                    <select id="monitoring-year" name="year" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All years</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected(($filters['year'] ?? '') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="monitoring-project" class="mb-1 block text-xs font-semibold text-slate-600">Project</label>
                    <select id="monitoring-project" name="project_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="monitoring-status" class="mb-1 block text-xs font-semibold text-slate-600">Delivery Status</label>
                    <select id="monitoring-status" name="delivery_status" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        @foreach(['pending' => 'Pending', 'released' => 'Released', 'delivered' => 'Delivered', 'accepted' => 'Accepted / Completed', 'warehouse' => 'Warehouse', 'mixed' => 'Mixed', 'cancelled' => 'Cancelled', 'for approval' => 'For approval'] as $status => $label)
                            <option value="{{ $status }}" @selected(($filters['delivery_status'] ?? '') === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach(['region' => 'Region', 'division' => 'Division', 'municipality' => 'Municipality'] as $field => $label)
                    <div>
                        <label for="monitoring-{{ $field }}" class="mb-1 block text-xs font-semibold text-slate-600">{{ $label }}</label>
                        <select id="monitoring-{{ $field }}" name="{{ $field }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All {{ strtolower($label) }}s</option>
                            @foreach($locations->pluck($field)->filter()->unique()->sort() as $value)
                                <option value="{{ $value }}" @selected(($filters[$field] ?? '') === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="flex flex-wrap items-center justify-between gap-4 sm:col-span-2 xl:col-span-3">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="active_only" value="0">
                        <input type="checkbox" id="monitoring-active" name="active_only" value="1" @checked($filters['active_only'] ?? true) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="monitoring-active" class="text-sm text-slate-600">Active projects only</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" id="monitoring-apply" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Apply Filters</button>
                        <a id="monitoring-reset" href="{{ route('deliveries.monitoring') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset Filters</a>
                    </div>
                </div>
            </form>
            <p id="monitoring-error" role="alert" class="mt-3 hidden text-sm text-rose-600"></p>
            @if($errors->any())
                <p class="mt-3 text-sm text-rose-600">{{ $errors->first() }}</p>
            @endif
        </section>

        <section id="monitoring-projects" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="projects-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="projects-heading" class="font-bold text-slate-900">Project delivery progress</h2>
                    <p class="mt-1 text-xs text-slate-500">Delivery totals count DR receipts. Expand a project to see every stage.</p>
                </div>
                <span class="text-xs font-medium text-blue-600">Delivered + accepted packages count as complete</span>
            </div>
            <div id="monitoring-table" class="overflow-x-auto">
                @include('operation.delivery.partials.monitoring-projects', ['report' => $report])
            </div>
        </section>
        <details class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs text-slate-500">
            <summary class="cursor-pointer font-semibold text-slate-700">How progress is calculated</summary>
            <dl class="mt-3 grid gap-3">
                @foreach($report['definitions'] as $key => $definition)
                    <div><dt class="font-semibold text-slate-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt><dd class="mt-1">{{ $definition }}</dd></div>
                @endforeach
            </dl>
        </details>
    </div>
    @push('scripts')
        <script>
            window.deliveryMonitoringLocations = {{ Illuminate\Support\Js::from($locations) }};
        </script>
    @endpush
</x-project_app-layout>
