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
            <button
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 active:bg-blue-700 transition shadow-sm shadow-blue-600/10 tracking-wide">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Item
            </button>

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
    <div class="mt-6 space-y-3">
        @forelse($inventories as $inventory)

            @php
                $isApproved = $inventory->inventory_status === 'Approved';
                $badgeClasses = $isApproved
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50'
                    : 'bg-amber-50 text-amber-700 border border-amber-200/50';
                $dotClasses = $isApproved ? 'bg-emerald-500' : 'bg-amber-500';
            @endphp

            <div class="bg-white border border-slate-100 rounded-xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_16px_-4px_rgba(0,0,0,0.08)] hover:border-slate-200/80 transition-all duration-200 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-10 w-10 shrink-0 rounded-lg bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5-8.25-4.5M20.25 7.5l-8.25-4.5-8.25 4.5M20.25 7.5v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9M3.75 7.5v9l8.25 4.5" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900 truncate tracking-tight">
                                {{ $inventory->item->item_name }}
                            </h3>
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">
                                ID: {{ $inventory->item->item_id }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md {{ $badgeClasses }} text-[11px] font-medium tracking-wide whitespace-nowrap">
                            <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                            {{ $inventory->inventory_status }}
                        </span>
                    </div>

                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3 mt-4 pt-4 border-t border-slate-50 text-xs">
                    <div>
                        <div class="text-slate-400 font-medium">Quantity</div>
                        <div class="font-semibold text-slate-800 mt-0.5 tabular-nums text-sm">{{ $inventory->qty }}</div>
                    </div>

                    <div>
                        <div class="text-slate-400 font-medium">Unit</div>
                        <div class="font-semibold text-slate-800 mt-0.5 text-sm">{{ $inventory->item->unit }}</div>
                    </div>

                    <div>
                        <div class="text-slate-400 font-medium">Price</div>
                        <div class="font-semibold text-slate-900 mt-0.5 tabular-nums text-sm">
                            ₱{{ number_format($inventory->item->price, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-400 font-medium">Supplier Price</div>
                        <div class="font-semibold text-slate-500 mt-0.5 tabular-nums text-sm">
                            ₱{{ number_format($inventory->item->supplier_price, 2) }}
                        </div>
                    </div>
                </div>
            </div>

        @empty

            <div class="bg-white border border-dashed border-slate-200 rounded-xl py-14 flex flex-col items-center justify-center text-center px-4">
                <div class="h-11 w-11 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mb-3 border border-slate-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0l-2 7H6l-2-7m16 0H4" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-800">No inventory records found</p>
                <p class="text-[11px] text-slate-400 mt-1 max-w-xs">Try adjusting your search or filters, or add a new item.</p>
            </div>

        @endforelse
    </div>

    {{-- PAGINATION --}}
    @if(method_exists($inventories, 'links'))
        <div class="mt-6 pt-2">
            {{ $inventories->links() }}
        </div>
    @endif

</div>

</x-project_app-layout>