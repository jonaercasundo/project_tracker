import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { chromium } from 'playwright';

const source = await readFile(new URL('../resources/js/project-details.js', import.meta.url), 'utf8');
const shell = `<!doctype html><html><body>
<span data-project-count="schools">120</span>
${['schools', 'items', 'packages', 'lots', 'keystage'].map((section) => {
    const plural = section === 'keystage' ? 'keystages' : section;
    const body = section === 'schools' ? 'schoolsTableBody' : section === 'keystage' ? 'keystageTableBody' : `${section}TableBody`;
    return `<section id="${section}" class="hidden" data-project-detail="${section}" data-lazy="true" data-endpoint="/projects/1/${plural}-data" data-options-endpoint="/projects/1/detail-options">
    <table><tbody id="${body}"></tbody></table><footer><span id="${plural}ShowingStart"></span><span id="${plural}ShowingEnd"></span><span id="${plural}Total"></span><div id="${plural}PaginationButtons"></div></footer></section>`;
}).join('')}
<input id="schoolSearch"><select id="schoolRegion"><option value="">All Regions</option></select>
<select id="schoolDivision"><option value="">All Divisions</option></select><select id="schoolMunicipality"><option value="">All Municipalities</option></select>
<button id="applySchoolFilters">Apply</button><button id="clearSchoolFilters">Clear</button>
<span id="schoolResultCount"></span><span id="schoolTableFooterCount"></span><span id="schoolActiveFilterBadge"></span>
<input id="itemSearch"><select id="itemTypeFilter"><option value="">All Types</option></select>
<input id="packageSearch"><select id="packageTypeFilter"><option value="">All Types</option></select>
<select id="packageLotFilter"><option value="">All Lots</option></select><select id="packageKeystageFilter"><option value="">All Keystages</option></select>
<input id="keystageSearch"><select id="keystageLotFilter"><option value="">All Lots</option></select>
</body></html>`;

async function harness(run, handler) {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH });
    try {
        const page = await browser.newPage();
        const requests = [];
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.route('http://project-details.test/**', async (route) => {
            const url = new URL(route.request().url());
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: shell });
            requests.push(url);
            if (handler && await handler(route, url)) return;
            if (url.pathname.endsWith('detail-options')) {
                const level = url.searchParams.get('level');
                const options = level === 'region' ? ['North', 'South'] : level === 'division' ? ['A'] : level === 'municipality' ? ['Town'] : [];
                return route.fulfill({ json: { options } });
            }
            const currentPage = Number(url.searchParams.get('page'));
            const perPage = Number(url.searchParams.get('per_page'));
            const from = (currentPage - 1) * perPage + 1;
            const to = Math.min(currentPage * perPage, 120);
            const html = Array.from({ length: to - from + 1 }, (_, index) => `<tr><td>Record ${from + index}</td></tr>`).join('');
            return route.fulfill({ json: { html, current_page: currentPage, last_page: Math.ceil(120 / perPage), per_page: perPage, total: 120, total_count: 120, from, to } });
        });
        await page.goto('http://project-details.test/');
        await page.addScriptTag({ content: source });
        const open = async (section) => {
            await page.evaluate((name) => {
                document.querySelectorAll('[data-project-detail]').forEach((element) => element.classList.toggle('hidden', element.id !== name));
                window.loadProjectDetails(name);
            }, section);
            await page.locator(`#${section} tbody tr`).first().waitFor();
            await page.waitForFunction((name) => !document.getElementById(name).hasAttribute('aria-busy'), section);
        };
        await run({ page, requests, open });
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
}

test('loads only opened tabs and requests server pages and page sizes', async () => {
    await harness(async ({ page, requests, open }) => {
        assert.equal(requests.length, 0);
        await open('schools');
        assert.equal(await page.locator('#schools tbody tr').count(), 25);
        assert.equal(requests.filter((url) => /items-data|packages-data/.test(url.pathname)).length, 0);
        await page.locator('#schoolsPaginationButtons').getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByText('Record 26', { exact: true }).waitFor();
        assert.equal(requests.filter((url) => url.pathname.endsWith('schools-data')).at(-1).searchParams.get('page'), '2');
        await page.getByLabel('Rows per page for schools').selectOption('100');
        await page.getByText('Record 100', { exact: true }).waitFor();
        assert.equal(await page.locator('#schools tbody tr').count(), 100);
        await open('items');
        await open('packages');
        await open('lots');
        await open('keystage');
        for (const section of ['items', 'packages', 'lots', 'keystage']) assert.equal(await page.locator(`#${section} tbody tr`).count(), 25);
    });
});

test('sends dependent filters, debounced searches, clear and refresh requests', async () => {
    await harness(async ({ page, requests, open }) => {
        await open('schools');
        await page.locator('#schoolRegion').selectOption('North');
        await page.waitForFunction(() => !document.getElementById('schools').hasAttribute('aria-busy'));
        await page.locator('#schoolDivision').selectOption('A');
        await page.locator('#schoolSearch').fill('Central');
        await page.waitForFunction(() => document.getElementById('schoolActiveFilterBadge').textContent === '3 active filters');
        const query = requests.filter((url) => url.pathname.endsWith('schools-data')).at(-1).searchParams;
        assert.equal(query.get('search'), 'Central');
        assert.equal(query.get('region'), 'North');
        assert.equal(query.get('division'), 'A');
        await page.locator('#clearSchoolFilters').click();
        await page.waitForFunction(() => document.getElementById('schoolActiveFilterBadge').textContent === '0 active filters');
        assert.equal(requests.filter((url) => url.pathname.endsWith('schools-data')).at(-1).searchParams.get('search'), '');
        const previousCount = requests.length;
        await page.evaluate(() => window.refreshProjectDetails('schools'));
        await page.waitForFunction(() => !document.getElementById('schools').hasAttribute('aria-busy'));
        assert.ok(requests.length > previousCount);
    });
});

test('shows retry after a failed request and recovers without reloading the page', async () => {
    let fail = true;
    await harness(async ({ page, open }) => {
        await open('schools');
        await page.getByRole('button', { name: 'Retry', exact: true }).click();
        await page.getByText('Record 1', { exact: true }).waitFor();
        assert.equal(await page.locator('#schools tbody tr').count(), 25);
    }, async (route, url) => {
        if (fail && url.pathname.endsWith('schools-data')) {
            fail = false;
            await route.fulfill({ status: 500, json: {} });
            return true;
        }
        return false;
    });
});
