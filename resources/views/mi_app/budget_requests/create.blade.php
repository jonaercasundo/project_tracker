<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('budget_requests.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-slate-900">New Budget Request</h1>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('budget_requests.store') }}" id="budget-form">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-6 p-4 border border-slate-200 rounded-xl">
                <div>
                    <label for="department" class="block text-xs font-bold text-slate-600 mb-1">
                        Department
                    </label>

                    <select
                        id="department"
                        name="department"
                        class="w-full border border-slate-200 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                        required
                    >
                        <option value="" disabled {{ old('department') ? '' : 'selected' }}>
                            Select Department
                        </option>

                        <option value="Design" {{ old('department') == 'Design' ? 'selected' : '' }}>
                            Design
                        </option>

                        <option value="Sourcing" {{ old('department') == 'Sourcing' ? 'selected' : '' }}>
                            Sourcing
                        </option>

                        <option value="Trading" {{ old('department') == 'Trading' ? 'selected' : '' }}>
                            Trading
                        </option>

                        <option value="Sales / Merchandising" {{ old('department') == 'Sales / Merchandising' ? 'selected' : '' }}>
                            Sales / Merchandising
                        </option>

                        <option value="Accounting" {{ old('department') == 'Accounting' ? 'selected' : '' }}>
                            Accounting
                        </option>

                        <option value="Management" {{ old('department') == 'Management' ? 'selected' : '' }}>
                            Management
                        </option>

                        <option value="Other" {{ old('department') == 'Other' ? 'selected' : '' }}>
                            Other
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Place / City, Country</label>
                    <div class="flex gap-2">
                        <input type="text" name="place" value="{{ old('place') }}" placeholder="Place"
                               class="w-1/2 border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                        <input type="text" name="country" value="{{ old('country') }}" placeholder="Country"
                               class="w-1/2 border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>
                <div>
                    <label for="travel_date_from" class="block text-xs font-bold text-slate-600 mb-1">Travel Date From</label>
                    <input id="travel_date_from" type="date" name="travel_date_from" value="{{ old('travel_date_from') }}"
                           class="w-full border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                </div>
                <div>
                    <label for="travel_date_to" class="block text-xs font-bold text-slate-600 mb-1">Travel Date To</label>
                    <input id="travel_date_to" type="date" name="travel_date_to" value="{{ old('travel_date_to') }}"
                           class="w-full border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <p id="date-range-error" class="hidden text-[11px] text-red-600 mt-1">Return date can't be before the departure date.</p>
                </div>
                <div class="col-span-2">
                    <label for="objectives" class="block text-xs font-bold text-slate-600 mb-1">Objectives</label>
                    <textarea id="objectives" name="objectives" rows="2"
                              class="w-full border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">{{ old('objectives') }}</textarea>
                </div>
            </div>

            <h2 class="text-sm font-bold text-slate-700 mb-2">Budget Breakdown</h2>

            {{-- Suggested categories -- items.*.expense_category stays a free-text field in the DB,
                 this just nudges toward consistent naming without restricting input. --}}
            <datalist id="expense-category-options">
                <option value="Airfare">
                <option value="Hotel / Accommodation">
                <option value="Per Diem">
                <option value="Transportation">
                <option value="Meals">
                <option value="Visa / Travel Documents">
                <option value="Communication">
                <option value="Other">
            </datalist>

            <div class="border border-slate-200 rounded-xl overflow-hidden mb-2">
                <table class="w-full text-sm" id="items-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="p-2.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Category</th>
                            <th class="p-2.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Particular</th>
                            <th class="p-2.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">Cash</th>
                            <th class="p-2.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">Credit Card</th>
                            <th class="p-2.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">Travel Agent</th>
                            <th class="p-2.5 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="item-row">
                            <td class="p-1.5">
                                <input list="expense-category-options" name="items[0][expense_category]"
                                       class="w-full p-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                            </td>
                            <td class="p-1.5">
                                <input name="items[0][particular]"
                                       class="w-full p-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                            </td>
                            <td class="p-1.5">
                                <input type="number" step="0.01" min="0" name="items[0][budget_cash]"
                                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
                            </td>
                            <td class="p-1.5">
                                <input type="number" step="0.01" min="0" name="items[0][budget_credit_card]"
                                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
                            </td>
                            <td class="p-1.5">
                                <input type="number" step="0.01" min="0" name="items[0][budget_travel_agent]"
                                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
                            </td>
                            <td class="p-1.5 text-center">
                                <button type="button" class="remove-row text-red-500 hover:text-red-700 text-lg leading-none" aria-label="Remove line">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" id="add-row"
                    class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 mb-6 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add line
            </button>

            <div class="flex justify-end mb-6 p-3 bg-slate-50 rounded-xl">
                <div class="text-sm">
                    <span class="text-slate-500">Total:</span>
                    <span id="grand-total" class="font-bold text-slate-900 ml-1">₱0.00</span>
                </div>
            </div>

            <div class="mb-6">
                <label for="remarks" class="block text-xs font-bold text-slate-600 mb-1">Remarks</label>
                <textarea id="remarks" name="remarks" rows="2"
                          class="w-full border border-slate-200 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">{{ old('remarks') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" id="submit-btn"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                    Submit Request
                </button>
                <a href="{{ route('budget_requests.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
    let rowIndex = 1;

    function rowTemplate(i) {
        return `<tr class="item-row">
            <td class="p-1.5">
                <input list="expense-category-options" name="items[${i}][expense_category]"
                       class="w-full p-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100" required>
            </td>
            <td class="p-1.5">
                <input name="items[${i}][particular]"
                       class="w-full p-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100" required>
            </td>
            <td class="p-1.5">
                <input type="number" step="0.01" min="0" name="items[${i}][budget_cash]"
                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
            </td>
            <td class="p-1.5">
                <input type="number" step="0.01" min="0" name="items[${i}][budget_credit_card]"
                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
            </td>
            <td class="p-1.5">
                <input type="number" step="0.01" min="0" name="items[${i}][budget_travel_agent]"
                       class="w-full p-1.5 text-sm text-right border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 amount">
            </td>
            <td class="p-1.5 text-center">
                <button type="button" class="remove-row text-red-500 hover:text-red-700 text-lg leading-none" aria-label="Remove line">&times;</button>
            </td>
        </tr>`;
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#items-table tbody .item-row');
        document.querySelectorAll('.remove-row').forEach(btn => {
            btn.disabled = rows.length <= 1;
            btn.classList.toggle('opacity-30', rows.length <= 1);
            btn.classList.toggle('cursor-not-allowed', rows.length <= 1);
        });
    }

    document.getElementById('add-row').addEventListener('click', () => {
        document.querySelector('#items-table tbody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex++));
        updateRemoveButtons();
    });

    document.getElementById('items-table').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-row') && !e.target.disabled) {
            const rows = document.querySelectorAll('#items-table tbody .item-row');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                recalcTotal();
                updateRemoveButtons();
            }
        }
    });

    document.getElementById('items-table').addEventListener('input', (e) => {
        if (e.target.classList.contains('amount')) recalcTotal();
    });

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.amount').forEach(el => total += parseFloat(el.value || 0));
        document.getElementById('grand-total').textContent = '₱' + total.toFixed(2);
    }

    // Travel date range validation
    const dateFrom = document.getElementById('travel_date_from');
    const dateTo = document.getElementById('travel_date_to');
    const dateRangeError = document.getElementById('date-range-error');

    function validateDateRange() {
        if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
            dateRangeError.classList.remove('hidden');
            dateTo.setCustomValidity("Return date can't be before the departure date.");
            return false;
        }
        dateRangeError.classList.add('hidden');
        dateTo.setCustomValidity('');
        return true;
    }

    dateFrom.addEventListener('change', validateDateRange);
    dateTo.addEventListener('change', validateDateRange);

    // Prevent double submit
    document.getElementById('budget-form').addEventListener('submit', function (e) {
        if (!validateDateRange()) {
            e.preventDefault();
            return;
        }
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Submitting...';
    });

    updateRemoveButtons();
    </script>
</x-mi_app>