<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">
        <h1 class="text-xl font-semibold mb-1">Liquidation — {{ $budgetRequest->control_id }}</h1>
        <p class="text-sm text-gray-500 mb-4">Budget total: {{ number_format($budgetRequest->budget_total, 2) }}</p>

        <form method="POST" action="{{ route('liquidation.store') }}">
            @csrf
            <input type="hidden" name="budget_request_id" value="{{ $budgetRequest->id }}">

            <table class="w-full text-sm border mb-2">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border text-left">Category</th>
                        <th class="p-2 border text-left">Particular</th>
                        <th class="p-2 border text-right">Budgeted</th>
                        <th class="p-2 border text-right">Actual Cash</th>
                        <th class="p-2 border text-right">Actual CC</th>
                        <th class="p-2 border text-right">Actual Agent</th>
                        <th class="p-2 border text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgetRequest->items as $i => $item)
                        <tr>
                            <td class="border p-1">
                                {{ $item->expense_category }}
                                <input type="hidden" name="items[{{ $i }}][budget_request_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $i }}][expense_category]" value="{{ $item->expense_category }}">
                            </td>
                            <td class="border p-1">
                                {{ $item->particular }}
                                <input type="hidden" name="items[{{ $i }}][particular]" value="{{ $item->particular }}">
                            </td>
                            <td class="border p-1 text-right budgeted" data-amount="{{ $item->budget_total }}">{{ number_format($item->budget_total, 2) }}</td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_cash]" value="{{ $item->budget_cash }}"></td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_credit_card]" value="{{ $item->budget_credit_card }}"></td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_travel_agent]" value="{{ $item->budget_travel_agent }}"></td>
                            <td class="border p-1 text-center">
                                <select name="items[{{ $i }}][receipt_attached]" class="p-1">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Live preview of the same balance check the server runs on submit --}}
            <div class="grid grid-cols-3 gap-4 text-sm mb-6 p-3 border rounded bg-gray-50">
                <div>Budget Total: <span class="font-semibold">{{ number_format($budgetRequest->budget_total, 2) }}</span></div>
                <div>Actual Total: <span class="font-semibold" id="actual-total">0.00</span></div>
                <div>Variance: <span class="font-semibold" id="variance">0.00</span></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium">Remarks</label>
                <textarea name="remarks" class="w-full border rounded p-2" rows="2"></textarea>
            </div>

            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded">Submit Liquidation</button>
        </form>
    </div>

    <script>
    const budgetTotal = {{ $budgetRequest->budget_total }};

    function recalc() {
        let actual = 0;
        document.querySelectorAll('.actual').forEach(el => actual += parseFloat(el.value || 0));
        const variance = budgetTotal - actual;
        document.getElementById('actual-total').textContent = actual.toFixed(2);
        const varEl = document.getElementById('variance');
        varEl.textContent = variance.toFixed(2);
        varEl.className = 'font-semibold ' + (Math.abs(variance) <= 0.01 ? 'text-green-700' : (variance < 0 ? 'text-red-700' : 'text-yellow-700'));
    }

    document.querySelectorAll('.actual').forEach(el => el.addEventListener('input', recalc));
    recalc();
    </script>
</x-mi_app>