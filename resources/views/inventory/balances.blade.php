<x-project_app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 antialiased sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 border-b border-slate-100 pb-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-white shadow-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18v15H3zM7 8h3m-3 4h3m-3 4h3m3-8h4m-4 4h4m-4 4h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Inventories</h1>
                    <p class="mt-1 text-sm text-slate-500">Stock movement and current balances by item and warehouse.</p>
                </div>
            </div>

            <a href="{{ route('operation_inventory.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Inventory List
            </a>
        </div>

        <form method="GET" action="{{ route('inventory.balances') }}" class="mt-6 rounded-xl border border-slate-200/70 bg-slate-50 p-4">
            <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="min-w-0 space-y-1">
                    <label for="inventory-balance-search" class="text-xs font-semibold text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        <input id="inventory-balance-search" type="search" name="search" value="{{ request('search') }}" placeholder="Item or warehouse..." class="h-[42px] w-full rounded-lg border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <x-searchable-project-filter
                    :projects="$projects"
                    name="project_id"
                    :selected="request('project_id', '')"
                    all-label="All Projects"
                    :truncate-selected="true"
                    id="inventory-balance-project"
                    rounded="rounded-lg" />

                <div class="min-w-0 space-y-1">
                    <label for="inventory-balance-warehouse" class="text-xs font-semibold text-slate-600">Warehouse</label>
                    <select id="inventory-balance-warehouse" name="warehouse_id" class="h-[42px] w-full rounded-lg border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->warehouse_id }}" @selected(request('warehouse_id') == $warehouse->warehouse_id)>{{ $warehouse->warehouse_name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-end justify-end gap-2 sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('inventory.balances') }}" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Reset</a>
                    <button type="submit" class="h-[42px] rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
                </div>
            </div>
        </form>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm" aria-labelledby="inventory-balance-heading">
            <h2 id="inventory-balance-heading" class="sr-only">Inventory balances by warehouse</h2>
            <div class="overflow-x-auto">
                <table class="min-w-[850px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Warehouse</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stock In Quantity</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stock Out Quantity</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stock on Hand Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($inventories as $inventory)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->item_name ?: 'Unnamed item' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $inventory->warehouse_name ?: 'Unassigned warehouse' }}</td>
                                <td class="px-5 py-4 text-right text-sm tabular-nums text-emerald-700">{{ number_format((float) $inventory->stock_in_quantity) }}</td>
                                <td class="px-5 py-4 text-right text-sm tabular-nums text-rose-700">{{ number_format((float) $inventory->stock_out_quantity) }}</td>
                                <td class="px-5 py-4 text-right text-sm font-semibold tabular-nums text-slate-900">{{ number_format((float) $inventory->stock_on_hand_quantity) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-semibold text-slate-800">No inventory balances found</p>
                                    <p class="mt-1 text-xs text-slate-500">Adjust your search or filters and try again.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($inventories->hasPages())
                <div class="border-t border-slate-100 bg-white p-4">{{ $inventories->links() }}</div>
            @endif
        </section>
    </div>
</x-project_app-layout>
