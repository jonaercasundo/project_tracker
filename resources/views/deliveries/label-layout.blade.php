<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <style>

        @page {
            margin: 15px;
        }

        body {
            font-family: DejaVu Sans;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        /*
        |--------------------------------------------------------------------------
        | SCHOOL HEADER
        |--------------------------------------------------------------------------
        */

        .school-header {
            background: #d9d9d9;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | SCHOOL INFORMATION
        |--------------------------------------------------------------------------
        */

        .school-info-label {
            width: 18%;
            font-weight: bold;
            background: #eeeeee;
        }

        .school-info-value {
            width: 82%;
        }

        /*
        |--------------------------------------------------------------------------
        | COLUMN HEADER
        |--------------------------------------------------------------------------
        */

        .column-header {
            background: #eeeeee;
            font-weight: bold;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | LOT
        |--------------------------------------------------------------------------
        */

        .lot-cell {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            width: 15%;
        }

        /*
        |--------------------------------------------------------------------------
        | KEYSTAGE
        |--------------------------------------------------------------------------
        */

        .keystage-cell {
            background: #eeeeee;
            font-weight: bold;
            text-align: left;
            padding: 6px;
        }

        /*
        |--------------------------------------------------------------------------
        | ITEM
        |--------------------------------------------------------------------------
        */

        .item-cell {
            width: 55%;
        }

        /*
        |--------------------------------------------------------------------------
        | QUANTITY
        |--------------------------------------------------------------------------
        */

        .qty-cell {
            width: 15%;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | UNIT
        |--------------------------------------------------------------------------
        */

        .unit-cell {
            width: 15%;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-row {
            text-align: center;
            padding: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | PAGE BREAK
        |--------------------------------------------------------------------------
        */

        .page-break {
            page-break-after: always;
        }

        /*
        |--------------------------------------------------------------------------
        | PREVENT ROW SPLITTING
        |--------------------------------------------------------------------------
        */

        tr {
            page-break-inside: avoid;
        }

    </style>

</head>


<body>


@php

    $totalSchools = count($data);

    $schoolCount = 0;

@endphp


{{-- =========================================================================
     SCHOOLS
========================================================================= --}}

@foreach($data as $sid => $school)

    @php

        $schoolCount++;

        $info = $school['info'] ?? [];

        $lots = $school['lots'] ?? [];

    @endphp


    {{-- =====================================================================
         SCHOOL INFORMATION
    ====================================================================== --}}

    <table>

        {{-- SCHOOL NAME --}}

        <tr class="school-header">

            <td colspan="4">

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

                    {{ $info['division'] ?? 'N/A' }}

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

                    {{ $info['region'] ?? 'N/A' }}

                </td>

            </tr>

        @endif

    </table>


    {{-- =====================================================================
         ITEMS TABLE
    ====================================================================== --}}

    <table>

        {{-- TABLE HEADER --}}

        <thead>

            <tr class="column-header">

                <th style="width:15%;">
                    LOT
                </th>

                <th style="width:55%;">
                    ITEM
                </th>

                <th style="width:15%;">
                    QUANTITY
                </th>

                <th style="width:15%;">
                    UNIT
                </th>

            </tr>

        </thead>


        <tbody>


        {{-- =================================================================
             LOTS
        ================================================================== --}}

        @foreach($lots as $lotKey => $lot)

            @php

                $lotName = trim(
                    (string) (
                        $lot['lot_name']
                        ?? 'NO LOT'
                    )
                );

                if ($lotName === '') {
                    $lotName = 'NO LOT';
                }

                $keystages =
                    $lot['keystages']
                    ?? [];


                /*
                |--------------------------------------------------------------------------
                | Calculate total rows occupied by LOT
                |--------------------------------------------------------------------------
                |
                | Every keystage = 1 row
                |
                | Every item = 1 row
                |
                */

                $lotRowspan = 0;

                foreach ($keystages as $keystage) {

                    /*
                    | Keystage header row
                    */

                    $lotRowspan++;

                    /*
                    | Item rows
                    */

                    $lotRowspan += count(
                        $keystage['items'] ?? []
                    );
                }

            @endphp


            {{-- =============================================================
                 SKIP EMPTY LOT
            ============================================================== --}}

            @if($lotRowspan > 0)

                @php

                    $lotPrinted = false;

                @endphp


                {{-- =========================================================
                     KEYSTAGES
                ========================================================== --}}

                @foreach(
                    $keystages
                    as $keystageKey => $keystage
                )

                    @php

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

                    <tr>


                        {{-- LOT CELL --}}

                        @if(!$lotPrinted)

                            <td
                                class="lot-cell"
                                rowspan="{{ $lotRowspan }}"
                            >

                                LOT {{ $lotName }}

                            </td>

                            @php

                                $lotPrinted = true;

                            @endphp

                        @endif


                        {{-- KEYSTAGE --}}

                        <td
                            colspan="3"
                            class="keystage-cell"
                        >

                            {{ $keystageLabel }}

                        </td>

                    </tr>


                    {{-- =====================================================
                         ITEMS
                    ====================================================== --}}

                    @foreach($items as $item)

                        <tr>


                            {{-- ITEM --}}

                            <td class="item-cell">

                                {{ $item['item_name'] ?? '' }}

                            </td>


                            {{-- QUANTITY --}}

                            <td class="qty-cell">

                                {{ number_format(
                                    (float) (
                                        $item['qty']
                                        ?? 0
                                    )
                                ) }}

                            </td>


                            {{-- UNIT --}}

                            <td class="unit-cell">

                                {{ $item['unit'] ?? '' }}

                            </td>


                        </tr>

                    @endforeach


                @endforeach

            @endif

        @endforeach


        {{-- =================================================================
             NO DATA
        ================================================================== --}}

        @if(empty($lots))

            <tr>

                <td
                    colspan="4"
                    class="empty-row"
                >

                    No package items found.

                </td>

            </tr>

        @endif


        </tbody>

    </table>


    {{-- =====================================================================
         PAGE BREAK BETWEEN SCHOOLS
    ====================================================================== --}}

    @if($schoolCount < $totalSchools)

        <div class="page-break"></div>

    @endif


@endforeach


</body>
</html>