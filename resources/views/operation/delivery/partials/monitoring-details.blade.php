@php
    $formatDate = static fn ($value) => $value ? Illuminate\Support\Carbon::parse($value, config('app.timezone'))->format('M d, Y') : 'Not recorded';
@endphp
<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        'Delivery Receipts' => ['total_deliveries_count' => 'Total DRs', 'pending_deliveries_count' => 'Pending', 'released_deliveries_count' => 'Released', 'delivered_deliveries_count' => 'Delivered', 'accepted_deliveries_count' => 'Accepted', 'completed_deliveries_count' => 'Completed', 'warehouse_deliveries_count' => 'Warehouse', 'mixed_deliveries_count' => 'Mixed', 'cancelled_deliveries_count' => 'Cancelled', 'for_approval_deliveries_count' => 'For approval', 'delivery_rows_count' => 'Delivery records'],
        'DR Package Allocations' => ['total_packages_count' => 'Total allocations', 'pending_packages_count' => 'Pending', 'released_packages_count' => 'Released', 'delivered_packages_count' => 'Delivered', 'accepted_packages_count' => 'Accepted', 'warehouse_packages_count' => 'Warehouse', 'completed_packages_count' => 'Completed', 'remaining_packages_count' => 'Remaining'],
        'Billing' => ['billing_groups_count' => 'Records', 'for_billing_groups_count' => 'For Billing', 'billed_groups_count' => 'Billed', 'paid_groups_count' => 'Paid'],
    ] as $group => $columns)
        <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $group }}</h3><dl class="grid gap-2">
            @foreach($columns as $column => $label)
                <div class="flex justify-between gap-4 text-xs"><dt class="text-slate-600">{{ $label }}</dt><dd class="font-semibold tabular-nums text-slate-900">{{ number_format($project[$column]) }}</dd></div>
            @endforeach
            @if($group === 'Billing')
                <div class="flex justify-between gap-4 text-xs"><dt class="text-slate-600">Billing %</dt><dd class="font-semibold">{{ $project['billing_progress_percent'] === null ? 'No billing records' : number_format($project['billing_progress_percent'], 1).'%' }}</dd></div>
            @endif
        </dl></section>
    @endforeach
    <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Timeline</h3><dl class="grid gap-2 text-xs">
        @foreach(['start_date' => 'Start Date', 'end_date' => 'End Date', 'last_delivery_date' => 'Last Delivery'] as $key => $label)
            <div class="flex justify-between gap-3"><dt class="text-slate-600">{{ $label }}</dt><dd class="whitespace-nowrap font-semibold">{{ $formatDate($project[$key]) }}</dd></div>
        @endforeach
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Project Status</dt><dd class="font-semibold">{{ $project['project_status'] }}</dd></div>
    </dl></section>
</div>
<div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4" aria-label="Project delivery stages">
    @foreach(['total_deliveries_count' => 'DRs', 'total_packages_count' => 'Package Allocations', 'released_packages_count' => 'Released', 'delivered_packages_count' => 'Delivered', 'accepted_packages_count' => 'Accepted', 'billing_groups_count' => 'Billing Records'] as $key => $label)
        @unless($loop->first)<span aria-hidden="true" class="text-slate-400">&rarr;</span>@endunless
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-sm font-bold tabular-nums">{{ number_format($project[$key]) }}</p></div>
    @endforeach
</div>
<p class="mt-3 text-xs text-slate-500">Stage counts are current allocation statuses; billing counts are records.</p>
