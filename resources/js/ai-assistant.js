import { renderAiMarkdown } from './ai-markdown.js';
import { AiSseParser } from './ai-sse.js';

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
    activeAttachment: null,
    attachmentUploading: false,
    pendingAttachmentName: '',
    pendingAttachmentSize: 0,
    uploadProgress: 0,
    attachmentError: '',
    draggingWorkbook: false,
    attachmentMessageSentId: null,
    mobileSidebarOpen: false,
    contextPanelOpen: false,
    contextOpen: false,
    thinkingLabel: 'Thinking…',

    renderMarkdown(content) { return renderAiMarkdown(content || ''); },

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
            this.activeAttachment = null;
            this.attachmentMessageSentId = null;
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
            const { conversation, attachments } = await response.json();
            this.conversationId = conversation.id;
            this.messages = conversation.messages || [];
            this.activeAttachment = attachments?.[0] || null;
            this.attachmentMessageSentId = conversation.messages?.some((message) => message.metadata?.attachment_id === this.activeAttachment?.id)
                ? this.activeAttachment?.id
                : null;
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
            this.activeAttachment = null;
            this.attachmentMessageSentId = null;
            await this.refreshConversations();
        } catch (error) { this.error = error.message; }
    },

    async submit() {
        const message = this.draft.trim();
        if ((!message && !this.activeAttachment) || this.loading || this.attachmentUploading) return;
        const prompt = message || (this.activeAttachment?.preview?.kind === 'workbook'
            ? 'Inspect this workbook and prepare a new MI petty cash liquidation draft. Report the extracted rows, missing information, warnings, and calculated VND total.'
            : `Read the attached file ${this.activeAttachment?.filename || ''}. Summarize its key information and ask me about any missing details.`);
        const quoteUpdated = this.quote && !this.quote.quotation ? this.applyQuoteAnswer(message) : false;
        await this.send(prompt);
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

    async send(message, retry = false) {
        if (this.loading || this.status !== 'ready') return;
        this.error = '';
        if (!this.conversationId) await this.newConversation();
        if (!this.conversationId) return;
        this.lastPrompt = message;
        const lastUserMessage = [...this.messages].reverse().find((item) => item.role === 'user');
        const lastAssistantMessage = this.messages[this.messages.length - 1];
        const isRetry = retry && lastUserMessage?.content === message && lastAssistantMessage?.role === 'assistant' && lastAssistantMessage.failed;
        if (isRetry) this.messages.pop();
        const userMessage = { role: 'user', content: message, created_at: new Date().toISOString() };
        if (!isRetry) this.messages.push(userMessage);
        this.messages.push({ role: 'assistant', content: '', created_at: new Date().toISOString(), failed: false, complete: false, error: '' });
        const assistantMessage = this.messages[this.messages.length - 1];
        this.loading = true;
        let messageAccepted = false;
        this.thinkingLabel = 'Connecting…';
        this.scrollToBottom(true);
        const requestController = new AbortController();
        const requestTimeout = setTimeout(() => requestController.abort(), 100_000);

        try {
            const response = await fetch(this.endpoint(this.routes.stream, this.conversationId), {
                method: 'POST',
                headers: { Accept: 'text/event-stream', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify({ message, retry: isRetry, page_route: this.context?.route || null, attachment_id: this.activeAttachment?.id || null }),
                signal: requestController.signal,
            });
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                const messages = { 403: 'You do not have access to that information. Contact your administrator if you think you need access.', 429: 'The assistant is temporarily at its usage limit. Please try again later.', 503: 'The assistant is not configured right now. Please contact your administrator.' };
                throw new Error(response.status === 422 ? 'Please check your message and try again.' : messages[response.status] || 'I’m having trouble reaching the assistant right now. Your message is still here; please try again in a moment.');
            }

            messageAccepted = true;
            if (this.draft.trim() === message) this.draft = '';
            if (this.$refs.composer) this.resizeComposer(this.$refs.composer);
            if (this.$refs.drawerComposer) this.resizeComposer(this.$refs.drawerComposer);
            if (this.$refs.workspaceComposer) this.resizeComposer(this.$refs.workspaceComposer);
            if (this.activeAttachment) this.attachmentMessageSentId = this.activeAttachment.id;

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            const parser = new AiSseParser();
            const processEvents = (events) => {
                for (const { event, data } of events) {
                    const payload = JSON.parse(data);
                    if (event === 'status') this.thinkingLabel = payload.label || 'Thinking…';
                    if (event === 'delta') {
                        const shouldStick = this.isNearBottom();
                        assistantMessage.content += payload.text || '';
                        assistantMessage.failed = false;
                        this.scrollToBottom(shouldStick);
                    }
                    if (event === 'products') assistantMessage.products = payload.items || [];
                    if (event === 'done') assistantMessage.complete = true;
                    if (event === 'error') throw new Error(payload.message || 'I’m having trouble retrieving that right now. Your message is still here; would you like to try again?');
                }
            };
            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                processEvents(parser.push(decoder.decode(value, { stream: true })));
            }
            processEvents(parser.push(decoder.decode()));
            processEvents(parser.finish());
            if (!assistantMessage.complete) throw new Error('The connection ended before the response was complete. Please try again.');
        } catch (error) {
            if (!messageAccepted) {
                this.messages = this.messages.filter((item) => item !== userMessage && item !== assistantMessage);
                this.draft = this.draft || message;
                this.error = error.message || 'Unable to send this message. Your text and attachment are still ready.';
                return;
            }
            const transportFailed = ['AbortError', 'TypeError', 'SyntaxError'].includes(error.name) || ['Failed to fetch', 'NetworkError', 'Load failed'].includes(error.message);
            assistantMessage.error = transportFailed
                ? (error.name === 'AbortError' ? 'The response took too long to arrive. Your question is still here; please try again.' : 'The connection was interrupted before the response finished. Your question is still here; please try again.')
                : (error.message || 'I’m having trouble retrieving that right now. Your message is still here; would you like to try again?');
            assistantMessage.failed = true;
            this.error = '';
        } finally {
            clearTimeout(requestTimeout);
            this.loading = false;
            this.scrollToBottom();
            await this.refreshConversations();
        }
    },

    attachmentPreviewRows() { return (this.activeAttachment?.preview?.rows || []).slice(0, 8); },
    formatFileSize(size) {
        if (!Number.isFinite(Number(size)) || Number(size) <= 0) return 'Checking size';
        return size < 1024 * 1024 ? `${Math.max(1, Math.round(size / 1024))} KB` : `${(size / (1024 * 1024)).toFixed(1)} MB`;
    },
    attachmentIcon() {
        const extension = (this.activeAttachment?.filename || this.pendingAttachmentName).split('.').pop()?.toLowerCase();
        if (['xlsx', 'xls', 'csv'].includes(extension)) return 'spreadsheet';
        if (['pdf', 'docx', 'txt'].includes(extension)) return 'fileText';
        return 'file';
    },
    attachmentTypeLabel() {
        const extension = (this.activeAttachment?.filename || this.pendingAttachmentName).split('.').pop()?.toLowerCase();
        return ({ xlsx: 'Excel workbook', xls: 'Excel workbook', csv: 'CSV spreadsheet', pdf: 'PDF document', docx: 'Word document', txt: 'Text document' })[extension] || 'File';
    },
    attachmentStatus() {
        if (this.attachmentUploading) return `Uploading ${this.uploadProgress}%`;
        return this.attachmentMessageSentId === this.activeAttachment?.id ? 'Attached to this conversation' : 'Ready to send';
    },
    openFilePicker() {
        const picker = this.$refs[this.workspace ? 'workspaceFile' : 'drawerFile'];
        if (picker) picker.click();
    },
    uploadDropped(event) {
        this.draggingWorkbook = false;
        const file = event.dataTransfer?.files?.[0];
        if (file) this.uploadWorkbook(file);
    },
    uploadAttachment(event) {
        const file = event.target?.files?.[0];
        event.target.value = '';
        if (file) this.uploadWorkbook(file);
    },
    async uploadWorkbook(file) {
        if (!this.attachmentAllowed) { this.attachmentError = 'You do not have permission to attach files to this assistant conversation.'; return; }
        if (!/\.(xlsx|xls|csv|pdf|docx|txt)$/i.test(file.name) || file.size > 10 * 1024 * 1024) {
            this.attachmentError = 'Choose an .xlsx, .xls, .csv, .pdf, .docx, or .txt file smaller than 10 MB.';
            return;
        }
        const workbook = /\.(xlsx|xls|csv)$/i.test(file.name);
        if (workbook && !this.liquidationAllowed) {
            this.attachmentError = 'Excel liquidation drafts are available only to authorized MI preparers.';
            return;
        }
        if (!this.conversationId) await this.newConversation();
        if (!this.conversationId) return;
        if (this.activeAttachment && this.attachmentMessageSentId !== this.activeAttachment.id) {
            await this.removeAttachment();
            if (this.activeAttachment) return;
        }
        this.attachmentError = '';
        this.pendingAttachmentName = file.name;
        this.pendingAttachmentSize = file.size;
        this.attachmentUploading = true;
        this.uploadProgress = 0;
        const form = new FormData();
        form.append('file', file);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', this.endpoint(this.routes.liquidationAttach, this.conversationId));
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrf());
        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) this.uploadProgress = Math.round((event.loaded / event.total) * 100);
        });
        xhr.addEventListener('load', () => {
            let payload = {};
            try { payload = JSON.parse(xhr.responseText || '{}'); } catch { payload = {}; }
            if (xhr.status < 200 || xhr.status >= 300) {
                this.attachmentError = payload.errors?.file?.[0] || payload.message || 'The workbook could not be inspected.';
                this.pendingAttachmentName = '';
                this.pendingAttachmentSize = 0;
                this.attachmentUploading = false;
                return;
            }
            this.activeAttachment = payload.attachment;
            this.attachmentMessageSentId = null;
            this.pendingAttachmentName = '';
            this.pendingAttachmentSize = 0;
            this.attachmentUploading = false;
            this.uploadProgress = 100;
            this.scrollToBottom();
        });
        xhr.addEventListener('error', () => {
            this.attachmentError = 'The file upload was interrupted. Check your connection and try again.';
            this.pendingAttachmentName = '';
            this.pendingAttachmentSize = 0;
            this.attachmentUploading = false;
        });
        xhr.send(form);
    },
    async removeAttachment() {
        if (!this.activeAttachment || this.attachmentUploading) return;
        try {
            const response = await fetch(this.endpoint(this.routes.liquidationDestroy, this.activeAttachment.id), {
                method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            });
            if (!response.ok) throw new Error('Unable to remove this workbook.');
            this.activeAttachment = null;
            this.attachmentMessageSentId = null;
            this.attachmentError = '';
        } catch (error) { this.attachmentError = error.message; }
    },
    async fillLiquidationForm() {
        if (!this.activeAttachment || !this.liquidationAllowed) return;
        this.attachmentError = '';
        try {
            const response = await fetch(this.endpoint(this.routes.liquidationFill, this.activeAttachment.id), {
                method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.url) throw new Error(payload.message || 'Unable to open the liquidation form.');
            window.location.assign(payload.url);
        } catch (error) { this.attachmentError = error.message; }
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

    resizeComposer(event) {
        const textarea = event.target || event;
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 144)}px`;
        textarea.style.overflowY = textarea.scrollHeight > 144 ? 'auto' : 'hidden';
    },

    isNearBottom() {
        const area = this.$refs.scrollArea;
        return !area || area.scrollHeight - area.scrollTop - area.clientHeight < 120;
    },

    scrollToBottom(force = false) {
        const shouldScroll = force || this.isNearBottom();
        if (shouldScroll) this.$nextTick(() => { if (this.$refs.scrollArea) this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight; });
    },
});
