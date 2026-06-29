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

        <tr class="header">
            <td colspan="4">
                DISTRICT: {{ $info['school_name'] }}
            </td>
        </tr>

        @if($showDivision)
            <tr>
                <td><strong>Division</strong></td>
                <td colspan="3">
                    {{ $info['division'] }}
                </td>
            </tr>
        @endif

        @if($showRegion)
            <tr>
                <td><strong>Region</strong></td>
                <td colspan="3">
                    {{ $info['region'] }}
                </td>
            </tr>
        @endif

        @foreach($school['lots'] as $lotName => $items)

                @php
                    $items = array_values($items ?? []);

                    // FILTER invalid rows
                    $items = array_filter($items, function($item) {
                        return is_array($item) && !empty($item['item_name']);
                    });

                    $itemCount = count($items);

                    if ($itemCount === 0) continue;
                @endphp

            @foreach($items as $item)

                <tr>

                    @if($first)
                        <td class="lot-cell" rowspan="{{ $itemCount }}">
                            LOT {{ $lotName }}
                        </td>
                        @php $first = false; @endphp
                    @endif

                    <td>
                        {{ $item['item_name'] }}
                    </td>

                    <td style="text-align:center;">
                        {{ number_format($item['qty']) }}
                    </td>

                    <td style="text-align:center;">
                        Copies
                    </td>

                </tr>

            @endforeach

        @endforeach

    </table>

    @if($schoolCount < $totalSchools)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>