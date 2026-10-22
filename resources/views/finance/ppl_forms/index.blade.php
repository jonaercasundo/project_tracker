<x-finance_app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-base font-black tracking-tight text-slate-900 sm:text-lg">PPL Forms</h1>
            <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Finance procurement & project financial tracking</p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('ppl_forms.import') }}" method="POST" enctype="multipart/form-data" class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-100/80 p-1 sm:flex">
                @csrf
                <label class="text-xs font-medium text-slate-600"><span class="sr-only">Choose import file</span><input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-48 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-white file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-slate-700"></label>
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800">Import</button>
            </form>
            <a href="{{ route('ppl_forms.create') }}" class="inline-flex h-9 items-center rounded-xl bg-blue-600 px-4 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-blue-500">Create New</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form action="{{ route('ppl_forms.index') }}" method="GET" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Search
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Project, code, ID, agency, lot, or form #" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="text-xs font-semibold text-slate-700">Operational project status
                    <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All statuses</option><option value="Unlinked" @selected(($filters['status'] ?? '') === 'Unlinked')>Unlinked / no Project List status</option>@foreach ($projectStatuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Project year
                    <select name="year" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All years</option>@foreach ($years as $year)<option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>@endforeach</select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Lot number
                    <select name="lot" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All lots</option>@foreach ($lots as $lot)<option value="{{ $lot }}" @selected(($filters['lot'] ?? '') === $lot)>{{ $lot }}</option>@endforeach</select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Project link
                    <select name="linkage" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All records</option><option value="linked" @selected(($filters['linkage'] ?? '') === 'linked')>Verified linked project</option><option value="unlinked" @selected(($filters['linkage'] ?? '') === 'unlinked')>Unlinked / needs review</option></select>
                </label>
                @if ($hasSourceLinks)
                    <label class="text-xs font-semibold text-slate-700">Bidding record
                        <select name="bidding_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All bidding records</option>@foreach ($biddingRecords as $bidding)<option value="{{ $bidding->id }}" @selected((string) ($filters['bidding_id'] ?? '') === (string) $bidding->id)>#{{ $bidding->id }} · {{ $bidding->project_name }}</option>@endforeach</select>
                    </label>
                @endif
                @if ($hasFinancialPlans)
                    <label class="text-xs font-semibold text-slate-700">Financial plan
                        <select name="financial_plan" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All forms</option><option value="linked" @selected(($filters['financial_plan'] ?? '') === 'linked')>Has active plan</option><option value="unlinked" @selected(($filters['financial_plan'] ?? '') === 'unlinked')>No active plan</option></select>
                    </label>
                @endif
                <label class="text-xs font-semibold text-slate-700">Created from
                    <input type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="text-xs font-semibold text-slate-700">Created to
                    <input type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="text-xs font-semibold text-slate-700">Sort by
                    <select name="sort_by" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (['project_title' => 'Project name', 'created_at' => 'Date created', 'abc' => 'ABC', 'project_code' => 'Project code', 'project_id_no' => 'Project reference', 'lot_number' => 'Lot number', 'id' => 'PPL Form ID'] as $sortKey => $sortLabel)<option value="{{ $sortKey }}" @selected(($filters['sort_by'] ?? 'id') === $sortKey)>{{ $sortLabel }}</option>@endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Order
                    <select name="sort_order" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="asc" @selected(($filters['sort_order'] ?? 'desc') === 'asc')>Ascending</option><option value="desc" @selected(($filters['sort_order'] ?? 'desc') === 'desc')>Descending</option></select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Rows per page
                    <select name="per_page" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="10" @selected(($filters['per_page'] ?? '10') === '10')>10</option><option value="25" @selected(($filters['per_page'] ?? '') === '25')>25</option><option value="50" @selected(($filters['per_page'] ?? '') === '50')>50</option></select>
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
                <a href="{{ route('ppl_forms.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear filters</a>
                <p class="ml-auto text-sm text-slate-600"><span class="font-semibold text-slate-900">{{ number_format($data->total()) }}</span> matching PPL Forms</p>
            </div>
        </form>

        @if ($activeFilterCount > 0)
            <div class="flex flex-wrap items-center gap-2" aria-label="Active filters">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Active filters</span>
                @foreach ($filters as $key => $value)
                    @if ($value !== null && $value !== '' && ! in_array($key, ['sort_by', 'sort_order', 'per_page'], true))
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-800">{{ str_replace('_', ' ', ucfirst($key)) }}: {{ $value }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] border-collapse text-sm text-left">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Project ledger</th><th class="px-5 py-4">Operational status</th><th class="px-5 py-4">Year</th><th class="px-5 py-4 text-right">ABC</th><th class="px-5 py-4">Created</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data as $row)
                            @php
                                $source = $hasSourceLinks ? $row->sourceLink : null;
                                $verifiedProject = $source?->match_status === 'verified' ? $source->project : null;
                                $verifiedBidding = $source?->match_status === 'verified' ? $source->bidding : null;
                                $operationalStatus = $verifiedProject?->status ?? 'Unlinked';
                                $agency = $verifiedProject?->agency ?? $verifiedBidding?->procuring_entity;
                                $returnParams = request()->query();
                            @endphp
                            <tr class="align-top hover:bg-slate-50/60">
                                <td class="max-w-[420px] px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded border border-blue-100 bg-blue-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-blue-700">{{ $row->project_code ?: 'No code' }}</span><span class="font-mono text-[10px] text-slate-500">PPL #{{ $row->id }} · Ref {{ $row->project_id_no ?: '—' }} · Lot {{ $row->lot_number ?: '—' }}</span></div>
                                    <p class="mt-1 line-clamp-2 break-words font-bold text-slate-900" title="{{ $row->project_title }}">{{ $row->project_title }}</p>
                                    <p class="mt-1 truncate text-[10px] text-slate-500">{{ $agency ?: 'Agency not linked' }} · {{ $row->region ?: 'No region' }}</p>
                                    @if ($verifiedBidding)<p class="mt-1 text-[10px] font-medium text-indigo-700">Bidding #{{ $verifiedBidding->id }} · {{ $verifiedBidding->project_name }}</p>@endif
                                </td>
                                <td class="px-5 py-4"><span class="inline-flex max-w-40 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $operationalStatus === 'Unlinked' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-800' }}">{{ $operationalStatus }}</span></td>
                                <td class="px-5 py-4 tabular-nums text-slate-700">{{ $row->year_of_revenue_recognition ?: '—' }}</td>
                                <td class="px-5 py-4 text-right tabular-nums font-semibold text-slate-900">₱{{ number_format((float) ($row->abc ?? 0), 2) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-600">{{ $row->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('ppl_forms.show', ['id' => $row->id] + $returnParams) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">View</a><a href="{{ url('/ppl-forms/'.$row->id.'/edit') }}" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-semibold text-amber-800 hover:bg-amber-100">Edit</a></div></td>
                            </tr>
                            <tr class="bg-slate-50/40"><td colspan="6" class="px-5 pb-5 pt-0">
                                <div class="grid grid-cols-1 gap-4 border-t border-slate-200 pt-4 text-[11px] md:grid-cols-3">
                                    <div class="space-y-2"><p class="font-bold uppercase tracking-wide text-slate-400">Timeline</p><p class="flex justify-between gap-3"><span class="text-slate-500">Bid opening</span><span>{{ $row->bid_opening?->format('M j, Y') ?? '—' }}</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">NOA / NTP</span><span>{{ $row->noa_months ?? 0 }} / {{ $row->ntp_months ?? 0 }} mo</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">Delivery days</span><span>{{ $row->delivery_days_after_production ?? 0 }}</span></p></div>
                                    <div class="space-y-2"><p class="font-bold uppercase tracking-wide text-slate-400">Financial</p><p class="flex justify-between gap-3"><span class="text-slate-500">LCB ABC</span><span class="font-semibold text-emerald-700">₱{{ number_format((float) ($row->lcb_abc ?? 0), 2) }}</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">Factory downpayment</span><span>₱{{ number_format((float) ($row->factory_downpayment ?? 0), 2) }}</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">OPEX</span><span>₱{{ number_format((float) ($row->opex ?? 0), 2) }}</span></p></div>
                                    <div class="space-y-2"><p class="font-bold uppercase tracking-wide text-slate-400">Location & linkage</p><p class="flex justify-between gap-3"><span class="text-slate-500">Warehouse</span><span class="max-w-48 truncate" title="{{ $row->warehouse_location }}">{{ $row->warehouse_location ?: '—' }}</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">Area</span><span>{{ $row->warehouse_area_sqm ?? '—' }} sqm</span></p><p class="flex justify-between gap-3"><span class="text-slate-500">Financial plan</span><span>@if ($hasFinancialPlans && $row->has_financial_plan > 0) Linked @else Not linked @endif</span></p></div>
                                </div>
                            </td></tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16 text-center"><p class="font-semibold text-slate-800">No PPL Forms match these search and filter settings.</p><p class="mt-1 text-xs text-slate-500">Clear one or more filters, or try another project name, code, reference, or lot number.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">Showing <span class="font-semibold text-slate-900">{{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }}</span> of <span class="font-semibold text-slate-900">{{ number_format($data->total()) }}</span> matching forms</p>
                <div>{{ $data->links('pagination::tailwind') }}</div>
            </div>
        </div>
    </div>
</x-finance_app-layout>
