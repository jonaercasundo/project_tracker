import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readFile, readdir, mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const source = await readFile('resources/js/bidding-schools.js', 'utf8');
const bidding = await readFile('resources/js/bidding.js', 'utf8');
const html = await readFile('storage/framework/testing/bidding-ui/project-bidding-edit.html', 'utf8');
const cssFile = (await readdir('public/build/assets')).find(f => f.endsWith('.css'));
const css = await readFile(`public/build/assets/${cssFile}`, 'utf8');

test('configures shared items, previews and confirms schools, and displays responsive totals', async () => {
    const browser = await chromium.launch({headless:true, executablePath:process.env.BIDDING_BROWSER_EXECUTABLE || 'C:/Users/RHM/AppData/Local/ms-playwright/chromium_headless_shell-1234/chrome-headless-shell-win64/chrome-headless-shell.exe'});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let packageKey;
        let confirmed = false;
        let receivedPlan;
        await page.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/fixture') return route.fulfill({contentType:'text/html',body:html});
            if (url.pathname.endsWith('/schools/workspace')) {
                const data = route.request().postDataJSON();
                let result = {token:'00000000-0000-4000-8000-000000000001',count:0};
                if (data.action === 'preview') {
                    receivedPlan = JSON.parse(data.school_plan);packageKey = data.package_key;
                    result = {...result,summary:{valid:2,rejected:0,added:2,removed:0,schools:2,assignments:2},errors:[]};
                }
                if (data.action === 'confirm') {confirmed=true;result.count=2;}
                if (data.action === 'list') result={rows:[{school_id:'134967',package_key:packageKey,school:{school_name:'Bacsil <script>unsafe</script>',region:'Region I',division:'Ilocos Norte',municipality:'Bacarra',address:'School Master address'}}],total:2,page:1,counts:{[packageKey]:2},schools:2};
                return route.fulfill({json:result});
            }
            if (url.pathname.endsWith('/catalog')) return route.fulfill({json:{items:[{id:1,item_name:'Microscope',description:'Microscope',unit:'pc',price:'10.50'}],has_more:false}});
            return route.fulfill({json:[]});
        });
        await page.goto('http://localhost/fixture');
        await page.addStyleTag({content:css});
        await page.addScriptTag({content:bidding});
        await page.addScriptTag({content:source});
        const action = name => page.locator(`[data-school-action="${name}"]`).first();
        await action('package').click();
        await page.locator('[data-edit="stage"]').fill('KS1');
        await page.locator('[data-edit="grade_alias"]').fill('G1toG3');
        await page.locator('[data-edit="code"]').fill('C1');
        await page.locator('[data-edit="quantity_basis"]').selectOption('per_school');
        await action('add-item').click();
        await page.locator('[data-item-search]').fill('Microscope');
        await action('catalog').click();
        await page.locator('select[data-edit="catalog_item_id"]').selectOption('1');
        await page.locator('[data-edit="quantity"]').fill('2');
        for(const width of [390,1440]) {
            await page.setViewportSize({width,height:900});
            await page.waitForTimeout(100);
            const overflow=await page.locator('[data-school-dialog]').evaluate(el=>el.scrollWidth>el.clientWidth+1);
            assert.equal(overflow,false,`dialog overflows at ${width}`);
            await mkdir('storage/framework/testing/bidding-ui/screenshots',{recursive:true});
            await page.screenshot({path:`storage/framework/testing/bidding-ui/screenshots/schools-package-${width}.png`});
        }
        await action('save-package').click();
        assert.equal(await page.locator('[data-school-plan]').isDisabled(),false);
        await action('bulk').click();
        await page.locator('[data-package-select]').selectOption({index:1});
        await page.locator('[data-school-ids]').fill('134967\n134969');
        await action('preview-ids').click();
        assert.equal(confirmed,false);
        await action('confirm').click();
        await page.waitForSelector('[data-assignment-search]');
        assert.equal(confirmed,true);
        assert.equal(receivedPlan.packages.length,1);
        assert.equal(await page.locator('[data-school-dialog-content] script').count(),0);
        assert((await page.locator('[data-school-dialog-content]').textContent()).includes('<script>unsafe</script>'));
        await action('close').click();
        assert.equal(await page.locator('[data-bidding-calculated-total]').textContent(),'67.50');
        assert.deepEqual(errors,[]);
    } finally {await browser.close();}
});
