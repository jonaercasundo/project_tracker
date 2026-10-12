export function parseFactoryCost(value) {
    const match = /^(\d{1,13})(?:\.(\d{1,2}))?$/.exec(String(value));
    if (!match) return null;

    return BigInt(match[1]) * 100n + BigInt((match[2] ?? '').padEnd(2, '0'));
}

export function summarizeFactoryCosts(entries) {
    const costs = entries.map((entry) => ({ ...entry, cents: parseFactoryCost(entry.factory_cost) }));
    const valid = costs.filter((entry) => entry.cents !== null);
    const primary = costs.find((entry) => entry.is_primary)?.cents ?? null;
    if (valid.length === 0) return { lowest: null, highest: null, average: null, primary };

    const total = valid.reduce((sum, entry) => sum + entry.cents, 0n);
    const count = BigInt(valid.length);

    return {
        lowest: valid.reduce((lowest, entry) => entry.cents < lowest ? entry.cents : lowest, valid[0].cents),
        highest: valid.reduce((highest, entry) => entry.cents > highest ? entry.cents : highest, valid[0].cents),
        average: (total * 2n + count) / (count * 2n),
        primary,
    };
}

export function formatFactoryCost(cents) {
    if (cents === null) return '\u2014';

    const pesos = String(cents / 100n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return `\u20b1${pesos}.${String(cents % 100n).padStart(2, '0')}`;
}

export function initializeFactoryCosts(form, markDirty) {
    const section = form.querySelector('[data-factory-costs]');
    if (!section) return;

    const container = section.querySelector('[data-factory-cost-rows]');
    const addButton = section.querySelector('#add_factory_cost');
    const feedback = section.querySelector('[data-factory-feedback]');
    const maxEntries = Number(section.dataset.maxEntries);
    const allRows = () => [...container.querySelectorAll('[data-factory-cost-row]')];
    const activeRows = () => allRows().filter((row) => row.dataset.removed !== 'true');
    let nextIndex = Math.max(-1, ...allRows().map((row) => Number(row.dataset.factoryCostIndex)).filter(Number.isFinite)) + 1;

    const refresh = () => {
        const active = activeRows();
        if (active.length && !active.some((row) => row.querySelector('[data-factory-primary]').checked)) {
            const replacement = active.find((row) => parseFactoryCost(row.querySelector('[data-factory-amount]').value) !== null) ?? active[0];
            replacement.querySelector('[data-factory-primary]').checked = true;
        }
        allRows().forEach((row) => {
            const isPrimary = row.dataset.removed !== 'true' && row.querySelector('[data-factory-primary]').checked;
            row.dataset.primary = String(isPrimary);
            row.querySelector('[data-factory-primary-value]').value = isPrimary ? '1' : '0';
            row.querySelector('[data-factory-primary-label]').textContent = isPrimary ? 'Primary factory cost' : 'Set as Primary';
        });
        const entries = active.map((row) => ({
            factory_cost: row.querySelector('[data-factory-amount]').value,
            is_primary: row.querySelector('[data-factory-primary]').checked,
        }));
        const summary = summarizeFactoryCosts(entries);
        Object.entries(summary).forEach(([key, cents]) => {
            section.querySelector(`[data-factory-summary="${key}"]`).textContent = formatFactoryCost(cents);
        });
        const invalidCount = entries.filter((entry) => parseFactoryCost(entry.factory_cost) === null).length;
        section.querySelector('[data-factory-summary-note]').textContent = invalidCount
            ? `${invalidCount} ${invalidCount === 1 ? 'entry needs' : 'entries need'} a valid cost. Comparisons include only valid costs; average is rounded to the nearest cent.`
            : 'Comparisons use valid costs, including zero. Average is rounded to the nearest cent.';
        section.querySelector('[data-factory-empty]').hidden = allRows().length > 0;
        addButton.disabled = active.length >= maxEntries;
    };

    const initializeRow = (row) => {
        const amount = row.querySelector('[data-factory-amount]');
        const amountError = row.querySelector('[data-factory-amount-error]');
        const validateAmount = (showEmpty = false) => {
            const invalid = parseFactoryCost(amount.value) === null;
            amount.setCustomValidity(invalid ? amountError.textContent : '');
            amountError.hidden = !invalid || (!showEmpty && amount.value === '');
        };
        amount.addEventListener('input', () => {
            validateAmount();
            refresh();
        });
        amount.addEventListener('invalid', () => validateAmount(true));
        amount.addEventListener('blur', () => {
            validateAmount();
            const cents = parseFactoryCost(amount.value);
            if (cents !== null) amount.value = `${cents / 100n}.${String(cents % 100n).padStart(2, '0')}`;
        });
        row.querySelector('[data-factory-primary]').addEventListener('change', refresh);

        const removeSaved = row.querySelector('[data-remove-saved-factory]');
        if (removeSaved) {
            removeSaved.addEventListener('change', () => {
                if (removeSaved.checked && !window.confirm('Remove this factory cost when you save changes?')) {
                    removeSaved.checked = false;
                    return;
                }
                if (!removeSaved.checked && activeRows().length >= maxEntries) {
                    removeSaved.checked = true;
                    feedback.textContent = `Remove another entry before restoring this cost. A product can have up to ${maxEntries} factory costs.`;
                    return;
                }
                row.dataset.removed = String(removeSaved.checked);
                row.querySelectorAll('[data-factory-field], [data-factory-primary]').forEach((input) => { input.disabled = removeSaved.checked; });
                if (removeSaved.checked) row.querySelector('[data-factory-primary]').checked = false;
                row.querySelector('[data-factory-removal-note]').hidden = !removeSaved.checked;
                feedback.textContent = removeSaved.checked ? 'Factory cost marked for removal. Save changes to apply.' : 'Factory cost restored.';
                refresh();
                markDirty();
            });
        } else {
            row.querySelector('[data-remove-new-factory]').addEventListener('click', () => {
                row.remove();
                refresh();
                feedback.textContent = 'New factory cost removed.';
                addButton.focus();
                markDirty();
            });
        }
        validateAmount();
    };

    allRows().forEach(initializeRow);
    addButton.addEventListener('click', () => {
        if (activeRows().length >= maxEntries) return;

        const holder = document.createElement('template');
        holder.innerHTML = section.querySelector('#factory_cost_template').innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        const row = holder.content.firstElementChild;
        container.append(row);
        initializeRow(row);
        refresh();
        feedback.textContent = activeRows().length >= maxEntries ? `You have reached the limit of ${maxEntries} factory costs.` : 'Factory cost added. Enter a supplier and cost, then save changes.';
        row.querySelector('[data-factory-name]').focus();
        markDirty();
    });
    refresh();
}
