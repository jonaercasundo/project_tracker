<div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
    @foreach([
        'active_projects_count' => ['Active Projects', 'text-slate-900'],
        'total_deliveries_count' => ['Total DRs', 'text-slate-900'],
        'total_packages_count' => ['Total DR Packages', 'text-slate-900'],
        'pending_packages_count' => ['Pending DR Packages', 'text-amber-600'],
        'delivered_packages_count' => ['Delivered DR Packages', 'text-emerald-600'],
        'accepted_packages_count' => ['Accepted DR Packages', 'text-emerald-700'],
        'delivery_progress_percent' => ['Overall Package Progress', 'text-blue-600'],
    ] as $key => [$label, $color])
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold leading-5 text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-extrabold tabular-nums {{ $color }}" data-summary="{{ $key }}">{{ $report['summary'][$key] === null ? 'No package allocations' : ($key === 'delivery_progress_percent' ? number_format($report['summary'][$key], 1).'%' : number_format($report['summary'][$key])) }}</p>
        </div>
    @endforeach
</div>
