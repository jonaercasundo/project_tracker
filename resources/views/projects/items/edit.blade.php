<div>
    <!-- We must ship. - Taylor Otwell -->
</div>
<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Project item · ID {{ $item->item_id }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Edit item</h1>
                <p class="mt-1 break-words text-sm text-slate-500">{{ $project->project_name }}</p>
            </div>
            <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'items']) }}" class="shrink-0 text-sm font-semibold text-blue-600 hover:text-blue-800">Back to Items</a>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold">Please correct the item information and try again.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.items.update', [$project, $item->item_id]) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @csrf
            @method('PUT')

            @foreach ($fields as $field)
                <div>
                    <label for="{{ $field['name'] }}" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                        {{ $field['label'] }}
                        @if ($field['name'] === 'item_name')
                            <span class="text-red-600">*</span>
                        @endif
                    </label>

                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4" maxlength="10000" @required($field['name'] === 'item_name') class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">{{ old($field['name'], $field['value']) }}</textarea>
                    @elseif (in_array($field['name'], ['price', 'supplier_price'], true))
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">&#8369;</span>
                            <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="number" value="{{ old($field['name'], $field['value']) }}" min="0" max="9999999999999.99" step="0.01" class="w-full rounded-xl border border-slate-200 py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @else
                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}" value="{{ old($field['name'], $field['value']) }}" @if ($field['name'] === 'item_name') maxlength="255" required @else maxlength="255" @endif class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    @endif

                    @error($field['name'])
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <section id="package-quantities" class="space-y-4 rounded-xl border border-slate-200 p-4 sm:p-5">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Per-package item quantities</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">These integer quantities are multiplied by each delivery's package count for delivery requirements, billing, and inventory calculations. Stored zero or blank quantities are treated as 1 by those calculations. Changes are blocked after delivery or inventory history exists.</p>
                </div>

                @forelse ($quantityPackages as $packageContent)
                    @php
                        $packageQuantityLabel = filled($packageContent->keystage_num)
                            && (int) $packageContent->keystage_project_id === (int) $project->project_id
                            && ($packageContent->lot_id === null || (int) $packageContent->lot_id === (int) $packageContent->keystage_lot_id)
                            ? 'Key Stage '.$packageContent->keystage_num
                            : 'No Key Stage assigned';
                    @endphp
                    <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[minmax(0,1fr)_12rem] sm:items-end">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $packageQuantityLabel }} · {{ $packageContent->lot_name ?: 'Lot not assigned' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Package {{ $packageContent->package_num ?? $packageContent->package_id }}</p>
                        </div>
                        <label for="package-quantity-{{ $packageContent->package_id }}" class="text-xs font-semibold text-slate-600">Quantity per package
                            <input id="package-quantity-{{ $packageContent->package_id }}" name="package_contents[{{ $packageContent->package_id }}]" type="number" min="0" max="2147483647" step="1" required value="{{ old('package_contents.'.$packageContent->package_id, $packageContent->qty ?? 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm tabular-nums text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                            @error('package_contents.'.$packageContent->package_id)
                                <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">This item is not currently included in any package for this project.</p>
                @endforelse
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'items']) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700">Save changes</button>
            </div>
        </form>
    </div>
</x-project-app-layout>
