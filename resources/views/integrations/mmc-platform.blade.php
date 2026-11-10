<x-project_app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operations / Integrations</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">MMC Platform API</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8" x-data="{ showApiKey: false }">
        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif

        @if (is_array($testResult))
            <section role="{{ $testResult['status'] === 'success' ? 'status' : 'alert' }}" aria-live="polite" @class([
                'rounded-xl border px-4 py-4',
                'border-emerald-200 bg-emerald-50 text-emerald-900' => $testResult['status'] === 'success',
                'border-rose-200 bg-rose-50 text-rose-900' => $testResult['status'] !== 'success',
            ])>
                <p class="text-sm font-semibold">{{ $testResult['status'] === 'success' ? 'Connection successful' : 'Connection test failed' }}</p>
                <p class="mt-1 text-sm">{{ $testResult['message'] }}</p>
                @if ($testResult['status'] === 'success')
                    <div class="mt-3 rounded-lg border border-white/70 bg-white/70 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide">Safe program preview</p>
                        @if ($testResult['preview'] !== [])
                            <dl class="mt-2 grid gap-x-4 gap-y-2 sm:grid-cols-2">
                                @foreach ($testResult['preview'] as $field => $value)
                                    <div>
                                        <dt class="text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($field)) }}</dt>
                                        <dd class="break-words text-sm font-medium">{{ is_scalar($value) ? (string) $value : '—' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-1 text-sm">The API is reachable and returned no program preview fields.</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Please review the configuration.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Connection configuration</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-bold',
                            'bg-emerald-50 text-emerald-800' => $connection['configured'],
                            'bg-amber-50 text-amber-800' => ! $connection['configured'],
                        ])>{{ $connection['configured'] ? 'Ready to test' : 'Configuration required' }}</span>
                        <span class="text-sm text-slate-600">{{ $connection['has_key'] ? 'API key configured' : 'No API key configured' }}</span>
                    </div>
                    @if ($connection['reason'])
                        <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $connection['reason'] }}</p>
                    @endif
                </div>
                <p class="text-xs text-slate-500">Key source: {{ ucfirst($connection['credential_source']) }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('integrations.mmc-platform.store') }}" class="space-y-6">
            @csrf
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">API configuration</h2>
                    <p class="mt-1 text-sm text-slate-500">Use an HTTPS API endpoint and a read-only key beginning with <code class="rounded bg-slate-100 px-1">mmc_</code>.</p>
                </div>
                <div class="grid gap-5 p-5 sm:p-6">
                    <div>
                        <label for="base_url" class="mb-1.5 block text-sm font-medium text-slate-700">API Base URL</label>
                        <input id="base_url" name="base_url" type="url" required maxlength="2048" value="{{ old('base_url', $connection['base_url'] ?? '') }}" placeholder="https://platform-host:3000/api/v1" autocomplete="url" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1.5 text-xs text-slate-500">Non-local hosts must use HTTPS. Credentials, query strings, and fragments are not accepted in this URL.</p>
                        @error('base_url')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="read_key" class="mb-1.5 block text-sm font-medium text-slate-700">Read-only API Key</label>
                        <div class="flex gap-2">
                            <input id="read_key" name="read_key" :type="showApiKey ? 'text' : 'password'" maxlength="4096" autocomplete="new-password" autocapitalize="off" spellcheck="false" class="min-w-0 flex-1 rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ $connection['has_key'] ? 'Saved key is hidden; leave blank to keep it' : 'mmc_…' }}">
                            <button type="button" @click="showApiKey = !showApiKey" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" :aria-pressed="showApiKey.toString()" x-text="showApiKey ? 'Hide' : 'Show'"></button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">The saved key is never returned to this page. Leave blank to keep the current key, or enter a replacement.</p>
                        @error('read_key')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="submit" formaction="{{ route('integrations.mmc-platform.test-connection') }}" formnovalidate class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-500 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Test Connection</button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Save Configuration</button>
            </div>
        </form>

        <p class="text-xs leading-5 text-slate-500">The API key is encrypted in the MMC Platform settings table with Laravel's application key. Keep the same <code class="rounded bg-slate-100 px-1">APP_KEY</code> across app servers and backups so the saved credential remains decryptable.</p>
    </div>
</x-project_app-layout>
