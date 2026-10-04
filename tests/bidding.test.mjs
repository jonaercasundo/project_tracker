import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { readFile, mkdir, access, readdir } from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const source = await readFile(new URL('../resources/js/bidding.js', import.meta.url), 'utf8');
const alpineSource = await readFile(new URL('../node_modules/alpinejs/dist/cdn.min.js', import.meta.url), 'utf8');
const documentSource = await readFile(new URL('../resources/js/bidding-documents.js', import.meta.url), 'utf8');
let browser;
before(async () => {
    const installed = 'C:/Users/RHM/AppData/Local/ms-playwright/chromium_headless_shell-1234/chrome-headless-shell-win64/chrome-headless-shell.exe';
    let executablePath = process.env.BIDDING_BROWSER_EXECUTABLE;
    if (!executablePath) { try { await access(installed); executablePath = installed; } catch {} }
    browser = await chromium.launch({ headless: true, executablePath });
});
after(async () => { await browser?.close(); });

const button = action => `<button type="button" data-bidding-action="${action}">${action}</button>`;
function item(index, prefix, options = {}) {
    return `<tr data-bidding-item data-entry-index="${index}" data-name-prefix="${prefix}" data-legacy="${Boolean(options.legacy)}" data-original-quantity="${options.quantity ?? '1.00'}" data-original-total="${options.total ?? '10.20'}" data-original-cost="${options.cost ?? '10.20'}"><td>
    ${options.legacy ? `<input type="hidden" name="${prefix}[id]" value="40">` : ''}
    <input data-catalog-search><select data-catalog-item name="${prefix}[catalog_item_id]"><option value="">Choose</option><option value="9" data-description="Saved item" data-unit="box" data-price="10.20">Saved item</option></select><span data-catalog-message></span>
    <textarea data-item-description name="${prefix}[item_description]"></textarea><input data-item-unit name="${prefix}[unit]">
    <input data-item-quantity name="${prefix}[quantity]" value="${options.quantity ?? '1.00'}"><input data-item-cost name="${prefix}[unit_cost]" value="${options.cost ?? '10.20'}"><input data-item-total name="${prefix}[total_amount]" readonly><span data-item-price-message></span>${button('remove-item')}</td></tr>`;
}
function stage(index, prefix, nested = false) {
    return `<section data-bidding-stage data-entry-index="${index}" data-name-prefix="${prefix}"><input name="${prefix}[name]" value="Stage"><input type="hidden" name="${prefix}[items_present]" value="1">${button('remove-stage')}<table><tbody data-bidding-items data-collection="items" data-name-prefix="${prefix}[items]">${nested ? item(0, `${prefix}[items][0]`) + item(2, `${prefix}[items][2]`) : ''}</tbody></table>${button('add-item')}</section>`;
}
function address(index, prefix, nested = false) {
    return `<div data-bidding-address data-entry-index="${index}" data-name-prefix="${prefix}"><textarea name="${prefix}[delivery_address]">Address</textarea><input type="hidden" name="${prefix}[keystages_present]" value="1">${button('remove-address')}<div data-bidding-stages data-collection="stages" data-name-prefix="${prefix}[keystages]">${nested ? stage(0, `${prefix}[keystages][0]`, true) + stage(2, `${prefix}[keystages][2]`) : ''}</div>${button('add-stage')}</div>`;
}
function lot(index, prefix, nested = false, location = {}) {
    return `<section data-bidding-lot data-entry-index="${index}" data-name-prefix="${prefix}"><input data-lot-number name="${prefix}[lot_no]" value="Lot ${Number(index) + 1}">${button('remove-lot')}<input type="hidden" name="${prefix}[addresses_present]" value="1"><input type="hidden" name="${prefix}[legacy_items_present]" value="1">${['region', 'province', 'city', 'barangay'].map(level => `<select data-location="${level}" data-selected="${location[level] || ''}" name="${prefix}[${level}_code]"><option value="">Choose</option>${location[level] ? `<option value="${location[level]}" selected>${location[level]}</option>` : ''}</select>`).join('')}<div data-location-message><span></span>${button('retry-locations')}</div><div data-bidding-addresses data-collection="addresses" data-name-prefix="${prefix}[addresses]">${nested ? address(0, `${prefix}[addresses][0]`, true) + address(2, `${prefix}[addresses][2]`) : ''}</div>${button('add-address')}<span data-bidding-lot-total></span></section>`;
}
function fixture(options = {}) {
    return `<html><body><form data-bidding-form data-catalog-url="/catalog" data-regions-url="/regions" data-provinces-url="/provinces" data-cities-url="/cities" data-barangays-url="/barangays"><input type="hidden" name="hierarchy_present" value="1"><input name="approved_budget_contract_abc" value="1000.00">${button('add-lot')}<div data-bidding-lots data-collection="lots" data-name-prefix="lots">${lot(0, 'lots[0]', options.nested, options.location)}${options.sparse ? lot(2, 'lots[2]') : ''}</div><p data-bidding-empty-lots hidden></p><span data-bidding-calculated-total></span><p data-bidding-feedback></p><input type="hidden" name="hierarchy_complete" value="1"><button data-bidding-save>Save</button><span data-bidding-save-status></span><template data-bidding-template="lot">${lot('__INDEX__', '__PREFIX__')}</template><template data-bidding-template="address">${address('__INDEX__', '__PREFIX__')}</template><template data-bidding-template="stage">${stage('__INDEX__', '__PREFIX__')}</template><template data-bidding-template="item">${item('__INDEX__', '__PREFIX__')}</template></form></body></html>`;
}
const standardLookup = url => {
    if (url.pathname.endsWith('/regions')) return [{ code: 'R1', name: 'Region one' }, { code: 'R2', name: 'Region two' }];
    if (url.pathname.endsWith('/provinces')) return [{ code: `${url.searchParams.get('region')}-P`, name: 'Province' }];
    if (url.pathname.endsWith('/cities')) return [{ code: `${url.searchParams.get('province')}-C`, name: 'City' }];
    if (url.pathname.endsWith('/barangays')) return [{ code: `${url.searchParams.get('city')}-B`, name: 'Barangay' }];
    if (url.pathname.endsWith('/catalog')) return { items: [{ id: 12, item_name: '<script>unsafe</script>', description: 'Catalog description', unit: 'piece', price: '0.10' }], has_more: false };
    return {};
};
async function open(html = fixture(), handler = standardLookup, scripts = [source]) {
    const page = await browser.newPage();
    const errors = [];
    const requests = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', dialog => dialog.accept());
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: html });
        requests.push(url);
        const data = await handler(url, route.request());
        if (data?.rawResponse) return route.fulfill(data.rawResponse);
        await route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) });
    });
    await page.goto('https://bidding.test/');
    for (const content of scripts) await page.addScriptTag({ content: `{${content}}` });
    return { page, errors, requests };
}
async function clickWithin(parent, action) { await parent.locator(`[data-bidding-action="${action}"]`).first().click(); }

