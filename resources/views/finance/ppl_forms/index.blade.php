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
                    @if(request()->hasAny(['search', 'sort_by']))
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
                    {{-- DYNAMIC SEGMENT INTERACTION ROUTINES --}}
                    @php
                        function sortUrl($column) {
                            $currentSort = request('sort_by');
                            $currentOrder = request('sort_order', 'asc');
                            $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';

                            return request()->fullUrlWithQuery([
                                'sort_by' => $column,
                                'sort_order' => $order
                            ]);
                        }
                    @endphp
                    <thead class="bg-slate-50 border-b border-slate-200/60 text-[11px] uppercase font-bold tracking-wider text-slate-400">
                        <tr>
                            <th scope="col" class="px-6 py-4">
                                <a href="{{ sortUrl('project_title') }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                                    Project Title
                                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </a>
                            </th>

                            <th scope="col" class="px-6 py-4">
                                <a href="{{ sortUrl('bid_opening') }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                                    Production Timelines
                                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </a>
                            </th>

                            <th scope="col" class="px-6 py-4">
                                <a href="{{ sortUrl('abc') }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                                    Financial Status Summary
                                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </a>
                            </th>

                            <th scope="col" class="px-6 py-4">
                                <a href="{{ sortUrl('ntp_date') }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                                    Milestone Benchmarks
                                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </a>
                            </th>

                            <th scope="col" class="px-6 py-4 text-right">System Interventions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($data as $row)
                        <tr class="hover:bg-slate-50/60 transition duration-150 group items-start">
                            
                            {{-- COLUMN 1: PROJECT IDENTITY --}}
                            <td class="px-6 py-4 max-w-sm">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-wide">
                                        {{ $row->project_code }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        UID {{ $row->project_id_no }} • LOT {{ $row->lot_number }}
                                    </span>
                                </div>

                                <div class="mt-1.5 text-sm font-bold text-slate-900 leading-snug">
                                    {{ $row->project_title }}
                                </div>

                                <div class="mt-1 text-[10px] text-slate-500 flex flex-wrap gap-2.5 items-center">
                                    <span>📍 {{ $row->region }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="truncate max-w-[150px]" title="{{ $row->bidder }}">{{ $row->bidder }}</span>
                                    @if($row->forex)
                                        <span class="text-slate-300">•</span>
                                        <span class="bg-slate-100 px-1 rounded font-mono text-slate-600 text-[9px]">FX {{ $row->forex }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- COLUMN 2: TIMELINES --}}
                            <td class="px-6 py-4 text-[11px] whitespace-nowrap">
                                <div class="space-y-1 min-w-[140px]">
                                    <div class="flex justify-between gap-4">
                                        <span class="text-slate-400">Bid Opening</span>
                                        <span class="text-slate-700 font-medium text-right">{{ $row->bid_opening }}</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-slate-400">NOA Duration</span>
                                        <span class="text-slate-700 font-medium text-right">{{ $row->noa_months ?? 0 }} mo</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-slate-400">NTP Duration</span>
                                        <span class="text-slate-700 font-medium text-right">{{ $row->ntp_months ?? 0 }} mo</span>
                                    </div>
                                    <div class="flex justify-between gap-4 pt-0.5 border-t border-dashed border-slate-100">
                                        <span class="text-slate-400 font-semibold">Total Lead Time</span>
                                        <span class="text-indigo-600 font-bold text-right">{{ $row->production_lead_time ?? 0 }} days</span>
                                    </div>
                                </div>
                            </td>

                            {{-- COLUMN 3: FINANCIAL DATA --}}
                            <td class="px-6 py-4 text-[11px] whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">ABC Allocation</div>
                                    <div class="text-sm font-bold text-slate-900">
                                        ₱{{ number_format($row->abc ?? 0, 2) }}
                                    </div>
                                    <div class="flex items-center gap-1.5 pt-0.5">
                                        <span class="text-slate-400">LCB:</span>
                                        <span class="text-emerald-700 font-bold">
                                            ₱{{ number_format($row->lcb_abc ?? 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- COLUMN 4: MILESTONES --}}
                            <td class="px-6 py-4 text-[11px] whitespace-nowrap">
                                <div class="space-y-1 min-w-[130px]">
                                    <div class="flex justify-between gap-3">
                                        <span class="text-slate-400">NTP Issued</span>
                                        <span class="text-slate-700 font-medium">{{ $row->ntp_date ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-slate-400">Factory Del.</span>
                                        <span class="text-slate-700 font-medium">{{ $row->factory_delivery ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-slate-400">Drop Target</span>
                                        <span class="text-blue-600 font-semibold">{{ $row->first_delivery_date ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-slate-400">Collection</span>
                                        <span class="text-slate-700 font-medium">{{ $row->collection_date ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- COLUMN 5: SYSTEM INTERVENTIONS (ACTIONS) --}}
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/ppl-forms/{{ $row->id }}"
                                       class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm">
                                        View
                                    </a>
                                    <a href="/ppl-forms/{{ $row->id }}/edit"
                                       class="px-2.5 py-1.5 text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100 rounded-lg hover:bg-amber-100 transition shadow-sm">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-20 bg-slate-50/30">
                                <div class="flex flex-col items-center max-w-sm mx-auto">
                                    <div class="p-3 bg-slate-100 text-slate-400 rounded-full mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25m-2.25-2.25l-2.25 2.25m2.25-2.25l2.25-2.25M3.75 5.25h16.5A2.25 2.25 0 0122 7.5v.75m-19.5 0v-.75A2.25 2.25 0 013.75 5.25z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        No Active PPL Ledgers Located
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[280px]">
                                        No matching records found. Try adjusting parameters or executing a fresh document import ledger cycle.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MASTER SEGMENT PAGINATION BAR --}}
            @if ($data->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-white flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    
                    {{-- Left info --}}
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