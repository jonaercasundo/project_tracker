import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from 'playwright';

test('monitoring renders before reports, scopes cached results, ignores late responses and retries failures', async (context) => {
    if (!process.env.MONITORING_SHELL_HTML) {
        context.skip('Set MONITORING_SHELL_HTML to the rendered monitoring shell from the Laravel shell test.');
        return;
    }
    const shell = (await readFile(process.env.MONITORING_SHELL_HTML, 'utf8')).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
    const source = await readFile(new URL('../resources/js/delivery-monitoring.js', import.meta.url), 'utf8');
    const dropdown = await readFile(new URL('../resources/js/searchable-project-dropdown.js', import.meta.url), 'utf8');
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => route.fulfill({ contentType: 'text/html', body: shell }));
        await page.goto('http://monitoring.test/deliveries/monitoring?project_id=1');
        await page.addScriptTag({ content: dropdown.replaceAll('export function ', 'function ') });
        await page.addScriptTag({ path: new URL('../node_modules/alpinejs/dist/cdn.min.js', import.meta.url).pathname });
        await page.evaluate(() => {
            const root = document.getElementById('delivery-monitoring');
            root.dataset.endpoint = `${location.origin}/deliveries/monitoring`;
            root.dataset.optionsEndpoint = `${location.origin}/deliveries/monitoring/options`;
            root.dataset.locationsEndpoint = `${location.origin}/deliveries/monitoring/locations`;
            window.pendingReports = [];
            window.fetch = (url) => {
                const target = new URL(url);
                if (target.pathname.endsWith('/options')) return Promise.resolve(new Response(JSON.stringify({ projects: [{ project_id: 1, project_name: 'Science Kits' }, { project_id: 2, project_name: 'Furniture' }], regions: ['Region I'], years: [2026] }), { headers: { 'Content-Type': 'application/json' } }));
                if (target.pathname.endsWith('/locations')) return Promise.resolve(new Response('{"options":[]}', { headers: { 'Content-Type': 'application/json' } }));
                // Deliberately ignore abort to exercise the late-response guard.
                return new Promise(resolve => window.pendingReports.push({ url: target.href, resolve }));
            };
            window.resolveReport = (index, text, status = 200) => window.pendingReports[index].resolve(new Response(JSON.stringify({ summary_html: '', projects_html: `<p>${text}</p>`, summary: { projects_count: 1 } }), { status, headers: { 'Content-Type': 'application/json' } }));
        });
        await page.addScriptTag({ content: source });
        const submit = async (project, search = '') => page.evaluate(({ project, search }) => {
            const form = document.getElementById('monitoring-filters');
            form.elements.project_id.value = project;
            form.elements.search.value = search;
            form.requestSubmit();
        }, { project, search });
        await page.waitForFunction(() => window.pendingReports.length === 1);
        assert.equal(await page.locator('h1').textContent(), 'Project Delivery Monitoring');
        assert.match(await page.locator('#monitoring-loading-status').textContent(), /Loading/);
        await page.waitForFunction(() => document.getElementById('monitoring-options-status').textContent === 'Filter choices loaded.');
        await page.evaluate(() => window.resolveReport(0, 'Project one'));
        await page.waitForFunction(() => document.getElementById('monitoring-loading-status').textContent === 'Operational progress updated.');
        await submit('1');
        await page.waitForFunction(() => window.pendingReports.length === 2);
        await page.evaluate(() => window.resolveReport(1, 'Project one refreshed'));
        await page.waitForFunction(() => document.getElementById('monitoring-table').textContent.includes('Project one refreshed'));
        // Exact-filter cache is visible immediately while the next request is pending.
        await submit('1');
        assert.match(await page.locator('#monitoring-table').textContent(), /Project one refreshed/);
        assert.match(await page.locator('#monitoring-loading-status').textContent(), /Refreshing/);
        await submit('1');
        assert.equal(await page.evaluate(() => window.pendingReports.length), 3);
        await submit('2', 'Furniture');
        assert.doesNotMatch(await page.locator('#monitoring-table').textContent(), /Project one/);
        await page.evaluate(() => window.resolveReport(3, 'Project two'));
        await page.waitForFunction(() => document.getElementById('monitoring-table').textContent.includes('Project two'));
        await page.evaluate(() => window.resolveReport(2, 'Late project one'));
        assert.match(await page.locator('#monitoring-table').textContent(), /Project two/);
        await submit('2', 'Furniture');
        await page.evaluate(() => window.resolveReport(4, 'Failed', 503));
        await page.locator('#monitoring-retry').waitFor({ state: 'visible' });
        assert.match(await page.locator('#monitoring-loading-status').textContent(), /Refresh failed/);
        assert.match(await page.locator('#monitoring-table').textContent(), /Project two/);
        await page.locator('#monitoring-retry').click();
        await page.evaluate(() => window.resolveReport(5, 'Retry succeeded'));
        await page.waitForFunction(() => document.getElementById('monitoring-loading-status').textContent === 'Operational progress updated.');
        assert.equal(await page.locator('#monitoring-table').getAttribute('aria-busy'), null);
        await submit('2', 'Different search');
        await page.evaluate(() => window.resolveReport(6, 'Failed', 503));
        await page.locator('#monitoring-retry').waitFor({ state: 'visible' });
        assert.doesNotMatch(await page.locator('#monitoring-table').textContent(), /Retry succeeded|Project two/);
        assert.match(await page.locator('#monitoring-table').textContent(), /No progress is available/);
        await page.locator('#monitoring-retry').click();
        await page.evaluate(() => window.resolveReport(7, 'Search retry succeeded'));
        await page.waitForFunction(() => document.getElementById('monitoring-loading-status').textContent === 'Operational progress updated.');
        assert.equal(new URL(page.url()).searchParams.get('search'), 'Different search');
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
