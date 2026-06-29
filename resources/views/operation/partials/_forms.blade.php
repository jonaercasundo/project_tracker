@csrf

@if(isset($item))
    @method('PUT')
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Classification --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Classification
        </label>

        <select
            name="code_prefix"
            required
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">

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

        </select>
    </div>

    {{-- Project --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Project
        </label>

        <select
            name="project_id"
            class="w-full rounded-lg border-slate-300">

            <option value="">Select Project</option>

            @foreach($projects as $project)
                <option value="{{ $project->project_id }}"
                    @selected(old('project_id', $item->project_id ?? '') == $project->project_id)>
                    {{ $project->project_name }}
                </option>
            @endforeach

        </select>
    </div>

    {{-- Item Name --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Item Name
        </label>

        <input
            type="text"
            name="item_name"
            value="{{ old('item_name', $item->item_name ?? '') }}"
            required
            class="w-full rounded-lg border-slate-300">
    </div>

    {{-- Description --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Description
        </label>

        <textarea
            name="description"
            rows="4"
            class="w-full rounded-lg border-slate-300">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    {{-- Unit --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Unit
        </label>

        <input
            type="text"
            name="unit"
            value="{{ old('unit', $item->unit ?? '') }}"
            class="w-full rounded-lg border-slate-300">
    </div>

    {{-- Price --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Selling Price
        </label>

        <input
            type="number"
            step="0.01"
            min="0"
            name="price"
            value="{{ old('price', $item->price ?? 0) }}"
            class="w-full rounded-lg border-slate-300">
    </div>

    {{-- Supplier Price --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Supplier Price
        </label>

        <input
            type="number"
            step="0.01"
            min="0"
            name="supplier_price"
            value="{{ old('supplier_price', $item->supplier_price ?? 0) }}"
            class="w-full rounded-lg border-slate-300">
    </div>

    {{-- Active --}}
    <div class="flex items-center mt-7">

        <input
            type="checkbox"
            name="active"
            value="1"
            @checked(old('active', $item->active ?? true))
            class="rounded">

        <span class="ml-2 text-sm text-slate-700">
            Active
        </span>

    </div>

</div>

<div class="mt-8 flex justify-end gap-3">

    <a href="{{ route('items.index') }}"
       class="px-5 py-2 rounded-lg bg-slate-200 hover:bg-slate-300">
        Cancel
    </a>

    <button
        class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
        {{ isset($item) ? 'Update Item' : 'Save Item' }}
    </button>

</div>