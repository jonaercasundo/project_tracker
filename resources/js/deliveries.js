(() => {

    document.addEventListener('DOMContentLoaded', () => {

        const container = document.querySelector('#filter-container');
        if (!container) return;

        // Prevent double init (VERY IMPORTANT in Vite / Blade partials)
        if (window.__FILTER_INIT__) return;
        window.__FILTER_INIT__ = true;

        const project = container.querySelector('#project');
        const lot = container.querySelector('#lot');
        const region = container.querySelector('#region');
        const division = container.querySelector('#division');
        const municipality = container.querySelector('#municipality');
        const year = container.querySelector('#year');
        const selectAll = container.querySelector('#select-all-drs');

        function fillSelect(select, data, valueField, textField, placeholder) {
            if (!select) return;

            const options = [`<option value="">${placeholder}</option>`];

            for (const row of data || []) {
                options.push(
                    `<option value="${row[valueField]}">${row[textField]}</option>`
                );
            }

            select.innerHTML = options.join('');
        }

        async function fetchData(url) {
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });

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
            fillSelect(region, [], '', '', 'Loading...');
            fillSelect(division, [], '', '', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');

            if (!project.value) return;

            const projectVal = encodeURIComponent(project.value);

            const [lots, regions] = await Promise.all([
                fetchData(`/filter/lots?project=${projectVal}`),
                fetchData(`/filter/regions?project=${projectVal}`)
            ]);

            fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');
            fillSelect(region, regions, 'region', 'region', 'All Regions');
        });

        // =========================
        // LOT CHANGE
        // =========================
        lot?.addEventListener('change', async () => {

            const url = `/filter/regions?project=${encodeURIComponent(project.value)}&lot=${encodeURIComponent(lot.value)}`;

            const regions = await fetchData(url);

            fillSelect(region, regions, 'region', 'region', 'All Regions');

            fillSelect(division, [], '', '', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');
        });

        // =========================
        // REGION CHANGE
        // =========================
        region?.addEventListener('change', async () => {

            const url =
                `/filter/divisions?project=${encodeURIComponent(project.value)}`
                + `&lot=${encodeURIComponent(lot.value)}`
                + `&region=${encodeURIComponent(region.value)}`;

            const divisions = await fetchData(url);

            fillSelect(division, divisions, 'division', 'division', 'All Divisions');
            fillSelect(municipality, [], '', '', 'All Municipalities');
        });

        // =========================
        // DIVISION CHANGE
        // =========================
        division?.addEventListener('change', async () => {

            const url =
                `/filter/municipalities?project=${encodeURIComponent(project.value)}`
                + `&lot=${encodeURIComponent(lot.value)}`
                + `&region=${encodeURIComponent(region.value)}`
                + `&division=${encodeURIComponent(division.value)}`;

            const municipalities = await fetchData(url);

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
        // SELECT ALL DRs (FIXED - SINGLE SOURCE ONLY)
        // =========================
        selectAll?.addEventListener('change', function () {

            container.querySelectorAll('.dr-checkbox')
                .forEach(cb => cb.checked = this.checked);
        });

        container.querySelectorAll('.dr-checkbox').forEach(cb => {

            cb.addEventListener('change', () => {

                const all = container.querySelectorAll('.dr-checkbox').length;
                const checked = container.querySelectorAll('.dr-checkbox:checked').length;

                selectAll.checked = all === checked;
            });
        });

    });

})();


// ======================================================
// SAFE GLOBAL ACTIONS (NO CONFLICT VERSION)
// ======================================================

window.generateQR = function () {

    const ids = Array.from(document.querySelectorAll('.dr-checkbox:checked'))
        .map(cb => cb.value);

    if (!ids.length) return alert('Select at least one DR');

    window.open(`/deliveries/pdf?ids=${ids.join(',')}`, '_blank');
};


window.generateLabels = function () {

    const ids = Array.from(document.querySelectorAll('.dr-checkbox:checked'))
        .map(cb => cb.value);

    if (!ids.length) return alert('Select at least one DR');

    const token = document.querySelector('meta[name="csrf-token"]').content;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/deliveries/labels';
    form.target = '_blank';

    form.innerHTML = `
        <input type="hidden" name="_token" value="${token}">
        <input type="hidden" name="ids" value="${ids.join(',')}">
    `;

    document.body.appendChild(form);
    form.submit();
    form.remove();
};