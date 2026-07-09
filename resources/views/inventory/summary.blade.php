<x-project_app-layout>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 antialiased font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 pb-6 border-b border-slate-100">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-slate-950 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V9m6 8V5m-11 12v-4a1 1 0 011-1h1a1 1 0 011 1v4m8 0h-4" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    Inventory Summary
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Summary of all inventory items.
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">

            {{-- Search --}}
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search item..."
                    class="w-full pl-9 h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            {{-- Warehouse --}}
            <select
                name="warehouse_id"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->warehouse_id }}"
                        {{ request('warehouse_id') == $warehouse->warehouse_id ? 'selected':'' }}>
                        {{ $warehouse->warehouse_name }}
                    </option>
                @endforeach
            </select>

            {{-- Inventory Status --}}
            <select
                name="inventory_status"
                class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="">All Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}"
                        {{ request('inventory_status') == $status ? 'selected':'' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-3 flex gap-2 justify-end">
            <a href="{{ route('inventory.summary') }}"
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

    {{-- SUMMARY TABLE --}}
    <div class="mt-6 bg-white rounded-xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/70 backdrop-blur sticky top-0 z-10 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Warehouse</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quantity</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unit</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Price</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Supplier Price</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-50 bg-white">
                @forelse($inventories as $inventory)
                    @php
                        $isApproved = $inventory->inventory_status === 'Approved';
                        $badgeClasses = $isApproved
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50'
                            : 'bg-amber-50 text-amber-700 border border-amber-200/50';
                        $dotClasses = $isApproved ? 'bg-emerald-500' : 'bg-amber-500';
                    @endphp

                    <tr class="hover:bg-slate-50/40 transition-colors duration-150">
                        {{-- Item Metadata --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5-8.25-4.5M20.25 7.5l-8.25-4.5-8.25 4.5M20.25 7.5v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9M3.75 7.5v9l8.25 4.5" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 text-xs tracking-tight truncate">
                                        {{ optional($inventory->item)->item_name ?? '—' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        ID: {{ optional($inventory->item)->item_id ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Warehouse --}}
                        <td class="px-5 py-3.5 text-xs text-slate-600">
                            {{ optional($inventory->warehouse)->warehouse_name ?? '—' }}
                        </td>

                        {{-- Quantity --}}
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-slate-800 tabular-nums">
                            {{ number_format($inventory->qty) }}
                        </td>

                        {{-- Unit --}}
                        <td class="px-5 py-3.5 text-center text-xs text-slate-500">
                            {{ optional($inventory->item)->unit ?? '—' }}
                        </td>

                        {{-- Price --}}
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-slate-900 tabular-nums">
                            ₱{{ number_format($inventory->item->price, 2) }}
                        </td>

                        {{-- Supplier Price --}}
                        <td class="px-5 py-3.5 text-right text-xs font-medium text-slate-500 tabular-nums">
                            ₱{{ number_format($inventory->item->supplier_price, 2) }}
                        </td>

                        {{-- Status Badge --}}
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md {{ $badgeClasses }} text-[11px] font-medium tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ $inventory->inventory_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-14">
                            <div class="flex flex-col items-center justify-center text-center px-4">
                                <div class="h-11 w-11 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mb-3 border border-slate-100">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0l-2 7H6l-2-7m16 0H4" />
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-800">No inventory records found</p>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-xs">Try adjusting your search or filters.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Wrapper --}}
        @if(method_exists($inventories, 'links'))
            <div class="p-3 border-t border-slate-100 bg-white">
                {{ $inventories->links() }}
            </div>
        @endif
    </div>

</div>

</x-project_app-layout>