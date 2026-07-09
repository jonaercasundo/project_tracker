<x-project_app-layout>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 antialiased font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 pb-6 border-b border-slate-100">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-slate-950 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    Inventory History
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Track inventory changes.
                </p>
            </div>
        </div>

        <a href="{{ route('inventory.index') }}"
           class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition tracking-wide shadow-sm">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>
    </div>

    {{-- FILTERS --}}
    <form method="GET" class="mt-8 bg-slate-50 border border-slate-200/60 rounded-xl p-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">

            {{-- Search --}}
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search item, user, remarks..."
                    class="w-full pl-9 h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            {{-- Change Type --}}
            <select
                name="change_type"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="">All Changes</option>
                <option value="insert" {{ request('change_type') == 'insert' ? 'selected' : '' }}>Insert</option>
                <option value="update" {{ request('change_type') == 'update' ? 'selected' : '' }}>Update</option>
                <option value="delete" {{ request('change_type') == 'delete' ? 'selected' : '' }}>Delete</option>
            </select>

            {{-- Warehouse --}}
            <select
                name="warehouse_id"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->warehouse_id }}" {{ request('warehouse_id') == $warehouse->warehouse_id ? 'selected' : '' }}>
                        {{ $warehouse->warehouse_name }}
                    </option>
                @endforeach
            </select>

            {{-- Date From --}}
            <input
                type="date"
                name="date_from"
                value="{{ request('date_from') }}"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-600 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">

            {{-- Date To --}}
            <input
                type="date"
                name="date_to"
                value="{{ request('date_to') }}"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-600 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
        </div>

        {{-- Action Buttons --}}
        <div class="mt-3 flex gap-2 justify-end">
            <a href="{{ route('inventory.history') }}"
               class="px-4 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                Reset
            </a>
            <button
                type="submit"
                class="px-5 h-9 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 active:bg-slate-950 transition tracking-wide">
                Filter
            </button>
        </div>
    </form>

    {{-- HISTORY TABLE --}}
    <div class="mt-6 bg-white rounded-xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/70 backdrop-blur sticky top-0 z-10 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Warehouse</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Qty Change</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Change Type</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Changed By</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-50 bg-white">
                @forelse($histories as $history)
                    @php
                        $changeStyles = match($history->change_type) {
                            'insert' => ['bg-emerald-50 text-emerald-700 border border-emerald-200/50', 'bg-emerald-500', 'Insert'],
                            'update' => ['bg-blue-50 text-blue-700 border border-blue-200/50', 'bg-blue-500', 'Update'],
                            default  => ['bg-rose-50 text-rose-700 border border-rose-200/50', 'bg-rose-500', 'Delete'],
                        };
                        $qtyDelta = (int) $history->new_qty - (int) $history->old_qty;
                        $deltaClass = $qtyDelta > 0 ? 'text-emerald-600 font-semibold' : ($qtyDelta < 0 ? 'text-rose-600 font-semibold' : 'text-slate-400');
                        $deltaSign = $qtyDelta > 0 ? '+' : '';
                    @endphp

                    <tr class="hover:bg-slate-50/40 transition-colors duration-150">
                        {{-- Date --}}
                        <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                            <span class="font-medium">{{ \Carbon\Carbon::parse($history->changed_at)->format('M d, Y') }}</span>
                            <span class="block text-[10px] font-mono text-slate-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($history->changed_at)->format('h:i A') }}
                            </span>
                        </td>

                        {{-- Item --}}
                        <td class="px-5 py-3.5 text-xs font-semibold text-slate-900 tracking-tight">
                            {{ optional($history->item)->item_name }}
                        </td>

                        {{-- Warehouse --}}
                        <td class="px-5 py-3.5 text-xs text-slate-600">
                            {{ optional($history->warehouse)->warehouse_name }}
                        </td>

                        {{-- Qty Change --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2 text-xs tabular-nums">
                                <span class="text-slate-400 font-normal">{{ $history->old_qty }}</span>
                                <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <span class="font-semibold text-slate-800">{{ $history->new_qty }}</span>
                                <span class="text-[11px] {{ $deltaClass }} ml-0.5">
                                    ({{ $deltaSign }}{{ $qtyDelta }})
                                </span>
                            </div>
                        </td>

                        {{-- Change Badge --}}
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md {{ $changeStyles[0] }} text-[11px] font-medium tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full {{ $changeStyles[1] }}"></span>
                                {{ $changeStyles[2] }}
                            </span>
                        </td>

                        {{-- Changed By --}}
                        <td class="px-5 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                            {{ $history->changed_by }}
                        </td>

                        {{-- Remarks --}}
                        <td class="px-5 py-3.5 text-xs text-slate-500 max-w-xs truncate" title="{{ $history->remarks }}">
                            {{ $history->remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-14">
                            <div class="flex flex-col items-center justify-center text-center px-4">
                                <div class="h-11 w-11 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mb-3 border border-slate-100">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-800">No inventory history found</p>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-xs">Try adjusting your filters or date range.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Wrapper --}}
        @if(method_exists($histories, 'links'))
            <div class="p-3 border-t border-slate-100 bg-white">
                {{ $histories->links() }}
            </div>
        @endif
    </div>

</div>

</x-project_app-layout>