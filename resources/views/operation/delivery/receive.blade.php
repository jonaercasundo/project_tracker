<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0d6efd">
    <title>Receive Delivery</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>

    body{
        background:#f4f6f9;
        /* Room at the bottom for the fixed action bar */
        padding-bottom:96px;
    }

    .container{
        max-width:720px;
    }

    .page-header{
        background:#0d6efd;
        color:#fff;
        border-radius:14px;
        padding:18px 20px;
    }

    .page-header h2{
        font-size:1.25rem;
    }

    .page-header p{
        font-size:.9rem;
    }

    .card{
        border:none;
        border-radius:14px;
        box-shadow:0 4px 14px rgba(0,0,0,.07);
    }

    .card-body{
        padding:16px;
    }

    .section-title{
        font-weight:700;
        margin-bottom:14px;
        font-size:1.05rem;
    }

    .status-good{
        color:#198754;
        font-weight:600;
    }

    .status-bad{
        color:#dc3545;
        font-weight:600;
    }

    .badge-lg{
        font-size:.85rem;
        padding:.5rem .75rem;
    }

    /* --- Collapsible info sections (accordion-style) --- */

    .info-toggle{
        display:flex;
        align-items:center;
        justify-content:space-between;
        width:100%;
        background:none;
        border:none;
        padding:0;
        text-align:left;
    }

    .info-toggle .chevron{
        transition:transform .2s ease;
    }

    .info-toggle[aria-expanded="true"] .chevron{
        transform:rotate(180deg);
    }

    .info-rows{
        margin-top:12px;
    }

    .info-rows dt{
        font-size:.78rem;
        color:#6c757d;
        text-transform:uppercase;
        letter-spacing:.03em;
        margin-top:10px;
    }

    .info-rows dt:first-child{
        margin-top:0;
    }

    .info-rows dd{
        font-size:1rem;
        margin-bottom:0;
    }

    /* --- Packing list: card rows instead of a table, so nothing scrolls sideways --- */

    .packing-item{
        display:flex;
        align-items:flex-start;
        gap:12px;
        padding:12px 0;
        border-bottom:1px solid #eef0f3;
    }

    .packing-item:last-child{
        border-bottom:none;
        padding-bottom:0;
    }

    .item-icon{
        flex:0 0 auto;
        width:42px;
        height:42px;
        background:#eef4ff;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:10px;
        font-size:20px;
    }

    .packing-item .item-body{
        flex:1 1 auto;
        min-width:0;
    }

    .packing-item .item-name{
        font-weight:600;
        display:block;
        margin-bottom:4px;
    }

    .packing-item .item-meta{
        display:flex;
        flex-wrap:wrap;
        gap:6px 14px;
        font-size:.85rem;
        color:#495057;
    }

    /* --- Big, thumb-friendly confirm checkbox --- */

    .confirm-row{
        display:flex;
        align-items:center;
        gap:12px;
    }

    .confirm-row input[type="checkbox"]{
        width:1.4rem;
        height:1.4rem;
        flex:0 0 auto;
        margin-top:0;
    }

    .confirm-row label{
        font-size:.95rem;
    }

    /* --- Photo picker --- */

    .photo-picker{
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:6px;
        border:2px dashed #c9d2db;
        border-radius:12px;
        padding:22px 12px;
        color:#495057;
        text-align:center;
        cursor:pointer;
    }

    .photo-picker input[type="file"]{
        display:none;
    }

    #previewContainer img{
        aspect-ratio:1/1;
        object-fit:cover;
        width:100%;
    }

    /* --- Fixed bottom action bar, always reachable with one thumb --- */

    .action-bar{
        position:fixed;
        left:0;
        right:0;
        bottom:0;
        background:#fff;
        box-shadow:0 -4px 16px rgba(0,0,0,.08);
        padding:12px 16px calc(12px + env(safe-area-inset-bottom));
        z-index:1030;
    }

    .action-bar .btn{
        width:100%;
        padding:.85rem;
        font-size:1.05rem;
        border-radius:12px;
    }

    </style>

</head>
<body>

