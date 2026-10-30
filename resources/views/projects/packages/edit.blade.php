<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-wide text-blue-600">Project package</p><h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Edit item quantities</h1><p class="mt-1 text-sm text-slate-500">{{ $project->project_name }} &middot; {{ $package->code_label }}</p></div>
            <a href="{{ route('projects.packages.show', [$project, $package->package_id]) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Back to package</a>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="font-bold">The package quantities could not be saved.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if ($quantityChangeBlockReason)
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><p class="font-bold">This package has transaction history</p><p class="mt-1">{{ $quantityChangeBlockReason }} You can proceed by entering a reason below. Existing delivery, billing, and inventory history will remain unchanged; current calculations will use the new quantity.</p></div>
        @endif

        @if ($duplicateItemIds !== [])
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This package contains duplicate item assignment rows (IDs: {{ implode(', ', $duplicateItemIds) }}). Resolve these records before changing quantities.</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-bold text-slate-900">Package details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Package</dt><dd class="mt-1 text-sm">Package {{ $package->package_num ?? $package->package_id }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Code</dt><dd class="mt-1 text-sm">{{ $package->code_label }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Lot</dt><dd class="mt-1 text-sm">{{ $package->lot_label ?: 'No Lot assigned' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Key Stage</dt><dd class="mt-1 text-sm">{{ $package->keystage_label }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Description</dt><dd class="mt-1 text-sm">{{ $package->description_label ?: 'Not provided' }}</dd></div>
            </dl>
        </section>

        <form method="POST" action="{{ route('projects.packages.update', [$project, $package->package_id]) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @csrf
            @method('PUT')
            <div><h2 class="font-bold text-slate-900">Individual item quantities</h2><p class="mt-1 text-xs leading-5 text-slate-500">Edit the quantity of each item assigned to this package. Quantities must be whole numbers from 0 to 2,147,483,647. Changes are recorded with your remarks. The current delivery, billing, and inventory calculations treat zero or blank package quantities as one.</p></div>
            <div>
                <label for="remarks" class="mb-1.5 block text-sm font-semibold text-slate-700">Reason for change @if ($quantityChangeBlockReason)<span class="text-red-600">*</span>@else <span class="text-xs font-normal text-slate-500">(optional)</span>@endif</label>
                <textarea id="remarks" name="remarks" rows="3" maxlength="1000" @required($quantityChangeBlockReason) class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500" placeholder="Explain why these package item quantities need to change.">{{ old('remarks') }}</textarea>
                @error('remarks')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
            </div>
            @forelse ($items as $item)
                <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[minmax(0,1fr)_12rem] sm:items-end">
                    <div><p class="text-sm font-semibold text-slate-800">{{ $item->item_name ?? 'Item '.$item->item_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->description ?? $item->item_description ?? 'No description' }} @if (filled($item->unit ?? null)) &middot; {{ $item->unit }} @endif</p></div>
                    <label for="item-quantity-{{ $item->item_id }}" class="text-xs font-semibold text-slate-600">Quantity per package
                        <input id="item-quantity-{{ $item->item_id }}" name="items[{{ $item->item_id }}]" type="number" min="0" max="2147483647" step="1" required value="{{ old('items.'.$item->item_id, $item->qty ?? 0) }}" @disabled(in_array((int) $item->item_id, $duplicateItemIds, true)) class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm tabular-nums text-slate-800 focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                        @error('items.'.$item->item_id)<span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
            @empty
                <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">No items are assigned to this package.</p>
            @endforelse
            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('projects.packages.show', [$project, $package->package_id]) }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" @disabled($duplicateItemIds !== [] || $items->isEmpty()) class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400">Save quantities</button>
            </div>
        </form>
    </div>
</x-project-app-layout>
