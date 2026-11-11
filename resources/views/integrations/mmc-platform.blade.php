<x-project_app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operations / Integrations</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">Third Party API Key Management</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8" x-data="{ showApiKey: false, previewBusy: false, programs: [], selectedProgram: '', sampleLimit: 25, result: null, connectionState: '', connectionMessage: '' }">
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

        <nav class="-mx-4 flex gap-1 overflow-x-auto border-b border-slate-200 px-4 sm:mx-0 sm:px-0" aria-label="Third party API key management sections">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('integrations.mmc-platform.index', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif
                    class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition {{ $tab === $key ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @if ($tab === 'overview')
            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="overview-title">
                <div>
                    <h2 id="overview-title" class="text-lg font-semibold text-slate-900">MMC Platform connection overview</h2>
                    <p class="mt-1 text-sm text-slate-500">Review the saved read-only connection and open a section to manage access or inspect integration details.</p>
                </div>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Connection</dt>
                        <dd class="mt-2 text-sm font-semibold {{ $connection['configured'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $connection['configured'] ? 'Ready to test' : 'Configuration required' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">API key</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-800">{{ $connection['has_key'] ? 'Configured · hidden' : 'Not configured' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Key source</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-800">{{ ucfirst($connection['credential_source']) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transport</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-800">{{ $connection['secure'] ? 'HTTPS' : 'Not verified' }}</dd>
                    </div>
                </dl>
                @if ($connection['reason'])
                    <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $connection['reason'] }}</p>
                @endif
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-950">
                    This page manages the MMC Platform read-only connection and preview. Data synchronization and reconciliation are not configured; the guide documents polling endpoints, not webhooks.
                </div>
            </section>
        @elseif ($tab === 'partners')
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="partners-title">
                <h2 id="partners-title" class="text-lg font-semibold text-slate-900">Partners</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">No partner registry is connected to MMC Platform API settings yet. The current integration stores one MMC endpoint and its read-only key.</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">Partner credentials should be added here only when the partner ownership model, scopes, rotation process, and storage requirements are defined.</p>
            </section>
        @elseif ($tab === 'api-webhooks')
            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="api-title">
                <div>
                    <h2 id="api-title" class="text-lg font-semibold text-slate-900">API &amp; Webhooks</h2>
                    <p class="mt-1 text-sm text-slate-500">Known MMC Platform read endpoints from the current API mapping.</p>
                </div>
                <div class="space-y-3 text-sm leading-6 text-slate-700">
                    <p><code class="rounded bg-slate-100 px-1.5 py-0.5">GET /api/v1/programs</code> lists programs. The connection test checks this endpoint with a one-record limit.</p>
                    <p><code class="rounded bg-slate-100 px-1.5 py-0.5">GET /api/v1/programs/{program_id}/master-list</code> returns paginated delivery rows; the guide documents a maximum page limit of 2,000.</p>
                    <p>Authentication uses a read-scoped <code class="rounded bg-slate-100 px-1.5 py-0.5">mmc_</code> bearer key. The API guide does not document webhook events or signature validation; polling is the documented approach.</p>
                </div>
                <section class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5" aria-labelledby="data-preview-title">
                    <div>
                        <h3 id="data-preview-title" class="text-base font-semibold text-slate-900">Data Preview</h3>
                        <p class="mt-1 text-sm text-slate-600">Read-only sample from MMC Platform. Previewed records are not saved to Tracker.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" @click="previewBusy = true; connectionState = 'loading'; connectionMessage = 'Checking GET /api/v1/programs…'; fetch('{{ route('integrations.mmc-platform.connection-check') }}', {headers:{'Accept':'application/json'}}).then(r => r.json()).then(async d => { if (d.status !== 'success') { connectionState = 'error'; connectionMessage = d.message; return; } connectionState = 'success'; connectionMessage = 'Connection successful. Loading programs…'; const p = await fetch('{{ route('integrations.mmc-platform.programs') }}', {headers:{'Accept':'application/json'}}).then(r => r.json()); if (p.status !== 'success') { connectionMessage = `Connection successful. Programs could not be loaded: ${p.message}`; return; } programs = p.programs; if (!programs.length) { connectionMessage = 'Connection successful, but the endpoint returned no programs.'; } else { selectedProgram = String(programs[0].id); connectionMessage = 'Connection successful. Select a program to preview its data.'; } }).catch(() => { connectionState = 'error'; connectionMessage = 'Could not contact Tracker preview service. Please try again.'; }).finally(() => previewBusy = false)" :disabled="previewBusy" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Test Connection</button>
                        <span x-show="connectionState" :role="connectionState === 'error' ? 'alert' : 'status'" class="text-sm" :class="connectionState === 'error' ? 'text-rose-700' : (connectionState === 'success' ? 'font-semibold text-emerald-700' : 'text-slate-600')" x-text="connectionMessage"></span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_150px_auto] sm:items-end">
                        <div>
                            <label for="preview-program" class="mb-1 block text-sm font-medium text-slate-700">Program</label>
                            <select id="preview-program" x-model="selectedProgram" :disabled="!programs.length || previewBusy" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">Choose a program</option>
                                <template x-for="program in programs" :key="program.id"><option :value="String(program.id)" x-text="`${program.name || 'Program'} (ID: ${program.id})`"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="preview-limit" class="mb-1 block text-sm font-medium text-slate-700">Sample records</label>
                            <input id="preview-limit" type="number" min="1" max="100" x-model.number="sampleLimit" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <button type="button" @click="previewBusy = true; result = null; fetch('{{ route('integrations.mmc-platform.preview-data') }}', {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content || ''}, body:JSON.stringify({program_id:selectedProgram, limit:sampleLimit})}).then(r => r.json()).then(d => result = d).catch(() => result = {status:'failed',message:'Could not contact Tracker preview service. Please try again.'}).finally(() => previewBusy = false)" :disabled="previewBusy || !selectedProgram || sampleLimit < 1 || sampleLimit > 100" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-700 disabled:opacity-50">Preview Data</button>
                    </div>
                    <p x-show="previewBusy" role="status" class="text-sm text-slate-600">Retrieving preview…</p>
                    <template x-if="result">
                        <div class="space-y-4 rounded-lg border bg-white p-4" :class="result.status === 'success' ? 'border-emerald-200' : 'border-rose-200'">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold" :class="result.status === 'success' ? 'text-emerald-800' : 'text-rose-800'" x-text="result.status === 'success' ? (result.records.length ? 'Data retrieved' : 'No data returned') : 'Preview failed'"></p>
                                <span class="text-sm text-slate-600" x-text="`Records returned: ${result.records?.length || 0}`"></span>
                            </div>
                            <p class="text-sm text-slate-700" x-text="result.message"></p>
                            <p class="text-xs text-slate-500" x-text="`Selected program: ${programs.find(program => String(program.id) === selectedProgram)?.name || selectedProgram} · Retrieved: ${result.retrieved_at || '—'}`"></p>
                            <div x-show="result.status === 'success' && result.records.length" class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                    <thead class="bg-slate-50"><tr><template x-for="field in Object.keys(result.records[0] || {})" :key="field"><th scope="col" class="whitespace-nowrap px-3 py-2 font-semibold text-slate-700" x-text="field"></th></template></tr></thead>
                                    <tbody class="divide-y divide-slate-100"><template x-for="(record, rowIndex) in result.records" :key="rowIndex"><tr><template x-for="field in Object.keys(result.records[0] || {})" :key="field"><td class="max-w-xs break-words px-3 py-2 align-top text-slate-700" x-text="typeof record[field] === 'object' && record[field] !== null ? JSON.stringify(record[field]) : (record[field] ?? '—')"></td></template></tr></template></tbody>
                                </table>
                            </div>
                            <details x-show="result.raw !== undefined && result.raw !== null" class="rounded-lg border border-slate-200 p-3">
                                <summary class="cursor-pointer text-sm font-semibold text-slate-700">Inspect safe raw response</summary>
                                <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-all rounded bg-slate-950 p-3 text-xs text-slate-100" x-text="JSON.stringify(result.raw, null, 2)"></pre>
                                <p class="mt-2 text-xs text-slate-500">Authorization headers are never included; credential-like response fields are redacted.</p>
                            </details>
                        </div>
                    </template>
                </section>
            </section>
        @elseif ($tab === 'reconciliation')
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="reconciliation-title">
                <h2 id="reconciliation-title" class="text-lg font-semibold text-slate-900">Reconciliation</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">No MMC Platform reconciliation runs are available. The connection test does not import or change Tracker records.</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">A reconciliation workflow depends on an approved field mapping, stable external identifiers, deletion rules, and sync persistence. These decisions are recorded in the MMC Tracker API mapping document.</p>
            </section>
        @elseif ($tab === 'audit-logs')
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="audit-title">
                <div>
                    <h2 id="audit-title" class="text-lg font-semibold text-slate-900">Audit Logs</h2>
                    <p class="mt-1 text-sm text-slate-500">MMC Platform connection configuration changes recorded in Tracker.</p>
                </div>
                @if (! $auditStorageAvailable)
                    <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">Audit log storage is not available.</p>
                @elseif ($auditRows->isEmpty())
                    <p class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">No MMC Platform configuration changes have been recorded.</p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-3 font-semibold">Date</th><th class="px-4 py-3 font-semibold">Event</th><th class="px-4 py-3 font-semibold">Actor ID</th><th class="px-4 py-3 font-semibold">Summary</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($auditRows as $auditRow)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($auditRow->created_at)->format('M j, Y g:i A') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700">{{ $auditRow->event }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $auditRow->actor_user_id ?? 'System' }}</td>
                                        <td class="min-w-64 px-4 py-3 text-slate-700">{{ $auditRow->summary }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $auditRows->links() }}
                @endif
            </section>
        @endif

        @if ($tab === 'integration')

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
        @endif
    </div>
</x-project_app-layout>
