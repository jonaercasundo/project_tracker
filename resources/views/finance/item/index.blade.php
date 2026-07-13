<x-finance_app-layout>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-xl font-semibold text-slate-900">
                Item Master
            </h1>

            <p class="text-sm text-slate-500 mt-0.5">
               Manage your master inventory items.
            </p>
        </div>

        <a href="{{ route('items.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 text-white font-medium text-sm hover:bg-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Item
        </a>

    </div>


    {{-- Search / Filters --}}
    <form method="GET"
          class="bg-white rounded-xl border border-slate-200 p-4">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <div class="lg:col-span-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Item ID, Classification, or Item Name..."
                    class="w-full rounded-lg border-slate-200 text-sm py-2
                           placeholder:text-slate-400
                           focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
            </div>

            <div>
                <select
                    name="active"
                    class="w-full rounded-lg border-slate-200 text-sm py-2
                           focus:ring-1 focus:ring-slate-400 focus:border-slate-400">

                    <option value="">All Statuses</option>
                    <option value="1" @selected(request('active')=='1')>Active</option>
                    <option value="0" @selected(request('active')=='0')>Inactive</option>

                </select>
            </div>

        </div>

        <div class="mt-3 flex justify-end gap-2">

            <a href="{{ route('items.index') }}"
               class="px-3.5 py-1.5 rounded-lg text-slate-500 text-xs font-medium hover:bg-slate-100 transition-colors">
                Reset
            </a>

            <button
                class="px-4 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition-colors">
                Apply Filters
            </button>

        </div>

    </form>


    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>
                <tr class="border-b border-slate-200">
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Item ID</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Class</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Item Name</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Unit</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Price</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                @forelse($items as $item)

                    <tr class="hover:bg-slate-50/70 transition-colors">

                        <td class="px-5 py-3.5 font-medium text-slate-900">
                            {{ $item->item_id }}
                        </td>

                        <td class="px-5 py-3.5">
                            <span class="inline-flex px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-medium">
                                {{ $item->code_prefix }}
                            </span>
                        </td>

                        <td class="px-5 py-3.5 text-slate-700">
                            {{ $item->item_name }}
                        </td>


                        <td class="px-5 py-3.5 text-center text-slate-500">
                            {{ $item->unit ?: '—' }}
                        </td>

                        <td class="px-5 py-3.5 text-right text-slate-900 font-medium">
                            ₱{{ number_format($item->price,2) }}
                        </td>

                        <td class="px-5 py-3.5 text-center">

                            @if($item->active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-xs font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Inactive
                                </span>
                            @endif

                        </td>

                        <td class="px-5 py-3.5">

                            <div class="flex justify-center items-center gap-1">

                                <a href="{{ route('items.edit',$item->id) }}"
                                   title="Edit"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                <form
                                    action="{{ route('items.destroy',$item->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this item?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        title="Delete"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="text-center py-16">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0l-2 2H6l-2-2"/>
                            </svg>
                            <p class="text-sm text-slate-500">No items found.</p>
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- Pagination --}}
    <div class="text-sm text-slate-500">
        {{ $items->links() }}
    </div>

</div>

</x-finance_app-layout>