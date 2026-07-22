@extends('layouts.app')

@section('title', 'Receive Delivery')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
<style>

body{
    background:#f4f6f9;
}

.page-header{
    background:#0d6efd;
    color:#fff;
    border-radius:15px;
    padding:25px;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 5px 18px rgba(0,0,0,.08);
}

.table td,
.table th{
    vertical-align:middle;
}

.badge-lg{
    font-size:.9rem;
    padding:.55rem .8rem;
}

.status-good{
    color:#198754;
    font-weight:600;
}

.status-bad{
    color:#dc3545;
    font-weight:600;
}

.item-icon{
    width:42px;
    height:42px;
    background:#eef4ff;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    font-size:20px;
}

.section-title{
    font-weight:700;
    margin-bottom:20px;
}

</style>
@endpush

@section('content')

<div class="container py-4">

    {{-- HEADER --}}

    <div class="page-header mb-4">

        <div class="row align-items-center">

            <div class="col-md-8">

                <h2 class="fw-bold mb-2">
                    📦 Delivery Confirmation
                </h2>

                <p class="mb-0">

                    Scan completed successfully.

                    Please verify the delivery before submitting.

                </p>

            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <span class="badge bg-light text-dark badge-lg">

                    {{ ucfirst($packageStatus->status) }}

                </span>

            </div>

        </div>

    </div>


    <div class="row">

        {{-- LEFT COLUMN --}}

        <div class="col-lg-4 mb-4">

            {{-- School --}}

            <div class="card mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        🏫 School Information
                    </h5>

                    <table class="table table-borderless">

                        <tr>

                            <th width="35%">School</th>

                            <td>

                                {{ $packageStatus->delivery->school->school_name }}

                            </td>

                        </tr>

                        <tr>

                            <th>Address</th>

                            <td>

                                {{ $packageStatus->delivery->school->address }}

                            </td>

                        </tr>

                        <tr>

                            <th>Division</th>

                            <td>

                                {{ optional($packageStatus->delivery->school->division)->division_name }}

                            </td>

                        </tr>

                        <tr>

                            <th>Region</th>

                            <td>

                                {{ optional($packageStatus->delivery->school->region)->region_name }}

                            </td>

                        </tr>

                    </table>

                </div>

            </div>


            {{-- Delivery --}}

            <div class="card">

                <div class="card-body">

                    <h5 class="section-title">

                        🚚 Delivery Information

                    </h5>

                    <table class="table table-borderless">

                        <tr>

                            <th width="40%">

                                DR Number

                            </th>

                            <td>

                                {{ $packageStatus->delivery->dr_no }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Project

                            </th>

                            <td>

                                {{ $packageStatus->delivery->project->project_name }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Package

                            </th>

                            <td>

                                {{ $packageStatus->package->package_name }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Package Type

                            </th>

                            <td>

                                {{ $packageStatus->delivery->package_type }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Delivery Qty

                            </th>

                            <td>

                                {{ $packageStatus->delivery->package_qty }}

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

        </div>


        {{-- RIGHT COLUMN --}}

        <div class="col-lg-8">

            <div class="card">

                <div class="card-body">

                    <h4 class="section-title">

                        📋 Packing List

                    </h4>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead class="table-light">

                            <tr>

                                <th width="55%">

                                    Item

                                </th>

                                <th class="text-center">

                                    Required

                                </th>

                                <th class="text-center">

                                    Available

                                </th>

                                <th class="text-center">

                                    Status

                                </th>

                            </tr>

                            </thead>

                            <tbody>

                            @foreach($items as $item)

                                @php

                                    $required = $item['required_qty'];

                                    $available = $inventory[$item['item_id']] ?? 0;

                                    $ok = $available >= $required;

                                @endphp

                                <tr>

                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div class="item-icon me-3">

                                                📦

                                            </div>

                                            <div>

                                                <strong>

                                                    {{ $item['item_name'] }}

                                                </strong>

                                            </div>

                                        </div>

                                    </td>

                                    <td class="text-center">

                                        {{ $required }}

                                    </td>

                                    <td class="text-center">

                                        {{ $available }}

                                    </td>

                                    <td class="text-center">

                                        @if($ok)

                                            <span class="status-good">

                                                ✔ Available

                                            </span>

                                        @else

                                            <span class="status-bad">

                                                ✖ Insufficient

                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <form
    method="POST"
    action="{{ route('delivery.receive.store',$packageStatus->package_status_id) }}"
    enctype="multipart/form-data"
>

@csrf

<input type="hidden" name="latitude" id="latitude">
<input type="hidden" name="longitude" id="longitude">
<input type="hidden" name="accuracy" id="accuracy">

<div class="card mt-4">

    <div class="card-body">

        <h4 class="section-title">

            📍 Delivery Location

        </h4>

        <div
            id="map"
            style="height:350px;border-radius:12px;"
        ></div>

        <div class="alert alert-info mt-3">

            <strong>Status:</strong>

            <span id="gpsStatus">

                Waiting for GPS...

            </span>

        </div>

    </div>

</div>


<div class="card mt-4">

    <div class="card-body">

        <h4 class="section-title">

            📷 Delivery Proof

        </h4>

        <input
            type="file"
            class="form-control"
            name="photos[]"
            id="photos"
            multiple
            accept="image/*"
        >

        <div
            class="row mt-3"
            id="previewContainer"
        ></div>

    </div>

</div>


<div class="card mt-4">

    <div class="card-body">

        <h4 class="section-title">

            📝 Remarks

        </h4>

        <textarea
            name="remarks"
            rows="3"
            class="form-control"
            placeholder="Optional remarks..."
        ></textarea>

    </div>

</div>


<div class="card mt-4">

    <div class="card-body">

        <div class="form-check">

            <input
                class="form-check-input"
                type="checkbox"
                id="confirmDelivery">

            <label
                class="form-check-label"
                for="confirmDelivery">

                I confirm that the package has been successfully delivered.

            </label>

        </div>

    </div>

</div>


<div class="text-end mt-4">

    <button
        class="btn btn-success btn-lg px-5"
        id="submitBtn"
        disabled>

        Confirm Delivery

    </button>

</div>

</form>

    <div class="card mt-4">

        <div class="card-body">

            <h4>

                📍 Rider Location

            </h4>

            <hr>

            <p class="text-muted mb-0">

                Live GPS map will be added in Part 2.

            </p>

        </div>

    </div>

</div>

@endsection
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

let map = L.map('map').setView([14.5995,120.9842],15);

L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
{
    attribution:'© OpenStreetMap'
}
).addTo(map);

let marker;

let gpsReady=false;

const submitBtn=document.getElementById('submitBtn');

const confirm=document.getElementById('confirmDelivery');

function updateButton(){

    submitBtn.disabled=!(gpsReady && confirm.checked);

}

confirm.addEventListener('change',updateButton);

navigator.geolocation.watchPosition(

function(position){

    let lat=position.coords.latitude;

    let lng=position.coords.longitude;

    let acc=Math.round(position.coords.accuracy);

    gpsReady=true;

    latitude.value=lat;

    longitude.value=lng;

    accuracy.value=acc;

    gpsStatus.innerHTML="GPS Ready ("+acc+"m accuracy)";

    if(marker){

        marker.setLatLng([lat,lng]);

    }else{

        marker=L.marker([lat,lng]).addTo(map);

    }

    map.setView([lat,lng],18);

    updateButton();

},

function(){

    gpsReady=false;

    gpsStatus.innerHTML="Unable to get GPS";

    updateButton();

},

{

enableHighAccuracy:true,

maximumAge:0,

timeout:10000

}

);

</script>
<script>

photos.addEventListener('change',function(){

    previewContainer.innerHTML='';

    [...this.files].forEach(file=>{

        let reader=new FileReader();

        reader.onload=function(e){

            previewContainer.innerHTML+=`
                <div class="col-md-3 mb-3">
                    <img
                        src="${e.target.result}"
                        class="img-fluid rounded shadow">
                </div>
            `;

        };

        reader.readAsDataURL(file);

    });

});

</script>