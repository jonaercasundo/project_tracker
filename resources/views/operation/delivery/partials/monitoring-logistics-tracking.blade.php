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
