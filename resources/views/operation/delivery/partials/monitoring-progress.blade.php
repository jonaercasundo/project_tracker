<div class="flex items-center justify-between gap-3 text-xs">
    <p class="font-semibold text-slate-700">Overall Operational Progress</p>
    <span class="font-bold tabular-nums text-blue-700">@if($project['overall_progress'] === null)Unavailable @else<span data-progress-count="{{ $project['overall_progress'] }}" data-progress-decimals="1">{{ number_format($project['overall_progress'], 1) }}</span>%@endif</span>
</div>
<div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="{{ $project['project_name'] }} overall operational progress" aria-valuemin="0" aria-valuemax="100" @if($project['overall_progress'] !== null) aria-valuenow="{{ $project['overall_progress'] }}" @else aria-valuetext="One or more operational stages have no schools to measure" @endif>
    @if($project['overall_progress'] !== null)<div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, max(0, $project['overall_progress'])) }}%"></div>@endif
</div>
<p class="mt-2 text-[11px] leading-4 text-slate-500">{{ number_format($project['delivered_schools_count']) }} delivered / accepted schools &middot; {{ number_format($project['billing_schools_count']) }} schools for billing &middot; {{ number_format($project['billed_schools_count']) }} billed schools</p>
@if($project['overall_progress'] === null)<p class="mt-1 text-[11px] leading-4 text-slate-500">Overall progress is unavailable because one or more operational stages have no schools to measure.</p>@endif
