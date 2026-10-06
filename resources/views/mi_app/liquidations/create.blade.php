<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">
        <h1 class="text-xl font-semibold mb-1">Liquidation — {{ $budgetRequest->control_id }}</h1>
        <p class="text-sm text-gray-500 mb-4">Budget total: {{ number_format($budgetRequest->budget_total, 2) }}</p>

        @if ($errors->any())
            <ul class="text-red-700 mb-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ isset($liquidation) ? route('travel_liquidation.update', $liquidation) : route('travel_liquidation.store') }}">
            @csrf
            @isset($liquidation)
                @method('PUT')
            @endisset
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
                    @foreach(isset($liquidation) ? $liquidation->items : $budgetRequest->items as $i => $item)
                        <tr>
                            <td class="border p-1">
                                {{ $item->expense_category }}
                                @isset($liquidation)
                                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                @else
                                    <input type="hidden" name="items[{{ $i }}][budget_request_item_id]" value="{{ $item->id }}">
                                @endisset
                                <input type="hidden" name="items[{{ $i }}][expense_category]" value="{{ $item->expense_category }}">
                            </td>
                            <td class="border p-1">
                                {{ $item->particular }}
                                <input type="hidden" name="items[{{ $i }}][particular]" value="{{ $item->particular }}">
                            </td>
                            @php $budgeted = isset($liquidation) ? ($item->budgetRequestItem?->budget_total ?? 0) : $item->budget_total; @endphp
                            <td class="border p-1 text-right budgeted" data-amount="{{ $budgeted }}">{{ number_format($budgeted, 2) }}</td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_cash]" value="{{ old("items.$i.actual_cash", isset($liquidation) ? $item->actual_cash : $item->budget_cash) }}"></td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_credit_card]" value="{{ old("items.$i.actual_credit_card", isset($liquidation) ? $item->actual_credit_card : $item->budget_credit_card) }}"></td>
                            <td class="border p-1"><input type="number" step="0.01" class="w-full p-1 text-right actual" name="items[{{ $i }}][actual_travel_agent]" value="{{ old("items.$i.actual_travel_agent", isset($liquidation) ? $item->actual_travel_agent : $item->budget_travel_agent) }}"></td>
                            <td class="border p-1 text-center">
                                <select name="items[{{ $i }}][receipt_attached]" class="p-1">
                                    <option value="yes" @selected(old("items.$i.receipt_attached", $item->receipt_attached ?? 'yes') === 'yes')>Yes</option>
                                    <option value="no" @selected(old("items.$i.receipt_attached", $item->receipt_attached ?? 'yes') === 'no')>No</option>
                                    <option value="n_a" @selected(old("items.$i.receipt_attached", $item->receipt_attached ?? 'yes') === 'n_a')>N/A</option>
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
                <textarea name="remarks" class="w-full border rounded p-2" rows="2">{{ old('remarks', $liquidation->remarks ?? '') }}</textarea>
            </div>

            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded">{{ isset($liquidation) ? 'Save changes' : 'Submit Liquidation' }}</button>
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
