<x-project_app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Billing ledger</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Delivery records</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('billing.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-500">Dashboard</a>
                <a href="{{ route('billing.prepare') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-500">Prepare for Billing</a>
                <a href="{{ route('billing.batches.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-500">Batches</a>
                <a href="{{ route('billing.scanner') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Open scanner</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5">
        <form method="GET" action="{{ route('billing.records.index') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="space-y-1 text-xs font-semibold text-slate-600">Project
                <select name="project_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">DR number
                <input name="dr_no" value="{{ $filters['dr_no'] ?? '' }}" maxlength="100" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Search DR number">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">School
                <select name="school_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All schools</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->school_id }}" @selected(($filters['school_id'] ?? '') == $school->school_id)>{{ $school->school_name }} · {{ $school->school_id }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Billing status
                <select name="billing_status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['billing_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery date from
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery date to
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice number
                <input name="invoice_no" value="{{ $filters['invoice_no'] ?? '' }}" maxlength="100" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Search invoice">
            </label>
            <div class="flex items-end gap-2">
                <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Apply filters</button>
                <a href="{{ route('billing.records.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                @can('billing.reports.export')
                    <a href="{{ route('billing.records.export', $filters) }}" class="ml-auto rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:border-emerald-500 hover:text-emerald-700">CSV</a>
                @endcan
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-900">Delivery billing ledger</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($records->total()) }} matching records</p>
                </div>
                <p class="text-xs text-slate-500">Logistics delivery status and billing status are tracked separately.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">DR number</th>
                            <th class="px-4 py-3">Project / school</th>
                            <th class="px-4 py-3">Delivery date</th>
                            <th class="px-4 py-3">Delivery status</th>
                            <th class="px-4 py-3">Billing status</th>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3"><span class="sr-only">Details</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-slate-900">{{ $record->dr_no }}</td>
                                <td class="min-w-64 px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ $record->project_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $record->school_name }} · {{ $record->school_id }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $record->delivery_date }}</td>
                                <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">{{ $record->delivery_status }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php
                                        $billingTone = match ($record->billing_status) {
                                            'billed' => 'bg-emerald-50 text-emerald-800',
                                            'for_billing' => 'bg-blue-50 text-blue-800',
                                            'on_hold' => 'bg-rose-50 text-rose-800',
                                            default => 'bg-amber-50 text-amber-800',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $billingTone }}">{{ $statuses[$record->billing_status] ?? $record->billing_status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-blue-700">
                                    @if ($record->billing_batch_id)
                                        <a href="{{ route('billing.batches.show', $record->billing_batch_id) }}" class="hover:underline">{{ $record->batch_ref }}</a>
                                    @else
                                        <span class="text-slate-400">Legacy / individual</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $record->invoice_no ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('billing.records.show', $record->delivery_id) }}" class="font-semibold text-blue-700 hover:text-blue-900">Details <span aria-hidden="true">→</span></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No delivery records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $records->links() }}</div>
        </section>
    </div>
</x-project_app-layout>
