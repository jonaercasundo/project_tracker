<x-project_warehouse_app>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Inventory Scanner</h1>
                <p class="text-sm text-slate-500">Scan packages or individual items for inventory movement.</p>
            </div>

            <a href="{{ route('warehouse.inventory.index') }}"
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
                    <div class="text-2xl font-bold text-green-700">Inventory In</div>
                    <p class="text-slate-500 mt-2">Add Quantity</p>
                </button>

                <button type="button" id="btnInventoryOut"
                    class="rounded-2xl border-2 border-red-500 bg-red-50 hover:bg-red-100 transition p-8">
                    <div class="text-5xl mb-4">📤</div>
                    <div class="text-2xl font-bold text-red-700">Inventory Out</div>
                    <p class="text-slate-500 mt-2">Minus Quantity</p>
                </button>
            </div>
        </div>

        {{-- STEP 2 --}}
        <div id="step2" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold">Step 2 — Select Scan Type</h2>
                <button type="button" id="btnBackToStep1" class="text-sm text-slate-500 hover:text-slate-700 underline">
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

        {{-- STEP 3 --}}
        <div id="step3" class="hidden space-y-6">

            {{-- Unsaved warning banner --}}
            <div id="unsavedBanner" class="hidden rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-800 text-sm font-medium">
                ⚠️ You have unsaved scans in this session. They are <strong>not yet in the database</strong> — click
                "Save to Database" below when you're done scanning.
            </div>

            {{-- Live Status --}}
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-green-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Scanned</p>
                    <h2 id="totalScanned" class="text-3xl font-bold">0</h2>
                </div>
                <div class="bg-blue-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Ready to Save</p>
                    <h2 id="successCount" class="text-3xl font-bold text-blue-600">0</h2>
                </div>
                <div class="bg-red-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Failed</p>
                    <h2 id="failedCount" class="text-3xl font-bold text-red-600">0</h2>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 border">
                    <p class="text-sm text-gray-500">Duplicate</p>
                    <h2 id="duplicateCount" class="text-3xl font-bold text-yellow-600">0</h2>
                </div>
            </div>

            {{-- Hidden Scanner --}}
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

            {{-- Save controls --}}
            <div class="flex items-center justify-between bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div>
                    <p class="text-sm text-slate-500">
                        <span id="saveHintCount">0</span> item(s) staged and ready to save.
                    </p>
                    <p id="saveStatusText" class="text-sm mt-1"></p>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="btnNewSession"
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
    let transactionType = null;
    let scanType = null;

    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');

    const currentMode = document.getElementById('currentMode');
    const transactionText = document.getElementById('transactionText');
    const scanTypeText = document.getElementById('scanTypeText');
    const scannerInput = document.getElementById('scannerInput');
    const unsavedBanner = document.getElementById('unsavedBanner');

    let scannedCount = 0;
    let successCounter = 0; // "ready to save" count
    let failedCounter = 0;
    let duplicateCounter = 0;
    let isProcessing = false; // guards overlapping validate calls
    let isSaving = false;
    let hasSaved = false; // becomes true once this batch is persisted

    const totalScannedEl = document.getElementById('totalScanned');
    const successCountEl = document.getElementById('successCount');
    const failedCountEl = document.getElementById('failedCount');
    const duplicateCountEl = document.getElementById('duplicateCount');
    const scanTable = document.getElementById('scanTable');
    const scannedList = new Set();

    const btnSaveToDb = document.getElementById('btnSaveToDb');
    const btnNewSession = document.getElementById('btnNewSession');
    const saveHintCount = document.getElementById('saveHintCount');
    const saveStatusText = document.getElementById('saveStatusText');

    // Items staged in memory — only these get sent to the DB on Save.
    // Only successfully-validated (non-duplicate, non-failed) scans go here.
    let stagedItems = [];

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    // ================================
    // STEP 1
    // ================================
    document.getElementById('btnInventoryIn').addEventListener('click', () => selectTransaction('IN', '📥 Inventory In'));
    document.getElementById('btnInventoryOut').addEventListener('click', () => selectTransaction('OUT', '📤 Inventory Out'));

    function selectTransaction(type, label) {
        transactionType = type;
        transactionText.textContent = label;
        scanTypeText.textContent = '-';
        currentMode.classList.remove('hidden');
        step1.classList.add('hidden');
        step2.classList.remove('hidden');
    }

    // ================================
    // STEP 2
    // ================================
    document.getElementById('btnBackToStep1').addEventListener('click', () => {
        transactionType = null;
        currentMode.classList.add('hidden');
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
    });

    document.getElementById('btnPackage').addEventListener('click', () => selectScanType('PACKAGE', '📦 Package'));
    document.getElementById('btnItem').addEventListener('click', () => selectScanType('ITEM', '📄 Individual Item'));

    function selectScanType(type, label) {
        scanType = type;
        scanTypeText.textContent = label;
        step2.classList.add('hidden');
        step3.classList.remove('hidden');
        activateScanner();
    }

    // ================================
    // STEP 3 — scanning (validate only, no DB write)
    // ================================
    scannerInput.addEventListener('keydown', async function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        if (isProcessing || isSaving) return;
        if (hasSaved) return; // session already saved, must start new scan

        const qr = scannerInput.value.trim();
        scannerInput.value = '';
        if (qr === '') return;

        if (scannedList.has(qr)) {
            scannedCount++;
            duplicateCounter++;
            addRow({ package: '-', item: '-', qty: '-', status: 'Duplicate' });
            updateDashboard();
            return;
        }

        scannedList.add(qr);

        isProcessing = true;
        try {
            await validateScan(qr);
        } finally {
            isProcessing = false;
        }
    });

    function activateScanner() {
        scannerInput.value = '';
        scannerInput.focus();
    }

    scannerInput.addEventListener('blur', function () {
        setTimeout(() => scannerInput.focus(), 50);
    });

    // Calls a lookup/validate endpoint that returns item details
    // WITHOUT writing anything to the database.
    async function validateScan(qr) {
        try {
            const response = await fetch("{{ route('warehouse.inventory.scan.validate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    qr: qr,
                    transaction: transactionType,
                    scan_type: scanType
                })
            });

            const result = await response.json();
            scannedCount++;

            if (response.ok && result.success) {
                successCounter++;

                // Stage the item locally — this is what gets sent on Save.
                stagedItems.push({
                    qr: qr,
                    package_status_id: result.package_status_id ?? null,
                    package: result.package ?? null,
                    item: result.item ?? null,
                    qty: result.qty ?? null
                });

                addRow({
                    package: result.package,
                    item: result.item,
                    qty: result.qty,
                    status: 'Staged'
                });
            } else {
                failedCounter++;
                addRow({
                    package: result.package ?? '-',
                    item: result.item ?? '-',
                    qty: result.qty ?? '-',
                    status: result.message ?? 'Failed'
                });
            }

            updateDashboard();
        } catch (err) {
            console.error(err);
            scannedCount++;
            failedCounter++;
            addRow({ package: '-', item: '-', qty: '-', status: 'Network error' });
            updateDashboard();
        } finally {
            scannerInput.focus();
        }
    }

    function updateDashboard() {
        totalScannedEl.textContent = scannedCount;
        successCountEl.textContent = successCounter;
        failedCountEl.textContent = failedCounter;
        duplicateCountEl.textContent = duplicateCounter;

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

    function addRow(data) {
        const status = data.status ?? '-';
        const badgeClass = statusBadgeClass(status);

        const row = `
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">${scannedCount}</td>
                <td class="px-4 py-3">${escapeHtml(data.package ?? '-')}</td>
                <td class="px-4 py-3">${escapeHtml(data.item ?? '-')}</td>
                <td class="px-4 py-3">${escapeHtml(String(data.qty ?? '-'))}</td>
                <td class="px-4 py-3">
                    <span class="rounded px-2 py-1 ${badgeClass}">${escapeHtml(status)}</span>
                </td>
                <td class="px-4 py-3">${new Date().toLocaleTimeString()}</td>
            </tr>
        `;

        scanTable.insertAdjacentHTML('afterbegin', row);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ================================
    // SAVE TO DATABASE (batch commit)
    // ================================
    btnSaveToDb.addEventListener('click', async function () {
        if (stagedItems.length === 0 || isSaving || hasSaved) return;

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
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    transaction: transactionType,
                    scan_type: scanType,
                    items: stagedItems
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                hasSaved = true;
                saveStatusText.textContent = `✔ Saved ${stagedItems.length} item(s) to the database.`;
                saveStatusText.classList.add('text-green-600', 'font-semibold');
                unsavedBanner.classList.add('hidden');
                btnSaveToDb.textContent = '✔ Saved';
                // lock the scanner — this batch is committed, must start a new session to scan more
            } else {
                // Save failed — keep everything staged so the user can retry, don't lose data.
                saveStatusText.textContent = `✘ Save failed: ${result.message ?? 'Unknown error'}. Your scans are still staged — try again.`;
                saveStatusText.classList.add('text-red-600', 'font-semibold');
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
        }
    });

    // ================================
    // START NEW SCAN (reset everything)
    // ================================
    btnNewSession.addEventListener('click', function () {
        if (stagedItems.length > 0 && !hasSaved) {
            const confirmed = confirm('You have unsaved scans that have not been saved to the database. Discard them and start a new scan?');
            if (!confirmed) return;
        }

        // Reset all state
        transactionType = null;
        scanType = null;
        scannedCount = 0;
        successCounter = 0;
        failedCounter = 0;
        duplicateCounter = 0;
        stagedItems = [];
        scannedList.clear();
        hasSaved = false;
        isSaving = false;

        scanTable.innerHTML = '';
        saveStatusText.textContent = '';
        btnSaveToDb.textContent = '💾 Save to Database';
        currentMode.classList.add('hidden');
        step3.classList.add('hidden');
        step1.classList.remove('hidden');

        updateDashboard();
    });
    </script>
    @endpush
</x-project_warehouse_app>