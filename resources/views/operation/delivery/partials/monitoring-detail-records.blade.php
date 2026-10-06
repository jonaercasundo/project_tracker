@php
    $columns = match ($section) {
        'warehouse' => ['item_name' => 'Item', 'unit' => 'Unit', 'required' => 'Required', 'stock_in' => 'Stock In', 'stock_out' => 'Stock Out', 'available' => 'Current Stock', 'covered' => 'Covered', 'percent' => 'Readiness %'],
        'stock-out' => ['history_id' => 'Transaction', 'item_name' => 'Item', 'unit' => 'Unit', 'warehouse_id' => 'Warehouse ID', 'stock_out' => 'Released quantity', 'changed_at' => 'Recorded at'],
        'delivered' => ['dr_no' => 'DR', 'delivery_status' => 'Status', 'delivery_rows_count' => 'Delivery rows', 'last_delivery_date' => 'Last delivery'],
        'billing' => ['dr_no' => 'DR', 'group_id' => 'Group ID', 'status' => 'Status', 'created_at' => 'Recorded at'],
        default => ['dr_no' => 'DR', 'delivery_status' => 'Status', 'delivery_rows_count' => 'Delivery rows', 'total_packages_count' => 'Package allocations', 'last_delivery_date' => 'Last delivery'],
    };
@endphp
@if(in_array($section, ['warehouse', 'stock-out']))
    <p class="mb-3 text-xs text-slate-500">Whole-project inventory and lifetime history. DR year, location and status filters apply to delivery and billing records.</p>
@endif
<div class="overflow-x-auto">
    <table class="w-full text-left text-xs">
        <thead class="text-slate-500"><tr>@foreach($columns as $label)<th class="whitespace-nowrap p-2">{{ $label }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($records as $record)
                <tr>@foreach($columns as $column => $label)<td class="p-2">{{ $record->{$column} ?? 'Not recorded' }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="p-2 text-slate-500">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
    <span>{{ number_format($records->firstItem() ?? 0) }}–{{ number_format($records->lastItem() ?? 0) }} of {{ number_format($records->total()) }} records</span>
    <div class="flex items-center gap-3">
        <button type="button" data-detail-page="{{ $records->currentPage() - 1 }}" @disabled($records->onFirstPage()) class="rounded border border-slate-300 px-3 py-2 disabled:opacity-40">Previous</button>
        <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
        <button type="button" data-detail-page="{{ $records->currentPage() + 1 }}" @disabled(!$records->hasMorePages()) class="rounded border border-slate-300 px-3 py-2 disabled:opacity-40">Next</button>
    </div>
</div>
