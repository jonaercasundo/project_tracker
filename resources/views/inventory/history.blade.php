<x-project_app-layout>

<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Inventory History
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Track inventory changes.
                </p>
            </div>
        </div>

        <a href="{{ route('inventory.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-xs font-bold text-slate-700 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>

    </div>

    {{-- FILTERS --}}
    <form method="GET" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4">

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">

            <div class="relative md:col-span-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search item, user, remarks..."
                    class="w-full pl-9 rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <select
                name="change_type"
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

                <option value="">
                    All Changes
                </option>

                <option value="insert"
                    {{ request('change_type')=='insert'?'selected':'' }}>
                    Insert
                </option>

                <option value="update"
                    {{ request('change_type')=='update'?'selected':'' }}>
                    Update
                </option>

                <option value="delete"
                    {{ request('change_type')=='delete'?'selected':'' }}>
                    Delete
                </option>

            </select>

            <select
                name="warehouse_id"
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

                <option value="">
                    All Warehouses
                </option>

                @foreach($warehouses as $warehouse)

                    <option value="{{ $warehouse->warehouse_id }}"
                        {{ request('warehouse_id')==$warehouse->warehouse_id?'selected':'' }}>

                        {{ $warehouse->warehouse_name }}

                    </option>

                @endforeach

            </select>

            <input
                type="date"
                name="date_from"
                value="{{ request('date_from') }}"
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

            <input
                type="date"
                name="date_to"
                value="{{ request('date_to') }}"
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

        </div>

        <div class="mt-3 flex gap-2">

            <button
                type="submit"
                class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
                Filter
            </button>

            <a href="{{ route('inventory.history') }}"
               class="px-5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">
                Reset
            </a>

        </div>

    </form>

    {{-- HISTORY TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">

                <thead class="bg-slate-50/80 backdrop-blur sticky top-0 z-10">

                    <tr>

                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Item</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Warehouse</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Qty Change</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Change</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Changed By</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Remarks</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                @forelse($histories as $history)

                    @php
                        $changeStyles = match($history->change_type) {
                            'insert' => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', 'bg-emerald-500', 'Insert'],
                            'update' => ['bg-blue-50 text-blue-700 ring-1 ring-blue-600/20', 'bg-blue-500', 'Update'],
                            default  => ['bg-red-50 text-red-700 ring-1 ring-red-600/20', 'bg-red-500', 'Delete'],
                        };
                        $qtyDelta = (int) $history->new_qty - (int) $history->old_qty;
                        $deltaClass = $qtyDelta > 0 ? 'text-emerald-600' : ($qtyDelta < 0 ? 'text-red-600' : 'text-slate-400');
                        $deltaSign = $qtyDelta > 0 ? '+' : '';
                    @endphp

                    <tr class="hover:bg-slate-50/70 transition-colors">

                        <td class="px-5 py-4 text-sm text-slate-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($history->changed_at)->format('M d, Y') }}
                            <span class="block text-xs text-slate-400">
                                {{ \Carbon\Carbon::parse($history->changed_at)->format('h:i A') }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">
                            {{ optional($history->item)->item_name }}
                        </td>

                        <td class="px-5 py-4 text-sm text-slate-600">
                            {{ optional($history->warehouse)->warehouse_name }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2 text-sm tabular-nums">
                                <span class="text-slate-400">{{ $history->old_qty }}</span>
                                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <span class="font-bold text-slate-900">{{ $history->new_qty }}</span>
                                <span class="font-semibold {{ $deltaClass }} ml-1">
                                    ({{ $deltaSign }}{{ $qtyDelta }})
                                </span>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $changeStyles[0] }} text-xs font-bold whitespace-nowrap">
                                <span class="h-1.5 w-1.5 rounded-full {{ $changeStyles[1] }}"></span>
                                {{ $changeStyles[2] }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-sm text-slate-600 whitespace-nowrap">
                            {{ $history->changed_by }}
                        </td>

                        <td class="px-5 py-4 text-sm text-slate-500 max-w-xs truncate" title="{{ $history->remarks }}">
                            {{ $history->remarks ?: '—' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="py-16">
                            <div class="flex flex-col items-center justify-center text-center">
                                <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No inventory history found</p>
                                <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or date range.</p>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $histories->links() }}
        </div>
    </div>

</div>

</x-project_app-layout>