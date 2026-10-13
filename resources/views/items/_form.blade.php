<div class="grid grid-cols-1 gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-6">
    @if ($item?->exists)
        <div class="sm:col-span-2">
            <label for="item-id" class="mb-1.5 block text-xs font-bold text-slate-600">Item ID</label>
            <input id="item-id" value="{{ $item->item_id }}" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-600">
        </div>
    @else
        <p class="sm:col-span-2 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">An item ID will be generated when the item is saved. If no code prefix is entered, the default prefix ITEM is used.</p>
    @endif

    <div>
        <label for="code-prefix" class="mb-1.5 block text-xs font-bold text-slate-600">Code prefix <span class="font-medium text-slate-400">(optional)</span></label>
        <input id="code-prefix" name="code_prefix" type="text" maxlength="255" value="{{ old('code_prefix', $item?->code_prefix) }}" autocomplete="off" aria-invalid="{{ $errors->has('code_prefix') ? 'true' : 'false' }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <x-input-error :messages="$errors->get('code_prefix')" class="mt-1" />
    </div>
    <div>
        <label for="item-name" class="mb-1.5 block text-xs font-bold text-slate-600">Item name <span class="text-red-600" aria-hidden="true">*</span></label>
        <input id="item-name" name="item_name" type="text" maxlength="255" required value="{{ old('item_name', $item?->item_name) }}" aria-invalid="{{ $errors->has('item_name') ? 'true' : 'false' }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <x-input-error :messages="$errors->get('item_name')" class="mt-1" />
    </div>
    <div>
        <label for="unit" class="mb-1.5 block text-xs font-bold text-slate-600">Unit of measurement</label>
        <input id="unit" name="unit" type="text" maxlength="255" value="{{ old('unit', $item?->unit) }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <x-input-error :messages="$errors->get('unit')" class="mt-1" />
    </div>
    <div>
        <label for="active" class="mb-1.5 block text-xs font-bold text-slate-600">Status <span class="text-red-600" aria-hidden="true">*</span></label>
        <select id="active" name="active" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            <option value="1" @selected((string) old('active', $item?->active === null ? '1' : (int) $item->active) === '1')>Active</option>
            <option value="0" @selected((string) old('active', $item?->active === null ? '1' : (int) $item->active) === '0')>Inactive</option>
        </select>
        <x-input-error :messages="$errors->get('active')" class="mt-1" />
    </div>
    <div>
        <label for="price" class="mb-1.5 block text-xs font-bold text-slate-600">Unit price <span class="text-red-600" aria-hidden="true">*</span></label>
        <input id="price" name="price" type="number" min="0" max="9999999999999.99" step="0.01" required value="{{ old('price', $item?->price ?? '0.00') }}" aria-invalid="{{ $errors->has('price') ? 'true' : 'false' }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <x-input-error :messages="$errors->get('price')" class="mt-1" />
    </div>
    <div>
        <label for="supplier-price" class="mb-1.5 block text-xs font-bold text-slate-600">Supplier price <span class="text-red-600" aria-hidden="true">*</span></label>
        <input id="supplier-price" name="supplier_price" type="number" min="0" max="9999999999999.99" step="0.01" required value="{{ old('supplier_price', $item?->supplier_price ?? '0.00') }}" aria-invalid="{{ $errors->has('supplier_price') ? 'true' : 'false' }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <x-input-error :messages="$errors->get('supplier_price')" class="mt-1" />
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="mb-1.5 block text-xs font-bold text-slate-600">Description</label>
        <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('description', $item?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>
</div>
