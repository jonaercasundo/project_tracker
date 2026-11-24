import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { setTimeout as delay } from 'node:timers/promises';
import test from 'node:test';
import { chromium } from 'playwright';

test('project dashboard retains visited tabs and deduplicates pending loads', async () => {
    const blade = await readFile(new URL('../resources/views/projects/dashboard.blade.php', import.meta.url), 'utf8');
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1];
    const alpine = await readFile(new URL('../node_modules/alpinejs/dist/cdn.min.js', import.meta.url), 'utf8');
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const origin = 'http://dashboard.test';
        const url = (id, filter = '') => `${origin}/dashboard?dashboard_id=${id}${filter}`;
        const dashboard = (id, revision, filter = '') => `<main id="project-dashboard" style="min-height:3000px" x-data="{ option: 'bar' }">
            ${[1, 2, 3].map(tab => `<a data-dashboard-nav="tab" id="tab-${tab}" ${tab === id ? 'aria-current="page"' : ''} href="${url(tab, filter)}">Tab ${tab}</a>`).join(' ')}
            <a data-dashboard-nav="refresh" id="refresh" href="${url(id, filter)}">Refresh</a>
            <form data-dashboard-async action="/dashboard"><input type="hidden" name="dashboard_id" value="${id}"><input name="search"><button>Apply</button></form>
            <select x-model="option"><option>bar</option><option>pie</option></select>
            <output x-text="option"></output>
            <svg id="chart" aria-label="Chart ${id}, revision ${revision}"></svg>
            <div id="pages" style="height:30px;overflow:auto"><div style="height:300px">Page ${id}</div></div>
        </main>`;
        const requests = [];
        await page.route('**/dashboard*', async route => {
            if (route.request().isNavigationRequest()) {
                await route.fulfill({ contentType: 'text/html', body: `${dashboard(1, 1)}<script>${alpine}</script><script>${script}</script>` });
                return;
            }
            requests.push(route);
        });
        const respond = async (index, id, revision, filter) => {
            await requests[index].fulfill({ contentType: 'text/html', body: dashboard(id, revision, filter) });
        };
        const click = id => page.evaluate(id => document.getElementById(id).click(), id);
        const waitForRequests = async count => {
            for (let attempt = 0; requests.length < count && attempt < 100; attempt++) {
                await delay(20);
            }
            assert.equal(requests.length, count);
        };
        const waitForChart = async (id, revision) => {
            await page.waitForFunction(label => document.querySelector('#chart')?.getAttribute('aria-label') === label, `Chart ${id}, revision ${revision}`);
        };

        await page.goto(origin + '/dashboard');
        await page.locator('select').selectOption('pie');
        await page.locator('input[name=search]').fill('draft filter');
        await page.evaluate(() => {
            window.originalChart = document.querySelector('#chart');
            document.querySelector('#pages').scrollTop = 80;
            window.scrollTo(0, 450);
        });
        await click('tab-2');
        await click('tab-1');
        assert.equal(await page.locator('[data-dashboard-loading]').count(), 0);
        await click('tab-2');
        await click('tab-3');
        await click('tab-2');
        await waitForRequests(2);
        const secondTabRequest = requests.findIndex(route => route.request().url() === url(2));
        const thirdTabRequest = requests.findIndex(route => route.request().url() === url(3));
        await respond(secondTabRequest, 2, 1);
        await waitForChart(2, 1);
        await respond(thirdTabRequest, 3, 1);
        await page.waitForFunction(() => performance.getEntriesByName(document.getElementById('tab-3').href).length > 0);
        assert.equal(await page.locator('#chart').getAttribute('aria-label'), 'Chart 2, revision 1');

        await click('tab-3');
        await waitForChart(3, 1);
        await click('tab-1');
        await waitForChart(1, 1);
        assert.equal(await page.locator('output').textContent(), 'pie');
        assert.equal(await page.locator('input[name=search]').inputValue(), 'draft filter');
        assert.deepEqual(await page.evaluate(() => [
            document.querySelector('#chart') === window.originalChart,
            window.scrollY,
            document.querySelector('#pages').scrollTop,
        ]), [true, 450, 80]);
        assert.equal(await page.locator('[data-dashboard-loading]').count(), 0);
        await click('tab-2');
        await waitForChart(2, 1);
        assert.equal(requests.length, 2);

        await click('refresh');
        await click('refresh');
        await waitForRequests(3);
        await respond(2, 2, 2);
        await waitForChart(2, 2);
        await click('tab-1');
        await click('tab-2');
        await waitForChart(2, 2);
        assert.equal(requests.length, 3);
        await page.goBack();
        await waitForChart(1, 1);
        await page.goForward();
        await waitForChart(2, 2);
        assert.equal(requests.length, 3);

        await page.locator('input[name=search]').fill('applied');
        await page.locator('form button').click();
        await waitForRequests(4);
        await respond(3, 2, 3, '&search=applied');
        await waitForChart(2, 3);
        await click('tab-1');
        await waitForRequests(5);
        assert.equal(requests[4].request().url(), url(1, '&search=applied'));
        await respond(4, 1, 2, '&search=applied');
        await waitForChart(1, 2);
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
