import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from 'playwright';

for (const width of [1440, 390]) {
    test(`provider tabs show saved settings and isolate tests and saves at ${width}px`, async (context) => {
        if (!process.env.AI_CONFIGURATION_SHELL_HTML) {
            context.skip('Set AI_CONFIGURATION_SHELL_HTML to the rendered AI Configuration test page.');
            return;
        }
        const html = (await readFile(process.env.AI_CONFIGURATION_SHELL_HTML, 'utf8')).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
        const source = await readFile(new URL('../resources/js/ai-configuration.js', import.meta.url), 'utf8');
        const manifest = JSON.parse(await readFile(new URL('../public/build/manifest.json', import.meta.url), 'utf8'));
        const stylesheet = Object.values(manifest).find(entry => entry.file.endsWith('.css')).file;
        const css = await readFile(new URL(`../public/build/${stylesheet}`, import.meta.url), 'utf8');
        const browser = await chromium.launch({ headless: true });
        try {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            const requests = [];
            await page.route('**/*', async route => {
                if (route.request().method() === 'POST') {
                    requests.push(route.request().postDataJSON());
                    await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ status: 'success', message: route.request().url().endsWith('/test-connection') ? 'Web search tested.' : 'AI configuration saved.', configured_by: 'Settings administrator', updated_at: '2026-11-13T08:00:00Z' }) });
                } else if (route.request().resourceType() === 'document') {
                    await route.fulfill({ contentType: 'text/html', body: html });
                } else {
                    await route.abort();
                }
            });
            await page.goto('https://tracker.metro-mobilia.com/admin/settings/ai');
            await page.evaluate(() => document.querySelector('[data-ai-assistant]')?.remove());
            await page.addStyleTag({ content: css });
            await page.addStyleTag({ content: '[x-cloak] { display: none !important; }' });
            await page.addScriptTag({ content: source });
            await page.addScriptTag({ path: new URL('../node_modules/alpinejs/dist/cdn.min.js', import.meta.url).pathname });
            await page.waitForFunction(() => document.getElementById('research-provider').value === 'anthropic');
            await page.locator('#ai-panel-overview [data-provider="anthropic"] button').waitFor();
            assert.ok(await page.locator('#ai-panel-overview').isVisible());
            assert.match(await page.locator('#ai-panel-overview').textContent(), /claude-sonnet-5-5/);
            assert.equal(await page.locator('#ai-panel-overview').textContent().then(text => text.includes('sk-ant-secret')), false);
            assert.equal(requests.length, 0);
            await page.getByRole('tab', { name: 'Configured AI', exact: true }).focus();
            await page.keyboard.press('ArrowRight');
            await page.waitForFunction(() => document.getElementById('ai-tab-openai').getAttribute('aria-selected') === 'true');
            await page.locator('#openai-api-key').fill('sk-openai-test-only');
            await page.getByRole('tab', { name: 'Claude', exact: true }).click();
            await page.locator('#anthropic-api-key').waitFor({ state: 'visible' });
            assert.ok(await page.locator('#anthropic-api-key').isVisible());
            await page.locator('#anthropic-api-key').fill('sk-claude-test-only');
            await page.locator('#anthropic-model').selectOption('claude-haiku-5-5');
            await page.getByRole('tab', { name: 'Research & limits', exact: true }).click();
            await page.locator('#research-provider').selectOption('openai');
            await page.locator('#anthropic-api-key').waitFor({ state: 'hidden' });
            assert.equal(await page.locator('#anthropic-api-key').isVisible(), false);
            await page.getByRole('button', { name: 'Test research web search', exact: true }).click();
            await page.waitForFunction(() => !document.querySelector('[x-data^="aiConfiguration"]')._x_dataStack[0].testing);
            assert.equal(requests[0].provider, 'openai');
            assert.equal(requests[0].openai_api_key, 'sk-openai-test-only');
            assert.equal(requests[0].anthropic_api_key, undefined);
            await page.locator('#research-provider').selectOption('anthropic');
            await page.getByRole('tab', { name: 'Claude', exact: true }).click();
            await page.locator('#anthropic-api-key').waitFor({ state: 'visible' });
            await page.locator('#anthropic-model').selectOption('claude-sonnet-5-5');
            await page.getByRole('tab', { name: 'Research & limits', exact: true }).click();
            await page.getByRole('button', { name: 'Test research web search', exact: true }).click();
            await page.waitForFunction(() => !document.querySelector('[x-data^="aiConfiguration"]')._x_dataStack[0].testing);
            assert.equal(requests[1].provider, 'anthropic');
            assert.equal(requests[1].model, 'claude-sonnet-5-5');
            assert.equal(requests[1].web_search, true);
            assert.equal(requests[1].anthropic_api_key, 'sk-claude-test-only');
            assert.equal(requests[1].openai_api_key, undefined);
            assert.equal(requests.length, 2);

            await page.getByRole('tab', { name: 'Claude', exact: true }).click();
            await page.locator('#anthropic-model').selectOption('claude-haiku-5-5');
            await page.getByRole('tab', { name: 'OpenAI', exact: true }).click();
            await page.locator('#ai-model').selectOption('gpt-5.4');
            page.once('dialog', dialog => dialog.accept());
            await page.getByRole('button', { name: 'Save OpenAI configuration', exact: true }).click();
            await page.waitForFunction(() => !document.querySelector('[x-data^="aiConfiguration"]')._x_dataStack[0].saving);
            assert.equal(requests[2].openai_api_key, 'sk-openai-test-only');
            assert.equal(requests[2].model, 'gpt-5.4');
            assert.equal(requests[2].anthropic_api_key, undefined);
            assert.equal(requests[2].anthropic_model, undefined);
            assert.equal(requests[2].research_provider, undefined);
            assert.equal(await page.locator('#anthropic-api-key').inputValue(), 'sk-claude-test-only');
            assert.equal(await page.locator('#anthropic-model').inputValue(), 'claude-haiku-5-5');
            await page.getByRole('tab', { name: 'Configured AI', exact: true }).click();
            assert.match(await page.locator('[data-provider="openai"]').textContent(), /gpt-5.4/);
            assert.match(await page.locator('[data-provider="anthropic"]').textContent(), /claude-sonnet-5-5/);
            await page.getByRole('button', { name: 'Edit Claude configuration', exact: true }).click();
            page.once('dialog', dialog => dialog.accept());
            await page.getByRole('button', { name: 'Save Claude configuration', exact: true }).click();
            await page.waitForFunction(() => !document.querySelector('[x-data^="aiConfiguration"]')._x_dataStack[0].saving);
            assert.equal(requests[3].anthropic_api_key, 'sk-claude-test-only');
            assert.equal(requests[3].anthropic_model, 'claude-haiku-5-5');
            assert.equal(requests[3].openai_api_key, undefined);
            assert.equal(requests[3].model, 'gpt-5.4');
            await page.getByRole('tab', { name: 'Claude', exact: true }).focus();
            await page.keyboard.press('End');
            await page.locator('#research-provider').waitFor({ state: 'visible' });
            await page.locator('#research-provider').selectOption('openai');
            await page.getByRole('button', { name: 'Save research & limits', exact: true }).click();
            await page.waitForFunction(() => !document.querySelector('[x-data^="aiConfiguration"]')._x_dataStack[0].saving);
            assert.equal(requests[4].research_provider, 'openai');
            assert.equal(requests[4].anthropic_api_key, undefined);
            assert.equal(requests[4].openai_api_key, undefined);
            await page.getByRole('tab', { name: 'Research & limits', exact: true }).focus();
            await page.keyboard.press('Home');
            await page.locator('#ai-panel-overview').waitFor({ state: 'visible' });
            assert.match(await page.locator('[data-provider="anthropic"]').textContent(), /claude-haiku-5-5/);
            assert.equal(requests.length, 5);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
            if (process.env.AI_CONFIGURATION_SCREENSHOT_PREFIX) {
                await page.screenshot({ path: `${process.env.AI_CONFIGURATION_SCREENSHOT_PREFIX}-${width}.png`, fullPage: true });
            }
        } finally {
            await browser.close();
        }
    });
}
