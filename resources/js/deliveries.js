document.addEventListener('DOMContentLoaded', () => {

    const project = document.getElementById('project');
    const lot = document.getElementById('lot');
    const region = document.getElementById('region');
    const division = document.getElementById('division');
    const municipality = document.getElementById('municipality');
    const year = document.getElementById('year');

    const selectAll = document.getElementById('select-all-drs');

    if (!project) return;

    function fillSelect(select, data, valueField, textField, placeholder) {
        const options = [`<option value="">${placeholder}</option>`];

        for (const row of data) {
            options.push(
                `<option value="${row[valueField]}">${row[textField]}</option>`
            );
        }

        select.innerHTML = options.join('');
    }

    async function fetchData(url) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error('Network error');
            return await res.json();
        } catch (err) {
            console.error(err);
            return [];
        }
    }

    // PROJECT -> LOT + REGION
    project.addEventListener('change', async () => {

        fillSelect(lot, [], '', '', 'Loading...');
        fillSelect(region, [], '', '', 'Loading...');
        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');

        if (!project.value) return;

        const projectVal = encodeURIComponent(project.value);

        const lots = await fetchData(`/filter/lots?project=${projectVal}`);
        fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');

        const regions = await fetchData(`/filter/regions?project=${projectVal}`);
        fillSelect(region, regions, 'region', 'region', 'All Regions');
    });

    // LOT -> REGION
    lot.addEventListener('change', async () => {

        const projectVal = encodeURIComponent(project.value);
        const lotVal = encodeURIComponent(lot.value);

        const regions = await fetchData(
            `/filter/regions?project=${projectVal}&lot=${lotVal}`
        );

        fillSelect(region, regions, 'region', 'region', 'All Regions');

        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');
    });

    // REGION -> DIVISION
    region.addEventListener('change', async () => {

        const url =
            `/filter/divisions?project=${encodeURIComponent(project.value)}`
            + `&lot=${encodeURIComponent(lot.value)}`
            + `&region=${encodeURIComponent(region.value)}`;

        const divisions = await fetchData(url);

        fillSelect(division, divisions, 'division', 'division', 'All Divisions');

        fillSelect(municipality, [], '', '', 'All Municipalities');
    });

    // DIVISION -> MUNICIPALITY
    division.addEventListener('change', async () => {

        const url =
            `/filter/municipalities?project=${encodeURIComponent(project.value)}`
            + `&lot=${encodeURIComponent(lot.value)}`
            + `&region=${encodeURIComponent(region.value)}`
            + `&division=${encodeURIComponent(division.value)}`;

        const municipalities = await fetchData(url);

        fillSelect(municipality, municipalities, 'municipality', 'municipality', 'All Municipalities');
    });

    // YEAR RESET
    year?.addEventListener('change', () => {

        project.value = '';

        fillSelect(lot, [], '', '', 'All Lots');
        fillSelect(region, [], '', '', 'All Regions');
        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');
    });

    // SELECT ALL DRs
    if (selectAll) {
        selectAll.addEventListener('change', function () {

            const checkboxes = document.querySelectorAll('.dr-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        document.querySelectorAll('.dr-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {

                const checkboxes = document.querySelectorAll('.dr-checkbox');
                const checked = document.querySelectorAll('.dr-checkbox:checked');

                selectAll.checked = checkboxes.length === checked.length;
            });
        });
    }
});

// ---------------- QR ----------------
function generateQR() {

    const ids = Array.from(document.querySelectorAll('.dr-checkbox:checked'))
        .map(cb => cb.value);

    if (!ids.length) return alert('Select at least one DR');

    window.open(`/deliveries/pdf?ids=${ids.join(',')}`, '_blank');
}

// ---------------- Labels (POST form) ----------------
function generateLabels() {

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
}

function generateQR() {

    const ids = [];

    document.querySelectorAll('.dr-checkbox:checked').forEach(cb => {
        ids.push(cb.value);
    });

    if (!ids.length) {
        alert('Select at least one DR');
        return;
    }

    window.open(`/deliveries/pdf?ids=${ids.join(',')}`, '_blank');

}

document.addEventListener('DOMContentLoaded', () => {

    const selectAll = document.getElementById('select-all-drs');

    if (!selectAll) return;

    selectAll.addEventListener('change', function () {

        document.querySelectorAll('.dr-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });

    });

    document.querySelectorAll('.dr-checkbox').forEach(cb => {

        cb.addEventListener('change', () => {

            const total = document.querySelectorAll('.dr-checkbox').length;
            const checked = document.querySelectorAll('.dr-checkbox:checked').length;

            selectAll.checked = total === checked;

        });

    });

});

function generateLabels() {

    const ids = [];

    document.querySelectorAll('.dr-checkbox:checked').forEach(cb => {
        ids.push(cb.value);
    });

    if (!ids.length) {
        alert('Select at least one DR');
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]').content;

    const form = document.createElement('form');

    form.method = 'POST';
    form.action = '/deliveries/labels';
    form.target = '_blank';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token;

    form.appendChild(csrf);

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids';
    input.value = ids.join(',');

    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

}