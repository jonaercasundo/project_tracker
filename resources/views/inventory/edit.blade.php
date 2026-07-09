<x-project_app-layout>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-slate-200 pb-6">

        <div class="flex items-center gap-4">

            <div class="h-12 w-12 rounded-xl bg-amber-500 text-white flex items-center justify-center">

                <svg class="h-5 w-5"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="1.5">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M16.862 4.487a2.25 2.25 0 113.182 3.182L8.25 19.462 3 21l1.538-5.25L16.862 4.487z"/>

                </svg>

            </div>

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Edit Inventory
                </h1>

                <p class="text-sm text-slate-500">
                    Update inventory information.
                </p>

            </div>

        </div>

        <a href="{{ route('inventory.index') }}"
           class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-100">
            Back
        </a>

    </div>

    <form method="POST"
          action="{{ route('inventory.update', $inventory->inventory_id) }}"
          class="mt-8">

        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow border border-slate-200">

            <div class="p-8 space-y-6">

                <div class="grid md:grid-cols-2 gap-6">

                    {{-- Item Name --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Item
                        </label>

                        <input
                            type="text"
                            value="{{ $inventory->item->item_name }}"
                            readonly
                            class="mt-2 w-full rounded-xl border-slate-300 bg-slate-100">

                    </div>

                    {{-- Item ID --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Item ID
                        </label>

                        <input
                            type="text"
                            value="{{ $inventory->item->item_id }}"
                            readonly
                            class="mt-2 w-full rounded-xl border-slate-300 bg-slate-100">

                    </div>

                    {{-- Quantity --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Quantity
                        </label>

                        <input
                            type="number"
                            name="qty"
                            value="{{ old('qty',$inventory->qty) }}"
                            class="mt-2 w-full rounded-xl border-slate-300 focus:ring-amber-500 focus:border-amber-500">

                    </div>

                    {{-- Status --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Inventory Status
                        </label>

                        <select
                            name="inventory_status"
                            class="mt-2 w-full rounded-xl border-slate-300 focus:ring-amber-500 focus:border-amber-500">

                            <option value="For Approval"
                                {{ $inventory->inventory_status=='For Approval' ? 'selected':'' }}>
                                For Approval
                            </option>

                            <option value="Approved"
                                {{ $inventory->inventory_status=='Approved' ? 'selected':'' }}>
                                Approved
                            </option>

                        </select>

                    </div>

                    {{-- Unit --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Unit
                        </label>

                        <input
                            type="text"
                            value="{{ $inventory->item->unit }}"
                            readonly
                            class="mt-2 w-full rounded-xl border-slate-300 bg-slate-100">

                    </div>

                    {{-- Price --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Price
                        </label>

                        <input
                            type="text"
                            value="₱{{ number_format($inventory->item->price,2) }}"
                            readonly
                            class="mt-2 w-full rounded-xl border-slate-300 bg-slate-100">

                    </div>

                    {{-- Supplier Price --}}
                    <div>

                        <label class="text-sm font-semibold text-slate-600">
                            Supplier Price
                        </label>

                        <input
                            type="text"
                            value="₱{{ number_format($inventory->item->supplier_price,2) }}"
                            readonly
                            class="mt-2 w-full rounded-xl border-slate-300 bg-slate-100">

                    </div>

                </div>

            </div>

            <div class="border-t border-slate-200 px-8 py-5 flex justify-end gap-3">

                <a href="{{ route('inventory.index') }}"
                   class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="px-6 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold">

                    Save Changes

                </button>

            </div>

        </div>

    </form>

</div>

</x-project_app-layout>