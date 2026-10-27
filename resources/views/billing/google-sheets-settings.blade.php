<x-project_app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Billing / Administrator settings</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">Google Sheets Sync</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Please review the settings below.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Connection status</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-bold',
                        'bg-emerald-50 text-emerald-800' => $connection['configured'],
                        'bg-amber-50 text-amber-800' => ! $connection['configured'] && $settings['enabled'],
                        'bg-slate-100 text-slate-700' => ! $settings['enabled'],
                    ])>{{ $connection['configured'] ? 'Credentials configured' : ($settings['enabled'] ? 'Configuration required' : 'Disabled') }}</span>
                    <span class="text-sm text-slate-600">{{ $settings['has_saved_settings'] ? 'Using administrator-saved settings' : 'Using current environment configuration' }}</span>
                </div>
                @unless ($connection['configured'])
                    <p class="mt-2 max-w-2xl text-sm text-amber-800">{{ $connection['reason'] }}</p>
                @endunless
            </div>
            <div class="shrink-0 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p>Credential source: <span class="font-semibold text-slate-800">{{ $settings['has_saved_credentials'] ? 'Encrypted database credential' : ($settings['credential_source'] === 'environment' ? 'Environment fallback' : 'Not configured') }}</span></p>
                <p class="mt-1">Credential value: <span class="font-semibold text-slate-800">{{ $settings['has_credentials'] ? 'Stored and masked' : 'Missing' }}</span></p>
            </div>
        </section>

        <form method="POST" action="{{ route('billing.google-sheets-settings.store') }}" autocomplete="off" class="space-y-6">
            @csrf
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">Spreadsheet connection</h2>
                    <p class="mt-1 text-sm text-slate-500">Saving settings explicitly overrides the environment target for future billing syncs. Existing sync records keep their captured spreadsheet and worksheet.</p>
                </div>
                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div class="sm:col-span-2">
                        <label for="spreadsheet_id" class="mb-1.5 block text-sm font-medium text-slate-700">Google spreadsheet ID</label>
                        <input id="spreadsheet_id" name="spreadsheet_id" type="text" required maxlength="200" pattern="[A-Za-z0-9_-]{10,200}" value="{{ $settings['spreadsheet_id'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" aria-describedby="spreadsheet-help">
                        <p id="spreadsheet-help" class="mt-1.5 text-xs text-slate-500">Use the ID from the spreadsheet URL. The app will not create or substitute a spreadsheet.</p>
                        @error('spreadsheet_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="sheet_tab" class="mb-1.5 block text-sm font-medium text-slate-700">Worksheet / tab name</label>
                        <input id="sheet_tab" name="sheet_tab" type="text" required maxlength="100" value="{{ $settings['sheet_tab'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1.5 text-xs text-slate-500">Default target: <span class="font-medium">Copy of SME LOT 2,9,11</span>.</p>
                        @error('sheet_tab')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="service_account_json" class="mb-1.5 block text-sm font-medium text-slate-700">Service account credentials (JSON)</label>
                        <textarea id="service_account_json" name="service_account_json" rows="6" maxlength="60000" spellcheck="false" autocomplete="new-password" autocapitalize="off" class="w-full rounded-lg border-slate-300 font-mono text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Paste service account JSON only when setting up or replacing credentials"></textarea>
                        <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $settings['has_saved_credentials'] ? 'A saved credential exists. Leave this blank to keep it.' : ($settings['credential_source'] === 'environment' ? 'The environment credential remains active. Leave this blank to keep using it.' : 'No saved or environment credential is configured.') }} New credentials are encrypted at rest and are never shown again.</p>
                        @error('service_account_json')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span><span class="block text-sm font-semibold text-slate-800">Enable billing Google Sheets sync</span><span class="mt-1 block text-xs leading-5 text-slate-500">When disabled, queued sync attempts do not contact Google. This setting does not change any existing spreadsheet values.</span></span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-base font-semibold text-slate-900">Billing date mapping</h2>
                    <p class="mt-1 text-sm text-slate-500">The integration only writes the recorded For Billing transition date after a unique lot, BIR DR, and Core Package Code match; populated cells are left unchanged.</p>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-3 sm:p-6">
                    @foreach ([['Lot 2', 'BF'], ['Lot 9', 'BU'], ['Lot 11', 'CI']] as [$lot, $column])
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $lot }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">Match the lot-specific BIR DR and Core Package Code, then write the transition date to <span class="font-mono font-bold text-slate-800">{{ $column }}</span>.</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="submit" form="test-google-sheets-connection" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-500 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Test Connection</button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Save Settings</button>
            </div>
        </form>

        <form id="test-google-sheets-connection" method="POST" action="{{ route('billing.google-sheets-settings.test-connection') }}" class="hidden">
            @csrf
        </form>
        <p class="text-xs leading-5 text-slate-500">Save settings before testing. Connection tests read worksheet cell A1 only; they do not write to the live Google Sheet. Laravel encryption uses the application key, so retain the same <code class="rounded bg-slate-100 px-1">APP_KEY</code> across app servers and backups.</p>
    </div>
</x-project_app-layout>
