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
    padding:6px;
}

.page-break{
    page-break-after:always;
}

.qr{
    text-align:center;
    width:50%;
}

.logoimg{
    max-width:250px;
    height:80px;
}

.footer{
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    text-align:center;
    font-size:10px;
}

.no-border{
    border:none !important;
}
</style>

</head>

<body>

@foreach($deliveries as $delivery)

@php

$ar = $delivery->project->arSetting ?? null;

$logoPath = public_path('logo.png');

if (
    $ar &&
    !empty($ar->ar_logo) &&
    file_exists(public_path('uploads/logo/' . $ar->ar_logo))
) {
    $logoPath = public_path('uploads/logo/' . $ar->ar_logo);
}

$logoBase64 = '';

if (file_exists($logoPath)) {
    $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));

    $mime = match ($extension) {
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        default => 'image/png'
    };

    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
}

@endphp

{{-- ========================================= --}}
{{-- PAGE 1 --}}
{{-- ========================================= --}}

<div>

    <div style="text-align:center;">
        <img src="{{ $logoPath }}" class="logoimg">
    </div>

    <div style="text-align:right;">
        <small>Date: {{ now()->format('Y-M-d') }}</small>
        <br>

        @if(optional($ar)->display_school_id)
            <small>
                AR: {{ $delivery->school_id }}
            </small>
        @endif
    </div>

    <table class="no-border">

        <tr>
            <td class="no-border" width="120">
                <strong>Project:</strong>
            </td>

            <td class="no-border">
                <strong>
                    {{ $ar->project_name ?? $delivery->project->project_name ?? '' }}
                </strong>
            </td>
        </tr>

    </table>

    <h3 style="text-align:center;">
        ACKNOWLEDGEMENT OF RECEIPT OF GOODS
    </h3>

    <p>

        The undersigned hereby acknowledges the receipt of goods
        pursuant to Contract No.

        {{ $delivery->lot->contract_no ?? '' }}

        @if(!empty($delivery->lot->lot_name))
            (LOT {{ $delivery->lot->lot_name }})
        @endif

        between

        {{ $ar->company ?? '' }}

        and

        {{ $ar->client ?? '' }}.

    </p>

    @if(optional($ar)->display_label)

        <p>

            <strong>School Name:</strong>
            {{ $delivery->school->school_name ?? '' }}

            <br>

            <strong>School Address:</strong>
            {{ $delivery->school->address ?? '' }}

            @if(optional($ar)->display_school_id)

                <br>

                <strong>School ID:</strong>
                {{ $delivery->school_id }}

            @endif

        </p>

    @endif
        <p>
        Delivery ID: {{ $delivery->delivery_id }}<br>
        </p>


        @php
            $prefix = implode('-', array_slice(explode('-', $delivery->school_id), 0, 2));

            $firstItem = $delivery->items[0] ?? null;
            $secondItem = $delivery->items[1] ?? null;
        @endphp
        <table>
            @if($delivery->items->isNotEmpty() && $prefix === 'TX-LOT13')
                <thead>
                    <tr>
                        <th colspan="2" style="text-align: left;">
                            Grade 2 Makabansa
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td class="w-1/2">
                            {{ $firstItem?->item_name }}
                        </td>

                        <td align="center">
                            @php
                                $package1 = $firstItem?->packageContent?->package;
                            @endphp

                            {{ $package1?->length }} × {{ $package1?->width }} × {{ $package1?->height }}

                            <hr style="margin:3px 0;">

                            {{ $delivery->package_qty }}
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/2">
                            {{ $secondItem?->item_name }}
                        </td>

                        <td align="center">
                            @php
                                $package2 = $secondItem?->packageContent?->package;
                            @endphp

                            {{ $package2?->length }} × {{ $package2?->width }} × {{ $package2?->height }}

                            <hr style="margin:3px 0;">

                            {{ $delivery->qty_teachers_manual }}
                        </td>
                    </tr>
                </tbody>       
            @endif
            @if($delivery->items->isNotEmpty() && $prefix === 'TX-LOT12')
                <thead>
                    <tr>
                        <th colspan="2" style="text-align: left;">
                            Grade 2 Filipino
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td class="w-1/2">
                            {{ $firstItem?->item_name }}
                        </td>

                        <td align="center">
                            @php
                                $package1 = $firstItem?->packageContent?->package;
                            @endphp

                            {{ $package1?->length }} × {{ $package1?->width }} × {{ $package1?->height }}

                            <hr style="margin:3px 0;">

                            {{ $delivery->package_qty }}
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/2">
                            {{ $secondItem?->item_name }}
                        </td>

                        <td align="center">
                            @php
                                $package2 = $secondItem?->packageContent?->package;
                            @endphp

                            {{ $package2?->length }} × {{ $package2?->width }} × {{ $package2?->height }}

                            <hr style="margin:3px 0;">

                            {{ $delivery->qty_teachers_manual }}
                        </td>
                    </tr>
                </tbody>       
            @endif
        </table>

    <div class="footer">

        <table style="border:none;">

            <tr>

                <td style="border:none;">
                    Printed Name Over Signature
                </td>

                <td style="border:none;">
                    {{ $signerName }}
                    <br>
                    {{ $ar->ar_company_footer ?? 'Metro Mobilia Corporation' }}
                </td>

            </tr>

        </table>

        <small>

            {{ $ar->ar_address_footer ?? '' }}

            <br>

            {{ $ar->ar_contact_footer ?? '' }}

        </small>

    </div>

</div>

<div class="page-break"></div>

{{-- ========================================= --}}
{{-- PAGE 2 --}}
{{-- ========================================= --}}

<div>

    <div style="text-align:right;">

        <small>
            Date: {{ now()->format('Y-M-d') }}
        </small>

        <br>

        <small>
            DR: {{ $delivery->dr_no }}
        </small>

    </div>

    <h3>
        QR Codes - DR #{{ $delivery->dr_no }}
    </h3>

    <table>

        @foreach($delivery->packageStatuses->chunk(2) as $chunk)

            <tr>

                @foreach($chunk as $status)

                    <td class="qr">

                        @if(isset($qrCodes[$status->package_status_id]))

                            <img
                                src="{{ $qrCodes[$status->package_status_id] }}"
                                width="150"
                            >

                        @endif

                        <br>

                        Package

                        <br>

                        ORD-{{ str_pad($status->package_status_id,5,'0',STR_PAD_LEFT) }}

                    </td>

                @endforeach

                @if($chunk->count() == 1)
                    <td></td>
                @endif

            </tr>

        @endforeach

    </table>

</div>

<div class="page-break"></div>

@endforeach

</body>
</html>