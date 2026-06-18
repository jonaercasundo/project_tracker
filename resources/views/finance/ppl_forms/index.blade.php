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
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200/60 text-[11px] uppercase font-bold tracking-wider text-slate-400">
                        <tr>
                            <th class="px-6 py-4">
                                Project Ledger
                            </th>
                            <th class="px-6 py-4 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($data as $row)

                        <tr class="hover:bg-slate-50/60 transition group">
                            <td colspan="2" class="px-5 py-4">

                                {{-- ================= HEADER ================= --}}
                                <div class="flex items-start justify-between gap-4">

                                    <div class="flex-1">

                                        <div class="flex items-center gap-2 flex-wrap">

                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-wide">
                                                {{ $row->project_code }}
                                            </span>

                                            <span class="text-[10px] text-slate-400 font-mono">
                                                UID {{ $row->project_id_no }} • LOT {{ $row->lot_number }}
                                            </span>

                                        </div>

                                        <div class="mt-1 text-sm font-bold text-slate-900">
                                            {{ $row->project_title }}
                                        </div>

                                        <div class="mt-1 text-[10px] text-slate-500">
                                            📍 {{ $row->region }}
                                        </div>

                                    </div>

                                    <div class="flex gap-2 shrink-0">
                                        <a href="/ppl-forms/{{ $row->id }}"
                                        class="px-3 py-1 text-[11px] font-semibold bg-white border rounded-lg hover:bg-slate-50">
                                            View
                                        </a>

                                        <a href="/ppl-forms/{{ $row->id }}/edit"
                                        class="px-3 py-1 text-[11px] font-semibold bg-amber-50 text-amber-700 border rounded-lg hover:bg-amber-100">
                                            Edit
                                        </a>
                                    </div>

                                </div>

                                <div class="my-3 border-t border-slate-200"></div>

                                {{-- ================= GRID ================= --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-[11px]">

                                    {{-- LEFT --}}
                                    <div class="space-y-2">
                                        <div class="text-slate-400 font-semibold uppercase text-[10px]">Timeline</div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">BID OPENING</span>
                                            <span>{{ $row->bid_opening }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">NOA</span>
                                            <span>{{ $row->noa_months ?? 0 }} mo</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">NTP</span>
                                            <span>{{ $row->ntp_months ?? 0 }} mo</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">DELIVERY</span>
                                            <span>{{ $row->delivery_days ?? 0 }} days</span>
                                        </div>
                                    </div>

                                    {{-- CENTER --}}
                                    <div class="space-y-2">
                                        <div class="text-slate-400 font-semibold uppercase text-[10px]">Financial</div>

                                        <div class="text-2xl font-bold">
                                            ₱{{ number_format($row->abc ?? 0, 2) }}
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">LCB</span>
                                            <span class="text-emerald-700 font-bold">%{{ number_format($row->lcb_abc ?? 0, 2) }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Downpayment</span>
                                            <span>₱{{ number_format($row->factory_downpayment ?? 0, 2) }}</span>
                                        </div>
                                    </div>

                                    {{-- RIGHT --}}
                                    <div class="space-y-2">
                                        <div class="text-slate-400 font-semibold uppercase text-[10px]">Location</div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Warehouse</span>
                                            <span>{{ $row->warehouse_location ?? '—' }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Area</span>
                                            <span>{{ $row->warehouse_area_sqm ?? '—' }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-slate-400">OPEX</span>
                                            <span>% {{ number_format($row->opex_net_sales ?? 0, 2) }}</span>
                                        </div>
                                    </div>

                                </div>

                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-20 text-slate-400">
                                No records found
                            </td>
                        </tr>
                        @endforelse

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