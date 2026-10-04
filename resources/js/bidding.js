const MAX_DECIMAL = 999999999999999n;

function decimalUnits(value) {
    const text = String(value ?? '').trim();
    if (!/^\d{1,13}(?:\.\d{1,2})?$/.test(text)) return null;
    const [whole, fraction = ''] = text.split('.');
    const units = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
    return units <= MAX_DECIMAL ? units : null;
}

function decimalString(units) {
    return `${units / 100n}.${String(units % 100n).padStart(2, '0')}`;
}

function displayMoney(units) {
    return `${(units / 100n).toLocaleString('en-PH')}.${String(units % 100n).padStart(2, '0')}`;
}

function initializeBidding(form) {
    if (form.dataset.biddingInitialized) return;
    form.dataset.biddingInitialized = 'true';
    const requestStates = new WeakMap();
    const catalogStates = new WeakMap();
    let regionsPromise;
    const feedback = form.querySelector('[data-bidding-feedback]');
    const message = (text = '') => { if (feedback) feedback.textContent = text; };
    const allLots = () => [...form.querySelectorAll('[data-bidding-lot]')];

    async function getJson(url, signal) {
        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Your session has expired. Sign in and reload this page.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'The lookup could not be loaded. Try again.');
        return data;
    }

    function initializeCounter(container) {
        if (!container.dataset.nextIndex) {
            const used = [...container.children].map(row => Number(row.dataset.entryIndex)).filter(Number.isInteger);
            container.dataset.nextIndex = String(used.length ? Math.max(...used) + 1 : 0);
        }
    }

    function nextIndex(container) {
        initializeCounter(container);
        const index = Number(container.dataset.nextIndex);
        container.dataset.nextIndex = String(index + 1);
        return index;
    }

    function appendEntry(type, container) {
        const maximum = { lot: 20, address: 20, stage: 20, item: 100 }[type];
        if (container.children.length >= maximum) {
            message(`This section supports at most ${maximum} ${type === 'stage' ? 'key stages' : `${type}s`}.`);
            return null;
        }
        const template = form.querySelector(`[data-bidding-template="${type}"]`);
        if (!template) return null;
        const index = nextIndex(container);
        const prefix = `${container.dataset.namePrefix}[${index}]`;
        const dot = prefix.replaceAll('[', '.').replaceAll(']', '');
        const uid = prefix.replace(/[^a-zA-Z0-9_-]/g, '-');
        const html = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__PREFIX__', prefix).replaceAll('__DOT__', dot).replaceAll('__UID__', uid);
        const holder = document.createElement('template');
        holder.innerHTML = html.trim();
        const entry = holder.content.firstElementChild;
        container.append(entry);
        entry.querySelectorAll('[data-collection]').forEach(initializeCounter);
        if (type === 'lot') {
            const used = new Set(allLots().filter(lot => lot !== entry).map(lot => lot.querySelector('[data-lot-number]')?.value));
            let number = index + 1;
            while (used.has(`Lot ${number}`)) number++;
            entry.querySelector('[data-lot-number]').value = `Lot ${number}`;
            restoreLocations(entry);
        }
        entry.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
        message();
        recalculate();
        return entry;
    }

    function recalculateRow(row) {
        const quantity = row.querySelector('[data-item-quantity]');
        const cost = row.querySelector('[data-item-cost]');
        const total = row.querySelector('[data-item-total]');
        const priceMessage = row.querySelector('[data-item-price-message]');
        const quantityUnits = decimalUnits(quantity.value);
        const costUnits = decimalUnits(cost.value);
        const legacy = row.dataset.legacy === 'true';
        const quantityUnchanged = quantity.value === '' && row.dataset.originalQuantity === '' || quantityUnits !== null && quantityUnits === decimalUnits(row.dataset.originalQuantity);
        const costUnchanged = cost.value === '' && row.dataset.originalCost === '' || costUnits !== null && costUnits === decimalUnits(row.dataset.originalCost);
        quantity.setCustomValidity('');
        cost.setCustomValidity('');
        quantity.required = !legacy;
        cost.required = !legacy;
        const missingLegacyPrice = legacy && (row.dataset.originalQuantity === '' || row.dataset.originalCost === '');
        if (missingLegacyPrice && quantityUnchanged && costUnchanged) {
            const original = decimalUnits(row.dataset.originalTotal);
            total.value = original === null ? '' : decimalString(original);
            priceMessage.textContent = row.dataset.originalQuantity === '' && row.dataset.originalCost === '' ? 'Quantity and unit cost not provided; existing amount retained.' : row.dataset.originalQuantity === '' ? 'Quantity not provided; existing amount retained.' : 'Unit cost not provided; existing amount retained.';
            return original;
        }
        if (legacy) {
            quantity.required = true;
            cost.required = true;
            if (quantityUnits === null || costUnits === null) {
                if (quantityUnits === null) quantity.setCustomValidity('Enter a quantity before changing this existing pricing.');
                if (costUnits === null) cost.setCustomValidity('Enter a unit cost before changing this existing pricing.');
                priceMessage.textContent = 'Enter both quantity and unit cost to change the existing pricing.';
                total.value = '';
                return null;
            }
        }
        priceMessage.textContent = '';
        if (quantityUnits === null || costUnits === null) {
            total.value = '';
            return null;
        }
        const amount = (quantityUnits * costUnits + 50n) / 100n;
        if (amount > MAX_DECIMAL) {
            cost.setCustomValidity('The calculated amount exceeds the supported limit. Reduce the quantity or unit cost.');
            priceMessage.textContent = 'Calculated amount exceeds the supported limit.';
            total.value = '';
            return null;
        }
        total.value = decimalString(amount);
        return amount;
    }

    function recalculate() {
        let documentTotal = 0n;
        for (const lot of allLots()) {
            let lotTotal = 0n;
            for (const row of lot.querySelectorAll('[data-bidding-item]')) lotTotal += recalculateRow(row) ?? 0n;
            lot.querySelector('[data-bidding-lot-total]').textContent = displayMoney(lotTotal);
            documentTotal += lotTotal;
        }
        form.querySelector('[data-bidding-calculated-total]').textContent = displayMoney(documentTotal);
        const empty = form.querySelector('[data-bidding-empty-lots]');
        if (empty) empty.hidden = allLots().length > 0;
    }

    const locationSelect = (lot, level) => lot.querySelector(`[data-location="${level}"]`);
    const locationLabel = level => level === 'city' ? 'city / municipality' : level;
    function syncLocationValue(select) {
        const mirror = select.closest('[data-bidding-lot]').querySelector(`[data-location-value="${select.dataset.location}"]`);
        if (!mirror) return;
        mirror.value = select.value;
        mirror.disabled = !select.disabled;
    }
    function resetSelect(select, level, disabled = true) {
        select.replaceChildren(new Option(`Select ${locationLabel(level)}`, ''));
        select.disabled = disabled;
        select.dataset.selected = '';
        syncLocationValue(select);
    }
    function populate(select, data, level, selected = '') {
        select.replaceChildren(new Option(`Select ${locationLabel(level)}`, ''));
        for (const entry of data) select.add(new Option(entry.name, entry.code));
        if (selected && !data.some(entry => String(entry.code) === selected)) select.add(new Option(`${selected} (previous selection)`, selected));
        select.value = selected;
        select.disabled = false;
        select.dataset.selected = selected;
        syncLocationValue(select);
    }
    function locationMessage(lot, text = '') {
        const box = lot.querySelector('[data-location-message]');
        box.querySelector('span').textContent = text;
        box.querySelector('button').hidden = !text;
    }
    function cancelLocations(lot) {
        requestStates.get(lot)?.abort();
        const controller = new AbortController();
        requestStates.set(lot, controller);
        locationMessage(lot);
        return controller;
    }
    function regions() {
        regionsPromise ??= getJson(form.dataset.regionsUrl).catch(error => { regionsPromise = undefined; throw error; });
        return regionsPromise;
    }
    async function loadChild(lot, level, parameter, value, controller, selected = '') {
        const select = locationSelect(lot, level);
        select.replaceChildren(new Option('Loading...', ''));
        if (selected) select.add(new Option(`${selected} (saved selection)`, selected, true, true));
        select.disabled = !selected;
        syncLocationValue(select);
        const endpoint = { province: form.dataset.provincesUrl, city: form.dataset.citiesUrl, barangay: form.dataset.barangaysUrl }[level];
        const url = new URL(endpoint, window.location.href);
        url.searchParams.set(parameter, value);
        const data = await getJson(url, controller.signal);
        if (controller.signal.aborted || requestStates.get(lot) !== controller) return null;
        populate(select, data, level, selected);
        return data;
    }
    function showLocationError(lot, controller, error) {
        if (error.name !== 'AbortError' && requestStates.get(lot) === controller) locationMessage(lot, error.message);
    }
    async function restoreLocations(lot) {
        if (!locationSelect(lot, 'region')) return;
        const saved = Object.fromEntries(['region', 'province', 'city', 'barangay'].map(level => [level, locationSelect(lot, level).dataset.selected || locationSelect(lot, level).value]));
        const controller = cancelLocations(lot);
        try {
            const data = await regions();
            if (controller.signal.aborted) return;
            populate(locationSelect(lot, 'region'), data, 'region', saved.region);
            if (!saved.region) return;
            const provinces = await loadChild(lot, 'province', 'region', saved.region, controller, saved.province);
            if (provinces === null) return;
            if (!provinces.length) {
                resetSelect(locationSelect(lot, 'province'), 'province');
                await loadChild(lot, 'city', 'region', saved.region, controller, saved.city);
            } else if (saved.province) {
                await loadChild(lot, 'city', 'province', saved.province, controller, saved.city);
            }
            if (saved.city) await loadChild(lot, 'barangay', 'city', saved.city, controller, saved.barangay);
        } catch (error) { showLocationError(lot, controller, error); }
    }
    async function changeLocation(select) {
        const lot = select.closest('[data-bidding-lot]');
        const level = select.dataset.location;
        const levels = ['region', 'province', 'city', 'barangay'];
        const controller = cancelLocations(lot);
        select.dataset.selected = select.value;
        syncLocationValue(select);
        for (const child of levels.slice(levels.indexOf(level) + 1)) resetSelect(locationSelect(lot, child), child);
        if (!select.value || level === 'barangay') return;
        try {
            if (level === 'region') {
                const data = await loadChild(lot, 'province', 'region', select.value, controller);
                if (data !== null && !data.length) {
                    resetSelect(locationSelect(lot, 'province'), 'province');
                    await loadChild(lot, 'city', 'region', select.value, controller);
                }
            } else if (level === 'province') await loadChild(lot, 'city', 'province', select.value, controller);
            else if (level === 'city') await loadChild(lot, 'barangay', 'city', select.value, controller);
        } catch (error) { showLocationError(lot, controller, error); }
    }

    async function searchCatalog(input) {
        const row = input.closest('[data-bidding-item]');
        const previous = catalogStates.get(row);
        previous?.controller?.abort();
        const controller = new AbortController();
        catalogStates.set(row, { controller });
        const status = row.querySelector('[data-catalog-message]');
        status.textContent = 'Searching catalog...';
        try {
            const url = new URL(form.dataset.catalogUrl, window.location.href);
            url.searchParams.set('q', input.value.trim());
            const data = await getJson(url, controller.signal);
            if (controller.signal.aborted || catalogStates.get(row)?.controller !== controller) return;
            const select = row.querySelector('[data-catalog-item]');
            const current = select.selectedOptions[0]?.value ? select.selectedOptions[0].cloneNode(true) : null;
            const placeholder = select.options[0]?.textContent || (row.dataset.legacy === 'true' ? 'Keep saved item' : 'Select catalog item');
            select.replaceChildren(new Option(placeholder, ''));
            for (const item of data.items) {
                const option = new Option(item.item_name, item.id);
                option.dataset.description = item.description || item.item_name;
                option.dataset.unit = item.unit || '';
                option.dataset.price = item.price ?? '';
                select.add(option);
            }
            if (current) {
                if (![...select.options].some(option => option.value === current.value)) select.add(current);
                select.value = current.value;
            }
            status.textContent = data.has_more ? 'More results available. Refine your search.' : `${data.items.length} catalog results.`;
        } catch (error) { if (error.name !== 'AbortError') status.textContent = error.message; }
    }

    form.addEventListener('click', event => {
        const button = event.target.closest('[data-bidding-action]');
        if (!button || !form.contains(button)) return;
        const action = button.dataset.biddingAction;
        if (action === 'add-lot') appendEntry('lot', form.querySelector('[data-bidding-lots]'));
        else if (action === 'add-address') appendEntry('address', button.closest('[data-bidding-lot]').querySelector('[data-bidding-addresses]'));
        else if (action === 'add-stage') appendEntry('stage', button.closest('[data-bidding-address]').querySelector('[data-bidding-stages]'));
        else if (action === 'add-item') appendEntry('item', button.closest('[data-bidding-stage]').querySelector('[data-bidding-items]'));
        else if (action === 'retry-locations') restoreLocations(button.closest('[data-bidding-lot]'));
        else if (action.startsWith('remove-')) {
            const type = action.slice(7);
            const entry = button.closest(`[data-bidding-${type}]`);
            const saved = [...entry.querySelectorAll('input[type="hidden"]')].some(input => input.name.endsWith('[id]'));
            if (saved && !window.confirm(`Remove this ${type === 'stage' ? 'key stage' : type} and its contents when you save?`)) return;
            if (type === 'lot') requestStates.get(entry)?.abort();
            if (type === 'item') {
                clearTimeout(catalogStates.get(entry)?.timer);
                catalogStates.get(entry)?.controller?.abort();
            }
            for (const row of entry.querySelectorAll('[data-bidding-item]')) {
                clearTimeout(catalogStates.get(row)?.timer);
                catalogStates.get(row)?.controller?.abort();
            }
            entry.remove();
            recalculate();
            message();
        }
    });
    form.addEventListener('change', event => {
        const target = event.target;
        if (target.matches('[data-location]')) changeLocation(target);
        if (target.matches('[data-catalog-item]')) {
            const option = target.selectedOptions[0];
            if (!option?.value) return;
            const row = target.closest('[data-bidding-item]');
            row.querySelector('[data-item-description]').value = option.dataset.description || option.textContent;
            row.querySelector('[data-item-unit]').value = option.dataset.unit || '';
            row.querySelector('[data-item-cost]').value = option.dataset.price || '';
            recalculate();
        }
    });
    form.addEventListener('input', event => {
        if (event.target.matches('[data-item-quantity], [data-item-cost]')) recalculate();
        if (event.target.matches('[data-catalog-search]')) {
            const row = event.target.closest('[data-bidding-item]');
            const previous = catalogStates.get(row);
            clearTimeout(previous?.timer);
            previous?.controller?.abort();
            catalogStates.set(row, { timer: setTimeout(() => searchCatalog(event.target), 250) });
        }
    });
    form.addEventListener('submit', () => {
        form.querySelector('[data-bidding-save]').disabled = true;
        form.querySelector('[data-bidding-save-status]').textContent = 'Saving bidding document...';
    });
    window.addEventListener('pageshow', () => {
        const save = form.querySelector('[data-bidding-save]');
        if (save) save.disabled = false;
        const status = form.querySelector('[data-bidding-save-status]');
        if (status) status.textContent = '';
    });
    form.querySelectorAll('[data-location]').forEach(syncLocationValue);
    form.querySelectorAll('[data-collection]').forEach(initializeCounter);
    recalculate();
    for (const lot of allLots()) restoreLocations(lot);
    form.querySelector('[data-bidding-errors]')?.focus();
}

function initializeBiddingForms() {
    document.querySelectorAll('[data-bidding-form]').forEach(initializeBidding);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeBiddingForms);
else initializeBiddingForms();
