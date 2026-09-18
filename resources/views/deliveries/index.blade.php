<x-project_app-layout>

<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Deliveries Tracking
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor DR groups and item progression in real-time
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl whitespace-nowrap
                bg-gradient-to-b from-blue-500 to-blue-600 text-white
                hover:from-blue-600 hover:to-blue-700
                active:scale-95
                shadow-sm hover:shadow-md
                ring-1 ring-inset ring-white/10
                transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Delivery
            </button>

            <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl whitespace-nowrap
                bg-white border border-slate-200 text-slate-700
                hover:bg-slate-50 hover:border-slate-300
                active:scale-95
                transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
                Import
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    @include('deliveries.partials._filters')
    <label class="flex items-center gap-2 px-3 py-2 bg-white border rounded-xl text-xs font-semibold">
        <input type="checkbox" id="select-all-drs">
        Select All
    </label>

    {{-- SUMMARY CARDS (scoped to project / year / lot / region filters) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

        {{-- TOTAL PENDING (DR-level) --}}
        <div class="group relative bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200 p-4 flex flex-col gap-1 overflow-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -right-2 -bottom-2 w-16 h-16 text-amber-50 group-hover:text-amber-100 transition-colors" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15h-2v-6h2zm0-8h-2V7h2z"/>
            </svg>
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Pending</span>
                <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
            </div>
            <span class="relative text-2xl font-extrabold text-slate-900 tabular-nums truncate" title="{{ $stats['total_pending'] ?? 0 }}">
                {{ number_format($stats['total_pending'] ?? 0) }}
            </span>
            <span class="relative text-[11px] text-slate-400">DRs fully pending</span>
        </div>

        {{-- TOTAL RELEASED (DR-level) --}}
        <div class="group relative bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-200 p-4 flex flex-col gap-1 overflow-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -right-2 -bottom-2 w-16 h-16 text-blue-50 group-hover:text-blue-100 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wide text-blue-600">Released</span>
                <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
            </div>
            <span class="relative text-2xl font-extrabold text-slate-900 tabular-nums truncate" title="{{ $stats['total_released'] ?? 0 }}">
                {{ number_format($stats['total_released'] ?? 0) }}
            </span>
            <span class="relative text-[11px] text-slate-400">DRs fully released</span>
        </div>

        {{-- TOTAL DELIVERED (DR-level) --}}
        <div class="group relative bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-200 p-4 flex flex-col gap-1 overflow-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -right-2 -bottom-2 w-16 h-16 text-emerald-50 group-hover:text-emerald-100 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wide text-emerald-600">Delivered</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
            </div>
            <span class="relative text-2xl font-extrabold text-slate-900 tabular-nums truncate" title="{{ $stats['total_delivered'] ?? 0 }}">
                {{ number_format($stats['total_delivered'] ?? 0) }}
            </span>
            <span class="relative text-[11px] text-slate-400">DRs fully delivered to school</span>
        </div>

        {{-- TOTAL COLLECTION --}}
        <div class="group relative bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-cyan-200 transition-all duration-200 p-4 flex flex-col gap-1 overflow-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -right-2 -bottom-2 w-16 h-16 text-cyan-50 group-hover:text-cyan-100 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <path d="M2 10h20"/>
            </svg>
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wide text-cyan-600">Billing</span>
                <span class="w-2 h-2 rounded-full bg-cyan-400 shrink-0"></span>
            </div>
            <span class="relative text-2xl font-extrabold text-slate-900 tabular-nums truncate" title="{{ $stats['total_collection'] ?? 0 }}">
                {{ number_format($stats['total_collection'] ?? 0) }}
            </span>
            <span class="relative text-[11px] text-slate-400">For billing</span>
        </div>

        {{-- TOTAL BILLED --}}
        <div class="group relative bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-slate-300 transition-all duration-200 p-4 flex flex-col gap-1 overflow-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -right-2 -bottom-2 w-16 h-16 text-slate-50 group-hover:text-slate-100 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12l2 2 4-4M12 3l8 4.5v9L12 21l-8-4.5v-9z"/>
            </svg>
            <div class="relative flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-600">Billed</span>
                <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
            </div>
            <span class="relative text-2xl font-extrabold text-slate-900 tabular-nums truncate" title="{{ $stats['total_billed'] ?? 0 }}">
                {{ number_format($stats['total_billed'] ?? 0) }}
            </span>
            <span class="relative text-[11px] text-slate-400">Total billed</span>
        </div>

    </div>

    {{-- CONTENT STATE HANDLERS --}}
    @if(empty($grouped_deliveries))
        <div class="text-center py-16 text-slate-400 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <span class="block text-sm font-medium text-slate-600">No deliveries found.</span>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your tracking filters above.</p>
        </div>
    @else

        <div class="space-y-5">
            @foreach($grouped_deliveries as $dr_group)
                @php
                    // Determine this DR's overall status
                    $drStatuses = [];
                    foreach ($dr_group['deliveries'] as $delivery) {
                        foreach ($delivery->packages ?? [] as $pkg) {
                            $drStatuses[] = strtolower($pkg['status']);
                        }
                    }

                    if (empty($drStatuses)) {
                        $drOverallStatus = null;
                    } elseif (count(array_unique($drStatuses)) === 1) {
                        $drOverallStatus = $drStatuses[0];
                    } else {
                        $drOverallStatus = 'mixed';
                    }

                    // Badge styles now include a dot color + richer states
                    $drStatusStyles = [
                        'delivered' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                        'released'  => ['bg-blue-50 text-blue-700 border-blue-200', 'bg-blue-500'],
                        'warehouse' => ['bg-cyan-50 text-cyan-700 border-cyan-200', 'bg-cyan-500'],
                        'pending'   => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
                        'mixed'     => ['bg-slate-100 text-slate-600 border-slate-300', 'bg-slate-400'],
                    ];
                    [$drStatusClass, $drDotClass] = $drStatusStyles[$drOverallStatus] ?? $drStatusStyles['mixed'];
                @endphp

                {{-- DR CARD --}}
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">

                    {{-- DR HEADER --}}
                    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 bg-slate-50/70 border-b border-slate-100">

                        <div class="flex items-start gap-3 min-w-0">
                            <input type="checkbox"
                                class="w-4 h-4 mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dr-checkbox cursor-pointer shrink-0"
                                value="{{ $dr_group['delivery_id'] }}"
                                data-school-id="{{ $dr_group['school_id'] }}">

                            <div class="min-w-0">
                                <div class="text-sm font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                                    <span>DR #{{ $dr_group['dr_no'] }}</span>
                                </div>

                                <div class="mt-1 space-y-0.5">
                                    <div class="flex items-start gap-1.5 text-[11px] text-slate-500 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mt-0.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                                        </svg>
                                        <span>{{ $dr_group['project_name'] }}</span>
                                    </div>

                                    <div class="flex items-start gap-1.5 text-[11px] text-slate-500 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mt-0.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 9L12 2 2 9l10 7 10-7z"/>
                                            <path d="M6 10.6V16c0 1 2.7 3 6 3s6-2 6-3v-5.4"/>
                                        </svg>
                                        <span>{{ $dr_group['school_name'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT SIDE: badge always on its own row, buttons always paired --}}
                        <div class="flex flex-col items-end gap-2 shrink-0 self-end sm:self-auto">
                            @if($drOverallStatus)
                                <span class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold border {{ $drStatusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $drDotClass }} {{ $drOverallStatus === 'mixed' ? 'animate-pulse' : '' }}"></span>
                                    {{ $drOverallStatus === 'mixed' ? 'IN PROGRESS' : strtoupper($drOverallStatus) }}
                                </span>
                            @endif

                            <div class="flex items-center gap-2">
                                <button type="button"
                                    onclick="generateQR()"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-[11px] font-bold rounded-lg whitespace-nowrap
                                        bg-gradient-to-b from-indigo-500 to-indigo-600 text-white
                                        hover:from-indigo-600 hover:to-indigo-700
                                        active:scale-95
                                        shadow-sm hover:shadow-md
                                        ring-1 ring-inset ring-white/10
                                        transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                                    </svg>
                                    Download AR
                                </button>

                                <button type="button"
                                    onclick="generateLabels()"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-[11px] font-bold rounded-lg whitespace-nowrap
                                        bg-gradient-to-b from-emerald-500 to-emerald-600 text-white
                                        hover:from-emerald-600 hover:to-emerald-700
                                        active:scale-95
                                        shadow-sm hover:shadow-md
                                        ring-1 ring-inset ring-white/10
                                        transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                                        <path d="M3 9h18M8 4v5"/>
                                    </svg>
                                    Download Label
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ITEMS LIST --}}
                    <div class="divide-y divide-slate-100">
                        @foreach($dr_group['deliveries'] as $d)
                            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 hover:bg-slate-50/50 transition">

                                {{-- LEFT DETAILS --}}
                                <div class="space-y-2.5 max-w-3xl min-w-0">
                                    <div class="text-sm font-semibold text-slate-700 flex items-center flex-wrap gap-2">
                                        <span class="text-slate-500 font-bold text-xs tracking-wide">
                                            LOT {{ $d->lot_name }}
                                        </span>
                                        @if($d->keystage_num)
                                            <span class="text-slate-300">·</span>
                                            <span class="text-slate-400 font-medium text-xs">
                                                Keystage {{ $d->keystage_num }}
                                                <span class="text-slate-500 font-normal">{{ $d->description }}</span>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-xs text-slate-500 leading-relaxed space-y-3">
                                        @forelse($d->packages ?? [] as $pkg)
                                            @php
                                                $statusStyles = [
                                                    'delivered' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                                                    'released'  => ['bg-blue-50 text-blue-700 border-blue-200', 'bg-blue-500'],
                                                    'warehouse' => ['bg-cyan-50 text-cyan-700 border-cyan-200', 'bg-cyan-500'],
                                                    'pending'   => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
                                                ];
                                                $statusKey = strtolower($pkg['status']);
                                                [$statusClass, $dotClass] = $statusStyles[$statusKey] ?? $statusStyles['pending'];
                                                $itemCount = count($pkg['items'] ?? []);
                                            @endphp

                                            <div>
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-slate-600">
                                                            Package {{ $pkg['package_num'] }} of {{ $pkg['total_packages'] }}
                                                        </span>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                                            <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                                            {{ strtoupper($pkg['status']) }}
                                                        </span>
                                                    </div>
                                                    @if($itemCount)
                                                        <span class="text-[10px] text-slate-400">
                                                            {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="space-y-0.5 pl-0.5">
                                                    @forelse($pkg['items'] as $item)
                                                        <div class="flex items-start gap-2">
                                                            <span class="text-slate-300">•</span>
                                                            <span class="text-slate-600">{{ $item }}</span>
                                                        </div>
                                                    @empty
                                                        <span class="text-slate-400 italic">No items</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-slate-400 italic">No items available</span>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- ROW ACTIONS --}}
                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                    @if(auth()->user()->hasAnyRole(['Super Admin','Office Admin','Office Coordinator','Warehouse Admin']))
                                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200/60 hover:bg-amber-100 active:scale-95 whitespace-nowrap transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            Edit
                                        </button>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>

        {{-- PAGINATION --}}
        <div class="flex items-center justify-center mt-8 gap-3">
            @if($page > 1)
                <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $page - 1])) }}"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition">
                    Prev
                </a>
            @endif

            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl">
                Page {{ $page }} of {{ $total_pages }}
            </span>

            @if($page < $total_pages)
                <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $page + 1])) }}"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition">
                    Next
                </a>
            @endif
        </div>

    @endif

</div>

</x-project_app-layout>