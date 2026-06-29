document.addEventListener('DOMContentLoaded', () => {

    const project = document.getElementById('project');
    const lot = document.getElementById('lot');
    const region = document.getElementById('region');
    const division = document.getElementById('division');
    const municipality = document.getElementById('municipality');
    const year = document.getElementById('year');

    if (!project) return;

    function fillSelect(select, data, valueField, textField, placeholder) {

        select.innerHTML = `<option value="">${placeholder}</option>`;

        data.forEach(row => {
            select.innerHTML += `
                <option value="${row[valueField]}">
                    ${row[textField]}
                </option>`;
        });
    }

    // -------------------------
    // PROJECT -> LOTS + REGIONS
    // -------------------------
    project.addEventListener('change', async () => {

        fillSelect(lot, [], '', '', 'Loading...');
        fillSelect(region, [], '', '', 'Loading...');
        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');

        if (!project.value) return;

        let response = await fetch(`/filter/lots?project=${project.value}`);
        let lots = await response.json();

        fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');

        response = await fetch(`/filter/regions?project=${project.value}`);
        let regions = await response.json();

        fillSelect(region, regions, 'region', 'region', 'All Regions');

    });

    // -------------------------
    // LOT -> REGIONS
    // -------------------------
    lot.addEventListener('change', async () => {

        let response = await fetch(
            `/filter/regions?project=${project.value}&lot=${lot.value}`
        );

        let regions = await response.json();

        fillSelect(region, regions, 'region', 'region', 'All Regions');

        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');

    });

    // -------------------------
    // REGION -> DIVISIONS
    // -------------------------
    region.addEventListener('change', async () => {

        let response = await fetch(
            `/filter/divisions?project=${project.value}&lot=${lot.value}&region=${region.value}`
        );

        let divisions = await response.json();

        fillSelect(
            division,
            divisions,
            'division',
            'division',
            'All Divisions'
        );

        fillSelect(municipality, [], '', '', 'All Municipalities');

    });

    // -------------------------
    // DIVISION -> MUNICIPALITIES
    // -------------------------
    division.addEventListener('change', async () => {

        let response = await fetch(
            `/filter/municipalities?project=${project.value}&lot=${lot.value}&region=${region.value}&division=${division.value}`
        );

        let municipalities = await response.json();

        fillSelect(
            municipality,
            municipalities,
            'municipality',
            'municipality',
            'All Municipalities'
        );

    });

    // -------------------------
    // YEAR RESET
    // -------------------------
    year?.addEventListener('change', () => {

        project.value = '';

        fillSelect(lot, [], '', '', 'All Lots');
        fillSelect(region, [], '', '', 'All Regions');
        fillSelect(division, [], '', '', 'All Divisions');
        fillSelect(municipality, [], '', '', 'All Municipalities');

    });

});

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