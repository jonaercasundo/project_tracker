export function resetProjectForYearChange(project, submitForm) {
    project.value = '';
    project.dispatchEvent(new Event('change', { bubbles: true }));
    submitForm();
}

export function classifyScanRecords(records, selectedIds) {
    const seenIds = new Set(selectedIds);

    return records.map((record) => {
        if (!record.eligible) {
            return { record, status: 'rejected' };
        }

        const key = record.group_key ?? record.delivery_id;
        if (seenIds.has(key)) {
            return { record, status: 'duplicate' };
        }

        seenIds.add(key);

        return { record, status: 'eligible' };
    });
}

export function getUnambiguousScanRecord(records) {
    return records.length === 1 ? records[0] : null;
}

export function buildConfirmationPayload({
    selectionMode,
    selectedIds,
    selectionMethods,
    allCriteria,
    selectionFilters,
    excludedIds,
    expectedIds = selectedIds,
    idempotencyKey,
}) {
    const isAllSelection = selectionMode === 'all' && allCriteria !== null && selectionMethods.size === 1;
    const selectedMethod = selectionMethods.values().next().value || selectionMode;
    const mode = isAllSelection ? 'all' : (selectionMethods.size > 1 || selectedMethod === 'all' ? 'mixed' : selectedMethod);
    const body = { mode, idempotency_key: idempotencyKey };

    if (isAllSelection) {
        Object.assign(body, allCriteria);
        body.expected_delivery_ids = [...expectedIds];
        body.excluded_delivery_ids = [...excludedIds];
    } else {
        body.delivery_ids = [...selectedIds];
        if (mode === 'range' && selectionFilters) body.selection_filters = selectionFilters;
    }

    return body;
}
