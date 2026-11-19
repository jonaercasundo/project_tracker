import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from 'playwright';

for (const mode of ['create', 'edit']) {
    for (const width of [1440, 390]) {
        test(`comparison images and explicit selection work on ${mode} at ${width}px`, async (context) => {
            if (!process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX) {
                context.skip('Set MI_COMPARISON_IMAGE_FIXTURE_PREFIX to the rendered comparison fixtures.');
                return;
            }
            const fragment = await readFile(`${process.env.MI_COMPARISON_IMAGE_FIXTURE_PREFIX}-${mode}.html`, 'utf8');
            const manifest = JSON.parse(await readFile(new URL('../public/build/manifest.json', import.meta.url), 'utf8'));
            const cssFile = Object.values(manifest).find(entry => entry.file.endsWith('.css')).file;
            const css = await readFile(new URL(`../public/build/${cssFile}`, import.meta.url), 'utf8');
            const browser = await chromium.launch({ headless: true });
            try {
                const page = await browser.newPage({ viewport: { width, height: 1000 } });
                const errors = [];
                page.on('pageerror', error => errors.push(error.message));
                const imageUrl = 'https://images.example.com/product.jpg?token=abc&size=800';
                const normalized = process.env.MI_COMPARISON_STRUCTURED_RESPONSE ? JSON.parse(await readFile(process.env.MI_COMPARISON_STRUCTURED_RESPONSE, 'utf8')).results[0] : null;
                if (normalized) assert.equal(normalized.photo_url, imageUrl);
                const failedUrl = 'https://images.example.com/failed.jpg';
                const offer = { store_name: 'Market Store', product_name: 'Oak table', price: 120, currency: 'USD', product_link: 'https://store.example.com/table', photo_url: normalized?.photo_url ?? imageUrl, match_reason: 'Same oak material and dining function.', price_evidence: 'Product listing states USD 120.' };
                const offers = [offer, { ...offer, product_name: 'No photo offer', product_link: 'https://store.example.com/no-photo', photo_url: null }, { ...offer, product_name: 'Failed photo offer', product_link: 'https://store.example.com/failed', photo_url: failedUrl }, { ...offer, product_name: 'Unsafe photo offer', product_link: 'https://store.example.com/unsafe', photo_url: 'http://127.0.0.1/unsafe.jpg' }, { ...offer, product_name: 'Lookup failed offer', product_link: 'https://store.example.com/unavailable', photo_url: null, image_status: 'lookup_failed' }];
                const posts = [];
                let searchCount = 0;
                let releaseImage;
                let imageRequested;
                const waitingImage = new Promise(resolve => imageRequested = resolve);
                const unsafeRequests = [];
                await page.route('**/*', async route => {
                    const request = route.request();
                    if (request.url().includes('127.0.0.1')) {
                        unsafeRequests.push(request.url());
                        await route.abort();
                    } else if (request.method() === 'POST') {
                        posts.push({ url: request.url(), body: request.url().endsWith('/search') ? null : request.postDataJSON() });
                        if (request.url().endsWith('/search')) searchCount++;
                        const results = searchCount === 1 ? offers : (searchCount === 2 ? offers.slice(1) : [{ ...offer, product_name: 'Retried photo offer', product_link: 'https://store.example.com/failed' }]);
                        await route.fulfill({ status: request.url().endsWith('/search') ? 200 : 201, contentType: 'application/json', body: JSON.stringify({ results, message: 'Selected comparisons saved.' }) });
                    } else if (request.resourceType() === 'document') {
                        await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta name="csrf-token" content="test-token"><style>${css}</style><style>body{margin:0;padding:16px;background:#f8fafc}main{max-width:1100px;margin:auto}.tx-card-body{padding:16px}.tx-card-head{padding:16px}.tx-field{display:block;width:100%;max-width:100%}button{padding:8px}main [hidden]{display:none!important}</style></head><body><main class="tx-product-form"><input id="item_name" value="Oak table"><textarea id="description">Solid oak dining table</textarea>${fragment}</main></body></html>` });
                    } else if (request.resourceType() === 'image') {
                        if (request.url() === failedUrl) await route.abort();
                        else {
                            if (request.url() === imageUrl && searchCount === 1) { imageRequested(); await new Promise(resolve => releaseImage = resolve); }
                            await route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="320"><rect x="20" y="10" width="120" height="300" fill="#b08968"/></svg>' });
                        }
                    } else {
                        await route.abort();
                    }
                });
                await page.goto(`https://tracker.metro-mobilia.com/mi/${mode === 'create' ? 'create' : '1/edit'}`);
                if (mode === 'edit') {
                    assert.equal(await page.locator('#saved_comparisons article').count(), 3);
                    const unavailable = page.locator('#saved_comparisons article').nth(1);
                    assert.equal(await unavailable.locator('img').count(), 0);
                    assert.match(await unavailable.textContent(), /No product image available/);
                    const uploaded = page.locator('#saved_comparisons article').filter({ hasText: 'Saved offer 2' }).locator('img');
                    assert.match(await uploaded.getAttribute('src'), /comparisons\/uploaded.png/);
                    assert.equal(await uploaded.getAttribute('loading'), 'lazy');
                }
                await page.locator('#comparison_limit').fill('5');
                await page.locator('#comparison_search').click();
                await waitingImage;
                assert.equal(await page.locator('#comparison_results article').count(), 0);
                assert.ok(await page.locator('#comparison_save').isDisabled());
                releaseImage();
                await page.waitForFunction(() => !document.getElementById('comparison_search').disabled);
                const cards = page.locator('#comparison_results article');
                const good = cards.nth(0);
                await good.scrollIntoViewIfNeeded();
                await page.waitForFunction(() => {
                    const image = document.querySelector('#comparison_results article img');
                    return image?.naturalWidth > 0 && !image.classList.contains('is-loading');
                });
                assert.equal(await good.locator('img').getAttribute('src'), imageUrl);
                assert.equal(await good.locator('img').getAttribute('loading'), 'lazy');
                assert.equal(await good.locator('img').getAttribute('decoding'), 'async');
                assert.equal(await good.locator('img').evaluate(image => getComputedStyle(image).objectFit), 'contain');
                assert.match(await good.textContent(), /Market Store[\s\S]*Oak table[\s\S]*USD 120.00/);
                assert.match(await good.textContent(), /Why comparable: Same oak material/);
                assert.match(await good.textContent(), /Listing details: Product listing states USD 120/);
                assert.equal(await good.getByRole('link', { name: 'View product' }).getAttribute('href'), offer.product_link);
                assert.equal(await cards.count(), 1);
                assert.equal(await cards.locator('[data-image-status]').count(), 0);
                const heights = await cards.locator('[data-comparison-image]').evaluateAll(areas => areas.map(area => area.getBoundingClientRect().height));
                assert.deepEqual(heights, [220]);
                assert.equal(unsafeRequests.length, 0);
                assert.equal(posts.length, 1);
                assert.ok(await page.locator('#comparison_save').isDisabled());
                await page.locator('#comparison_select_all').click();
                assert.equal(await cards.locator('input:checked').count(), 1);
                await page.locator('#comparison_clear_selection').click();
                assert.equal(await cards.locator('input:checked').count(), 0);
                assert.ok(await page.locator('#comparison_save').isDisabled());
                await good.locator('input[type="checkbox"]').check();
                await page.locator('#comparison_search').click();
                await page.waitForFunction(() => !document.getElementById('comparison_search').disabled);
                assert.equal(await cards.count(), 1);
                assert.ok(await good.locator('input').isChecked());
                assert.match(await page.locator('#comparison_status').textContent(), /No matching products with available images were found/);
                await page.locator('#comparison_search').click();
                await page.waitForFunction(() => !document.getElementById('comparison_search').disabled);
                assert.equal(await cards.count(), 2);
                await cards.nth(1).locator('input').check();
                if (mode === 'create') assert.equal(await page.locator('#price_comparisons_json').inputValue(), '');
                if (process.env.MI_COMPARISON_IMAGE_SCREENSHOT_PREFIX) {
                    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
                    await page.screenshot({ path: `${process.env.MI_COMPARISON_IMAGE_SCREENSHOT_PREFIX}-${mode}-${width}.png`, fullPage: true });
                }
                await page.locator('#comparison_save').click();
                if (mode === 'create') {
                    const staged = JSON.parse(await page.locator('#price_comparisons_json').inputValue());
                    assert.deepEqual(staged.map(result => result.photo_url), [imageUrl, imageUrl]);
                    assert.equal(posts.length, 3);
                } else {
                    await page.waitForFunction(() => document.querySelectorAll('#comparison_results [data-comparison-saved]').length === 2);
                    assert.equal(await cards.count(), 2);
                    assert.equal(posts.length, 4);
                    assert.deepEqual(posts[3].body.results.map(result => result.photo_url), [imageUrl, imageUrl]);
                }
                await good.locator('img').evaluate(image => image.dispatchEvent(new Event('error')));
                assert.equal(await cards.count(), 1);
                if (mode === 'create') {
                    const staged = JSON.parse(await page.locator('#price_comparisons_json').inputValue());
                    assert.deepEqual(staged.map(result => result.product_link), ['https://store.example.com/failed']);
                } else assert.equal(await page.locator('#saved_comparisons article').count(), 3);
                await page.locator('#comparison_save').click();
                if (mode === 'create') {
                    assert.deepEqual(JSON.parse(await page.locator('#price_comparisons_json').inputValue()).map(result => result.product_link), ['https://store.example.com/failed']);
                } else {
                    await page.waitForFunction(() => !document.getElementById('comparison_save').disabled);
                    assert.deepEqual(posts.at(-1).body.results.map(result => result.product_link), ['https://store.example.com/failed']);
                }
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
                assert.deepEqual(errors, []);
            } finally {
                await browser.close();
            }
        });
    }
}
