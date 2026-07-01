/**
 * bidding-form.js
 * Handles dynamic lot and item row management.
 * Include this near </body> or in your @push('scripts') section.
 */

let lotCount = document.querySelectorAll('.bf-lot').length;

document.addEventListener('change', function (e) {

    console.log('Change detected');

    if (!e.target.matches('.item-select')) return;

    console.log('Item Select');

    const option = e.target.selectedOptions[0];

    console.log(option.dataset);

});
/* ── Add Lot ──────────────────────────────────────────────────── */
document.getElementById('addLot')?.addEventListener('click', () => {
    const template = document.getElementById('lot-template');
    if (!template) return;

    const index = lotCount++;
    const html  = template.innerHTML
        .replaceAll('__INDEX__', index)
        .replaceAll(
            /Lot (\d+|__INDEX__)/g,
            `Lot ${document.querySelectorAll('.bf-lot').length + 1}`
        );

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const lot = wrapper.firstElementChild;

    document.getElementById('lotsContainer').appendChild(lot);
    loadRegions(lot);
    // wire up the "add item" button inside the freshly cloned lot

});
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.bf-btn-add-item');

    if (!btn) return;

    const lot = btn.closest('.bf-lot');

    addItem(btn, lot.dataset.lotIndex);

});
/* ── Add Item Row ─────────────────────────────────────────────── */
function addItem(btn, lotIndex) {
    const template = document.getElementById('item-template');
    if (!template) return;

    const table    = btn.closest('.bf-lot-body').querySelector('.bf-items');
    const rowCount = table.querySelectorAll('.bf-item-row').length;

    const html = template.innerHTML
        .replaceAll('__LOTINDEX__',  lotIndex)
        .replaceAll('__ITEMINDEX__', rowCount);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;

    // wire up delete on new row
    row.querySelector('.bf-item-del')?.addEventListener('click', function () {
        this.closest('.bf-item-row').remove();
    });

    table.appendChild(row);
    computeLotTotal(btn.closest('.bf-lot'));
}
document.querySelectorAll('.auto-expand').forEach(textarea => {

    function resize(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    resize(textarea);

    textarea.addEventListener('input', function () {
        resize(this);
    });

});
document.querySelectorAll('.currency').forEach(input => {

    function format(value) {
        value = value.replace(/,/g, '');

        // Keep only digits and one decimal point
        value = value.replace(/[^\d.]/g, '');

        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }

        let [integer, decimal] = value.split('.');

        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        if (decimal !== undefined) {
            decimal = decimal.substring(0, 2);
            return `${integer}.${decimal}`;
        }

        return integer;
    }

    // Initial value
    if (input.value) {
        input.value = format(input.value);
    }

    input.addEventListener('input', function () {
        const cursor = this.selectionStart;
        const beforeLength = this.value.length;

        this.value = format(this.value);

        const afterLength = this.value.length;
        const diff = afterLength - beforeLength;

        this.setSelectionRange(cursor + diff, cursor + diff);
    });

    input.form?.addEventListener('submit', () => {
        input.value = input.value.replace(/,/g, '');
    });

});
function computeLotTotal(lot){

    let grandTotal = 0;

    lot.querySelectorAll('.bf-item-row').forEach(row=>{

        const qty = parseFloat(row.querySelector('.qty')?.value || 0);
        const cost = parseFloat(row.querySelector('.unit-cost')?.value || 0);

        const amount = qty * cost;

        row.querySelector('.item-amount').value =
            amount.toLocaleString('en-PH',{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            });

        grandTotal += amount;
    });

    lot.querySelector('.lot-grand-total').textContent =
        grandTotal.toLocaleString('en-PH',{
            minimumFractionDigits:2,
            maximumFractionDigits:2
        });

}

document.addEventListener('input',e=>{

    if(e.target.classList.contains('qty') ||
       e.target.classList.contains('unit-cost')){

        computeLotTotal(e.target.closest('.bf-lot'));
    }

});

document.querySelectorAll('.bf-lot').forEach(computeLotTotal);


/* ==========================================================
 * PSGC Location Selector
 * ========================================================== */
 
document.addEventListener('DOMContentLoaded', () => {
    loadRegions();
});
 
/* ----------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------- */
 
