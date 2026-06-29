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
        const year         = container.querySelector('#year');
        const selectAll    = container.querySelector('#select-all-drs');

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
            fillSelect(lot, [], '', '', 'Loading...');
            fillSelect(region, [], '', '', 'All Regions');
            fillSelect(division, [], '', '', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');

            if (!project.value) return;

            const lots = await fetchData(`/filter/lots?project=${encodeURIComponent(project.value)}`);
            fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');
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

        // =========================
        // YEAR RESET
        // =========================
        year?.addEventListener('change', () => {
            project.value = '';
            fillSelect(lot, [], '', '', 'All Lots');
            fillSelect(region, [], '', '', 'All Regions');
            fillSelect(division, [], '', '', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');
        });

        // =========================
        // SELECT ALL DRs
        // =========================
        selectAll?.addEventListener('change', function () {
            container.querySelectorAll('.dr-checkbox')
                .forEach(cb => cb.checked = this.checked);
        });

        container.querySelectorAll('.dr-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const all     = container.querySelectorAll('.dr-checkbox').length;
                const checked = container.querySelectorAll('.dr-checkbox:checked').length;
                if (selectAll) {
                    selectAll.checked       = all === checked;
                    selectAll.indeterminate = checked > 0 && checked < all;
                }
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

    if (!projectVal || !lotVal) return;

    // Restore regions
    const regions = await fetchData(
        `/filter/regions?project=${encodeURIComponent(projectVal)}&lot=${encodeURIComponent(lotVal)}`
    );
    fillSelect(region, regions, 'region', 'region', 'All Regions');
    region.value = regionVal;

    if (!regionVal) return;

    // Restore divisions
    const divisions = await fetchData(
        `/filter/divisions`
        + `?project=${encodeURIComponent(projectVal)}`
        + `&lot=${encodeURIComponent(lotVal)}`
        + `&region=${encodeURIComponent(regionVal)}`
    );
    fillSelect(division, divisions, 'division', 'division', 'All Divisions');
    division.value = divisionVal;

    if (!divisionVal) return;

    // Restore municipalities
    const municipalities = await fetchData(
        `/filter/municipalities`
        + `?project=${encodeURIComponent(projectVal)}`
        + `&lot=${encodeURIComponent(lotVal)}`
        + `&region=${encodeURIComponent(regionVal)}`
        + `&division=${encodeURIComponent(divisionVal)}`
    );
    fillSelect(municipality, municipalities, 'municipality', 'municipality', 'All Municipalities');
    municipality.value = municipalityVal;
}

        restoreFilters(); // ← called here, inside DOMContentLoaded, so DOM vars are in scope

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