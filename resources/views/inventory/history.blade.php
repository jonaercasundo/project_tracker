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
        <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

            {{-- Search --}}
            <div class="min-w-0 space-y-1">
                <label for="inventory-history-search" class="text-xs font-semibold text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                    <input
                        id="inventory-history-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Item, user, remarks..."
                        class="h-[42px] w-full rounded-lg border-slate-200 bg-white pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            {{-- Project --}}
            <x-searchable-project-filter
                :projects="$projects"
                name="project_id"
                :selected="request('project_id', '')"
                all-label="All Projects"
                :truncate-selected="true"
                id="inventory-history-project"
                rounded="rounded-lg" />

            {{-- Change Type --}}
            <div class="min-w-0 space-y-1">
                <label for="inventory-history-change-type" class="text-xs font-semibold text-slate-600">Change Type</label>
                <select
                    id="inventory-history-change-type"
                    name="change_type"
                    class="h-[42px] w-full rounded-lg border-slate-200 bg-white text-xs text-slate-700 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Changes</option>
                    <option value="insert" {{ request('change_type') == 'insert' ? 'selected' : '' }}>Insert</option>
                    <option value="update" {{ request('change_type') == 'update' ? 'selected' : '' }}>Update</option>
                    <option value="delete" {{ request('change_type') == 'delete' ? 'selected' : '' }}>Delete</option>
                    <option value="stock_in" {{ request('change_type') == 'stock_in' ? 'selected' : '' }}>Stock In</option>
                    <option value="stock_out" {{ request('change_type') == 'stock_out' ? 'selected' : '' }}>Stock Out</option>
                </select>
            </div>

            {{-- Warehouse --}}
            <div class="min-w-0 space-y-1">
                <label for="inventory-history-warehouse" class="text-xs font-semibold text-slate-600">Warehouse</label>
                <select
                    id="inventory-history-warehouse"
                    name="warehouse_id"
                    class="h-[42px] w-full rounded-lg border-slate-200 bg-white text-xs text-slate-700 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->warehouse_id }}" {{ request('warehouse_id') == $warehouse->warehouse_id ? 'selected' : '' }}>
                            {{ $warehouse->warehouse_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div class="min-w-0 space-y-1">
                <label for="inventory-history-date-from" class="text-xs font-semibold text-slate-600">Start Date</label>
                <input
                    id="inventory-history-date-from"
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="h-[42px] w-full rounded-lg border-slate-200 bg-white px-3 text-xs text-slate-600 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            {{-- Date To --}}
            <div class="min-w-0 space-y-1">
                <label for="inventory-history-date-to" class="text-xs font-semibold text-slate-600">End Date</label>
                <input
                    id="inventory-history-date-to"
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="h-[42px] w-full rounded-lg border-slate-200 bg-white px-3 text-xs text-slate-600 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('inventory.history') }}"
               class="flex h-[42px] items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                Reset
            </a>
            <button
                type="submit"
                class="h-[42px] rounded-lg bg-slate-900 px-5 text-xs font-semibold tracking-wide text-white transition hover:bg-slate-800 active:bg-slate-950">
                Filter
            </button>
        </div>
    </form>

    {{-- HISTORY TABLE --}}
    <div class="mt-6 bg-white rounded-xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1180px] divide-y divide-slate-100">
                <thead class="bg-slate-50/70 backdrop-blur sticky top-0 z-10 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Batch No.</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Warehouse</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-4">Previous Quantity</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-4">Current Quantity</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-4">Quantity Variance</th>
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
                            'stock_in' => ['bg-green-50 text-green-700 border border-green-200/50', 'bg-green-500', 'Stock In'],
                            'stock_out' => ['bg-red-50 text-red-700 border border-red-200/50', 'bg-red-500', 'Stock Out'],
                            'delivered' => ['bg-purple-50 text-purple-700 border border-purple-200/50', 'bg-purple-500', 'Delivered'],
                            default  => ['bg-rose-50 text-rose-700 border border-rose-200/50', 'bg-rose-500', 'Delete'],
                        };

                        // Single source of truth for the delta — derived straight from
                        // old_qty/new_qty on this row. (Previously this was computed
                        // here correctly, then immediately overwritten below by
                        // $history->qty_change, a column that doesn't exist on
                        // inventory_history — that dead/wrong block has been removed.)
                        $qtyDelta = $history->qty_change;
                        $previousQty = $history->old_qty === null ? '—' : number_format((float) $history->old_qty, 0);
                        $currentQty = $history->new_qty === null ? '—' : number_format((float) $history->new_qty, 0);
                        $deltaMagnitude = $qtyDelta === null ? null : number_format(abs((float) $qtyDelta), 0);
                        $deltaSign = $qtyDelta === null || (float) $qtyDelta === 0.0
                            ? ''
                            : (in_array($history->change_type, ['stock_in', 'insert'], true) || ($history->change_type !== 'stock_out' && $qtyDelta > 0)
                                ? '+'
                                : ($history->change_type === 'stock_out' || $qtyDelta < 0 ? '−' : ''));
                        $deltaClass = $history->change_type === 'stock_out' || $qtyDelta < 0
                            ? 'text-rose-600 font-semibold'
                            : ($qtyDelta > 0
                            ? 'text-emerald-600 font-semibold'
                            : 'text-slate-400');
                    @endphp

                    <tr class="hover:bg-slate-50/40 transition-colors duration-150">
                        {{-- Date --}}
                        <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                            <span class="font-medium">{{ \Carbon\Carbon::parse($history->changed_at)->format('M d, Y') }}</span>
                            <span class="block text-[10px] font-mono text-slate-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($history->changed_at)->format('h:i A') }}
                            </span>
                        </td>
                        {{-- batch no --}}
                        <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                            <span class="font-medium">{{ $history->batch_no ?? 'Individual' }}</span>
                        </td>

                        {{-- Item --}}
                        <td class="px-5 py-3.5 text-xs font-semibold text-slate-900 tracking-tight">
                            {{ optional($history->item)->item_name }}
                        </td>

                        {{-- Warehouse --}}
                        <td class="px-5 py-3.5 text-xs text-slate-600">
                            {{ optional($history->warehouse)->warehouse_name }}
                        </td>

                        {{-- Previous Quantity --}}
                        <td class="px-4 py-3.5 text-right text-xs font-medium tabular-nums text-slate-600 whitespace-nowrap">
                            {{ $previousQty }}
                        </td>

                        {{-- Current Quantity --}}
                        <td class="px-4 py-3.5 text-right text-xs font-semibold tabular-nums text-slate-800 whitespace-nowrap">
                            {{ $currentQty }}
                        </td>

                        {{-- Quantity Variance --}}
                        <td class="px-4 py-3.5 text-right text-xs tabular-nums whitespace-nowrap {{ $deltaClass }}">
                            {{ $deltaMagnitude === null ? '—' : $deltaSign.$deltaMagnitude }}
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
                        <td colspan="10" class="py-14">
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
