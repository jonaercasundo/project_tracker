const monitoring = document.getElementById('delivery-monitoring');

if (monitoring) {
    const syncBillingDialog = document.getElementById('sync-billing-dialog');
    const syncBillingForm = document.getElementById('sync-billing-confirmation');
    const syncSpreadsheetDialog = document.getElementById('sync-spreadsheet-dialog');
    const syncSpreadsheetForm = document.getElementById('sync-spreadsheet-form');
    const spreadsheetSchoolIdInput = document.getElementById('spreadsheet-school-id');
    const spreadsheetSchoolFields = document.getElementById('spreadsheet-school-fields');
    const spreadsheetSchoolRowCount = document.getElementById('spreadsheet-school-row-count');
    const spreadsheetModalFeedback = syncSpreadsheetForm.querySelector('[data-spreadsheet-modal-feedback]');
    const spreadsheetSubmitButton = syncSpreadsheetForm.querySelector('[data-submit-spreadsheet]');
    const spreadsheetDryRun = document.getElementById('spreadsheet-dry-run');
    let pendingSyncBillingButton;
    let pendingSyncSpreadsheetButton;
    const form = document.getElementById('monitoring-filters');
    const region = form.elements.region;
    const division = form.elements.division;
    const municipality = form.elements.municipality;
    const apply = document.getElementById('monitoring-apply');
    const error = document.getElementById('monitoring-error');
    let currentRequest;
    let locationRequest;
    const lotBillingRequests = new Map();
    const projectBillingPanels = new Map();

    async function loadLotBilling(panel) {
        lotBillingRequests.get(panel)?.abort();
        const request = new AbortController();
        lotBillingRequests.set(panel, request);
        panel.dataset.loaded = '';
        panel.setAttribute('aria-busy', 'true');
        panel.textContent = 'Loading Billing by Lot...';
        try {
            const response = await fetch(panel.dataset.endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Your session has expired. Sign in again to load lot billing.');
            }
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? 'Lot billing could not be loaded. Expand details again to retry.');
            }
            panel.innerHTML = result.html || '<p class="text-sm text-slate-500">No lot billing records are available.</p>';
            panel.dataset.loaded = 'true';
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                panel.textContent = exception.message;
            }
        } finally {
            if (lotBillingRequests.get(panel) === request) {
                lotBillingRequests.delete(panel);
                panel.removeAttribute('aria-busy');
            }
        }
    }

    function fillLocations(select, field, values, selected = '') {
        const allLabel = field === 'municipality' ? 'All municipalities' : `All ${field}s`;
        select.replaceChildren(new Option(allLabel, ''));
        for (const value of values) {
            select.add(new Option(value, value));
        }
        if (selected && !values.includes(selected)) {
            select.add(new Option(selected, selected));
        }
        select.value = selected;
    }

    async function updateLocations(resetDivision = false, resetMunicipality = false) {
        locationRequest?.abort();
        const request = new AbortController();
        locationRequest = request;
        const selectedDivision = resetDivision ? '' : division.value;
        const selectedMunicipality = resetMunicipality ? '' : municipality.value;
        const selectedRegion = region.value;
        if (resetDivision) fillLocations(division, 'division', []);
        if (resetMunicipality) fillLocations(municipality, 'municipality', []);
        apply.disabled = true;
        try {
            const options = await Promise.all(['division', 'municipality'].map(async level => {
                const url = new URL(monitoring.dataset.locationsEndpoint);
                url.searchParams.set('level', level);
                if (selectedRegion) url.searchParams.set('region', selectedRegion);
                if (level === 'municipality' && selectedDivision) url.searchParams.set('division', selectedDivision);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal });
                if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Location choices could not be loaded. Refresh the page to retry.');
                }
                return (await response.json()).options;
            }));
            if (locationRequest === request) {
                fillLocations(division, 'division', options[0], selectedDivision);
                fillLocations(municipality, 'municipality', options[1], selectedMunicipality);
            }
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                error.textContent = exception.message;
                error.classList.remove('hidden');
            }
        } finally {
            if (locationRequest === request) {
                locationRequest = undefined;
                apply.disabled = Boolean(currentRequest);
            }
        }
    }

    region.addEventListener('change', () => updateLocations(true, true));
    division.addEventListener('change', () => updateLocations(false, true));
    updateLocations();

    async function loadReport(url, updateHistory = true) {
        if (!url.searchParams.get('project_id')) {
            currentRequest?.abort();
            currentRequest = undefined;
            apply.disabled = Boolean(locationRequest);
            apply.textContent = 'Apply Filters';
            monitoring.removeAttribute('aria-busy');
            error.classList.add('hidden');
            document.getElementById('monitoring-results').replaceChildren();
            document.getElementById('monitoring-table').innerHTML = '<p class="p-8 text-center text-slate-500">Choose a project above to load operational progress.</p>';
            document.getElementById('monitoring-project-count').textContent = 'Choose a project';
            if (updateHistory) {
                window.history.pushState({}, '', url);
            }

            return false;
        }

        currentRequest?.abort();
        const request = new AbortController();
        currentRequest = request;
        apply.disabled = true;
        apply.textContent = 'Applying…';
        monitoring.setAttribute('aria-busy', 'true');
        error.classList.add('hidden');
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error(response.status === 504
                    ? 'Operational progress refresh timed out. Try applying the filters again in a moment.'
                    : 'Your session has expired. Sign in again to refresh progress.');
            }
            const report = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(report.errors ?? {}).flat()[0] ?? 'Progress could not be refreshed. Please try again.');
            }
            lotBillingRequests.forEach(request => request.abort());
            lotBillingRequests.clear();
            projectBillingPanels.clear();
            document.getElementById('monitoring-results').innerHTML = report.summary_html;
            document.getElementById('monitoring-table').innerHTML = report.projects_html;
            document.getElementById('monitoring-project-count').textContent = `${report.summary.projects_count} projects`;
            if (updateHistory) {
                window.history.pushState({}, '', url);
            }
            return true;
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                error.textContent = exception.message;
                error.classList.remove('hidden');
            }

            return false;
        } finally {
            if (currentRequest === request) {
                currentRequest = undefined;
                apply.disabled = Boolean(locationRequest);
                apply.textContent = 'Apply Filters';
                monitoring.removeAttribute('aria-busy');
            }
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        const url = new URL(monitoring.dataset.endpoint);
        const filters = Object.fromEntries(new FormData(form));
        for (const [name, value] of Object.entries(filters)) {
            if (value !== '') {
                url.searchParams.set(name, value);
            }
        }
        loadReport(url);
    });

    function restoreFilters(url) {
        const filters = new URL(url).searchParams;
        for (const name of ['year', 'delivery_status', 'region', 'search', 'sort']) {
            form.elements[name].value = filters.get(name) ?? '';
        }
        form.elements.project_id.value = filters.get('project_id') ?? '';
        form.elements.project_id.dispatchEvent(new Event('change', { bubbles: true }));
        form.elements.direction.value = filters.get('direction') ?? 'asc';
        document.getElementById('monitoring-active').checked = filters.get('active_only') === '1';
        fillLocations(division, 'division', [], filters.get('division') ?? '');
        fillLocations(municipality, 'municipality', [], filters.get('municipality') ?? '');
        updateLocations();
    }

    document.getElementById('monitoring-reset').addEventListener('click', event => {
        event.preventDefault();
        const url = new URL(monitoring.dataset.endpoint);
        restoreFilters(url);
        loadReport(url);
    });
    window.addEventListener('popstate', () => {
        restoreFilters(window.location.href);
        loadReport(new URL(window.location.href), false);
    });
    loadReport(new URL(window.location.href), false);
    monitoring.addEventListener('click', event => {
        const spreadsheetButton = event.target.closest('[data-sync-spreadsheet]');
        if (spreadsheetButton) {
            event.preventDefault();
            pendingSyncSpreadsheetButton = spreadsheetButton;
            syncSpreadsheetDialog.showModal();
            prepareSpreadsheetSync();
            return;
        }

        const syncButton = event.target.closest('[data-sync-billing]');
        if (syncButton) {
            event.preventDefault();
            pendingSyncBillingButton = syncButton;
            syncBillingDialog.showModal();
            return;
        }

        const button = event.target.closest('[data-expand-project]');
        if (!button) {
            return;
        }
        const details = document.getElementById(button.getAttribute('aria-controls'));
        details.hidden = !details.hidden;
        const billingPanel = details.querySelector('[data-lazy-lot-billing]');
        if (!details.hidden && billingPanel) {
            const previousBillingPanel = projectBillingPanels.get(billingPanel.dataset.projectId);
            if (previousBillingPanel && previousBillingPanel !== billingPanel) {
                lotBillingRequests.get(previousBillingPanel)?.abort();
                billingPanel.replaceChildren(...previousBillingPanel.childNodes);
                billingPanel.dataset.loaded = previousBillingPanel.dataset.loaded;
                previousBillingPanel.dataset.loaded = '';
            }
            projectBillingPanels.set(billingPanel.dataset.projectId, billingPanel);
            if (!billingPanel.dataset.loaded) {
                loadLotBilling(billingPanel);
            }
        }
        for (const control of monitoring.querySelectorAll('[data-expand-project]')) {
            if (control.getAttribute('aria-controls') === details.id) {
                control.setAttribute('aria-expanded', String(!details.hidden));
                const chevron = control.querySelector('[data-chevron]');
                if (chevron) {
                    chevron.textContent = details.hidden ? '\u25B8' : '\u25BE';
                }
            }
        }
        button.closest('[data-actions-menu]')?.removeAttribute('open');
    });

    syncBillingForm.addEventListener('submit', event => {
        event.preventDefault();
        if (!pendingSyncBillingButton) {
            return;
        }

        const button = pendingSyncBillingButton;
        const projectId = button.dataset.syncBilling;
        const originalLabel = button.textContent;
        const setFeedback = (message, isError = false) => {
            monitoring.querySelectorAll(`[data-sync-billing-feedback="${projectId}"]`).forEach(feedback => {
                feedback.textContent = message;
                feedback.classList.toggle('text-rose-700', isError);
                feedback.classList.toggle('text-emerald-700', !isError);
            });
        };

        pendingSyncBillingButton = undefined;
        syncBillingDialog.close();
        button.disabled = true;
        button.textContent = 'Syncing…';
        setFeedback('Syncing billing deliveries and inventory…');
        trySyncBilling(button, originalLabel, setFeedback);
    });

    syncBillingDialog.addEventListener('click', event => {
        if (event.target.closest('[data-cancel-sync-billing]')) {
            pendingSyncBillingButton = undefined;
            syncBillingDialog.close();
        }
    });

    syncSpreadsheetForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!pendingSyncSpreadsheetButton) {
            return;
        }

        const button = pendingSyncSpreadsheetButton;
        const projectId = button.dataset.syncSpreadsheet;
        const feedback = [...monitoring.querySelectorAll(`[data-sync-spreadsheet-feedback="${projectId}"]`)];
        const setFeedback = (message, isError = false) => {
            spreadsheetModalFeedback.textContent = message;
            spreadsheetModalFeedback.classList.toggle('text-rose-700', isError);
            spreadsheetModalFeedback.classList.toggle('text-emerald-700', !isError);
            feedback.forEach(element => {
                element.textContent = message;
                element.classList.toggle('text-rose-700', isError);
                element.classList.toggle('text-emerald-700', !isError);
            });
        };
        const modeInput = syncSpreadsheetForm.elements.mode;
        const previewTokenInput = syncSpreadsheetForm.elements.preview_token;
        const isPreview = modeInput.value !== 'confirm';
        const payload = Object.fromEntries(new FormData(syncSpreadsheetForm).entries());
        payload.school_id = spreadsheetSchoolIdInput.value.trim();

        spreadsheetSubmitButton.disabled = true;
        spreadsheetSchoolIdInput.disabled = true;
        setFeedback(`${isPreview ? 'Preparing a read-only preview for' : 'Syncing'} ${spreadsheetSchoolIdInput.value.trim()}...`);

        try {
            const response = await fetch(button.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            const issueDetails = Array.isArray(result.issues) && result.issues.length ? ` ${result.issues.join(' ')}` : '';
            if (!response.ok) {
                throw new Error(`${result.message ?? `Spreadsheet sync failed (HTTP ${response.status}).`}${issueDetails}`);
            }

            if (isPreview) {
                renderSpreadsheetDryRun(result.preview ?? []);
                previewTokenInput.value = result.preview_token ?? '';
                modeInput.value = 'confirm';
                spreadsheetSubmitButton.textContent = 'Confirm Spreadsheet Sync';
                setFeedback(`${result.message} No spreadsheet cells have been changed.`);
            } else {
                setFeedback(`${result.message}${result.skipped_count ? ` ${result.skipped_count} row(s) without a 3PL received date were skipped.` : ''}`);
                syncSpreadsheetDialog.close();
                pendingSyncSpreadsheetButton = undefined;
            }
        } catch (exception) {
            if (!isPreview) {
                modeInput.value = 'preview';
                previewTokenInput.value = '';
                spreadsheetSubmitButton.textContent = 'Preview Changes';
                spreadsheetDryRun.replaceChildren();
                spreadsheetDryRun.hidden = true;
            }
            setFeedback(exception.message, true);
        } finally {
            spreadsheetSubmitButton.disabled = !spreadsheetSchoolIdInput.value;
            spreadsheetSchoolIdInput.disabled = false;
        }
    });

    function renderSpreadsheetDryRun(rows) {
        spreadsheetDryRun.replaceChildren();
        spreadsheetDryRun.hidden = rows.length === 0;
        const heading = document.createElement('p');
        heading.className = 'font-semibold text-slate-900';
        heading.textContent = 'Preview — no cells changed';
        spreadsheetDryRun.append(heading);
        for (const row of rows) {
            const entry = document.createElement('p');
            const mapping = row.mapping
                ? ` · ID mapping ${row.mapping.tracker_school_id} → ${row.mapping.sheet_school_id} (${row.mapping.mapping_id})`
                : '';
            entry.textContent = `${row.school_name || 'School'} · DR ${row.dr} · ${row.package_code} · Lot ${row.lot} · ${row.billing_status} · ${row.compliance_status} · Sheet row ${row.sheet_row} · Cells: ${(row.target_cells ?? []).join(', ') || 'no changes'} · Total ${row.amount}${mapping}`;
            spreadsheetDryRun.append(entry);

            const detailsTable = document.createElement('table');
            detailsTable.className = 'mt-2 w-full table-fixed border-collapse text-left';
            const headerRow = document.createElement('tr');
            const valueRow = document.createElement('tr');
            [
                ['Contact Person', row.contact_person],
                ['Position', row.position],
                ['Telephone 1', row.telephone_1],
            ].forEach(([label, value]) => {
                const heading = document.createElement('th');
                heading.className = 'border border-slate-200 bg-white px-2 py-1 font-semibold';
                heading.textContent = label;
                headerRow.append(heading);

                const cell = document.createElement('td');
                cell.className = 'break-words border border-slate-200 px-2 py-1';
                cell.textContent = value || '—';
                valueRow.append(cell);
            });

            const tableHead = document.createElement('thead');
            tableHead.append(headerRow);
            const tableBody = document.createElement('tbody');
            tableBody.append(valueRow);
            detailsTable.append(tableHead, tableBody);
            spreadsheetDryRun.append(detailsTable);
        }
    }

    function localTodayDate() {
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');

        return `${today.getFullYear()}-${month}-${day}`;
    }

    function resetSpreadsheetFields(enabled) {
        const today = localTodayDate();
        const defaultValues = {
            billing_status: '1st Billing',
            compliance_status: 'COMPLIANT',
            date_checking: today,
            date_encoding: today,
        };
        spreadsheetSchoolFields.querySelectorAll('input, select').forEach(control => {
            control.disabled = !enabled;
            control.value = defaultValues[control.name] ?? '';
        });
    }

    spreadsheetSchoolIdInput.addEventListener('input', () => {
        const hasSchool = spreadsheetSchoolIdInput.value.trim() !== '';
        spreadsheetSchoolFields.hidden = !hasSchool;
        resetSpreadsheetFields(hasSchool);
        spreadsheetSubmitButton.disabled = !hasSchool;
        syncSpreadsheetForm.elements.mode.value = 'preview';
        syncSpreadsheetForm.elements.preview_token.value = '';
        spreadsheetSubmitButton.textContent = 'Preview Changes';
        spreadsheetDryRun.replaceChildren();
        spreadsheetDryRun.hidden = true;
        spreadsheetSchoolRowCount.textContent = hasSchool
            ? 'Enter the Lot details. Preview will verify the tracker DR and exact package against the spreadsheet.'
            : 'Enter a School ID to prepare a preview of matching DR/package rows.';
        spreadsheetModalFeedback.textContent = '';
    });

    syncSpreadsheetForm.querySelectorAll('input:not([type="hidden"]), select').forEach(control => {
        control.addEventListener(control.tagName === 'SELECT' ? 'change' : 'input', () => {
            if (syncSpreadsheetForm.elements.mode.value === 'confirm') {
                syncSpreadsheetForm.elements.mode.value = 'preview';
                syncSpreadsheetForm.elements.preview_token.value = '';
                spreadsheetSubmitButton.textContent = 'Preview Changes';
                spreadsheetDryRun.replaceChildren();
                spreadsheetDryRun.hidden = true;
                spreadsheetModalFeedback.textContent = 'Details changed. Run a new preview before syncing.';
            }
        });
    });

    ['received_by', 'position', 'telephone_1', 'inspection_1', 'inspection_2'].forEach(name => {
        const control = syncSpreadsheetForm.elements[name];
        control.addEventListener('input', () => {
            const cursorStart = control.selectionStart;
            const cursorEnd = control.selectionEnd;
            const uppercaseValue = control.value.toLocaleUpperCase();
            if (control.value !== uppercaseValue) {
                control.value = uppercaseValue;
                control.setSelectionRange(cursorStart, cursorEnd);
            }
        });
    });

    syncSpreadsheetDialog.addEventListener('click', event => {
        if (event.target.closest('[data-cancel-sync-spreadsheet]')) {
            pendingSyncSpreadsheetButton = undefined;
            syncSpreadsheetDialog.close();
        }
    });

    function prepareSpreadsheetSync() {
        spreadsheetSchoolIdInput.value = '';
        spreadsheetSchoolIdInput.disabled = false;
        spreadsheetSchoolFields.hidden = true;
        resetSpreadsheetFields(false);
        spreadsheetSubmitButton.disabled = true;
        syncSpreadsheetForm.elements.mode.value = 'preview';
        syncSpreadsheetForm.elements.preview_token.value = '';
        spreadsheetSubmitButton.textContent = 'Preview Changes';
        spreadsheetDryRun.replaceChildren();
        spreadsheetDryRun.hidden = true;
        spreadsheetSchoolRowCount.textContent = 'Enter a School ID to prepare a preview of matching DR/package rows.';
        spreadsheetModalFeedback.textContent = '';
        document.getElementById('spreadsheet-preview-issues').textContent = '';
    }

    async function trySyncBilling(button, originalLabel, setFeedback) {
        try {
            const response = await fetch(button.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            const contentType = response.headers.get('content-type') ?? '';
            if (!contentType.includes('application/json')) {
                throw new Error(`Sync Billing received an unexpected server response (HTTP ${response.status}). Check the queue worker and Laravel logs.`);
            }

            let result = await response.json();
            if (!response.ok) {
                throw new Error(result.message ?? `Sync Billing could not be queued (HTTP ${response.status}).`);
            }

            if (response.status === 202 && !result.status_url) {
                throw new Error('Sync Billing was queued, but this page cannot track its completion. Reload the latest app version and check the sync status before trying again; it may still be running.');
            }

            if (result.status_url) {
                setFeedback(result.message);
                result = await waitForBillingSync(result.status_url, setFeedback);
            }

            const refreshed = await loadReport(new URL(window.location.href), false);
            if (!refreshed) {
                throw new Error(`${result.message} The sync completed, but pipeline counts could not refresh. Reload the page.`);
            }

            setFeedback(`${result.message} Pipeline counts refreshed.`);
        } catch (exception) {
            setFeedback(exception.message, true);
        } finally {
            if (button.isConnected) {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        }
    }

    async function waitForBillingSync(statusUrl, setFeedback) {
        const startedAt = Date.now();

        while (Date.now() - startedAt < 35 * 60 * 1000) {
            await new Promise(resolve => window.setTimeout(resolve, 2000));

            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const contentType = response.headers.get('content-type') ?? '';
            if (!contentType.includes('application/json')) {
                throw new Error(`Could not check Sync Billing status (HTTP ${response.status}). The job may still be running; check again before starting another sync.`);
            }

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message ?? `Could not check Sync Billing status (HTTP ${response.status}).`);
            }

            if (result.status === 'completed') {
                return result;
            }
            if (result.status === 'failed') {
                throw new Error(result.message ?? 'Sync Billing failed.');
            }

            setFeedback(result.status === 'running' ? 'Sync Billing is processing inventory and delivery stages…' : 'Sync Billing is waiting for a database queue worker…');
        }

        throw new Error('Sync Billing is still processing. Check the project again before starting another sync.');
    }
    monitoring.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            monitoring.querySelectorAll('[data-actions-menu][open]').forEach(menu => menu.removeAttribute('open'));
        }
    });
    monitoring.addEventListener('toggle', event => {
        const menu = event.target;
        if (!menu.matches('[data-actions-menu]') || !menu.open) {
            return;
        }
        monitoring.querySelectorAll('[data-actions-menu][open]').forEach(other => {
            if (other !== menu) {
                other.removeAttribute('open');
            }
        });
        const bounds = menu.querySelector('summary').getBoundingClientRect();
        const popover = menu.querySelector('[data-actions-popover]');
        popover.style.position = 'fixed';
        popover.style.right = 'auto';
        popover.style.left = `${Math.max(8, Math.min(bounds.right - 160, window.innerWidth - 168))}px`;
        popover.style.top = `${Math.max(8, Math.min(bounds.bottom, window.innerHeight - popover.offsetHeight - 8))}px`;
    }, true);
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-actions-menu]')) {
            monitoring.querySelectorAll('[data-actions-menu][open]').forEach(menu => menu.removeAttribute('open'));
        }
    });
}
