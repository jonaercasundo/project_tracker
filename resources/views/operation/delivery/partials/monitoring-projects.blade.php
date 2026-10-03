@php
    $formatDate = static fn ($value) => $value ? Illuminate\Support\Carbon::parse($value, config('app.timezone'))->format('M d, Y') : 'Not recorded';
@endphp
<div class="hidden overflow-x-auto md:block">
<table class="w-full min-w-[1060px] table-fixed text-left text-sm">
    <colgroup><col class="w-[25%]"><col class="w-[53%]"><col class="w-[11%]"><col class="w-[11%]"></colgroup>
    <thead class="bg-slate-50 text-xs font-semibold text-slate-500"><tr><th class="px-4 py-3">Project / Contract</th><th class="px-4 py-3">Operational Progress</th><th class="px-3 py-3">Last Activity</th><th class="px-3 py-3">Actions</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($report['projects'] as $project)
            <tr class="align-top hover:bg-blue-50/30" data-project-row="{{ $project['project_id'] }}">
                <td class="px-4 py-5">
                    <a href="{{ route('projects.show', $project['project_id']) }}" title="{{ $project['project_name'] }}" class="line-clamp-2 font-bold leading-5 text-slate-900 hover:text-blue-600">{{ $project['project_name'] }}</a>
                    <p class="mt-2 truncate text-xs text-slate-500" title="{{ $project['ref_no'] }} {{ $project['lot_names'] }}">{{ $project['ref_no'] ?: 'No reference recorded' }} @if($project['lot_names']) &middot; {{ $project['lot_names'] }} @endif</p>
                    <p class="mt-2 whitespace-nowrap text-xs text-slate-500">{{ $formatDate($project['start_date']) }} &rarr; {{ $formatDate($project['end_date']) }}</p>
                    <span class="mt-2 inline-block rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $project['project_status'] }}</span>
                </td>
                <td class="px-4 py-5">@include('operation.delivery.partials.monitoring-pipeline')</td>
                <td class="px-3 py-5"><p class="whitespace-nowrap text-xs font-semibold text-slate-700">{{ $formatDate($project['last_activity']['at'] ?? null) }}</p><p class="mt-1 text-xs text-slate-500">{{ $project['last_activity']['type'] ?? 'No dated activity' }}</p></td>
                <td class="px-3 py-5">@include('operation.delivery.partials.monitoring-actions', ['detailsId' => 'project-details-'.$project['project_id']])</td>
            </tr>
            <tr id="project-details-{{ $project['project_id'] }}" hidden class="bg-slate-50"><td colspan="4" class="px-5 py-5">@include('operation.delivery.partials.monitoring-details')</td></tr>
        @empty
            <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No projects match these filters. Reset filters or include inactive projects.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<div class="divide-y divide-slate-100 md:hidden">
    @forelse($report['projects'] as $project)
        <article class="p-4">
            <div class="flex items-start justify-between gap-3">
                <a href="{{ route('projects.show', $project['project_id']) }}" title="{{ $project['project_name'] }}" class="line-clamp-2 font-bold leading-5 text-slate-900">{{ $project['project_name'] }}</a>
                @include('operation.delivery.partials.monitoring-actions', ['detailsId' => 'project-mobile-details-'.$project['project_id']])
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $project['ref_no'] ?: 'No reference recorded' }} @if($project['lot_names']) &middot; {{ $project['lot_names'] }} @endif &middot; {{ $project['project_status'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $formatDate($project['start_date']) }} &rarr; {{ $formatDate($project['end_date']) }}</p>
            <div class="mt-5">@include('operation.delivery.partials.monitoring-pipeline')</div>
            <p class="mt-4 text-xs text-slate-500">Last Activity: <span class="whitespace-nowrap font-semibold text-slate-700">{{ $formatDate($project['last_activity']['at'] ?? null) }}</span> &middot; {{ $project['last_activity']['type'] ?? 'No dated activity' }}</p>
            <div id="project-mobile-details-{{ $project['project_id'] }}" hidden class="mt-4 rounded-lg bg-slate-50 p-4">@include('operation.delivery.partials.monitoring-details')</div>
        </article>
    @empty
        <p class="p-6 text-center text-sm text-slate-500">No projects match these filters.</p>
    @endforelse
</div>
