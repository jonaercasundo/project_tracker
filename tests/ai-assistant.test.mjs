import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';
import { AiSseParser } from '../resources/js/ai-sse.js';

const source = (await readFile(new URL('../resources/js/ai-assistant.js', import.meta.url), 'utf8')).replace(/^import .*;\r?\n/gm, '');
const viewSource = await readFile(new URL('../resources/views/components/ai-assistant.blade.php', import.meta.url), 'utf8');

function createAssistant(settings, { fetch = async () => ({ ok: true, json: async () => ({}) }), storage = new Map() } = {}) {
    const calls = [];
    const context = {
        window: {},
        fetch: async (...args) => {
            calls.push(args);
            return fetch(...args);
        },
        sessionStorage: {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, String(value)),
            removeItem: (key) => storage.delete(key),
        },
        TextDecoder,
        TextEncoder,
        AbortController,
        setTimeout,
        clearTimeout,
        AiSseParser,
        renderAiMarkdown: (content) => content,
        document: { querySelector: () => null },
    };

    vm.runInNewContext(source, context);

    const assistant = context.window.aiAssistant(settings);
    assistant.$nextTick = (callback) => callback();
    assistant.$refs = {};

    return { assistant, calls, storage };
}

function settings(overrides = {}) {
    return {
        workspace: false,
        company: 'MI',
        status: 'ready',
        attachmentAllowed: true,
        liquidationAllowed: true,
        routes: {
            conversations: '/assistant/conversations',
            create: '/assistant/conversations',
            show: '/assistant/conversations/__ID__',
            destroy: '/assistant/conversations/__ID__',
            stream: '/assistant/conversations/__ID__/messages',
        },
        ...overrides,
    };
}

test('launcher opens and closes the shared assistant panel', () => {
    const { assistant } = createAssistant(settings());

    assistant.toggle();
    assert.equal(assistant.open, true);

    assistant.close();
    assert.equal(assistant.open, false);
});

test('unavailable service state is shown without calling assistant endpoints', async () => {
    const { assistant, calls } = createAssistant(settings({ status: 'disabled' }));

    await assistant.init();

    assert.equal(assistant.statusMessage(), 'The AI Assistant is disabled by an administrator.');
    assert.deepEqual(calls, []);
});

test('restores the active conversation after navigation using the existing session identifier', async () => {
    const storage = new Map([['assistant:MI', '42']]);
    const { assistant, calls } = createAssistant(settings(), {
        storage,
        fetch: async (url) => {
            if (url === '/assistant/conversations') {
                return { ok: true, json: async () => ({ conversations: [{ id: 42, title: 'Product questions' }] }) };
            }

            return {
                ok: true,
                json: async () => ({ conversation: { id: 42, messages: [{ role: 'user', content: 'How do I add a product?' }] } }),
            };
        },
    });

    await assistant.init();

    assert.equal(calls.length, 2);
    assert.equal(calls[1][0], '/assistant/conversations/42');
    assert.equal(assistant.conversationId, 42);
    assert.equal(assistant.messages[0].content, 'How do I add a product?');
});

test('sends a prompt in the selected conversation and renders streamed text', async () => {
    const { assistant, calls } = createAssistant(settings(), {
        fetch: async (url) => {
            if (url.endsWith('/messages')) {
                let read = false;
                const chunk = new TextEncoder().encode(
                    'event: delta\ndata: {"text":"Hello from AI"}\n\nevent: done\ndata: {"ok":true}\n\n',
                );

                return {
                    ok: true,
                    body: {
                        getReader: () => ({
                            read: async () => {
                                if (read) return { done: true };
                                read = true;
                                return { done: false, value: chunk };
                            },
                        }),
                    },
                };
            }

            return { ok: true, json: async () => ({ conversations: [{ id: 42, title: 'Hello' }] }) };
        },
    });
    assistant.conversationId = 42;
    assistant.draft = 'Hello';

    await assistant.send('Hello');

    assert.equal(calls[0][0], '/assistant/conversations/42/messages');
    assert.equal(assistant.messages[0].content, 'Hello');
    assert.equal(assistant.messages[1].content, 'Hello from AI');
    assert.equal(assistant.draft, '');
    assert.equal(assistant.loading, false);
});

