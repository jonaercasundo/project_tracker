<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f2f2f2; }
        .text-right { text-align: right; }
        .totals { margin-top: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Liquidation Report — {{ $liquidation->budgetRequest->control_id }}</h2>
    <p>
        Employee: {{ $liquidation->liquidatedBy->name }} |
        Department: {{ $liquidation->budgetRequest->department }} |
        Status: {{ ucfirst($liquidation->status) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Particular</th>
                <th class="text-right">Budgeted</th>
                <th class="text-right">Actual</th>
                <th class="text-right">Variance</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liquidation->items as $item)
                @php $budgeted = optional($item->budgetRequestItem)->budget_total ?? 0; @endphp
                <tr>
                    <td>{{ $item->expense_category }}</td>
                    <td>{{ $item->particular }}</td>
                    <td class="text-right">{{ number_format($budgeted, 2) }}</td>
                    <td class="text-right">{{ number_format($item->actual_total, 2) }}</td>
                    <td class="text-right">{{ number_format($budgeted - $item->actual_total, 2) }}</td>
                    <td>{{ $item->receipt_attached }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="totals">
        Budget Total: {{ number_format($liquidation->budgetRequest->budget_total, 2) }} —
        Actual Total: {{ number_format($liquidation->actual_total, 2) }} —
        Variance: {{ number_format($liquidation->variance, 2) }}
        ({{ $liquidation->isBalanced() ? 'Balanced' : ($liquidation->isOverBudget() ? 'Over Budget' : 'Under Budget') }})
    </p>
</body>
</html>