test('one click adds one node at each level; stable counters survive deleting highest restored indices', async () => {
    const { page, errors } = await open(fixture({ nested: true, sparse: true }));
    await page.addScriptTag({ content: `{${source}}` });
    const firstLot = page.locator('[data-bidding-lot]').first();
    const firstAddress = firstLot.locator('[data-bidding-address]').first();
    const firstStage = firstAddress.locator('[data-bidding-stage]').first();
    await clickWithin(page.locator('[data-bidding-lot]').last(), 'remove-lot');
    await clickWithin(page, 'add-lot');
    await clickWithin(page, 'add-lot');
    assert.deepEqual(await page.locator('[data-bidding-lot]').evaluateAll(nodes => nodes.map(node => node.dataset.entryIndex)), ['0', '3', '4']);
    await clickWithin(firstLot.locator('[data-bidding-address]').last(), 'remove-address');
    await clickWithin(firstLot, 'add-address');
    await clickWithin(firstLot, 'add-address');
    assert.deepEqual(await firstLot.locator('[data-bidding-address]').evaluateAll(nodes => nodes.map(node => node.dataset.entryIndex)), ['0', '3', '4']);
    await clickWithin(firstAddress.locator('[data-bidding-stage]').last(), 'remove-stage');
    await clickWithin(firstAddress, 'add-stage');
    await clickWithin(firstAddress, 'add-stage');
    assert.deepEqual(await firstAddress.locator('[data-bidding-stage]').evaluateAll(nodes => nodes.map(node => node.dataset.entryIndex)), ['0', '3', '4']);
    await clickWithin(firstStage.locator('[data-bidding-item]').last(), 'remove-item');
    await clickWithin(firstStage, 'add-item');
    await clickWithin(firstStage, 'add-item');
    assert.deepEqual(await firstStage.locator('[data-bidding-item]').evaluateAll(nodes => nodes.map(node => node.dataset.entryIndex)), ['0', '3', '4']);
    const names = await page.locator('form').evaluate(form => [...new FormData(form).keys()]);
    assert.equal(names.length, new Set(names).size);
    assert(names.includes('lots[0][addresses][0][keystages][0][items][4][quantity]'));
    assert.equal(names.at(-1), 'hierarchy_complete');
    assert.deepEqual(errors, []);
    await page.close();
});

