<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: DejaVu Sans;
            font-size: 11px;
            margin: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
        }

        .header {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .lot-cell {
            background: #e0e0e0;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            width: 15%;
        }

        .keystage-cell {
            background: #f2f2f2;
            font-weight: bold;
        }

        .item-cell {
            width: 55%;
        }

        .qty-cell {
            text-align: center;
            width: 15%;
        }

        .unit-cell {
            text-align: center;
            width: 15%;
        }

        .page-break {
            page-break-after: always;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent table rows from being split across PDF pages
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


{{-- ========================================================================
     SCHOOLS
========================================================================= --}}

@foreach($data as $sid => $school)

    @php
        $schoolCount++;

        $info = $school['info'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Controller structure:
        |
        | $school['lots'][$lotId] = [
        |     'lot_name' => '9',
        |     'keystages' => [...]
        | ];
        |--------------------------------------------------------------------------
        */

        $lots = $school['lots'] ?? [];
    @endphp


    <table>

        {{-- =================================================================
             SCHOOL / DISTRICT HEADER
        ================================================================== --}}

        <tr class="header">
            <td colspan="4">
                SCHOOL:
                {{ $info['school_name'] ?? '' }}
            </td>
        </tr>


        {{-- =================================================================
             SCHOOL ID
        ================================================================== --}}

        @if($showSchoolID)

            <tr>
                <td>
                    <strong>School ID</strong>
                </td>

                <td colspan="3">
                    {{ $info['school_id'] ?? '' }}
                </td>
            </tr>

        @endif


        {{-- =================================================================
             MUNICIPALITY
        ================================================================== --}}

        @if($showMunicipality)

            <tr>
                <td>
                    <strong>Municipality</strong>
                </td>

                <td colspan="3">
                    {{ $info['municipality'] ?? '' }}
                </td>
            </tr>

        @endif


        {{-- =================================================================
             DIVISION
        ================================================================== --}}

        @if($showDivision)

            <tr>
                <td>
                    <strong>Division</strong>
                </td>

                <td colspan="3">
                    {{ $info['division'] ?? '' }}
                </td>
            </tr>

        @endif


        {{-- =================================================================
             REGION
        ================================================================== --}}

        @if($showRegion)

            <tr>
                <td>
                    <strong>Region</strong>
                </td>

                <td colspan="3">
                    {{ $info['region'] ?? '' }}
                </td>
            </tr>

        @endif


        {{-- =================================================================
             LOTS
        ================================================================== --}}

        @foreach($lots as $lotId => $lot)

            @php

                /*
                |--------------------------------------------------------------------------
                | LOT INFORMATION
                |--------------------------------------------------------------------------
                */

                $lotName = $lot['lot_name'] ?? 'NO LOT';

                $keystages = $lot['keystages'] ?? [];


                /*
                |--------------------------------------------------------------------------
                | Calculate LOT ROWSPAN
                |--------------------------------------------------------------------------
                |
                | Every keystage label = 1 row
                | Every item = 1 row
                |
                | Example:
                |
                | LOT 9
                |
                | Keystage 1 G1toG3       ← 1 row
                | Balance                  ← 1 row
                |
                | Keystage 2 G4toG6       ← 1 row
                | Item B                   ← 1 row
                |
                | LOT rowspan = 4
                |
                */

                $lotRowspan = 0;

                foreach($keystages as $keystage) {

                    $label = $keystage['label'] ?? null;

                    $items = $keystage['items'] ?? [];

                    if(!empty($label)) {
                        $lotRowspan++;
                    }

                    $lotRowspan += count($items);
                }

            @endphp


            {{-- =================================================================
                 SKIP EMPTY LOT
            ================================================================== --}}

            @if($lotRowspan > 0)

                @php
                    $lotCellPrinted = false;
                @endphp


                {{-- =============================================================
                     KEYSTAGES
                ============================================================== --}}

                @foreach($keystages as $keystageId => $keystage)

                    @php

                        $label = $keystage['label'] ?? null;

                        $items = $keystage['items'] ?? [];

                    @endphp


                    {{-- =========================================================
                         KEYSTAGE HEADER
                    ========================================================== --}}

                    @if(!empty($label))

                        <tr>

                            {{-- LOT --}}
                            @if(!$lotCellPrinted)

                                <td
                                    class="lot-cell"
                                    rowspan="{{ $lotRowspan }}"
                                >
                                    LOT {{ $lotName }}
                                </td>

                                @php
                                    $lotCellPrinted = true;
                                @endphp

                            @endif


                            {{-- KEYSTAGE --}}
                            <td
                                colspan="3"
                                class="keystage-cell"
                            >
                                {{ $label }}
                            </td>

                        </tr>

                    @endif


                    {{-- =========================================================
                         ITEMS
                    ========================================================== --}}

                    @foreach($items as $item)

                        <tr>

                            {{-- LOT CELL
                                 Only printed here if the LOT has no
                                 keystage label.
                            --}}

                            @if(!$lotCellPrinted)

                                <td
                                    class="lot-cell"
                                    rowspan="{{ $lotRowspan }}"
                                >
                                    LOT {{ $lotName }}
                                </td>

                                @php
                                    $lotCellPrinted = true;
                                @endphp

                            @endif


                            {{-- ITEM NAME --}}

                            <td class="item-cell">
                                {{ $item['item_name'] ?? '' }}
                            </td>


                            {{-- QUANTITY --}}

                            <td class="qty-cell">
                                {{ number_format(
                                    (float) ($item['qty'] ?? 0)
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

    </table>


    {{-- ========================================================================
         PAGE BREAK BETWEEN SCHOOLS
    ========================================================================= --}}

    @if($schoolCount < $totalSchools)

        <div class="page-break"></div>

    @endif

@endforeach

</body>
</html>