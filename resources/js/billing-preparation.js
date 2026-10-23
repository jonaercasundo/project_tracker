import { buildConfirmationPayload, resetProjectForYearChange } from './billing-preparation-state.js';

const root = document.getElementById('billing-preparation');

if (root) {
    const filterForm = document.getElementById('billing-preparation-filters');
    const yearSelect = document.getElementById('billing-year');
    const projectSelect = document.getElementById('billing-project');
    const tabButtons = [...root.querySelectorAll('[data-billing-tab]')];
    const tabPanels = [...root.querySelectorAll('[data-billing-panel]')];
    const feedback = document.getElementById('billing-preparation-feedback');
    const review = document.getElementById('billing-preparation-review');
    const results = document.getElementById('billing-preparation-results');
    const summary = document.getElementById('billing-preparation-summary');
    const pagination = document.getElementById('billing-preparation-pagination');
    const confirmButton = document.getElementById('billing-preparation-confirm');
    const confirmDialog = document.getElementById('billing-preparation-confirm-dialog');
    const confirmSubmit = document.getElementById('billing-preparation-confirm-submit');
    const camera = document.getElementById('billing-preparation-camera');
    const cameraPlaceholder = document.getElementById('billing-preparation-camera-placeholder');
    const cameraSupport = document.getElementById('billing-preparation-camera-support');
    const cameraStart = document.getElementById('billing-preparation-camera-start');
    const cameraStop = document.getElementById('billing-preparation-camera-stop');
    const maxBatchSize = Number(root.dataset.maxBatchSize || 500);
    const canConfirm = root.dataset.canConfirm === 'true';
    const selected = new Map();
    let visibleRecords = [];
    let selectionMode = 'scan';
    let currentPage = null;
    let allCriteria = null;
    let selectionFilters = null;
    let allExcluded = new Set();
    let allExpected = [];
    let selectionMethods = new Set();
    let cameraStream = null;
    let barcodeDetector = null;
    let lastCameraPayload = '';
    let scanBuffer = '';
    let lastScanKeyAt = 0;
    let scanBufferTimer = null;

    function showFeedback(message, tone = 'info') {
        feedback.textContent = message;
        feedback.className = 'rounded-lg border px-4 py-3 text-sm';
        feedback.classList.add(...({
            info: ['border-blue-200', 'bg-blue-50', 'text-blue-900'],
            success: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900'],
            warning: ['border-amber-200', 'bg-amber-50', 'text-amber-900'],
            error: ['border-rose-200', 'bg-rose-50', 'text-rose-900'],
        }[tone] || ['border-slate-200', 'bg-slate-50', 'text-slate-800']));
        feedback.classList.remove('hidden');
    }

    async function readResponse(response) {
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const rejected = Array.isArray(payload.rejected) ? payload.rejected.map((record) => `${record.dr_no}: ${record.reason}`).join('; ') : '';
            throw new Error([payload.message || 'The request could not be completed.', rejected].filter(Boolean).join(' '));
        }
        return payload;
    }

    function currentFilters() {
        const filters = Object.fromEntries(new FormData(filterForm).entries());
        Object.keys(filters).forEach((key) => {
            if (filters[key] === '') delete filters[key];
        });
        return filters;
    }

    function appendSelected(record, source) {
        if (!record.eligible) return;
        if (!selected.has(record.delivery_id) && selected.size >= maxBatchSize) {
            showFeedback(`A batch can contain at most ${maxBatchSize} deliveries. Remove a delivery or confirm the current review first.`, 'warning');
            return;
        }
        selected.set(record.delivery_id, record);
        selectionMethods.add(source);
    }

    function setRecords(records, source, { selectEligible = false, paginationData = null, filters = null } = {}) {
        visibleRecords = records;
        currentPage = paginationData;
        selectionMode = source;
        if (source !== 'all') {
            allCriteria = null;
            allExcluded = new Set();
            allExpected = [];
        } else {
            allCriteria = filters;
            allExcluded = new Set();
            allExpected = records.filter((record) => record.eligible).map((record) => record.delivery_id);
        }
        selectionFilters = ['range', 'all'].includes(source) ? filters : null;
        if (selectEligible) {
            records.forEach((record) => appendSelected(record, source));
        }
        review.classList.remove('hidden');
        render();
    }

    function label(text, classes = '') {
        const span = document.createElement('span');
        span.className = classes;
        span.textContent = text;
        return span;
    }

    function addCell(row, content, classes = '') {
        const cell = document.createElement('td');
        cell.className = `px-3 py-3 align-top ${classes}`;
        if (content instanceof Node) cell.append(content);
        else cell.textContent = content;
        row.append(cell);
        return cell;
    }

    function renderRecord(record) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50/70';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = selected.has(record.delivery_id);
        checkbox.disabled = !record.eligible;
        checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600 disabled:opacity-40';
        checkbox.setAttribute('aria-label', `Select DR ${record.dr_no}, ${record.project_name}, ${record.school_name}`);
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                if (selectionMode === 'all') allExcluded.delete(record.delivery_id);
                appendSelected(record, selectionMode === 'all' ? 'all' : selectionMode);
            } else {
                selected.delete(record.delivery_id);
                if (selectionMode === 'all') allExcluded.add(record.delivery_id);
            }
            render();
        });
        addCell(row, checkbox);
        addCell(row, record.dr_no, 'whitespace-nowrap font-mono font-bold text-slate-900');
        const project = document.createElement('span');
        project.className = 'block min-w-48 max-w-sm whitespace-normal break-words font-medium text-slate-800';
        project.textContent = record.project_name;
        addCell(row, project);
        const place = document.createElement('div');
        place.append(label(record.lot_name, 'block font-semibold text-slate-800'));
        place.append(label(record.school_name, 'mt-1 block max-w-64 whitespace-normal break-words text-slate-700'));
        place.append(label(`${record.division} · ${record.school_id}`, 'mt-1 block text-xs text-slate-500'));
        addCell(row, place);
        addCell(row, record.delivery_date, 'whitespace-nowrap text-slate-600');
        addCell(row, label(record.delivery_status, 'rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700'));
        addCell(row, label(record.pod_available ? 'Available' : 'Not recorded', record.pod_available ? 'text-emerald-700' : 'text-slate-500'));
        addCell(row, record.billing_status.replaceAll('_', ' '), 'capitalize text-slate-700');
        addCell(row, label(record.remarks, record.eligible ? 'font-semibold text-emerald-700' : 'font-semibold text-amber-800'));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'rounded px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50';
        remove.textContent = selected.has(record.delivery_id) ? 'Remove' : 'Dismiss';
        remove.setAttribute('aria-label', `Remove DR ${record.dr_no} from review`);
        remove.addEventListener('click', () => {
            selected.delete(record.delivery_id);
            if (selectionMode === 'all') allExcluded.add(record.delivery_id);
            visibleRecords = visibleRecords.filter((item) => item.delivery_id !== record.delivery_id);
            render();
        });
        addCell(row, remove, 'whitespace-nowrap');
        return row;
    }

    function displayedRecords() {
        const rows = new Map(visibleRecords.map((record) => [record.delivery_id, record]));
        selected.forEach((record, id) => rows.set(id, record));
        return [...rows.values()];
    }

    function renderPagination() {
        pagination.replaceChildren();
        if (!currentPage || currentPage.last_page <= 1) return;
        const info = document.createElement('span');
        info.textContent = `Page ${currentPage.current_page} of ${currentPage.last_page} · ${currentPage.total} matching records`;
        pagination.append(info);
        const controls = document.createElement('div');
        controls.className = 'flex gap-2';
        [['Previous', currentPage.current_page - 1], ['Next', currentPage.current_page + 1]].forEach(([text, page]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = text;
            button.disabled = page < 1 || page > currentPage.last_page;
            button.className = 'rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 disabled:opacity-40';
            button.addEventListener('click', () => search(page));
            controls.append(button);
        });
        pagination.append(controls);
    }

    function render() {
        results.replaceChildren();
        const rows = displayedRecords();
        if (rows.length === 0) {
            const empty = document.createElement('tr');
            const cell = addCell(empty, 'No delivery records match this selection method.', 'py-10 text-center text-slate-500');
            cell.colSpan = 10;
            results.append(empty);
        } else {
            rows.forEach((record) => results.append(renderRecord(record)));
        }

        const eligibleVisible = visibleRecords.filter((record) => record.eligible).length;
        const excludedVisible = visibleRecords.length - eligibleVisible;
        const selectedProjects = new Set([...selected.values()].map((record) => record.project_name));
        summary.textContent = `${selected.size} selected · ${selected.size} eligible · ${excludedVisible} excluded in this review${currentPage ? ` · ${currentPage.total} matching results` : ''}${selectedProjects.size ? ` · ${selectedProjects.size} project(s)` : ''}`;
        confirmButton.disabled = selected.size === 0 || !canConfirm;
        renderPagination();
    }

    function activateTab(name) {
        tabButtons.forEach((button) => {
            const active = button.dataset.billingTab === name;
            button.setAttribute('aria-selected', active.toString());
            button.className = active
                ? 'rounded-lg bg-blue-700 px-3 py-2.5 text-sm font-bold text-white'
                : 'rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50';
        });
        tabPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.billingPanel !== name));
        if (allCriteria && name !== 'all') {
            allCriteria = null;
            allExcluded = new Set();
            allExpected = [];
        }
        selectionMode = name;
    }

    function queryString(values) {
        const params = new URLSearchParams();
        Object.entries(values).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') params.set(key, value);
        });
        return params.toString();
    }

    function createIdempotencyKey() {
        if (typeof crypto.randomUUID === 'function') return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }

    async function search(page = 1) {
        const query = { ...currentFilters(), q: document.getElementById('billing-preparation-search-input').value.trim(), page, per_page: 25 };
        try {
            const response = await fetch(`${root.dataset.searchUrl}?${queryString(query)}`, { headers: { Accept: 'application/json' } });
            const payload = await readResponse(response);
            setRecords(payload.records, 'search', { paginationData: payload.pagination });
            showFeedback(`${payload.pagination.total} matching delivery record(s). Select eligible rows to add them to this review.`, 'success');
        } catch (error) {
            showFeedback(error.message, 'error');
        }
    }

    async function scan(value) {
        const response = await fetch(`${root.dataset.scanUrl}?${queryString({ q: value })}`, { headers: { Accept: 'application/json' } });
        const payload = await readResponse(response);
        const currentIds = new Set(visibleRecords.map((record) => record.delivery_id));
        payload.records.forEach((record) => {
            if (!currentIds.has(record.delivery_id)) visibleRecords.unshift(record);
            appendSelected(record, 'scan');
        });
        currentPage = null;
        review.classList.remove('hidden');
        render();
        showFeedback(payload.message, payload.records.some((record) => !record.eligible) ? 'warning' : 'success');
    }

    function setBusy(button, busy, busyText) {
        if (!button.dataset.originalText) button.dataset.originalText = button.textContent;
        button.disabled = busy;
        button.textContent = busy ? busyText : button.dataset.originalText;
    }

    document.getElementById('billing-preparation-scan-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = document.getElementById('billing-preparation-scan-input');
        const value = input.value.trim();
        if (!value) return;
        try {
            await scan(value);
            input.value = '';
            input.focus();
        } catch (error) {
            showFeedback(error.message, 'error');
        }
    });

    document.getElementById('billing-preparation-search-form').addEventListener('submit', (event) => {
        event.preventDefault();
        search(1);
    });

    document.getElementById('billing-preparation-range-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!projectSelect.value || !yearSelect.value) {
            showFeedback('Select a Year and Project before previewing a DR range.', 'warning');
            return;
        }
        const button = event.currentTarget.querySelector('button[type="submit"], button:not([type])');
        const query = { ...currentFilters(), ...Object.fromEntries(new FormData(event.currentTarget).entries()) };
        try {
            setBusy(button, true, 'Loading range…');
            const response = await fetch(`${root.dataset.rangeUrl}?${queryString(query)}`, { headers: { Accept: 'application/json' } });
            const payload = await readResponse(response);
            const selectionFilterValues = {
                year: query.year,
                project_id: query.project_id,
                dr_from: query.dr_from,
                dr_to: query.dr_to,
                lot_id: query.lot_id,
                division: query.division,
                school_id: query.school_id,
                date_from: query.date_from,
                date_to: query.date_to,
                q: query.q,
            };
            Object.keys(selectionFilterValues).forEach((key) => {
                if (selectionFilterValues[key] === undefined || selectionFilterValues[key] === '') delete selectionFilterValues[key];
            });
            setRecords(payload.records, 'range', { selectEligible: true, filters: selectionFilterValues });
            showFeedback(`${payload.summary.eligible} eligible and ${payload.summary.excluded} excluded. Review the complete range before confirming. Missing DR numbers were skipped.`, payload.summary.excluded ? 'warning' : 'success');
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            setBusy(button, false, '');
        }
    });

    document.getElementById('billing-preparation-all-button').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        try {
            setBusy(button, true, 'Loading eligible deliveries…');
            const filters = currentFilters();
            const response = await fetch(`${root.dataset.allUrl}?${queryString(filters)}`, { headers: { Accept: 'application/json' } });
            const payload = await readResponse(response);
            setRecords(payload.records, 'all', { selectEligible: true, filters });
            showFeedback(`${payload.summary.eligible} eligible and ${payload.summary.excluded} excluded. The review was generated from the server-side filters.`, payload.summary.excluded ? 'warning' : 'success');
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            setBusy(button, false, '');
        }
    });

    document.getElementById('billing-preparation-clear').addEventListener('click', () => {
        selected.clear();
        visibleRecords = [];
        selectionMethods = new Set();
        allCriteria = null;
        selectionFilters = null;
        allExcluded = new Set();
        allExpected = [];
        currentPage = null;
        render();
        showFeedback('Review cleared. No billing records were changed.', 'info');
    });

    tabButtons.forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.billingTab)));

    yearSelect.addEventListener('change', () => {
        resetProjectForYearChange(projectSelect, () => filterForm.requestSubmit());
    });

    confirmButton.addEventListener('click', () => {
        const projectNames = [...new Set([...selected.values()].map((record) => record.project_name))];
        document.getElementById('billing-confirm-projects').textContent = projectNames.join(', ');
        document.getElementById('billing-confirm-count').textContent = String(selected.size);
        document.getElementById('billing-confirm-eligible').textContent = String(selected.size);
        const excludedCount = visibleRecords.filter((record) => !record.eligible).length;
        document.getElementById('billing-confirm-excluded').textContent = String(excludedCount);
        confirmDialog.showModal();
    });

    confirmSubmit?.addEventListener('click', async () => {
        const body = buildConfirmationPayload({
            selectionMode,
            selectedIds: selected.keys(),
            selectionMethods,
            allCriteria,
            selectionFilters,
            excludedIds: allExcluded,
            idempotencyKey: createIdempotencyKey(),
        });

        try {
            setBusy(confirmSubmit, true, 'Confirming…');
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(root.dataset.confirmUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(body),
            });
            const payload = await readResponse(response);
            confirmDialog.close();
            showFeedback(`${payload.message} Batch ${payload.batch.batch_ref} contains ${payload.batch.delivery_count} DR(s).`, 'success');
            const link = document.createElement('a');
            link.href = payload.batch.url;
            link.textContent = 'Open batch details';
            link.className = 'ml-2 font-bold underline';
            feedback.append(link);
            selected.clear();
            selectionMethods = new Set();
            allCriteria = null;
            selectionFilters = null;
            allExcluded = new Set();
            allExpected = [];
            render();
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            setBusy(confirmSubmit, false, '');
        }
    });

    function stopCamera() {
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        barcodeDetector = null;
        camera.srcObject = null;
        camera.classList.add('hidden');
        cameraPlaceholder.classList.remove('hidden');
        cameraStop.classList.add('hidden');
        cameraStart.classList.remove('hidden');
    }

    async function readCamera() {
        if (!cameraStream || !barcodeDetector || camera.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) return;
        try {
            const codes = await barcodeDetector.detect(camera);
            const payload = codes[0]?.rawValue;
            if (payload && payload !== lastCameraPayload) {
                lastCameraPayload = payload;
                scan(payload).catch((error) => showFeedback(error.message, 'error'));
                window.setTimeout(() => {
                    if (lastCameraPayload === payload) lastCameraPayload = '';
                }, 1800);
            }
        } catch (error) {
            cameraSupport.textContent = 'Camera could not read this frame. Keep the QR steady or use the manual entry fallback.';
        }
        if (cameraStream) window.setTimeout(readCamera, 350);
    }

    cameraStart.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
            cameraSupport.textContent = 'Camera QR scanning is unavailable in this browser. Use a USB scanner or manual QR entry.';
            return;
        }
        try {
            barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            camera.srcObject = cameraStream;
            await camera.play();
            camera.classList.remove('hidden');
            cameraPlaceholder.classList.add('hidden');
            cameraStart.classList.add('hidden');
            cameraStop.classList.remove('hidden');
            cameraSupport.textContent = 'Camera is active. Point it at a delivery QR code.';
            readCamera();
        } catch (error) {
            stopCamera();
            cameraSupport.textContent = error.name === 'NotAllowedError'
                ? 'Camera access was denied. Allow access in browser settings or use manual entry.'
                : 'Camera could not be started. Use HTTPS, a supported browser, or manual entry.';
        }
    });
    cameraStop.addEventListener('click', stopCamera);
    window.addEventListener('pagehide', stopCamera);

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable="true"]')) return;
        if (event.ctrlKey || event.metaKey || event.altKey) return;
        if (event.key === 'Enter' || event.key === 'Tab') {
            if (scanBuffer.trim().length >= 8) {
                event.preventDefault();
                window.clearTimeout(scanBufferTimer);
                const value = scanBuffer.trim();
                scanBuffer = '';
                scan(value).catch((error) => showFeedback(error.message, 'error'));
            }
            return;
        }
        if (event.key.length !== 1) return;
        const now = Date.now();
        if (now - lastScanKeyAt > 150) scanBuffer = '';
        lastScanKeyAt = now;
        scanBuffer = (scanBuffer + event.key).slice(-2048);
        window.clearTimeout(scanBufferTimer);
        scanBufferTimer = window.setTimeout(() => {
            const value = scanBuffer.trim();
            scanBuffer = '';
            if (value.length >= 8) scan(value).catch((error) => showFeedback(error.message, 'error'));
        }, 250);
    }, true);

    const initialSearch = document.getElementById('billing-preparation-search-input').value.trim();
    if (initialSearch) {
        activateTab('search');
        search(1);
    }
}
