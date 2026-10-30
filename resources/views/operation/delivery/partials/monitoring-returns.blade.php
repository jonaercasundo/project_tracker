<section class="space-y-4" data-return-panel data-project="{{ $project }}" data-store-url="{{ route('deliveries.monitoring.returns.store', ['project' => $project]) }}">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="font-bold text-slate-900">Record a return</h3>
        <p class="mt-1 text-xs text-slate-500">Item Returns affect stock only after staff confirm physical receipt. Document Returns never change inventory. School ID links to the most recent delivery in this project; item and quantity are recorded when an Item Return is received.</p>
        <form data-return-create method="POST" action="{{ route('deliveries.monitoring.returns.store', ['project' => $project]) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @csrf
            <label class="text-xs font-semibold text-slate-600">Return type
                <select name="return_type" required class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    <option value="Item Return">Item Return</option>
                    <option value="Document Return">Document Return</option>
                </select>
            </label>
            <label class="text-xs font-semibold text-slate-600">School ID
                <input name="school_id" data-return-school type="text" required maxlength="100" autocomplete="off" placeholder="Enter School ID" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
            </label>
            <label class="text-xs font-semibold text-slate-600 sm:col-span-2 xl:col-span-4">Reason
                <textarea name="reason" required maxlength="2000" rows="3" class="mt-1 w-full resize-y rounded-lg border-slate-200 text-sm"></textarea>
            </label>
            <div class="flex items-end"><button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Record return</button></div>
            <p data-return-feedback role="status" aria-live="polite" class="sm:col-span-2 xl:col-span-4 text-sm text-slate-600"></p>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h3 class="font-bold text-slate-900">Return monitoring</h3>
            <form data-return-filter method="GET" action="{{ route('deliveries.return-monitoring') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="project_id" value="{{ $project }}">
                <input name="school_id" type="search" value="{{ $filters['school_id'] ?? '' }}" maxlength="100" placeholder="School ID" aria-label="Filter by School ID" class="w-32 rounded-lg border-slate-200 text-xs">
                <select name="return_type" aria-label="Filter by return type" class="rounded-lg border-slate-200 text-xs"><option value="">All return types</option><option value="Item Return" @selected(($filters['return_type'] ?? '') === 'Item Return')>Item Return</option><option value="Document Return" @selected(($filters['return_type'] ?? '') === 'Document Return')>Document Return</option></select>
                <select name="return_status" aria-label="Filter by return status" class="rounded-lg border-slate-200 text-xs"><option value="">All return statuses</option>@foreach(['Reported', 'In Transit', 'Received', 'Rejected'] as $status)<option value="{{ $status }}" @selected(($filters['return_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
                <button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white">Filter</button>
            </form>
        </div>
        <div data-return-records class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-xs">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="p-2">School ID</th><th class="p-2">DR</th><th class="p-2">Return Type</th><th class="p-2">Item / Quantity</th><th class="p-2">Reason / Remarks</th><th class="p-2">Status</th><th class="p-2">Monitoring Date</th><th class="p-2">Received Date / By</th><th class="p-2">Update</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        <tr class="align-top">
                            <td class="p-2 font-semibold">{{ $record->school_id ?: '—' }}</td>
                            <td class="p-2 font-semibold">{{ $record->dr_no ?: 'Delivery '.$record->delivery_id }}</td>
                            <td class="p-2">{{ $record->return_type }}<br><span class="text-slate-500">Lot {{ $record->lot_id ?? '—' }} · Package {{ $record->package_id ?? '—' }}</span></td>
                            <td class="p-2">{{ $record->return_type === 'Document Return' ? 'Document' : ($record->item?->item_name ?? 'Item not recorded') }}<br>{{ $record->quantity_returned ?? '—' }}</td>
                            <td class="p-2">{{ $record->reason }}<br><span class="text-slate-500">{{ $record->remarks ?: '—' }}</span></td>
                            <td class="p-2">{{ $record->status }}</td>
                            <td class="p-2">{{ $record->monitoring_date?->format('M d, Y') }}</td>
                            <td class="p-2">{{ $record->received_date?->format('M d, Y') ?? 'Not received' }}<br><span class="text-slate-500">{{ $record->received_by ?: '—' }}{{ $record->warehouse?->warehouse_name ? ' · '.$record->warehouse->warehouse_name : '' }}</span></td>
                            <td class="p-2">
                                @if($record->status !== 'Received')
                                    <form data-return-update method="POST" action="{{ route('deliveries.monitoring.returns.update', ['project' => $project, 'return' => $record->return_id]) }}" data-url="{{ route('deliveries.monitoring.returns.update', ['project' => $project, 'return' => $record->return_id]) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="w-full rounded border-slate-200 text-xs"><option @selected($record->status === 'Reported')>Reported</option><option @selected($record->status === 'In Transit')>In Transit</option><option>Received</option><option>Rejected</option></select>
                                        <input name="monitoring_date" type="date" required value="{{ now()->toDateString() }}" title="Monitoring date" class="w-full rounded border-slate-200 text-xs">
                                        <div data-return-receipt-fields class="space-y-2">
                                            <input name="received_date" type="date" title="Date physically received" class="w-full rounded border-slate-200 text-xs">
                                            <input name="received_by" maxlength="255" placeholder="Received by" class="w-full rounded border-slate-200 text-xs">
                                            @if($record->return_type === 'Item Return')
                                                <select name="item_id" class="w-full rounded border-slate-200 text-xs"><option value="">Choose returned item</option>@foreach($items as $item)<option value="{{ $item->item_id }}" @selected($record->item_id === $item->item_id)>{{ $item->item_name }}{{ $item->unit ? ' ('.$item->unit.')' : '' }}</option>@endforeach</select>
                                                <input name="quantity_returned" type="number" min="1" value="{{ $record->quantity_returned }}" placeholder="Quantity returned" class="w-full rounded border-slate-200 text-xs">
                                                <select name="received_warehouse_id" class="w-full rounded border-slate-200 text-xs"><option value="">Choose receiving warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>@endforeach</select>
                                            @endif
                                        </div>
                                        <button class="rounded bg-slate-800 px-2 py-1 text-xs font-semibold text-white">Save</button>
                                    </form>
                                @else
                                    <span class="text-slate-500">Receipt locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="p-4 text-center text-slate-500">No returns match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500"><span>Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }} returns</span>{{ $records->links() }}</div>
        </div>
    </div>
</section>
