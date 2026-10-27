<x-project_app-layout>
    <x-slot name="header">
        <div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Confirmed · For Billing</p>
                <h1 class="mt-1 font-mono text-xl font-extrabold tracking-tight text-slate-900">{{ $batch->batch_ref }}</h1>
            </div>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-5">
        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</p><p class="mt-1 font-semibold text-blue-800">{{ str($batch->status)->replace('_', ' ')->title() }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Delivery records</p><p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ number_format($records->count()) }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Created / confirmed by</p><p class="mt-1 text-sm text-slate-900">{{ $batch->confirmed_by_name ?? $batch->confirmedBy?->name ?? 'Former user' }}</p><p class="mt-1 text-xs text-slate-500">{{ $batch->confirmed_at?->format('Y-m-d H:i:s') }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selection source</p><p class="mt-1 capitalize text-slate-900">{{ str($batch->selection_method)->replace('_', ' ') }}</p><p class="mt-1 text-xs text-slate-500">Queued for Billing review; no invoice created.</p></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-slate-900">Batch delivery receipts</h2><p class="mt-1 text-sm text-slate-500">The Billing team can continue each delivery through the existing queue and invoice workflow.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">DR Number</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Lot / School</th><th class="px-4 py-3">Delivery Date</th><th class="px-4 py-3">Delivery Status</th><th class="px-4 py-3">Billing Status</th><th class="px-4 py-3">Invoice</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recordGroups as $drRecords)
                            @foreach ($drRecords as $record)
                            <tr class="hover:bg-slate-50/70">
                                @if ($loop->first)
                                    <th scope="rowgroup" rowspan="{{ $drRecords->count() }}" class="min-w-[9rem] align-middle px-4 py-3 text-left font-mono font-semibold text-slate-900">
                                        <div class="space-y-2">
                                            <div>
                                                <p>{{ $record->dr_no }}</p>
                                                @if ($drRecords->count() > 1)
                                                    <p class="mt-1 font-sans text-xs font-medium text-slate-500">{{ $drRecords->count() }} deliveries</p>
                                                @endif
                                            </div>
                                            <div class="space-y-1 font-sans text-xs font-medium">
                                                @foreach ($drRecords as $deliveryRecord)
                                                    <a href="{{ route('billing.records.show', $deliveryRecord->delivery_id) }}" class="block text-blue-700 hover:underline">Delivery #{{ $deliveryRecord->delivery_id }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </th>
                                @endif
                                <td class="max-w-sm whitespace-normal break-words px-4 py-3 font-medium text-slate-800">{{ $record->project_name }}</td>
                                <td class="px-4 py-3"><p class="font-semibold text-slate-800">{{ $record->lot_name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $record->school_name }}</p></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $record->delivery_date }}</td>
                                <td class="px-4 py-3 capitalize text-slate-700">{{ $record->delivery_status }}</td>
                                <td class="px-4 py-3 capitalize font-semibold text-blue-800">{{ str($record->billing_status)->replace('_', ' ') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $record->invoice_no ?? '—' }}</td>
                            </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">This batch does not have linked delivery records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-project_app-layout>
