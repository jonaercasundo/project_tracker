<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Warehouse Receipt
                    </div>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">📥 Stock In</h1>
                    <p class="mt-2 text-sm text-slate-500">Receive inventory into the warehouse using the approved details.</p>
                </div>

                <a href="{{ route('warehouse.dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Stock In Information</h2>
                <p class="mt-1 text-sm text-slate-500">Select the project, lot to begin receiving inventory.</p>
            </div>

            <div class="grid gap-6 p-6 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Project <span class="text-red-500">*</span></label>
                    <select id="project_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Project --</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->project_id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Lot <span class="text-red-500">*</span></label>
                    <select id="lot_id" disabled class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Lot --</option>
                    </select>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const projectSelect = document.getElementById('project_id');
                const lotSelect = document.getElementById('lot_id');

                projectSelect.addEventListener('change', function () {
                    const projectId = this.value;
                    lotSelect.innerHTML = '<option value="">-- Select Lot --</option>';
                    lotSelect.disabled = true;

                    if (!projectId) {
                        return;
                    }

                    fetch(`{{ route('warehouse.stock-in.lots') }}?project_id=${projectId}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(lot => {
                                const option = document.createElement('option');
                                option.value = lot.lot_id;
                                option.textContent = lot.lot_name;
                                lotSelect.appendChild(option);
                            });
                            lotSelect.disabled = false;
                        });
                });

                lotSelect.addEventListener('change', function () {
                    const lotId = this.value;

                    const itemsTable = document.getElementById('itemsTable');
                    const itemsSection = document.getElementById('itemsSection');
                    const saveSection = document.getElementById('saveSection');
                    const deliveryInfo = document.getElementById('deliveryInfo');
                    const infoProject = document.getElementById('info_project');
                    const infoLot = document.getElementById('info_lot');
                    const infoSchool = document.getElementById('info_school');
                    const infoDate = document.getElementById('info_date');

                    itemsTable.innerHTML = '';
                    infoProject.textContent = '';
                    infoLot.textContent = '';
                    infoSchool.textContent = '';
                    infoDate.textContent = '';

                    itemsSection.classList.add('hidden');
                    saveSection.classList.add('hidden');
                    deliveryInfo.classList.add('hidden');

                    if (!lotId) {
                        return;
                    }

                    fetch(`{{ route('warehouse.stock-in.items') }}?lot_id=${lotId}`)
                        .then(async response => {
                            const text = await response.text();
                            if (!response.ok) {
                                throw new Error(text || 'Unable to load delivery items.');
                            }

                            try {
                                return JSON.parse(text);
                            } catch (error) {
                                throw new Error('Invalid response from server.');
                            }
                        })
                        .then(data => {
                            const items = Array.isArray(data.items) ? data.items : [];

                            infoProject.textContent = data.project || '';
                            infoLot.textContent = data.lot || '';
                            infoSchool.textContent = data.school || '';
                            infoDate.textContent = data.delivery_date || '';

                            if (items.length) {
                                itemsTable.innerHTML = items.map(item => `
                                <tr class="border-t">

                                    <td class="px-4 py-3">

                                        <div class="font-semibold">
                                            ${item.item_name}
                                        </div>

                                    </td>

                                    <td class="px-4 py-3 text-center">

                                        ${item.delivered_qty}

                                    </td>

                                    <td class="px-4 py-3 text-center">

                                        <input
                                            type="number"
                                            min="0"
                                            max="${item.delivered_qty}"
                                            value="${item.delivered_qty}"
                                            data-delivered="${item.delivered_qty}"
                                            class="receivedQty w-24 rounded-lg border px-2 py-1 text-center">

                                    </td>

                                    <td class="px-4 py-3 text-center">

                                        <span class="remainingQty">
                                            0
                                        </span>

                                    </td>

                                    <td class="px-4 py-3">

                                        <input
                                            type="text"
                                            class="w-full rounded-lg border px-2 py-1"
                                            placeholder="Remarks">

                                    </td>

                                </tr>
                                `).join('');
                                itemsSection.classList.remove('hidden');
                                saveSection.classList.remove('hidden');
                                deliveryInfo.classList.remove('hidden');
                            } else {
                                itemsTable.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No delivery items found for this lot.</td></tr>';
                                itemsSection.classList.remove('hidden');
                                deliveryInfo.classList.remove('hidden');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            itemsTable.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-sm text-red-600">Unable to load delivery items.</td></tr>';
                            itemsSection.classList.remove('hidden');
                        });
                });
            });
            document.querySelectorAll('.receivedQty').forEach(input => {

    input.addEventListener('input', function () {

        let delivered = parseInt(this.dataset.delivered);

        let received = parseInt(this.value);

        if (isNaN(received))
            received = 0;

        if (received > delivered)
            received = delivered;

        if (received < 0)
            received = 0;

        this.value = received;

        this.closest('tr')
            .querySelector('.remainingQty')
            .textContent = delivered - received;

    });

});
        </script>
        @endpush

        <div id="deliveryInfo" class="hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Delivery Information</h2>
            </div>
            <div class="grid gap-6 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Project</label>
                    <div id="info_project" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lot</label>
                    <div id="info_lot" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">School</label>
                    <div id="info_school" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Delivery Date</label>
                    <div id="info_date" class="mt-1 font-semibold text-slate-800"></div>
                </div>
            </div>
        </div>

        <div id="itemsSection" class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">
                    Delivery Items
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Encode the quantity that was actually received in the warehouse.
                </p>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-100">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Item
                            </th>

                            <th class="px-4 py-3 text-center">
                                Delivered
                            </th>

                            <th class="px-4 py-3 text-center">
                                Received
                            </th>

                            <th class="px-4 py-3 text-center">
                                Remaining
                            </th>

                            <th class="px-4 py-3">
                                Remarks
                            </th>

                        </tr>

                    </thead>

                    <tbody id="itemsTable">

                    </tbody>

                </table>

            </div>

        </div>

        <div id="saveSection" class="hidden flex justify-end">
            <button id="btnSaveStockIn" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-emerald-700">
                📥 Complete Stock In
            </button>
        </div>
    </div>
    
</x-project_warehouse_app>