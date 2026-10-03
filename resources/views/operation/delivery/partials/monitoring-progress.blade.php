<div class="flex items-center justify-between gap-3 text-xs">
    <p class="font-semibold text-slate-700">Overall Operational Progress</p>
    <span class="font-bold tabular-nums text-blue-700">{{ $project['overall_progress'] === null ? 'Unavailable' : number_format($project['overall_progress'], 1).'%' }}</span>
</div>
<div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="{{ $project['project_name'] }} overall operational progress" aria-valuemin="0" aria-valuemax="{{ max(100, $project['overall_progress'] ?? 0) }}" @if($project['overall_progress'] !== null) aria-valuenow="{{ $project['overall_progress'] }}" @else aria-valuetext="Separate units, incomplete quantities or filtered delivery scope; see details" @endif>
    @if($project['overall_progress'] !== null)<div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, max(0, $project['overall_progress'])) }}%"></div>@endif
</div>
<p class="mt-2 text-[11px] leading-4 text-slate-500">{{ number_format($project['completed_packages_count']) }} delivered / accepted allocations &middot; {{ number_format($project['for_billing_groups_count']) }} for billing &middot; {{ number_format($project['billed_groups_count']) }} billed records</p>
@if($project['overall_progress'] === null)<p class="mt-1 text-[11px] leading-4 text-slate-500">Overall needs complete, compatible stage quantities. Inventory is project-wide; delivery filters and separate units remain explicit.</p>@endif
