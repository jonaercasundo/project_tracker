<x-finance_app-layout>
    {{-- LAYOUT TOP HEADER BREADCRUMB HOOKS --}}
    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-base font-black text-slate-900 tracking-tight sm:text-lg">PPL Forms</h1>
            <p class="text-[11px] font-semibold text-slate-400 tracking-wide uppercase mt-0.5">
                Finance procurement & project financial tracking
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- DATA INGESTION ENGINE --}}
            <form action="/ppl-forms/import" method="POST" enctype="multipart/form-data" class="hidden sm:flex items-center gap-2 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60 transition focus-within:border-blue-500/50">
                @csrf
                <label class="block text-xs font-medium text-slate-600 cursor-pointer hover:text-blue-600 transition">
                    <span class="sr-only">Choose file</span>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="block w-full text-xs text-slate-500
                                  file:mr-3 file:py-1 file:px-2.5
                                  file:rounded-lg file:border-0
                                  file:text-[11px] file:font-bold
                                  file:bg-white file:text-slate-700
                                  file:shadow-sm hover:file:bg-slate-50
                                  cursor-pointer focus:outline-none" />
                </label>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition active:scale-95 shadow-sm">
                    Import
                </button>
            </form>

            {{-- RECORD INITIALIZATION TRIGGER --}}
            <a href="/ppl-forms/create"
               class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold tracking-wide uppercase bg-blue-600 text-white rounded-xl hover:bg-blue-500 active:scale-95 transition shadow-md shadow-blue-600/10 h-[36px]">
                <svg class="w-3.5 h-3.5 mr-1.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Create New
            </a>
        </div>
    </x-slot>

    {{-- PRIMARY ACTION DATA CONTAINER --}}
    <div class="space-y-5">

        {{-- MOBILE BULK ACTION DROPDOWN PANEL --}}
        <div class="block sm:hidden bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Workspace Actions</p>
            <form action="/ppl-forms/import" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-xs text-slate-600 border border-slate-200 rounded-xl p-2 bg-slate-50" />
                <button type="submit" class="w-full py-2 text-xs font-bold bg-slate-900 text-white rounded-xl">Import Workbook Ledger</button>
            </form>
        </div>

        {{-- FILTER MATRIX AND DATA COMPASS BAR --}}
        <div class="bg-white/90 p-4 rounded-2xl border border-slate-200/80 shadow-sm backdrop-blur-md">
            <form action="{{ request()->url() }}" method="GET" class="flex flex-col lg:flex-row flex-wrap gap-3 items-stretch lg:items-center">
                
                {{-- Global Search Bar --}}
                <div class="flex-1 min-w-[280px] relative">
                    <label for="search" class="sr-only">Search Parameters</label>
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                        id="search"
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search parameters (Code, Project Title, ID, Vendor...)" 
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition placeholder-slate-400 font-medium text-slate-800">
                </div>

                {{-- Context Evaluation Controls --}}
                <div class="flex items-center gap-3 w-full lg:w-auto lg:ml-auto justify-end">
                    @if(request()->hasAny(['search', 'sort']))
                        <a href="{{ request()->url() }}" class="w-full lg:w-auto text-center px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-slate-600 transition-colors duration-150">
                            Clear Filters
                        </a>
                    @endif
                    <button type="submit" class="w-full lg:w-auto px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl shadow-sm shadow-blue-500/10 transition-all duration-150 whitespace-nowrap">
                        Search Records
                    </button>
                </div>
            </form>
        </div>

        {{-- MASTER ARCHIVAL LEDGER DATA MATRIX --}}
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
    <thead class="bg-slate-50 text-[11px] uppercase text-slate-400">
        <tr>
            <th class="px-6 py-4">Project</th>
            <th class="px-6 py-4">ABC</th>
            <th class="px-6 py-4">Region</th>
            <th class="px-6 py-4 text-right">Action</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-slate-100">

        @foreach($data as $row)

        {{-- ================= SUMMARY ROW ================= --}}
        <tr class="hover:bg-slate-50 cursor-pointer"
            onclick="toggleRow('{{ $row->id }}')">

            <td class="px-6 py-4">
                <div class="font-bold text-slate-900">
                    {{ $row->project_title }}
                </div>
                <div class="text-[10px] text-slate-400">
                    {{ $row->project_code }} • LOT {{ $row->lot_number }}
                </div>
            </td>

            <td class="px-6 py-4 font-bold text-slate-900">
                ₱{{ number_format($row->abc ?? 0, 2) }}
            </td>

            <td class="px-6 py-4 text-slate-500">
                {{ $row->region }}
            </td>

            <td class="px-6 py-4 text-right">
                <a href="/ppl-forms/{{ $row->id }}"
                   class="text-blue-600 text-xs font-bold">
                    View
                </a>
            </td>
        </tr>

        {{-- ================= EXPAND ROW ================= --}}
        <tr id="expand-{{ $row->id }}" class="hidden bg-slate-50/40">
            <td colspan="4" class="px-6 py-6">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-[11px]">

                    {{-- ================= IDENTITY ================= --}}
                    <div class="space-y-2">
                        <div class="font-bold text-slate-400 uppercase text-[10px]">Identity</div>

                        <div>PROJECT CODE: {{ $row->project_code }}</div>
                        <div>LOT #: {{ $row->lot_number }}</div>
                        <div>PROJECT ID: {{ $row->project_id_no }}</div>
                        <div>REGION: {{ $row->region }}</div>
                        <div>BIDDER: {{ $row->bidder ?? '—' }}</div>
                        <div>SIGNATORY: {{ $row->authorized_signatory ?? '—' }}</div>
                    </div>

                    {{-- ================= TIMELINE ================= --}}
                    <div class="space-y-2">
                        <div class="font-bold text-slate-400 uppercase text-[10px]">Timeline</div>

                        <div>BID OPENING: {{ $row->bid_opening }}</div>
                        <div>NOA: {{ $row->noa_months ?? 0 }} mo</div>
                        <div>NTP: {{ $row->ntp_months ?? 0 }} mo</div>
                        <div>DELIVERY: {{ $row->delivery_days ?? 0 }} days</div>
                        <div>PRODUCTION: {{ $row->production_lead_time ?? 0 }}</div>
                        <div>COLLECTION: {{ $row->collection_period ?? 0 }}</div>
                        <div>DELIVERY PERIOD: {{ $row->delivery_period ?? 0 }}</div>
                        <div>NTP DATE: {{ $row->ntp_date ?? '—' }}</div>
                        <div>COLLECTION DATE: {{ $row->collection_date ?? '—' }}</div>
                        <div>END DATE: {{ $row->end_date ?? '—' }}</div>
                    </div>

                    {{-- ================= FINANCIAL ================= --}}
                    <div class="space-y-2">
                        <div class="font-bold text-slate-400 uppercase text-[10px]">Financial</div>

                        <div>ABC: ₱{{ number_format($row->abc ?? 0, 2) }}</div>
                        <div>LCB: %{{ number_format($row->lcb_abc ?? 0, 2) }}</div>
                        <div>FOREX: {{ $row->forex ?? '-' }}</div>
                        <div>DOWNPAYMENT: ₱{{ number_format($row->factory_downpayment ?? 0, 2) }}</div>

                        <div>PF1: %{{ number_format($row->pf1_contract_amt ?? 0, 2) }}</div>
                        <div>PF2: %{{ number_format($row->pf2_contract_amt ?? 0, 2) }}</div>
                        <div>PF3: %{{ number_format($row->pf3_contract_amt ?? 0, 2) }}</div>

                        <div>LL COM: %{{ number_format($row->ll_com_factory_cost ?? 0, 2) }}</div>
                        <div>INTEREST + DST: %{{ number_format($row->interest_rate_dst ?? 0, 2) }}</div>

                        <div>FULL PAYMENT: %{{ number_format($row->full_payment_after_delivery ?? 0, 2) }}</div>
                    </div>

                    {{-- ================= LOGISTICS ================= --}}
                    <div class="space-y-2">
                        <div class="font-bold text-slate-400 uppercase text-[10px]">Logistics</div>

                        <div>WAREHOUSE: {{ $row->warehouse_location ?? '—' }}</div>
                        <div>AREA: {{ $row->warehouse_area_sqm ?? '—' }}</div>
                        <div>CONTAINER: {{ $row->shipping_brokerage ?? '—' }}</div>
                        <div>RATE: {{ $row->rate_per_container ?? '—' }}</div>

                        <div>LOGISTICS: %{{ number_format($row->logistics_abc ?? 0, 2) }}</div>

                        <div>RENT/SQM: {{ $row->warehouse_rental_per_sqm ?? '—' }}</div>
                        <div>RENT MONTHS: {{ $row->warehouse_rental_no_of_months ?? '—' }}</div>

                        <div>OTHER EXPENSES: ₱{{ number_format($row->other_expenses_contract_amt ?? 0, 2) }}</div>
                    </div>

                    {{-- ================= OPERATIONS & TAX ================= --}}
                    <div class="space-y-2">
                        <div class="font-bold text-slate-400 uppercase text-[10px]">Operations & Tax</div>

                        <div>MANPOWER: %{{ number_format($row->manpower_abc ?? 0, 2) }}</div>
                        <div>ASSEMBLY: %{{ number_format($row->assembly_abc ?? 0, 2) }}</div>
                        <div>R&D: %{{ number_format($row->rnd_abc ?? 0, 2) }}</div>
                        <div>FACILITATION: %{{ number_format($row->facilitation_abc ?? 0, 2) }}</div>

                        <div>OPEX: %{{ number_format($row->opex_net_sales ?? 0, 2) }}</div>

                        <div>INCOME TAX: {{ $row->income_tax_net_sales ?? 0 }}</div>
                        <div>INCENTIVE: {{ $row->incentive_net_profit ?? 0 }}</div>
                        <div>IMPORT VAT: {{ $row->importation_vat_cogs ?? 0 }}</div>
                        <div>OUTPUT VAT: {{ $row->output_vat ?? 0 }}</div>

                        <div>HOLD-OUT: {{ $row->hold_out ?? 0 }}</div>
                        <div>RETENTION: {{ $row->retention ?? 0 }}</div>
                        <div>BUSINESS CYCLE: {{ $row->business_cycle ?? '—' }}</div>
                    </div>

                </div>

            </td>
        </tr>

        @endforeach

    </tbody>
</table>
            </div>

            {{-- MASTER SEGMENT PAGINATION BAR --}}
            @if ($data->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-white flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    
                    {{-- Left info: Swapped to item counts for better UX --}}
                    <div class="text-sm text-slate-600">
                        Showing 
                        <span class="font-semibold text-slate-900">{{ $data->firstItem() }}</span> 
                        to 
                        <span class="font-semibold text-slate-900">{{ $data->lastItem() }}</span> 
                        of 
                        <span class="font-semibold text-slate-900">{{ $data->total() }}</span> 
                        results
                    </div>

                    {{-- Pagination links wrapper --}}
                    <div class="flex items-center separator-clean">
                        {{ $data->appends(request()->query())->links('pagination::tailwind') }}
                    </div>
                </div>
            @endif
        </div>

    </div>
</x-finance_app-layout>