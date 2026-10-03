@php($readiness = $project['warehouse_readiness'])
<section class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3" aria-label="Warehouse readiness for {{ $project['project_name'] }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600">Warehouse Readiness</h3>
        <span class="text-xs font-bold tabular-nums text-blue-700">{{ $readiness['percent'] === null ? ($readiness['required'] === null ? 'Separate item units' : 'No complete requirements') : number_format($readiness['percent'], 1).'% ready' }}</span>
    </div>
    @if($readiness['available'] !== null)
        <p class="mt-2 text-sm font-bold tabular-nums text-slate-900">{{ number_format($readiness['available']) }} <span class="text-xs font-normal text-slate-500">{{ $readiness['unit'] }} in warehouse &middot; {{ number_format($readiness['required']) }} required</span></p>
        <p class="mt-1 text-[11px] text-slate-500">{{ number_format($readiness['covered']) }} required {{ $readiness['unit'] }} covered by current stock</p>
    @else
        <p class="mt-2 text-xs text-slate-600">Mixed units are shown separately in View Details.</p>
    @endif
    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Warehouse readiness" aria-valuemin="0" aria-valuemax="100" @if($readiness['percent'] !== null) aria-valuenow="{{ $readiness['percent'] }}" @else aria-valuetext="See required quantities and unit breakdown" @endif>
        @if($readiness['percent'] !== null)<div class="h-full rounded-full bg-blue-600" style="width: {{ $readiness['percent'] }}%"></div>@endif
    </div>
    <p class="mt-2 text-[11px] leading-4 text-slate-500">Whole-project inventory readiness, separate from delivery completion.</p>
</section>
