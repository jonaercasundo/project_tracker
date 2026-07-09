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
<div class="overflow-x-auto mt-6 bg-white rounded-xl shadow border border-slate-200">

<table class="min-w-full">

    <thead class="bg-slate-100">

        <tr>

            <th class="px-5 py-3 text-left">Item</th>

            <th class="px-5 py-3 text-center">Quantity</th>

            <th class="px-5 py-3 text-center">Unit</th>

            <th class="px-5 py-3 text-center">Price</th>

            <th class="px-5 py-3 text-center">Supplier Price</th>

            <th class="px-5 py-3 text-center">Status</th>

            <th class="px-5 py-3 text-center">Action</th>

        </tr>

    </thead>

    <tbody>

    @forelse($inventories as $inventory)

        @php
            $isApproved = $inventory->inventory_status === 'Approved';
        @endphp

        <tr class="border-b hover:bg-slate-50">

            <td class="px-5 py-4">
                <div class="font-semibold">{{ $inventory->item->item_name }}</div>
                <div class="text-xs text-slate-400">
                    ID: {{ $inventory->item->item_id }}
                </div>
            </td>

            <td class="text-center">
                {{ number_format($inventory->qty) }}
            </td>

            <td class="text-center">
                {{ $inventory->item->unit }}
            </td>

            <td class="text-center">
                ₱{{ number_format($inventory->item->price,2) }}
            </td>

            <td class="text-center">
                ₱{{ number_format($inventory->item->supplier_price,2) }}
            </td>

            <td class="text-center">

                @if($isApproved)
                    <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">
                        Approved
                    </span>
                @else
                    <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs">
                        For Approval
                    </span>
                @endif

            </td>

            <td class="text-center">

                <div class="flex justify-center gap-2">

                    <a href="{{ route('inventory.show',$inventory->inventory_id) }}"
                        class="text-blue-600 hover:text-blue-800">
                        View
                    </a>

                    <a href="{{ route('inventory.edit',$inventory->inventory_id) }}"
                        class="text-amber-600 hover:text-amber-800">
                        Edit
                    </a>

                </div>

            </td>

        </tr>

    @empty

        <tr>
            <td colspan="7" class="py-10 text-center text-slate-500">
                No inventory records found.
            </td>
        </tr>

    @endforelse

    </tbody>

</table>

</div>

    {{-- PAGINATION --}}
    @if(method_exists($inventories, 'links'))
        <div class="mt-6 pt-2">
            {{ $inventories->links() }}
        </div>
    @endif

</div>

</x-project_app-layout>