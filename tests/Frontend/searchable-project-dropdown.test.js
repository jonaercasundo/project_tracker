import assert from 'node:assert/strict';
import test from 'node:test';

import {
    filterProjectOptions,
    nextProjectOptionIndex,
    searchableProjectDropdown,
} from '../../resources/js/searchable-project-dropdown.js';

test('searches full project names and project codes while keeping All Projects available', () => {
    const projects = [
        { id: '12', name: 'Northern Division Learning Materials Distribution Program', code: 'MMC-2026-12' },
        { id: '13', name: 'Furniture Delivery', code: 'MMC-2026-13' },
    ];

    assert.deepEqual(filterProjectOptions(projects, 'learning materials').map(({ id }) => id), ['12']);
    assert.deepEqual(filterProjectOptions(projects, '2026-13').map(({ id }) => id), ['13']);
    assert.equal(filterProjectOptions(projects, '')[0].name, 'All Projects');
});

test('moves keyboard selection through options and wraps at either end', () => {
    assert.equal(nextProjectOptionIndex(0, -1, 3), 2);
    assert.equal(nextProjectOptionIndex(2, 1, 3), 0);
    assert.equal(nextProjectOptionIndex(2, 1, 0), 0);
});

test('keeps a selected project on the trigger and submits its project_id on Enter selection', () => {
    const dropdown = searchableProjectDropdown({
        options: [{ id: '42', name: 'Full Long Project Name', code: 'PRJ-42' }],
        selectedId: '42',
        enabled: true,
        allLabel: 'All Projects',
        idPrefix: 'billing-project',
    });
    const dispatchedEvents = [];
    dropdown.$refs = {
        project: {
            value: '',
            dispatchEvent(event) {
                dispatchedEvents.push(event);
            },
        },
        trigger: { focus() {} },
    };

    assert.equal(dropdown.selectedLabel(), 'Full Long Project Name · PRJ-42');
    dropdown.selectOption({ id: '42', name: 'Full Long Project Name', code: 'PRJ-42' });

    assert.equal(dropdown.$refs.project.value, '42');
    assert.equal(dispatchedEvents[0].type, 'change');
    assert.equal(dispatchedEvents[0].bubbles, true);
    assert.equal(dropdown.open, false);
});

test('positions its fixed menu within a narrow viewport and caps its height at 350px', () => {
    const originalWindow = globalThis.window;
    globalThis.window = { innerWidth: 390, innerHeight: 800 };
    const dropdown = searchableProjectDropdown({
        options: [],
        selectedId: '',
        enabled: true,
        allLabel: 'All Projects',
        idPrefix: 'billing-project',
    });
    dropdown.$refs = {
        trigger: { getBoundingClientRect: () => ({ left: 230, right: 290, top: 180, bottom: 222, width: 60 }) },
    };

    dropdown.positionDropdown();

    assert.equal(dropdown.dropdownStyle.width, '360px');
    assert.equal(dropdown.dropdownStyle.left, '22px');
    assert.equal(dropdown.dropdownStyle.maxHeight, '350px');
    globalThis.window = originalWindow;
});
