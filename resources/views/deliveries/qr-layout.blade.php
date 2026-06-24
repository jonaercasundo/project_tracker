<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>

@page {
    margin:20mm;
}

body{
    font-family: DejaVu Sans;
    font-size:12px;
}

table{
    width:100%;
    border-collapse:collapse;
}

td,th{
    border:1px solid #000;
    padding:5px;
}

.page-break{
    page-break-after:always;
}

.qr{
    text-align:center;
    width:50%;
}

</style>
</head>
<body>

@foreach($deliveries as $delivery)

<div>

    <h2>
        DR #{{ $delivery->dr_no }}
    </h2>

    <p>
        <strong>Project:</strong>
        {{ $delivery->project->project_name ?? '' }}
    </p>

    <p>
        <strong>School:</strong>
        {{ $delivery->school->school_name ?? '' }}
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

                <td>
                    Package {{ $index + 1 }}
                </td>

                <td>
                    {{ optional($status->package)->length }}
                    x
                    {{ optional($status->package)->width }}
                    x
                    {{ optional($status->package)->height }}
                </td>

            </tr>

        @endforeach

        </tbody>

    </table>

</div>

<div class="page-break"></div>

<div>

    <h2>
        QR Codes - DR #{{ $delivery->dr_no }}
    </h2>

    <table>

        @foreach($delivery->packageStatuses->chunk(2) as $chunk)

            <tr>

            @foreach($chunk as $status)

                <td class="qr">

                    <img
                        src="{{ $qrCodes[$status->package_status_id] }}"
                        width="150"
                    >

                    <br>

                    ORD-{{ str_pad($status->package_status_id,5,'0',STR_PAD_LEFT) }}

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