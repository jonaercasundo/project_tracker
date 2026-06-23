<x-app-layout>
<div class="space-y-6">

    <div class="flex justify-between">
        <h1 class="text-xl font-bold">Deliveries</h1>

        <div class="flex gap-2">
            <button class="btn btn-primary">Add Delivery</button>
            <button class="btn btn-primary">Batch Delivery</button>
            <button class="btn btn-primary">Import</button>
        </div>
    </div>
<table class="table table-bordered">

<thead class="table-dark">
<tr>
    <th></th>
    <th>Delivery Details</th>
    <th>Items</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

@foreach($grouped_deliveries as $dr_group)

<tr class="table-secondary fw-bold">
    <td colspan="1">
        <input type="checkbox"
               class="form-check-input dr-checkbox"
               value="{{ $dr_group['dr_no'] }}"
               data-school-id="{{ $dr_group['school_id'] }}">
    </td>

    <td colspan="2">
        DR No: {{ $dr_group['dr_no'] }}
        — Project: {{ $dr_group['project_name'] }}
        — School: {{ $dr_group['school_name'] }}
    </td>

    <td>
        <button class="btn btn-secondary btn-sm" onclick="generateARs()">QR</button>
        <button class="btn btn-secondary btn-sm" onclick="generateLabels()">Label</button>
    </td>
</tr>

@foreach($dr_group['deliveries'] as $d)

<tr>
    <td></td>

    <td>
        LOT {{ $d->lot_name }}
        @if($d->keystage_num)
            Keystage {{ $d->keystage_num }} {{ $d->description }}
        @endif
    </td>

    <td>
        {!! $d->items_contents ?? '<em>No items</em>' !!}
    </td>

    <td>
        @if(auth()->user()->hasAnyRole(['Super Admin','Office Admin','Office Coordinator','Warehouse Admin']))
            <button class="btn btn-warning btn-sm">Edit</button>
        @endif

        <a class="btn btn-info btn-sm"
           href="{{ url('deliveries_details/'.$d->dr_no) }}">
            View
        </a>
    </td>
</tr>

@endforeach

</tbody>
</table>