test('catalog IDs populate editable fields; exact costs and quantities keep ABC independent', async () => {
    const { page, errors } = await open();
    const firstLot = page.locator('[data-bidding-lot]').first();
    await clickWithin(firstLot, 'add-address');
    await clickWithin(firstLot, 'add-stage');
    await clickWithin(firstLot, 'add-item');
    const row = firstLot.locator('[data-bidding-item]');
    await row.locator('[data-catalog-item]').selectOption('9');
    assert.equal(await row.locator('[data-item-description]').inputValue(), 'Saved item');
    assert.equal(await row.locator('[data-item-unit]').inputValue(), 'box');
    assert.equal(await row.locator('[data-item-cost]').inputValue(), '10.20');
    await row.locator('[data-item-quantity]').fill('2.50');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '25.50');
    assert.equal(await page.locator('[data-bidding-calculated-total]').textContent(), '25.50');
    assert.equal(await page.locator('[name="approved_budget_contract_abc"]').inputValue(), '1000.00');
    await row.locator('[data-item-quantity]').fill('0.05');
    await row.locator('[data-item-cost]').fill('0.10');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '0.01');
    await row.locator('[data-item-quantity]').fill('9999999999999.99');
    await row.locator('[data-item-cost]').fill('1.00');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '9999999999999.99');
    await row.locator('[data-item-cost]').fill('2.00');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '');
    assert(await row.locator('[data-item-cost]').evaluate(input => input.validationMessage.includes('exceeds')));
    await row.locator('[data-catalog-search]').fill('book');
    await page.waitForFunction(() => document.querySelector('[data-catalog-message]').textContent === '1 catalog results.');
    assert.equal(await row.locator('[data-catalog-item]').inputValue(), '9');
    await row.locator('[data-catalog-item]').selectOption('12');
    assert.equal(await row.locator('[data-item-description]').inputValue(), 'Catalog description');
    assert.equal(await page.locator('script:not([src])').count(), 1);
    assert.deepEqual(errors, []);
    await page.close();
});

test('legacy missing cost retains saved total until quantity changes, then requires a cost', async () => {
    const html = fixture().replace('<span data-bidding-lot-total>', `<table><tbody data-bidding-items data-collection="items" data-name-prefix="lots[0][legacy_items]">${item(0, 'lots[0][legacy_items][0]', { legacy: true, quantity: '2.50', cost: '', total: '25.50' })}</tbody></table><span data-bidding-lot-total>`);
    const { page, errors } = await open(html);
    const row = page.locator('[data-bidding-item]');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '25.50');
    assert.equal(await row.locator('[data-item-cost]').evaluate(input => input.required), false);
    await row.locator('[data-item-quantity]').fill('3');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '');
    assert.equal(await row.locator('[data-item-cost]').evaluate(input => input.required), true);
    await row.locator('[data-item-cost]').fill('10.20');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '30.60');
    assert.deepEqual(errors, []);
    await page.close();
});


