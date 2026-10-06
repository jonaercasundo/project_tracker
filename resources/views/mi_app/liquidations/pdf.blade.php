<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f2f2f2; }
        .text-right { text-align: right; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; } p { overflow-wrap: break-word; }
        .totals { margin-top: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Liquidation Report - {{ $liquidation->budgetRequest->control_id }}</h2>
    <p>
        Employee: {{ $liquidation->liquidatedBy->name }} |
        Department: {{ $liquidation->budgetRequest->department }} |
        Status: {{ ucfirst($liquidation->status) }}
    </p>

    <p>Company: {{ $liquidation->company?->name }}<br>
        Travel dates: {{ $liquidation->budgetRequest->travel_date_from?->format('Y-m-d') ?? 'Not recorded' }}
        to {{ $liquidation->budgetRequest->travel_date_to?->format('Y-m-d') ?? 'Not recorded' }}<br>
        Submitted: {{ $liquidation->submitted_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }}</p>
    <h3>Recorded releases</h3>
    @forelse($liquidation->budgetRequest->releases as $release)
        <p>{{ $release->currency }} {{ $release->amount }}; {{ $release->payment_method }};
            Reference {{ $release->reference_no }}; {{ $release->released_at->format('Y-m-d H:i:s') }}
            @if($release->note)<br>{{ $release->note }}@endif</p>
    @empty
        <p>No quantified release evidence recorded.</p>
    @endforelse
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Particular</th>
                <th class="text-right">Budgeted</th>
                <th class="text-right">Actual</th>
                <th class="text-right">Variance</th>
                <th>Cash</th><th>Credit card</th><th>Travel agent</th>
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
                    <td class="text-right">{{ \Brick\Math\BigDecimal::of($budgeted)->minus($item->actual_total)->toScale(2) }}</td>
                    <td>{{ $item->actual_cash }}</td><td>{{ $item->actual_credit_card }}</td><td>{{ $item->actual_travel_agent }}</td>
                    <td>{{ $item->receipt_attached }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="totals">
        Budget Total: {{ number_format($liquidation->budgetRequest->budget_total, 2) }} -
        Actual Total: {{ number_format($liquidation->actual_total, 2) }} -
        Variance: {{ number_format($liquidation->variance, 2) }}
        ({{ $liquidation->isBalanced() ? 'Balanced' : ($liquidation->isOverBudget() ? 'Over Budget' : 'Under Budget') }})
    </p>
    @if($liquidation->remarks)<p>Liquidation remarks: {{ $liquidation->remarks }}</p>@endif
    @if($liquidation->budgetRequest->remarks)<p>Budget remarks: {{ $liquidation->budgetRequest->remarks }}</p>@endif
    @if($liquidation->settlement)
        <h3>Settlement evidence</h3>
        <p>Currency: {{ $liquidation->settlement->currency }}; release snapshot: {{ $liquidation->settlement->released_amount }};
            expenses: {{ $liquidation->settlement->expense_amount }}<br>
            Employee return: {{ $liquidation->settlement->employee_return_amount }};
            company reimbursement: {{ $liquidation->settlement->company_reimbursement_amount }}<br>
            Recorded payments: {{ $liquidation->settlement->settlement_amount }};
            outstanding: {{ $liquidation->settlement->outstanding_balance }}<br>
            Reference: {{ $liquidation->settlement->reference_no }};
            method: {{ $liquidation->settlement->settlement_method }};
            recorded: {{ $liquidation->settlement->settled_at->format('Y-m-d H:i:s') }}<br>
            {{ $liquidation->settlement->note }}</p>
    @else
        <p>No settlement evidence recorded.</p>
    @endif
    <h3>Stored workflow stamps</h3>
    <p>Budget approval: {{ $liquidation->budgetRequest->approved_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }};
        {{ $liquidation->budgetRequest->approver?->name ?? 'Actor not available' }}<br>
        Accounting note: {{ $liquidation->budgetRequest->noted_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }};
        {{ $liquidation->budgetRequest->accountant?->name ?? 'Actor not available' }}<br>
        Release confirmation: {{ $liquidation->budgetRequest->released_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }};
        {{ $liquidation->budgetRequest->releaser?->name ?? 'Actor not available' }}<br>
        Employee receipt confirmation: {{ $liquidation->budgetRequest->received_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }}<br>
        Travel review: {{ $liquidation->noted_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }};
        Travel approval: {{ $liquidation->approved_at?->format('Y-m-d H:i:s') ?? 'Not recorded' }}</p>
    <h3>Budget sign-off history</h3>
    @foreach($liquidation->budgetRequest->activities as $activity)
        <p>{{ str_replace('_', ' ', $activity->event) }}: {{ $activity->actor_name_snapshot }};
            {{ $activity->created_at->format('Y-m-d H:i:s') }}
            @if($activity->amount !== null) - {{ $activity->currency }} {{ $activity->amount }} @endif
            @if($activity->reference_no) - {{ $activity->reference_no }} @endif
            @if($activity->note)<br>{{ $activity->note }}@endif</p>
    @endforeach
    <h3>Travel sign-off history</h3>
    @forelse($liquidation->activities as $activity)
        <p>{{ str_replace('_', ' ', $activity->event) }}: {{ $activity->actor_name_snapshot }};
            {{ $activity->created_at->format('Y-m-d H:i:s') }}
            @if($activity->note)<br>{{ $activity->note }}@endif</p>
    @empty
        <p>No recorded travel sign-off history available.</p>
    @endforelse
</body>
</html>
