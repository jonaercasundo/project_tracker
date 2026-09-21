<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">

        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">Liquidation Report — {{ $liquidation->budgetRequest->control_id }}</h1>
            <a href="{{ route('liquidation.pdf', $liquidation) }}" class="text-sm text-blue-600 underline">Download PDF</a>
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
            @if($liquidation->status === 'submitted')
                <form method="POST" action="{{ route('liquidation.note', $liquidation) }}">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded text-sm">Note (Accounting)</button>
                </form>
            @endif
            @if($liquidation->status === 'noted')
                <form method="POST" action="{{ route('liquidation.approve', $liquidation) }}">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded text-sm">Approve & Close</button>
                </form>
            @endif
        </div>
    </div>
</x-mi_app>