test('legacy missing quantities preserve their amount and require both values when pricing changes', async () => {
    const html = fixture().replace('<span data-bidding-lot-total>', `<table><tbody data-bidding-items data-collection="items" data-name-prefix="lots[0][legacy_items]">${item(0, 'lots[0][legacy_items][0]', { legacy: true, quantity: '', cost: '', total: '25.50' })}</tbody></table><span data-bidding-lot-total>`);
    const { page, errors } = await open(html);
    const row = page.locator('[data-bidding-item]');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '25.50');
    assert.equal(await row.locator('[data-item-quantity]').evaluate(input => input.required), false);
    assert((await row.locator('[data-item-price-message]').textContent()).includes('Quantity and unit cost not provided'));
    await row.locator('[data-item-cost]').fill('10.20');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '');
    assert.equal(await row.locator('[data-item-quantity]').evaluate(input => input.required), true);
    await row.locator('[data-item-quantity]').fill('3');
    assert.equal(await row.locator('[data-item-total]').inputValue(), '30.60');
    assert.deepEqual(errors, []);
    await page.close();
});


test('submit locks duplicate saves and browser history restores the save button', async () => {
    const { page, errors } = await open();
    await page.locator('form').evaluate(form => { form.addEventListener('submit', event => event.preventDefault()); form.requestSubmit(); });
    assert.equal(await page.locator('[data-bidding-save]').isDisabled(), true);
    assert.equal(await page.locator('[data-bidding-save-status]').textContent(), 'Saving bidding document...');
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow')));
    assert.equal(await page.locator('[data-bidding-save]').isDisabled(), false);
    assert.equal(await page.locator('[data-bidding-save-status]').textContent(), '');
    assert.deepEqual(errors, []);
    await page.close();
});

test('saved geography restores every level and delayed obsolete region responses cannot overwrite selection', async () => {
    const location = { region: 'R1', province: 'R1-P', city: 'R1-P-C', barangay: 'R1-P-C-B' };
    const { page, errors } = await open(fixture({ location }), async url => {
        if (url.pathname.endsWith('/provinces') && url.searchParams.get('region') === 'R1') await new Promise(resolve => setTimeout(resolve, 80));
        return standardLookup(url);
    });
    await page.waitForFunction(() => document.querySelector('[data-location="barangay"]').value === 'R1-P-C-B' && !document.querySelector('[data-location="barangay"]').disabled);
    await page.locator('[data-location="region"]').selectOption('R2');
    await page.locator('[data-location="region"]').selectOption('R1');
    await page.locator('[data-location="region"]').selectOption('R2');
    await page.waitForFunction(() => document.querySelector('[data-location="province"]').options[1]?.value === 'R2-P');
    await page.waitForTimeout(150);
    assert.equal(await page.locator('[data-location="province"]').evaluate(select => select.options[1].value), 'R2-P');
    assert.equal(await page.locator('[data-location="city"]').inputValue(), '');
    assert.equal(await page.locator('[data-location="barangay"]').inputValue(), '');
    assert.deepEqual(errors, []);
    await page.close();
});


