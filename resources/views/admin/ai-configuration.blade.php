<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Administrator / Settings</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">AI Configuration</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"
        x-data="aiConfiguration({
            storeUrl: @js(route('admin.ai-configuration.store')),
            testUrl: @js(route('admin.ai-configuration.test-connection')),
            removeUrl: @js(route('admin.ai-configuration.key.destroy')),
            initialModel: @js($model),
            initialResearchProvider: @js($researchProvider),
            initialAnthropicModel: @js($anthropicModel),
            initialEnabled: @js($enabled),
            initialPerUserLimit: @js($perUserRequestLimit),
            initialMonthlyLimit: @js($monthlyTokenLimit),
            initialTestStatus: @js($lastTestStatus),
            initialTestMessage: @js($lastTestMessage),
            initialTestCredentialSource: @js($lastTestCredentialSource),
            initialLastSuccessfulTest: @js($lastSuccessfulTestAt?->format('M j, Y g:i A')),
            hasDatabaseKey: @js($hasDatabaseKey),
            hasAnthropicKey: @js($hasAnthropicKey),
            configuredBy: @js($configuredBy ?? 'Not configured'),
            lastUpdatedAt: @js($lastUpdatedAt?->toIso8601String()),
            anthropicFallbackConfigured: @js($anthropicFallbackConfigured),
            credentialSource: @js($credentialSource),
            fallbackConfigured: @js($environmentFallbackConfigured)
        })">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Manage your AI providers</h2>
                <p class="mt-1 text-sm text-slate-500">View configured providers, add an API key, or edit a provider’s model.</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700" x-text="initialEnabled ? 'AI features enabled' : 'AI features disabled'"></span>
                    <span class="text-slate-600">Research uses <strong x-text="initialResearchProvider === 'anthropic' ? 'Claude' : 'OpenAI'"></strong></span>
                </div>
            </div>
            <div class="text-left sm:max-w-sm sm:text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Latest provider test</p>
                <p class="mt-1 text-sm text-slate-700" x-text="testMessage || 'No connection test recorded.'"></p>
                <p class="mt-1 text-xs text-slate-500" x-show="testCredentialSource" x-text="`Credential: ${testCredentialSource}`"></p>
                <p class="mt-1 text-xs text-slate-500" x-show="lastSuccessfulTest" x-text="`Last success: ${lastSuccessfulTest}`"></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-1 rounded-xl border border-slate-200 bg-white p-1 sm:flex" role="tablist" aria-label="AI configuration"
            @keydown.arrow-right.prevent="moveTab(1)" @keydown.arrow-left.prevent="moveTab(-1)"
            @keydown.home.prevent="selectTab('overview', true)" @keydown.end.prevent="selectTab('research', true)">
            @foreach(['overview' => 'Configured AI', 'openai' => 'OpenAI', 'anthropic' => 'Claude', 'research' => 'Research & limits'] as $tab => $label)
                <button id="ai-tab-{{ $tab }}" type="button" role="tab" aria-controls="ai-panel-{{ $tab }}"
                    :aria-selected="activeTab === '{{ $tab }}'" :tabindex="activeTab === '{{ $tab }}' ? 0 : -1"
                    @click="selectTab('{{ $tab }}')"
                    :class="activeTab === '{{ $tab }}' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                    class="shrink-0 rounded-lg px-4 py-2.5 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="notice" x-cloak class="rounded-xl border px-4 py-3 text-sm" :class="noticeType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'" x-text="notice" role="status" aria-live="polite"></div>

        <section id="ai-panel-overview" role="tabpanel" aria-labelledby="ai-tab-overview" x-show="activeTab === 'overview'" class="space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Configured AI providers</h2>
                <p class="mt-1 text-sm text-slate-500">Saved models and credential status are shown below. Keys remain masked. Test a provider to verify its billing and access.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <article class="flex min-w-0 flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-provider="openai">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-slate-900">OpenAI</h3>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="credentialSource !== 'none' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'" x-text="credentialSource !== 'none' ? 'Key configured' : 'Not configured'"></span>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Saved model</dt><dd class="mt-1 break-all font-medium text-slate-900" x-text="initialModel"></dd></div>
                        <div><dt class="text-xs text-slate-500">API key</dt><dd class="mt-1 text-slate-700" x-text="hasDatabaseKey ? '•••••••• · Stored securely' : (credentialSource === 'environment' ? '•••••••• · Environment fallback' : 'No API key added')"></dd></div>
                        <div><dt class="text-xs text-slate-500">Used for</dt><dd class="mt-1 text-slate-700" x-text="initialResearchProvider === 'openai' ? 'AI assistant and product research' : 'AI assistant'"></dd></div>
                    </dl>
                    <button type="button" @click="selectTab('openai', true)" class="mt-auto rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" x-text="credentialSource !== 'none' ? 'Edit OpenAI configuration' : 'Add OpenAI configuration'"></button>
                </article>
                <article class="flex min-w-0 flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-provider="anthropic">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-slate-900">Claude (Anthropic)</h3>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="hasAnthropicKey || anthropicFallbackConfigured ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'" x-text="hasAnthropicKey || anthropicFallbackConfigured ? 'Key configured' : 'Not configured'"></span>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Saved model</dt><dd class="mt-1 break-all font-medium text-slate-900" x-text="initialAnthropicModel"></dd></div>
                        <div><dt class="text-xs text-slate-500">API key</dt><dd class="mt-1 text-slate-700" x-text="hasAnthropicKey ? '•••••••• · Stored securely' : (anthropicFallbackConfigured ? '•••••••• · Environment fallback' : 'No API key added')"></dd></div>
                        <div><dt class="text-xs text-slate-500">Used for</dt><dd class="mt-1 text-slate-700" x-text="initialResearchProvider === 'anthropic' ? 'Active product research provider' : 'Available for product research'"></dd></div>
                    </dl>
                    <button type="button" @click="selectTab('anthropic', true)" class="mt-auto rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" x-text="hasAnthropicKey || anthropicFallbackConfigured ? 'Edit Claude configuration' : 'Add Claude configuration'"></button>
                </article>
            </div>
            <button type="button" @click="selectTab('research', true)" class="text-sm font-semibold text-blue-700 underline underline-offset-4">Choose the research provider and manage usage limits</button>
        </section>

        <section id="ai-panel-openai" role="tabpanel" aria-labelledby="ai-tab-openai" x-show="activeTab === 'openai'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">OpenAI configuration</h2>
                <p class="mt-1 text-sm text-slate-500">Add or update the key and model used by the AI assistant and OpenAI product research.</p>
            </div>
            <form @submit.prevent="save('openai')" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="ai-provider" class="mb-1.5 block text-sm font-medium text-slate-700">AI Provider</label>
                    <input id="ai-provider" value="OpenAI" disabled class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-600">
                </div>
                <div>
                    <label for="ai-model" class="mb-1.5 block text-sm font-medium text-slate-700">AI Model</label>
                    <select id="ai-model" x-model="model" class="w-full rounded-lg border-slate-300 text-sm text-slate-800">
                        @foreach($supportedModels as $supportedModel)<option value="{{ $supportedModel }}">{{ $supportedModel }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="openai-api-key" class="mb-1.5 block text-sm font-medium text-slate-700">OpenAI API Key</label>
                    <input id="openai-api-key" x-model="apiKey" type="password" autocomplete="off" spellcheck="false" maxlength="255" :placeholder="hasDatabaseKey ? 'Key stored securely. Leave blank to keep it.' : 'Enter an OpenAI API key'" class="w-full rounded-lg border-slate-300 text-sm text-slate-800">
                    <p class="mt-2 text-xs text-slate-500">Keys are stored encrypted and never shown again. Leave blank to keep the saved key. A saved key takes precedence over OPENAI_API_KEY.</p>
                    <p class="mt-1 text-xs text-slate-500">API billing must be active on the project owning the key. Product research also requires web-search access.</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60" x-text="saving ? 'Saving…' : 'Save OpenAI configuration'"></button>
                    <button type="button" @click="testConnection()" :disabled="testing" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-60">Test API Connection</button>
                    <button type="button" @click="testConnection(true)" :disabled="testing" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-60">Test web search</button>
                    <button type="button" x-show="hasDatabaseKey" @click="removeKey()" class="rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove API Key</button>
                </div>
            </form>
        </section>

        <section id="ai-panel-anthropic" role="tabpanel" aria-labelledby="ai-tab-anthropic" x-show="activeTab === 'anthropic'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Claude configuration</h2>
                <p class="mt-1 text-sm text-slate-500">Add or update your Anthropic API key and Claude model for product research.</p>
            </div>
            <form @submit.prevent="save('anthropic')" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2">
                    <label for="anthropic-model" class="mb-1.5 block text-sm font-medium text-slate-700">Claude model</label>
                    <select id="anthropic-model" x-model="anthropicModel" class="w-full rounded-lg border-slate-300 text-sm text-slate-800">
                        @foreach($anthropicModels as $claudeModel)<option value="{{ $claudeModel }}">{{ $claudeModel }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="anthropic-api-key" class="mb-1.5 block text-sm font-medium text-slate-700">Claude API key</label>
                    <input id="anthropic-api-key" x-model="anthropicApiKey" type="password" autocomplete="off" spellcheck="false" maxlength="255" :placeholder="hasAnthropicKey ? 'Claude key stored securely. Leave blank to keep it.' : 'Enter an Anthropic API key'" class="w-full rounded-lg border-slate-300 text-sm text-slate-800">
                    <p class="mt-2 text-xs text-slate-500">Keys are stored encrypted and never shown again. Leave blank to keep the saved key. A saved key takes precedence over ANTHROPIC_API_KEY.</p>
                    <p class="mt-1 text-xs text-slate-500">Fund the Anthropic API account and enable web search in your Claude Console before testing.</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60" x-text="saving ? 'Saving…' : 'Save Claude configuration'"></button>
                    <button type="button" @click="testConnection(false, 'anthropic')" :disabled="testing" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-60">Test Claude connection</button>
                    <button type="button" @click="testConnection(true, 'anthropic')" :disabled="testing" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-60">Test Claude web search</button>
                </div>
            </form>
        </section>

        <section id="ai-panel-research" role="tabpanel" aria-labelledby="ai-tab-research" x-show="activeTab === 'research'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Product research provider and usage controls</h2>
                <p class="mt-1 text-sm text-slate-500">Choose the configured provider for Find Prices and manage system-wide AI access.</p>
            </div>
            <form @submit.prevent="save('research')" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="research-provider" class="mb-1.5 block text-sm font-medium text-slate-700">Research provider</label>
                    <select id="research-provider" x-model="researchProvider" class="w-full rounded-lg border-slate-300 text-sm text-slate-800">
                        <option value="openai">OpenAI</option><option value="anthropic">Claude (Anthropic)</option>
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Save keys and models in the provider’s tab before selecting it for product research.</p>
                </div>
                <div class="flex items-end">
                    <button type="button" @click="testConnection(true, researchProvider)" :disabled="testing" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-60" x-text="testing ? 'Testing…' : 'Test research web search'"></button>
                </div>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 sm:col-span-2">
                    <input type="checkbox" x-model="enabled" class="mt-0.5 rounded border-slate-300 text-blue-600">
                    <span><span class="block text-sm font-semibold text-slate-800">Enable AI Assistant</span><span class="mt-1 block text-xs text-slate-500">Controls assistant routes, product research, and the page widget for all users.</span></span>
                </label>
                <div>
                    <label for="user-limit" class="mb-1.5 block text-sm font-medium text-slate-700">Per-user request limit</label>
                    <input id="user-limit" x-model.number="perUserLimit" type="number" min="1" max="120" required class="w-full rounded-lg border-slate-300 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Requests per minute.</p>
                </div>
                <div>
                    <label for="monthly-limit" class="mb-1.5 block text-sm font-medium text-slate-700">Monthly token limit</label>
                    <input id="monthly-limit" x-model="monthlyLimit" type="number" min="1" step="1" placeholder="No limit" class="w-full rounded-lg border-slate-300 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Blank means no application-level cap. Requests already in progress can complete after the cap is reached.</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">This month</p>
                    <p class="mt-2 text-sm text-slate-700">{{ number_format((int) ($usage->request_count ?? 0)) }} requests · {{ number_format((int) ($usage->input_tokens ?? 0) + (int) ($usage->output_tokens ?? 0)) }} tokens</p>
                    <p class="mt-2 text-xs text-slate-500">Totals cover completed assistant requests recorded by this application. Provider billing data is not connected.</p>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60" x-text="saving ? 'Saving…' : 'Save research & limits'"></button>
                </div>
            </form>
        </section>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            <span class="font-semibold">Configured by:</span> <span x-text="configuredBy"></span>
            <span class="mx-2 text-slate-300">•</span>
            <span class="font-semibold">Last updated:</span> <span x-text="lastUpdatedAt ? new Date(lastUpdatedAt).toLocaleString() : 'Not updated'"></span>
        </div>
    </div>
</x-app-layout>
