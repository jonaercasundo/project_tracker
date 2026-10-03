const monitoring = document.getElementById('delivery-monitoring');

if (monitoring) {
    const form = document.getElementById('monitoring-filters');
    const locations = window.deliveryMonitoringLocations ?? [];
    const region = form.elements.region;
    const division = form.elements.division;
    const municipality = form.elements.municipality;
    const apply = document.getElementById('monitoring-apply');
    const error = document.getElementById('monitoring-error');
    let currentRequest;

    function fillLocations(select, field, rows, selected = '') {
        const values = [...new Set(rows.map(row => row[field]).filter(Boolean))].sort();
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

    function updateLocations(resetDivision = false, resetMunicipality = false) {
        const selectedDivision = resetDivision ? '' : division.value;
        const selectedMunicipality = resetMunicipality ? '' : municipality.value;
        const regionalRows = locations.filter(row => !region.value || row.region === region.value);
        fillLocations(division, 'division', regionalRows, selectedDivision);
        fillLocations(municipality, 'municipality', regionalRows.filter(row => !division.value || row.division === division.value), selectedMunicipality);
    }

    region.addEventListener('change', () => updateLocations(true, true));
    division.addEventListener('change', () => updateLocations(false, true));
    updateLocations();

    async function loadReport(url, updateHistory = true) {
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
                throw new Error('Your session has expired. Sign in again to refresh progress.');
            }
            const report = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(report.errors ?? {}).flat()[0] ?? 'Progress could not be refreshed. Please try again.');
            }
            document.getElementById('monitoring-results').innerHTML = report.summary_html;
            document.getElementById('monitoring-table').innerHTML = report.projects_html;
            document.getElementById('monitoring-project-count').textContent = report.summary.projects_count;
            if (updateHistory) {
                window.history.pushState({}, '', url);
            }
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                error.textContent = exception.message;
                error.classList.remove('hidden');
            }
        } finally {
            if (currentRequest === request) {
                apply.disabled = false;
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
        for (const name of ['year', 'project_id', 'delivery_status', 'region']) {
            form.elements[name].value = filters.get(name) ?? '';
        }
        document.getElementById('monitoring-active').checked = filters.get('active_only') !== '0';
        fillLocations(division, 'division', locations, filters.get('division') ?? '');
        fillLocations(municipality, 'municipality', locations, filters.get('municipality') ?? '');
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
    monitoring.addEventListener('click', event => {
        const button = event.target.closest('[data-expand-project]');
        if (!button) {
            const row = event.target.closest('[data-project-url]');
            if (row && !event.target.closest('a, button, input, select')) {
                window.location.assign(row.dataset.projectUrl);
            }
            return;
        }
        const details = document.getElementById(button.getAttribute('aria-controls'));
        details.hidden = !details.hidden;
        button.setAttribute('aria-expanded', String(!details.hidden));
    });
}
