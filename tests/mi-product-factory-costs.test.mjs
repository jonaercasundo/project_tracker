import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatFactoryCost, parseFactoryCost, summarizeFactoryCosts } from '../resources/js/mi-product-factory-costs.js';

test('excludes legacy PHP quotes from USD summaries without converting their amounts', () => {
    const entries = [
        { factory_cost: '1000.00', currency: 'PHP', is_primary: true },
        { factory_cost: '20.00', currency: 'USD', is_primary: false },
        { factory_cost: '30.00', currency: 'USD', is_primary: false },
    ];
    assert.deepEqual(summarizeFactoryCosts(entries), { lowest: 2000n, highest: 3000n, average: 2500n, primary: null });
    entries[0].currency = 'USD';
    assert.deepEqual(summarizeFactoryCosts(entries), { lowest: 2000n, highest: 100000n, average: 35000n, primary: 100000n });
    assert.equal(entries[0].factory_cost, '1000.00');
});

test('compares quotes independently and reports the selected primary cost', () => {
    const entries = [
        { factory_cost: '1250.00', is_primary: true },
        { factory_cost: '1180.00', is_primary: false },
        { factory_cost: '1350.00', is_primary: false },
    ];

    const summary = summarizeFactoryCosts(entries);

    assert.deepEqual(summary, { lowest: 118000n, highest: 135000n, average: 126000n, primary: 125000n });
    assert.equal(formatFactoryCost(summary.average), 'USD 1,260.00');
});

test('includes zero costs and rounds a half-cent average up without floating point errors', () => {
    const summary = summarizeFactoryCosts([
        { factory_cost: '0', is_primary: true },
        { factory_cost: '0.01', is_primary: false },
    ]);

    assert.deepEqual(summary, { lowest: 0n, highest: 1n, average: 1n, primary: 0n });
    assert.equal(formatFactoryCost(summary.primary), 'USD 0.00');
});

test('keeps every cent when averaging 100 quotes near the database maximum', () => {
    const entries = Array.from({ length: 100 }, (_, index) => ({ factory_cost: index < 51 ? '9999999999999.98' : '9999999999999.99', is_primary: index === 0 }));

    const summary = summarizeFactoryCosts(entries);

    assert.equal(summary.average, 999999999999998n);
    assert.equal(formatFactoryCost(summary.highest), 'USD 9,999,999,999,999.99');
});

test('ignores invalid quotes without substituting another value for an invalid primary', () => {
    const summary = summarizeFactoryCosts([
        { factory_cost: '-1', is_primary: true },
        { factory_cost: '12.50', is_primary: false },
        { factory_cost: '', is_primary: false },
    ]);

    assert.deepEqual(summary, { lowest: 1250n, highest: 1250n, average: 1250n, primary: null });
    assert.equal(formatFactoryCost(summary.primary), '\u2014');
    assert.deepEqual(summarizeFactoryCosts([]), { lowest: null, highest: null, average: null, primary: null });
});

test('accepts plain non-negative decimals and rejects overprecision, overflow, and non-decimal values', () => {
    assert.equal(parseFactoryCost('0012.3'), 1230n);
    assert.equal(parseFactoryCost('9999999999999.99'), 999999999999999n);
    for (const value of ['', '-0.01', '1.234', '10000000000000', '1e3', '1,250.00', 'NaN', 'Infinity', ' 1', '.50', '1.']) {
        assert.equal(parseFactoryCost(value), null, `Unexpectedly accepted ${value}`);
    }
});
