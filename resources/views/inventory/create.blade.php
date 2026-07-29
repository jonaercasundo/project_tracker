<x-project_app-layout>

<div class="max-w-4xl mx-auto px-6 py-8">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Add Inventory Item
            </h1>

            <p class="text-sm text-slate-500">
                Add a new item stock record.
            </p>
        </div>


        <a href="{{ route('inventory.index') }}"
           class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-sm font-medium">
            Back
        </a>

    </div>


    {{-- FORM --}}
    <div class="bg-white rounded-2xl shadow border border-slate-200 p-6">


        <form method="POST" action="{{ route('inventory.store') }}">

            @csrf


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                {{-- ITEM --}}
                <div>

                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">
                        Item
                    </label>


                    <select
                        name="item_id"
                        required
                        class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">

                        <option value="">
                            Select Items
                        </option>


                        @foreach($items as $item)

                            <option value="{{ $item->item_id }}"
                                {{ old('item_id') == $item->item_id ? 'selected' : '' }}>

                                {{ $item->item_name }}

                            </option>

                        @endforeach

                    </select>


                    @error('item_id')
                        <p class="text-xs text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror

                </div>



                {{-- WAREHOUSE --}}
                <div>

                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">
                        Warehouse
                    </label>


                    <select
                        name="warehouse_id"
                        required
                        class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">


                        <option value="">
                            Select Warehouse
                        </option>


                        @foreach($warehouses as $warehouse)

                            <option value="{{ $warehouse->warehouse_id }}"
                                {{ old('warehouse_id') == $warehouse->warehouse_id ? 'selected' : '' }}>

                                {{ $warehouse->warehouse_name }}

                            </option>

                        @endforeach


                    </select>


                    @error('warehouse_id')
                        <p class="text-xs text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror

                </div>




                {{-- QUANTITY --}}
                <div>

                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">
                        Quantity
                    </label>


                    <input
                        type="number"
                        name="qty"
                        min="0"
                        value="{{ old('qty',0) }}"
                        required
                        class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">


                    @error('qty')
                        <p class="text-xs text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror

                </div>




                {{-- STATUS --}}
                <div>

                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">
                        Status
                    </label>


                    <select
                        name="inventory_status"
                        class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">


                        <option value="For Approval">
                            For Approval
                        </option>


                        <option value="Approved">
                            Approved
                        </option>


                    </select>

                </div>



            </div>



            {{-- REMARKS --}}
            <div class="mt-5">

                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">
                    Remarks
                </label>


                <textarea
                    name="remarks"
                    rows="3"
                    class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Optional remarks">{{ old('remarks') }}</textarea>

            </div>



            {{-- BUTTONS --}}
            <div class="flex justify-end gap-3 mt-6">


                <a href="{{ route('inventory.index') }}"
                   class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    Cancel

                </a>



                <button
                    type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500">

                    Save Inventory

                </button>


            </div>


        </form>


    </div>


</div>


</x-project_app-layout>