<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <style>

        @page {
            margin: 18px 20px 18px 20px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }

        /*
        |--------------------------------------------------------------------------
        | SCHOOL BLOCK
        |--------------------------------------------------------------------------
        */

        .school-table {
            margin-bottom: 8px;
        }

        .school-header {
            background: #d9d9d9;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            padding: 7px;
        }

        .school-info-label {
            width: 20%;
            background: #eeeeee;
            font-weight: bold;
        }

        .school-info-value {
            width: 80%;
        }

        /*
        |--------------------------------------------------------------------------
        | ITEMS TABLE
        |--------------------------------------------------------------------------
        */

        .items-table {
            width: 100%;
            table-layout: fixed;
            margin-bottom: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | COLUMN WIDTHS
        |--------------------------------------------------------------------------
        */

        .col-lot {
            width: 16%;
        }

        .col-item {
            width: 54%;
        }

        .col-qty {
            width: 15%;
        }

        .col-unit {
            width: 15%;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE HEADER
        |--------------------------------------------------------------------------
        */

        .items-table thead {
            display: table-header-group;
        }

        .column-header {
            background: #eeeeee;
            font-weight: bold;
            text-align: center;
        }

        .column-header th {
            padding: 6px 4px;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | LOT HEADER
        |--------------------------------------------------------------------------
        */

        .lot-row td {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 10px;
            padding: 6px;
        }

        .lot-title {
            text-align: left;
        }

        /*
        |--------------------------------------------------------------------------
        | LOT QR CODE
        |--------------------------------------------------------------------------
        */

        .lot-qr {
            text-align: center;
            vertical-align: middle;
            padding: 3px;
        }

        .lot-qr img {
            width: 40px;
            height: 40px;
        }

        /*
        |--------------------------------------------------------------------------
        | TOP-RIGHT LOT QR BADGES
        |--------------------------------------------------------------------------
        |
        | Sits in normal document flow, right-aligned, directly above the
        | school table — one badge per lot. Avoids position:absolute since
        | that was clipping above the page's top margin and overlapping
        | the header text. Uses inline-block (not flexbox) since dompdf
        | does not support CSS flexbox.
        */

        .top-lot-badges {
            text-align: right;
            margin-bottom: 6px;
        }

        .top-lot-badge {
            display: inline-block;
            vertical-align: top;
            text-align: center;
            background: #fff;
            border: 1px solid #000;
            padding: 3px 4px;
            margin: 0 0 4px 4px;
        }

        .top-lot-badge img {
            width: 36px;
            height: 36px;
            display: block;
            margin: 0 auto;
        }

        .top-lot-badge span {
            display: block;
            font-size: 7px;
            font-weight: bold;
            margin-top: 2px;
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | KEYSTAGE HEADER
        |--------------------------------------------------------------------------
        */

        .keystage-row td {
            background: #f2f2f2;
            font-weight: bold;
            font-size: 9px;
            padding: 5px 6px;
        }

        .keystage-title {
            text-align: left;
        }

        /*
        |--------------------------------------------------------------------------
        | ITEM ROW
        |--------------------------------------------------------------------------
        */

        .item-row td {
            padding: 5px;
        }

        .item-lot {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .item-name {
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .item-qty {
            text-align: center;
            white-space: nowrap;
        }

        .item-unit {
            text-align: center;
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY DATA
        |--------------------------------------------------------------------------
        */

        .empty-row td {
            text-align: center;
            padding: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | PAGE CONTROL
        |--------------------------------------------------------------------------
        */

        /*
        | Do NOT force page breaks inside the items table.
        | The table is allowed to continue naturally.
        */

        tr {
            page-break-inside: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | SCHOOL PAGE BREAK
        |--------------------------------------------------------------------------
        |
        | Each school starts on a new page, except the first school.
        |
        */

        .school-page {
            page-break-before: always;
        }

        /*
        |--------------------------------------------------------------------------
        | SMALL TEXT
        |--------------------------------------------------------------------------
        */

        .small-text {
            font-size: 8px;
        }

    </style>

</head>

<body>

@php

    $totalSchools = 0;

    foreach ($data as $project) {
        $totalSchools += count($project['schools'] ?? []);
    }

    $schoolIndex = 0;

@endphp


{{-- ========================================================================
     PROJECT LOOP
========================================================================= --}}

@foreach($data as $projectId => $project)

    @php

        $projectInfo = $project['info'] ?? [];

        $schools = $project['schools'] ?? [];

    @endphp


    {{-- ====================================================================
         SCHOOL LOOP
    ===================================================================== --}}

    @forelse($schools as $schoolId => $school)

        @php

            $schoolIndex++;

            $info = $school['info'] ?? [];

            $lots = $school['lots'] ?? [];

        @endphp


        {{-- ================================================================
             NEW SCHOOL
        ================================================================= --}}

        @if($schoolIndex > 1)

            <div class="school-page"></div>

        @endif


        {{-- ================================================================
             SCHOOL INFORMATION
        ================================================================= --}}

        @php
            // One badge per lot on this school's page (not just the first).
        @endphp

        <div class="school-block">

            @if(!empty($lots))
                <div class="top-lot-badges">
                    @foreach($lots as $lot)
                        @if(!empty($lot['lot_qr']))
                            <div class="top-lot-badge">
                                <img src="{{ $lot['lot_qr'] }}" alt="QR">
                                <span>LOT {{ $lot['lot_name'] ?? '' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

        <table class="school-table">

            <tbody>

                {{-- PROJECT NAME --}}

                <tr>

                    <td
                        colspan="4"
                        class="school-header"
                    >

                        PROJECT:
                        {{ $projectInfo['project_name'] ?? 'N/A' }}

                    </td>

                </tr>


                {{-- SCHOOL NAME --}}

                <tr>

                    <td
                        colspan="4"
                        class="school-header"
                    >

                        SCHOOL:
                        {{ $info['school_name'] ?? 'N/A' }}

                    </td>

                </tr>


                {{-- SCHOOL ID --}}

                @if($showSchoolID)

                    <tr>

                        <td class="school-info-label">
                            School ID
                        </td>

                        <td
                            colspan="3"
                            class="school-info-value"
                        >

                            {{ $info['school_id'] ?? 'N/A' }}

                        </td>

                    </tr>

                @endif


                {{-- MUNICIPALITY --}}

                @if($showMunicipality)

                    <tr>

                        <td class="school-info-label">
                            Municipality
                        </td>

                        <td
                            colspan="3"
                            class="school-info-value"
                        >

                            {{ $info['municipality'] ?? 'N/A' }}

                        </td>

                    </tr>

                @endif


                {{-- DIVISION --}}

                @if($showDivision)

                    <tr>

                        <td class="school-info-label">
                            Division
                        </td>

                        <td
                            colspan="3"
                            class="school-info-value"
                        >

                            {{ $projectInfo['division'] ?? 'N/A' }}

                        </td>

                    </tr>

                @endif


                {{-- REGION --}}

                @if($showRegion)

                    <tr>

                        <td class="school-info-label">
                            Region
                        </td>

                        <td
                            colspan="3"
                            class="school-info-value"
                        >

                            {{ $projectInfo['region'] ?? 'N/A' }}

                        </td>

                    </tr>

                @endif

            </tbody>

        </table>


        {{-- ================================================================
             ITEMS TABLE
        ================================================================= --}}

        <table class="items-table">

            <colgroup>

                <col class="col-lot">
                <col class="col-item">
                <col class="col-qty">
                <col class="col-unit">

            </colgroup>


            {{-- =============================================================
                 TABLE HEADER
            ============================================================== --}}

            <thead>

                <tr class="column-header">

                    <th>
                        LOT
                    </th>

                    <th>
                        ITEM
                    </th>

                    <th>
                        QUANTITY
                    </th>

                    <th>
                        UNIT
                    </th>

                </tr>

            </thead>


            <tbody>


            {{-- =============================================================
                 LOTS
            ============================================================== --}}

            @forelse($lots as $lotKey => $lot)

                @php

                    $lotId = $lot['lot_id'] ?? null;

                    $lotName = trim(
                        (string) (
                            $lot['lot_name'] ?? ''
                        )
                    );

                    if ($lotName === '') {
                        $lotName = 'NO LOT';
                    }

                    // Pre-generated by DeliveryController@generateLabels,
                    // same pattern as $qrCodes in the AR flow.
                    $lotQrSrc = $lot['lot_qr'] ?? null;

                    $keystages = $lot['keystages'] ?? [];

                @endphp


                {{-- =========================================================
                     LOT HEADER
                ========================================================== --}}

                <tr class="lot-row">

                    {{-- QR CODE --}}

                    <td class="lot-qr">

                        @if($lotQrSrc)
                            <img src="{{ $lotQrSrc }}" alt="QR">
                        @endif

                    </td>


                    {{-- LOT NAME --}}

                    <td
                        colspan="3"
                        class="lot-title"
                    >

                        @if($lotId)
                            LOT {{ $lotName }}
                        @else
                            NO LOT
                        @endif

                    </td>

                </tr>


                {{-- =========================================================
                     KEYSTAGES
                ========================================================== --}}

                @forelse($keystages as $keystageKey => $keystage)

                    @php

                        $keystageId =
                            $keystage['keystage_id']
                            ?? null;

                        $keystageLabel = trim(
                            (string) (
                                $keystage['label']
                                ?? ''
                            )
                        );

                        if ($keystageLabel === '') {
                            $keystageLabel = 'No Keystage';
                        }

                        $items =
                            $keystage['items']
                            ?? [];

                    @endphp


                    {{-- =====================================================
                         KEYSTAGE HEADER
                    ====================================================== --}}

                    <tr class="keystage-row">

                        <td
                            colspan="4"
                            class="keystage-title"
                        >

                            {{ $keystageLabel }}

                        </td>

                    </tr>


                    {{-- =====================================================
                         ITEMS
                    ====================================================== --}}

                    @forelse($items as $item)

                        @php

                            $itemName = trim(
                                (string) (
                                    $item['item_name']
                                    ?? ''
                                )
                            );

                            $itemQty =
                                (float) (
                                    $item['qty']
                                    ?? 0
                                );

                            $itemUnit = trim(
                                (string) (
                                    $item['unit']
                                    ?? ''
                                )
                            );

                        @endphp


                        <tr class="item-row">

                            {{-- LOT --}}

                            <td class="item-lot">

                                {{ $lotName }}

                            </td>


                            {{-- ITEM --}}

                            <td class="item-name">

                                {{ $itemName }}

                            </td>


                            {{-- QUANTITY --}}

                            <td class="item-qty">

                                {{ number_format($itemQty) }}

                            </td>


                            {{-- UNIT --}}

                            <td class="item-unit">

                                {{ $itemUnit }}

                            </td>

                        </tr>

                    @empty

                        <tr class="empty-row">

                            <td colspan="4">

                                No items found for this keystage.

                            </td>

                        </tr>

                    @endforelse


                @empty

                    <tr class="empty-row">

                        <td colspan="4">

                            No keystages found for this lot.

                        </td>

                    </tr>

                @endforelse


            @empty

                {{-- =========================================================
                     NO LOTS
                ========================================================== --}}

                <tr class="empty-row">

                    <td colspan="4">

                        No package items found.

                    </td>

                </tr>

            @endforelse


            </tbody>

        </table>

        </div>{{-- /.school-block --}}


    @empty

        {{-- ================================================================
             NO SCHOOLS
        ================================================================= --}}

        <tr class="empty-row">

            <td colspan="4">

                No schools found for this project.

            </td>

        </tr>

    @endforelse


@endforeach

</body>
</html>