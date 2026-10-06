@include('operation.delivery.partials.monitoring-warehouse')
<p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-600">Operational Flow</p>
<ol class="grid gap-4 md:grid-cols-5 md:gap-2" aria-label="DR through Stock Out to billed operational pipeline">
    @foreach($project['operational_pipeline'] as $key => $stage)
        <li class="relative pl-7 md:pl-0">
            @unless($loop->last)<span aria-hidden="true" class="absolute left-2 top-4 h-full w-px bg-slate-200 md:left-2 md:top-2 md:h-px md:w-full"></span>@endunless
            <span aria-hidden="true" class="absolute left-0 top-0.5 z-10 h-4 w-4 rounded-full border-2 {{ ($stage['percent'] ?? 0) >= 100 ? 'border-blue-600 bg-blue-600' : (($stage['completed'] ?? 0) > 0 ? 'border-blue-600 bg-blue-100' : 'border-slate-300 bg-white') }} md:relative md:top-0 md:block"></span>
            <div class="md:mt-3" title="{{ $stage['context'] }}">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-600">{{ $stage['label'] }}</p>
                @if(!$stage['available'])
                    <p class="mt-1 text-sm font-semibold text-slate-500">Per item unit</p>
                    <p class="mt-1 text-[11px] leading-4 text-slate-500">See details</p>
                @else
                    <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">{{ number_format($stage['completed']) }} @if($key !== 'dr')<span class="text-xs font-normal text-slate-500">/ {{ number_format($stage['total']) }}{{ $key === 'delivered' ? ' DRs' : '' }}</span>@endif</p>
                    <p class="mt-1 text-xs font-semibold tabular-nums {{ ($stage['completed'] ?? 0) > 0 ? 'text-blue-700' : 'text-slate-500' }}">{{ $key === 'dr' ? ($stage['total'] ? 'Recorded' : 'No DRs') : ($stage['percent'] === null ? ($key === 'delivered' ? 'N/A' : 'No records') : number_format($stage['percent'], 1).'%') }}</p>
                    <p class="mt-1 text-[11px] leading-4 text-slate-500">{{ $stage['unit'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
@include('operation.delivery.partials.monitoring-logistics', ['performance' => $project['logistics_delivery_performance']])
<div class="mt-4 border-t border-slate-100 pt-3">@include('operation.delivery.partials.monitoring-progress')</div>
@if($project['data_integrity_flags'])
    <ul class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900" aria-label="Workflow inconsistencies">
        @foreach($project['data_integrity_flags'] as $flag)<li>{{ $flag }}</li>@endforeach
    </ul>
@endif
