<x-mi_app>
    <div class="max-w-5xl mx-auto py-6">
        <h1 class="text-xl font-semibold mb-4">Liquidations</h1>

        <table class="w-full text-sm border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border text-left">Control ID</th>
                    <th class="p-2 border text-right">Budget</th>
                    <th class="p-2 border text-right">Actual</th>
                    <th class="p-2 border text-right">Variance</th>
                    <th class="p-2 border text-left">Status</th>
                    <th class="p-2 border"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($liquidations as $l)
                    <tr>
                        <td class="border p-2">{{ $l->budgetRequest->control_id }}</td>
                        <td class="border p-2 text-right">{{ number_format($l->budgetRequest->budget_total, 2) }}</td>
                        <td class="border p-2 text-right">{{ number_format($l->actual_total, 2) }}</td>
                        <td class="border p-2 text-right {{ $l->isOverBudget() ? 'text-red-600' : '' }}">{{ number_format($l->variance, 2) }}</td>
                        <td class="border p-2">{{ ucfirst($l->status) }}</td>
                        <td class="border p-2"><a href="{{ route('liquidation.show', $l) }}" class="text-blue-600 underline">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $liquidations->links() }}</div>
    </div>
</x-mi_app>