test('saved geography remains serialized while restoration is loading or a lookup fails', async () => {
    const location = { region: 'R1', province: 'R1-P', city: 'R1-P-C', barangay: 'R1-P-C-B' };
    const { page, errors } = await open(fixture({ location }), async url => {
        if (url.pathname.endsWith('/provinces')) {
            await new Promise(resolve => setTimeout(resolve, 150));
            return { rawResponse: { status: 500, contentType: 'application/json', body: JSON.stringify({ message: 'Synthetic province failure' }) } };
        }
        return standardLookup(url);
    });
    await page.waitForFunction(() => document.querySelector('[data-location="province"] option').textContent === 'Loading...');
    const serialized = await page.locator('form').evaluate(form => Object.fromEntries(new FormData(form)));
    for (const [level, code] of Object.entries(location)) assert.equal(serialized[`lots[0][${level}_code]`], code);
    await page.waitForFunction(() => document.querySelector('[data-location-message] span').textContent === 'Synthetic province failure');
    const afterFailure = await page.locator('form').evaluate(form => Object.fromEntries(new FormData(form)));
    for (const [level, code] of Object.entries(location)) assert.equal(afterFailure[`lots[0][${level}_code]`], code);
    assert.deepEqual(errors, []);
    await page.close();
});

test('province-less geography and failed lookup retry work; unrelated pages make no requests', async () => {
    let failure = true;
    const { page, errors } = await open(fixture(), url => {
        if (url.pathname.endsWith('/regions') && failure) { failure = false; return { rawResponse: { status: 500, contentType: 'application/json', body: JSON.stringify({ message: 'Lookup unavailable' }) } }; }
        if (url.pathname.endsWith('/provinces')) return [];
        if (url.pathname.endsWith('/cities')) return [{ code: 'CITY', name: 'Province-less city' }];
        return standardLookup(url);
    });
    await page.waitForFunction(() => document.querySelector('[data-location-message] span').textContent === 'Lookup unavailable');
    await clickWithin(page, 'retry-locations');
    await page.waitForFunction(() => document.querySelector('[data-location="region"]').options.length === 3);
    await page.locator('[data-location="region"]').selectOption('R2');
    await page.waitForFunction(() => document.querySelector('[data-location="city"]').options[1]?.value === 'CITY');
    assert.equal(await page.locator('[data-location="province"]').isDisabled(), true);
    assert.deepEqual(errors, []);
    await page.close();
    const unrelated = await open('<html><body><input name="quantity" value="5"></body></html>', standardLookup, [source, documentSource]);
    assert.deepEqual(unrelated.requests, []);
    assert.deepEqual(unrelated.errors, []);
    await unrelated.page.close();
});


const documentMarkup = (await readFile(new URL('../resources/views/operation/bidding/partials/_documents.blade.php', import.meta.url), 'utf8'))
    .replace(/^@if.*$/gm, '').replace(/^@endif\s*$/gm, '')
    .replace(/data-([a-z-]+)-url="{{[^\n]+?}}"/g, (_, name) => `data-${name}-url="/${name}${name.startsWith('folder-') && name !== 'folder-store' ? '/__FOLDER__' : ''}"`);
