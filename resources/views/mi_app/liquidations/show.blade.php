<x-dynamic-component :component="auth()->user()->hasRole('accounting') ? 'accounting_app' : 'mi_app'">
    <div class="max-w-4xl mx-auto py-6">

        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">Liquidation Report — {{ $liquidation->budgetRequest->control_id }}</h1>
            @can('view', $liquidation)<a href="{{ route('travel_liquidation.pdf', $liquidation) }}" class="text-sm text-blue-600 underline">Download PDF</a>@endcan
        </div>

        {{-- Automated balance check: budget requisition vs. liquidation actuals --}}
        <div class="p-4 rounded border mb-6
            {{ $liquidation->isBalanced() ? 'bg-green-50 border-green-300' : ($liquidation->isOverBudget() ? 'bg-red-50 border-red-300' : 'bg-yellow-50 border-yellow-300') }}">
            <div class="font-semibold mb-2">
                @if($liquidation->isBalanced())
                    ✅ Balanced — actual matches the approved budget.
                @elseif($liquidation->isOverBudget())
                    ⚠️ Over budget by {{ number_format(abs($liquidation->variance), 2) }} ({{ number_format(abs($liquidation->percentVariance()) * 100, 1) }}%)
                @else
                    ℹ️ Under budget by {{ number_format($liquidation->variance, 2) }} ({{ number_format($liquidation->percentVariance() * 100, 1) }}%)
                @endif
            </div>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>Budget Requisition Total: <span class="font-semibold">{{ number_format($liquidation->budgetRequest->budget_total, 2) }}</span></div>
                <div>Liquidated (Actual) Total: <span class="font-semibold">{{ number_format($liquidation->actual_total, 2) }}</span></div>
                <div>Variance: <span class="font-semibold">{{ number_format($liquidation->variance, 2) }}</span></div>
            </div>
        </div>

        <table class="w-full text-sm border mb-6">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border text-left">Category</th>
                    <th class="p-2 border text-left">Particular</th>
                    <th class="p-2 border text-right">Budgeted</th>
                    <th class="p-2 border text-right">Actual</th>
                    <th class="p-2 border text-right">Variance</th>
                    <th class="p-2 border text-center">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liquidation->items as $item)
                    @php $budgeted = optional($item->budgetRequestItem)->budget_total ?? 0; @endphp
                    <tr>
                        <td class="border p-2">{{ $item->expense_category }}</td>
                        <td class="border p-2">{{ $item->particular }}</td>
                        <td class="border p-2 text-right">{{ number_format($budgeted, 2) }}</td>
                        <td class="border p-2 text-right">{{ number_format($item->actual_total, 2) }}</td>
                        <td class="border p-2 text-right {{ ($budgeted - $item->actual_total) < 0 ? 'text-red-600' : '' }}">
                            {{ number_format($budgeted - $item->actual_total, 2) }}
                        </td>
                        <td class="border p-2 text-center">{{ $item->receipt_attached }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex gap-2">
            @if($liquidation->status === 'returned_for_revision')
                @can('update', $liquidation)
                    <div class="flex flex-wrap gap-3"><a href="{{ route('travel_liquidation.edit', $liquidation) }}" class="text-blue-700">Edit returned report</a><form method="POST" action="{{ route('travel_liquidation.submit', $liquidation) }}" onsubmit="return confirm('Resubmit this report for accounting review?');">@csrf<button class="font-semibold text-blue-700">Resubmit for accounting review</button></form></div>
                @endcan
            @endif
            @if($liquidation->status === 'submitted')
                @can('noteByAccounting', $liquidation)
                <form method="POST" action="{{ route('travel_liquidation.note', $liquidation) }}">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded text-sm">Note (Accounting)</button>
                </form>
                @endcan
            @endif
            @if($liquidation->status === 'noted')
                @include('mi_app.approval_actions', ['record' => $liquidation, 'type' => 'travel'])
            @endif
        </div>
        @include('mi_app.financial_history', ['activities' => $liquidation->activities])
        <p class="text-sm">Next expected action / responsible party:
            {{ match($liquidation->status) {
                'draft' => 'Employee: submission', 'submitted' => 'Accounting: review',
                'noted' => 'Designated approver: final approval',
                'approved' => 'Accounting: verified settlement; authorized closure',
                'closed' => 'Complete', default => 'Administrative review',
            } }}
        </p>
        @if($liquidation->settlement)
            <p>Settlement reference: {{ $liquidation->settlement->reference_no }};
                {{ $liquidation->settlement->currency }} outstanding: {{ $liquidation->settlement->outstanding_balance }}</p>
        @endif
        @if($liquidation->status === 'approved' && ! $liquidation->settlement)
            @can('recordSettlement', $liquidation)
                <form method="POST" action="{{ route('travel_liquidation.settlement', $liquidation) }}" class="my-4 space-y-2">
                    @csrf
                    <label>Employee return <input name="employee_return_amount" required inputmode="decimal" class="border p-2"></label>
                    <label>Company reimbursement <input name="company_reimbursement_amount" required inputmode="decimal" class="border p-2"></label>
                    <label>Currency <select name="currency">@foreach(config('mi_financial.currencies') as $currency)<option>{{ $currency }}</option>@endforeach</select></label>
                    <label>Payment method <input name="settlement_method" required class="border p-2"></label>
                    <label>Reference <input name="reference_no" required class="border p-2"></label>
                    <button class="rounded bg-blue-600 p-2 text-white">Record verified settlement</button>
                </form>
            @endcan
        @endif
        @if($liquidation->status === 'approved' && $liquidation->settlement?->outstanding_balance === '0.00')
            @can('close', $liquidation)
                <form method="POST" action="{{ route('travel_liquidation.close', $liquidation) }}">@csrf<button>Close liquidation</button></form>
            @endcan
        @endif
        @if($errors->any())<p class="text-red-700">{{ $errors->first() }}</p>@endif
    </div>
</x-dynamic-component>
