@if($project['delivery_progress_percent'] === null)
    <p class="text-xs font-medium text-slate-500">No package allocations</p>
@else
    <p class="text-lg font-extrabold tabular-nums text-blue-700">{{ number_format($project['delivery_progress_percent'], 1) }}%</p>
    <div class="mt-1 h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="{{ $project['project_name'] }} DR package progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $project['delivery_progress_percent'] }}">
        <div class="h-full rounded-full bg-blue-600" style="width: {{ $project['delivery_progress_percent'] }}%"></div>
    </div>
    <p class="mt-2 text-xs tabular-nums text-slate-500">{{ number_format($project['completed_packages_count']) }} / {{ number_format($project['total_packages_count']) }} DR packages</p>
@endif
