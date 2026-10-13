<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Item details</p>
                <h1 class="break-words text-2xl font-extrabold tracking-tight text-slate-900">{{ $item->item_name }}</h1>
                <p class="mt-2 break-words font-mono text-sm text-slate-500">{{ $item->item_id }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <a href="{{ route('catalog-items.index', $listQuery) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Back to items</a>
                <a href="{{ route('catalog-items.edit', ['item' => $item->id] + $listQuery) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700">Edit item</a>
            </div>
        </div>

        @include('items._alerts')

        <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm sm:grid-cols-2">
            @foreach ([
                'item_id' => 'Item ID',
                'item_name' => 'Item name',
                'code_prefix' => 'Code prefix',
                'description' => 'Description',
                'unit' => 'Unit of measurement',
                'price' => 'Unit price',
                'supplier_price' => 'Supplier price',
                'active' => 'Status',
            ] as $fieldName => $label)
                <div class="min-w-0 bg-white p-5">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                    <dd class="mt-2 break-words text-sm font-medium text-slate-900">
                        @if ($fieldName === 'active')
                            {{ $item->active ? 'Active' : 'Inactive' }}
                        @elseif (in_array($fieldName, ['price', 'supplier_price'], true))
                            {{ number_format((float) $item->{$fieldName}, 2) }}
                        @else
                            {{ $item->{$fieldName} ?: '—' }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
</x-project-app-layout>
