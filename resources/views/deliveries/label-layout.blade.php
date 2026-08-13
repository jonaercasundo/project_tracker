:::writing{variant="document" id="63847" title="Final Blade — Multiple LOTs per School"}
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
    </style>
</head>

<body>

@php
    $totalSchools = count($data);
    $schoolCount = 0;
@endphp


{{-- ============================================================
     EACH SCHOOL
============================================================ --}}
@foreach($data as $schoolId => $lots)

    @php
        $schoolCount++;

        /*
         * If your school information is stored separately,
         * keep using your existing $school information here.
         *
         * Replace this section with your actual school info
         * source if necessary.
         */
        $info = $school['info'] ?? [];
    @endphp


    <table>

        {{-- =====================================================
             DISTRICT
        ====================================================== --}}
        <tr class="header">
            <td colspan="4">
                DISTRICT: {{ $info['school_name'] ?? $schoolId }}
            </td>
        </tr>


        {{-- =====================================================
             DIVISION
        ====================================================== --}}
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


        {{-- =====================================================
             REGION
        ====================================================== --}}
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


        {{-- =====================================================
             MULTIPLE LOTS FOR THIS SCHOOL

             ACTUAL STRUCTURE:

             $data
                [school_id]
                    [lot_id]
                        [label]
                        [items]
        ====================================================== --}}

        @foreach($lots as $lotName => $lot)

            @php
                $label = $lot['label'] ?? '';
                $items = $lot['items'] ?? [];

                /*
                 * Calculate the rowspan for THIS LOT.
                 *
                 * Keystage label = 1 row
                 * Each item       = 1 row
                 */

                $lotRowspan = 0;

                if (!empty($label)) {
                    $lotRowspan++;
                }

                $lotRowspan += count($items);
            @endphp


            {{-- =================================================
                 DO NOT SKIP A LOT JUST BECAUSE IT HAS NO ITEMS
            ================================================== --}}
            @if($lotRowspan > 0)

                @php
                    $lotCellPrinted = false;
                @endphp


                {{-- =================================================
                     KEYSTAGE
                ================================================== --}}
                @if(!empty($label))

                    <tr>

                        {{-- LOT --}}
                        <td
                            class="lot-cell"
                            rowspan="{{ $lotRowspan }}"
                        >
                            LOT {{ $lotName }}
                        </td>


                        {{-- KEYSTAGE --}}
                        <td
                            colspan="3"
                            class="keystage-cell"
                        >
                            {{ $label }}
                        </td>

                    </tr>

                    @php
                        $lotCellPrinted = true;
                    @endphp

                @endif


                {{-- =================================================
                     ITEMS
                ================================================== --}}
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


                        {{-- ITEM NAME --}}
                        <td class="item-cell">
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

            @endif

        @endforeach

    </table>


    {{-- ============================================================
         PAGE BREAK BETWEEN SCHOOLS
    ============================================================= --}}
    @if($schoolCount < $totalSchools)

        <div class="page-break"></div>

    @endif

@endforeach

</body>
</html>
:::