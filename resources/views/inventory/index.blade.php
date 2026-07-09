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
                class="rounded-xl border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option>All Categories</option>
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

    {{-- EMPTY STATE --}}
    @if(empty($categories))

        <div class="text-center py-16 text-slate-400 bg-white rounded-2xl border border-slate-200 shadow-sm">

            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">

                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M20 7L12 3 4 7m16 0v10l-8 4-8-4V7m16 0-8 4m-8-4 8 4m0 10V11"/>
            </svg>

            <span class="block text-sm font-medium text-slate-600">
                No inventory items found.
            </span>

            <p class="text-xs text-slate-400 mt-1">
                Add your first inventory item to get started.
            </p>

        </div>

    @else

        <div class="space-y-5">

            @foreach($categories as $category)

                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition">

                    {{-- CATEGORY HEADER --}}
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">

                        <div>

                            <h2 class="text-sm font-bold text-slate-900">
                                {{ $category['name'] }}
                            </h2>

                            <p class="text-xs text-slate-500 mt-1">
                                {{ count($category['items']) }} Items
                            </p>

                        </div>

                        <button
                            class="px-3 py-1.5 rounded-lg border bg-white text-xs font-bold hover:bg-slate-50">
                            View Category
                        </button>

                    </div>

                    {{-- ITEMS --}}
                    <div class="divide-y divide-slate-100">

                        @foreach($category['items'] as $item)

                            <div class="px-5 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 hover:bg-slate-50">

                                <div>

                                    <div class="flex items-center gap-2">

                                        <span class="font-semibold text-slate-800">
                                            {{ $item->item_name }}
                                        </span>

                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-xs font-bold">
                                            {{ $item->sku }}
                                        </span>

                                    </div>

                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ $item->description }}
                                    </p>

                                </div>

                                <div class="flex items-center gap-6">

                                    <div class="text-center">
                                        <div class="text-xs text-slate-400">
                                            Stock
                                        </div>
                                        <div class="font-bold">
                                            {{ $item->quantity }}
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <div class="text-xs text-slate-400">
                                            Unit
                                        </div>
                                        <div class="font-bold">
                                            {{ $item->unit }}
                                        </div>
                                    </div>

                                    <div>
                                        @if($item->quantity > 20)
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                In Stock
                                            </span>
                                        @elseif($item->quantity > 0)
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                                Out of Stock
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex gap-2">

                                        <button
                                            class="px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100">
                                            Edit
                                        </button>

                                        <button
                                            class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100">
                                            View
                                        </button>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>

</x-project_app-layout>