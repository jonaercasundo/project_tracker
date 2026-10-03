@php
    $formatDate = static fn ($value) => $value ? Illuminate\Support\Carbon::parse($value, config('app.timezone'))->format('M d, Y') : 'Not recorded';
@endphp
<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        'DR Summary' => ['total_deliveries_count' => 'Total DRs', 'pending_deliveries_count' => 'Pending', 'released_deliveries_count' => 'Released', 'delivered_deliveries_count' => 'Delivered', 'accepted_deliveries_count' => 'Accepted', 'completed_deliveries_count' => 'Completed', 'warehouse_deliveries_count' => 'Warehouse', 'mixed_deliveries_count' => 'Mixed', 'cancelled_deliveries_count' => 'Cancelled', 'for_approval_deliveries_count' => 'For approval', 'delivery_rows_count' => 'Delivery records'],
        'Delivery / DR Packages' => ['total_packages_count' => 'Total allocations', 'pending_packages_count' => 'Pending', 'released_packages_count' => 'Released', 'delivered_packages_count' => 'Delivered', 'accepted_packages_count' => 'Accepted', 'warehouse_packages_count' => 'Warehouse status', 'completed_packages_count' => 'Delivered + accepted', 'remaining_packages_count' => 'Remaining'],
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
    <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Stock In</h3><dl class="grid gap-2 text-xs">
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Expected DR allocations</dt><dd class="font-semibold">{{ number_format($project['total_packages_count']) }}</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Stock In</dt><dd class="font-semibold">Unavailable</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Remaining</dt><dd class="font-semibold">Unknown</dd></div>
    </dl><p class="mt-3 text-xs leading-5 text-slate-500">Stock-IN item receipts cannot currently be assigned to these DRs. Warehouse package status is shown separately and does not verify stock receipt.</p></section>
    <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Timeline</h3><dl class="grid gap-2 text-xs">
        @foreach(['start_date' => 'Project Start', 'end_date' => 'Project End', 'last_dr_date' => 'Last DR recorded', 'last_delivery_date' => 'Last Delivery', 'last_billing_date' => 'Last Billing record'] as $key => $label)
            <div class="flex justify-between gap-3"><dt class="text-slate-600">{{ $label }}</dt><dd class="whitespace-nowrap font-semibold">{{ $formatDate($project[$key]) }}</dd></div>
        @endforeach
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Project Status</dt><dd class="font-semibold">{{ $project['project_status'] }}</dd></div>
    </dl></section>
</div>
<p class="mt-4 border-t border-slate-200 pt-3 text-xs text-slate-500">Billing entry includes for billing, billed and paid. Billed shows only the current billed status. Billing dates reflect recorded DR/group links, not status-update dates.</p>
