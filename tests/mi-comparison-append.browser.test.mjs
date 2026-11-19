import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from 'playwright';

for (const mode of ['create', 'edit']) {
    for (const width of [1440, 390]) {
        test(`manual and AI comparisons append, deduplicate and retain selections on ${mode} at ${width}px`, async (context) => {
            if (!process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX || !process.env.MI_COMPARISON_MANUAL_RESPONSE) {
                context.skip('Set comparison HTML fixtures and MI_COMPARISON_MANUAL_RESPONSE from the feature tests.');
                return;
            }
            const fragment = await readFile(`${process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX}-${mode}.html`, 'utf8');
            const manual = JSON.parse(await readFile(process.env.MI_COMPARISON_MANUAL_RESPONSE, 'utf8')).results[0];
            const manifest = JSON.parse(await readFile(new URL('../public/build/manifest.json', import.meta.url), 'utf8'));
            const css = await readFile(new URL(`../public/build/${Object.values(manifest).find(entry => entry.file.endsWith('.css')).file}`, import.meta.url), 'utf8');
            const browser = await chromium.launch({ headless: true });
            try {
                const page = await browser.newPage({ viewport: { width, height: 1000 } });
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const posts = [];
                let searchCount = 0;
                let finishSave;
                const small = { store_name: 'AI Store', product_name: 'AI Oak Table Small', price: 100, currency: 'USD', product_link: 'https://store.example.com/table?variant=small&utm_source=first', photo_url: 'https://images.example.com/small.jpg', match_reason: 'Comparable oak construction.' };
                const large = { ...small, product_name: 'AI Oak Table Large', price: 200, product_link: 'https://store.example.com/table?variant=large' };
                await page.route('**/*', async route => {
                    const request = route.request();
                    const url = request.url();
                    if (request.method() === 'POST') {
                        const body = url.endsWith('/search') ? null : request.postDataJSON();
                        posts.push({ url, body });
                        if (url.endsWith('/search')) {
                            searchCount++;
                            const results = searchCount === 1 ? [small] : (searchCount === 2 ? [{ ...small, price: 99, product_link: 'https://store.example.com/table?utm_campaign=again&variant=small' }, { ...manual, product_link: 'https://store.example.com/manual' }, large] : []);
                            await route.fulfill({ status: searchCount === 3 ? 502 : 200, contentType: 'application/json', body: JSON.stringify(searchCount === 3 ? { message: 'Provider billing failed.', setup_required: true, results: [] } : { results }) });
                        } else if (url.endsWith('/from-link')) {
                            if (body.url.includes('/incomplete')) await route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'Required product price is unavailable.', results: [] }) });
                            else await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ results: [{ ...manual, product_link: body.url, ...(body.url.includes('/no-image') ? { photo_url: null, canonical_url: body.url } : {}), ...(body.url.includes('/bad-image') ? { photo_url: 'https://images.example.com/bad.jpg', canonical_url: body.url } : {}) }] }) });
                        } else {
                            await new Promise(resolve => {
                                finishSave = async () => {
                                    await route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify({ message: 'Selected comparisons saved.' }) });
                                    resolve();
                                };
                            });
                        }
                    } else if (request.resourceType() === 'document') {
                        await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><style>${css}</style><style>body{margin:0;padding:16px}main{max-width:1100px;margin:auto}.tx-card-body,.tx-card-head{padding:16px}.tx-field{display:block;width:100%}main [hidden]{display:none!important}button{padding:8px}</style></head><body><main><form action="/mi/store" method="POST"><input id="item_name" value="Oak table"><textarea id="description">Solid oak table</textarea>${fragment}</form></main></body></html>` });
                    } else if (request.resourceType() === 'image') {
                        if (url === 'https://images.example.com/bad.jpg') { await route.abort(); return; }
                        await route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="240"><rect width="120" height="240" fill="#ab8866"/></svg>' });
                    } else await route.abort();
                });
                await page.goto(`https://tracker.metro-mobilia.com/mi/${mode === 'create' ? 'create' : '1/edit'}`);
                const cards = page.locator('#comparison_results article');
                const search = async () => {
                    await page.locator('#comparison_search').click();
                    await page.waitForFunction(() => !document.getElementById('comparison_search').disabled);
                };
                const add = async (url, enter = false) => {
                    await page.locator('#comparison_product_url').fill(url);
                    if (enter) await page.locator('#comparison_product_url').press('Enter');
                    else await page.locator('#comparison_add_link').click();
                    await page.waitForFunction(() => !document.getElementById('comparison_add_link').disabled);
                };
                assert.equal(posts.length, 0);
                await search();
                assert.equal(await cards.count(), 1);
                await cards.nth(0).locator('input[type=checkbox]').check();
                await add(manual.product_link, true);
                assert.equal(await cards.count(), 2);
                assert.ok(await cards.nth(0).locator('input').isChecked());
                assert.equal(await cards.nth(1).getByRole('link', { name: 'View product' }).getAttribute('href'), manual.product_link);
                assert.match(await cards.nth(1).textContent(), /Manual Oak Table[\s\S]*USD 240.00/);
                assert.match(await cards.nth(1).textContent(), /Added by product link/);
                const image = cards.nth(1).locator('img');
                assert.equal(await image.getAttribute('src'), manual.photo_url);
                await image.scrollIntoViewIfNeeded();
                await page.waitForFunction(url => [...document.querySelectorAll('#comparison_results img')].some(image => image.src === url && image.naturalWidth > 0), manual.photo_url);
                await cards.nth(1).locator('input').check();
                const beforeDuplicate = posts.length;
                await add('https://STORE.EXAMPLE.COM/manual?utm_medium=again#details');
                assert.equal(posts.length, beforeDuplicate);
                assert.match(await page.locator('#comparison_link_status').textContent(), /already added/);
                await search();
                assert.equal(await cards.count(), 3);
                assert.equal(await cards.locator('input:checked').count(), 2);
                assert.match(await cards.nth(0).textContent(), /USD 100.00/);
                assert.match(await page.locator('#comparison_status').textContent(), /already added/);
                await search();
                assert.ok(await page.locator('#comparison_setup_error').isVisible());
                assert.equal(await cards.count(), 3);
                assert.equal(await cards.locator('input:checked').count(), 2);
                await search();
                assert.match(await page.locator('#comparison_status').textContent(), /No matching/);
                assert.equal(await cards.count(), 3);
                await add('https://store.example.com/incomplete');
                assert.match(await page.locator('#comparison_link_status').textContent(), /price is unavailable/);
                assert.equal(await cards.count(), 3);
                const beforeUnsafe = posts.length;
                await add('http://127.0.0.1/private');
                assert.equal(posts.length, beforeUnsafe);
                await add('https://store.example.com/alias?utm_source=alias');
                assert.match(await page.locator('#comparison_link_status').textContent(), /already added/);
                assert.equal(await cards.count(), 3);
                await add('https://store.example.com/manual?variant=blue');
                assert.equal(await cards.count(), 4);
                assert.equal(await cards.locator('input:checked').count(), 2);
                assert.equal(posts.filter(post => !post.url.endsWith('/search') && !post.url.endsWith('/from-link')).length, 0);
                await add('https://store.example.com/no-image');
                assert.equal(await cards.count(), 4);
                assert.equal(await cards.locator('input:checked').count(), 2);
                assert.match(await page.locator('#comparison_link_status').textContent(), /no loadable image/);
                assert.ok(await page.locator('#comparison_manual_fields').isVisible());
                await add('https://store.example.com/bad-image');
                assert.equal(await cards.count(), 4);
                assert.equal(await cards.locator('input:checked').count(), 2);
                assert.match(await page.locator('#comparison_link_status').textContent(), /no loadable image/);
                await page.locator('#comparison_select_all').click();
                assert.equal(await cards.locator('input:checked').count(), 4);
                await page.locator('#comparison_clear_selection').click();
                assert.equal(await cards.locator('input:checked').count(), 0);
                assert.ok(await page.locator('#comparison_save').isDisabled());
                await cards.nth(1).locator('input').check();
                await cards.nth(2).locator('input').check();
                if (mode === 'create') assert.equal(await page.locator('#price_comparisons_json').inputValue(), '');
                await page.locator('#comparison_save').click();
                let selected;
                if (mode === 'create') {
                    selected = JSON.parse(await page.locator('#price_comparisons_json').inputValue());
                    assert.equal(posts.filter(post => !post.url.endsWith('/search') && !post.url.endsWith('/from-link')).length, 0);
                } else {
                    await page.waitForFunction(() => document.getElementById('comparison_status').textContent.includes('Saving selected'));
                    await cards.nth(0).locator('input').check();
                    assert.ok(await page.locator('#comparison_save').isDisabled());
                    await finishSave();
                    await page.waitForFunction(() => document.querySelectorAll('[data-comparison-saved]').length === 2);
                    selected = posts.at(-1).body.results;
                    assert.equal(await cards.nth(0).locator('[data-comparison-saved]').count(), 0);
                }
                assert.deepEqual(selected.map(result => result.product_link), [manual.product_link, large.product_link]);
                assert.equal(selected[0].photo_url, manual.photo_url);
                assert.equal(selected[0].canonical_url, undefined);
                assert.equal(await cards.count(), 4);
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
                assert.deepEqual(errors, []);
            } finally { await browser.close(); }
        });
    }
}

