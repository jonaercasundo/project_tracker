window.aiAssistant = (settings) => ({
    ...settings,
    open: Boolean(settings.workspace),
    conversations: [],
    conversationSearch: '',
    loadingConversations: false,
    openingConversation: false,
    conversationId: null,
    messages: [],
    draft: '',
    loading: false,
    error: '',
    lastPrompt: '',
    quote: null,
    quoteDraft: { customer_name: '', items: [] },
    quotePreview: null,
    quoteProducts: [],
    quoteSearch: '',
    quoteError: '',
    quoteLoading: false,
    quoteSearching: false,
    mobileSidebarOpen: false,
    contextPanelOpen: false,
    contextOpen: false,

    async init() {
        this.open = Boolean(this.workspace);
        if (this.status !== 'ready') return;
        await this.refreshConversations();
        const stored = sessionStorage.getItem(`assistant:${this.company}`);
        const existing = this.conversations.find((item) => String(item.id) === stored);
        if (existing) await this.load(existing.id);
    },

    toggle() { this.open = !this.open; },
    close() { if (!this.workspace) this.open = false; },
    exitWorkspace() {
        if (!this.workspace) { this.close(); return; }
        const previous = document.referrer;
        if (previous && new URL(previous).origin === window.location.origin && !new URL(previous).pathname.endsWith('/assistant')) {
            window.location.assign(previous);
            return;
        }
        window.location.assign(this.routes.dashboard);
    },
    statusMessage() {
        return {
            select_company: 'Select an active company to use the assistant. Your conversations stay isolated by company.',
            disabled: 'The AI Assistant is disabled by an administrator.',
            not_configured: 'The AI service is not configured. Contact an administrator.',
        }[this.status] || 'The AI service is unavailable. Contact an administrator.';
    },
    endpoint(template, id) { return template.replace('__ID__', id); },
    csrf() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },

    async refreshConversations() {
        this.loadingConversations = true;
        try {
            const response = await fetch(this.routes.conversations, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            this.conversations = (await response.json()).conversations || [];
        } catch { this.error = 'Unable to load saved conversations.'; }
        finally { this.loadingConversations = false; }
    },

    filteredConversations() {
        const query = this.conversationSearch.trim().toLowerCase();
        return this.conversations.filter((item) => !query || item.title.toLowerCase().includes(query));
    },

    pageTitle() { return this.context?.page?.title || this.context?.label || `${this.company || 'Company'} workspace`; },
    pageDescription() { return this.context?.page?.help || this.context?.description || 'Ask questions about the work and records available in your current workspace.'; },
    welcomeTitle() { return this.context?.page?.title ? `Let’s make ${this.pageTitle()} easier.` : `Good to see you. What are you working on?`; },
    statusTitle() {
        return {
            select_company: 'Choose your workspace',
            disabled: 'Your assistant is paused',
            not_configured: 'AI setup is needed',
        }[this.status] || 'The assistant is unavailable';
    },
    statusDetails() {
        return {
            select_company: 'Choose an active company from the application menu. Your assistant history stays separated by company.',
            disabled: 'An administrator has temporarily turned off the AI Assistant. Your conversations are still saved.',
            not_configured: 'An administrator needs to finish connecting the AI provider before you can start a conversation.',
        }[this.status] || 'Try again shortly or contact your application administrator.';
    },
    statusLabel() { return this.status === 'ready' ? 'Ready' : ({ select_company: 'Workspace needed', disabled: 'Paused', not_configured: 'Setup needed' }[this.status] || 'Unavailable'); },
    quickActions() {
        if (this.context?.key === 'mi_designer') {
            const actions = [
                { title: 'Search products', subtitle: 'Find an item by name or SKU', prompt: 'Find a product in the MI catalog', icon: 'search' },
                { title: 'Check a price', subtitle: 'Look up an approved selling price', prompt: 'How much is ROUND SIDE TABLE (M)?', icon: 'tag' },
                { title: 'Explain this page', subtitle: 'Get a guided walkthrough', prompt: 'Explain this page and what I can do here', icon: 'help' },
            ];
            if (this.quoteAllowed) actions.splice(2, 0, { title: 'Create a quotation', subtitle: 'Build and review a quote from catalog prices', prompt: 'Help me create a quotation', icon: 'document' });

            return actions;
        }

        return (this.context?.suggestions || ['Explain what I can do in this area.', 'What should I check before saving?'])
            .slice(0, 4).map((prompt, index) => ({
                title: prompt.replace(/^how do i /i, '').replace(/[?!.]+$/, '').replace(/^./, (letter) => letter.toUpperCase()).slice(0, 42),
                subtitle: `A quick guide for ${this.pageTitle()}`,
                prompt,
                icon: /find|search|which/i.test(prompt) ? 'search' : (/dashboard|financial|report|summary|analytics|compare/i.test(prompt) ? 'chart' : (/pending|review|approval|work/i.test(prompt) ? 'activity' : 'help')),
            }));
    },
    formatTime(value) {
        if (!value) return 'Now';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(date);
    },

    async newConversation() {
        if (this.status !== 'ready') return;
        this.error = '';
        try {
            const response = await fetch(this.routes.create, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (!response.ok) throw new Error('Unable to start a conversation.');
            const { conversation } = await response.json();
            this.conversationId = conversation.id;
            this.messages = [];
            this.quote = null;
            this.quotePreview = null;
            sessionStorage.setItem(`assistant:${this.company}`, String(conversation.id));
            await this.refreshConversations();
        } catch (error) { this.error = error.message; }
    },

    async load(id) {
        if (this.status !== 'ready') return;
        this.error = '';
        this.openingConversation = true;
        try {
            const response = await fetch(this.endpoint(this.routes.show, id), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to open this conversation.');
            const { conversation } = await response.json();
            this.conversationId = conversation.id;
            this.messages = conversation.messages || [];
            this.quote = null;
            this.quotePreview = null;
            const quoteResponse = await fetch(this.endpoint(this.routes.quoteShow, id), { headers: { Accept: 'application/json' } });
            if (quoteResponse.ok) {
                const payload = await quoteResponse.json();
                this.quote = payload.workflow;
                if (this.quote) {
                    this.quoteDraft = { customer_name: this.quote.state.customer_name || '', items: this.quote.state.items || [] };
                    if (this.quote.state.preview_items) this.quotePreview = { customer_name: this.quote.state.customer_name, items: this.quote.state.preview_items, total: this.sumQuote(this.quote.state.preview_items), currency: 'PHP' };
                }
            }
            sessionStorage.setItem(`assistant:${this.company}`, String(conversation.id));
            this.scrollToBottom();
        } catch (error) { this.error = error.message; }
        finally { this.openingConversation = false; }
    },

    async deleteConversation() {
        if (!this.conversationId || this.status !== 'ready') return;
        try {
            const response = await fetch(this.endpoint(this.routes.destroy, this.conversationId), { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (!response.ok) throw new Error('Unable to delete this conversation.');
            sessionStorage.removeItem(`assistant:${this.company}`);
            this.conversationId = null;
            this.messages = [];
            this.quote = null;
            this.quotePreview = null;
            this.quoteDraft = { customer_name: '', items: [] };
            await this.refreshConversations();
        } catch (error) { this.error = error.message; }
    },

    async submit() {
        const message = this.draft.trim();
        if (!message || this.loading) return;
        const quoteUpdated = this.quote && !this.quote.quotation ? this.applyQuoteAnswer(message) : false;
        await this.send(message);
        if (quoteUpdated && !this.error) await this.previewQuote();
    },

    applyQuoteAnswer(message) {
        let updated = false;
        const quantity = message.match(/\b(\d{1,6})\s*(?:pieces?|pcs?|units?)\b/i);
        if (quantity && this.quoteDraft.items.length === 1) {
            this.quoteDraft.items[0].quantity = Number(quantity[1]);
            updated = true;
        }
        const customer = message.match(/\b(?:for|customer(?:\s+is)?|company(?:\s+is)?)\s+([^,;]+?)(?=\s*,|\s+delivery\b|\s+quantity\b|$)/i);
        if (customer?.[1]) {
            this.quoteDraft.customer_name = customer[1].trim().replace(/[.?!]+$/, '');
            updated = true;
        }

        return updated;
    },

    async send(message) {
        if (this.loading || this.status !== 'ready') return;
        this.error = '';
        if (!this.conversationId) await this.newConversation();
        if (!this.conversationId) return;
        this.lastPrompt = message;
        this.draft = '';
        this.messages.push({ role: 'user', content: message, created_at: new Date().toISOString() });
        const assistantMessage = { role: 'assistant', content: '', created_at: new Date().toISOString() };
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
                    if (event === 'products') assistantMessage.products = payload.items || [];
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

    async quoteRequest(path, method = 'GET', body = null) {
        const options = { method, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const response = await fetch(path, options);
        const payload = await response.json().catch(() => ({}));
        if (!response.ok && response.status !== 409) throw new Error(payload.message || payload.errors?.customer_name?.[0] || 'Unable to update the quotation.');
        return { response, payload };
    },

    async startQuote(product = null) {
        if (!this.conversationId) await this.newConversation();
        if (!this.conversationId) return;
        this.quoteLoading = true;
        this.quoteError = '';
        try {
            const { payload } = await this.quoteRequest(this.endpoint(this.routes.quoteStart, this.conversationId), 'POST', product ? { product_id: product.id } : {});
            this.quote = { ...payload, quotation: null };
            this.quoteDraft = { customer_name: payload.state.customer_name || '', items: payload.state.items || [] };
            this.quotePreview = null;
        } catch (error) { this.quoteError = error.message; }
        finally { this.quoteLoading = false; }
    },

    async searchQuoteProducts() {
        if (this.quoteSearch.trim().length < 2) { this.quoteProducts = []; return; }
        this.quoteSearching = true;
        try {
            const url = new URL(this.routes.quoteProducts, window.location.origin);
            url.searchParams.set('query', this.quoteSearch.trim());
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Product search is unavailable.');
            this.quoteProducts = (await response.json()).products || [];
        } catch (error) { this.quoteError = error.message; }
        finally { this.quoteSearching = false; }
    },

    addQuoteProduct(product) {
        const existing = this.quoteDraft.items.find((item) => Number(item.id) === Number(product.id));
        if (existing) existing.quantity += 1;
        else this.quoteDraft.items.push({ id: product.id, quantity: 1, name: product.name, sku: product.sku });
        this.quoteSearch = '';
        this.quoteProducts = [];
        this.quotePreview = null;
    },

    removeQuoteProduct(index) { this.quoteDraft.items.splice(index, 1); this.quotePreview = null; },
    sumQuote(items) { return items.reduce((total, item) => total + Math.round(Number(item.subtotal || 0) * 100), 0) / 100; },

    async previewQuote() {
        this.quoteLoading = true;
        this.quoteError = '';
        try {
            const { payload } = await this.quoteRequest(this.endpoint(this.routes.quotePreview, this.conversationId), 'POST', this.quoteDraft);
            this.quotePreview = payload.preview;
        } catch (error) { this.quoteError = error.message; }
        finally { this.quoteLoading = false; }
    },

    async confirmQuote() {
        this.quoteLoading = true;
        this.quoteError = '';
        try {
            const { response, payload } = await this.quoteRequest(this.endpoint(this.routes.quoteConfirm, this.conversationId), 'POST');
            if (response.status === 409) { this.quotePreview = payload.preview; this.quoteError = payload.message; return; }
            this.quote.quotation = payload.quotation;
            this.quote.quotation_id = payload.quotation.id;
        } catch (error) { this.quoteError = error.message; }
        finally { this.quoteLoading = false; }
    },

    async cancelQuote() {
        try {
            await this.quoteRequest(this.endpoint(this.routes.quoteShow, this.conversationId), 'DELETE');
            this.quote = null;
            this.quotePreview = null;
            this.quoteDraft = { customer_name: '', items: [] };
        } catch (error) { this.quoteError = error.message; }
    },

    scrollToBottom() {
        this.$nextTick(() => { if (this.$refs.scrollArea) this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight; });
    },
});
