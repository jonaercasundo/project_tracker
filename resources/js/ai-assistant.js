window.aiAssistant = (settings) => ({
    ...settings,
    open: Boolean(settings.workspace),
    conversations: [],
    conversationId: null,
    messages: [],
    draft: '',
    loading: false,
    error: '',
    lastPrompt: '',

    async init() {
        this.open = Boolean(this.workspace);
        await this.refreshConversations();
        const stored = sessionStorage.getItem(`assistant:${this.company}`);
        const existing = this.conversations.find((item) => String(item.id) === stored);
        if (existing) await this.load(existing.id);
    },

    toggle() { this.open = !this.open; },
    close() { if (!this.workspace) this.open = false; },
    endpoint(template, id) { return template.replace('__ID__', id); },
    csrf() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },

    async refreshConversations() {
        try {
            const response = await fetch(this.routes.conversations, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            this.conversations = (await response.json()).conversations || [];
        } catch { this.error = 'Unable to load saved conversations.'; }
    },

    async newConversation() {
        this.error = '';
        try {
            const response = await fetch(this.routes.create, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (!response.ok) throw new Error('Unable to start a conversation.');
            const { conversation } = await response.json();
            this.conversationId = conversation.id;
            this.messages = [];
            sessionStorage.setItem(`assistant:${this.company}`, String(conversation.id));
            await this.refreshConversations();
        } catch (error) { this.error = error.message; }
    },

    async load(id) {
        this.error = '';
        try {
            const response = await fetch(this.endpoint(this.routes.show, id), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to open this conversation.');
            const { conversation } = await response.json();
            this.conversationId = conversation.id;
            this.messages = conversation.messages || [];
            sessionStorage.setItem(`assistant:${this.company}`, String(conversation.id));
            this.scrollToBottom();
        } catch (error) { this.error = error.message; }
    },

    async deleteConversation() {
        if (!this.conversationId) return;
        try {
            const response = await fetch(this.endpoint(this.routes.destroy, this.conversationId), { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (!response.ok) throw new Error('Unable to delete this conversation.');
            sessionStorage.removeItem(`assistant:${this.company}`);
            this.conversationId = null;
            this.messages = [];
            await this.refreshConversations();
        } catch (error) { this.error = error.message; }
    },

    async submit() {
        const message = this.draft.trim();
        if (!message || this.loading) return;
        await this.send(message);
    },

    async send(message) {
        if (this.loading) return;
        this.error = '';
        if (!this.conversationId) await this.newConversation();
        if (!this.conversationId) return;
        this.lastPrompt = message;
        this.draft = '';
        this.messages.push({ role: 'user', content: message });
        const assistantMessage = { role: 'assistant', content: '' };
        this.messages.push(assistantMessage);
        this.loading = true;
        this.scrollToBottom();

        try {
            const response = await fetch(this.endpoint(this.routes.stream, this.conversationId), {
                method: 'POST',
                headers: { Accept: 'text/event-stream', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify({ message, page_route: this.context?.route || null }),
            });
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.errors?.message?.[0] || payload.message || 'The assistant could not start. Check the message and try again.');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const events = buffer.split('\n\n');
                buffer = events.pop() || '';
                for (const raw of events) {
                    const event = raw.match(/^event:\s*(.+)$/m)?.[1]?.trim();
                    const data = raw.match(/^data:\s*(.+)$/m)?.[1];
                    if (!data) continue;
                    const payload = JSON.parse(data);
                    if (event === 'delta') assistantMessage.content += payload.text || '';
                    if (event === 'error') throw new Error(payload.message || 'The assistant could not complete this response.');
                    this.scrollToBottom();
                }
            }
            await this.refreshConversations();
        } catch (error) {
            if (!assistantMessage.content) this.messages.pop();
            this.error = error.message || 'The assistant could not complete this response.';
            this.draft = message;
        } finally {
            this.loading = false;
            this.scrollToBottom();
        }
    },

    scrollToBottom() {
        this.$nextTick(() => { if (this.$refs.scrollArea) this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight; });
    },
});