for (const mode of ['create', 'edit']) {
    for (const width of [1440, 390]) {
        test(`blocked listing fallback confirms before appending on ${mode} at ${width}px`, async (context) => {
            if (!process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX) { context.skip('Set rendered comparison fixtures.'); return; }
            const fragment = await readFile(`${process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX}-${mode}.html`, 'utf8');
            const manifest = JSON.parse(await readFile(new URL('../public/build/manifest.json', import.meta.url), 'utf8'));
            const css = await readFile(new URL(`../public/build/${Object.values(manifest).find(entry => entry.file.endsWith('.css')).file}`, import.meta.url), 'utf8');
            const browser = await chromium.launch({ headless: true });
            try {
                const page = await browser.newPage({ viewport: { width, height: 1000 } });
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const posts = [];
                const source = 'https://www.etsy.com/listing/123/table';
                const existing = { photo_url: 'https://images.example.com/existing.jpg', product_name: 'Existing AI table', store_name: 'Existing Store', price: 100, currency: 'USD', product_link: 'https://store.example.com/existing' };
                await page.route('**/*', async route => {
                    const request = route.request();
                    if (request.method() === 'POST') {
                        const body = request.url().endsWith('/search') ? null : request.postDataJSON();
                        posts.push({ url: request.url(), body });
                        if (request.url().endsWith('/search')) await route.fulfill({ json: { results: [existing] } });
                        else if (request.url().endsWith('/from-link')) await route.fulfill({ status: 422, json: { message: 'This store blocked automatic access. Enter the listing details to add it manually.', error_code: 'access_blocked', correlation_id: 'test-correlation', fallback: { product_link: body.url, product_name: 'Metadata table', photo_url: 'https://images.example.com/table.jpg' }, results: [] } });
                        else if (request.url().endsWith('/manual')) {
                            if (!body.store_name) await route.fulfill({ status: 422, json: { errors: { store_name: ['The store name field is required.'] } } });
                            else await route.fulfill({ json: { results: [{ ...body, price: Number(body.price), source: 'manual', image_status: 'available', price_evidence: 'Details confirmed manually.' }] } });
                        } else await route.fulfill({ json: { message: 'Saved' } });
                    } else if (request.resourceType() === 'image') await route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100"><rect width="200" height="100" fill="tan"/></svg>' });
                    else await route.fulfill({ contentType: 'text/html', body: `<html><head><style>${css}</style></head><body><form><input id="item_name" value="Oak Table">${fragment}</form></body></html>` });
                });
                await page.goto(`https://tracker.test/mi/${mode === 'create' ? 'create' : '1/edit'}`);
                const cards = page.locator('#comparison_results article');
                await page.locator('#comparison_search').click();
                await page.waitForFunction(() => !document.getElementById('comparison_search').disabled);
                await cards.nth(0).locator('input[type=checkbox]').check();
                await page.locator('#comparison_product_url').fill(source);
                await page.locator('#comparison_add_link').click();
                await page.waitForFunction(() => !document.getElementById('comparison_add_link').disabled);
                assert.match(await page.locator('#comparison_link_status').textContent(), /blocked automatic access/);
                assert.equal(await page.locator('#comparison_product_url').inputValue(), source);
                assert.equal(await page.locator('#comparison_manual_product_link').inputValue(), source);
                assert.equal(await page.locator('#comparison_manual_product_name').inputValue(), 'Metadata table');
                assert.equal(await page.locator('#comparison_manual_currency').inputValue(), '');
                assert.equal(await cards.count(), 1);
                assert.ok(await cards.nth(0).locator('input').isChecked());
                await page.locator('#comparison_confirm_manual').click();
                await page.waitForFunction(() => !document.getElementById('comparison_confirm_manual').disabled);
                assert.match(await page.locator('#comparison_link_status').textContent(), /store name field is required/);
                assert.equal(await page.locator('#comparison_manual_product_name').inputValue(), 'Metadata table');
                assert.equal(await cards.count(), 1);
                await page.locator('#comparison_manual_store_name').fill('User confirmed store');
                await page.locator('#comparison_manual_price').fill('240.50');
                await page.locator('#comparison_manual_currency').fill('USD');
                await page.locator('#comparison_manual_photo_url').fill('');
                await page.locator('#comparison_confirm_manual').click();
                await page.waitForFunction(() => !document.getElementById('comparison_confirm_manual').disabled);
                assert.equal(await cards.count(), 1);
                assert.ok(await cards.nth(0).locator('input').isChecked());
                assert.match(await page.locator('#comparison_link_status').textContent(), /no loadable image/);
                assert.ok(await page.locator('#comparison_manual_fields').isVisible());
                await page.locator('#comparison_manual_photo_url').fill('https://images.example.com/table.jpg');
                await page.locator('#comparison_confirm_manual').click();
                await page.waitForFunction(() => !document.getElementById('comparison_confirm_manual').disabled);
                assert.equal(await cards.count(), 2);
                assert.ok(await cards.nth(0).locator('input').isChecked());
                assert.match(await cards.nth(1).textContent(), /Metadata table[\s\S]*USD 240.50/);
                assert.equal(await cards.nth(1).locator('img').getAttribute('src'), 'https://images.example.com/table.jpg');
                assert.equal(await cards.locator('input:checked').count(), 1);
                assert.equal(posts.filter(post => !/\/(search|from-link|manual)$/.test(post.url)).length, 0);
                if (mode === 'create') assert.equal(await page.locator('#price_comparisons_json').inputValue(), '');
                await page.locator('#comparison_select_all').click();
                assert.equal(await cards.locator('input:checked').count(), 2);
                await page.locator('#comparison_clear_selection').click();
                assert.equal(await cards.locator('input:checked').count(), 0);
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
                assert.equal(overflow, false);
                assert.deepEqual(errors, []);
            } finally { await browser.close(); }
        });
    }
}
