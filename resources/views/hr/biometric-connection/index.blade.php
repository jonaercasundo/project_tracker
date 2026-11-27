<x-project_app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('hr.index') }}" class="text-xs font-bold text-blue-700 hover:text-blue-900">← HR workspace</a>
                <p class="mt-4 text-[10px] font-extrabold uppercase tracking-[0.18em] text-blue-700">MMC · Time and attendance</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">ZKTeco G1 connection</h1>
                <p class="mt-1 text-sm text-slate-500">Configure the G1 terminal’s TCP/IP communication settings.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-bold {{ $connection ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $connection ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $connection ? 'Settings saved' : 'Setup needed' }}
            </span>
        </div>

        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif
        @if (is_array(session('connection_test')))
            <div role="{{ session('connection_test.status') === 'success' ? 'status' : 'alert' }}" aria-live="polite" class="rounded-xl border px-4 py-3 text-sm font-medium {{ session('connection_test.status') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
                <p class="font-bold">{{ session('connection_test.status') === 'success' ? 'Connection test passed' : 'Connection test failed' }}</p>
                <p class="mt-1">{{ session('connection_test.message') }}</p>
            </div>
        @endif
        @if (! $storageAvailable)
            <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Connection storage is not ready yet. Apply the pending database migrations to save device details.</div>
        @endif

        <form method="POST" action="{{ route('hr.biometric.update') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
                <section class="border-b border-slate-100 p-5 sm:p-7" aria-labelledby="terminal-heading">
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Zm-2-4h12" /></svg>
                        </div>
                        <div>
                            <h2 id="terminal-heading" class="text-sm font-extrabold text-slate-900">Terminal</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">ZKTeco G1 fingerprint time attendance terminal · TCP/IP</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Device IP address
                            <input name="device_ip" value="{{ old('device_ip', $connection?->device_ip) }}" required inputmode="decimal" autocomplete="off" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. 192.168.1.201">
                            @error('device_ip')<span class="mt-1 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs font-semibold text-slate-700">TCP COMM Port
                            <input type="number" name="tcp_port" value="{{ old('tcp_port', $connection?->tcp_port ?? 4370) }}" required min="1" max="65535" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="mt-1 block font-normal text-slate-500">G1 manual default: 4370. Change it if the terminal uses another port.</span>
                            @error('tcp_port')<span class="mt-1 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Device ID
                            <input type="number" name="device_id" value="{{ old('device_id', $connection?->device_id ?? 1) }}" required min="1" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="mt-1 block font-normal text-slate-500">Use the Machine ID configured on the G1 terminal.</span>
                            @error('device_id')<span class="mt-1 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Serial number <span class="font-normal text-slate-400">(optional)</span>
                            <input name="device_serial" value="{{ old('device_serial', $connection?->device_serial) }}" maxlength="100" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Terminal serial number">
                            @error('device_serial')<span class="mt-1 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Comm Key <span class="font-normal text-slate-400">(optional)</span>
                            <input type="password" name="password" maxlength="255" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ $connection?->comm_key ? 'Saved securely · leave blank to keep current key' : 'Enter the Comm Key set on the terminal' }}">
                            <span class="mt-1 block font-normal text-slate-500">The key must match the G1’s PC Connection Comm Key. Saved keys are encrypted.</span>
                            @error('password')<span class="mt-1 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </section>

                <aside class="border-b border-slate-100 bg-blue-50/60 px-5 py-4 sm:px-7">
                    <h2 class="text-xs font-extrabold text-slate-800">Where to find these settings</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-600">On the G1, open <strong>Menu → Comm. → Ethernet</strong> for the IP address. Check <strong>PC Connection</strong> for the Device ID and Comm Key. Ensure the terminal and the Tracker server can reach each other over the network.</p>
                </aside>

                <div class="flex flex-col gap-3 bg-slate-50/70 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                    <p class="max-w-xl text-xs leading-5 text-slate-500">Test checks whether the Tracker server can reach the G1’s TCP port. Save settings stores the connection details for later use.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" formaction="{{ route('hr.biometric.test') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white px-5 py-2.5 text-xs font-bold text-blue-800 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Test connection</button>
                        <button type="submit" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" @disabled(! $storageAvailable)>Save settings</button>
                    </div>
                </div>
        </form>
    </div>
</x-project_app-layout>