test('keeps the message draft and attachment when the server rejects the send before accepting it', async () => {
    const { assistant } = createAssistant(settings(), {
        fetch: async (url) => url.endsWith('/messages')
            ? { ok: false, status: 403, json: async () => ({ message: 'Permission required.' }) }
            : { ok: true, json: async () => ({ conversations: [] }) },
    });
    assistant.conversationId = 42;
    assistant.draft = 'Read this attachment';
    assistant.activeAttachment = { id: 19, filename: 'notes.txt' };

    await assistant.send('Read this attachment');

    assert.equal(assistant.draft, 'Read this attachment');
    assert.equal(assistant.activeAttachment.id, 19);
    assert.equal(assistant.messages.length, 0);
    assert.match(assistant.error, /do not have access/i);
});

test('parses SSE frames when event fields and separators are split across chunks', () => {
    const parser = new AiSseParser();
    assert.deepEqual(parser.push('event: sta'), []);
    assert.deepEqual(parser.push('tus\r\ndata: {"label":"Thinking"}\r\n\r'), []);
    assert.deepEqual(parser.push('\nevent: delta\ndata: {"text":"Hi"}'), [
        { event: 'status', data: '{"label":"Thinking"}' },
    ]);
    assert.deepEqual(parser.finish(), [{ event: 'delta', data: '{"text":"Hi"}' }]);
});

test('shows a thinking state, updates the same message across chunks, and completes cleanly', async () => {
    const frameChunks = [
        'event: status\r\ndata: {"label":"Thinking…"}\r\n\r\n',
        'event: delta\r\ndata: {"text":"First "}\r\n\r\n',
        'event: delta\r\ndata: {"text":"answer"}\r\n\r\n',
        'event: done\r\ndata: {"ok":true}\r\n\r\n',
    ].map((frame) => new TextEncoder().encode(frame));
    const { assistant } = createAssistant(settings(), {
        fetch: async (url) => url.endsWith('/messages')
            ? { ok: true, body: { getReader: () => ({ read: async () => frameChunks.length ? { done: false, value: frameChunks.shift() } : { done: true } }) } }
            : { ok: true, json: async () => ({ conversations: [] }) },
    });
    assistant.conversationId = 42;

    const response = assistant.send('Question');
    assert.equal(assistant.loading, true);
    assert.equal(assistant.thinkingLabel, 'Connecting…');
    await response;

    assert.equal(assistant.messages.length, 2);
    assert.equal(assistant.messages[1].content, 'First answer');
    assert.equal(assistant.messages[1].complete, true);
    assert.equal(assistant.loading, false);
});

test('retries a failed stream without duplicating the user or assistant message', async () => {
    let attempt = 0;
    const requestBodies = [];
    const { assistant } = createAssistant(settings(), {
        fetch: async (url, options) => {
            if (url.endsWith('/messages')) {
                attempt += 1;
                const payload = JSON.parse(options.body);
                requestBodies.push(payload);
                const event = attempt === 1
                    ? 'event: error\ndata: {"message":"Temporary failure"}\n\n'
                    : 'event: delta\ndata: {"text":"Recovered"}\n\n' + 'event: done\ndata: {"ok":true}\n\n';
                let sent = false;
                return { ok: true, body: { getReader: () => ({ read: async () => sent ? { done: true } : (sent = true, { done: false, value: new TextEncoder().encode(event) }) }) } };
            }
            return { ok: true, json: async () => ({ conversations: [] }) };
        },
    });
    assistant.conversationId = 42;
    await assistant.send('Question');
    assert.equal(assistant.messages.length, 2);
    assert.equal(assistant.messages[1].failed, true);

    await assistant.send('Question', true);

    assert.equal(assistant.messages.length, 2);
    assert.equal(assistant.messages[0].role, 'user');
    assert.equal(assistant.messages[1].content, 'Recovered');
    assert.equal(assistant.messages[1].failed, false);
    assert.equal(requestBodies[1].retry, true);
});

test('uses content-sized message bubbles and an auto-growing composer in both layouts', () => {
    assert.equal((viewSource.match(/w-fit max-w-full/g) || []).length, 1);
    assert.equal((viewSource.match(/w-fit max-w-\[92%\]/g) || []).length, 1);
    assert.match(viewSource, /@input="resizeComposer\(\$event\)"/);
    assert.match(viewSource, /@keydown\.enter="if \(!\$event\.shiftKey\)/);
    assert.doesNotMatch(viewSource, /flex flex-1 flex-col gap-6 pb-8/);
});
