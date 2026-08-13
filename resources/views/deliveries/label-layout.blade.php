:::writing{variant="document" id="41752" title="Revised Blade — Multiple LOTs per School"}
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


@foreach($data as $school)

    @php
        $schoolCount++;
        $info = $school['info'];
    @endphp


    <table>

        {{-- =====================================================
             DISTRICT
        ====================================================== --}}
        <tr class="header">
            <td colspan="4">
                DISTRICT: {{ $info['school_name'] ?? '' }}
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
             LOTS

             IMPORTANT:
             Your actual structure is:

             $school['lots'][LOT_ID]['label']
             $school['lots'][LOT_ID]['items']
        ====================================================== --}}

        @foreach($school['lots'] as $lotName => $lot)

            @php
                $label = $lot['label'] ?? '';
                $items = $lot['items'] ?? [];

                /*
                 * Each LOT gets its own rowspan.
                 *
                 * One row is required for the keystage label.
                 * Then one row for every item.
                 *
                 * Example:
                 *
                 * LOT 68
                 *   Keystage 1 G1toG3       = 1 row
                 *   Balance, Double-pan     = 1 row
                 *
                 * rowspan = 2
                 */

                $lotRowspan = 0;

                if (!empty($label)) {
                    $lotRowspan++;
                }

                $lotRowspan += count($items);
            @endphp


            {{-- =================================================
                 SKIP COMPLETELY EMPTY LOT
            ================================================== --}}
            @if($lotRowspan > 0)

                @php
                    $lotCellPrinted = false;
                @endphp


                {{-- =================================================
                     KEYSTAGE LABEL
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

                        {{-- 
                            If there was no keystage label,
                            print LOT here instead.
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

            @endif

        @endforeach

    </table>


    {{-- =========================================================
         PAGE BREAK BETWEEN SCHOOLS
    ========================================================== --}}
    @if($schoolCount < $totalSchools)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
:::