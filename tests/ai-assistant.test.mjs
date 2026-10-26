import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const source = await readFile(new URL('../resources/js/ai-assistant.js', import.meta.url), 'utf8');

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

    await assistant.send('Hello');

    assert.equal(calls[0][0], '/assistant/conversations/42/messages');
    assert.equal(assistant.messages[0].content, 'Hello');
    assert.equal(assistant.messages[1].content, 'Hello from AI');
    assert.equal(assistant.loading, false);
});
