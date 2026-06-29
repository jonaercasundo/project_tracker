<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receive of Items</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            max-width: 650px;
            margin: auto;
            border: 1px solid #ddd;
        }

        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items th,
        .items td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }

        .items th {
            background-color: #f1f1f1;
            font-weight: 600;
        }

        .insufficient-item {
            background: #fff3cd;
        }

        .quantity-warning {
            color: #b45309;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<div class="container mt-5">

    <div class="card p-4 shadow-lg rounded-3">

        <!-- Header -->
        <div class="text-center mb-3">
            <h4 class="mt-3">{{ $deliveries->project_name }}</h4>
            <h6 class="text-muted">{{ $deliveries->school }}</h6>
            <h6 class="text-muted">{{ strtoupper($deliveries->dr_no) }}</h6>
            <p class="mb-0">{{ $deliveries->address }}</p>
        </div>

        <!-- Items Table -->
        <div class="items mb-4">
            <h5 class="mb-2">Packing List</h5>

            <table>
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    @if($deliveries->package_status === 'pending')
                        <th>Available</th>
                    @endif
                </tr>
                </thead>

                <tbody>
                @foreach($items as $item)

                    @php
                        $actualQty = $item->qty * $multiplier;
                        $availableQty = $inventory[$item->item_id] ?? 0;

                        $isSufficient = true;

                        if ($deliveries->package_status === 'pending') {
                            $isSufficient = $availableQty >= $actualQty;
                        }
                    @endphp

                    <tr class="{{ ($deliveries->package_status === 'pending' && !$isSufficient) ? 'insufficient-item' : '' }}">

                        <td>{{ $item->item_name }}</td>

                        <td>{{ $actualQty }}</td>

                        @if($deliveries->package_status === 'pending')
                            <td>
                                {{ $availableQty }}

                                @if(!$isSufficient)
                                    <div class="quantity-warning">
                                        Insufficient! Need {{ $actualQty - $availableQty }} more
                                    </div>
                                @endif
                            </td>
                        @endif

                    </tr>

                @endforeach
                </tbody>

            </table>

            <!-- Warning -->
            @if($deliveries->package_status === 'pending' && !$ok)
                <div class="alert alert-warning mt-3">
                    <strong>Warning:</strong>
                    Some items have insufficient inventory. Please check before submitting.
                </div>
            @endif

        </div>

        <!-- Form -->
        <form method="POST" action="{{ url('check.php') }}" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="id" value="{{ $id }}">
            <input type="hidden" name="status" value="{{ $deliveries->status ?? '' }}">
            <input type="hidden" name="delivery_id" value="{{ $delivery_id }}">

            <!-- Upload -->
            <div class="mb-3">
                <label class="form-label">Upload Photos (Optional)</label>
                <input type="file" class="form-control" name="photo_upload[]" multiple accept="image/*">
            </div>

            <!-- CAPTCHA -->
            <div class="mb-3">
                <label class="form-label">{{ $question }}</label>
                <input type="text" class="form-control" name="captcha_answer" placeholder="Enter your answer" required>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="btn btn-primary w-100"
                    {{ ($deliveries->package_status === 'pending' && !$ok) ? 'disabled' : '' }}>
                {{ ($deliveries->package_status === 'pending' && !$ok) ? 'Insufficient Inventory' : 'Submit' }}
            </button>

            <!-- Error List -->
            @if($deliveries->package_status === 'pending' && !$ok)
                <div class="alert alert-danger mt-3">
                    <strong>Cannot Submit:</strong>
                    <ul class="mb-0">
                        @foreach($insufficient as $item)
                            <li>
                                {{ $item['item_name'] }}:
                                Required {{ $item['required'] }},
                                Available {{ $item['available'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </form>

    </div>

</div>

</body>
</html>