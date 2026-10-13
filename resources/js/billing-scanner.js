const root = document.querySelector('#billing-scanner');

if (root) {
    const camera = document.querySelector('#billing-camera');
    const placeholder = document.querySelector('#camera-placeholder');
    const startButton = document.querySelector('#start-camera');
    const stopButton = document.querySelector('#stop-camera');
    const cameraSupport = document.querySelector('#camera-support');
    const searchForm = document.querySelector('#manual-search');
    const searchInput = document.querySelector('#dr-search');
    const hardwareStatus = document.querySelector('#hardware-scanner-status');
    const feedback = document.querySelector('#billing-feedback');
    const results = document.querySelector('#billing-results');
    const selectAllCheckbox = document.querySelector('#billing-select-all');
    const clearRecordsButton = document.querySelector('#billing-clear-records');
    const selectionSummary = document.querySelector('#billing-selection-summary');
    const bulkButtons = document.querySelector('#billing-bulk-buttons');
    const bulkBilledForm = document.querySelector('#billing-bulk-billed-form');
    const bulkBilledLabel = document.querySelector('#billing-bulk-billed-label');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let stream = null;
    let detector = null;
    let lookupInProgress = false;
    let lastPayload = '';
    let records = [];
    const selectedDeliveryIds = new Set();
    let scannerQueue = [];
    let processingScannerQueue = false;
    let lastHardwarePayload = '';
    let lastHardwareScanAt = 0;
    let scannerInputBuffer = '';
    let scannerInputTimer = null;
    let lastScannerKeyAt = 0;

    const urls = {
        lookup: root.dataset.lookupUrl,
        mark: root.dataset.markUrl,
        billed: root.dataset.billedUrl,
        hold: root.dataset.holdUrl,
        release: root.dataset.releaseUrl,
    };

    function showFeedback(message, tone = 'info') {
        feedback.textContent = message;
        feedback.className = 'rounded-lg border px-4 py-3 text-sm';
        feedback.classList.add(...({
            success: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900'],
            warning: ['border-amber-200', 'bg-amber-50', 'text-amber-900'],
            error: ['border-rose-200', 'bg-rose-50', 'text-rose-900'],
            info: ['border-blue-200', 'bg-blue-50', 'text-blue-900'],
        }[tone] ?? ['border-blue-200', 'bg-blue-50', 'text-blue-900']));
        feedback.classList.remove('hidden');
    }

    function addText(parent, label, value) {
        const item = document.createElement('div');
        const title = document.createElement('dt');
        const content = document.createElement('dd');
        title.className = 'text-[11px] font-bold uppercase tracking-wide text-slate-500';
        content.className = 'mt-1 break-words text-sm font-medium text-slate-900';
        title.textContent = label;
        content.textContent = value || '—';
        item.append(title, content);
        parent.append(item);
    }

    function actionButton(label, className, action) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.className = className;
        button.addEventListener('click', action);
        return button;
    }

    function endpoint(template, deliveryId) {
        return template.replace('__delivery__', encodeURIComponent(deliveryId));
    }

    async function postAction(url, body, confirmation) {
        if (!window.confirm(confirmation)) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });
            const payload = await response.json();

            if (!response.ok) {
                showFeedback(payload.message ?? 'The billing status was not changed.', response.status === 409 ? 'warning' : 'error');
                return;
            }

            showFeedback(payload.message, 'success');
            records = records.map((record) => record.delivery_id === payload.record.delivery_id ? payload.record : record);
            selectedDeliveryIds.delete(payload.record.delivery_id);
            renderRecords();
        } catch (error) {
            showFeedback('The billing request could not be completed. Check the connection and try again.', 'error');
            console.error(error);
        }
    }

    async function postBatchAction(actionRecords, urlTemplate, body, actionLabel) {
        const recordList = actionRecords.map((record) => `DR ${record.dr_no}`).join(', ');
        if (!window.confirm(`${actionLabel} ${actionRecords.length} selected record(s)?\n\n${recordList}`)) {
            return;
        }

        let completed = 0;
        const failures = [];

        for (const record of actionRecords) {
            try {
                const response = await fetch(endpoint(urlTemplate, record.delivery_id), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(body),
                });
                const payload = await response.json();

                if (!response.ok) {
                    failures.push(`DR ${record.dr_no}: ${payload.message ?? 'The billing status was not changed.'}`);
                    continue;
                }

                records = records.map((current) => current.delivery_id === payload.record.delivery_id ? payload.record : current);
                selectedDeliveryIds.delete(payload.record.delivery_id);
                completed += 1;
                renderRecords();
            } catch (error) {
                failures.push(`DR ${record.dr_no}: the billing request could not be completed.`);
                console.error(error);
            }
        }

        if (failures.length > 0) {
            showFeedback(
                `${completed} record(s) updated. ${failures.length} could not be updated: ${failures.join(' ')}`,
                completed > 0 ? 'warning' : 'error'
            );
            return;
        }

        showFeedback(`${completed} record(s) ${actionLabel.toLowerCase()} successfully.`, 'success');
    }

    function renderRecord(record) {
        const card = document.createElement('article');
        card.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';

        const header = document.createElement('div');
        header.className = 'flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4';
        const dr = document.createElement('h3');
        dr.className = 'font-mono text-base font-bold text-slate-950';
        dr.textContent = `DR ${record.dr_no}`;
        const recordCheckbox = document.createElement('input');
        recordCheckbox.type = 'checkbox';
        recordCheckbox.checked = selectedDeliveryIds.has(record.delivery_id);
        recordCheckbox.className = 'rounded border-slate-300 text-blue-700 focus:ring-blue-600';
        recordCheckbox.setAttribute('aria-label', `Select DR ${record.dr_no}, ${record.school ?? record.school_id}, ${record.project ?? ''}`);
        recordCheckbox.addEventListener('change', () => {
            if (recordCheckbox.checked) {
                selectedDeliveryIds.add(record.delivery_id);
            } else {
                selectedDeliveryIds.delete(record.delivery_id);
            }
            renderBulkActions();
        });
        const status = document.createElement('span');
        status.className = 'rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-800';
        status.textContent = record.billing_status.replaceAll('_', ' ');
        const identity = document.createElement('div');
        identity.className = 'flex items-center gap-3';
        identity.append(recordCheckbox, dr);
        header.append(identity, status);

        const details = document.createElement('dl');
        details.className = 'grid gap-4 p-5 sm:grid-cols-2';
        addText(details, 'Project', record.project);
        addText(details, 'School', record.school ? `${record.school} · ${record.school_id}` : record.school_id);
        addText(details, 'Delivery date', record.delivery_date);
        addText(details, 'Delivery status', record.delivery_status);
        addText(details, 'Billing status', record.billing_status.replaceAll('_', ' '));
        addText(details, 'Invoice', record.invoice_no ? `${record.invoice_no} · ${record.invoice_date ?? ''}` : 'Not recorded');
        if (record.invoice_no) {
            addText(details, 'Invoice amount', record.invoice_amount);
            addText(details, 'Invoice payment status', record.invoice_status ?? 'Not recorded');
        }
        if (record.billing_reference) {
            addText(details, 'Billing reference', record.billing_reference);
        }
        if (record.billing_remarks) {
            addText(details, 'Billing remarks', record.billing_remarks);
        }

        const actions = document.createElement('div');
        actions.className = 'flex flex-wrap gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4';
        const deliveryId = record.delivery_id;

        if (record.next_action === 'mark_for_billing') {
            actions.append(actionButton(
                'Confirm: mark For Billing',
                'rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                () => postAction(endpoint(urls.mark, deliveryId), {}, `Mark DR ${record.dr_no} For Billing?`)
            ));
        }

        if (record.next_action === 'confirm_billed') {
            const form = document.createElement('form');
            form.className = 'grid w-full gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2';
            form.innerHTML = `
                <p class="sm:col-span-2 text-sm font-bold text-slate-900">Invoice details are required before confirming Billed.</p>
                <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice number
                    <input name="invoice_no" maxlength="100" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice date
                    <input name="invoice_date" type="date" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice total
                    <input name="amount" type="number" min="0" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery billing amount (optional)
                    <input name="billing_amount" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-600 sm:col-span-2">Billing reference
                    <input name="billing_reference" maxlength="255" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-600 sm:col-span-2">Remarks
                    <textarea name="billing_remarks" rows="2" maxlength="5000" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </label>
                <div class="sm:col-span-2">
                    <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">Review and confirm Billed</button>
                </div>`;
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!form.reportValidity()) {
                    return;
                }
                const body = Object.fromEntries(new FormData(form).entries());
                await postAction(endpoint(urls.billed, deliveryId), body, `Confirm DR ${record.dr_no} as Billed with the invoice details entered?`);
            });
            actions.append(form);
        }

        if (record.next_action === 'ineligible') {
            const note = document.createElement('p');
            note.className = 'text-sm font-medium text-amber-800';
            note.textContent = 'Billing transitions are available only while logistics status is Delivered or Accepted.';
            actions.append(note);
        } else if (record.next_action === 'awaiting_authorized_user') {
            const note = document.createElement('p');
            note.className = 'text-sm text-slate-600';
            note.textContent = 'This stage is waiting for a user with the required billing permission.';
            actions.append(note);
        } else if (record.next_action === 'billed') {
            const note = document.createElement('p');
            note.className = 'text-sm font-semibold text-emerald-800';
            note.textContent = 'Already billed. Scanning again will not change this record.';
            actions.append(note);
        } else if (record.next_action === 'on_hold') {
            const note = document.createElement('p');
            note.className = 'text-sm font-semibold text-rose-800';
            note.textContent = 'On hold. Contact a billing exception manager to release it.';
            actions.append(note);
        }

        if (root.dataset.canHold === 'true' && ['pending_billing', 'for_billing'].includes(record.billing_status)) {
            actions.append(actionButton(
                'Place On Hold',
                'rounded-lg border border-rose-300 px-4 py-2.5 text-sm font-bold text-rose-800 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600',
                async () => {
                    const remarks = window.prompt('Enter the missing document or discrepancy:');
                    if (remarks === null || remarks.trim() === '') {
                        showFeedback('A reason is required to place a delivery On Hold.', 'warning');
                        return;
                    }
                    await postAction(endpoint(urls.hold, deliveryId), { remarks }, `Place DR ${record.dr_no} On Hold?`);
                }
            ));
        }

        if (record.next_action === 'release_hold') {
            actions.append(actionButton(
                'Release billing hold',
                'rounded-lg border border-blue-300 px-4 py-2.5 text-sm font-bold text-blue-800 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                () => postAction(endpoint(urls.release, deliveryId), {}, `Release the billing hold for DR ${record.dr_no}?`)
            ));
        }

        card.append(header, details, actions);
        return card;
    }

    function renderBulkActions() {
        const selectedRecords = records.filter((record) => selectedDeliveryIds.has(record.delivery_id));
        const forBillingRecords = selectedRecords.filter((record) => record.next_action === 'mark_for_billing');
        const billedRecords = selectedRecords.filter((record) => record.next_action === 'confirm_billed');

        selectAllCheckbox.disabled = records.length === 0;
        selectAllCheckbox.checked = records.length > 0 && selectedRecords.length === records.length;
        selectAllCheckbox.indeterminate = selectedRecords.length > 0 && selectedRecords.length < records.length;
        clearRecordsButton.disabled = records.length === 0;
        selectionSummary.textContent = `${selectedRecords.length} of ${records.length} record(s) selected. `
            + `${forBillingRecords.length} ready for For Billing; ${billedRecords.length} ready for Billed.`;
        bulkButtons.replaceChildren();

        if (forBillingRecords.length > 0) {
            bulkButtons.append(actionButton(
                `Confirm For Billing (${forBillingRecords.length})`,
                'rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                () => postBatchAction(forBillingRecords, urls.mark, {}, 'Mark For Billing')
            ));
        }

        if (billedRecords.length > 0) {
            bulkBilledLabel.textContent = `Invoice details will be applied to ${billedRecords.length} selected record(s).`;
            bulkBilledForm.classList.remove('hidden');
        } else {
            bulkBilledForm.classList.add('hidden');
        }
    }

    function renderRecords() {
        results.replaceChildren();
        if (records.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-500';
            empty.textContent = 'No delivery records are available.';
            results.append(empty);
        } else {
            records.forEach((record) => results.append(renderRecord(record)));
        }
        renderBulkActions();
    }

    async function lookup(value, source) {
        if (lookupInProgress) {
            return;
        }

        lookupInProgress = true;
        try {
            const url = new URL(urls.lookup, window.location.origin);
            url.searchParams.set('q', value);
            url.searchParams.set('source', source);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json();

            if (!response.ok) {
                showFeedback(payload.message ?? 'Unable to identify a delivery from that input.', response.status === 410 ? 'warning' : 'error');
                return;
            }

            const knownDeliveryIds = new Set(records.map((record) => record.delivery_id));
            const newRecords = payload.records.filter((record) => {
                if (knownDeliveryIds.has(record.delivery_id)) {
                    return false;
                }
                knownDeliveryIds.add(record.delivery_id);
                return true;
            });
            records = [...records, ...newRecords];
            renderRecords();
            if (newRecords.length === 0) {
                showFeedback('This delivery was already scanned. No duplicate was added.', 'warning');
                hardwareStatus.textContent = 'Repeated delivery ignored. Ready for the next label.';
            } else {
                const duplicateCount = payload.records.length - newRecords.length;
                const message = duplicateCount > 0
                    ? `${newRecords.length} record(s) added; ${duplicateCount} duplicate record(s) ignored.`
                    : `${newRecords.length} record(s) added. ${payload.message}`;
                showFeedback(message, duplicateCount > 0 || newRecords.length > 1 ? 'warning' : 'success');
                hardwareStatus.textContent = `${records.length} unique delivery record(s) scanned. Ready for the next label.`;
            }
        } catch (error) {
            showFeedback('The delivery lookup could not be completed. Check the connection and try again.', 'error');
            console.error(error);
        } finally {
            lookupInProgress = false;
        }
    }

    async function processScannerQueue() {
        if (processingScannerQueue) {
            return;
        }
        processingScannerQueue = true;
        while (scannerQueue.length > 0) {
            const payload = scannerQueue.shift();
            await lookup(payload, 'qr');
        }
        processingScannerQueue = false;
        if (scannerQueue.length === 0) {
            hardwareStatus.textContent = 'Ready — scan another delivery label.';
        }
    }

    function queueHardwarePayload(value) {
        const payload = value.trim();
        if (!payload) {
            return;
        }

        const now = Date.now();
        if (payload === lastHardwarePayload && now - lastHardwareScanAt < 1500) {
            hardwareStatus.textContent = 'Repeated scan ignored. Ready for the next label.';
            return;
        }

        lastHardwarePayload = payload;
        lastHardwareScanAt = now;
        scannerQueue.push(payload);
        hardwareStatus.textContent = 'Reading scanned label…';
        processScannerQueue();
    }

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable="true"]')) {
            return;
        }

        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        const queueBufferedScan = () => {
            const payload = scannerInputBuffer;
            scannerInputBuffer = '';
            scannerInputTimer = null;
            if (payload.trim().length >= 2) {
                queueHardwarePayload(payload);
            }
        };

        if (event.key === 'Enter' || event.key === 'Tab') {
            if (scannerInputBuffer.length >= 2) {
                event.preventDefault();
                window.clearTimeout(scannerInputTimer);
                queueBufferedScan();
            }
            return;
        }

        if (event.key.length !== 1) {
            return;
        }

        const now = Date.now();
        if (now - lastScannerKeyAt > 150) {
            scannerInputBuffer = '';
        }
        lastScannerKeyAt = now;
        scannerInputBuffer = (scannerInputBuffer + event.key).slice(-2048);
        window.clearTimeout(scannerInputTimer);
        scannerInputTimer = window.setTimeout(queueBufferedScan, 180);
    }, true);

    selectAllCheckbox.addEventListener('change', () => {
        if (selectAllCheckbox.checked) {
            records.forEach((record) => selectedDeliveryIds.add(record.delivery_id));
        } else {
            records.forEach((record) => selectedDeliveryIds.delete(record.delivery_id));
        }
        renderRecords();
    });

    clearRecordsButton.addEventListener('click', () => {
        records = [];
        selectedDeliveryIds.clear();
        renderRecords();
        showFeedback('Scanned list cleared.', 'info');
    });

    bulkBilledForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!bulkBilledForm.reportValidity()) {
            return;
        }
        const billedRecords = records.filter((record) => (
            selectedDeliveryIds.has(record.delivery_id) && record.next_action === 'confirm_billed'
        ));
        if (billedRecords.length === 0) {
            showFeedback('Select at least one eligible For Billing record first.', 'warning');
            return;
        }
        await postBatchAction(
            billedRecords,
            urls.billed,
            Object.fromEntries(new FormData(bulkBilledForm).entries()),
            'Confirm Billed'
        );
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const value = searchInput.value.trim();
        if (!value) {
            return;
        }
        const isQrPayload = value.startsWith('MMCBILLING:1:') || value.startsWith('MMCBILLING:2:')
            || /^https:\/\/philgeps\.metro-mobilia\.com\/entry\.php(?:\?|$)/i.test(value);
        lookup(value, isQrPayload ? 'qr' : 'manual');
        searchInput.value = '';
    });

    async function scanFrame() {
        if (!stream || !detector || camera.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            return;
        }

        try {
            const codes = await detector.detect(camera);
            const value = codes[0]?.rawValue;
            if (value && value !== lastPayload && !lookupInProgress) {
                lastPayload = value;
                lookup(value, 'qr');
                window.setTimeout(() => {
                    if (lastPayload === value) {
                        lastPayload = '';
                    }
                }, 2500);
            }
        } catch (error) {
            showFeedback('The camera could not read this frame. Keep the label steady or use DR search.', 'warning');
            console.error(error);
        }
    }

    async function cameraLoop() {
        if (!stream) {
            return;
        }
        await scanFrame();
        window.setTimeout(cameraLoop, 300);
    }

    startButton.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            cameraSupport.textContent = 'This browser does not include built-in QR detection. Use a current Chrome or Edge browser, or search by DR number below.';
            cameraSupport.classList.remove('hidden');
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            cameraSupport.textContent = 'Camera access is unavailable. Use HTTPS or localhost, or search by DR number below.';
            cameraSupport.classList.remove('hidden');
            return;
        }

        try {
            detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            camera.srcObject = stream;
            await camera.play();
            camera.classList.remove('hidden');
            placeholder.classList.add('hidden');
            startButton.classList.add('hidden');
            stopButton.classList.remove('hidden');
            cameraSupport.classList.add('hidden');
            cameraLoop();
        } catch (error) {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            detector = null;
            cameraSupport.textContent = error.name === 'NotAllowedError'
                ? 'Camera permission was denied. Allow camera access in the browser settings, or search by DR number.'
                : 'Camera could not be started. Use a supported browser or search by DR number.';
            cameraSupport.classList.remove('hidden');
            console.error(error);
        }
    });

    stopButton.addEventListener('click', () => {
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        detector = null;
        camera.srcObject = null;
        camera.classList.add('hidden');
        placeholder.classList.remove('hidden');
        stopButton.classList.add('hidden');
        startButton.classList.remove('hidden');
    });

    window.addEventListener('pagehide', () => stream?.getTracks().forEach((track) => track.stop()));
}
