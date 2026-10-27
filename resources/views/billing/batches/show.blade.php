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
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">DR Count</p><p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ number_format($recordGroups->count()) }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Created / confirmed by</p><p class="mt-1 text-sm text-slate-900">{{ $batch->confirmed_by_name ?? $batch->confirmedBy?->name ?? 'Former user' }}</p><p class="mt-1 text-xs text-slate-500">{{ $batch->confirmed_at?->format('Y-m-d H:i:s') }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selection source</p><p class="mt-1 capitalize text-slate-900">{{ str($batch->selection_method)->replace('_', ' ') }}</p><p class="mt-1 text-xs text-slate-500">Queued for Billing review; no invoice created.</p></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-slate-900">Batch delivery receipts</h2><p class="mt-1 text-sm text-slate-500">The Billing team can continue each delivery through the existing queue and invoice workflow.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">DR Number</th><th class="px-4 py-3">Delivery Records</th><th class="px-4 py-3">Billing Status</th><th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Details</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recordGroups as $drRecords)
                            @php
                                $billingStatuses = $drRecords->pluck('billing_status')->unique();
                                $invoiceNumbers = $drRecords->pluck('invoice_no')->filter()->unique();
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <th scope="row" class="whitespace-nowrap px-4 py-4 font-mono font-semibold text-slate-900">{{ $drRecords->first()->dr_no ?: '—' }}</th>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ number_format($drRecords->count()) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold capitalize text-blue-800">
                                    {{ $billingStatuses->count() === 1 ? str($billingStatuses->first())->replace('_', ' ') : 'Mixed' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    @if ($invoiceNumbers->isEmpty())
                                        —
                                    @elseif ($invoiceNumbers->count() === 1)
                                        {{ $invoiceNumbers->first() }}
                                    @else
                                        {{ $invoiceNumbers->count() }} invoices
                                    @endif
                                </td>
                                <td class="min-w-[16rem] px-4 py-4">
                                    <details>
                                        <summary class="w-fit cursor-pointer list-none rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700 hover:border-blue-400 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">View details</summary>
                                        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                                            <table class="min-w-[64rem] text-left text-xs">
                                                <thead class="bg-slate-50 uppercase tracking-wide text-slate-500">
                                                    <tr><th class="px-3 py-2">Delivery record ID</th><th class="px-3 py-2">Project</th><th class="px-3 py-2">Lot / School</th><th class="px-3 py-2">Delivery date</th><th class="px-3 py-2">Delivery status</th><th class="px-3 py-2">Billing status</th><th class="px-3 py-2">Invoice</th></tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($drRecords as $record)
                                                        <tr>
                                                            <td class="whitespace-nowrap px-3 py-2 font-mono font-semibold"><a href="{{ route('billing.records.show', $record->delivery_id) }}" class="text-blue-700 hover:underline">#{{ $record->delivery_id }}</a></td>
                                                            <td class="max-w-sm whitespace-normal break-words px-3 py-2 font-medium text-slate-800">{{ $record->project_name }}</td>
                                                            <td class="px-3 py-2"><p class="font-semibold text-slate-800">{{ $record->lot_name ?? '—' }}</p><p class="mt-1 text-slate-500">{{ $record->school_name }}</p></td>
                                                            <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $record->delivery_date }}</td>
                                                            <td class="whitespace-nowrap px-3 py-2 capitalize text-slate-700">{{ $record->delivery_status }}</td>
                                                            <td class="whitespace-nowrap px-3 py-2 capitalize font-semibold text-blue-800">{{ str($record->billing_status)->replace('_', ' ') }}</td>
                                                            <td class="whitespace-nowrap px-3 py-2 text-slate-700">{{ $record->invoice_no ?? '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">This batch does not have linked delivery records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-project_app-layout>
