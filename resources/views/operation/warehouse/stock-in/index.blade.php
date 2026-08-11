<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- ================= HEADER ================= --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Warehouse Receipt
                    </div>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">📥 Stock In</h1>
                    <p class="mt-2 text-sm text-slate-500">Receive inventory into the warehouse using the approved delivery details.</p>
                </div>

                <a href="{{ route('warehouse.dashboard') }}"
                   class="inline-flex items-center justify-center rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        {{-- ================= SELECTION CARD ================= --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Stock In Information</h2>
                <p class="mt-1 text-sm text-slate-500">Select the project, lot, and receiving warehouse to begin.</p>
            </div>

            <div class="grid gap-6 p-6 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Project <span class="text-red-500">*</span>
                    </label>
                    <select id="project_id"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Project --</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->project_id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Lot <span class="text-red-500">*</span>
                    </label>
                    <select id="lot_id" disabled
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">-- Select Lot --</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Receiving Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select id="warehouse_id"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ================= ALERT BANNER ================= --}}
        <div id="alertBanner" class="hidden rounded-2xl border px-4 py-3 text-sm font-medium"></div>

        {{-- ================= DELIVERY INFO ================= --}}
        <div id="deliveryInfo" class="hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Delivery Information</h2>
            </div>
            <div class="grid gap-6 p-6 md:grid-cols-2 xl:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Project</label>
                    <div id="info_project" class="mt-1 font-semibold text-slate-800">—</div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lot</label>
                    <div id="info_lot" class="mt-1 font-semibold text-slate-800">—</div>
                </div>
            </div>
        </div>
        <div hidden>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">School</label>
                    <div id="info_school" class="mt-1 font-semibold text-slate-800">—</div>
                </div>
                <div hidden>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Delivery Date</label>
                    <div id="info_date" class="mt-1 font-semibold text-slate-800">—</div>
                </div>
        {{-- ================= LOADING SKELETON ================= --}}
        <div id="itemsLoading" class="hidden rounded-3xl border border-slate-200 bg-white p-10 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col items-center justify-center gap-3 text-slate-400">
                <svg class="h-8 w-8 animate-spin text-emerald-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-sm font-medium">Loading delivery items…</p>
            </div>
        </div>

        {{-- ================= ITEMS TABLE ================= --}}
        <div id="itemsSection" class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Delivery Items</h2>
                <p class="mt-1 text-sm text-slate-500">Encode the quantity that was actually received in the warehouse.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Item</th>
                            <th class="px-4 py-3 text-center font-semibold">Received Quantity</th>
                            <th class="px-4 py-3 text-left font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTable" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        {{-- ================= SAVE ACTION ================= --}}
        <div id="saveSection" class="hidden flex items-center justify-end gap-3">
            <span id="saveHint" class="text-sm text-slate-500"></span>
            <button id="btnSaveStockIn"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300">
                <svg id="saveSpinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span id="saveLabel">📥 Complete Stock In</span>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const projectSelect   = document.getElementById('project_id');
            const lotSelect       = document.getElementById('lot_id');
            const warehouseSelect = document.getElementById('warehouse_id');

            const alertBanner   = document.getElementById('alertBanner');
            const deliveryInfo  = document.getElementById('deliveryInfo');
            const itemsLoading  = document.getElementById('itemsLoading');
            const itemsSection  = document.getElementById('itemsSection');
            const itemsTable    = document.getElementById('itemsTable');
            const saveSection   = document.getElementById('saveSection');
            const saveHint      = document.getElementById('saveHint');
            const btnSave       = document.getElementById('btnSaveStockIn');
            const saveSpinner   = document.getElementById('saveSpinner');
            const saveLabel     = document.getElementById('saveLabel');

            const infoProject = document.getElementById('info_project');
            const infoLot     = document.getElementById('info_lot');
            const infoSchool  = document.getElementById('info_school');
            const infoDate    = document.getElementById('info_date');

            let currentDeliveryId = null;

            function showAlert(message, type = 'error') {
                const styles = {
                    error:   'border-red-200 bg-red-50 text-red-700',
                    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                };
                alertBanner.className = `rounded-2xl border px-4 py-3 text-sm font-medium ${styles[type]}`;
                alertBanner.textContent = message;
                alertBanner.classList.remove('hidden');
            }

            function hideAlert() {
                alertBanner.classList.add('hidden');
            }

            function resetDownstream() {
                deliveryInfo.classList.add('hidden');
                itemsLoading.classList.add('hidden');
                itemsSection.classList.add('hidden');
                saveSection.classList.add('hidden');
                itemsTable.innerHTML = '';
                infoProject.textContent = '—';
                infoLot.textContent = '—';
                infoSchool.textContent = '—';
                infoDate.textContent = '—';
                hideAlert();
                currentDeliveryId = null;
            }

            // ---------- Project -> Lots ----------
            projectSelect.addEventListener('change', function () {
                const projectId = this.value;
                lotSelect.innerHTML = '<option value="">-- Select Lot --</option>';
                lotSelect.disabled = true;
                resetDownstream();

                if (!projectId) return;

                fetch(`{{ route('warehouse.stock-in.lots') }}?project_id=${projectId}`)
                    .then(async response => {
                        const text = await response.text();
                        if (!response.ok) throw new Error(text || 'Unable to load lots.');
                        return JSON.parse(text);
                    })
                    .then(data => {
                        if (!data.length) {
                            lotSelect.innerHTML = '<option value="">No lots found for this project</option>';
                            return;
                        }
                        data.forEach(lot => {
                            const option = document.createElement('option');
                            option.value = lot.lot_id;       // primary key on `lot`
                            option.textContent = lot.lot_name; // display field on `lot`
                            lotSelect.appendChild(option);
                        });
                        lotSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error(error);
                        showAlert('Unable to load lots for this project.');
                    });
            });

            // ---------- Lot -> Delivery Items ----------
            lotSelect.addEventListener('change', function () {
                const lotId = this.value;
                resetDownstream();

                if (!lotId) return;

                itemsLoading.classList.remove('hidden');

                fetch(`{{ route('warehouse.stock-in.items') }}?lot_id=${lotId}`)
                    .then(async response => {
                        const text = await response.text();
                        if (!response.ok) throw new Error(text || 'Unable to load delivery items.');
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Invalid response from server.');
                        }
                    })
                    .then(data => {
                        itemsLoading.classList.add('hidden');

                        const items = Array.isArray(data.items) ? data.items : [];
                        currentDeliveryId = data.delivery_id ?? null;

                        infoProject.textContent = data.project || '—';
                        infoLot.textContent     = data.lot || '—';
                        infoSchool.textContent  = data.school || '—';
                        infoDate.textContent    = data.delivery_date || '—';
                        deliveryInfo.classList.remove('hidden');

                        if (!items.length) {
                            itemsTable.innerHTML = `
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No delivery items found for this lot.
                                    </td>
                                </tr>`;
                            itemsSection.classList.remove('hidden');
                            return;
                        }

                        // NOTE: controller returns the delivered amount as `qty`
                        itemsTable.innerHTML = items.map(item => `
                            <tr data-item-id="${item.item_id}">
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    ${item.item_name || 'Unnamed Item'}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="number"
                                        min="0"
                                        class="receivedQty w-24 rounded-xl border border-slate-300 px-3 py-2 text-center text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                </td>

                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        placeholder="Remarks"
                                        class="itemRemarks w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                </td>
                            </tr>
                        `).join('');

                        itemsSection.classList.remove('hidden');
                        saveSection.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error(error);
                        itemsLoading.classList.add('hidden');
                        itemsSection.classList.remove('hidden');
                        itemsTable.innerHTML = `
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">
                                    Unable to load delivery items. Please try again.
                                </td>
                            </tr>`;
                    });
            });

            // ---------- Save ----------
            btnSave.addEventListener('click', function () {
                hideAlert();

                const warehouseId = warehouseSelect.value;
                if (!warehouseId) {
                    showAlert('Please select a receiving warehouse before saving.');
                    warehouseSelect.focus();
                    return;
                }

                const rows = Array.from(itemsTable.querySelectorAll('tr[data-item-id]'));
                if (!rows.length) {
                    showAlert('There are no items to save.');
                    return;
                }

                const items = rows.map(row => ({
                    item_id:       row.dataset.itemId,
                    received_qty:  parseInt(row.querySelector('.receivedQty').value, 10) || 0,
                    remarks:       row.querySelector('.itemRemarks').value.trim(),
                }));

                setSaving(true);

                fetch(`{{ route('warehouse.stock-in.save') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lot_id:        lotSelect.value,
                        warehouse_id:  warehouseId,
                        items,
                    }),
                })
                    .then(async response => {
                        const text = await response.text();
                        if (!response.ok) throw new Error(text || 'Unable to complete stock in.');
                        return JSON.parse(text);
                    })
                    .then(data => {
                        showAlert(data.message || 'Stock in completed successfully.', 'success');
                        saveSection.classList.add('hidden');
                        itemsSection.classList.add('hidden');
                        lotSelect.value = '';
                        lotSelect.dispatchEvent(new Event('change'));
                    })
                    .catch(error => {
                        console.error(error);
                        showAlert('Unable to complete stock in. Please check the entries and try again.');
                    })
                    .finally(() => setSaving(false));
            });

            function setSaving(isSaving) {
                btnSave.disabled = isSaving;
                saveSpinner.classList.toggle('hidden', !isSaving);
                saveLabel.textContent = isSaving ? 'Saving…' : '📥 Complete Stock In';
                saveHint.textContent = isSaving ? 'Please wait, do not close this page.' : '';
            }
        });
    </script>
    @endpush
</x-project_warehouse_app>