const pendingTabs = new Set();
const tables = new Map();

window.loadProjectDetails = (section) => {
    const table = tables.get(section);
    if (table) {
        if (!table.loaded && !table.loading) table.load();
    } else {
        pendingTabs.add(section);
    }
};

// Existing or future CRUD handlers can refresh a table without reloading the project.
window.refreshProjectDetails = (section) => tables.get(section)?.refresh();
document.addEventListener('project-details:refresh', (event) => window.refreshProjectDetails(event.detail?.section));

function initializeProjectDetails() {
    const definitions = {
        schools: { body: 'schoolsTableBody', buttons: 'schoolsPaginationButtons', columns: 8, fields: { search: 'schoolSearch', region: 'schoolRegion', division: 'schoolDivision', municipality: 'schoolMunicipality' } },
        items: { body: 'itemsTableBody', buttons: 'itemsPaginationButtons', columns: 5, fields: { search: 'itemSearch', item_type: 'itemTypeFilter' } },
        packages: { body: 'packagesTableBody', buttons: 'packagesPaginationButtons', columns: 5, fields: { search: 'packageSearch', package_type: 'packageTypeFilter', lot: 'packageLotFilter', keystage: 'packageKeystageFilter' } },
        lots: { body: 'lotsTableBody', buttons: 'lotsPaginationButtons', columns: 4, fields: {} },
        keystage: { body: 'keystageTableBody', buttons: 'keystagesPaginationButtons', columns: 4, fields: { search: 'keystageSearch', lot: 'keystageLotFilter' } },
    };

    document.querySelectorAll('[data-project-detail][data-lazy="true"]').forEach((container) => {
        const section = container.dataset.projectDetail;
        const config = definitions[section];
        const body = document.getElementById(config.body);
        const buttons = document.getElementById(config.buttons);
        const fields = Object.fromEntries(Object.entries(config.fields).map(([name, id]) => [name, document.getElementById(id)]));
        let page = 1;
        let request;
        let optionsLoaded = false;
        let timer;
        let optionVersion = 0;

        const perPage = document.createElement('select');
        perPage.setAttribute('aria-label', `Rows per page for ${section}`);
        perPage.className = 'rounded-lg border border-slate-200 text-xs text-slate-600';
        [25, 50, 100].forEach((value) => perPage.add(new Option(`${value} per page`, value)));
        buttons.parentElement.appendChild(perPage);

        const parameters = () => new URLSearchParams(Object.entries(fields).map(([name, field]) => [name, field?.value || '']));
        const get = async (url, signal) => {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal });
            if (!response.ok) throw new Error('Unable to load records. Please retry.');
            return response.json();
        };
        const status = (message, retry = false) => {
            const row = body.insertRow();
            const cell = row.insertCell();
            cell.colSpan = config.columns;
            cell.className = 'px-6 py-12 text-center text-slate-500';
            cell.setAttribute('role', 'status');
            cell.textContent = message;
            if (retry) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = 'Retry';
                button.className = 'ml-3 text-blue-600 font-bold';
                button.onclick = () => table.load();
                cell.append(button);
            }
        };
        const fillOptions = async (field, level, reset = true, optionPage = 1) => {
            if (!field) return;
            const version = optionVersion;
            const selected = field.value;
            const params = parameters();
            params.set('section', section);
            params.set('level', level);
            params.set('page', optionPage);
            const data = await get(`${container.dataset.optionsEndpoint}?${params}`, request?.signal);
            if (version !== optionVersion) return;
            if (reset) field.length = 1;
            else field.querySelector('option[value="__more__"]')?.remove();
            data.options.forEach((option) => field.add(new Option(option.label ?? option, option.value ?? option)));
            if (data.next_page) {
                const more = new Option('Load more options…', '__more__');
                more.dataset.page = data.next_page;
                field.add(more);
            }
            field.value = selected;
        };
        const loadOptions = async () => {
            if (section === 'schools') {
                await Promise.all([fillOptions(fields.region, 'region'), fillOptions(fields.division, 'division'), fillOptions(fields.municipality, 'municipality')]);
            } else if (section === 'items') {
                await fillOptions(fields.item_type, 'type');
            } else if (section === 'packages') {
                await Promise.all([fillOptions(fields.package_type, 'type'), fillOptions(fields.lot, 'lot'), fillOptions(fields.keystage, 'keystage')]);
            } else if (section === 'keystage') {
                await fillOptions(fields.lot, 'lot');
            }
            optionsLoaded = true;
        };
        const addButton = (label, target, disabled = false, active = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            button.className = active
                ? 'h-8 min-w-8 rounded-lg bg-blue-600 px-2 text-xs font-bold text-white'
                : 'h-8 min-w-8 rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 disabled:opacity-40';
            if (active) button.setAttribute('aria-current', 'page');
            button.onclick = () => { page = target; table.load(); };
            buttons.append(button);
        };
        const showCounts = (data) => {
            const label = section === 'keystage' ? 'keystages' : section;
            const range = `Showing ${data.from || 0}–${data.to || 0} of ${data.total} ${label}`;
            const ids = section === 'schools' ? ['schoolResultCount', 'schoolTableFooterCount'] : section === 'lots' ? ['lotResultCount'] : section === 'keystage' ? ['keystageResultCount'] : [];
            ids.forEach((id) => { const element = document.getElementById(id); if (element) element.textContent = range; });
            ['ShowingStart', 'ShowingEnd', 'Total'].forEach((suffix, index) => {
                const element = document.getElementById(section + suffix);
                if (element) element.textContent = [data.from || 0, data.to || 0, data.total][index];
            });
            document.querySelectorAll(`[data-project-count="${section}"]`).forEach((element) => { element.textContent = data.total_count.toLocaleString(); });
            if (section === 'schools') {
                const badge = document.getElementById('schoolActiveFilterBadge');
                const count = Object.values(fields).filter((field) => field?.value).length;
                if (badge) { badge.textContent = `${count} active filters`; badge.classList.toggle('hidden', count === 0); }
            }
        };
        const table = {
            loaded: false,
            loading: false,
            refresh() { request?.abort(); optionsLoaded = false; optionVersion++; this.loaded = false; if (!container.classList.contains('hidden')) this.load(); },
            async load() {
                clearTimeout(timer);
                request?.abort();
                request = new AbortController();
                const currentRequest = request;
                this.loading = true;
                body.replaceChildren();
                buttons.replaceChildren();
                status('Loading…');
                container.setAttribute('aria-busy', 'true');
                try {
                    if (!optionsLoaded) await loadOptions();
                    const params = parameters();
                    params.set('page', page);
                    params.set('per_page', perPage.value);
                    const data = await get(`${container.dataset.endpoint}?${params}`, currentRequest.signal);
                    if (request !== currentRequest) return;
                    if (page > data.last_page) { page = data.last_page; return this.load(); }
                    body.innerHTML = data.html;
                    showCounts(data);
                    addButton('Previous', page - 1, page === 1);
                    const pages = new Set([1, data.last_page, page - 1, page, page + 1]);
                    [...pages].filter((value) => value > 0 && value <= data.last_page).sort((a, b) => a - b).forEach((value) => addButton(value, value, false, value === page));
                    addButton('Next', page + 1, page >= data.last_page);
                    this.loaded = true;
                    container.dispatchEvent(new CustomEvent('project-details:loaded', { bubbles: true, detail: { section } }));
                } catch (error) {
                    if (error.name !== 'AbortError' && request === currentRequest) {
                        this.loaded = false;
                        body.replaceChildren();
                        status(error.message, true);
                    }
                } finally {
                    if (request === currentRequest) { this.loading = false; container.removeAttribute('aria-busy'); }
                }
            },
        };
        tables.set(section, table);
        const change = async (name) => {
            clearTimeout(timer);
            page = 1;
            try {
                const field = fields[name];
                if (field?.value === '__more__') {
                    const optionPage = field.selectedOptions[0].dataset.page;
                    field.value = field.dataset.selectedValue || '';
                    const level = name === 'lot' ? 'lot' : name === 'keystage' ? 'keystage' : 'type';
                    await fillOptions(field, level, false, optionPage);
                    return;
                }
                if (field?.tagName === 'SELECT') field.dataset.selectedValue = field.value;
                optionVersion++;
                const currentVersion = optionVersion;
                request?.abort();
                if (section === 'schools' && name === 'region') {
                    fields.division.value = ''; fields.municipality.value = '';
                    fields.division.dataset.selectedValue = ''; fields.municipality.dataset.selectedValue = '';
                    request = new AbortController();
                    await Promise.all([fillOptions(fields.division, 'division'), fillOptions(fields.municipality, 'municipality')]);
                } else if (section === 'schools' && name === 'division') {
                    fields.municipality.value = '';
                    fields.municipality.dataset.selectedValue = '';
                    request = new AbortController();
                    await fillOptions(fields.municipality, 'municipality');
                } else if (section === 'packages' && name === 'lot') {
                    fields.keystage.value = '';
                    fields.keystage.dataset.selectedValue = '';
                    request = new AbortController();
                    await fillOptions(fields.keystage, 'keystage');
                }
                if (currentVersion === optionVersion) await table.load();
            } catch (error) {
                if (error.name !== 'AbortError') { body.replaceChildren(); status(error.message, true); }
            }
        };
        Object.entries(fields).forEach(([name, field]) => {
            field?.addEventListener(name === 'search' ? 'input' : 'change', () => {
                clearTimeout(timer);
                if (name === 'search') { request?.abort(); timer = setTimeout(() => change(name), 300); }
                else change(name);
            });
        });
        perPage.addEventListener('change', () => change('per_page'));
        if (section === 'schools') {
            document.getElementById('applySchoolFilters')?.addEventListener('click', () => change('apply'));
            document.getElementById('clearSchoolFilters')?.addEventListener('click', () => {
                Object.values(fields).forEach((field) => { if (field) { field.value = ''; field.dataset.selectedValue = ''; } });
                optionsLoaded = false;
                change('clear');
            });
        }
        if (pendingTabs.has(section) || !container.classList.contains('hidden')) table.load();
    });
    pendingTabs.clear();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeProjectDetails);
else initializeProjectDetails();
