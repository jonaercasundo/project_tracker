<div class="grid grid-cols-2 gap-3 xl:grid-cols-6" aria-label="Logistics DR summary">
    @foreach([
        ['label' => 'Total scoped DRs', 'value' => $logistics['summary']['total_scoped_drs'], 'tone' => 'border-slate-200 bg-slate-50 text-slate-700'],
        ['label' => 'Logistics Received', 'value' => $logistics['summary']['logistics_received'], 'tone' => 'border-blue-100 bg-blue-50 text-blue-700'],
        ['label' => 'Logistics Delivered', 'value' => $logistics['summary']['logistics_delivered'], 'tone' => 'border-emerald-100 bg-emerald-50 text-emerald-700'],
        ['label' => 'Pending Logistics updates', 'value' => $logistics['summary']['pending_updates'], 'tone' => 'border-amber-100 bg-amber-50 text-amber-700'],
        ['label' => 'Reconciliation required', 'value' => $logistics['summary']['reconciliation_required'], 'tone' => 'border-rose-100 bg-rose-50 text-rose-700'],
        ['label' => 'Delivery performance', 'value' => $logistics['summary']['delivery_performance_percentage'], 'suffix' => '%', 'tone' => 'border-indigo-100 bg-indigo-50 text-indigo-700'],
    ] as $card)
        <article class="rounded-xl border p-4 {{ $card['tone'] }}">
            <p class="text-xs font-semibold">{{ $card['label'] }}</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums">{{ number_format((float) $card['value'], isset($card['suffix']) ? 1 : 0) }}{{ $card['suffix'] ?? '' }}</p>
            @if($card['label'] === 'Pending Logistics updates')
                <p class="mt-1 text-[11px]">Distinct project/school/DR scopes without a current milestone</p>
            @elseif($card['label'] === 'Reconciliation required')
                <p class="mt-1 text-[11px]">Distinct event scopes needing review</p>
            @else
                <p class="mt-1 text-[11px]">Distinct verified DR scopes</p>
            @endif
        </article>
    @endforeach
</div>

@if($logistics['summary']['partner_performance'] !== [])
    <div class="flex flex-wrap gap-2" aria-label="Logistics partner performance">
        @foreach($logistics['summary']['partner_performance'] as $partner)
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700">
                {{ $partner['integration_id'] }} · {{ $partner['performance_percentage'] }}% delivered ({{ $partner['delivered_scopes'] }}/{{ $partner['total_scopes'] }} verified scopes)
            </span>
        @endforeach
    </div>
@endif

<div class="overflow-x-auto rounded-xl border border-slate-200">
    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th scope="col" class="px-4 py-3">DR# / integration</th>
                <th scope="col" class="px-4 py-3">Project</th>
                <th scope="col" class="px-4 py-3">School</th>
                <th scope="col" class="px-4 py-3">Logistics status</th>
                <th scope="col" class="px-4 py-3">Status time</th>
                <th scope="col" class="px-4 py-3">MMC receiving</th>
                <th scope="col" class="px-4 py-3">Reconciliation</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($logistics['rows'] as $row)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-slate-900">{{ $row['dr_reference'] }}<span class="mt-1 block font-sans text-[10px] font-normal text-slate-400">{{ $row['integration_id'] }}</span></td>
                    <td class="px-4 py-3 text-slate-700">{{ $row['project_name'] ?? 'Project '.$row['project_id'] }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $row['school_name'] ?: $row['school_id'] }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $row['logistics_status'] === 'delivered' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $row['logistics_status'] === 'delivered' ? 'Logistics Delivered' : 'Logistics Received' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ \Illuminate\Support\Carbon::parse($row['last_logistics_update'])->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-700">
                        {{ str_replace('_', ' ', ucfirst($row['mmc_receiving_status'])) }}
                        <span class="block text-[11px] text-slate-500">{{ $row['mmc_received_package_count'] }} / {{ $row['mmc_expected_package_count'] }} packages</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">{{ str_replace('_', ' ', ucfirst($row['reconciliation_status'])) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No Logistics events match these filters. DR scopes without webhook events are counted as pending above and are not assigned a Logistics status.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="flex flex-col gap-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
    <p>
        @if($logistics['rows']->total() > 0)
            Showing {{ $logistics['rows']->firstItem() }}–{{ $logistics['rows']->lastItem() }} of {{ $logistics['rows']->total() }} verified scopes
        @else
            No verified scopes on this page
        @endif
    </p>
    <div class="flex items-center gap-2">
        <button type="button" data-logistics-page="{{ max(1, $logistics['rows']->currentPage() - 1) }}" @disabled($logistics['rows']->onFirstPage()) class="rounded-lg border border-slate-200 px-3 py-2 font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button>
        <span>Page {{ $logistics['rows']->currentPage() }} of {{ max(1, $logistics['rows']->lastPage()) }}</span>
        <button type="button" data-logistics-page="{{ min($logistics['rows']->lastPage(), $logistics['rows']->currentPage() + 1) }}" @disabled(!$logistics['rows']->hasMorePages()) class="rounded-lg border border-slate-200 px-3 py-2 font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
    </div>
</div>
