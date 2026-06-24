<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>
@page { margin:20mm; }

body {
    font-family: DejaVu Sans;
    font-size: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td,th {
    border: 1px solid #000;
    padding: 5px;
}

.page-break {
    page-break-after: always;
}

.qr {
    text-align: center;
    width: 50%;
}

.logoimg {
    max-width: 250px;
    height: 80px;
}
</style>
</head>

<body>

@foreach($deliveries as $delivery)

{{-- ================= AR PAGE ================= --}}
<div>

    <div style="text-align:center;">
        {{-- Replace with your logo --}}
        <img class="logoimg" src="{{ public_path('logo.webp') }}">
    </div>

    <div style="text-align:right;">
        <small>Date: {{ now()->format('Y-M-d') }}</small><br>
        <small>AR: {{ $delivery->school_id }}</small>
    </div>

    <table>
        <tr>
            <td><b>Project:</b></td>
            <td><b>{{ $delivery->project->project_name ?? '' }}</b></td>
        </tr>
    </table>

    <h3 style="text-align:center;">
        ACKNOWLEDGEMENT OF RECEIPT OF GOODS
    </h3>

    <p>
        The undersigned hereby acknowledges the receipt of goods
        pursuant to Contract No. {{ $delivery->contract_no ?? '' }}
    </p>

    <p>
        School Name: {{ $delivery->school->school_name ?? '' }} <br>
        School Address: {{ $delivery->school->address ?? '' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Package</th>
                <th>Dimensions</th>
            </tr>
        </thead>

        <tbody>
        @foreach($delivery->packageStatuses as $index => $status)
            <tr>
                <td>{{ $status->package_label }}</td>

                <td>
                    {{ optional($status->package)->length ?? '-' }}
                    x
                    {{ optional($status->package)->width ?? '-' }}
                    x
                    {{ optional($status->package)->height ?? '-' }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

<div class="page-break"></div>

{{-- ================= QR PAGE ================= --}}
<div>

    <h3>QR Codes - DR #{{ $delivery->dr_no }}</h3>

    <table>

        @foreach(collect($delivery->packageStatuses)->chunk(2) as $chunk)

            <tr>

                @foreach($chunk as $status)

                    <td class="qr">

                        <img src="{{ $qrCodes[$status->package_status_id] ?? '' }}" width="150">

                        <br>

                        {!! $status->package_label !!}

                        <br>

                        ORD-{{ str_pad($status->package_status_id, 5, '0', STR_PAD_LEFT) }}

                    </td>

                @endforeach

            </tr>

        @endforeach

    </table>

</div>

<div class="page-break"></div>

@endforeach

</body>
</html>