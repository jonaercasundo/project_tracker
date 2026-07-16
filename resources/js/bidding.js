/**
 * bidding-form.js
 * Handles dynamic lot and item row management.
 * Include this near </body> or in your @push('scripts') section.
 */

let lotCount = document.querySelectorAll('.bf-lot').length;
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
document.addEventListener('click', function(e){

    const btn = e.target.closest('.bf-btn-add-keystage');

    if(!btn) return;

    addKeystage(btn);

});
/* ── Add Item Row ─────────────────────────────────────────────── */
window.addItem = function(btn, lotIndex) {
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

    lot.querySelectorAll('.bf-item-row').forEach(row => {

        const amountInput = row.querySelector('.item-amount');

        let amount = parseFloat((amountInput?.value || '0').replace(/,/g, ''));

        if (isNaN(amount)) amount = 0;
        grandTotal += amount;
    });

    lot.querySelector('.lot-grand-total').textContent =
        grandTotal.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    // Update overall ABC
    computeApprovedBudgetABC();

}

document.addEventListener('input', e => {

    if (e.target.classList.contains('item-amount')) {
        computeLotTotal(e.target.closest('.bf-lot'));
    }

});
function computeApprovedBudgetABC() {

    let totalABC = 0;

    document.querySelectorAll('.lot-grand-total').forEach(el => {

        const value = parseFloat(
            (el.textContent || '0').replace(/,/g, '')
        );

        if (!isNaN(value)) {
            totalABC += value;
        }
    });

    const abcInput = document.getElementById('approved_budget_contract_abc');

    if (abcInput) {
        abcInput.value = totalABC.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

}
document.querySelectorAll('.bf-lot').forEach(computeLotTotal);
computeApprovedBudgetABC();


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
    select.innerHTML = `<option value="">${placeholder}</option>`;
    select.disabled = true;
}
 
function setLoading(select) {
    select.innerHTML = '<option value="">Loading…</option>';
    select.disabled = true;
}
 
function populate(select, items, placeholder) {
    select.innerHTML = `<option value="">${placeholder}</option>`;
    items.forEach(({ name, code }) => select.add(new Option(name, code)));
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
window.addKeystage = function(button) {

    let lotIndex = button.dataset.lot;
    let addressIndex = button.dataset.address;

    let container = document.querySelector(
        `#keystages-${lotIndex}-${addressIndex}`
    );


    if (!container) {
        console.error("Keystage container not found");
        return;
    }


    let stageIndex = container.children.length;


    let html = `
        <div class="bf-keystage">

            <input type="text"
                class="bf-input"
                name="lots[${lotIndex}][addresses][${addressIndex}][keystages][${stageIndex}][name]"
                placeholder="Key Stage Name">


            <div class="bf-items"
                id="items-${lotIndex}-${addressIndex}-${stageIndex}">
            </div>


            <button type="button"
                class="bf-btn-add-item">
                Add Item
            </button>

        </div>
    `;


    container.insertAdjacentHTML(
        'beforeend',
        html
    );

};
document.addEventListener('change', function (e) {

    if (!e.target.matches('.item-select')) return;

    const select = e.target;
    const option = select.options[select.selectedIndex];

    if (!option) return;

    const row = select.closest('.bf-item-row');
    if (!row) return;

    const unitInput = row.querySelector('.unit-input');
    const costInput = row.querySelector('.unit-cost');

    if (unitInput) {
        unitInput.value = option.dataset.unit || '';
    }

    if (costInput) {
        costInput.value = option.dataset.price || '';
        costInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

});