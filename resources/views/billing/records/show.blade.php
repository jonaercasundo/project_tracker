<x-project_app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Delivery billing record</p>
                <h1 class="mt-1 font-mono text-xl font-extrabold tracking-tight text-slate-900">DR {{ $record->dr_no }}</h1>
            </div>
            <div class="flex gap-2">
                @if ($record->billing_batch_id)
                    <a href="{{ route('billing.batches.show', $record->billing_batch_id) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-500">Batch {{ $record->batch_ref }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5">
        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif
        @php
            $billingStatusLabel = match ($record->billing_status) {
                'for_billing' => 'For Billing',
                'billed' => 'Billed',
                'on_hold' => 'On Hold',
                default => 'Pending Billing',
            };
        @endphp
        <section class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div class="space-y-4">
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Project</p><p class="mt-1 font-semibold text-slate-900">{{ $record->project_name }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">School</p><p class="mt-1 font-semibold text-slate-900">{{ $record->school_name }} <span class="font-normal text-slate-500">({{ $record->school_id }})</span></p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Delivery date</p><p class="mt-1 text-slate-800">{{ $record->delivery_date }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Logistics delivery status</p><p class="mt-1 capitalize text-slate-800">{{ $record->delivery_status }}</p></div>
            </div>
            <div class="space-y-4 border-t border-slate-100 pt-4 sm:border-l sm:border-t-0 sm:pl-5 sm:pt-0">
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Billing status</p><p class="mt-1 inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-800">{{ $billingStatusLabel }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Invoice number / date</p><p class="mt-1 text-slate-900">{{ $record->invoice_no ?? '—' }} <span class="text-slate-500">{{ $record->invoice_date ? '· '.$record->invoice_date : '' }}</span></p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Invoice amount</p><p class="mt-1 font-mono text-slate-900">{{ $record->invoice_amount === null ? '—' : number_format((float) $record->invoice_amount, 2) }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Delivery allocation / reference</p><p class="mt-1 text-slate-800">{{ $record->billing_amount === null ? '—' : number_format((float) $record->billing_amount, 2) }} · {{ $record->billing_reference ?? 'No reference' }}</p></div>
                @if ($record->billing_remarks)
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Billing remarks</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ $record->billing_remarks }}</p></div>
                @endif
                @if ($record->invoice_status)
                    <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">Invoice payment status is separate: <span class="font-semibold">{{ $record->invoice_status }}</span></p>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">Google Sheets delivery-date reconciliation</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $sheetConfiguration['configured'] ? 'Google Sheets credentials and target are configured.' : $sheetConfiguration['reason'] }}</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($sheetSyncs as $sync)
                    @php
                        $syncLabel = str($sync->outcome)->replace('_', ' ')->title();
                        $syncTone = in_array($sync->outcome, ['synced', 'already_synced'], true) ? 'bg-emerald-50 text-emerald-800'
                            : (in_array($sync->outcome, ['conflict', 'no_match', 'duplicate_match', 'reconciliation_error', 'api_failed', 'ambiguous_write', 'batch_blocked'], true) ? 'bg-rose-50 text-rose-800'
                                : (in_array($sync->outcome, ['configuration_missing', 'queue_unavailable', 'dispatch_failed', 'retrying'], true) ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700'));
                        $canRetrySheetSync = !in_array($sync->outcome, ['synced', 'already_synced', 'not_applicable'], true);
                    @endphp
                    <article class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto] sm:items-start">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="text-sm text-slate-900">{{ $sync->lot_number ? 'Lot '.$sync->lot_number : 'Lot not identified' }}</strong>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $syncTone }}">{{ $syncLabel }}</span>
                                @if ($sync->target_cell)<span class="font-mono text-xs text-slate-500">{{ $sync->sheet_tab }}!{{ $sync->target_cell }}</span>@endif
                            </div>
                            <p class="mt-2 break-words text-sm text-slate-700">BIR DR: <span class="font-mono">{{ $sync->bir_dr ?? 'Not available' }}</span> · Core Package Code: <span class="font-mono">{{ $sync->package_code ?? 'Not available' }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">Transition date: {{ $sync->transition_date->toDateString() }} · Attempts: {{ $sync->attempts }}</p>
                            @if ($sync->error_details)<p class="mt-2 break-words text-sm text-rose-700">{{ $sync->error_details }}</p>@endif
                        </div>
                        @if ($canRetrySheetSync)
                            <form method="POST" action="{{ route('billing.records.sheet-sync.retry', [$record->delivery_id, $sync]) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-700">Retry reconciliation</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-5 text-sm text-slate-500">No Billing transition has been queued for Google Sheets.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">Billing history</h2>
                <p class="mt-1 text-sm text-slate-500">Status transitions are append-only and retain the acting user and timestamp.</p>
            </div>
            <ol class="divide-y divide-slate-100">
                @forelse ($history as $event)
                    <li class="grid gap-2 px-5 py-4 sm:grid-cols-[1fr_auto]">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $event->previous_status ? str($event->previous_status)->replace('_', ' ')->title() : 'Initial status' }} <span class="px-1 text-slate-400">→</span> {{ str($event->new_status)->replace('_', ' ')->title() }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $event->actor_name ?? 'Former user' }}@if ($event->invoice_no) · Invoice {{ $event->invoice_no }} @endif</p>
                            @if ($event->remarks)
                                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $event->remarks }}</p>
                            @endif
                        </div>
                        <time class="text-xs tabular-nums text-slate-500">{{ $event->created_at }}</time>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">No billing transitions have been recorded for this delivery.</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-project_app-layout>
