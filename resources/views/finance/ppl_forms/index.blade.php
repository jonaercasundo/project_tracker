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

        @if ($financialSummary)
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Filtered financial totals">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><h2 class="text-sm font-bold text-slate-900">Filtered lot totals</h2><p class="text-xs text-slate-600">Historical PPL Form ABC: <strong>&#8369;{{ number_format((float) $financialSummary['form_abc'], 2) }}</strong>. The amounts below use the latest saved version of one active MMC plan per matching form, across all filtered pages.</p></div>
                    <p class="text-xs font-semibold text-slate-700">{{ number_format($financialSummary['plan_count']) }} versioned plans included @if ($financialSummary['ambiguous_count']) &middot; {{ number_format($financialSummary['ambiguous_count']) }} forms with multiple or incomplete plans excluded @endif</p>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
                    @foreach (['abc' => 'ABC', 'contract_amount' => 'Contract', 'net_sales' => 'Net sales', 'factory_cost' => 'Factory cost', 'pf1' => 'PF1', 'pf2' => 'PF2', 'pf3' => 'PF3', 'interest_dst_working_capital' => 'Interest / DST', 'total_cost_of_sales' => 'Total cost', 'gross_profit' => 'Gross profit', 'opex' => 'OPEX', 'net_profit_before_tax' => 'Before tax', 'income_tax_provision' => 'Income tax', 'net_profit_after_tax' => 'After tax', 'ppl' => 'PPL', 'working_capital_needed' => 'Working capital'] as $key => $label)
                        <div class="rounded-xl bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-1 text-sm font-bold tabular-nums {{ (float) $financialSummary['amounts'][$key] < 0 ? 'text-rose-800' : 'text-slate-900' }}">&#8369;{{ number_format((float) $financialSummary['amounts'][$key], 2) }}</p></div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-600">Gross margin: <strong>{{ $financialSummary['gpm'] === null ? 'N/A' : number_format((float) $financialSummary['gpm'] * 100, 2).'%' }}</strong> &middot; Net margin: <strong>{{ $financialSummary['npm'] === null ? 'N/A' : number_format((float) $financialSummary['npm'] * 100, 2).'%' }}</strong>. Margins use summed profit divided by summed net sales; rates are never summed.</p>
            </section>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                @php
                    $financialColumns = [
                        'contract_amount' => 'Contract', 'net_sales' => 'Net sales', 'factory_cost' => 'Factory cost',
                        'pf1' => 'PF1', 'pf2' => 'PF2', 'pf3' => 'PF3',
                        'interest_dst_working_capital' => 'Interest / DST', 'total_cost_of_sales' => 'Total cost',
                        'gross_profit' => 'Gross profit', 'gpm' => 'Gross margin', 'opex' => 'OPEX',
                        'net_profit_before_tax' => 'Before tax', 'income_tax_provision' => 'Income tax',
                        'net_profit_after_tax' => 'After tax', 'ppl' => 'PPL', 'npm' => 'Net margin',
                        'working_capital_needed' => 'Working capital',
                    ];
                @endphp
                <table class="w-max min-w-full border-collapse text-left text-xs" aria-label="PPL Forms project and lot financial table">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 min-w-[300px] max-w-[380px] bg-slate-50 px-4 py-3">Project / lot</th>
                            <th scope="col" class="min-w-[125px] px-3 py-3">Project status</th>
                            <th scope="col" class="min-w-[70px] px-3 py-3">Year</th>
                            <th scope="col" class="min-w-[135px] px-3 py-3 text-right">ABC</th>
                            <th scope="col" class="min-w-[95px] px-3 py-3 text-right">LCB discount</th>
                            @foreach ($financialColumns as $label)
                                <th scope="col" class="min-w-[135px] px-3 py-3 text-right">{{ $label }}</th>
                            @endforeach
                            <th scope="col" class="min-w-[100px] px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data as $row)
                            @php
                                $source = $hasSourceLinks ? $row->sourceLink : null;
                                $verifiedProject = $source?->match_status === 'verified' ? $source->project : null;
                                $verifiedBidding = $source?->match_status === 'verified' ? $source->bidding : null;
                                $operationalStatus = $verifiedProject?->status ?? 'Unlinked';
                                $plans = $hasFinancialPlans ? $row->financialPlans : collect();
                                $version = $plans->count() === 1 ? $plans->first()?->latestVersion : null;
                                $results = $version?->calculation_results ?? [];
                                $lcbRate = $version?->input_data['workbook_import']['source_cells']['1. FORM']['N']['value'] ?? $row->lcb_abc;
                                $returnParams = request()->query();
                            @endphp
                            <tr class="align-middle hover:bg-slate-50/70">
                                <td class="sticky left-0 z-10 max-w-[380px] bg-white px-4 py-3 group-hover:bg-slate-50">
                                    <p class="line-clamp-2 max-w-[350px] break-words font-bold leading-5 text-slate-900" title="{{ $row->project_title }}">{{ $row->project_title ?: $row->project_code }}</p>
                                    <p class="mt-1 text-[11px] text-slate-600">Lot {{ $row->lot_number ?: 'N/A' }} &middot; PPL #{{ $row->id }} &middot; {{ $row->project_code ?: 'No code' }}</p>
                                    <p class="max-w-[350px] truncate text-[10px] text-slate-500" title="{{ $row->project_id_no }}">{{ $row->project_id_no ?: 'No project reference' }} @if ($verifiedProject) &middot; Project #{{ $verifiedProject->getKey() }} @endif @if ($verifiedBidding) &middot; Bidding #{{ $verifiedBidding->id }} @endif</p>
                                </td>
                                <td class="px-3 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $verifiedProject ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $operationalStatus }}</span></td>
                                <td class="px-3 py-3 tabular-nums">{{ $row->year_of_revenue_recognition ?: 'N/A' }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums" title="Historical PPL Form lot ABC">@if ($row->abc === null) &mdash; @else &#8369;{{ number_format((float) $row->abc, 2) }} @endif @if (isset($results['abc']) && $row->abc !== null && number_format((float) $results['abc'], 2, '.', '') !== number_format((float) $row->abc, 2, '.', '')) <span class="block text-[10px] text-amber-800" title="Saved financial plan ABC differs from the historical PPL Form ABC">Plan ABC differs</span> @endif</td>
                                <td class="px-3 py-3 text-right tabular-nums" title="LCB discount rate from the imported workbook when available, otherwise the historical PPL Form">{{ ! is_numeric($lcbRate) ? 'N/A' : number_format((float) $lcbRate * 100, 2).'%' }}</td>
                                @foreach ($financialColumns as $key => $label)
                                    @php $value = in_array($key, ['pf1', 'pf2', 'pf3', 'interest_dst_working_capital'], true) ? data_get($results, 'costs.'.$key) : ($results[$key] ?? null); @endphp
                                    <td class="px-3 py-3 text-right tabular-nums {{ $value !== null && (float) $value < 0 ? 'bg-rose-50 font-semibold text-rose-800' : 'text-slate-800' }}" @if ($value !== null && (float) $value < 0) title="Negative financial result" @endif>
                                        @if ($value === null) &mdash; @elseif (in_array($key, ['gpm', 'npm'], true)) {{ number_format((float) $value * 100, 2) }}% @else &#8369;{{ number_format((float) $value, 2) }} @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-right"><div class="flex justify-end gap-1"><a href="{{ route('ppl_forms.show', ['id' => $row->id] + $returnParams) }}" class="rounded border border-slate-200 px-2 py-1 font-semibold hover:bg-slate-100">View</a><a href="{{ url('/ppl-forms/'.$row->id.'/edit') }}" class="rounded border border-amber-200 bg-amber-50 px-2 py-1 font-semibold text-amber-800 hover:bg-amber-100">Edit</a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 6 + count($financialColumns) }}" class="px-6 py-16 text-center"><p class="font-semibold text-slate-800">No PPL Forms match these search and filter settings.</p><p class="mt-1 text-xs text-slate-500">Clear one or more filters, or try another project name, code, reference, or lot number.</p></td></tr>
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
