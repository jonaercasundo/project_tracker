<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deliveries Batch</title>
</head>
<body>

<h2>Deliveries Batch PDF</h2>

@foreach($deliveries as $delivery)
    <div style="margin-bottom:20px;">
        <h3>DR #{{ $delivery->dr_no }}</h3>

        <p>
            Project: {{ $delivery->project->project_name ?? '' }} <br>
            School: {{ $delivery->school->school_name ?? '' }}
        </p>

        <hr>
    </div>
@endforeach

</body>
</html>