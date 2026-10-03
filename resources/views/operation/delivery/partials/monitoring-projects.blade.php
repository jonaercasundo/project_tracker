@php
    $formatDate = static fn ($value) => $value ? Illuminate\Support\Carbon::parse($value, config('app.timezone'))->format('M d, Y') : 'Not recorded';
@endphp
<div class="hidden overflow-x-auto md:block">
<table class="w-full min-w-[960px] table-fixed text-left text-sm">
    <colgroup><col class="w-[30%]"><col class="w-[9%]"><col class="w-[12%]"><col class="w-[11%]"><col class="w-[16%]"><col class="w-[11%]"><col class="w-[11%]"></colgroup>
    <thead class="bg-slate-50 text-xs font-semibold text-slate-500"><tr>
        <th class="px-4 py-3">Project / Contract</th><th class="px-3 py-3" title="Delivery Receipts">DRs</th><th class="px-3 py-3" title="Package allocations across Delivery Receipts">DR Packages</th><th class="px-3 py-3">Billing</th><th class="px-3 py-3">Progress</th><th class="px-3 py-3">Last Delivery</th><th class="px-3 py-3">Actions</th>
    </tr></thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($report['projects'] as $project)
            <tr class="align-top hover:bg-blue-50/30" data-project-row="{{ $project['project_id'] }}">
                <td class="px-4 py-3">
                    <a href="{{ route('projects.show', $project['project_id']) }}" title="{{ $project['project_name'] }}" class="line-clamp-2 font-bold leading-5 text-slate-900 hover:text-blue-600">{{ $project['project_name'] }}</a>
                    <p class="mt-1 truncate text-xs text-slate-500" title="{{ $project['ref_no'] }} {{ $project['lot_names'] }}">{{ $project['ref_no'] ?: 'No reference recorded' }} @if($project['lot_names']) &middot; {{ $project['lot_names'] }} @endif</p>
                    <p class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $formatDate($project['start_date']) }} &rarr; {{ $formatDate($project['end_date']) }}</p>
                    <span class="mt-1 inline-block rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $project['project_status'] }}</span>
                </td>
                <td class="px-3 py-3"><p class="font-bold tabular-nums">{{ number_format($project['total_deliveries_count']) }}</p><p class="text-xs text-slate-500">Total DRs</p><p class="mt-2 text-xs text-emerald-700">{{ number_format($project['completed_deliveries_count']) }} complete</p></td>
                <td class="px-3 py-3"><p class="font-bold tabular-nums">{{ number_format($project['total_packages_count']) }}</p><p class="text-xs text-slate-500">Allocations</p><p class="mt-2 text-xs text-emerald-700">{{ number_format($project['completed_packages_count']) }} complete</p><p class="text-xs text-amber-700">{{ number_format($project['remaining_packages_count']) }} remaining</p></td>
                <td class="px-3 py-3">
                    @if($project['billing_groups_count'])
                        <p class="font-bold tabular-nums">{{ number_format($project['billing_progress_percent'], 1) }}% <span class="text-xs font-normal">billed / paid</span></p>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($project['billing_groups_count']) }} records</p>
                        <p class="mt-2 text-xs text-blue-700">Billed: {{ number_format($project['billed_groups_count']) }}</p><p class="text-xs text-emerald-700">Paid: {{ number_format($project['paid_groups_count']) }}</p>
                    @else<p class="text-xs text-slate-500">No billing records</p>@endif
                </td>
                <td class="px-3 py-3">@include('operation.delivery.partials.monitoring-progress')</td>
                <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-600">{{ $formatDate($project['last_delivery_date']) }}</td>
                <td class="px-3 py-3">@include('operation.delivery.partials.monitoring-actions', ['detailsId' => 'project-details-'.$project['project_id']])</td>
            </tr>
            <tr id="project-details-{{ $project['project_id'] }}" hidden class="bg-slate-50"><td colspan="7" class="px-5 py-5">@include('operation.delivery.partials.monitoring-details')</td></tr>
        @empty
            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No projects match these filters. Reset filters or include inactive projects.</td></tr>
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
            <p class="mt-1 text-xs text-slate-500">{{ $project['ref_no'] ?: 'No reference recorded' }} @if($project['lot_names']) &middot; {{ $project['lot_names'] }} @endif &middot; {{ $project['project_status'] }}</p>
            <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div><p class="text-slate-500">Total DRs</p><p class="mt-1 font-bold">{{ number_format($project['total_deliveries_count']) }}</p><p class="mt-1 text-emerald-700">{{ number_format($project['completed_deliveries_count']) }} complete</p></div>
                <div><p class="text-slate-500">DR Packages</p><p class="mt-1 font-bold">{{ number_format($project['total_packages_count']) }} allocations</p><p class="mt-1 text-emerald-700">{{ number_format($project['completed_packages_count']) }} complete</p><p class="text-amber-700">{{ number_format($project['remaining_packages_count']) }} remaining</p></div>
                <div><p class="text-slate-500">Billing</p><p class="mt-1 font-bold">{{ $project['billing_progress_percent'] === null ? 'No billing records' : number_format($project['billing_progress_percent'], 1).'% billed / paid' }}</p><p class="mt-1">Billed: {{ number_format($project['billed_groups_count']) }} &middot; Paid: {{ number_format($project['paid_groups_count']) }}</p></div>
                <div><p class="text-slate-500">Last Delivery</p><p class="mt-1 whitespace-nowrap font-semibold">{{ $formatDate($project['last_delivery_date']) }}</p></div>
            </div>
            <div class="mt-4">@include('operation.delivery.partials.monitoring-progress')</div>
            <div id="project-mobile-details-{{ $project['project_id'] }}" hidden class="mt-4 rounded-lg bg-slate-50 p-4">@include('operation.delivery.partials.monitoring-details')</div>
        </article>
    @empty
        <p class="p-6 text-center text-sm text-slate-500">No projects match these filters.</p>
    @endforelse
</div>
