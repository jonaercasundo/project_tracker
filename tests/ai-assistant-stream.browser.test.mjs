import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { chromium } from 'playwright';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));

test('Alpine displays a streamed assistant answer before the stream closes without reloading', async (context) => {
    let browser;
    try {
        browser = await chromium.launch({ headless: true });
    } catch (error) {
        if (String(error.message).includes('Executable doesn’t exist') || String(error.message).includes('Executable doesn\'t exist')) {
            context.skip('Playwright Chromium is not installed in this environment.');
            return;
        }
        throw error;
    }

    const page = await browser.newPage();
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));
    await page.addInitScript(() => { window.__pageMarker = 'same-page'; });
    await page.setContent(`
        <meta name="csrf-token" content="test">
        <main id="assistant" x-data="aiAssistant({
            workspace: true, company: 'MI', status: 'ready', context: {},
            routes: { conversations: '/assistant/conversations', create: '/assistant/conversations', show: '/assistant/conversations/__ID__', destroy: '/assistant/conversations/__ID__', stream: '/assistant/conversations/__ID__/messages' }
        })" x-init="conversationId = 42">
            <button id="send" @click="send('How much is ROUND SIDE TABLE (M)?')">Send</button>
            <div x-ref="scrollArea" style="height: 400px; overflow:auto">
                <template x-for="(message, index) in messages" :key="index">
                    <article :data-role="message.role">
                        <span x-show="loading && message.role === 'assistant' && !message.content" data-testid="thinking" x-text="thinkingLabel"></span>
                        <p data-testid="message" x-text="message.content"></p>
                    </article>
                </template>
            </div>
        </main>
    `);
    await page.evaluate(() => {
        window.__pageMarker = 'same-page';
        Object.defineProperty(window, 'sessionStorage', { configurable: true, value: { getItem: () => null, setItem: () => {}, removeItem: () => {} } });
    });

    const parserSource = (await readFile(resolve(root, 'resources/js/ai-sse.js'), 'utf8'))
        .replace('export class AiSseParser', 'class AiSseParser') + '\nwindow.AiSseParser = AiSseParser;';
    await page.addScriptTag({ content: `(function () { ${parserSource} })();` });
    const assistantSource = (await readFile(resolve(root, 'resources/js/ai-assistant.js'), 'utf8'))
        .replace(/^import .*;\r?\n/gm, '');
    await page.addScriptTag({ content: `const AiSseParser = window.AiSseParser; const renderAiMarkdown = (text) => text;\n${assistantSource}` });
    await page.evaluate(() => {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = async (url, options) => {
            if (String(url) === '/assistant/conversations') return new Response(JSON.stringify({ conversations: [] }), { headers: { 'Content-Type': 'application/json' } });
            if (String(url).endsWith('/messages')) {
                return new Response(new ReadableStream({ start(controller) { window.__streamController = controller; } }), { headers: { 'Content-Type': 'text/event-stream' } });
            }
            return nativeFetch(url, options);
        };
    });
    const alpinePath = resolve(root, 'node_modules/alpinejs/dist/cdn.min.js');
    await page.addScriptTag({ path: alpinePath });

    try {
        await page.locator('#send').click();
        const thinking = page.locator('[data-role="assistant"] [data-testid="thinking"]');
        await thinking.waitFor({ state: 'visible', timeout: 3000 });
        assert.equal(await thinking.textContent(), 'Connecting…');
        await page.evaluate(() => window.__streamController.enqueue(new TextEncoder().encode('event: status\ndata: {"label":"Thinking…"}\n\n')));
        await page.waitForFunction(() => document.querySelector('[data-role="assistant"] [data-testid="thinking"]')?.textContent === 'Thinking…');
        const firstChunk = 'event: delta\ndata: {"text":"ROUND SIDE TABLE price: "}\n\n';
        await page.evaluate((frame) => {
            const bytes = new TextEncoder().encode(frame);
            window.__streamController.enqueue(bytes.slice(0, 21));
            window.__streamController.enqueue(bytes.slice(21));
        }, firstChunk);
        await page.waitForFunction(() => document.querySelector('[data-role="assistant"] [data-testid="message"]')?.textContent.includes('ROUND SIDE TABLE price:'));
        assert.equal(await page.evaluate(() => window.__pageMarker), 'same-page');
        await page.evaluate(() => {
            window.__streamController.enqueue(new TextEncoder().encode('event: delta\ndata: {"text":" PHP 1,250"}\n\nevent: done\ndata: {"ok":true}\n\n'));
            window.__streamController.close();
        });
        await page.waitForFunction(() => document.querySelector('[data-role="assistant"] [data-testid="message"]')?.textContent === 'ROUND SIDE TABLE price:  PHP 1,250');
        await thinking.waitFor({ state: 'hidden' });
        assert.deepEqual(pageErrors, []);
    } finally {
        await browser.close();
    }
});