function documentFixture() { return `<html><head><meta name="csrf-token" content="synthetic-token"></head><body>${documentMarkup}</body></html>`; }
function documentApi() {
    const calls = [];
    const folders = [{ id: 1, parent_id: null, name: 'Specifications', document_count: 1 }];
    const documents = [4, 5].map(id => ({ id, folder_id: id === 4 ? 1 : null, display_name: id === 4 ? '<img src=x onerror=alert(1)>' : 'Delivery schedule', original_name: `original-${id}.pdf`, extension: 'pdf', mime_type: 'application/pdf', file_size: 1024, description: 'Saved description', version: 2, uploaded_by: { id: 1, name: 'Synthetic uploader' }, created_at: '2026-01-01T10:00:00Z', updated_at: '2026-01-02T10:00:00Z', download_url: `/download/${id}`, preview_url: `/preview/${id}`, history_url: `/history/${id}`, update_url: `/update/${id}`, replace_url: `/replace/${id}`, delete_url: `/delete/${id}` }));
    const uploadAttempts = new Map();
    async function handler(url, request) {
        const method = request.method();
        calls.push({ path: url.pathname, method, body: request.postData(), headers: request.headers() });
        if (url.pathname === '/index') {
            let filtered = documents;
            const folder = url.searchParams.get('folder_id');
            if (folder) filtered = filtered.filter(item => folder === 'root' ? item.folder_id === null : String(item.folder_id) === folder);
            const search = url.searchParams.get('search');
            if (search) filtered = filtered.filter(item => item.display_name.toLowerCase().includes(search.toLowerCase()));
            return { folders, documents: filtered, permissions: { upload: true, update: true, delete: true }, limits: { extensions: ['pdf', 'png'], max_file_size_kb: 1024, max_files: 10, max_zip_files: 100 }, meta: { current_page: 1, last_page: 1, total: filtered.length } };
        }
        if (url.pathname.startsWith('/update/')) {
            const body = JSON.parse(request.postData());
            Object.assign(documents.find(item => item.id === Number(url.pathname.split('/').pop())), body);
            return { message: 'Document updated.' };
        }
        if (url.pathname === '/folder-store') { folders.push({ id: 2, name: JSON.parse(request.postData()).name, parent_id: null, document_count: 0 }); return { message: 'Folder created.' }; }
        if (url.pathname.startsWith('/folder-update/')) { folders[0].name = JSON.parse(request.postData()).name; return { message: 'Folder renamed.' }; }
        if (url.pathname.startsWith('/folder-delete/')) { folders.splice(folders.findIndex(folder => folder.id === Number(url.pathname.split('/').pop())), 1); return { message: 'Folder deleted.' }; }
        if (url.pathname.startsWith('/history/')) return { versions: [{ id: 1, version: 1, original_name: 'original-version.pdf', file_size: 100, uploaded_by: { name: 'Synthetic uploader' }, created_at: '2026-01-01T10:00:00Z', download_url: '/version/1' }], meta: { current_page: 1, last_page: 1, total: 1 } };
        if (url.pathname === '/zip') return { rawResponse: { contentType: 'application/zip', body: 'synthetic ZIP response' } };
        if (url.pathname === '/store' || url.pathname.startsWith('/replace/')) {
            const body = request.postData() || '';
            const filename = body.match(/filename="([^"]+)"/)?.[1] || 'unknown';
            const attempts = (uploadAttempts.get(filename) || 0) + 1;
            uploadAttempts.set(filename, attempts);
            await new Promise(resolve => setTimeout(resolve, 150));
            if (filename === 'failed.pdf' && attempts === 1) return { rawResponse: { status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'Validation failed', errors: { 'files.0': ['Synthetic file rejection.'] } }) } };
            return { message: 'Uploaded successfully.', documents: [] };
        }
        if (url.pathname.startsWith('/delete/')) { documents.splice(documents.findIndex(item => item.id === Number(url.pathname.split('/').pop())), 1); return { message: 'Deleted.' }; }
        return {};
    }
    return { handler, calls };
}

test('document cards escape names, expose downloads/history, edit names and move folders, and ZIP uses selected IDs', async () => {
    const api = documentApi();
    const { page, errors } = await open(documentFixture(), api.handler, [documentSource]);
    await page.waitForSelector('[data-document-card="4"]');
    assert.equal(await page.locator('[data-document-card="4"] h4').textContent(), '<img src=x onerror=alert(1)>');
    assert.equal(await page.locator('[data-document-card] img').count(), 0);
    assert.equal(await page.locator('[data-document-card="4"] a').first().getAttribute('href'), '/download/4');
    await page.locator('[data-document-card="4"] [data-document-action="history"]').click();
    await page.waitForSelector('[data-document-history] a');
    assert.equal(await page.locator('[data-document-history] a').getAttribute('href'), '/version/1');
    await page.locator('dialog [data-document-action="close-dialog"]').first().click();
    await page.locator('[data-document-card="4"] [data-document-action="edit-document"]').click();
    await page.locator('[name="name"]').fill('Technical specification');
    await page.locator('[name="description"]').fill('Revised metadata');
    await page.locator('[name="folder_id"]').selectOption('');
    await page.locator('[data-document-dialog-save]').click();
    await page.waitForFunction(() => document.querySelector('[data-document-card="4"] h4').textContent === 'Technical specification');
    const update = api.calls.find(call => call.path === '/update/4');
    assert.equal(update.method, 'PUT');
    assert.deepEqual(JSON.parse(update.body), { display_name: 'Technical specification', description: 'Revised metadata', folder_id: null });
    assert.equal(update.headers['x-csrf-token'], 'synthetic-token');
    await page.locator('[data-document-select="4"]').check();
    await page.locator('[data-document-select="5"]').check();
    const download = page.waitForEvent('download');
    await page.locator('[data-document-action="zip"]').click();
    assert.equal((await download).suggestedFilename(), 'bidding-documents.zip');
    const zip = api.calls.find(call => call.path === '/zip');
    assert.equal(zip.method, 'POST');
    assert.deepEqual(JSON.parse(zip.body), { document_ids: ['4', '5'] });
    await page.locator('[data-document-search]').fill('schedule');
    await page.waitForFunction(() => document.querySelectorAll('[data-document-card]').length === 1);
    assert.equal(await page.locator('[data-document-card]').getAttribute('data-document-card'), '5');
    assert.deepEqual(errors, []);
    await page.close();
});

