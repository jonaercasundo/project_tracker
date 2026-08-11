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
        @foreach($school['lots'] as $lotName => $keystageGroups)

@php
    // Total rows spanned by the LOT cell = one header row per keystage
    // that has a label, plus one row per item across all groups.
    $totalRows = 0;
    foreach ($keystageGroups as $group) {
        $totalRows += ($group['label'] ? 1 : 0) + count($group['items']);
    }
    $firstRowOfLot = true;
@endphp

@foreach($keystageGroups as $group)

    @if($group['label'])
        <tr>
            @if($firstRowOfLot)
                <td class="lot-cell" rowspan="{{ $totalRows }}">
                    LOT {{ $lotName }}
                </td>
                @php $firstRowOfLot = false; @endphp
            @endif

            <td colspan="3" style="background:#f2f2f2; font-weight:bold;">
                {{ $group['label'] }}
            </td>
        </tr>
    @endif

    @foreach($group['items'] as $item)
        <tr>
            @if($firstRowOfLot)
                <td class="lot-cell" rowspan="{{ $totalRows }}">
                    LOT {{ $lotName }}
                </td>
                @php $firstRowOfLot = false; @endphp
            @endif

            <td>{{ $item['item_name'] }}</td>
            <td style="text-align:center;">{{ number_format($item['qty']) }}</td>
            <td style="text-align:center;">{{ $item['unit'] }}</td>
        </tr>
    @endforeach

@endforeach

@endforeach

    </table>

    @if($schoolCount < $totalSchools)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>