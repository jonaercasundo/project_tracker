<section class="mt-4 border-t border-slate-100 pt-3" aria-label="Logistics Delivery Performance">
    <p class="text-xs font-bold uppercase tracking-wide text-slate-600">Logistics Delivery Performance</p>
    @if($performance['completion_leader'])
        <p class="mt-2 text-xs text-slate-600">Completion Leader: <span class="font-semibold text-slate-900">{{ $performance['completion_leader']['name'] }}</span></p>
    @endif
    <p class="mt-2 text-xs tabular-nums text-slate-500">{{ number_format($performance['delivered_assigned_drs']) }} / {{ number_format($performance['assigned_drs']) }} assigned DRs delivered</p>
    @forelse(array_slice($performance['partners'], 0, 3) as $partner)
        <div class="mt-3">
            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700"><span>#{{ $loop->iteration }} {{ $partner['name'] }}</span><span class="tabular-nums">{{ number_format($partner['delivery_percentage'], 1) }}%</span></div>
            <p class="mt-1 text-xs tabular-nums text-slate-500">{{ number_format($partner['delivered_drs']) }} / {{ number_format($partner['total_assigned_drs']) }} DRs delivered</p>
            <progress class="mt-1 h-2 w-full accent-blue-600" max="100" value="{{ $partner['delivery_percentage'] }}" aria-label="{{ $partner['name'] }} delivery completion">{{ number_format($partner['delivery_percentage'], 1) }}%</progress>
        </div>
    @empty
        <p class="mt-2 text-xs text-slate-500">No assigned logistics partners.</p>
    @endforelse
    @if(count($performance['partners']) > 3)
        <details class="mt-3 text-xs text-slate-600">
            <summary class="cursor-pointer font-semibold">View all {{ count($performance['partners']) }} logistics partners</summary>
            @foreach($performance['partners'] as $partner)
                <p class="mt-2">{{ $partner['name'] }}: {{ number_format($partner['delivered_drs']) }} / {{ number_format($partner['total_assigned_drs']) }} DRs delivered ({{ number_format($partner['delivery_percentage'], 1) }}%)</p>
            @endforeach
        </details>
    @endif
    @if($performance['unassigned_drs'])
        <p class="mt-3 text-xs text-amber-800">{{ number_format($performance['unassigned_drs']) }} DRs without logistics partner; {{ number_format($performance['delivered_unassigned_drs']) }} delivered. Included in project totals.</p>
    @endif
    @if($performance['conflicting_drs'])
        <p class="mt-2 text-xs text-amber-800">{{ number_format($performance['conflicting_drs']) }} DRs have conflicting logistics assignments and are included in the unassigned total.</p>
    @endif
</section>
