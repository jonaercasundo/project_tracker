<x-project_app-layout>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 antialiased font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 pb-6 border-b border-slate-100">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-slate-950 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14l-8 4m0 10l-8-4V7m8 14V11" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    Inventory
                </h1>
                <p class="text-xs text-slate-500 mt-1 max-w-md">
                    Manage inventory items, stock levels, and warehouse availability.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('inventory.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 active:bg-blue-700 transition shadow-sm shadow-blue-600/10 tracking-wide">

                    <svg class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4" />

                    </svg>

                    Add Item

            </a>

            <button
                class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 active:bg-slate-950 transition tracking-wide">
                Categories
            </button>

            <button
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition tracking-wide">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                </svg>
                Import
            </button>
        </div>
    </div>

    {{-- QUICK STATS --}}
    @php
        $totalItems = $inventories->count();
        $approvedCount = $inventories->where('inventory_status', 'Approved')->count();
        $pendingCount = $inventories->where('inventory_status', 'For Approval')->count();
        $totalValue = $inventories->sum(fn($inv) => ($inv->qty ?? 0) * ($inv->item->price ?? 0));
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
        <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)]">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Items on page</div>
            <div class="text-xl font-bold text-slate-900 mt-2 tabular-nums tracking-tight">{{ $totalItems }}</div>
        </div>

        <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)]">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Approved</div>
            <div class="text-xl font-bold text-emerald-600 mt-2 tabular-nums tracking-tight">{{ $approvedCount }}</div>
        </div>

        <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)]">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">For approval</div>
            <div class="text-xl font-bold text-amber-500 mt-2 tabular-nums tracking-tight">{{ $pendingCount }}</div>
        </div>

        <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)]">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total value</div>
            <div class="text-xl font-bold text-slate-900 mt-2 tabular-nums tracking-tight">₱{{ number_format($totalValue, 2) }}</div>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('inventory.index') }}" class="mt-8">
        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                {{-- Project --}}
                <select
                    name="project_id"
                    class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->project_id }}"
                            {{ request('project_id') == $project->project_id ? 'selected' : '' }}>
                            {{ $project->project_name }}
                        </option>
                    @endforeach
                </select>
                {{-- Search --}}
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search items..."
                        class="w-full pl-9 h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                {{-- Inventory Status --}}
                <select
                    name="inventory_status"
                    class="w-full h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Status</option>
                    <option value="For Approval" {{ request('inventory_status') == 'For Approval' ? 'selected' : '' }}>
                        For Approval
                    </option>
                    <option value="Approved" {{ request('inventory_status') == 'Approved' ? 'selected' : '' }}>
                        Approved
                    </option>
                </select>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="flex-1 h-9 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 active:bg-slate-950 transition tracking-wide">
                        Search
                    </button>
                    <a href="{{ route('inventory.index') }}"
                        class="px-4 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                        Reset
                    </a>
                </div>

            </div>
        </div>
    </form>

    {{-- INVENTORY LIST --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/60">

                {{-- TABLE HEADER --}}
                <thead class="bg-slate-50/70">
                    <tr>
                        @php
                            // Helper to generate sorting URLs (assuming standard Laravel request query sorting)
                            $sortBy = request('sort_by');
                            $sortOrder = request('sort_order', 'asc');
                            $nextOrder = $sortOrder === 'asc' ? 'desc' : 'asc';

                            function getSortUrl($column, $sortBy, $sortOrder, $nextOrder) {
                                return request()->fullUrlWithQuery([
                                    'sort_by' => $column,
                                    'sort_order' => $sortBy === $column ? $nextOrder : 'asc'
                                ]);
                            }
                        @endphp

                        {{-- Sortable Column: Item --}}
                        <th scope="col" class="px-6 py-3.5 text-left">
                            <a href="{{ getSortUrl('item_name', $sortBy, $sortOrder, $nextOrder) }}" class="group inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-900 transition-colors">
                                Item
                                <span class="text-slate-400 group-hover:text-slate-600 transition">
                                    @if($sortBy === 'item_name')
                                        <svg class="h-3 w-3 {{ $sortOrder === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- Sortable Column: Warehouse --}}
                        <th scope="col" class="px-6 py-3.5 text-left">
                            <a href="{{ getSortUrl('warehouse_name', $sortBy, $sortOrder, $nextOrder) }}" class="group inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-900 transition-colors">
                                Warehouse
                                <span class="text-slate-400 group-hover:text-slate-600 transition">
                                    @if($sortBy === 'warehouse_name')
                                        <svg class="h-3 w-3 {{ $sortOrder === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- Sortable Column: Quantity --}}
                        <th scope="col" class="px-6 py-3.5 text-right">
                            <a href="{{ getSortUrl('qty', $sortBy, $sortOrder, $nextOrder) }}" class="group inline-flex items-center justify-end w-full gap-1.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-900 transition-colors">
                                Quantity
                                <span class="text-slate-400 group-hover:text-slate-600 transition">
                                    @if($sortBy === 'qty')
                                        <svg class="h-3 w-3 {{ $sortOrder === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                    @endif
                                </span>
                            </a>
                        </th>

                        <th scope="col" class="px-6 py-3.5 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Unit</th>

                        {{-- Sortable Column: Price --}}
                        <th scope="col" class="px-6 py-3.5 text-right">
                            <a href="{{ getSortUrl('price', $sortBy, $sortOrder, $nextOrder) }}" class="group inline-flex items-center justify-end w-full gap-1.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-900 transition-colors">
                                Price
                                <span class="text-slate-400 group-hover:text-slate-600 transition">
                                    @if($sortBy === 'price')
                                        <svg class="h-3 w-3 {{ $sortOrder === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- Sortable Column: Supplier Price --}}
                        <th scope="col" class="px-6 py-3.5 text-right">
                            <a href="{{ getSortUrl('supplier_price', $sortBy, $sortOrder, $nextOrder) }}" class="group inline-flex items-center justify-end w-full gap-1.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-900 transition-colors">
                                Supplier Price
                                <span class="text-slate-400 group-hover:text-slate-600 transition">
                                    @if($sortBy === 'supplier_price')
                                        <svg class="h-3 w-3 {{ $sortOrder === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                    @endif
                                </span>
                            </a>
                        </th>

                        <th scope="col" class="px-6 py-3.5 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-6 py-3.5">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>

                {{-- TABLE BODY --}}
                <tbody class="divide-y divide-slate-100 bg-white">

                @forelse($inventories as $inventory)

                    @php
                        $isApproved = $inventory->inventory_status === 'Approved';
                        $badgeClasses = $isApproved
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'
                            : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20';
                        $dotClasses = $isApproved ? 'bg-emerald-500' : 'bg-amber-500';
                    @endphp

                    <tr class="group hover:bg-slate-50/50 transition-colors duration-150">

                        {{-- Item Column --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center group-hover:bg-white transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5-8.25-4.5M20.25 7.5l-8.25-4.5-8.25 4.5M20.25 7.5v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9M3.75 7.5v9l8.25 4.5" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex flex-col">
                                    <span class="font-medium text-slate-900 text-sm truncate">{{ $inventory->item->item_name }}</span>
                                    <span class="text-xs text-slate-400 font-mono mt-0.5">{{ $inventory->item->item_id }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Warehouse Column --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-sm text-slate-700 font-medium">
                                    {{ optional($inventory->warehouse)->warehouse_name ?? '—' }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">
                                    {{ optional($inventory->warehouse)->warehouse_id }}
                                </span>
                            </div>
                        </td>

                        {{-- Qty Column --}}
                        <td class="px-6 py-4 text-right whitespace-nowrap text-sm font-semibold text-slate-900 tabular-nums">
                            {{ number_format($inventory->qty) }}
                        </td>

                        {{-- Unit Column --}}
                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-slate-500 font-medium">
                            {{ $inventory->item->unit }}
                        </td>

                        {{-- Price Column --}}
                        <td class="px-6 py-4 text-right whitespace-nowrap text-sm font-medium text-slate-700 tabular-nums">
                            ₱{{ number_format($inventory->item->price, 2) }}
                        </td>

                        {{-- Supplier Price Column --}}
                        <td class="px-6 py-4 text-right whitespace-nowrap text-sm text-slate-500 tabular-nums">
                            ₱{{ number_format($inventory->item->supplier_price, 2) }}
                        </td>

                        {{-- Status Column --}}
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md {{ $badgeClasses }} text-xs font-semibold tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ $inventory->inventory_status }}
                            </span>
                        </td>

                        {{-- Actions Column --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex justify-end items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                <a href="{{ route('inventory.show', $inventory->inventory_id) }}"
                                    title="View details"
                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 border border-transparent hover:border-slate-200 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>

                                <a href="{{ route('inventory.edit', $inventory->inventory_id) }}"
                                    title="Edit record"
                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-amber-700 hover:bg-amber-50 border border-transparent hover:border-amber-200 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v4.5a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V8.25A2.25 2.25 0 016 6h4.5" />
                                    </svg>
                                </a>
                            </div>
                        </td>

                    </tr>

                @empty

                    {{-- Empty State Row --}}
                    <tr>
                        <td colspan="8" class="py-24">
                            <div class="flex flex-col items-center justify-center text-center max-w-sm mx-auto">
                                <div class="h-12 w-12 rounded-xl bg-slate-50 border border-slate-200/60 text-slate-400 flex items-center justify-center mb-4 shadow-sm">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0l-2 7H6l-2-7m16 0H4" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900">No inventory records found</h3>
                                <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Try adjusting your search filters or add a new record to get started.</p>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>
        </div>
    </div>

    {{-- PAGINATION --}}
    @if(method_exists($inventories, 'links'))
        <div class="mt-6 pt-2">
            {{ $inventories->links() }}
        </div>
    @endif

</div>

</x-project_app-layout>