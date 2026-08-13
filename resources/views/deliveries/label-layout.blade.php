
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
            margin-bottom: 20px;
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
        | TABLE HEADER
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
            background: #e0e0e0;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            width: 15%;
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

    /*
    |--------------------------------------------------------------------------
    | TOTAL SCHOOLS
    |--------------------------------------------------------------------------
    */

    $totalSchools = count($data);

    $schoolCount = 0;

@endphp


{{-- ========================================================================
     SCHOOLS
======================================================================== --}}

@foreach($data as $sid => $school)

    @php

        $schoolCount++;

        $info = $school['info'] ?? [];

        $lots = $school['lots'] ?? [];

    @endphp


    {{-- ====================================================================
         SCHOOL INFORMATION
    ===================================================================== --}}

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


    {{-- ====================================================================
         ITEMS TABLE
    ===================================================================== --}}

    <table>

        {{-- TABLE HEADER --}}

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


        {{-- =================================================================
             LOTS
        ================================================================== --}}

        @foreach($lots as $lotName => $items)

            @php

                /*
                |--------------------------------------------------------------------------
                | NORMALIZE LOT NAME
                |--------------------------------------------------------------------------
                */

                $displayLotName = trim(
                    (string) $lotName
                );

                if ($displayLotName === '') {
                    $displayLotName = 'NO LOT';
                }


                /*
                |--------------------------------------------------------------------------
                | NORMALIZE ITEMS
                |--------------------------------------------------------------------------
                */

                $items = array_values(
                    $items ?? []
                );


                /*
                |--------------------------------------------------------------------------
                | REMOVE EMPTY ITEMS
                |--------------------------------------------------------------------------
                */

                $items = array_filter(
                    $items,
                    function ($item) {

                        return !empty(
                            trim(
                                (string) (
                                    $item['item_name']
                                    ?? ''
                                )
                            )
                        );
                    }
                );


                /*
                |--------------------------------------------------------------------------
                | REINDEX
                |--------------------------------------------------------------------------
                */

                $items = array_values(
                    $items
                );


                /*
                |--------------------------------------------------------------------------
                | NUMBER OF ITEMS
                |--------------------------------------------------------------------------
                */

                $itemCount = count($items);

            @endphp


            {{-- =============================================================
                 SKIP EMPTY LOT
            ============================================================== --}}

            @if($itemCount > 0)

                @php
                    $lotPrinted = false;
                @endphp


                {{-- =========================================================
                     ITEMS
                ========================================================== --}}

                @foreach($items as $item)

                    <tr>

                        {{-- =================================================
                             LOT
                        ================================================== --}}

                        @if(!$lotPrinted)

                            <td
                                class="lot-cell"
                                rowspan="{{ $itemCount }}"
                            >

                                LOT {{ $displayLotName }}

                            </td>

                            @php
                                $lotPrinted = true;
                            @endphp

                        @endif


                        {{-- =================================================
                             ITEM
                        ================================================== --}}

                        <td class="item-cell">

                            {{ $item['item_name'] ?? '' }}

                        </td>


                        {{-- =================================================
                             QUANTITY
                        ================================================== --}}

                        <td class="qty-cell">

                            {{ number_format(
                                (float) (
                                    $item['qty']
                                    ?? 0
                                )
                            ) }}

                        </td>


                        {{-- =================================================
                             UNIT
                        ================================================== --}}

                        <td class="unit-cell">

                            {{ $item['unit'] ?? '' }}

                        </td>

                    </tr>

                @endforeach

            @endif

        @endforeach


    </table>


    {{-- ====================================================================
         PAGE BREAK BETWEEN SCHOOLS
    ===================================================================== --}}

    @if($schoolCount < $totalSchools)

        <div class="page-break"></div>

    @endif


@endforeach


</body>
</html>
