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
    <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Warehouse Inventory</h3><dl class="grid gap-2 text-xs">
        @foreach(['required' => 'Required item quantity', 'stock_in' => 'Stock In transactions', 'stock_out' => 'Stock Out transactions', 'opening' => 'Opening / manual inserts', 'available' => 'Current warehouse stock', 'covered' => 'Required quantity covered', 'history_balance' => 'History net balance'] as $key => $label)
            <div class="flex justify-between gap-3"><dt class="text-slate-600">{{ $label }}</dt><dd class="font-semibold tabular-nums">{{ $project['warehouse_readiness'][$key] === null ? 'By unit' : number_format($project['warehouse_readiness'][$key]) }}</dd></div>
        @endforeach
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Warehouse Readiness</dt><dd class="font-semibold">{{ $project['warehouse_readiness']['percent'] === null ? 'See requirements' : number_format($project['warehouse_readiness']['percent'], 1).'%' }}</dd></div>
    </dl><p class="mt-3 text-xs leading-5 text-slate-500">{{ $project['warehouse_readiness']['scope'] }} Quantities retain their item units; they are separate from DR package allocation counts.</p></section>
    <section><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Timeline</h3><dl class="grid gap-2 text-xs">
        @foreach(['start_date' => 'Project Start', 'end_date' => 'Project End', 'last_dr_date' => 'Last DR recorded', 'last_delivery_date' => 'Last Delivery', 'last_billing_date' => 'Last Billing record', 'last_inventory_date' => 'Last Inventory activity'] as $key => $label)
            <div class="flex justify-between gap-3"><dt class="text-slate-600">{{ $label }}</dt><dd class="whitespace-nowrap font-semibold">{{ $formatDate($project[$key]) }}</dd></div>
        @endforeach
        <div class="flex justify-between gap-3"><dt class="text-slate-600">Project Status</dt><dd class="font-semibold">{{ $project['project_status'] }}</dd></div>
    </dl></section>
</div>
<div class="mt-5 overflow-x-auto">
    <table class="w-full min-w-[650px] text-left text-xs">
        <caption class="mb-3 text-left font-bold text-slate-600">Warehouse readiness by item</caption>
        <thead class="text-slate-500"><tr><th class="p-2">Item / Unit</th><th class="p-2">Required</th><th class="p-2">Stock In</th><th class="p-2">Stock Out</th><th class="p-2">Current Stock</th><th class="p-2">Covered</th><th class="p-2">Readiness</th></tr></thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($project['warehouse_readiness']['items'] as $item)
                <tr><td class="p-2">{{ $item['item_name'] }} <span class="text-slate-500">({{ $item['unit'] ?: 'Unit unspecified' }})</span></td>
                    @foreach(['required', 'stock_in', 'stock_out', 'available', 'covered'] as $key)<td class="p-2 font-semibold tabular-nums">{{ number_format($item[$key]) }}</td>@endforeach
                    <td class="p-2">{{ $item['percent'] === null ? 'No complete requirements' : number_format($item['percent'], 1).'%' }}</td>
                </tr>
            @empty<tr><td colspan="7" class="p-2 text-slate-500">No inventory items recorded for this project.</td></tr>@endforelse
        </tbody>
    </table>
</div>
<p class="mt-4 border-t border-slate-200 pt-3 text-xs text-slate-500">Billing entry includes for billing, billed and paid. Billed shows only the current billed status. Billing dates reflect recorded DR/group links, not status-update dates.</p>
