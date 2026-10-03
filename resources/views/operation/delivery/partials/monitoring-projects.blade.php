<table class="w-full min-w-[980px] text-left text-sm">
    <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
        <tr><th class="px-5 py-3">Project / Contract</th><th class="px-4 py-3">Deliveries</th><th class="px-4 py-3">Packages</th><th class="px-4 py-3">Billing</th><th class="px-4 py-3">Delivery Progress</th><th class="px-4 py-3">Last Delivery</th><th class="px-4 py-3">Action</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($report['projects'] as $project)
            <tr class="align-top hover:bg-blue-50/30" data-project-row="{{ $project['project_id'] }}">
                <td class="px-5 py-4">
                    <a href="{{ route('projects.show', $project['project_id']) }}" class="font-bold text-slate-900 hover:text-blue-600">{{ $project['project_name'] }}</a>
                    <p class="mt-1 text-xs text-slate-500">{{ $project['ref_no'] ?: 'No reference recorded' }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $project['start_date'] ?: '—' }} to {{ $project['end_date'] ?: '—' }}</p>
                    <span class="mt-2 inline-block rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ $project['project_status'] }}</span>
                </td>
                <td class="px-4 py-4"><p class="font-bold tabular-nums">{{ number_format($project['total_deliveries_count']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($project['completed_deliveries_count']) }} completed receipts</p></td>
                <td class="px-4 py-4"><p class="font-bold tabular-nums">{{ number_format($project['total_packages_count']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($project['completed_packages_count']) }} completed</p><p class="mt-1 text-xs text-amber-600">{{ number_format($project['remaining_packages_count']) }} remaining</p></td>
                <td class="px-4 py-4"><p class="font-bold tabular-nums">{{ $project['billing_progress_percent'] === null ? '—' : number_format($project['billing_progress_percent'], 1).'%' }}</p><p class="mt-1 text-xs text-slate-500">{{ $project['billing_groups_count'] ? number_format($project['billing_groups_count']).' recorded groups' : 'No billing records' }}</p></td>
                <td class="px-4 py-4">
                    <div class="flex items-center justify-between gap-3"><span class="font-bold tabular-nums text-blue-700">{{ $project['delivery_progress_percent'] === null ? '—' : number_format($project['delivery_progress_percent'], 1).'%' }}</span></div>
                    <div class="mt-2 h-2 w-36 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="{{ $project['project_name'] }} delivery progress" aria-valuemin="0" aria-valuemax="100" @if($project['delivery_progress_percent'] !== null) aria-valuenow="{{ $project['delivery_progress_percent'] }}" @else aria-valuetext="No delivery allocations recorded" @endif>
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $project['delivery_progress_percent'] ?? 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ $project['progress_basis'] === 'package_allocations' ? 'Package allocations' : 'Receipt fallback' }}</p>
                </td>
                <td class="px-4 py-4 text-xs text-slate-600">{{ $project['last_delivery_date'] ?: 'Not recorded' }}</td>
                <td class="px-4 py-4">
                    <div class="flex flex-col items-start gap-2 text-xs font-semibold">
                        <a href="{{ route('projects.show', $project['project_id']) }}" class="text-blue-600 hover:underline">View Project</a>
                        <a href="{{ route('deliveries.index', $project['project_id']) }}?project={{ $project['project_id'] }}" class="text-blue-600 hover:underline">View Deliveries</a>
                        <button type="button" data-expand-project="{{ $project['project_id'] }}" aria-expanded="false" aria-controls="project-details-{{ $project['project_id'] }}" class="text-slate-500 hover:text-slate-900">Stage details</button>
                    </div>
                </td>
            </tr>
            <tr id="project-details-{{ $project['project_id'] }}" hidden class="bg-slate-50/70">
                <td colspan="7" class="px-5 py-4">
                    <div class="grid grid-cols-3 gap-6">
                        @foreach([
                            'Deliveries' => ['pending_deliveries_count' => 'Pending', 'released_deliveries_count' => 'Released', 'delivered_deliveries_count' => 'Delivered', 'accepted_deliveries_count' => 'Accepted / Completed', 'warehouse_deliveries_count' => 'Warehouse', 'mixed_deliveries_count' => 'Mixed', 'cancelled_deliveries_count' => 'Cancelled', 'for_approval_deliveries_count' => 'For approval', 'delivery_rows_count' => 'Delivery rows'],
                            'Packages' => ['total_packages_count' => 'Total Packages', 'pending_packages_count' => 'Pending Packages', 'released_packages_count' => 'Released Packages', 'delivered_packages_count' => 'Delivered Packages', 'accepted_packages_count' => 'Accepted Packages', 'warehouse_packages_count' => 'Warehouse Packages'],
                            'Billing' => ['for_billing_groups_count' => 'For Billing', 'billed_groups_count' => 'Billed', 'paid_groups_count' => 'Paid'],
                        ] as $group => $columns)
                            <div><h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $group }}</h3><dl class="grid gap-2">
                                @foreach($columns as $column => $label)
                                    @php
                                        $color = str_starts_with($column, 'pending') || str_starts_with($column, 'for_billing') ? 'text-amber-600' : (str_starts_with($column, 'released') || str_starts_with($column, 'billed') ? 'text-blue-600' : (str_starts_with($column, 'delivered') || str_starts_with($column, 'accepted') || str_starts_with($column, 'paid') ? 'text-emerald-600' : 'text-slate-600'));
                                    @endphp
                                    <div class="flex justify-between gap-4 text-xs"><dt class="{{ $color }}">{{ $label }}</dt><dd class="font-bold tabular-nums text-slate-900">{{ number_format($project[$column]) }}</dd></div>
                                @endforeach
                            </dl></div>
                        @endforeach
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-14 text-center"><p class="font-semibold text-slate-700">No projects match these filters.</p><p class="mt-2 text-xs text-slate-500">Reset the filters or turn off Active projects only to include later project stages.</p></td></tr>
        @endforelse
    </tbody>
</table>
