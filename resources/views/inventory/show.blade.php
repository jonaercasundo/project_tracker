<x-project_app-layout>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 pb-6 border-b border-slate-100">

        <div class="flex items-center gap-4">

            <div class="h-12 w-12 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Inventory Details
                </h1>
                <p class="text-sm text-slate-500">
                    View inventory information.
                </p>
            </div>

        </div>

        <div class="flex gap-2">

            <a href="{{ route('inventory.edit', $inventory->inventory_id) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition">

                <svg class="h-4 w-4 mr-2"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M16.862 4.487a2.25 2.25 0 113.182 3.182L8.25 19.462 3 21l1.538-5.25L16.862 4.487z"/>

                </svg>

                Edit

            </a>

            <a href="{{ route('inventory.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-sm font-semibold transition">

                Back

            </a>

        </div>

    </div>

    @php
        $isApproved = $inventory->inventory_status === 'Approved';

        $badgeClasses = $isApproved
            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
            : 'bg-amber-50 text-amber-700 border border-amber-200';

        $dotClasses = $isApproved
            ? 'bg-emerald-500'
            : 'bg-amber-500';
    @endphp

    <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">

        <div class="p-8">

            <div class="flex items-center justify-between mb-8">

                <div>

                    <h2 class="text-xl font-bold text-slate-900">
                        {{ $inventory->item->item_name }}
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Item ID:
                        <span class="font-mono">
                            {{ $inventory->item->item_id }}
                        </span>
                    </p>

                </div>

                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-sm font-semibold {{ $badgeClasses }}">

                    <span class="w-2 h-2 rounded-full {{ $dotClasses }}"></span>

                    {{ $inventory->inventory_status }}

                </span>

            </div>

            <div class="grid md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Quantity
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 font-semibold">
                        {{ $inventory->qty }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Unit
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        {{ $inventory->item->unit }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Price
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        ₱{{ number_format($inventory->item->price,2) }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Supplier Price
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        ₱{{ number_format($inventory->item->supplier_price,2) }}
                    </div>
                </div>

                @if(isset($inventory->project))
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Project
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        {{ $inventory->project->project_name }}
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Created At
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        {{ $inventory->created_at?->format('F d, Y h:i A') }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Last Updated
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        {{ $inventory->updated_at?->format('F d, Y h:i A') }}
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

</x-project_app-layout>