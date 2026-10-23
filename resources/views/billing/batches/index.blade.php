<x-project_app-layout>
    <x-slot name="header">
        <div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Billing queue integration</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Billing batches</h1>
            </div>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-4">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">Operations submissions</h2>
                <p class="mt-1 text-sm text-slate-500">Each batch links to the existing delivery billing records by delivery ID. Historical records remain individually traceable.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr>
                        <th class="px-4 py-3">Batch Reference</th><th class="px-4 py-3">DR Count</th><th class="px-4 py-3">Created By</th><th class="px-4 py-3">Created</th><th class="px-4 py-3">Confirmed By</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Details</span></th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($batches as $batch)
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 font-mono font-bold text-blue-700"><a href="{{ route('billing.batches.show', $batch) }}" class="hover:underline">{{ $batch->batch_ref }}</a></td>
                                <td class="px-4 py-3 font-semibold tabular-nums text-slate-900">{{ number_format($batch->delivery_billing_records_count) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $batch->created_by_name ?? $batch->createdBy?->name ?? 'Former user' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $batch->confirmed_by_name ?? $batch->confirmedBy?->name ?? 'Former user' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-800">{{ str($batch->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('billing.batches.show', $batch) }}" class="font-semibold text-blue-700 hover:text-blue-900">Review batch →</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No Operations billing batches have been confirmed.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $batches->links() }}</div>
        </section>
    </main>
</x-project_app-layout>