<div class="container py-3 px-3">

    {{-- Feedback from the last submission attempt --}}

    @if(session('success'))

        <div class="alert alert-success mb-3" role="alert">
            {{ session('success') }}
        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger mb-3" role="alert">

            <strong class="d-block mb-1">There was a problem submitting this delivery:</strong>

            <ul class="mb-0 ps-3">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif

    {{-- HEADER --}}

    <div class="page-header mb-3">

        <div class="d-flex align-items-start justify-content-between gap-2">

            <div>

                <h2 class="fw-bold mb-1">
                    📦 Delivery Confirmation
                </h2>

                <p class="mb-0">
                    Scan completed. Verify the delivery before submitting.
                </p>

            </div>

            <span class="badge bg-light text-dark badge-lg text-nowrap">
                {{ ucfirst($packageStatus->status) }}
            </span>

        </div>

    </div>


    {{-- School info: collapsed by default on mobile to save scroll --}}

    <div class="card mb-3">

        <div class="card-body">

            <button
                class="info-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#schoolInfo"
                aria-expanded="false"
                aria-controls="schoolInfo"
            >
                <h5 class="section-title mb-0">🏫 School Information</h5>
                <span class="chevron">▾</span>
            </button>

            <div class="collapse" id="schoolInfo">

                <dl class="info-rows">

                    <dt>School</dt>
                    <dd>{{ $packageStatus->delivery->school->school_name }}</dd>

                    <dt>Address</dt>
                    <dd>{{ $packageStatus->delivery->school->address }}</dd>

                    <dt>Division</dt>
                    <dd>{{ optional($packageStatus->delivery->school->division)->division_name }}</dd>

                    <dt>Region</dt>
                    <dd>{{ optional($packageStatus->delivery->school->region)->region_name }}</dd>

                </dl>

            </div>

        </div>

    </div>


    {{-- Delivery info: expanded by default, it's what the rider checks first --}}

    <div class="card mb-3">

        <div class="card-body">

            <button
                class="info-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#deliveryInfo"
                aria-expanded="true"
                aria-controls="deliveryInfo"
            >
                <h5 class="section-title mb-0">🚚 Delivery Information</h5>
                <span class="chevron">▾</span>
            </button>

            <div class="collapse show" id="deliveryInfo">

                <dl class="info-rows">

                    <dt>DR Number</dt>
                    <dd>{{ $packageStatus->delivery->dr_no }}</dd>

                    <dt>Project</dt>
                    <dd>{{ $packageStatus->delivery->project->project_name }}</dd>

                    <dt>Package</dt>
                    <dd>{{ $packageStatus->package->package_name }}</dd>

                    <dt>Package Type</dt>
                    <dd>{{ $packageStatus->delivery->package_type }}</dd>

                    <dt>Delivery Qty</dt>
                    <dd>{{ $packageStatus->delivery->package_qty }}</dd>

                </dl>

            </div>

        </div>

    </div>


    {{-- Packing list as stacked rows, no side-scrolling table --}}

    <div class="card mb-3">

        <div class="card-body">

            <h4 class="section-title">📋 Packing List</h4>

            @foreach($items as $item)

                @php

                    $required = $item['required_qty'];

                    $available = $inventory[$item['item_id']] ?? 0;

                    $ok = $available >= $required;

                @endphp

                <div class="packing-item">

                    <div class="item-icon">📦</div>

                    <div class="item-body">

                        <span class="item-name">{{ $item['item_name'] }}</span>

                        <div class="item-meta">

                            <span>Quantity: <strong>{{ $required }}</strong></span>
                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

    <form
        method="POST"
        action="{{ route('delivery.receive.store',$packageStatus->package_status_id) }}"
        enctype="multipart/form-data"
        id="deliveryForm"
    >

        @csrf


        <div class="card mb-3">

            <div class="card-body">

                <h4 class="section-title">📷 Delivery Proof</h4>

                <label class="photo-picker" for="photos">

                    <span style="font-size:1.6rem;">📷</span>
                    <span><strong>Tap to take or choose photos</strong></span>
                    <span class="text-muted small">You can add more than one</span>

                    <input
                        type="file"
                        name="photos[]"
                        id="photos"
                        multiple
                        accept="image/*"
                        capture="environment"
                    >

                </label>

                <div class="row g-2 mt-1" id="previewContainer"></div>

            </div>

        </div>


        <div class="card mb-3">

            <div class="card-body">

                <h4 class="section-title">📝 Remarks</h4>

                <textarea
                    name="remarks"
                    rows="3"
                    class="form-control"
                    placeholder="Optional remarks..."
                ></textarea>

            </div>

        </div>


        {{-- Fixed action bar keeps the submit button reachable while scrolling --}}

        <div class="action-bar">

            <button
                class="btn btn-success btn-lg"
                id="submitBtn">

                Confirm Delivery

            </button>

        </div>

    </form>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('deliveryForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = 'Submitting...';
    });
</script>
<script>

photos.addEventListener('change',function(){

    previewContainer.innerHTML='';

    [...this.files].forEach(file=>{

        let reader=new FileReader();

        reader.onload=function(e){

            previewContainer.innerHTML+=`
                <div class="col-4 col-md-3">
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
</body>
</html>
