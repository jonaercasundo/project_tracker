<x-project-app-layout>
    <div class="min-w-0 space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Items Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage standalone item records.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('catalog-items.import', $filters) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-900/10 transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" /></svg>
                    Import Items
                </a>
                <a href="{{ route('catalog-items.create', $filters) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    Add New Item
                </a>
            </div>
        </div>

        @include('items._alerts')

        <form action="{{ route('catalog-items.index') }}" method="GET" x-data="{ submitting: false }" x-on:submit="submitting = true" :aria-busy="submitting" class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-5">
            <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
                <div>
                    <label for="item-search" class="sr-only">Search items</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                        <input id="item-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="Search by item ID, name, code, description, or unit" class="w-full rounded-2xl border-0 bg-slate-100/80 py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 ring-1 ring-transparent transition focus:bg-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label for="item-active" class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                    <select id="item-active" name="active" class="w-full rounded-xl border-0 bg-white py-2.5 text-sm text-slate-800 shadow-sm ring-1 ring-slate-900/10 focus:ring-2 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="1" @selected(($filters['active'] ?? '') === '1')>Active</option>
                        <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="submitting" class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!submitting">Apply filters</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                    <a href="{{ route('catalog-items.index') }}" class="rounded-xl px-3 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Reset</a>
                </div>
            </div>
        </form>

        <section class="min-w-0 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5" aria-labelledby="item-list-title">
            <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-5">
                <h2 id="item-list-title" class="flex items-center gap-2 text-base font-bold text-slate-900">
                    Item directory
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">{{ number_format($items->total()) }}</span>
                </h2>
                @if ($items->count())
                    <p class="text-xs text-slate-500">Showing {{ number_format($items->firstItem()) }}–{{ number_format($items->lastItem()) }} of {{ number_format($items->total()) }}</p>
                @endif
            </div>

            <div class="hidden overflow-x-auto border-t border-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 md:block" tabindex="0" role="region" aria-label="Items table">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-semibold text-slate-500">
                        <tr>
                            @foreach (['item_id' => 'Item ID', 'item_name' => 'Item', 'unit' => 'Unit', 'price' => 'Unit price', 'supplier_price' => 'Supplier price', 'active' => 'Status'] as $column => $label)
                                @php($nextDirection = ($filters['sort'] ?? 'item_name') === $column && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc')
                                <th scope="col" class="whitespace-nowrap px-4 py-3.5">
                                    <a href="{{ route('catalog-items.index', array_merge($filters, ['sort' => $column, 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1.5 hover:text-blue-700">
                                        {{ $label }}
                                        <span aria-hidden="true">{{ ($filters['sort'] ?? 'item_name') === $column ? (($filters['direction'] ?? 'asc') === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                    </a>
                                </th>
                            @endforeach
                            <th scope="col" class="whitespace-nowrap px-6 py-3.5 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @forelse ($items as $item)
                            <tr class="group transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-500">{{ $item->item_id }}</td>
                                <th scope="row" class="min-w-[260px] px-4 py-4 text-left font-normal">
                                    <div class="font-semibold text-slate-900">{{ $item->item_name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $item->code_prefix }}@if ($item->description) <span aria-hidden="true">·</span> {{ \Illuminate\Support\Str::limit($item->description, 90) }} @endif</div>
                                </th>
                                <td class="whitespace-nowrap px-4 py-4">{{ $item->unit ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 tabular-nums">{{ number_format((float) $item->price, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 tabular-nums">{{ number_format((float) $item->supplier_price, 2) }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $item->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('catalog-items.show', ['item' => $item->id] + $filters) }}" aria-label="View {{ $item->item_name }}" title="View" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 ring-1 ring-slate-900/10 transition hover:bg-white hover:text-slate-900">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                        </a>
                                        <a href="{{ route('catalog-items.edit', ['item' => $item->id] + $filters) }}" aria-label="Edit {{ $item->item_name }}" title="Edit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9a2.8 2.8 0 00-4-4L4 16v4z" /></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-16 text-center"><p class="text-sm font-semibold text-slate-700">No items found.</p><p class="mt-1 text-xs text-slate-500">Try a different search or status filter, or add a new item.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 border-t border-slate-100 bg-slate-50/60 p-3 md:hidden">
                @forelse ($items as $item)
                    <article class="space-y-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold leading-snug text-slate-900">{{ $item->item_name }}</h3>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $item->item_id }} · {{ $item->code_prefix }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $item->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p class="text-sm text-slate-600">{{ $item->description ?: 'No description' }}</p>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                            <div><dt class="font-semibold text-slate-500">Unit</dt><dd class="mt-0.5 text-slate-700">{{ $item->unit ?: '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Unit price</dt><dd class="mt-0.5 tabular-nums text-slate-700">{{ number_format((float) $item->price, 2) }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Supplier price</dt><dd class="mt-0.5 tabular-nums text-slate-700">{{ number_format((float) $item->supplier_price, 2) }}</dd></div>
                        </dl>
                        <div class="flex gap-2">
                            <a href="{{ route('catalog-items.show', ['item' => $item->id] + $filters) }}" class="flex-1 rounded-xl py-2.5 text-center text-xs font-bold text-slate-700 ring-1 ring-slate-900/10 transition hover:bg-slate-50">View</a>
                            <a href="{{ route('catalog-items.edit', ['item' => $item->id] + $filters) }}" class="flex-1 rounded-xl bg-blue-600 py-2.5 text-center text-xs font-bold text-white transition hover:bg-blue-700">Edit</a>
                        </div>
                    </article>
                @empty
                    <div class="px-3 py-12 text-center"><p class="text-sm font-semibold text-slate-700">No items found.</p><p class="mt-1 text-xs text-slate-500">Try a different search or status filter, or add a new item.</p></div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $items->links() }}</div>
            @endif
        </section>
    </div>
</x-project-app-layout>
