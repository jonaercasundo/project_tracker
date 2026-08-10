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

{{--
    EXPECTED DATA SHAPE (mirrors deliveries_batch.php)

    $drGroups: a collection, ONE ENTRY PER DR NUMBER (not per keystage row).
    Each $drGroup should expose:

        $drGroup->dr_no
        $drGroup->school_id
        $drGroup->project        -> ->project_name, ->arSetting (same as before: $ar)
        $drGroup->school         -> ->school_name, ->address
        $drGroup->lot            -> ->contract_no, ->lot_name

        $drGroup->keystageGroups : collection, one per keystage tied to this DR
            each keystage group exposes:
                ->label            (e.g. "Keystage 1 G1toG3")
                ->packages : collection, one per package_status row
                    each package exposes:
                        ->package_num   (e.g. "Package 1 of 3")
                        ->dimensions    -> ->width / ->height / ->length / ->unit
                        ->items         : collection of ['item_name' => ..., 'qty' => ...]
                                          (qty here = package_content.qty * keystage multiplier,
                                           already computed server-side — do NOT re-multiply in the view)
                        ->qr_code       (base64 data URI or image URL)
                        ->qr_label      (fallback label if no keystage, e.g. "Package 1 of 3 ORD-00001")

    This replaces the old flat $delivery->items / $delivery->packageStatuses shape.
    Adjust property/relationship names below to match your actual Eloquent models —
    the structure (DR -> keystage -> package -> items) is what matters.
--}}

@foreach($drGroups as $drGroup)
    @php

        $ar = $drGroup->project->arSetting ?? null;

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
                    AR: {{ preg_replace('/^TX-LOT\d+-/', '', $drGroup->school_id) }}
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
                        {{ $ar->project_name ?? $drGroup->project->project_name ?? '' }}
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

            {{ $drGroup->lot->contract_no ?? '' }}

            @if(!empty($drGroup->lot->lot_name))
                (LOT {{ $drGroup->lot->lot_name }})
            @endif

            between

            {{ $ar->company ?? '' }}

            and

            {{ $ar->client ?? '' }}.

        </p>

        @if(optional($ar)->display_label)

            <p>

                <strong>School Name:</strong>
                {{ $drGroup->school->school_name ?? '' }}

                <br>

                <strong>School Address:</strong>
                {{ $drGroup->school->address ?? '' }}
            </p>

        @endif

        @foreach($drGroup->keystageGroups as $keystageGroup)

            @if(!empty($keystageGroup->label))
                <p><strong>Keystage {{ $keystageGroup->label }}</strong></p>
            @endif

            <table>
                @foreach($keystageGroup->packages as $package)

                    <tr class="package-row">
                        <td style="width:50%;">
                            <small>{{ $package->package_num }}</small>
                        </td>
                        <td style="width:50%; text-align:center;">
                            @if($package->dimensions)
                                <small>
                                    {{ $package->dimensions->width ?? 'N/A' }} ×
                                    {{ $package->dimensions->height ?? 'N/A' }} ×
                                    {{ $package->dimensions->length ?? 'N/A' }}
                                    {{ $package->dimensions->unit ?? 'cm' }}
                                </small>
                            @else
                                <small>Dimensions: N/A</small>
                            @endif
                        </td>
                    </tr>

                    @foreach($package->items as $item)
                        <tr>
                            <td style="width:80%;">
                                {{ $item['item_name'] }}
                            </td>
                            <td style="width:20%; text-align:center;">
                                {{ $item['qty'] }}
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
            <small>DR: {{ $drGroup->dr_no }}</small>
        </div>

        @foreach($drGroup->keystageGroups as $keystageGroup)

            @if(!empty($keystageGroup->label))
                <table>
                    <tr>
                        <td class="keystage-header" colspan="2">
                            Keystage {{ $keystageGroup->label }}
                        </td>
                    </tr>
                </table>
            @endif

            <table>
                @foreach($keystageGroup->packages->chunk(2) as $chunk)

                    <tr>

                        @foreach($chunk as $package)
                            <td class="qr">
                                @if($package->qr_code)
                                    <img
                                        src="{{ $package->qr_code }}"
                                        width="150"
                                    >
                                @endif

                                <br>
                                    <small>
                                        <strong>
                                            {{ $keystageGroup->label ?: $package->qr_label }}
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