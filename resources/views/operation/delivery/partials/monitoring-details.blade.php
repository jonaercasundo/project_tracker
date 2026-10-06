<div class="flex flex-wrap items-center gap-3">
    <label class="text-xs font-semibold text-slate-600">Records
        <select data-detail-section class="ml-2 rounded border-slate-300 text-xs">
            @foreach(['warehouse' => 'Warehouse items', 'dr' => 'DR receipts', 'stock-out' => 'Stock Out transactions', 'delivered' => 'Delivered DRs', 'billing' => 'Billing records'] as $value => $label)
                <option value="{{ $value }}" @selected($section === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-xs text-slate-600">Rows
        <select data-detail-per-page class="ml-2 rounded border-slate-300 text-xs">
            @foreach([25, 50, 100] as $size)<option value="{{ $size }}" @selected($records->perPage() === $size)>{{ $size }}</option>@endforeach
        </select>
    </label>
</div>
<div class="mt-3">@include('operation.delivery.partials.monitoring-detail-records', ['records' => $records, 'section' => $section])</div>
