import assert from 'node:assert/strict';
import test from 'node:test';

import { buildConfirmationPayload, classifyScanRecords, getUnambiguousScanRecord, resetProjectForYearChange } from '../../resources/js/billing-preparation-state.js';

test('only automatically selects a label QR result when it resolves to one delivery', () => {
    const first = { delivery_id: 11, dr_no: 'DR-SHARED' };
    const second = { delivery_id: 12, dr_no: 'DR-SHARED' };

    assert.equal(getUnambiguousScanRecord([first]), first);
    assert.equal(getUnambiguousScanRecord([first, second]), null);
    assert.equal(getUnambiguousScanRecord([]), null);
});

test('classifies repeated delivery IDs as duplicates while preserving distinct deliveries with the same DR number', () => {
    const results = classifyScanRecords([
        { delivery_id: 11, dr_no: 'DR-SHARED', eligible: true },
        { delivery_id: 12, dr_no: 'DR-SHARED', eligible: true },
        { delivery_id: 13, dr_no: 'DR-HELD', eligible: false },
    ], [11]);

    assert.deepEqual(results.map(({ record, status }) => [record.delivery_id, status]), [
        [11, 'duplicate'],
        [12, 'eligible'],
        [13, 'rejected'],
    ]);
});

test('clears Project and submits the unchanged Year filter form when Year changes', () => {
    const project = {
        value: '42',
        dispatchEvent(event) {
            this.changeEvent = event;
        },
    };
    let submitted = false;

    resetProjectForYearChange(project, () => {
        submitted = true;
    });

    assert.equal(project.value, '');
    assert.equal(project.changeEvent.type, 'change');
    assert.equal(project.changeEvent.bubbles, true);
    assert.equal(submitted, true);
});

test('confirms Select All from server filters and the complete expected ID set', () => {
    const payload = buildConfirmationPayload({
        selectionMode: 'all',
        selectedIds: [11, 13],
        selectionMethods: new Set(['all']),
        allCriteria: { year: '2026', project_id: '4', lot_id: '2' },
        selectionFilters: null,
        excludedIds: [12],
        idempotencyKey: 'batch-key',
    });

    assert.deepEqual(payload, {
        mode: 'all',
        idempotency_key: 'batch-key',
        year: '2026',
        project_id: '4',
        lot_id: '2',
        expected_delivery_ids: [11, 13],
        excluded_delivery_ids: [12],
    });
    assert.equal('delivery_ids' in payload, false);
});

test('keeps range criteria with an explicit selection and marks cross-method selections as mixed', () => {
    const rangePayload = buildConfirmationPayload({
        selectionMode: 'range',
        selectedIds: [21],
        selectionMethods: new Set(['range']),
        allCriteria: null,
        selectionFilters: { year: '2026', project_id: '4', dr_from: '100', dr_to: '120' },
        excludedIds: [],
        idempotencyKey: 'range-key',
    });
    const mixedPayload = buildConfirmationPayload({
        selectionMode: 'search',
        selectedIds: [21, 22],
        selectionMethods: new Set(['range', 'scan']),
        allCriteria: null,
        selectionFilters: null,
        excludedIds: [],
        idempotencyKey: 'mixed-key',
    });

    assert.equal(rangePayload.mode, 'range');
    assert.equal(rangePayload.selection_filters.dr_from, '100');
    assert.deepEqual(mixedPayload.delivery_ids, [21, 22]);
    assert.equal(mixedPayload.mode, 'mixed');
});
