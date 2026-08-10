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

.keystage-header{
    background:#eee;
    font-weight:bold;
}

.package-row td{
    background:#f7f7f7;
}
</style>

</head>

<body>

@php
    // $deliveries is FLAT: one row per delivery per keystage (Delivery::keystage is belongsTo).
    // A single DR with 2 keystages = 2 Delivery rows sharing the same dr_no.
    // Group them here so page 1 / page 2 are built once per DR, with each
    // keystage rendered as a sub-section underneath — mirrors deliveries_batch.php.
    $drGroups = $deliveries->groupBy('dr_no');
@endphp

@foreach($drGroups as $drNo => $drDeliveries)
    @php

        $first = $drDeliveries->first();
        $ar    = $first->project->arSetting ?? null;

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
    {{-- PAGE 1 — Acknowledgement of Receipt --}}
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
                    AR: {{ preg_replace('/^TX-LOT\d+-/', '', $first->school_id) }}
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
                        {{ $ar->project_name ?? $first->project->project_name ?? '' }}
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

            {{ $first->lot->contract_no ?? '' }}

            @if(!empty($first->lot->lot_name))
                (LOT {{ $first->lot->lot_name }})
            @endif

            between

            {{ $ar->company ?? '' }}

            and

            {{ $ar->client ?? '' }}.

        </p>

        @if(optional($ar)->display_label)

            <p>

                <strong>School Name:</strong>
                {{ $first->school->school_name ?? '' }}

                <br>

                <strong>School Address:</strong>
                {{ $first->school->address ?? '' }}
            </p>

        @endif

        @foreach($drDeliveries as $delivery)

            @php
                // package_type holds something like "Box of 2" — same numeric-extraction
                // logic as deliveries_batch.php, applied per keystage row.
                $multiplier = 1;
                if (!empty($delivery->package_type)) {
                    $numeric    = preg_replace('/[^0-9]/', '', $delivery->package_type);
                    $multiplier = $numeric !== '' ? (int) $numeric : 1;
                }

                $keystageLabel = $delivery->keystage
                    ? trim($delivery->keystage->keystage_num . ' ' . $delivery->package_type . ' ' . ($delivery->keystage->description ?? ''))
                    : '';

                $packageCount = $delivery->packageStatuses->count();
            @endphp

            @if($keystageLabel)
                <p><strong>Keystage {{ $keystageLabel }}</strong></p>
            @endif

            <table>
                @foreach($delivery->packageStatuses as $i => $status)
                    @php $pkg = $status->package; @endphp

                    <tr class="package-row">
                        <td style="width:50%;">
                            <small>Package {{ $i + 1 }} of {{ $packageCount }}</small>
                        </td>
                        <td style="width:50%; text-align:center;">
                            @if($pkg)
                                <small>
                                    {{ $pkg->length ?? 'N/A' }} ×
                                    {{ $pkg->width ?? 'N/A' }} ×
                                    {{ $pkg->height ?? 'N/A' }}
                                    {{ $pkg->unit ?? 'cm' }}
                                </small>
                            @else
                                <small>Dimensions: N/A</small>
                            @endif
                        </td>
                    </tr>

                    @foreach($pkg->packageContent ?? [] as $content)
                        <tr>
                            <td style="width:80%;">
                                {{ $content->item->item_name ?? '' }}
                            </td>
                            <td style="width:20%; text-align:center;">
                                {{ ($content->qty ?? 0) * $multiplier }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </table>

        @endforeach

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
    {{-- PAGE 2 — QR Codes, grouped by keystage --}}
    {{-- ========================================= --}}

    <div>

        <div style="text-align:right;">
            <small>Date: {{ now()->format('Y-M-d') }}</small>
            <br>
            <small>DR: {{ $drNo }}</small>
        </div>

        @foreach($drDeliveries as $delivery)

            @php
                $keystageLabel = $delivery->keystage
                    ? 'Keystage ' . $delivery->keystage->keystage_num . ' ' . strtok($delivery->keystage->description ?? '', ' ')
                    : '';
            @endphp

            @if($keystageLabel)
                <table>
                    <tr>
                        <td class="keystage-header" colspan="2">
                            {{ $keystageLabel }}
                        </td>
                    </tr>
                </table>
            @endif

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
                                    <small>
                                        <strong>
                                            {{ $keystageLabel ?: ($status->qr_label ?? 'Unknown Item') }}
                                        </strong>
                                    </small>
                            </td>
                        @endforeach

                        @if($chunk->count() == 1)
                            <td></td>
                        @endif

                    </tr>

                @endforeach
            </table>

        @endforeach

    </div>

    <div class="page-break"></div>
@endforeach


</body>
</html>