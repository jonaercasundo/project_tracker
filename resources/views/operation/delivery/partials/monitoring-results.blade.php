<div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
    @foreach([
        ['Active Projects', $report['summary']['active_projects_count'], null],
        ['Total DRs', $report['summary']['total_deliveries_count'], 'receipts'],
        ['Warehouse Stock', $report['summary']['warehouse_readiness']['available'], $report['summary']['warehouse_readiness']['unit']],
        ['Delivered / Accepted', $report['summary']['completed_packages_count'], 'DR allocations'],
        ['Entered Billing', $report['summary']['pipeline']['billing']['completed'], 'DR/group records'],
        ['Billed', $report['summary']['billed_groups_count'], 'DR/group records'],
        ['Overall Operational Progress', $report['summary']['overall_progress'], '%'],
    ] as [$label, $value, $unit])
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold leading-5 text-slate-500">{{ $label }}</p>
            <p class="mt-3 {{ $value === null ? 'text-sm text-slate-500' : 'text-2xl text-slate-900' }} font-extrabold tabular-nums">{{ $value === null ? 'Unavailable' : ($unit === '%' ? number_format($value, 1).'%' : number_format($value)) }}</p>
            @if($unit && $unit !== '%')<p class="mt-1 text-[11px] text-slate-500">{{ $value === null ? 'See unit breakdown' : $unit }}</p>@endif
        </div>
    @endforeach
</div>
