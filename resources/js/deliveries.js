document.addEventListener('DOMContentLoaded', () => {

    const project = document.getElementById('project');
    const lot = document.getElementById('lot');
    const region = document.getElementById('region');
    const division = document.getElementById('division');
    const municipality = document.getElementById('municipality');

    function fillSelect(select, data, valueField, textField, placeholder) {

        select.innerHTML = `<option value="">${placeholder}</option>`;

        data.forEach(row => {
            select.innerHTML += `
                <option value="${row[valueField]}">
                    ${row[textField]}
                </option>`;
        });

    }

    // -----------------------
    // Project -> Lots + Regions
    // -----------------------
    project.addEventListener('change', async () => {

        fillSelect(lot, [], '', '', 'Loading...');
        fillSelect(region, [], '', '', 'Loading...');
        fillSelect(division, [], '', '', 'Division');
        fillSelect(municipality, [], '', '', 'Municipality');

        let lots = await fetch(`/api/filter/lots?project=${project.value}`);
        lots = await lots.json();

        fillSelect(lot, lots, 'lot_id', 'lot_name', 'All Lots');

        let regions = await fetch(`/api/filter/regions?project=${project.value}`);
        regions = await regions.json();

        fillSelect(region, regions, 'region', 'region', 'All Regions');

    });

    // -----------------------
    // Lot -> Regions
    // -----------------------
    lot.addEventListener('change', async () => {

        let regions = await fetch(
            `/api/filter/regions?project=${project.value}&lot=${lot.value}`
        );

        regions = await regions.json();

        fillSelect(region, regions, 'region', 'region', 'All Regions');

    });

    // -----------------------
    // Region -> Divisions
    // -----------------------
    region.addEventListener('change', async () => {

        let divisions = await fetch(
            `/api/filter/divisions?project=${project.value}&lot=${lot.value}&region=${region.value}`
        );

        divisions = await divisions.json();

        fillSelect(
            division,
            divisions,
            'division',
            'division',
            'All Divisions'
        );

    });

    // -----------------------
    // Division -> Municipality
    // -----------------------
    division.addEventListener('change', async () => {

        let municipalities = await fetch(
            `/api/filter/municipalities?project=${project.value}&lot=${lot.value}&region=${region.value}&division=${division.value}`
        );

        municipalities = await municipalities.json();

        fillSelect(
            municipality,
            municipalities,
            'municipality',
            'municipality',
            'All Municipalities'
        );

    });

});