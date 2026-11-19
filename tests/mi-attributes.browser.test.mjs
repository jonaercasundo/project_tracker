import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from 'playwright';

for (const mode of ['create', 'edit']) {
    for (const width of [1440, 390]) {
        test(`attribute tags search, create, clear and isolate rows on ${mode} at ${width}px`, async (context) => {
            if (!process.env.MI_ATTRIBUTE_FIXTURE_PREFIX) {
                context.skip('Export rendered fixtures using MI_ATTRIBUTE_FIXTURE_PREFIX in MIProductEditTest.');
                return;
            }
            const html = await readFile(`${process.env.MI_ATTRIBUTE_FIXTURE_PREFIX}-${mode}.html`, 'utf8');
            const tomScript = await readFile('/tmp/tx-tom-select.js', 'utf8');
            const tomStyles = await readFile('/tmp/tx-tom-select.css', 'utf8');
            const manifest = JSON.parse(await readFile(new URL('../public/build/manifest.json', import.meta.url), 'utf8'));
            const cssAsset = Object.values(manifest).find((entry) => entry.file.endsWith('.css')).file;
            const appStyles = await readFile(new URL(`../public/build/${cssAsset}`, import.meta.url), 'utf8');
            const browser = await chromium.launch({ headless: true });
            try {
                const page = await browser.newPage({ viewport: { width, height: 900 } });
                await page.route('**/*', async (route) => {
                    if (route.request().resourceType() === 'document') await route.fulfill({ contentType: 'text/html', body: html });
                    else if (route.request().url().includes('tom-select') && route.request().resourceType() === 'script') await route.fulfill({ contentType: 'application/javascript', body: tomScript });
                    else if (route.request().url().includes('tom-select') && route.request().resourceType() === 'stylesheet') await route.fulfill({ contentType: 'text/css', body: tomStyles });
                    else if (route.request().resourceType() === 'stylesheet') await route.fulfill({ contentType: 'text/css', body: appStyles });
                    else await route.abort();
                });
                await page.goto(`https://mi.test/${mode}`);
                await page.waitForFunction(() => document.getElementById('materials')?.tomselect);
                const values = (id) => page.evaluate((id) => document.getElementById(id).tomselect.items, id);
                const wrapper = (id) => page.locator(`#${id}`).locator('..').locator('.ts-wrapper');
                const input = (id) => wrapper(id).locator('input');
                const type = async (id, text) => {
                    await page.evaluate((id) => document.getElementById(id).tomselect.focus(), id);
                    await page.waitForFunction((id) => document.getElementById(id).tomselect.isFocused, id);
                    await input(id).fill(text);
                };
                assert.equal(await page.locator('select.tomselected:visible').count(), 0);
                assert.equal(await page.locator('.tx-attribute-dropdown:visible').count(), 0);
                await type('materials', 'Oak Wood');
                await page.locator('.tx-attribute-dropdown:visible .option[data-value="Oak Wood"]').click();
                assert.ok((await values('materials')).includes('Oak Wood'));
                await type('materials', 'Browser Composite');
                await page.locator('.tx-attribute-dropdown:visible .create').waitFor();
                await input('materials').press('Enter');
                await page.waitForFunction(() => document.getElementById('materials').tomselect.items.includes('Browser Composite'));
                assert.ok((await values('materials')).includes('Browser Composite'));
                await wrapper('materials').locator('.item[data-value="Browser Composite"] .remove').click();
                assert.ok(!(await values('materials')).includes('Browser Composite'));
                await page.locator('#materials').locator('..').locator('.tx-multi-clear').click();
                assert.deepEqual(await values('materials'), []);
                assert.equal(await page.locator('.tx-attribute-dropdown:visible').count(), 0);
                await type('color', 'Browser Color');
                await page.locator('.tx-attribute-dropdown:visible .create').click();
                assert.ok((await values('color')).includes('Browser Color'));
                assert.deepEqual(await values('materials'), []);

                if (mode === 'create') {
                    await page.locator('#add_product_variant').click();
                    await page.locator('#add_product_variant').click();
                }
                assert.deepEqual(await values('variant_1_materials'), mode === 'edit' ? ['Steel'] : []);
                await type('variant_0_materials', 'Variant Composite');
                await page.locator('.tx-attribute-dropdown:visible .create').waitFor();
                await input('variant_0_materials').press('Enter');
                await page.waitForFunction(() => document.getElementById('variant_0_materials').tomselect.items.includes('Variant Composite'));
                assert.ok((await values('variant_0_materials')).includes('Variant Composite'));
                assert.deepEqual(await values('variant_1_materials'), mode === 'edit' ? ['Steel'] : []);
                assert.deepEqual(await values('variant_0_colors'), mode === 'edit' ? ['Blue'] : []);
                await type('variant_0_materials', 'variant composite');
                assert.equal(await page.locator('.tx-attribute-dropdown:visible .create').count(), 0);
                await input('variant_0_materials').press('Escape');
                await page.locator('#variant_0_materials').locator('..').locator('.tx-multi-clear').click();
                assert.deepEqual(await values('variant_0_materials'), []);
                assert.deepEqual(await values('variant_1_materials'), mode === 'edit' ? ['Steel'] : []);
                await input('variant_1_colors').click();
                await page.locator('.tx-attribute-dropdown:visible').waitFor();
                const bounds = await page.locator('.tx-attribute-dropdown:visible').boundingBox();
                assert.ok(bounds);
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
                await input('variant_1_colors').press('Escape');
                assert.equal(await page.locator('.tx-attribute-dropdown:visible').count(), 0);
                await page.locator('#product_variants').screenshot({ path: `/tmp/mi-attributes-${mode}-${width}.png` });
            } finally {
                await browser.close();
            }
        });
    }
}
