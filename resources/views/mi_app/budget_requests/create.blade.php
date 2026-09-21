<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">
        <h1 class="text-xl font-semibold mb-4">New Budget Request</h1>

        <form method="POST" action="{{ route('budget_requests.store') }}" id="budget-form">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium">Department</label>
                    <input type="text" name="department" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Place / City, Country</label>
                    <div class="flex gap-2">
                        <input type="text" name="place" class="w-1/2 border rounded p-2" placeholder="Place">
                        <input type="text" name="country" class="w-1/2 border rounded p-2" placeholder="Country">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium">Travel Date From</label>
                    <input type="date" name="travel_date_from" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium">Travel Date To</label>
                    <input type="date" name="travel_date_to" class="w-full border rounded p-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium">Objectives</label>
                    <textarea name="objectives" class="w-full border rounded p-2" rows="2"></textarea>
                </div>
            </div>

            <h2 class="font-semibold mb-2">Budget Breakdown</h2>
            <table class="w-full text-sm border mb-2" id="items-table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border text-left">Category</th>
                        <th class="p-2 border text-left">Particular</th>
                        <th class="p-2 border text-right">Cash</th>
                        <th class="p-2 border text-right">Credit Card</th>
                        <th class="p-2 border text-right">Travel Agent</th>
                        <th class="p-2 border"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="item-row">
                        <td class="border p-1"><input name="items[0][expense_category]" class="w-full p-1" required></td>
                        <td class="border p-1"><input name="items[0][particular]" class="w-full p-1" required></td>
                        <td class="border p-1"><input type="number" step="0.01" name="items[0][budget_cash]" class="w-full p-1 text-right amount"></td>
                        <td class="border p-1"><input type="number" step="0.01" name="items[0][budget_credit_card]" class="w-full p-1 text-right amount"></td>
                        <td class="border p-1"><input type="number" step="0.01" name="items[0][budget_travel_agent]" class="w-full p-1 text-right amount"></td>
                        <td class="border p-1 text-center"><button type="button" class="remove-row text-red-600">&times;</button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" id="add-row" class="text-sm text-blue-600 mb-4">+ Add line</button>

            <div class="text-right font-semibold mb-6">Total: <span id="grand-total">0.00</span></div>

            <div class="mb-6">
                <label class="block text-sm font-medium">Remarks</label>
                <textarea name="remarks" class="w-full border rounded p-2" rows="2"></textarea>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Submit Request</button>
        </form>
    </div>

    <script>
    let rowIndex = 1;

    function rowTemplate(i) {
        return `<tr class="item-row">
            <td class="border p-1"><input name="items[${i}][expense_category]" class="w-full p-1" required></td>
            <td class="border p-1"><input name="items[${i}][particular]" class="w-full p-1" required></td>
            <td class="border p-1"><input type="number" step="0.01" name="items[${i}][budget_cash]" class="w-full p-1 text-right amount"></td>
            <td class="border p-1"><input type="number" step="0.01" name="items[${i}][budget_credit_card]" class="w-full p-1 text-right amount"></td>
            <td class="border p-1"><input type="number" step="0.01" name="items[${i}][budget_travel_agent]" class="w-full p-1 text-right amount"></td>
            <td class="border p-1 text-center"><button type="button" class="remove-row text-red-600">&times;</button></td>
        </tr>`;
    }

    document.getElementById('add-row').addEventListener('click', () => {
        document.querySelector('#items-table tbody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex++));
    });

    document.getElementById('items-table').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            recalcTotal();
        }
    });

    document.getElementById('items-table').addEventListener('input', (e) => {
        if (e.target.classList.contains('amount')) recalcTotal();
    });

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.amount').forEach(el => total += parseFloat(el.value || 0));
        document.getElementById('grand-total').textContent = total.toFixed(2);
    }
    </script>
</x-mi_app>