test('document uploads report individual progress/errors, retry only failed files, replace, and reject disallowed types locally', async () => {
    const api = documentApi();
    const { page, errors } = await open(documentFixture(), api.handler, [documentSource]);
    await page.waitForSelector('[data-document-card="4"]');
    await page.locator('[data-document-files]').setInputFiles([{ name: 'accepted.pdf', mimeType: 'application/pdf', buffer: Buffer.from('synthetic accepted') }, { name: 'failed.pdf', mimeType: 'application/pdf', buffer: Buffer.from('synthetic rejected') }, { name: 'blocked.exe', mimeType: 'application/octet-stream', buffer: Buffer.from('synthetic blocked') }]);
    assert.equal(await page.locator('[data-upload-entry]').count(), 3);
    assert((await page.locator('[data-upload-entry="2"]').textContent()).includes('not allowed'));
    await page.locator('[data-document-action="start-upload"]').click();
    await page.waitForFunction(() => document.querySelector('[data-upload-entry="0"]').textContent.includes('Uploading'));
    await page.waitForFunction(() => document.querySelector('[data-upload-entry="0"]').textContent.includes('Uploaded successfully') && document.querySelector('[data-upload-entry="1"]').textContent.includes('Synthetic file rejection'));
    assert.equal(await page.locator('[data-upload-entry="0"] progress').getAttribute('value'), '100');
    await page.waitForFunction(() => document.querySelector('[data-document-status]').textContent.includes('Upload processing finished'));
    assert.equal(api.calls.filter(call => call.path === '/store').length, 2);
    await page.locator('[data-upload-entry="1"] [data-document-action="retry-upload"]').click();
    await page.waitForFunction(() => document.querySelector('[data-upload-entry="1"]').textContent.includes('Uploaded successfully'));
    assert.equal(api.calls.filter(call => call.path === '/store').length, 3);
    await page.waitForFunction(() => document.querySelector('[data-document-status]').textContent.includes('Upload processing finished'));
    assert(api.calls.filter(call => call.path === '/store').every(call => call.body.includes('name="files[]"') && call.headers['x-csrf-token'] === 'synthetic-token'));
    await page.locator('[data-document-card="4"] [data-document-action="replace"]').evaluate(button => button.click());
    await page.locator('[data-document-replacement]').setInputFiles({ name: 'replacement.pdf', mimeType: 'application/pdf', buffer: Buffer.from('synthetic replacement') });
    await page.waitForFunction(() => document.querySelector('[data-upload-entry="0"]').textContent.includes('Uploaded successfully'));
    assert(api.calls.some(call => call.path === '/replace/4' && call.method === 'POST' && call.body.includes('name="file"')));
    assert.deepEqual(errors, []);
    await page.close();
});

