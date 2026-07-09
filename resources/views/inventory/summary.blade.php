<x-project_app-layout>

<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Inventory Summary
            </h1>
            <p class="text-sm text-slate-500">
                Summary of all inventory items.
            </p>
        </div>

        <a href="{{ route('inventory.index') }}"
           class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-sm font-medium">
            Back
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-100">

                <tr class="text-xs uppercase tracking-wider text-slate-600">

                    <th class="px-6 py-4 text-left">Item</th>
                    <th class="px-6 py-4 text-center">Warehouse</th>
                    <th class="px-6 py-4 text-center">Quantity</th>
                    <th class="px-6 py-4 text-center">Unit</th>
                    <th class="px-6 py-4 text-center">Price</th>
                    <th class="px-6 py-4 text-center">Supplier Price</th>
                    <th class="px-6 py-4 text-center">Status</th>

                </tr>

            </thead>

            <tbody>

            @forelse($inventories as $inventory)

                <tr class="border-t hover:bg-slate-50">

                    <td class="px-6 py-4">

                        <div class="font-semibold">
                            {{ $inventory->item->item_name }}
                        </div>

                        <div class="text-xs text-slate-400">
                            ID: {{ $inventory->item->item_id }}
                        </div>

                    </td>

                    <td class="text-center">
                        {{ optional($inventory->warehouse)->warehouse_name ?? '-' }}
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

                        @if($inventory->inventory_status=='Approved')

                            <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">
                                Approved
                            </span>

                        @else

                            <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs">
                                {{ $inventory->inventory_status }}
                            </span>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7" class="py-12 text-center text-slate-500">
                        No inventory records found.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

</x-project_app-layout>