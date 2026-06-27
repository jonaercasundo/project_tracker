<div class="space-y-4 max-w-7xl mx-auto p-2 antialiased text-slate-700 text-xs">

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 backdrop-blur-sm animate-fade-in">
            <div class="font-semibold text-red-800 flex items-center gap-2 mb-1.5 text-sm">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc ml-5 space-y-0.5 text-red-600/90 font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= PROJECT INFORMATION ================= --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 flex items-center gap-2">
            <div class="w-1 h-3.5 bg-blue-600 rounded-full"></div>
            <h2 class="font-bold text-slate-800 tracking-tight text-sm">
                Project Information
            </h2>
        </div>

        <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Project ID
                </label>
                <input type="text"
                       name="project_id"
                       value="{{ old('project_id', $project->project_id ?? '') }}"
                       class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2.5">
            </div>

            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Project Name
                </label>
                <input type="text"
                       name="project_name"
                       value="{{ old('project_name', $project->project_name ?? '') }}"
                       class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2.5">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Procuring Entity / Agency
                </label>
                <input type="text"
                       name="procuring_entity"
                       value="{{ old('procuring_entity', $project->procuring_entity ?? '') }}"
                       class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2.5">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Approved Budget for the Contract (ABC)
                </label>
                <div class="relative">
                    <span class="absolute left-2.5 top-2 text-slate-400 font-medium text-[11px]">₱</span>
                    <input type="number"
                           step="0.01"
                           name="approved_budget_contract_abc"
                           value="{{ old('approved_budget_contract_abc', $project->approved_budget_contract_abc ?? '') }}"
                           class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 pl-6 pr-2.5 font-medium text-slate-800">
                </div>
            </div>
            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 truncate">
                    Lot No.
                </label>
                <input type="text"
                        name="lot_no"
                        value="{{ old('lot_no', $project->lot_no ?? '') }}"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1 truncate">
                    Delivery Period (No. of Calendar Days)
                </label>
                <input type="number"
                        name="delivery_period"
                        value="{{ old('delivery_period', $project->delivery_period ?? '') }}"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Date of Bid Opening
                </label>
                <input type="date"
                       name="date_of_bid_opening"
                       value="{{ old('date_of_bid_opening', $project->date_of_bid_opening ?? '') }}"
                       class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2.5 text-slate-600">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Status
                </label>
                <select name="status"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                    @foreach([
                        'Draft',
                        'For Review',
                        'Published',
                        'Awarded',
                        'Cancelled',
                        'Completed'
                    ] as $status)
                        <option value="{{ $status }}"
                            {{ old('status', $project->status ?? 'Draft') == $status ? 'selected':'' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- ================= DELIVERY LOCATION ================= --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 flex items-center gap-2">
            <div class="w-1 h-3.5 bg-emerald-500 rounded-full"></div>
            <h3 class="font-bold text-slate-800 tracking-tight text-sm">
                Delivery Location
            </h3>
        </div>

        <div class="p-4 grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Country
                </label>
                <select id="country"
                        name="country"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                    <option value="Philippines">Philippines</option>
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Region
                </label>
                <select id="region"
                        name="region"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                    <option value="">Select Region</option>
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Province
                </label>
                <select id="province"
                        name="province"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                <option value="">Select Province</option>
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    City / Municipality
                </label>
                <select id="city"
                        name="city"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                <option value="">Select City</option>
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Barangay
                </label>
                <select id="barangay"
                        name="barangay"
                        class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 py-0 px-2.5">
                <option value="">Select Barangay</option>
                </select>
            </div>

            <div class="md:col-span-5">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Street / Building
                </label>
                <input type="text"
                       name="address"
                       class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 h-8 px-2.5">
            </div>
        </div>
    </div>

    {{-- ================= SPECIAL CONDITIONS ================= --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-2.5">
            <h2 class="font-bold text-slate-800 tracking-tight text-sm">
                Notes / Special Conditions
            </h2>
        </div>
        <div class="p-3">
            <textarea name="notes_special_condition"
                      rows="3"
                      class="w-full text-xs rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all duration-150 p-2.5 resize-y min-h-[4.5rem] placeholder:text-slate-400">{{ old('notes_special_condition', $project->notes_special_condition ?? '') }}</textarea>
        </div>
    </div>

    {{-- ================= PROJECT ITEMS ================= --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-50/70">
            <div>
                <h2 class="font-bold text-slate-800 tracking-tight text-sm">
                    Project Items
                </h2>
                <p class="text-[10px] text-slate-400 font-medium">
                    Add all items included in this bidding document.
                </p>
            </div>
            <button type="button"
                    id="addItem"
                    class="inline-flex items-center justify-center px-3 h-7 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all duration-150 shadow-sm hover:shadow active:scale-95 text-[11px] gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Item
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200/60 text-[10px] uppercase font-bold text-slate-500 tracking-wider text-left">
                        <th class="px-3 py-2 w-16 text-center">Item No.</th>
                        <th class="px-3 py-2 min-w-[200px]">Item Description</th>
                        <th class="px-3 py-2 min-w-[200px]">Unit</th>
                        <th class="px-3 py-2 min-w-[100px] text-right">Quantity</th>
                        <th class="px-3 py-2 min-w-[120px] text-right">Unit Cost (PHP)</th>
                        <th class="px-3 py-2 min-w-[150px] text-right">Total Amount</th>
                        <th class="px-3 py-2 min-w-[200px]">Brand / Specs</th>
                        <th class="px-3 py-2 min-w-[200px]">Remarks</th>
                        <th class="px-3 py-2 w-10 text-center"></th>
                    </tr>
                </thead>
                <tbody id="itemsTable" class="divide-y divide-slate-100 bg-white">
                    @php
                        $oldItems = old('items', isset($project) ? $project->items->toArray() : []);
                    @endphp

                    @forelse($oldItems as $i => $item)
                    <tr class="hover:bg-slate-50/40 transition-colors group">
                        <td class="p-1.5 text-center">
                            <input type="number"
                                   name="items[{{ $i }}][item_no]"
                                   value="{{ $item['item_no'] ?? ($i+1) }}"
                                   class="w-full text-center text-xs font-medium rounded-md border-slate-200 h-7 p-1 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-0">
                        </td>
                        <td class="p-1.5">
                            <textarea name="items[{{ $i }}][item_description]"
                                      rows="1"
                                      class="w-full text-xs rounded-md border-slate-200 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0 min-h-[1.75rem] resize-y">{{ $item['item_description'] ?? '' }}</textarea>
                        </td>
                        <td class="p-1.5">
                            <input type="text"
                                   name="items[{{ $i }}][unit]"
                                   value="{{ $item['unit'] ?? '' }}"
                                   class="w-full text-xs rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0">
                        </td>
                        <td class="p-1.5">
                            <input type="number"
                                   class="qty w-full text-right text-xs rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0"
                                   name="items[{{ $i }}][quantity]"
                                   value="{{ $item['quantity'] ?? '' }}">
                        </td>
                        <td class="p-1.5">
                            <input type="number"
                                   step="0.01"
                                   class="unit_cost w-full text-right text-xs font-mono rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0"
                                   name="items[{{ $i }}][unit_cost]"
                                   value="{{ $item['unit_cost'] ?? '' }}">
                        </td>
                        <td class="p-1.5">
                            <input readonly
                                   type="number"
                                   step="0.01"
                                   class="total_amount w-full text-right text-xs font-mono font-semibold rounded-md bg-slate-50 border-slate-200 text-slate-600 h-7 p-1 focus:ring-0"
                                   name="items[{{ $i }}][total_amount]"
                                   value="{{ $item['total_amount'] ?? '' }}">
                        </td>
                        <td class="p-1.5">
                            <input type="text"
                                   name="items[{{ $i }}][brand]"
                                   value="{{ $item['brand'] ?? '' }}"
                                   class="w-full text-xs rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0">
                        </td>
                        <td class="p-1.5">
                            <input type="text"
                                   name="items[{{ $i }}][remarks]"
                                   value="{{ $item['remarks'] ?? '' }}"
                                   class="w-full text-xs rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0">
                        </td>
                        <td class="p-1.5 text-center">
                            <button type="button"
                                    class="removeRow inline-flex items-center justify-center w-6 h-6 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all duration-150">
                                ✕
                            </button>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-4 py-3 flex justify-end bg-slate-50/40">
            <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                    Grand Total
                </div>
                <div id="grandTotal"
                     class="text-xl font-black text-blue-700 tracking-tight font-mono mt-0.5">
                    ₱0.00
                </div>
            </div>
        </div>
    </div>

    {{-- BUTTONS --}}
    <div class="flex justify-end gap-2.5 pt-1">
        <a href="{{ route('bidding.index') }}"
           class="inline-flex items-center justify-center px-4 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition-colors duration-150">
            Cancel
        </a>
        
        <button type="submit"
                class="inline-flex items-center justify-center px-5 h-9 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold transition-all duration-150 shadow-sm active:scale-[0.98]">
            Save Bidding Document
        </button>
    </div>

</div>

{{-- ================= JAVASCRIPT ================= --}}
<script>
document.querySelector('form').addEventListener('submit', function () {
    document.querySelectorAll('#itemsTable tr').forEach((tr, index) => {
        tr.querySelectorAll('input, textarea').forEach(input => {
            const name = input.getAttribute('name');
            if (!name) return;
            input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${index}]`));
        });
    });
});

// ✅ Fix: declare row counter
let row = document.querySelectorAll('#itemsTable tr').length;

function getRowId() {
    return Date.now() + Math.floor(Math.random() * 1000);
}

document.getElementById('addItem').addEventListener('click', function () {
    // ✅ Fix: declare id before using it
    const id = getRowId();

    document.getElementById('itemsTable').insertAdjacentHTML('beforeend', `
<tr class="hover:bg-slate-50/40 transition-colors group">
    <td class="p-1.5 text-center">
        <input class="w-full text-center text-xs font-medium rounded-md border-slate-200 h-7 p-1 bg-slate-50 focus:ring-0"
               type="number"
               name="items[${id}][item_no]"
               value="${row + 1}">
    </td>
    <td class="p-1.5">
        <textarea rows="1"
          class="description w-full text-xs rounded-md border-slate-200 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0 overflow-hidden resize-none"
          name="items[${id}][item_description]"
          oninput="autoResize(this)"></textarea>
    </td>
    <td class="p-1.5">
        <textarea rows="1"
          class="description w-full text-xs rounded-md border-slate-200 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0 overflow-hidden resize-none"
          name="items[${id}][unit]"
          oninput="autoResize(this)"></textarea>
    </td>
    <td class="p-1.5">
        <input class="qty w-full text-right text-xs rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0"
               type="number"
               name="items[${id}][quantity]">
    </td>
    <td class="p-1.5">
        <input class="unit_cost w-full text-right text-xs font-mono rounded-md border-slate-200 h-7 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0"
               step="0.01"
               type="number"
               name="items[${id}][unit_cost]">
    </td>
    <td class="p-1.5">
        <input readonly
               class="total_amount w-full text-right text-xs font-mono font-semibold rounded-md bg-slate-50 border-slate-200 text-slate-600 h-7 p-1 focus:ring-0"
               step="0.01"
               type="number"
               name="items[${id}][total_amount]">
    </td>
    <td class="p-1.5">
        <textarea rows="1"
          class="description w-full text-xs rounded-md border-slate-200 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0 overflow-hidden resize-none"
          name="items[${id}][brand]"
          oninput="autoResize(this)"></textarea>
    </td>
    <td class="p-1.5">
        <textarea rows="1"
          class="description w-full text-xs rounded-md border-slate-200 p-1 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-0 overflow-hidden resize-none"
          name="items[${id}][remarks]"
          oninput="autoResize(this)"></textarea>
    </td>
    <td class="p-1.5 text-center">
        <button type="button"
                class="removeRow inline-flex items-center justify-center w-6 h-6 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all duration-150">
            ✕
        </button>
    </td>
</tr>
`);

    // ✅ Fix: resize all new textareas in the last row
    const lastRow = document.querySelector('#itemsTable tr:last-child');
    lastRow.querySelectorAll('textarea').forEach(autoResize);

    row++;
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty') || e.target.classList.contains('unit_cost')) {
        const tr = e.target.closest('tr');
        let qty = parseFloat(tr.querySelector('.qty').value) || 0;
        let unit = parseFloat(tr.querySelector('.unit_cost').value) || 0;
        tr.querySelector('.total_amount').value = (qty * unit).toFixed(2);
        calculateGrand();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('removeRow')) {
        e.target.closest('tr').remove();
        calculateGrand();
    }
});

function calculateGrand() {
    let grand = 0;
    document.querySelectorAll('.total_amount').forEach(function(i) {
        grand += parseFloat(i.value) || 0;
    });
    document.getElementById('grandTotal').innerHTML =
        '₱' + grand.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
}

calculateGrand();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const region = document.getElementById('region');
    const province = document.getElementById('province');
    const city = document.getElementById('city');
    const barangay = document.getElementById('barangay');

    province.disabled = true;
    city.disabled = true;
    barangay.disabled = true;

    function resetSelect(select, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
    }

    function populateSelect(select, data, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>`;

        data.forEach(item => {

            const option = document.createElement('option');

            // Save the NAME
            option.value = item.code;

            // Display the NAME
            option.textContent = item.name;

            select.appendChild(option);
        });

        select.disabled = false;
    }

    async function fetchData(url) {

        try {

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Request failed.');
            }

            return await response.json();

        } catch (error) {

            console.error(error);

            return [];
        }
    }

    async function loadRegions() {

        const data = await fetchData('/api/regions');

        populateSelect(region, data, 'Select Region');
    }

    async function loadProvinces(regionCode) {

        province.innerHTML = '<option>Loading...</option>';

        const data = await fetchData(`/api/provinces?region=${regionCode}`);

        if (data.length === 0) {

            // NCR (no province)
            resetSelect(province, 'No Province');

            await loadCities(null, regionCode);

            return;
        }

        populateSelect(province, data, 'Select Province');
    }

    async function loadCities(provinceCode = null, regionCode = null) {

        city.innerHTML = '<option>Loading...</option>';

        let url = '/api/cities?';

        if (provinceCode) {
            url += `province=${provinceCode}`;
        } else {
            url += `region=${regionCode}`;
        }

        const data = await fetchData(url);

        populateSelect(city, data, 'Select City / Municipality');
    }

    async function loadBarangays(cityCode) {

        barangay.innerHTML = '<option>Loading...</option>';

        const data = await fetchData(`/api/barangays?city=${cityCode}`);

        populateSelect(barangay, data, 'Select Barangay');
    }

    // Load regions on page load
    loadRegions();

    // Region changed
    region.addEventListener('change', async function () {

        resetSelect(province, 'Select Province');
        resetSelect(city, 'Select City / Municipality');
        resetSelect(barangay, 'Select Barangay');

        if (!this.value) {
            return;
        }

        await loadProvinces(this.value);

    });

    // Province changed
    province.addEventListener('change', async function () {

        resetSelect(city, 'Select City / Municipality');
        resetSelect(barangay, 'Select Barangay');

        if (!this.value) {
            return;
        }

        await loadCities(this.value);

    });

    // City changed
    city.addEventListener('change', async function () {

        resetSelect(barangay, 'Select Barangay');

        if (!this.value) {
            return;
        }

        await loadBarangays(this.value);

    });

});
</script>
<script>
function autoResize(el) {
    console.log('autoResize');
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}
</script>