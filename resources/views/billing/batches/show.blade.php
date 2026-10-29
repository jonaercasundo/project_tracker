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
        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $errors->first() }}</div>
        @endif
        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</p><p class="mt-1 font-semibold text-blue-800">{{ str($batch->status)->replace('_', ' ')->title() }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active DR Count</p><p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ number_format($activeDrCount) }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Created / confirmed by</p><p class="mt-1 text-sm text-slate-900">{{ $batch->confirmed_by_name ?? $batch->confirmedBy?->name ?? 'Former user' }}</p><p class="mt-1 text-xs text-slate-500">{{ $batch->confirmed_at?->format('Y-m-d H:i:s') }}</p><p class="mt-1 text-xs text-slate-500">Confirmation date: {{ $batch->confirmation_date?->toDateString() ?? 'Not recorded' }}</p></div>
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selection source</p><p class="mt-1 capitalize text-slate-900">{{ str($batch->selection_method)->replace('_', ' ') }}</p><p class="mt-1 text-xs text-slate-500">Queued for Billing review; no invoice created.</p></div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Google Sheets reconciliation</h2>
                    @if ($activeBillingRecordCount === 0)
                        <p class="mt-1 text-sm text-slate-600">Revoked package rows are excluded from sync. No active package rows remain to synchronize.</p>
                    @elseif ($activeSheetSyncs->isEmpty())
                        <p class="mt-1 text-sm text-amber-800">No reconciliation entries were recorded yet. Retry to prepare them from this batch’s confirmed delivery records.</p>
                    @elseif ($sheetSyncNeedsRetry)
                        <p class="mt-1 text-sm text-rose-700">Some DR/package rows need correction. Billing confirmations remain saved; this attempt made no Google Sheets updates.</p>
                    @else
                        <p class="mt-1 text-sm text-emerald-700">Billing batch synchronized to Google Sheets for all {{ $activeSheetSyncs->count() }} active DR/package row(s). Delivery, proof of delivery, and inventory were not changed. {{ $revokedSheetSyncCount }} revoked row(s) are excluded.</p>
                    @endif
                </div>
                @if ($sheetSyncNeedsRetry)
                    @can('billing.for-billing.mark')
                        <form method="POST" action="{{ route('billing.batches.sheet-sync.retry', $batch) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-700">Retry Google Sheets sync</button>
                        </form>
                    @endcan
                @endif
            </div>
            @if ($sheetSyncNeedsRetry && $sheetSyncs->isNotEmpty())
                <ul class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                    @foreach ($activeSheetSyncs->whereNotIn('outcome', ['synced', 'already_synced']) as $sync)
                        <li class="text-rose-800">DR {{ $sync->bir_dr ?? 'not available' }} · School ID {{ $sync->school_id ?? 'not available' }} · Package {{ $sync->package_code ?? 'not available' }} (ID {{ $sync->package_id ?? 'not available' }}) · Lot {{ $sync->lot_number ?? 'not identified' }}: {{ $sync->error_details ?? str($sync->outcome)->replace('_', ' ')->title() }}</li>
                    @endforeach
                </ul>
            @endif
            @if (! $sheetSyncNeedsRetry && $activeSheetSyncs->isNotEmpty())
                <details class="mt-4 border-t border-slate-100 pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">View synchronized cells and audit details</summary>
                    <ul class="mt-3 space-y-2 text-xs text-slate-600">
                        @foreach ($activeSheetSyncs as $sync)
                            <li>
                                DR {{ $sync->bir_dr ?? 'not available' }} · School ID {{ $sync->school_id ?? 'not available' }} · Package {{ $sync->package_code ?? 'not available' }} (ID {{ $sync->package_id ?? 'not available' }}) · Lot {{ $sync->lot_number ?? 'not identified' }}.
                                Amount written: {{ $sync->written_amount === null ? 'No billing amount was recorded' : number_format((float) $sync->written_amount, 2) }}.
                                Checked by {{ $sync->authenticated_user_name ?? 'not recorded' }} at {{ $sync->synced_at?->format('Y-m-d H:i:s') ?? 'time unavailable' }}.
                                Cells: {{ collect($sync->destination_cells ?? [])->map(fn ($cell, $field) => str($field)->replace('_', ' ')->title().' '.$sync->sheet_tab.'!'.$cell)->implode(', ') ?: 'not recorded' }}.
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-slate-900">Batch delivery receipts</h2><p class="mt-1 text-sm text-slate-500">The Billing team can continue each delivery through the existing queue and invoice workflow.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">DR Number</th><th class="px-4 py-3">Delivery Records</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Lot / School</th><th class="px-4 py-3">Delivery Date</th><th class="px-4 py-3">Delivery Status</th><th class="px-4 py-3">Billing Status</th><th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Actions</th></tr>
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
                                <td class="min-w-[18rem] px-4 py-4 text-slate-700">
                                    <ul class="space-y-3">
                                        @foreach ($deliveryRecords as $deliveryRecord)
                                            <li class="rounded-lg border border-slate-200 p-3">
                                                @if ($deliveryRecord->billing_status === 'revoked')
                                                    @php($revocation = $revocationHistories->get($deliveryRecord->billing_record_id))
                                                    <p class="font-semibold text-rose-700">Revoked</p>
                                                    <p class="mt-1 text-xs text-slate-500">By {{ $revocation?->changed_by_name ?? 'Former user' }} · {{ $revocation?->created_at ? $revocation->created_at : 'Time unavailable' }}</p>
                                                    @if ($revocation?->remarks)
                                                        <p class="mt-1 text-xs text-slate-600">{{ $revocation->remarks }}</p>
                                                    @endif
                                                @elseif ($deliveryRecord->billing_status === 'for_billing' && $deliveryRecord->invoice_id === null)
                                                    <p class="mb-2 text-xs text-slate-500">{{ $deliveryRecord->lot_name ?? 'All lots' }} · {{ $deliveryRecord->school_name ?? 'Unknown school' }} · Delivery {{ $deliveryRecord->delivery_id }}</p>
                                                    @can('billing.for-billing.mark')
                                                        <form method="POST" action="{{ route('billing.batches.records.revoke', [$batch, $deliveryRecord->billing_record_id]) }}" onsubmit="return window.confirm('Revoke this selected DR delivery and all its package rows from billing and future Google Sheets syncs?')">
                                                            @csrf
                                                            <label class="sr-only" for="revoke-reason-{{ $deliveryRecord->billing_record_id }}">Optional revocation reason</label>
                                                            <input id="revoke-reason-{{ $deliveryRecord->billing_record_id }}" name="reason" type="text" maxlength="1000" placeholder="Optional reason" class="mb-2 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs text-slate-700">
                                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Revoke DR / packages</button>
                                                        </form>
                                                    @endcan
                                                @else
                                                    <p class="text-xs text-slate-500">{{ $deliveryRecord->lot_name ?? 'All lots' }} · {{ $deliveryRecord->school_name ?? 'Unknown school' }} · {{ str($deliveryRecord->billing_status)->replace('_', ' ')->title() }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-10 text-center text-slate-500">This batch does not have linked delivery records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-project_app-layout>
