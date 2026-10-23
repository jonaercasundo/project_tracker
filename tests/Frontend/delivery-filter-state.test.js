import assert from 'node:assert/strict';
import test from 'node:test';

import { resetProjectForYearChange } from '../../resources/js/delivery-filter-state.js';

test('clears and enables Project after choosing a year, then submits the filters', () => {
    const year = { value: '2025' };
    const project = {
        value: '42',
        disabled: false,
        dispatchEvent(event) {
            this.changeEvent = event;
        },
    };
    let submitted = false;

    resetProjectForYearChange(year, project, () => {
        submitted = true;
    });

    assert.equal(project.value, '');
    assert.equal(project.disabled, false);
    assert.equal(project.changeEvent.type, 'change');
    assert.equal(project.changeEvent.bubbles, true);
    assert.equal(submitted, true);
});

test('clears and disables Project when returning to All Years', () => {
    const year = { value: '' };
    const project = {
        value: '42',
        disabled: false,
        dispatchEvent() {},
    };

    resetProjectForYearChange(year, project, () => {});

    assert.equal(project.value, '');
    assert.equal(project.disabled, true);
});
