(() => {

    document.addEventListener('DOMContentLoaded', () => {

        const container = document.querySelector('#filter-container');
        if (!container) return;

        if (window.__FILTER_INIT__) return;
        window.__FILTER_INIT__ = true;

        const project      = container.querySelector('#project');
        const lot          = container.querySelector('#lot');
        const region       = container.querySelector('#region');
        const division     = container.querySelector('#division');
        const municipality = container.querySelector('#municipality');
        let previousProjectValue = project?.value || '';
        
        function fillSelect(select, data, valueField, textField, placeholder) {
            if (!select) return;
            const options = [`<option value="">${placeholder}</option>`];
            for (const row of data || []) {
                options.push(`<option value="${row[valueField]}">${row[textField]}</option>`);
            }
            select.innerHTML = options.join('');
        }

        async function fetchData(url) {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Network error');
                return await res.json();
            } catch (err) {
                console.error('Fetch error:', err);
                return [];
            }
        }

        // =========================
        // PROJECT CHANGE
        // =========================
        project?.addEventListener('change', async () => {
            const selectedProject = project.value;

            if (!selectedProject) {
                previousProjectValue = '';

                return;
            }

            if (selectedProject === previousProjectValue) return;

            previousProjectValue = selectedProject;
            fillSelect(lot, [], '', '', 'Loading...');
            fillSelect(region, [], '', '', 'All Regions');
            fillSelect(division, [], '', '', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');

            const lots = await fetchData(`/filter/lots?project=${encodeURIComponent(project.value)}`);
            if (project.value === selectedProject) {
                fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');
            }
        });

        // =========================
        // LOT CHANGE
        // =========================
        lot?.addEventListener('change', async () => {

            if (!project?.value) {
                console.warn('Project not selected');
                return;
            }

            if (!lot?.value) return;

            const regions = await fetchData(
                `/filter/regions?project=${encodeURIComponent(project.value)}&lot=${encodeURIComponent(lot.value)}`
            );

            fillSelect(region, regions, 'region', 'region', 'All Regions');
        });

        // =========================
        // REGION CHANGE
        // =========================
        region?.addEventListener('change', async () => {

            if (!project?.value || !lot?.value) {
                console.warn('Missing project or lot');
                return;
            }

            const divisions = await fetchData(
                `/filter/divisions?project=${encodeURIComponent(project.value)}`
                + `&lot=${encodeURIComponent(lot.value)}`
                + `&region=${encodeURIComponent(region.value)}`
            );

            fillSelect(division, divisions, 'division', 'division', 'All Divisions');
        });

        // =========================
        // DIVISION CHANGE
        // =========================
        division?.addEventListener('change', async () => {

            if (!project?.value || !lot?.value || !region?.value) return;

            const municipalities = await fetchData(
                `/filter/municipalities?project=${encodeURIComponent(project.value)}`
                + `&lot=${encodeURIComponent(lot.value)}`
                + `&region=${encodeURIComponent(region.value)}`
                + `&division=${encodeURIComponent(division.value)}`
            );

            fillSelect(municipality, municipalities, 'municipality', 'municipality', 'All Municipalities');
        });

        const exportForm = document.getElementById('delivery-export');
        const exportScope = document.getElementById('delivery-export-scope');
        const recordSelector = '.delivery-record-checkbox';

        function updateCheckbox(checkbox, records) {
            const checked = Array.from(records).filter(record => record.checked).length;
            checkbox.checked = checked > 0 && checked === records.length;
            checkbox.indeterminate = checked > 0 && checked < records.length;
        }

        function updateSelection() {
            const selectAll = document.getElementById('select-all-drs');
            if (!selectAll || !exportScope) return;
            const records = document.querySelectorAll(recordSelector);
            updateCheckbox(selectAll, records);

            const selectedOption = exportScope.querySelector('option[value="selected"]');
            selectedOption.disabled = !document.querySelector(`${recordSelector}:checked`);
            if (selectedOption.disabled) exportScope.value = 'filtered';
        }

        document.addEventListener('change', event => {
            if (event.target.id !== 'select-all-drs') return;
            document.querySelectorAll(`.dr-checkbox, ${recordSelector}`).forEach(checkbox => {
                checkbox.checked = event.target.checked;
                checkbox.indeterminate = false;
            });
            updateSelection();
        });

        document.addEventListener('change', event => {
            const checkbox = event.target;
            if (!checkbox.matches(`.dr-checkbox, ${recordSelector}`)) return;
            const group = checkbox.closest('[data-delivery-group]');
            const records = group.querySelectorAll(recordSelector);
            if (checkbox.matches('.dr-checkbox')) {
                records.forEach(record => { record.checked = checkbox.checked; });
            } else {
                updateCheckbox(group.querySelector('.dr-checkbox'), records);
            }
            updateSelection();
        });

        exportForm?.addEventListener('formdata', event => {
            if (exportScope.value !== 'selected') return;
            document.querySelectorAll(`${recordSelector}:checked`).forEach(record => {
                event.formData.append('delivery_ids[]', record.value);
            });
        });

        // =========================
        // RESTORE FILTERS ON RELOAD  ← MUST BE INSIDE HERE
        // =========================
        async function restoreFilters() {

    const params = new URLSearchParams(window.location.search);

    const projectVal      = params.get('project') || '';
    const lotVal          = params.get('lot') || '';
    const regionVal       = params.get('region') || '';
    const divisionVal     = params.get('division') || '';
    const municipalityVal = params.get('municipality') || '';

    // Project and lot are already rendered by Blade @selected
    // so we just need to make sure the DOM values match
    if (project) project.value = projectVal;
    if (lot) lot.value = lotVal;

    if (!lotVal) return;

    const filterParams = new URLSearchParams();
    if (projectVal) filterParams.set('project', projectVal);
    filterParams.set('lot', lotVal);

    // Restore regions
    const regions = await fetchData(
        `/filter/regions?${filterParams.toString()}`
    );
    fillSelect(region, regions, 'region', 'region', 'All Regions');
    region.value = regionVal;

    if (!regionVal) return;

    // Restore divisions
    filterParams.set('region', regionVal);
    const divisions = await fetchData(`/filter/divisions?${filterParams.toString()}`);
    fillSelect(division, divisions, 'division', 'division', 'All Divisions');
    division.value = divisionVal;

    if (!divisionVal) return;

    // Restore municipalities
    filterParams.set('division', divisionVal);
    const municipalities = await fetchData(`/filter/municipalities?${filterParams.toString()}`);
    fillSelect(municipality, municipalities, 'municipality', 'municipality', 'All Municipalities');
    municipality.value = municipalityVal;
}

        restoreFilters(); // ← called here, inside DOMContentLoaded, so DOM vars are in scope

        async function loadDeliveryPhases() {
            if (!project?.value || !document.getElementById('delivery-summary-cards')) return;

            const status = document.getElementById('delivery-loading-status');
            const makePhaseUrl = phase => {
                const url = new URL(window.location.href);
                url.searchParams.set('phase', phase);
                return url;
            };

            try {
                const summaryResponse = await fetch(makePhaseUrl('summary'), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!summaryResponse.ok) throw new Error('Delivery summary could not be loaded.');
                const summary = await summaryResponse.json();
                const statElements = {
                    total_pending: document.getElementById('delivery-stat-pending'),
                    total_stock_out: document.getElementById('delivery-stat-stock-out'),
                    total_delivered: document.getElementById('delivery-stat-delivered'),
                    total_collection: document.getElementById('delivery-stat-billing'),
                    total_billed: document.getElementById('delivery-stat-billed'),
                };
                for (const [key, element] of Object.entries(statElements)) {
                    const target = Number(summary.stats?.[key] ?? 0);
                    if (!element || !Number.isFinite(target)) continue;
                    const startedAt = performance.now();
                    const animate = now => {
                        const progress = Math.min((now - startedAt) / 600, 1);
                        element.textContent = Math.round(target * (1 - (1 - progress) ** 3)).toLocaleString();
                        element.title = target.toLocaleString();
                        if (progress < 1) requestAnimationFrame(animate);
                    };
                    requestAnimationFrame(animate);
                }
                status.textContent = 'Summary loaded · Fetching delivery records…';

                const recordsResponse = await fetch(makePhaseUrl('records'), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!recordsResponse.ok) throw new Error('Delivery records could not be loaded.');
                const records = await recordsResponse.json();
                const renderedPage = new DOMParser().parseFromString(records.html, 'text/html');
                const renderedRecords = renderedPage.getElementById('delivery-records');
                if (!renderedRecords) throw new Error('Delivery records could not be displayed. Refresh the page and try again.');
                document.getElementById('delivery-records').innerHTML = renderedRecords.innerHTML;
                updateSelection();
                status.textContent = 'Delivery records loaded.';
            } catch (exception) {
                status.textContent = exception.message;
            }
        }

        updateSelection();
        loadDeliveryPhases();

    });

})();


// ======================================================
// SAFE GLOBAL ACTIONS
// ======================================================

window.generateQR = function () {
    const ids = Array.from(document.querySelectorAll('.dr-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return alert('Select at least one DR');
    window.open(`/deliveries/pdf?ids=${ids.join(',')}`, '_blank');
};

window.generateLabels = function () {
    const ids = Array.from(document.querySelectorAll('.dr-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return alert('Select at least one DR');

    const tokenEl = document.querySelector('meta[name="csrf-token"]');
    if (!tokenEl) return alert('CSRF token missing');

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/deliveries/labels';
    form.target = '_blank';
    form.innerHTML = `
        <input type="hidden" name="_token" value="${tokenEl.content}">
        <input type="hidden" name="ids" value="${ids.join(',')}">
    `;
    document.body.appendChild(form);
    form.submit();
    form.remove();
};