function resetSelect(select, placeholder) {

    if (!select) return;

    select.innerHTML = `<option value="">${placeholder}</option>`;
    select.disabled = true;
}
 
function setLoading(select) {

    if (!select) return;

    select.innerHTML = '<option value="">Loading...</option>';
    select.disabled = true;
}
 
function populate(select, items, placeholder) {

    if (!select) return;

    select.innerHTML = `<option value="">${placeholder}</option>`;

    items.forEach(({ name, code }) => {
        select.add(new Option(name, code));
    });

    select.disabled = false;
}
 
async function apiFetch(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}
 
/* ----------------------------------------------------------
 * Load all Regions
 * ---------------------------------------------------------- */
 
async function loadRegions(scope = document) {
    try {
        const regions = await apiFetch('/api/regions');
 
        scope.querySelectorAll('.region').forEach(select => {
            if (select.dataset.loaded) return;
            select.dataset.loaded = true;
            populate(select, regions, 'Select region');
        });
 
    } catch (err) {
        console.error('Unable to load regions.', err);
    }
}
 
/* ----------------------------------------------------------
 * Event Delegation
 * ---------------------------------------------------------- */
 
document.addEventListener('change', async (e) => {
    const { target } = e;
 
    if (target.classList.contains('region'))   await loadProvinces(target);
    if (target.classList.contains('province')) await loadCities(target);
    if (target.classList.contains('city'))     await loadBarangays(target);
});
 
/* ----------------------------------------------------------
 * Provinces
 * ---------------------------------------------------------- */
 
async function loadProvinces(regionSelect) {
    const lot      = regionSelect.closest('.bf-lot');
    const province = lot.querySelector('.province');
    const city     = lot.querySelector('.city');
    const barangay = lot.querySelector('.barangay');
 
    resetSelect(city,     'Select city / municipality');
    resetSelect(barangay, 'Select barangay');
 
    if (!regionSelect.value) {
        resetSelect(province, 'Select province');
        return;
    }
 
    setLoading(province);
 
    try {
        const data = await apiFetch(`/api/provinces?region=${encodeURIComponent(regionSelect.value)}`);
        if (data.length === 0) {

            province.disabled = true;
            province.innerHTML = '<option value="">No Province</option>';

            await loadCitiesFromRegion(regionSelect);

            return;
        }

        populate(province, data, 'Select Province');
    } catch (err) {
        console.error('Unable to load provinces.', err);
        resetSelect(province, 'Select province');
    }
}
 
/* ----------------------------------------------------------
 * Cities
 * ---------------------------------------------------------- */
 async function loadCitiesFromRegion(regionSelect) {

    const lot = regionSelect.closest('.bf-lot');
    const city = lot.querySelector('.city');

    const data = await apiFetch(
        `/api/cities?region=${regionSelect.value}`
    );

    populate(city, data, 'Select City / Municipality');
}

async function loadCities(provinceSelect) {
    const lot      = provinceSelect.closest('.bf-lot');
    const city     = lot.querySelector('.city');
    const barangay = lot.querySelector('.barangay');
 
    resetSelect(barangay, 'Select barangay');
 
    if (!provinceSelect.value) {
        resetSelect(city, 'Select city / municipality');
        return;
    }
 
    setLoading(city);
 
    try {
        const data = await apiFetch(`/api/cities?province=${encodeURIComponent(provinceSelect.value)}`);
        populate(city, data, 'Select city / municipality');
    } catch (err) {
        console.error('Unable to load cities.', err);
        resetSelect(city, 'Select city / municipality');
    }
}
 
/* ----------------------------------------------------------
 * Barangays
 * ---------------------------------------------------------- */
 
async function loadBarangays(citySelect) {
    const lot      = citySelect.closest('.bf-lot');
    const barangay = lot.querySelector('.barangay');
 
    if (!citySelect.value) {
        resetSelect(barangay, 'Select barangay');
        return;
    }
 
    setLoading(barangay);
 
    try {
        const data = await apiFetch(`/api/barangays?city=${encodeURIComponent(citySelect.value)}`);
        populate(barangay, data, 'Select barangay');
    } catch (err) {
        console.error('Unable to load barangays.', err);
        resetSelect(barangay, 'Select barangay');
    }
}