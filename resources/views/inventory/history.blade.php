<x-project_app-layout>

<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-6">

        <div>

            <h1 class="text-2xl font-bold">
                Inventory History
            </h1>

            <p class="text-sm text-slate-500">
                Track inventory changes.
            </p>

        </div>

        <a href="{{ route('inventory.index') }}"
           class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-sm">

            Back

        </a>

    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-100">

                <tr class="text-xs uppercase text-slate-600">

                    <th class="px-6 py-4 text-center">Date</th>
                    <th class="px-6 py-4 text-left">Item</th>
                    <th class="px-6 py-4 text-center">Warehouse</th>
                    <th class="px-6 py-4 text-center">Old Qty</th>
                    <th class="px-6 py-4 text-center">New Qty</th>
                    <th class="px-6 py-4 text-center">Change</th>
                    <th class="px-6 py-4 text-center">Changed By</th>
                    <th class="px-6 py-4 text-left">Remarks</th>

                </tr>

            </thead>

            <tbody>

            @forelse($histories as $history)

                <tr class="border-t hover:bg-slate-50">

                    <td class="px-6 py-4 text-center">
                        {{ \Carbon\Carbon::parse($history->changed_at)->format('M d, Y h:i A') }}
                    </td>

                    <td class="px-6 py-4">
                        {{ optional($history->item)->item_name }}
                    </td>

                    <td class="text-center">
                        {{ optional($history->warehouse)->warehouse_name }}
                    </td>

                    <td class="text-center">
                        {{ $history->old_qty }}
                    </td>

                    <td class="text-center font-semibold">
                        {{ $history->new_qty }}
                    </td>

                    <td class="text-center">

                        @if($history->change_type=='insert')

                            <span class="text-green-600 font-semibold">
                                Insert
                            </span>

                        @elseif($history->change_type=='update')

                            <span class="text-blue-600 font-semibold">
                                Update
                            </span>

                        @else

                            <span class="text-red-600 font-semibold">
                                Delete
                            </span>

                        @endif

                    </td>

                    <td class="text-center">
                        {{ $history->changed_by }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $history->remarks }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="8" class="py-12 text-center text-slate-500">
                        No inventory history found.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

</x-project_app-layout>