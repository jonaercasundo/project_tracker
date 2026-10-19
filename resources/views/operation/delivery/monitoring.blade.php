<x-project_app-layout>
    <div class="space-y-6" id="delivery-monitoring" data-endpoint="{{ route('deliveries.monitoring') }}" data-locations-endpoint="{{ route('deliveries.monitoring.locations') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operations / Delivery Monitoring</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Project Delivery Monitoring</h1>
                <p class="mt-2 text-sm text-slate-500">Check warehouse readiness, then follow DR, Stock Out, Delivered, Billing and Billed.</p>
            </div>
            <span class="text-xs text-slate-500">Live Tracker records · <span id="monitoring-project-count">{{ $report['summary']['projects_count'] }}</span> projects</span>
        </div>

        <div id="monitoring-results" aria-live="polite">
            @include('operation.delivery.partials.monitoring-results', ['report' => $report])
        </div>

        <details open class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <summary class="cursor-pointer text-sm font-bold text-slate-900">Filter progress <span class="ml-2 text-xs font-normal text-slate-500">DR filters apply to delivery/billing; warehouse inventory remains project-wide</span></summary>
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
                            @foreach($field === 'region' ? $locations : array_filter([$filters[$field] ?? null]) as $value)
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
        </details>

        <section id="monitoring-projects" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="projects-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="projects-heading" class="font-bold text-slate-900">Operational pipeline</h2>
                    <p class="mt-1 text-xs text-slate-500">View Details for inventory quantities, DR allocations, delivery and billing.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="monitoring-search">Search project or reference</label>
                    <input form="monitoring-filters" id="monitoring-search" name="search" type="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" placeholder="Search project or reference..." class="w-64 max-w-full rounded-lg border-slate-200 text-sm">
                    <label class="sr-only" for="monitoring-sort">Sort projects</label>
                    <select form="monitoring-filters" id="monitoring-sort" name="sort" class="rounded-lg border-slate-200 text-sm">
                        <option value="">Default order</option>
                        @foreach(['project' => 'Project', 'progress' => 'Delivered allocation %', 'total_drs' => 'Total DRs', 'total_dr_packages' => 'Total DR Packages', 'last_delivery' => 'Last Delivery', 'end_date' => 'End Date'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="monitoring-direction">Sort direction</label>
                    <select form="monitoring-filters" id="monitoring-direction" name="direction" class="rounded-lg border-slate-200 text-sm">
                        <option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>Ascending</option>
                        <option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Descending</option>
                    </select>
                    <button form="monitoring-filters" type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Search / Sort</button>
                </div>
            </div>
            <div id="monitoring-table" class="overflow-x-auto">
                @include('operation.delivery.partials.monitoring-projects', ['report' => $report])
            </div>
        </section>

        <section id="logistics-tracking" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                 data-endpoint="{{ route('deliveries.monitoring.logistics') }}" aria-labelledby="logistics-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">External carrier updates</p>
                    <h2 id="logistics-heading" class="mt-1 text-lg font-extrabold text-slate-900">Logistics DR Tracking</h2>
                    <p class="mt-1 text-xs text-slate-500">Logistics milestones are separate from MMC receiving and acceptance.</p>
                </div>
                <span id="logistics-updated" class="text-xs text-slate-500" aria-live="polite">Refreshes every 7 seconds while this page is active</span>
            </div>

            <form id="logistics-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-8">
                <div>
                    <label for="logistics-project" class="mb-1 block text-xs font-semibold text-slate-600">Project</label>
                    <select id="logistics-project" name="project_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->project_id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="logistics-lot" class="mb-1 block text-xs font-semibold text-slate-600">Lot</label>
                    <select id="logistics-lot" name="lot_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All lots</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->lot_id }}">{{ $lot->lot_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="logistics-partner" class="mb-1 block text-xs font-semibold text-slate-600">Logistics partner</label>
                    <select id="logistics-partner" name="integration_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All partners</option>
                        @foreach($logisticsIntegrations as $integration)
                            <option value="{{ $integration->integration_id }}">{{ $integration->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="logistics-dr" class="mb-1 block text-xs font-semibold text-slate-600">DR#</label>
                    <input id="logistics-dr" name="dr_reference" type="search" maxlength="100" autocomplete="off" placeholder="Exact DR number" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="logistics-school" class="mb-1 block text-xs font-semibold text-slate-600">School</label>
                    <input id="logistics-school" name="school" type="search" maxlength="255" autocomplete="off" placeholder="School name or ID" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="logistics-status" class="mb-1 block text-xs font-semibold text-slate-600">Logistics status</label>
                    <select id="logistics-status" name="status" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All statuses</option>
                        <option value="logistics_received">Logistics Received</option>
                        <option value="delivered">Logistics Delivered</option>
                    </select>
                </div>
                <div>
                    <label for="logistics-date-from" class="mb-1 block text-xs font-semibold text-slate-600">From</label>
                    <input id="logistics-date-from" name="date_from" type="date" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="logistics-date-to" class="mb-1 block text-xs font-semibold text-slate-600">To</label>
                    <input id="logistics-date-to" name="date_to" type="date" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <input type="hidden" name="per_page" value="25">
                <input type="hidden" name="page" value="1">
                <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-8">
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Apply filters</button>
                    <button type="reset" id="logistics-reset" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
                    <p id="logistics-error" role="alert" class="ml-2 hidden text-sm text-rose-600"></p>
                </div>
            </form>

            <div id="logistics-content" aria-live="polite">
                @include('operation.delivery.partials.monitoring-logistics-tracking', ['logistics' => $logistics])
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
</x-project_app-layout>
