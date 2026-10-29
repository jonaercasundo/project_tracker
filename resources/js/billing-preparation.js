import { buildConfirmationPayload, classifyScanRecords, getUnambiguousScanRecord, resetProjectForYearChange } from './billing-preparation-state.js';

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
    const selectAllControl = document.getElementById('billing-preparation-select-all-control');
    const selectAllCheckbox = document.getElementById('billing-preparation-select-all');
    const summary = document.getElementById('billing-preparation-summary');
    const pagination = document.getElementById('billing-preparation-pagination');
    const confirmButton = document.getElementById('billing-preparation-confirm');
    const confirmDialog = document.getElementById('billing-preparation-confirm-dialog');
    const confirmSubmit = document.getElementById('billing-preparation-confirm-submit');
    const confirmationDateInput = document.getElementById('billing-confirmation-date');
    const confirmationDateSummary = document.getElementById('billing-confirm-date-summary');
    const billingSharedFields = document.getElementById('billing-confirm-shared-fields');
    const billingLotCoverage = document.getElementById('billing-confirm-lot-coverage');
    const camera = document.getElementById('billing-preparation-camera');
    const cameraPlaceholder = document.getElementById('billing-preparation-camera-placeholder');
    const cameraSupport = document.getElementById('billing-preparation-camera-support');
    const cameraStart = document.getElementById('billing-preparation-camera-start');
    const cameraStop = document.getElementById('billing-preparation-camera-stop');
    const scanCounters = {
        total: document.getElementById('billing-preparation-scan-total'),
        eligible: document.getElementById('billing-preparation-scan-eligible'),
        duplicate: document.getElementById('billing-preparation-scan-duplicate'),
        rejected: document.getElementById('billing-preparation-scan-rejected'),
    };
    const scanLast = document.getElementById('billing-preparation-scan-last');
    const scanRecent = document.getElementById('billing-preparation-scan-recent');
    const maxBatchSize = Number(root.dataset.maxBatchSize || 500);
    const canConfirm = root.dataset.canConfirm === 'true';
    const selected = new Map();
    let visibleRecords = [];
    let selectionMode = 'scan';
    let activeSearch = null;
    let currentPage = null;
    let allCriteria = null;
    let selectionFilters = null;
    let allExcluded = new Set();
    let allExpected = [];
    let selectionMethods = new Set();
    let cameraStream = null;
    let barcodeDetector = null;
    let lastCameraPayload = '';
    let lastCameraSeenAt = 0;
    let scanBuffer = '';
    let lastScanKeyAt = 0;
    let scanBufferTimer = null;
    const scanStats = { total: 0, eligible: 0, duplicate: 0, rejected: 0 };
    let recentScans = [];

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

    function selectedBillingLotNumbers() {
        return [...new Set([...selected.values()].flatMap((group) => group.lots || [])
            .map((lotName) => String(lotName).match(/^\s*(?:lot\s*)?(2|9|11)\s*$/i)?.[1])
            .filter(Boolean))].sort((first, second) => Number(first) - Number(second));
    }

    function renderBillingLotFields() {
        const lotNumbers = selectedBillingLotNumbers();
        billingSharedFields.classList.toggle('hidden', lotNumbers.length === 0);
        billingLotCoverage.textContent = lotNumbers.length > 0
            ? `The same entered details will be applied to Lot${lotNumbers.length === 1 ? '' : 's'} ${lotNumbers.join(', ')}.`
            : '';
    }

    function billingLotFieldValues() {
        const sharedValues = Object.fromEntries([...billingSharedFields.querySelectorAll('[data-billing-shared-field]')].map((input) => [
            input.dataset.billingSharedField,
            input.value.trim(),
        ]));

        return Object.fromEntries(selectedBillingLotNumbers().map((lotNumber) => [lotNumber, { ...sharedValues }]));
    }

    function recordScan(status, record = null, message = '', displayLabel = null) {
        scanStats.total += 1;
        if (status in scanStats && status !== 'total') scanStats[status] += 1;
        Object.entries(scanStats).forEach(([key, value]) => {
            scanCounters[key].textContent = String(value);
        });

        const displayRecord = record || { dr_no: 'Unrecognized QR', project_name: '', school_name: '' };
        scanLast.textContent = displayLabel || (record
            ? `${record.dr_no} · ${record.project_name} · ${record.school_name}`
            : 'No delivery record identified');
        recentScans.unshift({
            dr_no: displayRecord.dr_no,
            details: [displayRecord.project_name, displayRecord.school_name].filter(Boolean).join(' · '),
            status,
            message,
        });
        recentScans = recentScans.slice(0, 8);
        scanRecent.replaceChildren();
        recentScans.forEach((scan) => {
            const item = document.createElement('li');
            item.className = 'flex flex-wrap items-start justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2';
            const detail = document.createElement('span');
            detail.className = 'min-w-0';
            const dr = document.createElement('span');
            dr.className = 'block font-semibold text-slate-800';
            dr.textContent = scan.dr_no;
            detail.append(dr);
            if (scan.details) {
                const context = document.createElement('span');
                context.className = 'block break-words text-xs text-slate-500';
                context.textContent = scan.details;
                detail.append(context);
            }
            const result = document.createElement('span');
            result.className = scan.status === 'eligible'
                ? 'shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800'
                : (scan.status === 'duplicate'
                    ? 'shrink-0 rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800'
                    : 'shrink-0 rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-800');
            result.textContent = scan.status === 'eligible' ? 'Added' : (scan.status === 'duplicate' ? 'Duplicate' : 'Rejected');
            result.title = scan.message;
            item.append(detail, result);
            scanRecent.append(item);
        });
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
        if (!record.eligible) return false;
        const groupKey = record.group_key;
        const selectedDeliveryCount = [...selected.values()].reduce((total, group) => total + group.eligible_delivery_ids.length, 0);
        if (!selected.has(groupKey) && selectedDeliveryCount + record.eligible_delivery_ids.length > maxBatchSize) {
            showFeedback(`A batch can contain at most ${maxBatchSize} delivery records. Remove a DR group or confirm the current review first.`, 'warning');
            return false;
        }
        const isNewSelection = !selected.has(groupKey);
        selected.set(groupKey, record);
        selectionMethods.add(source);

        return isNewSelection;
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
            allExpected = records.filter((record) => record.eligible).flatMap((record) => record.eligible_delivery_ids);
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
        checkbox.checked = selected.has(record.group_key);
        checkbox.disabled = !record.eligible;
        checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600 disabled:opacity-40';
        checkbox.setAttribute('aria-label', `Select DR ${record.dr_no}, ${record.project_name}, ${record.delivery_count} delivery records`);
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                if (selectionMode === 'all') record.eligible_delivery_ids.forEach((id) => allExcluded.delete(id));
                appendSelected(record, selectionMode === 'all' ? 'all' : selectionMode);
            } else {
                selected.delete(record.group_key);
                if (selectionMode === 'all') record.eligible_delivery_ids.forEach((id) => allExcluded.add(id));
            }
            render();
        });
        addCell(row, checkbox);
        const drCell = document.createElement('div');
        drCell.className = 'font-mono font-bold text-slate-900';
        drCell.textContent = record.dr_no || 'No DR number';
        const countLabel = label(`${record.delivery_count} delivery record${record.delivery_count === 1 ? '' : 's'} · ${record.package_count} linked package${record.package_count === 1 ? '' : 's'}`, 'mt-1 block whitespace-nowrap font-sans text-xs font-medium text-slate-500');
        drCell.append(countLabel);
        addCell(row, drCell);
        const project = document.createElement('span');
        project.className = 'block min-w-48 max-w-sm whitespace-normal break-words font-medium text-slate-800';
        project.textContent = record.project_name;
        addCell(row, project);
        const place = document.createElement('div');
        place.append(label(record.lots.join(', ') || 'No lot recorded', 'block font-semibold text-slate-800'));
        place.append(label(record.schools.join(', ') || 'No school recorded', 'mt-1 block max-w-64 whitespace-normal break-words text-slate-700'));
        addCell(row, place);
        addCell(row, record.records.map((item) => item.delivery_date).filter(Boolean).join(', '), 'whitespace-normal text-slate-600');
        addCell(row, label(record.delivery_status, 'rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700'));
        addCell(row, label(record.pod_state, record.pod_state === 'Available' ? 'text-emerald-700' : 'text-slate-500'));
        addCell(row, record.billing_status.replaceAll('_', ' '), 'capitalize text-slate-700');
        addCell(row, label(record.remarks, record.eligible ? 'font-semibold text-emerald-700' : 'font-semibold text-amber-800'));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'rounded px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50';
        remove.textContent = selected.has(record.group_key) ? 'Remove' : 'Dismiss';
        remove.setAttribute('aria-label', `Remove DR ${record.dr_no} from review`);
        remove.addEventListener('click', () => {
            selected.delete(record.group_key);
            if (selectionMode === 'all') record.eligible_delivery_ids.forEach((id) => allExcluded.add(id));
            visibleRecords = visibleRecords.filter((item) => item.group_key !== record.group_key);
            render();
        });
        addCell(row, remove, 'whitespace-nowrap');
        const detailRow = document.createElement('tr');
        const detailCell = document.createElement('td');
        detailCell.colSpan = 10;
        detailCell.className = 'bg-slate-50 px-5 py-3';
        const details = document.createElement('details');
        const summary = document.createElement('summary');
        summary.className = 'cursor-pointer text-xs font-semibold text-blue-800';
        summary.textContent = `Inspect ${record.delivery_count} linked delivery records and their statuses`;
        const list = document.createElement('div');
        list.className = 'mt-3 grid gap-2 md:grid-cols-2';
        record.records.forEach((item) => {
            const card = document.createElement('div');
            card.className = 'flex flex-wrap items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-xs leading-5';
            const detailsText = document.createElement('span');
            detailsText.textContent = `Delivery #${item.delivery_id} · ${item.lot_name} · ${item.school_name} · ${item.delivery_status} · POD ${item.pod_available ? 'available' : 'not recorded'} · Billing ${item.billing_status.replaceAll('_', ' ')}${item.eligible ? '' : ` · Excluded: ${item.remarks}`}`;
            card.append(detailsText);
            if (canConfirm && item.billing_status === 'for_billing') {
                const undoButton = document.createElement('button');
                undoButton.type = 'button';
                undoButton.className = 'shrink-0 rounded border border-amber-300 px-2.5 py-1 font-semibold text-amber-800 hover:bg-amber-50 disabled:opacity-50';
                undoButton.textContent = 'Undo';
                undoButton.setAttribute('aria-label', `Undo For Billing confirmation for delivery ${item.delivery_id}`);
                undoButton.addEventListener('click', async () => {
                    const confirmed = window.confirm(`Undo For Billing for DR ${record.dr_no}, delivery #${item.delivery_id}? Its billing status will return to Pending Billing. Delivery status and POD will not change.`);
                    if (!confirmed) return;

                    try {
                        setBusy(undoButton, true, 'Undoing...');
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const undoUrl = root.dataset.undoUrlTemplate.replace('__DELIVERY__', encodeURIComponent(item.delivery_id));
                        const response = await fetch(undoUrl, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                        });
                        const payload = await readResponse(response);
                        visibleRecords = visibleRecords.map((group) => group.group_key === payload.group.group_key ? payload.group : group);
                        if (selected.has(payload.group.group_key)) {
                            if (payload.group.eligible) selected.set(payload.group.group_key, payload.group);
                            else selected.delete(payload.group.group_key);
                        }
                        render();
                        showFeedback(payload.message, 'success');
                    } catch (error) {
                        showFeedback(error.message, 'error');
                    } finally {
                        setBusy(undoButton, false, '');
                    }
                });
                card.append(undoButton);
            }
            list.append(card);
        });
        details.append(summary, list);
        detailCell.append(details);
        detailRow.append(detailCell);
        row.dataset.groupKey = record.group_key;
        detailRow.dataset.groupKey = record.group_key;
        row._detailRow = detailRow;
        return row;
    }

    function displayedRecords() {
        const rows = new Map(visibleRecords.map((record) => [record.group_key, record]));
        selected.forEach((record, id) => rows.set(id, record));
        return [...rows.values()];
    }

    function updateSelectAll() {
        const eligibleRecords = visibleRecords.filter((record) => record.eligible);
        const selectedCount = eligibleRecords.filter((record) => selected.has(record.group_key)).length;
        const isSearchResultsPage = selectionMode === 'search' && currentPage !== null;

        selectAllControl.classList.toggle('hidden', !isSearchResultsPage);
        selectAllCheckbox.disabled = !isSearchResultsPage || eligibleRecords.length === 0;
        selectAllCheckbox.checked = eligibleRecords.length > 0 && selectedCount === eligibleRecords.length;
        selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < eligibleRecords.length;
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
            button.addEventListener('click', () => activeSearch && search(activeSearch.field, page, activeSearch.term));
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
            rows.forEach((record) => {
                const row = renderRecord(record);
                results.append(row, row._detailRow);
            });
        }

        const eligibleVisible = visibleRecords.filter((record) => record.eligible).length;
        const excludedVisible = visibleRecords.length - eligibleVisible;
        const selectedProjects = new Set([...selected.values()].map((record) => record.project_name));
        const selectedDeliveries = [...selected.values()].reduce((total, group) => total + group.eligible_delivery_ids.length, 0);
        summary.textContent = `${selected.size} DR group(s) selected · ${selectedDeliveries} eligible delivery record(s) · ${excludedVisible} excluded group(s) in this review${currentPage ? ` · ${currentPage.total} matching DR groups` : ''}${selectedProjects.size ? ` · ${selectedProjects.size} project(s)` : ''}`;
        confirmButton.textContent = selected.size ? `Confirm for Billing (${selected.size})` : 'Confirm for Billing';
        confirmButton.disabled = selected.size === 0 || !canConfirm;
        updateSelectAll();
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
        updateSelectAll();
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

    async function search(field, page = 1, pageTerm = null) {
        const inputId = field === 'dr_search' ? 'billing-preparation-dr-search-input' : 'billing-preparation-school-search-input';
        const term = pageTerm ?? document.getElementById(inputId).value.trim();
        if (!term) {
            showFeedback(field === 'dr_search' ? 'Enter a DR number to search.' : 'Enter a School ID to search.', 'warning');
            return;
        }
        if (page === 1) activeSearch = { field, term };
        const filters = currentFilters();
        delete filters.q;
        const query = { ...filters, [field]: term, page, per_page: 25 };
        try {
            const response = await fetch(`${root.dataset.searchUrl}?${queryString(query)}`, { headers: { Accept: 'application/json' } });
            const payload = await readResponse(response);
            setRecords(payload.records, 'search', { paginationData: payload.pagination });
            const searchLabel = field === 'dr_search' ? 'DR number' : 'School ID';
            showFeedback(`${payload.pagination.total} delivery record(s) match this ${searchLabel} search. Select eligible rows to add them to this review.`, 'success');
        } catch (error) {
            showFeedback(error.message, 'error');
        }
    }

    async function scan(value) {
        try {
            const response = await fetch(`${root.dataset.scanUrl}?${queryString({ q: value })}`, { headers: { Accept: 'application/json' } });
            const payload = await readResponse(response);
            const unambiguousRecord = getUnambiguousScanRecord(payload.records);
            if (payload.records.length === 0) {
                throw new Error('This QR code did not identify exactly one delivery. Use Search DR to choose the correct record.');
            }

            const currentIds = new Set(visibleRecords.map((item) => item.delivery_id));
            payload.records.slice().reverse().forEach((record) => {
                if (!currentIds.has(record.delivery_id)) visibleRecords.unshift(record);
            });
            currentPage = null;
            review.classList.remove('hidden');
            render();
            if (!unambiguousRecord) {
                const drNumber = payload.records[0].dr_no;
                const message = `${payload.records.length} deliveries share DR ${drNumber}. Review project, lot, and school, then select the correct delivery.`;
                recordScan('rejected', {
                    dr_no: drNumber,
                    project_name: `${payload.records.length} matching deliveries`,
                    school_name: '',
                }, message, `${drNumber} · ${payload.records.length} matching deliveries need selection`);
                showFeedback(message, 'warning');
                return;
            }

            const record = unambiguousRecord;
            const [result] = classifyScanRecords([record], selected.keys());
            const wasAdded = result.status === 'eligible' && appendSelected(record, 'scan');
            const scanStatus = result.status === 'eligible' && !wasAdded ? 'rejected' : result.status;
            render();
            const scanMessage = scanStatus === 'rejected'
                ? `The batch limit of ${maxBatchSize} deliveries has been reached.`
                : (scanStatus === 'duplicate' ? 'This delivery is already in the review.' : payload.message);
            recordScan(scanStatus, record, scanMessage);
            showFeedback(
                scanStatus === 'duplicate' ? `${record.dr_no} is already in this review.` : (scanStatus === 'rejected' ? `${record.dr_no}: ${scanMessage}` : payload.message),
                scanStatus === 'eligible' ? 'success' : 'warning',
            );
        } catch (error) {
            recordScan('rejected', null, error.message);
            showFeedback(error.message, 'error');
            throw error;
        }
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

    document.getElementById('billing-preparation-dr-search-form').addEventListener('submit', (event) => {
        event.preventDefault();
        search('dr_search');
    });

    document.getElementById('billing-preparation-school-search-form').addEventListener('submit', (event) => {
        event.preventDefault();
        search('school_id_search');
    });

    selectAllCheckbox.addEventListener('change', () => {
        const eligibleRecords = visibleRecords.filter((record) => record.eligible);
        if (selectAllCheckbox.checked) {
            eligibleRecords.forEach((record) => appendSelected(record, 'search'));
        } else {
            eligibleRecords.forEach((record) => selected.delete(record.group_key));
        }
        render();
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
            confirmDialog.close();
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
        Object.keys(scanStats).forEach((key) => {
            scanStats[key] = 0;
            scanCounters[key].textContent = '0';
        });
        recentScans = [];
        scanRecent.replaceChildren();
        scanLast.textContent = 'Waiting for a scan';
        render();
        showFeedback('Review cleared. No billing records were changed.', 'info');
    });

    tabButtons.forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.billingTab)));

    yearSelect.addEventListener('change', () => {
        resetProjectForYearChange(projectSelect, () => filterForm.requestSubmit());
    });

    confirmButton.addEventListener('click', () => {
        if (!confirmationDateInput.value) {
            showFeedback('Choose a billing confirmation date.', 'warning');
            confirmationDateInput.focus();
            return;
        }
        const projectNames = [...new Set([...selected.values()].map((record) => record.project_name))];
        document.getElementById('billing-confirm-projects').textContent = projectNames.join(', ');
        confirmationDateSummary.textContent = confirmationDateInput.value;
        document.getElementById('billing-confirm-count').textContent = `${selected.size} DR group(s)`;
        document.getElementById('billing-confirm-eligible').textContent = `${selected.size} groups · ${[...selected.values()].reduce((sum, group) => sum + group.eligible_delivery_ids.length, 0)} records`;
        const excludedGroups = visibleRecords.filter((record) => !record.eligible || record.excluded_count > 0).length;
        const excludedRecords = visibleRecords.reduce((sum, group) => sum + group.excluded_count, 0);
        document.getElementById('billing-confirm-excluded').textContent = `${excludedGroups} group(s) · ${excludedRecords} record(s)`;
        renderBillingLotFields();
        confirmDialog.showModal();
    });

    confirmationDateInput.addEventListener('input', () => {
        confirmationDateSummary.textContent = confirmationDateInput.value;
    });

    confirmSubmit?.addEventListener('click', async () => {
        const missingBillingStatus = billingSharedFields.querySelector('[data-billing-shared-field="status"]:invalid');
        if (missingBillingStatus) {
            missingBillingStatus.reportValidity();
            return;
        }
        const body = buildConfirmationPayload({
            selectionMode,
            selectedIds: [...selected.values()].flatMap((group) => group.eligible_delivery_ids),
            selectionMethods,
            allCriteria,
            selectionFilters,
            excludedIds: allExcluded,
            expectedIds: allExpected.filter((id) => !allExcluded.has(id)),
            idempotencyKey: createIdempotencyKey(),
        });
        body.confirmation_date = confirmationDateInput.value;
        body.google_sheet_fields = billingLotFieldValues();

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
            const updated = Array.isArray(payload.updated_records) ? payload.updated_records.map((record) => `DR ${record.dr_no} · #${record.delivery_id}${record.lot_name ? ` · ${record.lot_name}` : ''}`).join('; ') : '';
            const rejected = Array.isArray(payload.rejected_records) ? payload.rejected_records.map((record) => `DR ${record.dr_no} / #${record.delivery_id}: ${record.reason}`).join('; ') : '';
            const sheetSync = payload.sheet_sync;
            const sheetIssues = Array.isArray(sheetSync?.issues) ? sheetSync.issues : [];
            const sheetSummary = sheetSync ? ` Google Sheets: ${sheetSync.message}` : '';
            const tone = sheetSync?.status === 'error' ? 'error' : (rejected ? 'warning' : 'success');
            showFeedback(`${payload.message} Batch ${payload.batch.batch_ref} updated ${payload.batch.delivery_count} delivery record(s) across ${payload.batch.group_count ?? selected.size} DR group(s).${updated ? ` Updated records: ${updated}.` : ''}${rejected ? ` Not updated: ${rejected}` : ''}${sheetSummary}`, tone);
            if (sheetIssues.length > 0) {
                const issueList = document.createElement('ul');
                issueList.className = 'mt-2 list-disc space-y-1 pl-5';
                sheetIssues.forEach((issue) => {
                    const item = document.createElement('li');
                    item.textContent = `DR ${issue.dr_no || 'not available'} · Core Package Code ${issue.package_code || 'not available'} · Lot ${issue.lot_number || 'not identified'} · Delivery #${issue.delivery_id}: ${issue.reason}`;
                    issueList.append(item);
                });
                feedback.append(issueList);
            }
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
        lastCameraPayload = '';
        lastCameraSeenAt = 0;
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
            if (payload) {
                lastCameraSeenAt = Date.now();
                if (payload !== lastCameraPayload) {
                    lastCameraPayload = payload;
                    scan(payload).catch(() => {});
                }
            } else if (Date.now() - lastCameraSeenAt > 1200) {
                lastCameraPayload = '';
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

    const initialDrSearch = document.getElementById('billing-preparation-dr-search-input').value.trim();
    const initialSchoolSearch = document.getElementById('billing-preparation-school-search-input').value.trim();
    if (initialDrSearch && !initialSchoolSearch) {
        activateTab('search');
        search('dr_search');
    } else if (initialSchoolSearch && !initialDrSearch) {
        activateTab('search');
        search('school_id_search');
    }
}
