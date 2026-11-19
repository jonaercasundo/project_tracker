window.aiConfiguration = (settings) => ({
    ...settings,
    tabs: ['overview', 'openai', 'anthropic', 'research'],
    activeTab: ['overview', 'openai', 'anthropic', 'research'].includes(window.location.hash.slice(1)) ? window.location.hash.slice(1) : 'overview',
    model: settings.initialModel,
    researchProvider: settings.initialResearchProvider || 'openai',
    anthropicModel: settings.initialAnthropicModel || 'claude-sonnet-5-5',
    anthropicApiKey: '',
    enabled: Boolean(settings.initialEnabled),
    perUserLimit: Number(settings.initialPerUserLimit),
    monthlyLimit: settings.initialMonthlyLimit ?? '',
    apiKey: '',
    testStatus: settings.initialTestStatus,
    testMessage: settings.initialTestMessage || '',
    testCredentialSource: settings.initialTestCredentialSource || '',
    lastSuccessfulTest: settings.initialLastSuccessfulTest || '',
    testing: false,
    saving: false,
    notice: '',
    noticeType: 'success',

    selectTab(tab, focus = false) {
        if (!this.tabs.includes(tab)) return;
        this.activeTab = tab;
        window.history.replaceState(null, '', `#${tab}`);
        if (focus) this.$nextTick(() => document.getElementById(`ai-tab-${tab}`)?.focus());
    },

    moveTab(direction) {
        const index = (this.tabs.indexOf(this.activeTab) + direction + this.tabs.length) % this.tabs.length;
        this.selectTab(this.tabs[index], true);
    },

    csrf() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },
    credentialLabel() {
        if (this.credentialSource === 'database') return 'Database-managed key is stored (masked)';
        if (this.credentialSource === 'environment') return 'Using environment fallback';
        return 'No credential is configured';
    },

    async send(url, method, payload = {}) {
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            body: method === 'GET' ? undefined : JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = Object.values(data.errors || {}).flat()[0];
            const error = new Error(validation || data.message || 'The request could not be completed.');
            error.status = data.status;
            throw error;
        }
        return data;
    },

    payload(section) {
        const payload = {
            model: this.initialModel,
            enabled: Boolean(this.initialEnabled),
            per_user_requests_per_minute: Number(this.initialPerUserLimit),
            monthly_token_limit: this.initialMonthlyLimit ?? null,
        };
        if (section === 'openai') {
            payload.model = this.model;
            if (this.apiKey.trim()) payload.openai_api_key = this.apiKey.trim();
        } else if (section === 'anthropic') {
            payload.anthropic_model = this.anthropicModel;
            if (this.anthropicApiKey.trim()) payload.anthropic_api_key = this.anthropicApiKey.trim();
        } else if (section === 'research') {
            payload.research_provider = this.researchProvider;
            payload.enabled = this.enabled;
            payload.per_user_requests_per_minute = Number(this.perUserLimit);
            payload.monthly_token_limit = this.monthlyLimit === '' ? null : Number(this.monthlyLimit);
        }
        return payload;
    },

    async save(section) {
        const replacingKey = section === 'openai' ? this.apiKey.trim() : (section === 'anthropic' ? this.anthropicApiKey.trim() : '');
        if (replacingKey && !window.confirm('Save this API key? It will replace any key already stored for this provider.')) return;
        this.saving = true;
        this.notice = '';
        try {
            const payload = this.payload(section);
            const data = await this.send(this.storeUrl, 'POST', payload);
            if (section === 'openai') {
                this.initialModel = this.model;
                if (payload.openai_api_key) {
                    this.hasDatabaseKey = true;
                    this.credentialSource = 'database';
                }
                this.apiKey = '';
            } else if (section === 'anthropic') {
                this.initialAnthropicModel = this.anthropicModel;
                if (payload.anthropic_api_key) this.hasAnthropicKey = true;
                this.anthropicApiKey = '';
            } else if (section === 'research') {
                this.initialResearchProvider = this.researchProvider;
                this.initialEnabled = this.enabled;
                this.initialPerUserLimit = this.perUserLimit;
                this.initialMonthlyLimit = this.monthlyLimit === '' ? null : Number(this.monthlyLimit);
            }
            this.notice = data.message;
            this.noticeType = 'success';
            if (data.configured_by) this.configuredBy = data.configured_by;
            if (data.updated_at) this.lastUpdatedAt = data.updated_at;
        } catch (error) {
            this.notice = error.message;
            this.noticeType = 'error';
        } finally { this.saving = false; }
    },

    async testConnection(webSearch = false, provider = 'openai') {
        this.testing = true;
        this.notice = '';
        this.testMessage = '';
        try {
            const data = await this.send(this.testUrl, 'POST', {
                ...(provider === 'openai' && this.apiKey.trim() ? { openai_api_key: this.apiKey.trim() } : {}),
                ...(provider === 'anthropic' && this.anthropicApiKey.trim() ? { anthropic_api_key: this.anthropicApiKey.trim() } : {}),
                provider,
                model: provider === 'anthropic' ? this.anthropicModel : this.model,
                web_search: webSearch,
            });
            this.testMessage = data.message;
            this.testStatus = data.status;
            this.testCredentialSource = provider === 'anthropic' ? 'Claude credential' : (this.apiKey.trim() ? 'submitted' : this.credentialSource);
            if (data.tested_at) {
                this.lastSuccessfulTest = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(data.tested_at));
            }
            this.notice = data.message;
            this.noticeType = 'success';
        } catch (error) {
            this.testMessage = error.message;
            this.testStatus = error.status || 'failed';
            this.testCredentialSource = provider === 'anthropic' ? 'Claude credential' : (this.apiKey.trim() ? 'submitted' : this.credentialSource);
            this.notice = error.message;
            this.noticeType = 'error';
        } finally { this.testing = false; }
    },

    async removeKey() {
        if (!window.confirm('Remove the database-managed OpenAI key? An environment fallback, if configured, will remain active.')) return;
        try {
            const data = await this.send(this.removeUrl, 'DELETE');
            this.notice = data.message;
            this.noticeType = 'success';
            window.location.reload();
        } catch (error) {
            this.notice = error.message;
            this.noticeType = 'error';
        }
    },
});
