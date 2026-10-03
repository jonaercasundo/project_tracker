const monitoring = document.getElementById('delivery-monitoring');

if (monitoring) {
    const form = document.getElementById('monitoring-filters');
    const region = form.elements.region;
    const division = form.elements.division;
    const municipality = form.elements.municipality;
    const apply = document.getElementById('monitoring-apply');
    const error = document.getElementById('monitoring-error');
    let currentRequest;
    let locationRequest;
    const detailRequests = new Map();
    const projectDetails = new Map();
    let appliedFilters = new URL(window.location.href).searchParams;

    async function loadDetails(panel, page = 1) {
        detailRequests.get(panel)?.abort();
        const request = new AbortController();
        detailRequests.set(panel, request);
        const content = panel.querySelector('[data-detail-content]');
        const url = new URL(panel.dataset.endpoint);
        url.search = appliedFilters.toString();
        panel.dataset.section = panel.querySelector('[data-detail-section]')?.value ?? panel.dataset.section ?? 'warehouse';
        panel.dataset.perPage = panel.querySelector('[data-detail-per-page]')?.value ?? panel.dataset.perPage ?? '25';
        panel.dataset.page = String(page);
        panel.dataset.loaded = '';
        url.searchParams.set('section', panel.dataset.section);
        url.searchParams.set('per_page', panel.dataset.perPage);
        url.searchParams.set('page', page);
        panel.setAttribute('aria-busy', 'true');
        content.textContent = 'Loading records…';
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal });
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Your session has expired. Sign in again to load records.');
            }
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? 'Records could not be loaded. Expand details again to retry.');
            }
            content.innerHTML = result.html;
            panel.dataset.loaded = 'true';
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                panel.dataset.loaded = '';
                content.textContent = exception.message;
            }
        } finally {
            if (detailRequests.get(panel) === request) {
                detailRequests.delete(panel);
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
            detailRequests.forEach(request => request.abort());
            detailRequests.clear();
            projectDetails.clear();
            appliedFilters = new URL(url).searchParams;
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
        for (const name of ['year', 'project_id', 'delivery_status', 'region', 'search', 'sort']) {
            form.elements[name].value = filters.get(name) ?? '';
        }
        form.elements.direction.value = filters.get('direction') ?? 'asc';
        document.getElementById('monitoring-active').checked = filters.get('active_only') !== '0';
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
    monitoring.addEventListener('click', event => {
        const pageButton = event.target.closest('[data-detail-page]');
        if (pageButton && !pageButton.disabled) {
            loadDetails(pageButton.closest('[data-lazy-details]'), Number(pageButton.dataset.detailPage));
            return;
        }
        const button = event.target.closest('[data-expand-project]');
        if (!button) {
            return;
        }
        const details = document.getElementById(button.getAttribute('aria-controls'));
        details.hidden = !details.hidden;
        const panel = details.querySelector('[data-lazy-details]');
        if (!details.hidden && panel) {
            const previous = projectDetails.get(panel.dataset.projectId);
            if (previous && previous !== panel) {
                detailRequests.get(previous)?.abort();
                panel.querySelector('[data-detail-content]').replaceChildren(...previous.querySelector('[data-detail-content]').childNodes);
                panel.dataset.loaded = previous.dataset.loaded;
                panel.dataset.section = previous.dataset.section ?? 'warehouse';
                panel.dataset.perPage = previous.dataset.perPage ?? '25';
                panel.dataset.page = previous.dataset.page ?? '1';
                previous.dataset.loaded = '';
            }
            projectDetails.set(panel.dataset.projectId, panel);
            if (!panel.dataset.loaded) {
                loadDetails(panel, Number(panel.dataset.page ?? 1));
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
    monitoring.addEventListener('change', event => {
        if (event.target.matches('[data-detail-section], [data-detail-per-page]')) {
            loadDetails(event.target.closest('[data-lazy-details]'));
        }
    });
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
