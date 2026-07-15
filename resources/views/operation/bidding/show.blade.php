<x-project_app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight sm:text-2xl">
                Bidding Document
            </h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Project Information Summary
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('project.bidding.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50
                      border border-slate-200 text-xs font-semibold text-slate-600 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <a href="{{ route('project.bidding.edit', $project->id) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600
                      text-xs font-semibold text-white transition-colors shadow-sm shadow-amber-500/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </x-slot>

    <div class="space-y-4 max-w-7xl mx-auto">

        {{-- ================= PROJECT INFORMATION ================= --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Card header --}}
            <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-6 h-6 rounded-md bg-blue-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-[12.5px] font-semibold text-slate-800">Project Information</h2>
                </div>

                @switch($project->status)
                    @case('Draft')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-slate-100 text-slate-600 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span>Draft
                        </span>
                        @break
                    @case('For Review')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>For Review
                        </span>
                        @break
                    @case('Published')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>Published
                        </span>
                        @break
                    @case('Awarded')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>Awarded
                        </span>
                        @break
                    @case('Completed')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span>Completed
                        </span>
                        @break
                    @default
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block"></span>Cancelled
                        </span>
                @endswitch
            </div>

            {{-- ABC highlight banner --}}
            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50/60 to-white flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                        Approved Budget for the Contract (ABC)
                    </p>
                    <p class="text-2xl font-bold text-blue-700 mt-0.5 font-mono tracking-tight tabular-nums">
                        ₱{{ number_format($project->approved_budget_contract_abc, 2) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Bid Opening</p>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5">{{ $project->date_of_bid_opening }}</p>
                </div>
            </div>

            {{-- Fields grid --}}
            <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">

                {{-- Left column --}}
                <div class="divide-y divide-slate-50">
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-[11px] font-medium text-slate-400">Project ID</span>
                        <span class="text-[12px] font-semibold text-slate-800 font-mono">{{ $project->project_id }}</span>
                    </div>
                    <div class="flex items-start justify-between px-5 py-3 gap-6">
                        <span class="text-[11px] font-medium text-slate-400 whitespace-nowrap">Project Name</span>
                        <span class="text-[12px] font-semibold text-slate-800 text-right">{{ $project->project_name }}</span>
                    </div>
                    <div class="flex items-start justify-between px-5 py-3 gap-6">
                        <span class="text-[11px] font-medium text-slate-400 whitespace-nowrap">Procuring Entity / Agency</span>
                        <span class="text-[12px] font-medium text-slate-600 text-right">{{ $project->procuring_entity }}</span>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="divide-y divide-slate-50">
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-[11px] font-medium text-slate-400">Delivery Period</span>
                        <span class="text-[12px] font-medium text-slate-600">{{ $project->delivery_period }} calendar days</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-[11px] font-medium text-slate-400">Bid Opening</span>
                        <span class="text-[12px] font-medium text-slate-600">{{ $project->date_of_bid_opening }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-[11px] font-medium text-slate-400">Status</span>
                        <span class="text-[12px] font-medium text-slate-600">{{ $project->status }}</span>
                    </div>
                </div>

            </div>
        </div>
        
        {{-- ================= LOTS + ITEMS ================= --}}
        @forelse($project->lots as $lot)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">

                {{-- LOT HEADER --}}
                <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-md bg-violet-50 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 7h18M3 12h18M3 17h18"/>
                            </svg>
                        </div>

                        <h2 class="text-[12.5px] font-semibold text-slate-800">
                            {{ $lot->lot_no ?? '—' }} - {{ $lot->lot_name ?? 'Untitled Lot' }}
                        </h2>
                    </div>

                    <span class="text-[11px] text-slate-400 font-medium">
                        {{ $lot->items->count() }} {{ Str::plural('item', $lot->items->count()) }}
                    </span>
                </div>

                {{-- ITEMS TABLE --}}
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">

                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                                <th class="px-4 py-2.5 w-16 text-center">Item No.</th>
                                <th class="px-4 py-2.5">Item Description</th>
                                <th class="px-4 py-2.5 w-16">Unit</th>
                                <th class="px-4 py-2.5 w-20 text-right">Quantity</th>
                                <th class="px-4 py-2.5 w-32 text-right">Unit Cost (PHP)</th>
                                <th class="px-4 py-2.5 w-36 text-right">Total Amount</th>
                                <th class="px-4 py-2.5 w-28">Brand / Specs</th>
                                <th class="px-4 py-2.5 w-32">Remarks</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-50 text-slate-600">

                        @forelse($lot->items as $item)
                            <tr class="hover:bg-slate-50/50 transition-colors">

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md
                                                bg-slate-100 text-slate-500 text-[11px] font-semibold">
                                        {{ $item->item_no }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-[12px] font-semibold text-slate-800">
                                    {{ $item->item_description }}
                                </td>

                                <td class="px-4 py-3 text-[11.5px] text-slate-500">
                                    {{ $item->unit }}
                                </td>

                                <td class="px-4 py-3 text-right text-[12px] font-medium text-slate-700">
                                    {{ number_format($item->quantity) }}
                                </td>

                                <td class="px-4 py-3 text-right text-[12px] font-mono text-slate-700 tabular-nums">
                                    ₱{{ number_format($item->unit_cost, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right text-[12px] font-bold text-slate-900 font-mono tabular-nums">
                                    ₱{{ number_format($item->total_amount, 2) }}
                                </td>

                                <td class="px-4 py-3 text-[11.5px] text-slate-500">
                                    {{ $item->brand ?: '—' }}
                                </td>

                                <td class="px-4 py-3 text-[11.5px] text-slate-400">
                                    {{ $item->remarks ?: '—' }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400 text-[12px]">
                                    No items in this lot.
                                </td>
                            </tr>
                        @endforelse

                        </tbody>

                        {{-- LOT TOTAL --}}
                        <tfoot>
                            <tr class="bg-slate-50/60 border-t border-slate-200">
                                <td colspan="5" class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                    Lot Total
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-blue-700 text-[14px] font-mono tabular-nums">
                                    ₱{{ number_format($lot->items->sum('total_amount'), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>

        @empty

    <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400 text-[12px]">
        No lots available.
    </div>

@endforelse

        {{-- ================= NOTES / SPECIAL CONDITIONS ================= --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-md bg-amber-50 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h2 class="text-[12.5px] font-semibold text-slate-800">Notes / Special Conditions</h2>
            </div>
            <div class="px-5 py-4">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-2">Remarks</p>
                <p class="text-[12.5px] text-slate-600 leading-relaxed font-medium">
                    {{ $project->notes_special_condition ?? '—' }}
                </p>
            </div>
        </div>

        {{-- ================= DOCUMENT CONTROL ================= --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h2 class="text-[12.5px] font-semibold text-slate-800">Document Control</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-slate-100">
                <div class="bg-white px-5 py-4">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Prepared By</p>
                    <p class="text-[12.5px] font-semibold text-slate-700 mt-1">{{ $project->prepared_by ?: '—' }}</p>
                </div>
                <div class="bg-white px-5 py-4">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Prepared Date</p>
                    <p class="text-[12.5px] font-semibold text-slate-700 mt-1">{{ $project->prepared_date ?: '—' }}</p>
                </div>
                <div class="bg-white px-5 py-4">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Verified By</p>
                    <p class="text-[12.5px] font-semibold text-slate-700 mt-1">{{ $project->verified_by ?: '—' }}</p>
                </div>
            </div>
        </div>

    </div>

</x-project_app-layout>