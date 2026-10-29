<x-project_app-layout>
    <div class="space-y-6" id="delivery-monitoring" data-endpoint="{{ route('deliveries.monitoring') }}" data-locations-endpoint="{{ route('deliveries.monitoring.locations') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operations / Delivery Monitoring</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Project Delivery Monitoring</h1>
                <p class="mt-2 text-sm text-slate-500">Check warehouse readiness, then follow DR, Stock Out, Delivered, Billing and Billed.</p>
            </div>
            <span class="text-xs text-slate-500">Live Tracker records · <span id="monitoring-project-count">{{ isset($filters['project_id']) ? 'Loading…' : 'Choose a project' }}</span></span>
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
                <div class="min-w-0">
                    <x-searchable-project-filter
                        :projects="$projects"
                        name="project_id"
                        :selected="$filters['project_id'] ?? ''"
                        all-label="All projects"
                        id="monitoring-project"
                        rounded="rounded-xl"
                    />
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
                        <input type="checkbox" id="monitoring-active" name="active_only" value="1" @checked($filters['active_only'] ?? false) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
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

        <dialog id="sync-billing-dialog" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-0 shadow-xl backdrop:bg-slate-900/50">
            <form id="sync-billing-confirmation" class="space-y-4 p-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Sync Billing</h2>
                    <p class="mt-2 text-sm text-slate-600">Billing-eligible DRs will advance through Stock Out and Delivered, and their quantities will be deducted from inventory.</p>
                    <p class="mt-2 text-xs text-slate-500">Each DR uses its assigned logistics warehouse when available. Otherwise, its school region selects the configured regional hub: Luzon to Pampanga, Visayas to Cebu, and Mindanao to Davao. DRs without a clear warehouse will be left unchanged and reported.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" data-cancel-sync-billing class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Confirm and Sync</button>
                </div>
            </form>
        </dialog>

        <dialog id="sync-spreadsheet-dialog" class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-0 shadow-xl backdrop:bg-slate-900/50">
            <form id="sync-spreadsheet-form" class="max-h-[90vh] space-y-5 overflow-y-auto p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Spreadsheet update</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Sync Spreadsheet</h2>
                    <p class="mt-2 text-sm text-slate-600">Enter the School ID and billing details, then preview the DR/package matches and target cells before syncing. You can enter a tracker School ID or a configured mapped sheet SchoolID. The selected billing status fills blank status cells; existing nonblank values are preserved. Each Lot total is calculated from its DR.</p>
                </div>
                <div>
                    <label for="spreadsheet-school-id" class="mb-1 block text-xs font-semibold text-slate-600">School ID</label>
                    <input id="spreadsheet-school-id" name="school_id" type="text" autocomplete="off" maxlength="255" required placeholder="Enter tracker School ID or mapped sheet SchoolID" class="w-full rounded-lg border-slate-200 text-sm">
                    <p class="mt-1 text-xs text-slate-500">For Bacsil Elementary School, enter 102205 (tracker) or 134967 (mapped spreadsheet ID).</p>
                </div>
                <div id="spreadsheet-school-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2" hidden>
                    <div class="sm:col-span-2">
                        <label for="spreadsheet-billing-status" class="mb-1 block text-xs font-semibold text-slate-600">Billing status (Lot X)</label>
                        <select id="spreadsheet-billing-status" name="billing_status" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                            <option value="">Choose a billing status</option>
                            <option value="1st Billing">1st Billing</option>
                            <option value="2nd Billing">Second Billing</option>
                            <option value="3rd Billing">Third Billing</option>
                        </select>
                    </div>
                    <div>
                        <label for="spreadsheet-date-received" class="mb-1 block text-xs font-semibold text-slate-600">Date received (Lot X)</label>
                        <input id="spreadsheet-date-received" name="date_received" type="date" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label for="spreadsheet-received-by" class="mb-1 block text-xs font-semibold text-slate-600">Received by (Lot X)</label>
                        <input id="spreadsheet-received-by" name="received_by" type="text" maxlength="255" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label for="spreadsheet-inspection-1" class="mb-1 block text-xs font-semibold text-slate-600">Inspection 1 (Lot X)</label>
                        <input id="spreadsheet-inspection-1" name="inspection_1" type="text" maxlength="255" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label for="spreadsheet-inspection-2" class="mb-1 block text-xs font-semibold text-slate-600">Inspection 2 (Lot X)</label>
                        <input id="spreadsheet-inspection-2" name="inspection_2" type="text" maxlength="255" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label for="spreadsheet-date-checking" class="mb-1 block text-xs font-semibold text-slate-600">Date checking (Lot X)</label>
                        <input id="spreadsheet-date-checking" name="date_checking" type="date" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label for="spreadsheet-date-encoding" class="mb-1 block text-xs font-semibold text-slate-600">Date encoding (Lot X)</label>
                        <input id="spreadsheet-date-encoding" name="date_encoding" type="date" required disabled class="w-full rounded-lg border-slate-200 text-sm">
                    </div>
                </div>
                <p id="spreadsheet-school-row-count" class="text-xs text-slate-500" aria-live="polite">Enter a School ID to prepare a preview of matching DR/package rows.</p>
                <p id="spreadsheet-preview-issues" class="text-xs text-amber-700" role="status" aria-live="polite"></p>
                <div id="spreadsheet-dry-run" class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700" hidden aria-live="polite"></div>
                <input type="hidden" name="mode" value="preview">
                <input type="hidden" name="preview_token" value="">
                <p data-spreadsheet-modal-feedback class="text-sm text-slate-600" role="status" aria-live="polite"></p>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" data-cancel-sync-spreadsheet class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" data-submit-spreadsheet disabled class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">Preview Changes</button>
                </div>
            </form>
        </dialog>
    </div>
</x-project_app-layout>
