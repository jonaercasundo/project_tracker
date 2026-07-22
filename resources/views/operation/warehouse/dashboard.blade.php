<x-project_warehouse_app>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Inventory Scanner</h1>
                <p class="text-sm text-slate-500">Scan packages or individual items for inventory movement.</p>
            </div>

            <a href="{{ route('warehouse.dashboard') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300">
                ← Back
            </a>
        </div>

        {{-- Current Mode --}}
        <div class="hidden rounded-2xl border border-indigo-200 bg-indigo-50 p-5" id="currentMode">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Current Transaction</p>
                    <h2 class="text-xl font-bold text-indigo-700"><span id="transactionText">-</span></h2>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Scan Type</p>
                    <h2 class="text-xl font-bold text-indigo-700"><span id="scanTypeText">-</span></h2>
                </div>
            </div>
        </div>

        {{-- STEP 1 --}}
        <div id="step1" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-6">Step 1 — Select Transaction</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <button type="button" id="btnInventoryIn"
                    class="rounded-2xl border-2 border-green-500 bg-green-50 hover:bg-green-100 transition p-8">
                    <div class="text-5xl mb-4">📥</div>
                    <div class="text-2xl font-bold text-green-700">Stock In</div>
                    <p class="text-slate-500 mt-2">Add Quantity</p>
                </button>

                <button type="button" id="btnInventoryOut"
                    class="rounded-2xl border-2 border-red-500 bg-red-50 hover:bg-red-100 transition p-8">
                    <div class="text-5xl mb-4">📤</div>
                    <div class="text-2xl font-bold text-red-700">Stock Out</div>
                    <p class="text-slate-500 mt-2">Minus Quantity</p>
                </button>
            </div>
        </div>
        {{-- STEP 2 --}}
        <div id="step2" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold">
                    Step 2 — Select Warehouse
                </h2>

                <button
                    type="button"
                    id="btnBackToStep1"
                    class="text-sm text-slate-500 hover:text-slate-700 underline">

                    ← Change Transaction

                </button>
            </div>

            <div class="max-w-lg">

                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Warehouse
                </label>

                <select
                    id="warehouseSelect"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    <option value="">
                        -- Select Warehouse --
                    </option>

                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->warehouse_id }}">
                            {{ $warehouse->warehouse_name }}
                        </option>
                    @endforeach

                </select>

                <button
                    id="btnContinueWarehouse"
                    type="button"
                    class="mt-6 px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700">

                    Continue →

                </button>

            </div>

        </div>
        {{-- STEP 3 --}}
        <div id="step3" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold">Step 3 — Select Scan Type</h2>
                <button type="button" id="btnBackToTransaction" class="text-sm text-slate-500 hover:text-slate-700 underline">
                    ← Change transaction
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <button type="button" id="btnPackage"
                    class="rounded-2xl border-2 border-blue-500 bg-blue-50 hover:bg-blue-100 transition p-8">
                    <div class="text-5xl mb-4">📦</div>
                    <div class="text-2xl font-bold text-blue-700">Package</div>
                    <p class="text-slate-500 mt-2">Scan Package QR</p>
                </button>

                <button type="button" id="btnItem"
                    class="rounded-2xl border-2 border-amber-500 bg-amber-50 hover:bg-amber-100 transition p-8">
                    <div class="text-5xl mb-4">📄</div>
                    <div class="text-2xl font-bold text-amber-700">Individual Item</div>
                    <p class="text-slate-500 mt-2">Scan Item QR</p>
                </button>
            </div>
        </div>

        {{-- STEP 4 --}}
        <div id="step4" class="hidden space-y-6">

            {{-- Unsaved warning banner --}}
            <div id="unsavedBanner" class="hidden rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm font-medium">
                ⚠️ You have unsaved scans in this session. They are <strong>not yet in the database</strong> — click
                "Save to Database" below when you're done scanning.
            </div>

            {{-- Live Status --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Scanned -->
                <div class="bg-green-50 rounded-xl p-5 border border-green-100 shadow-sm">
                    <p class="text-sm font-medium text-green-800 mb-1">Scanned</p>
                    <h2 id="totalScanned" class="text-3xl font-extrabold text-green-600 tracking-tight">0</h2>
                </div>

                <!-- Ready to Save -->
                <div class="bg-blue-50 rounded-xl p-5 border border-blue-100 shadow-sm">
                    <p class="text-sm font-medium text-blue-800 mb-1">Ready to Save</p>
                    <h2 id="successCount" class="text-3xl font-extrabold text-blue-600 tracking-tight">0</h2>
                </div>

                <!-- Failed -->
                <div class="bg-red-50 rounded-xl p-5 border border-red-100 shadow-sm">
                    <p class="text-sm font-medium text-red-800 mb-1">Failed</p>
                    <h2 id="failedCount" class="text-3xl font-extrabold text-red-600 tracking-tight">0</h2>
                </div>

                <!-- Duplicate -->
                <div class="bg-amber-50 rounded-xl p-5 border border-amber-100 shadow-sm">
                    <p class="text-sm font-medium text-amber-800 mb-1">Duplicate</p>
                    <h2 id="duplicateCount" class="text-3xl font-extrabold text-amber-600 tracking-tight">0</h2>
                </div>
            </div>

            {{-- Hidden scanner input. Uses inline styles (not Tailwind arbitrary-value
                 classes) because those can silently fail to compile depending on your
                 Tailwind content/purge config. Must stay off-screen rather than
                 display:none, since display:none elements cannot receive focus and a
                 USB/Bluetooth barcode scanner just types into whatever is focused. --}}
            <input
                id="scannerInput"
                type="text"
                autocomplete="off"
                aria-hidden="true"
                tabindex="-1"
                style="position: absolute; left: -9999px; top: 0; opacity: 0; width: 1px; height: 1px; pointer-events: none;">

            {{-- Live Table --}}
            <div class="bg-white rounded-xl border overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Package</th>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-left">Qty</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Time</th>
                        </tr>
                    </thead>
                    <tbody id="scanTable" class="divide-y divide-slate-200"></tbody>
                </table>
            </div>
            <div id="reviewSection"
                class="hidden bg-white rounded-xl border overflow-hidden mt-6">

                <div class="px-4 py-3 bg-red-50 border-b">
                    <h3 class="font-bold text-red-700">
                        Review Required
                    </h3>

                    <p class="text-sm text-red-600">
                        These QR codes will NOT be saved.
                    </p>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3">Package</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody id="reviewTable"></tbody>

                </table>

            </div>
            {{-- Save controls --}}
            <div class="flex items-center justify-between bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div>
                    <p class="text-sm text-slate-500">
                        <span id="saveHintCount">0</span> item(s) staged and ready to save.
                    </p>
                    <p id="saveStatusText" class="text-sm mt-1"></p>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="btnNewSession" hidden
                        class="px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300">
                        Start New Scan
                    </button>

                    <button type="button" id="btnSaveToDb" disabled
                        class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        💾 Save to Database
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        // ============================================================
        // STATE
        // ============================================================
        let transactionType = null; // 'IN' | 'OUT'
        let scanType = null;        // 'PACKAGE' | 'ITEM'

        let scannedCount = 0;
        let successCounter = 0;   // "ready to save" count
        let failedCounter = 0;
        let duplicateCounter = 0;
        let rowCounter = 0;

        let isProcessing = false; // guards overlapping validate calls
        let isSaving = false;
        let hasSaved = false;     // true once this batch has been persisted
        let scannerEnabled = false;
        // scannedList: raw QR strings we've SUCCESSFULLY validated this session.
        // Only added to on success, so a failed scan can be retried rather than
        // permanently locked out as a false "Duplicate".
        const scannedList = new Set();

        // stagedItems: one entry per physical scan that validated successfully.
        // This is exactly what gets sent to the batch save endpoint — the backend
        // re-derives quantities per package_status_id, so every scan must be
        // represented here even if several scans get merged for DISPLAY below.
        let stagedItems = [];

        // stagedByItemName: display-only merge map, keyed by normalized item name.
        // Lets two different QR codes that resolve to the same item show as one
        // row with an incrementing qty, without collapsing what actually gets saved.
        const stagedByItemName = new Map();

        let warehouseId = null;
        const warehouseSelect = document.getElementById('warehouseSelect');
        const btnContinueWarehouse = document.getElementById('btnContinueWarehouse');

        // ============================================================
        // DOM REFS
        // ============================================================
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        const step4 = document.getElementById('step4');

        const currentMode = document.getElementById('currentMode');
        const transactionText = document.getElementById('transactionText');
        const scanTypeText = document.getElementById('scanTypeText');
        const scannerInput = document.getElementById('scannerInput');
        const unsavedBanner = document.getElementById('unsavedBanner');

        const totalScannedEl = document.getElementById('totalScanned');
        const successCountEl = document.getElementById('successCount');
        const failedCountEl = document.getElementById('failedCount');
        const duplicateCountEl = document.getElementById('duplicateCount');
        const scanTable = document.getElementById('scanTable');

        const btnSaveToDb = document.getElementById('btnSaveToDb');
        const btnNewSession = document.getElementById('btnNewSession');
        const saveHintCount = document.getElementById('saveHintCount');
        const saveStatusText = document.getElementById('saveStatusText');
        const reviewTable = document.getElementById('reviewTable');
        const reviewSection = document.getElementById('reviewSection');

        let reviewItems = [];
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        }

        function normalizeItemName(name) {
            return String(name ?? '').trim().toLowerCase();
        }

        // ============================================================
        // STEP 1 — Select Transaction
        // ============================================================
        document.getElementById('btnInventoryIn').addEventListener('click', () => selectTransaction('IN', '📥 Stock In'));
        document.getElementById('btnInventoryOut').addEventListener('click', () => selectTransaction('OUT', '📤 Stock Out'));

        function selectTransaction(type, label) {
            transactionType = type;
            transactionText.textContent = label;
            scanTypeText.textContent = '-';
            currentMode.classList.remove('hidden');
            step1.classList.add('hidden');
            step2.classList.remove('hidden');
        }

        // ============================================================
        // STEP 2 — Select Warehouse Type
        // ============================================================
        btnContinueWarehouse.addEventListener('click', () => {

            if (!warehouseSelect.value) {
                alert('Please select a warehouse.');
                warehouseSelect.focus();
                return;
            }

            warehouseId = warehouseSelect.value;

            step2.classList.add('hidden');
            step3.classList.remove('hidden');

        });
        // ============================================================
        // STEP 3 — Select Scan Type
        // ============================================================
        document.getElementById('btnBackToStep1').addEventListener('click', () => {

            transactionType = null;
            warehouseId = null;

            warehouseSelect.value = '';

            currentMode.classList.add('hidden');

            step2.classList.add('hidden');
            step1.classList.remove('hidden');

        });

        document.getElementById('btnPackage').addEventListener('click', () => selectScanType('PACKAGE', '📦 Package'));
        document.getElementById('btnItem').addEventListener('click', () => selectScanType('ITEM', '📄 Individual Item'));

        function selectScanType(type, label) {

            scanType = type;

            scanTypeText.textContent = label;

            step3.classList.add('hidden');
            step4.classList.remove('hidden');

            activateScanner();

        }
        document.getElementById('btnBackToTransaction').addEventListener('click', () => {

            warehouseId = null;
            warehouseSelect.value = '';

            step4.classList.add('hidden');
            step3.classList.add('hidden');
            step2.classList.add('hidden');
            step1.classList.remove('hidden');

        });
        // ============================================================
        // STEP 4 — Scanning (validate only, no DB write yet)
        // ============================================================
        function activateScanner() {
            scannerInput.value = '';
            scannerEnabled = true;
            setTimeout(() => {
                scannerInput.focus();
            }, 100);
        }

        scannerInput.addEventListener('blur', () => {

            if (!scannerEnabled) return;

            setTimeout(() => {

                const active = document.activeElement;

                if (
                    active &&
                    (
                        active.tagName === 'BUTTON' ||
                        active.tagName === 'A' ||
                        active.tagName === 'INPUT' ||
                        active.tagName === 'TEXTAREA' ||
                        active.tagName === 'SELECT'
                    )
                ) {
                    return;
                }

                scannerInput.focus();

            }, 50);

        });

        scannerInput.addEventListener('keydown', async function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            if (isProcessing || isSaving) return;
            if (hasSaved) return; // batch already committed, must start a new session

            const qr = scannerInput.value.trim();
            scannerInput.value = '';
            if (qr === '') return;

            if (scannedList.has(qr)) {
                scannedCount++;
                duplicateCounter++;
                addReviewRow({
                    package_status_id:null,
                    package:'-',
                    item:'-',
                    reason:'Duplicate QR scanned'
                });
                updateDashboard();
                return;
            }

            isProcessing = true;
            try {
                const ok = await validateScan(qr);
                if (ok) {
                    scannedList.add(qr); // only lock in as "seen" once it actually succeeded
                }
            } finally {
                isProcessing = false;
            }
        });

        // Calls a lookup/validate endpoint that returns item details
        // WITHOUT writing anything to the database.
        async function validateScan(qr) {
            try {
                const response = await fetch("{{ route('warehouse.inventory.scan.validate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json', // forces Laravel to return JSON on validation failures instead of a redirect
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        qr: qr,
                        warehouse_id: warehouseId,
                        transaction: transactionType,
                        scan_type: scanType
                    })
                });

                let result;
                try {
                    result = await response.json();
                } catch {
                    console.error('Non-JSON response, status:', response.status, response.statusText);
                    scannedCount++;
                    failedCounter++;
                    addReviewRow({
                        package_status_id: result.package_status_id,
                        package: result.package,
                        item: result.item,
                        reason: result.message
                    });
                    updateDashboard();
                    return false;
                }

                scannedCount++;

                const hasValidPackageName = result.package && String(result.package).trim() !== '';

                if (response.ok && result.success && hasValidPackageName) {
                    successCounter++;

                    const scannedQty = Number(result.qty) || 1;
                    const itemNameKey = normalizeItemName(result.item);

                    // Only merge single-item scans with a real name — never merge
                    // multi-item package summaries (e.g. "3 items").
                    const isMergeable = itemNameKey !== '' && result.item_id !== null;

                    // Always record the full scan — this is what actually gets saved.
                    stagedItems.push({
                        qr: qr,
                        package_status_id: result.package_status_id ?? null,
                        item_id: result.item_id ?? null,
                        package: result.package,
                        item: result.item ?? null,
                        qty: scannedQty
                    });

                    if (isMergeable && stagedByItemName.has(itemNameKey)) {
                        // Same item name as an earlier scan — merge the display row.
                        const existing = stagedByItemName.get(itemNameKey);
                        existing.totalQty += scannedQty;
                        existing.count += 1;
                        updateRowQty(existing.rowEl, existing.totalQty);
                    } else {
                        const rowEl = addRow({
                            package: result.package,
                            item: result.item,
                            qty: scannedQty,
                            status: transactionType === 'IN'
                                ? 'Stock In'
                                : 'Stock Out'
                        });

                        if (isMergeable) {
                            stagedByItemName.set(itemNameKey, {
                                rowEl,
                                totalQty: scannedQty,
                                count: 1
                            });
                        }
                    }

                    updateDashboard();
                    return true;
                } else {
                    failedCounter++;
                    const msg = !hasValidPackageName
                        ? 'No package name found — not staged'
                        : (result.message ?? (result.errors ? Object.values(result.errors).flat().join(', ') : 'Failed'));

                    addRow({
                        package: result.package || '-',
                        item: result.item || '-',
                        qty: result.qty ?? '-',
                        status: msg
                    });
                    updateDashboard();
                    return false;
                }
            } catch (err) {
                console.error(err);
                scannedCount++;
                failedCounter++;
                addReviewRow({
                    package_status_id: result.package_status_id,
                    package: result.package,
                    item: result.item,
                    reason: result.message
                });
                updateDashboard();
                return false;
            } finally {
                scannerInput.focus();
            }
        }

        // ============================================================
        // TABLE / DASHBOARD RENDERING
        // ============================================================
        function updateDashboard() {
            totalScannedEl.textContent = scannedCount;
            successCountEl.textContent = successCounter;
            failedCountEl.textContent = failedCounter;
            duplicateCountEl.textContent = duplicateCounter;

            // Note: this reflects total physical scans staged (stagedItems.length),
            // not the number of visually merged rows — every scan still needs its
            // own package_status_id sent on save, even when several share one row.
            saveHintCount.textContent = stagedItems.length;
            btnSaveToDb.disabled = stagedItems.length === 0 || isSaving || hasSaved;

            unsavedBanner.classList.toggle('hidden', stagedItems.length === 0 || hasSaved);
        }

        function statusBadgeClass(status) {
            switch (status) {
                case 'Staged':
                    return 'bg-blue-100 text-blue-700';
                case 'Duplicate':
                    return 'bg-yellow-100 text-yellow-700';
                case 'Failed':
                    return 'bg-red-100 text-red-700';
                case 'Saved':
                    return 'bg-green-100 text-green-700';
                default:
                    if (/fail|error|invalid/i.test(status)) {
                        return 'bg-red-100 text-red-700';
                    }
                    return 'bg-slate-100 text-slate-700';
            }
        }

        // Returns the created <tr> so callers can update it in place (for merges).
        function addRow(data) {
            rowCounter++;
            const status = data.status ?? '-';
            const badgeClass = statusBadgeClass(status);

            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50';
            row.innerHTML = `
                <td class="px-4 py-3 row-number">${rowCounter}</td>
                <td class="px-4 py-3">${escapeHtml(data.package ?? '-')}</td>
                <td class="px-4 py-3">${escapeHtml(data.item ?? '-')}</td>
                <td class="px-4 py-3 row-qty">${escapeHtml(String(data.qty ?? '-'))}</td>
                <td class="px-4 py-3">
                    <span class="rounded px-2 py-1 ${badgeClass}">${escapeHtml(status)}</span>
                </td>
                <td class="px-4 py-3">${new Date().toLocaleTimeString()}</td>
            `;

            scanTable.insertAdjacentElement('afterbegin', row);
            return row;
        }

        // Bumps the qty cell on an existing row and gives it a brief highlight flash.
        function updateRowQty(rowEl, newQty) {
            const qtyCell = rowEl.querySelector('.row-qty');
            qtyCell.textContent = newQty;

            rowEl.classList.add('bg-indigo-50');
            setTimeout(() => rowEl.classList.remove('bg-indigo-50'), 600);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        function addReviewRow(data) {

            reviewSection.classList.remove('hidden');

            reviewItems.push(data);

            const row = document.createElement('tr');

            row.innerHTML = `
                <td class="px-4 py-3">${escapeHtml(data.package ?? '-')}</td>

                <td class="px-4 py-3">${escapeHtml(data.item ?? '-')}</td>

                <td class="px-4 py-3 text-red-600 font-semibold">
                    ${escapeHtml(data.reason)}
                </td>

                <td class="px-4 py-3">

                    <button
                        class="remove-review
                            bg-red-500
                            text-white
                            px-3
                            py-1
                            rounded"
                        data-id="${data.package_status_id}">

                        Remove

                    </button>

                </td>
            `;

            reviewTable.appendChild(row);
        }
        // ============================================================
        // SAVE TO DATABASE (batch commit)
        // ============================================================
        btnSaveToDb.addEventListener('click', async function () {
            scannerEnabled = false;
            scannerInput.blur();

            if (stagedItems.length === 0 || isSaving || hasSaved) {
                scannerEnabled = true;
                return;
            }
            isSaving = true;
            btnSaveToDb.disabled = true;
            btnSaveToDb.textContent = 'Saving…';
            saveStatusText.textContent = '';
            saveStatusText.className = 'text-sm mt-1';

            try {
                const response = await fetch("{{ route('warehouse.inventory.scan.save') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        warehouse_id: warehouseId,
                        transaction: transactionType,
                        scan_type: scanType,
                        items: stagedItems
                    })
                });

                let result;
                try {
                    result = await response.json();
                } catch {
                    saveStatusText.textContent = `✘ Server error (${response.status}). Your scans are still staged — try again.`;
                    saveStatusText.classList.add('text-red-600', 'font-semibold');
                    btnSaveToDb.disabled = false;
                    btnSaveToDb.textContent = '💾 Save to Database';
                    isSaving = false;
                    return;
                }

                if (response.ok) {

                    const savedCount = Array.isArray(result.saved) ? result.saved.length : 0;
                    const failedCount = Array.isArray(result.failed) ? result.failed.length : 0;

                    if (failedCount === 0) {

                        hasSaved = true;

                        saveStatusText.textContent =
                            `✔ Saved ${savedCount} item(s) to the database.`;

                        saveStatusText.className =
                            'text-green-600 font-semibold';

                        unsavedBanner.classList.add('hidden');

                        btnSaveToDb.textContent = '✔ Saved';

                    } else {

                        saveStatusText.textContent =
                            `⚠ Saved ${savedCount}, Failed ${failedCount}`;

                        saveStatusText.className =
                            'text-amber-600 font-semibold';

                        btnSaveToDb.disabled = false;
                        btnSaveToDb.textContent = '💾 Save to Database';

                        console.table(result.failed);

                    }

                } else {

                    saveStatusText.textContent =
                        result.message ?? 'Unknown error';

                    saveStatusText.className =
                        'text-red-600 font-semibold';

                    btnSaveToDb.disabled = false;
                    btnSaveToDb.textContent = '💾 Save to Database';

                }
            } catch (err) {
                console.error(err);
                saveStatusText.textContent = '✘ Network error — your scans are still staged. Try saving again.';
                saveStatusText.classList.add('text-red-600', 'font-semibold');
                btnSaveToDb.disabled = false;
                btnSaveToDb.textContent = '💾 Save to Database';
            } finally {

                isSaving = false;

                if (!hasSaved) {

                    scannerEnabled = true;

                    setTimeout(() => {
                        scannerInput.focus();
                    }, 100);

                }

            }
        });

        // ============================================================
        // START NEW SCAN (full reset)
        // ============================================================
        btnNewSession.addEventListener('click', function () {
            if (stagedItems.length > 0 && !hasSaved) {
                const confirmed = confirm('You have unsaved scans that have not been saved to the database. Discard them and start a new scan?');
                if (!confirmed) return;
            }

            transactionType = null;
            scanType = null;
            scannedCount = 0;
            successCounter = 0;
            failedCounter = 0;
            duplicateCounter = 0;
            rowCounter = 0;

            stagedItems = [];
            scannedList.clear();
            stagedByItemName.clear();

            hasSaved = false;
            isSaving = false;
            scannerEnabled = false;
            scannerInput.blur();
            scanTable.innerHTML = '';
            saveStatusText.textContent = '';
            btnSaveToDb.textContent = '💾 Save to Database';

            currentMode.classList.add('hidden');
            warehouseId = null;
            warehouseSelect.value = '';

            step4.classList.add('hidden');
            step3.classList.add('hidden');
            step2.classList.add('hidden');
            step1.classList.remove('hidden');

            updateDashboard();
        });
    })();
    </script>
    @endpush
</x-project_warehouse_app>