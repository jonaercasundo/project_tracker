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
            initialEnabled: @js($enabled),
            initialPerUserLimit: @js($perUserRequestLimit),
            initialMonthlyLimit: @js($monthlyTokenLimit),
            initialTestStatus: @js($lastTestStatus),
            initialTestMessage: @js($lastTestMessage),
            initialTestCredentialSource: @js($lastTestCredentialSource),
            initialLastSuccessfulTest: @js($lastSuccessfulTestAt?->format('M j, Y g:i A')),
            hasDatabaseKey: @js($hasDatabaseKey),
            credentialSource: @js($credentialSource),
            fallbackConfigured: @js($environmentFallbackConfigured)
        })">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Connection status</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="credentialSource === 'none' ? 'bg-amber-50 text-amber-800' : (enabled ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700')" x-text="credentialSource === 'none' ? 'Configuration required' : (enabled ? 'Enabled' : 'Disabled')"></span>
                    <span class="text-sm text-slate-600" x-text="credentialLabel()"></span>
                </div>
                <p class="mt-2 text-xs text-slate-500" x-show="fallbackConfigured">The environment credential is used only when no database-managed key is stored.</p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs text-slate-500">Last successful connection test</p>
                <p class="mt-1 text-sm font-medium text-slate-800" x-text="lastSuccessfulTest || 'Not tested'"></p>
                <p class="mt-1 max-w-xl text-xs text-slate-500" x-show="testMessage" x-text="testMessage"></p>
                <p class="mt-1 text-xs text-slate-400" x-show="testCredentialSource" x-text="`Last test credential: ${testCredentialSource}`"></p>
            </div>
        </div>

        <div x-show="notice" x-cloak class="rounded-xl border px-4 py-3 text-sm" :class="noticeType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'" x-text="notice" role="status" aria-live="polite"></div>

        <form @submit.prevent="save()" class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">Provider and credential</h2>
                    <p class="mt-1 text-sm text-slate-500">This credential is system-wide. Company and user access checks still apply to assistant requests.</p>
                </div>
                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="ai-provider">AI Provider</label>
                        <input id="ai-provider" value="{{ $provider }}" disabled class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-600">
                        <p class="mt-1 text-xs text-slate-500">OpenAI Responses API</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="ai-model">AI Model</label>
                        <select id="ai-model" x-model="model" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($supportedModels as $supportedModel)
                                <option value="{{ $supportedModel }}">{{ $supportedModel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">A connection test checks a minimal request for this model only.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="openai-api-key">OpenAI API Key</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input id="openai-api-key" x-model="apiKey" type="password" autocomplete="off" spellcheck="false" maxlength="255" placeholder="{{ $hasDatabaseKey ? 'Key is stored securely. Enter a new key to replace it.' : ($environmentFallbackConfigured ? 'Environment fallback is active. Enter a key to store one in the database.' : 'Paste an OpenAI API key') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" @click="testConnection()" :disabled="testing" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"><span x-text="testing ? 'Testing…' : 'Test API Connection'"></span></button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Stored keys are encrypted with the application key and never returned to this page. Leave blank to keep the current database key.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="submit" :disabled="saving" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"><span x-text="saving ? 'Saving…' : (apiKey.trim() ? 'Replace API Key and Save' : 'Save Configuration')"></span></button>
                            <button type="button" x-show="hasDatabaseKey" @click="removeKey()" class="inline-flex items-center justify-center rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove API Key</button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input type="checkbox" x-model="enabled" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span><span class="block text-sm font-semibold text-slate-800">Enable AI Assistant</span><span class="mt-1 block text-xs text-slate-500">When disabled, assistant routes and the page widget are unavailable to all users.</span></span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">Usage controls</h2>
                    <p class="mt-1 text-sm text-slate-500">Monthly token totals use completed assistant requests recorded by this application.</p>
                </div>
                <div class="grid gap-5 p-5 sm:grid-cols-3 sm:p-6">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="user-limit">Per-user request limit</label>
                        <div class="flex items-center gap-2"><input id="user-limit" x-model.number="perUserLimit" type="number" min="1" max="120" required class="w-full rounded-lg border-slate-300 text-sm"><span class="whitespace-nowrap text-xs text-slate-500">requests / minute</span></div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="monthly-limit">System-wide monthly token limit</label>
                        <input id="monthly-limit" x-model="monthlyLimit" type="number" min="1" step="1" placeholder="No limit" class="w-full rounded-lg border-slate-300 text-sm">
                        <p class="mt-1 text-xs text-slate-500">Blank means no application-level monthly cap.</p>
                        <p class="mt-1 text-xs text-slate-500">Requests already in progress can complete after the cap is reached.</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">This month</p>
                        <p class="mt-2 text-sm text-slate-700">{{ number_format((int) ($usage->request_count ?? 0)) }} requests</p>
                        <p class="mt-1 text-sm text-slate-700">{{ number_format((int) ($usage->input_tokens ?? 0) + (int) ($usage->output_tokens ?? 0)) }} tokens</p>
                        <p class="mt-2 text-xs text-slate-500">No API cost estimate is shown; verified billing data is not connected.</p>
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Save Configuration</button>
                    </div>
                </div>
            </section>
        </form>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            <span class="font-semibold">Configured by:</span> {{ $configuredBy ?? 'Not configured' }}
            <span class="mx-2 text-slate-300">•</span>
            <span class="font-semibold">Last updated:</span> {{ $lastUpdatedAt?->format('M j, Y g:i A') ?? 'Not updated' }}
            <span class="mx-2 text-slate-300">•</span>
            <span class="font-semibold">Connection status:</span> <span x-text="testStatus ? testStatus.replaceAll('_', ' ') : 'Not tested'"></span>
        </div>
    </div>
</x-app-layout>
