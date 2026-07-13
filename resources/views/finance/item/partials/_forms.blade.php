@csrf

@if(isset($item))
    @method('PUT')
@endif

<div class="max-w-4xl">

    {{-- Section: Classification & Identity --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">

        <h3 class="text-sm font-semibold text-slate-900 mb-5 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Basic Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

            {{-- Classification --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Classification <span class="text-red-500">*</span>
                </label>

                <select
                    name="code_prefix"
                    required
                    class="w-full rounded-xl border-slate-300 shadow-sm text-sm px-3.5 py-2.5
                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                           transition-colors">

                    <option value="">Select Classification</option>

                    <option value="SME"
                        @selected(old('code_prefix', $item->code_prefix ?? '') == 'SME')>
                        SME
                    </option>

                    <option value="STE"
                        @selected(old('code_prefix', $item->code_prefix ?? '') == 'STE')>
                        STE
                    </option>

                    <option value="ICT"
                        @selected(old('code_prefix', $item->code_prefix ?? '') == 'ICT')>
                        ICT
                    </option>
                    <option value="TX"
                        @selected(old('code_prefix', $item->code_prefix ?? '') == 'TX')>
                        Textbooks
                    </option>
                    <option value="TM"
                        @selected(old('code_prefix', $item->code_prefix ?? '') == 'TM')>
                        Teacher's Manual
                    </option>

                </select>
            </div>

            {{-- Unit --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Unit
                </label>

                <input
                    type="text"
                    name="unit"
                    placeholder="e.g. pcs, box, license"
                    value="{{ old('unit', $item->unit ?? '') }}"
                    class="w-full rounded-xl border-slate-300 shadow-sm text-sm px-3.5 py-2.5
                           placeholder:text-slate-400
                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                           transition-colors">
            </div>

            {{-- Item Name --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Item Name <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="item_name"
                    value="{{ old('item_name', $item->item_name ?? '') }}"
                    required
                    class="w-full rounded-xl border-slate-300 shadow-sm text-sm px-3.5 py-2.5
                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                           transition-colors">
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Optional notes about this item..."
                    class="w-full rounded-xl border-slate-300 shadow-sm text-sm px-3.5 py-2.5
                           placeholder:text-slate-400 resize-none
                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                           transition-colors">{{ old('description', $item->description ?? '') }}</textarea>
            </div>

        </div>
    </div>

    {{-- Section: Pricing --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">

        <h3 class="text-sm font-semibold text-slate-900 mb-5 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0-8c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pricing
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

            {{-- Price --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Selling Price
                </label>

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">
                        ₱
                    </span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        value="{{ old('price', $item->price ?? 0) }}"
                        class="w-full rounded-xl border-slate-300 shadow-sm text-sm pl-8 pr-3.5 py-2.5
                               focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                               transition-colors">
                </div>
            </div>

            {{-- Supplier Price --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Supplier Price
                </label>

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">
                        ₱
                    </span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="supplier_price"
                        value="{{ old('supplier_price', $item->supplier_price ?? 0) }}"
                        class="w-full rounded-xl border-slate-300 shadow-sm text-sm pl-8 pr-3.5 py-2.5
                               focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                               transition-colors">
                </div>
            </div>

        </div>
    </div>

    {{-- Section: Status --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">

        <label class="flex items-center justify-between cursor-pointer">
            <span>
                <span class="block text-sm font-medium text-slate-900">Active</span>
                <span class="block text-sm text-slate-500">Item is visible and available for use</span>
            </span>

            <span class="relative inline-flex items-center">
                <input
                    type="checkbox"
                    name="active"
                    value="1"
                    @checked(old('active', $item->active ?? true))
                    class="peer sr-only">
                <span class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-blue-600
                             transition-colors duration-200"></span>
                <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow
                             transition-transform duration-200 peer-checked:translate-x-5"></span>
            </span>
        </label>

    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3">

        <a href="{{ route('items.index') }}"
           class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 text-sm font-medium
                  hover:bg-slate-50 transition-colors">
            Cancel
        </a>

        <button
            type="submit"
            class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium
                   hover:bg-blue-700 shadow-sm shadow-blue-600/20 transition-colors
                   flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7"/>
            </svg>
            {{ isset($item) ? 'Update Item' : 'Save Item' }}
        </button>

    </div>

</div>