test('folder creation, rename and empty-folder deletion use their own routes', async () => {
    const api = documentApi();
    const { page, errors } = await open(documentFixture(), api.handler, [documentSource]);
    await page.waitForSelector('[data-document-card="4"]');
    await page.locator('[data-document-action="new-folder"]').click();
    assert.equal(await page.locator('[name="name"]').getAttribute('maxlength'), '120');
    await page.locator('[name="name"]').fill('Contracts');
    await page.locator('[data-document-dialog-save]').click();
    await page.waitForSelector('[data-folder-id="2"]');
    await page.locator('[data-folder-id="1"]').click();
    await page.waitForSelector('[data-document-action="rename-folder"]', { state: 'visible' });
    await page.locator('[data-document-action="rename-folder"]').click();
    await page.locator('[name="name"]').fill('Technical files');
    await page.locator('[data-document-dialog-save]').click();
    await page.waitForFunction(() => document.querySelector('[data-folder-id="1"]').textContent === 'Technical files');
    assert(api.calls.some(call => call.path === '/folder-update/1' && call.method === 'PUT'));
    await page.locator('[data-folder-id="2"]').click();
    await page.locator('[data-document-action="delete-folder"]').click();
    await page.waitForFunction(() => !document.querySelector('[data-folder-id="2"]'));
    assert(api.calls.some(call => call.path === '/folder-delete/2' && call.method === 'DELETE'));
    assert.deepEqual(errors, []);
    await page.close();
});

const fixtureDirectory = path.resolve('storage/framework/testing/bidding-ui');
let actualFixturesAvailable = true;
try { await access(path.join(fixtureDirectory, 'project-bidding-edit.html')); } catch { actualFixturesAvailable = false; }

test('actual isolated Blade pages render responsive forms with no page overflow', { skip: !actualFixturesAvailable }, async () => {
    const artifacts = path.join(fixtureDirectory, 'browser');
    await mkdir(artifacts, { recursive: true });
    let css = '';
    try { const assets = await readdir('public/build/assets'); const name = assets.find(name => name.endsWith('.css')); if (name) css = await readFile(path.join('public/build/assets', name), 'utf8'); } catch {}
    for (const file of ['project-bidding-create.html', 'project-bidding-edit.html', 'project-bidding-show.html', 'bidding-create.html', 'bidding-edit.html', 'bidding-show.html']) {
        const { page, errors } = await open(await readFile(path.join(fixtureDirectory, file), 'utf8'), url => {
            if (url.pathname.includes('/documents')) return { folders: [], documents: [], permissions: { upload: true, update: true, delete: true }, limits: { extensions: ['pdf'], max_file_size_kb: 1024, max_files: 10 }, meta: { current_page: 1, last_page: 1, total: 0 } };
            return standardLookup(url);
        }, [source, documentSource, alpineSource]);
        if (css) await page.addStyleTag({ content: css });
        await page.setViewportSize({ width: 390, height: 844 });
        const menu = page.getByRole('button', { name: /Operations menu|Finance menu/ });
        if (css) {
            await menu.click();
            await page.getByRole('button', { name: 'Close navigation' }).waitFor({ state: 'visible' });
            await page.getByRole('button', { name: 'Close navigation' }).click();
        }
        await page.screenshot({ path: path.join(artifacts, file.replace('.html', '-mobile.png')), fullPage: true });
        if (css) {
            const overflow = await page.evaluate(() => ({ width: document.documentElement.scrollWidth, viewport: window.innerWidth, elements: [...document.querySelectorAll('body *')].filter(element => { const box = element.getBoundingClientRect(); return box.width && box.right > window.innerWidth + 1 && !element.closest('table'); }).slice(0, 10).map(element => ({ tag: element.tagName, classes: element.className, right: element.getBoundingClientRect().right })) }));
            assert(overflow.width <= overflow.viewport + 1, `${file} overflows the page: ${JSON.stringify(overflow)}`);
        }
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.screenshot({ path: path.join(artifacts, file.replace('.html', '-desktop.png')), fullPage: true });
        assert.deepEqual(errors, []);
        await page.close();
    }
});
