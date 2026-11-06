<x-project_app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operations / Integrations</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">MMC Platform API</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
        @endif

        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Connection configuration</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-bold',
                        'bg-emerald-50 text-emerald-800' => $connection['configured'],
                        'bg-amber-50 text-amber-800' => ! $connection['configured'],
                    ])>{{ $connection['configured'] ? 'Ready to test' : 'Configuration required' }}</span>
                    @if ($connection['base_url'])
                        <span class="break-all text-sm text-slate-600">{{ $connection['base_url'] }}</span>
                    @endif
                </div>
                @if ($connection['reason'])
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $connection['reason'] }}</p>
                @endif
            </div>
            @if ($connection['configured'])
                    <form method="POST" action="{{ route('integrations.mmc-platform.test-connection') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Test read access</button>
                </form>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-slate-900">Server setup</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">Set these values in the Tracker server environment, then reload Laravel configuration. The API key is never displayed or stored in the database.</p>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">MMC_PLATFORM_API_BASE_URL</dt>
                    <dd class="mt-2 break-all font-mono text-sm text-slate-800">https://&lt;platform-host&gt;:3000/api/v1</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">MMC_PLATFORM_READ_KEY</dt>
                    <dd class="mt-2 font-mono text-sm text-slate-800">mmc_…</dd>
                </div>
            </dl>
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                The platform guide says its current service listens only on its own PC. The Tracker server needs a network route to that host and HTTPS before the read key is sent. Do not use a non-loopback HTTP URL.
            </div>
        </section>

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-blue-950">Read-only connection boundary</h2>
            <p class="mt-1 text-sm leading-6 text-blue-900">The test calls <code class="rounded bg-white/70 px-1">GET /programs?limit=1</code> only. No records are imported or changed yet. The platform contract provides cursor feeds at <code class="rounded bg-white/70 px-1">/sync/lots</code>, <code class="rounded bg-white/70 px-1">/sync/schools</code>, and <code class="rounded bg-white/70 px-1">/sync/deliveries</code>; the data sync must preserve each feed cursor only after its page is durably applied.</p>
        </section>
    </div>
</x-project_app-layout>
