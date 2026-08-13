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
}

.page-break {
    page-break-after: always;
}
</style>

</head>
:::writing{variant="document" id="74126" title="Blade Template — Multiple LOTs per School"}
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
    </style>
</head>

<body>

@php
    $totalSchools = count($data);
    $schoolCount = 0;
@endphp


{{-- ============================================================
     SCHOOL / DISTRICT
============================================================ --}}
@foreach($data as $school)

    @php
        $schoolCount++;
        $info = $school['info'];
    @endphp


    <table>

        {{-- DISTRICT HEADER --}}
        <tr class="header">
            <td colspan="4">
                DISTRICT: {{ $info['school_name'] ?? '' }}
            </td>
        </tr>


        {{-- DIVISION --}}
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


        {{-- REGION --}}
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


        {{-- ====================================================
             MULTIPLE LOTS FOR ONE SCHOOL
        ===================================================== --}}
        @foreach($school['lots'] as $lotName => $keystageGroups)

            @php

                /*
                 * Calculate ROWSPAN for THIS LOT ONLY.
                 *
                 * Example:
                 *
                 * LOT 1
                 *   Key Stage 1      = 1 row
                 *   Item A            = 1 row
                 *   Item B            = 1 row
                 *   Key Stage 2      = 1 row
                 *   Item C            = 1 row
                 *
                 * Total rowspan = 5
                 */

                $lotRowspan = 0;

                foreach ($keystageGroups as $group) {

                    $label = $group['label'] ?? null;
                    $items = $group['items'] ?? [];

                    // Key stage header
                    if (!empty($label)) {
                        $lotRowspan++;
                    }

                    // Items
                    $lotRowspan += count($items);
                }

            @endphp


            {{-- Skip empty LOT --}}
            @if($lotRowspan > 0)

                @php
                    $lotCellPrinted = false;
                @endphp


                {{-- ====================================================
                     KEY STAGE GROUPS INSIDE THIS LOT
                ===================================================== --}}
                @foreach($keystageGroups as $group)

                    @php
                        $label = $group['label'] ?? null;
                        $items = $group['items'] ?? [];
                    @endphp


                    {{-- ==================================================
                         KEY STAGE HEADER
                    =================================================== --}}
                    @if(!empty($label))

                        <tr>

                            {{-- LOT CELL --}}
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


                            {{-- KEY STAGE --}}
                            <td
                                colspan="3"
                                class="keystage-cell"
                            >
                                {{ $label }}
                            </td>

                        </tr>

                    @endif


                    {{-- ==================================================
                         ITEMS
                    =================================================== --}}
                    @foreach($items as $item)

                        <tr>

                            {{-- LOT CELL --}}
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


                            {{-- ITEM --}}
                            <td>
                                {{ $item['item_name'] ?? '' }}
                            </td>


                            {{-- QUANTITY --}}
                            <td class="qty-cell">
                                {{ number_format((float) ($item['qty'] ?? 0)) }}
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


    {{-- ============================================================
         PAGE BREAK AFTER EACH SCHOOL
    ============================================================= --}}
    @if($schoolCount < $totalSchools)

        <div class="page-break"></div>

    @endif

@endforeach

</body>
</html>
:::