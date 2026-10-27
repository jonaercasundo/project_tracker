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
                        <tr><th class="px-4 py-3">DR Number</th><th class="px-4 py-3">Delivery Records</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Lot / School</th><th class="px-4 py-3">Delivery Date</th><th class="px-4 py-3">Delivery Status</th><th class="px-4 py-3">Billing Status</th><th class="px-4 py-3">Invoice</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recordGroups as $drRecords)
                            @php
                                $deliveryRecords = $drRecords->unique('delivery_id')->values();
                                $projects = $drRecords->pluck('project_name')->filter()->unique()->values();
                                $lotSchools = $drRecords
                                    ->map(fn (object $record): string => collect([$record->lot_name ?? 'All lots', $record->school_name])->filter()->implode(' · '))
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $deliveryDates = $drRecords->pluck('delivery_date')->filter()->unique()->values();
                                $deliveryStatuses = $drRecords->pluck('delivery_status')->filter()->unique()->values();
                                $billingStatuses = $drRecords->pluck('billing_status')->filter()->unique()->values();
                                $invoiceNumbers = $drRecords->pluck('invoice_no')->filter()->unique()->values();
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <th scope="row" class="whitespace-nowrap px-4 py-4 font-mono font-semibold text-slate-900">
                                    <p>{{ $drRecords->first()->dr_no ?? '—' }}</p>
                                    <div class="mt-2 space-y-1 font-sans text-xs font-medium">
                                        @foreach ($deliveryRecords as $record)
                                            <a href="{{ route('billing.records.show', $record->delivery_id) }}" class="block text-blue-700 hover:underline">Delivery #{{ $record->delivery_id }}</a>
                                        @endforeach
                                    </div>
                                </th>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">{{ number_format($deliveryRecords->count()) }}</td>
                                <td class="min-w-[16rem] px-4 py-4 font-medium text-slate-800">
                                    <ul class="space-y-1">
                                        @forelse ($projects as $project)
                                            <li>{{ $project }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="min-w-[12rem] px-4 py-4 text-slate-700">
                                    <ul class="space-y-1">
                                        @forelse ($lotSchools as $lotSchool)
                                            <li>{{ $lotSchool }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                    <ul class="space-y-1">
                                        @forelse ($deliveryDates as $deliveryDate)
                                            <li>{{ $deliveryDate }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 capitalize text-slate-700">
                                    <ul class="space-y-1">
                                        @forelse ($deliveryStatuses as $deliveryStatus)
                                            <li>{{ str($deliveryStatus)->replace('_', ' ')->title() }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold capitalize text-blue-800">
                                    <ul class="space-y-1">
                                        @forelse ($billingStatuses as $billingStatus)
                                            <li>{{ str($billingStatus)->replace('_', ' ')->title() }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    <ul class="space-y-1">
                                        @forelse ($invoiceNumbers as $invoiceNumber)
                                            <li>{{ $invoiceNumber }}</li>
                                        @empty
                                            <li>—</li>
                                        @endforelse
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">This batch does not have linked delivery records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-project_app-layout>
