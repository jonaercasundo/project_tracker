<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        .qrbox { text-align:center; border:1px solid #000; padding:10px; }
    </style>
</head>
<body>

@foreach($data as $group)

    <h3>DR #{{ $group['first']->dr_no }}</h3>

    <table width="100%">
        <tr>

        @foreach($group['qrs'] as $i => $qr)

            <td class="qrbox" width="50%">
                <img src="{{ $qr['qr'] }}" width="120">
                <br>
                <small><b>{{ $qr['keystage'] ?? $qr['label'] }}</b></small>
            </td>

            @if(($i + 1) % 2 == 0)
                </tr><tr>
            @endif

        @endforeach

        </tr>
    </table>

    <div style="page-break-after: always;"></div>

@endforeach

</body>
</html>