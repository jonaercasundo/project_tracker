<x-project_app-layout>

<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14l-8 4m0 10l-8-4V7m8 14V11" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Inventory
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Manage inventory items, stock levels, and warehouse availability.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button
                class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-600/20 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Item
            </button>

            <button
                class="px-4 py-2.5 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 active:bg-slate-950 transition">
                Categories
            </button>

            <button
                class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Items on page</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $totalItems }}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Approved</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">{{ $approvedCount }}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">For approval</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-1 tabular-nums">{{ $pendingCount }}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total value</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">₱{{ number_format($totalValue, 2) }}</div>
        </div>

    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('inventory.index') }}">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">

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
                        class="w-full pl-9 rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Project --}}
                <select
                    name="project_id"
                    class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

                    <option value="">All Projects</option>

                    @foreach($projects as $project)
                        <option value="{{ $project->project_id }}"
                            {{ request('project_id') == $project->project_id ? 'selected' : '' }}>
                            {{ $project->project_name }}
                        </option>
                    @endforeach

                </select>

                {{-- Inventory Status --}}
                <select
                    name="inventory_status"
                    class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

                    <option value="">All Status</option>

                    <option value="For Approval"
                        {{ request('inventory_status') == 'For Approval' ? 'selected' : '' }}>
                        For Approval
                    </option>

                    <option value="Approved"
                        {{ request('inventory_status') == 'Approved' ? 'selected' : '' }}>
                        Approved
                    </option>

                </select>

                {{-- Buttons --}}
                <div class="flex gap-2">

                    <button
                        type="submit"
                        class="flex-1 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition py-2.5">
                        Search
                    </button>

                    <a href="{{ route('inventory.index') }}"
                        class="px-4 flex items-center justify-center rounded-xl border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">
                        Reset
                    </a>

                </div>

            </div>

        </div>
    </form>

    {{-- INVENTORY LIST --}}
    @forelse($inventories as $inventory)

        @php
            $isApproved = $inventory->inventory_status === 'Approved';
            $badgeClasses = $isApproved
                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
            $dotClasses = $isApproved ? 'bg-emerald-500' : 'bg-amber-500';
        @endphp

        <div class="group bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md hover:border-slate-300 transition p-5">

            <div class="flex items-start justify-between gap-4">

                <div class="flex items-start gap-3 min-w-0">
                    <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5-8.25-4.5M20.25 7.5l-8.25-4.5-8.25 4.5M20.25 7.5v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9M3.75 7.5v9l8.25 4.5" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 truncate">
                            {{ $inventory->item->item_name }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Item #{{ $inventory->item->item_id }}
                        </p>
                    </div>
                </div>

                <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $badgeClasses }} text-xs font-bold whitespace-nowrap">
                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                    {{ $inventory->inventory_status }}
                </span>

            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100 text-sm">

                <div>
                    <div class="text-slate-400 text-xs font-medium">Quantity</div>
                    <div class="font-bold text-slate-900 mt-0.5 tabular-nums">{{ $inventory->qty }}</div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs font-medium">Unit</div>
                    <div class="font-bold text-slate-900 mt-0.5">{{ $inventory->item->unit }}</div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs font-medium">Price</div>
                    <div class="font-bold text-slate-900 mt-0.5 tabular-nums">
                        ₱{{ number_format($inventory->item->price, 2) }}
                    </div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs font-medium">Supplier Price</div>
                    <div class="font-bold text-slate-900 mt-0.5 tabular-nums">
                        ₱{{ number_format($inventory->item->supplier_price, 2) }}
                    </div>
                </div>

            </div>

        </div>

    @empty

        <div class="bg-white border border-dashed border-slate-300 rounded-2xl py-16 flex flex-col items-center justify-center text-center">
            <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0l-2 7H6l-2-7m16 0H4" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">No inventory records found</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or filters, or add a new item.</p>
        </div>

    @endforelse

    {{-- PAGINATION --}}
    @if(method_exists($inventories, 'links'))
        <div class="pt-2">
            {{ $inventories->links() }}
        </div>
    @endif

</div>

</x-project_app-layout>