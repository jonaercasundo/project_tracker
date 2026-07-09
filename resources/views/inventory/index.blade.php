<x-project_app-layout>

<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Inventory
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Manage inventory items, stock levels, and warehouse availability.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button
                class="px-4 py-2 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Add Item
            </button>

            <button
                class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition">
                Categories
            </button>

            <button
                class="px-4 py-2 text-xs font-bold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                Import
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input
                type="text"
                placeholder="Search item..."
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

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

            <select
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option>All Status</option>
                <option>In Stock</option>
                <option>Low Stock</option>
                <option>Out of Stock</option>
            </select>

            <button
                class="rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                Search
            </button>

        </div>
    </div>

    @forelse($inventories as $inventory)

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">

            <div class="flex items-center justify-between">

                <div>

                    <h3 class="text-sm font-bold text-slate-900">
                        {{ $inventory->item->item_name }}
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Item #{{ $inventory->item->item_id }}
                    </p>

                </div>

                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                    {{ $inventory->inventory_status }}
                </span>

            </div>

            <div class="grid grid-cols-4 gap-4 mt-5 text-sm">

                <div>
                    <div class="text-slate-400 text-xs">Quantity</div>
                    <div class="font-bold">{{ $inventory->qty }}</div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs">Unit</div>
                    <div class="font-bold">{{ $inventory->item->unit }}</div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs">Price</div>
                    <div class="font-bold">
                        ₱{{ number_format($inventory->item->price,2) }}
                    </div>
                </div>

                <div>
                    <div class="text-slate-400 text-xs">Supplier Price</div>
                    <div class="font-bold">
                        ₱{{ number_format($inventory->item->supplier_price,2) }}
                    </div>
                </div>

            </div>

        </div>

        @empty

        <div class="text-center py-10 text-slate-500">
            No inventory records found.
        </div>

        @endforelse

</div>

</x-project_app-layout>