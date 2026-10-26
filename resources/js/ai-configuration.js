window.aiConfiguration = (settings) => ({
    ...settings,
    model: settings.initialModel,
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
    testMessage: '',

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

    payload(includeKey = true) {
        return {
            ...(includeKey && this.apiKey.trim() ? { openai_api_key: this.apiKey.trim() } : {}),
            model: this.model,
            enabled: this.enabled,
            per_user_requests_per_minute: Number(this.perUserLimit),
            monthly_token_limit: this.monthlyLimit === '' ? null : Number(this.monthlyLimit),
        };
    },

    async save() {
        if (this.apiKey.trim() && !window.confirm('Replace the currently stored API key with the new key?')) return;
        this.saving = true;
        this.notice = '';
        try {
            const data = await this.send(this.storeUrl, 'POST', this.payload());
            this.apiKey = '';
            this.notice = data.message;
            this.noticeType = 'success';
            window.location.reload();
        } catch (error) {
            this.notice = error.message;
            this.noticeType = 'error';
        } finally { this.saving = false; }
    },

    async testConnection() {
        this.testing = true;
        this.notice = '';
        this.testMessage = '';
        try {
            const data = await this.send(this.testUrl, 'POST', {
                ...(this.apiKey.trim() ? { openai_api_key: this.apiKey.trim() } : {}),
                model: this.model,
            });
            this.testMessage = data.message;
            this.testStatus = data.status;
            this.testCredentialSource = this.apiKey.trim() ? 'submitted' : this.credentialSource;
            if (data.tested_at) {
                this.lastSuccessfulTest = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(data.tested_at));
            }
            this.notice = data.message;
            this.noticeType = 'success';
        } catch (error) {
            this.testMessage = error.message;
            this.testStatus = error.status || 'failed';
            this.testCredentialSource = this.apiKey.trim() ? 'submitted' : this.credentialSource;
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
