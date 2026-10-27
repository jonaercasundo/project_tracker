<x-app-layout>
<div class="mx-auto flex max-w-[1500px] flex-col gap-5">
    <section class="relative overflow-hidden rounded-2xl border border-[#dce5f0] bg-white p-5 shadow-sm sm:p-6">
        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-violet-700 via-blue-600 to-cyan-400"></div>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="text-[10px] font-extrabold uppercase tracking-[.15em] text-violet-700">AI management</p><h2 class="mt-1 text-xl font-extrabold tracking-tight text-[#172a45]">Assistant capabilities and usage</h2><p class="mt-1.5 max-w-2xl text-xs leading-relaxed text-slate-500">System-wide provider configuration with company and permission checks still applied to each assistant request.</p></div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.access.permissions.ai') }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-[10px] font-bold text-slate-700 hover:bg-slate-50">Review AI permissions</a>
                @can('ai.settings.manage')<a href="{{ route('admin.ai-configuration.index') }}" class="rounded-xl bg-[#174b8a] px-3.5 py-2.5 text-[10px] font-bold text-white hover:bg-[#123b72]">OpenAI configuration</a>@endcan
            </div>
        </div>
    </section>

    <section aria-label="AI service status" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-200/80 bg-white p-4"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Service</p><p class="mt-2 text-sm font-extrabold text-slate-800">{{ $enabled ? 'Enabled' : 'Disabled' }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $enabled ? 'Global assistant switch is on' : 'Global assistant switch is off' }}</p></article>
        <article class="rounded-xl border border-slate-200/80 bg-white p-4"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Credential source</p><p class="mt-2 text-sm font-extrabold text-slate-800">{{ $credentialSource === 'database' ? 'Database managed' : ($credentialSource === 'environment' ? 'Environment fallback' : 'Not configured') }}</p><p class="mt-1 text-[10px] text-slate-500">Credential value is never shown here.</p></article>
        <article class="rounded-xl border border-slate-200/80 bg-white p-4"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Configured model</p><p class="mt-2 break-all text-sm font-extrabold text-slate-800">{{ $model ?: 'No model selected' }}</p><p class="mt-1 text-[10px] text-slate-500">OpenAI Responses API</p></article>
        <article class="rounded-xl border border-slate-200/80 bg-white p-4"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Current company</p><p class="mt-2 text-sm font-extrabold text-slate-800">{{ $company?->name ?? 'No company selected' }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $company?->code ?? 'Company-scoped tools unavailable' }}</p></article>
    </section>

    <section id="usage-monitoring" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">System-wide · this month</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">Usage and token monitoring</h3></div><p class="text-[10px] text-slate-400">Estimated API cost: unavailable; verified billing data is not connected.</p></div>
        @if ($usage->request_count === null)
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-5 text-xs text-slate-500">Usage statistics are not installed in this environment.</div>
        @else
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div class="rounded-xl bg-[#f5f8fc] p-4"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Completed requests</p><p class="mt-1.5 text-2xl font-extrabold text-[#172a45]">{{ number_format($usage->request_count) }}</p></div>
                <div class="rounded-xl bg-[#f5f8fc] p-4"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Input tokens</p><p class="mt-1.5 text-2xl font-extrabold text-[#172a45]">{{ number_format($usage->input_tokens) }}</p></div>
                <div class="rounded-xl bg-[#f5f8fc] p-4"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Output tokens</p><p class="mt-1.5 text-2xl font-extrabold text-[#172a45]">{{ number_format($usage->output_tokens) }}</p></div>
            </div>
        @endif
    </section>

    <section id="tool-access" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">AI capability registry</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">What the assistant can do</h3><p class="mt-1 text-[10px] text-slate-500">Status reflects implemented handlers, the global AI setting, selected company and your business permissions.</p></div>
            <a href="{{ route('admin.access.permissions.ai') }}" class="shrink-0 text-[10px] font-bold text-blue-700 hover:text-blue-900">Manage role permissions <span aria-hidden="true">→</span></a>
        </div>
        <div class="grid grid-cols-1 gap-x-8 px-4 py-2 sm:grid-cols-2 sm:px-6">
            @foreach ($capabilityGroups as $category)
                <article class="min-w-0 border-b border-slate-100 py-4 last:border-0 sm:[&:nth-last-child(2)]:border-0">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                @switch($category['icon'])
                                    @case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5M7.5 10.5h6m-3-3v6"/>@break
                                    @case('create')<circle cx="12" cy="12" r="9"/><path d="M12 8v8m-4-4h8"/>@break
                                    @case('edit')<path d="m15 5 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16zM13 20h7"/>@break
                                    @case('pdf')<path d="M6 3h8l5 5v13H6zM14 3v5h5M9 14h6m-6 3h6"/>@break
                                    @case('excel')<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11m6-11v11M5 12l2 3-2 3m4-6-2 3 2 3"/>@break
                                    @case('analytics')<path d="M4 20V11m5 9V5m5 15v-7m5 7V3M2 20h20"/>@break
                                    @case('forecast')<path d="M4 18 9 12l4 3 7-9M15 6h5v5M5 5h.01M5 9h.01M5 13h.01"/>@break
                                    @case('document')<path d="M6 3h8l5 5v13H6zM14 3v5h5m-9 5h4m-4 4h6"/><circle cx="9" cy="13" r="3"/>@break
                                    @case('help')<path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3m.1 4h.01"/><circle cx="12" cy="12" r="9"/>@break
                                    @case('workflow')<rect x="3" y="4" width="7" height="6" rx="1"/><rect x="14" y="14" width="7" height="6" rx="1"/><path d="M10 7h3a2 2 0 0 1 2 2v5M12 14l3 3 3-3"/>@break
                                    @default<path d="M20 11.5a7.5 7.5 0 0 1-8 7.5 8.6 8.6 0 0 1-3.5-.7L4 20l1.4-3.4A7.3 7.3 0 0 1 4 12c0-4.1 3.6-7.5 8-7.5s8 3.1 8 7zM8 12h.01M12 12h.01M16 12h.01"/>
                                @endswitch
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-xs font-bold text-slate-900">{{ $category['label'] }}</h4>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-semibold text-slate-500">{{ $category['implemented_count'] }} implemented · {{ $category['available_count'] }} available here</span>
                            </div>
                            <p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $category['subtitle'] }}</p>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 pl-12">
                        @foreach ($category['capabilities'] as $capability)
                            @php
                                $statusTone = match ($capability['status']) {
                                    'Implemented' => 'bg-emerald-50 text-emerald-800',
                                    'Partially Implemented' => 'bg-blue-50 text-blue-800',
                                    'Not yet implemented', 'Not Implemented' => 'bg-slate-100 text-slate-600',
                                    'Disabled', 'Configuration Required' => 'bg-amber-50 text-amber-800',
                                    'Permission Restricted', 'Company Restricted', 'Business Access Restricted' => 'bg-rose-50 text-rose-800',
                                    default => 'bg-rose-50 text-rose-800',
                                };
                            @endphp
                            <div class="rounded-lg bg-slate-50/80 px-3 py-2.5">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0"><p class="text-[11px] font-semibold text-slate-800">{{ $capability['label'] }}</p><p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $capability['description'] }}</p></div>
                                    <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold {{ $statusTone }}">{{ $capability['status'] }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[9px] text-slate-400">
                                    <span>{{ $capability['company'] === '*' ? 'Cross-module' : $capability['company'] }}</span>
                                    <span>{{ $capability['operation'] }}</span>
                                    <span>Risk: {{ str_replace('_', ' ', $capability['risk']) }}</span>
                                    <span>{{ $capability['confirmation'] === 'explicit_confirmation' ? 'Confirmation required' : 'No write action' }}</span>
                                </div>
                                <p class="mt-1 break-words text-[9px] text-slate-400">Requires: {{ $capability['permission_label'] }}{{ $capability['roles'] !== [] ? ' · Roles: '.implode(', ', $capability['roles']) : '' }}</p>
                                @if ($capability['status'] !== 'Implemented' && $capability['status'] !== 'Partially Implemented')
                                    <p class="mt-1 rounded-md bg-amber-50 px-2 py-1.5 text-[9px] leading-relaxed text-amber-900"><span class="font-bold">Unavailable because:</span> {{ $capability['diagnostic_reason'] }}
                                    </p>
                                @endif
                                <details class="mt-2 text-[9px] text-slate-500">
                                    <summary class="cursor-pointer font-semibold hover:text-blue-700">Handler, audit and data contract</summary>
                                    <div class="mt-2 grid gap-2 lg:grid-cols-2">
                                        <div><p class="font-bold text-slate-600">Handler</p><p class="mt-0.5 break-all">{{ $capability['handler'] ?? 'Not connected' }}</p><p class="mt-1">Audit: {{ $capability['audit'] }}</p></div>
                                        <div><p class="font-bold text-slate-600">Input schema</p><pre class="mt-1 max-h-28 overflow-auto whitespace-pre-wrap break-words rounded-md bg-white p-2 font-mono text-[8px]">{{ json_encode($capability['input_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                        <div class="lg:col-span-2"><p class="font-bold text-slate-600">Output schema</p><pre class="mt-1 max-h-28 overflow-auto whitespace-pre-wrap break-words rounded-md bg-white p-2 font-mono text-[8px]">{{ json_encode($capability['output_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
        <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-[10px] leading-relaxed text-slate-500">AI Assistant use never grants business-data access by itself. Company membership, business permissions, role checks and existing module rules still apply. No AI tool can approve or modify records autonomously.</div>
    </section>

    <section id="failed-requests" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6"><div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">AI activity</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">Recent failed requests</h3><p class="mt-1 text-[10px] text-slate-500">Only status and non-sensitive request metadata are shown; prompts and credentials are excluded.</p></div><a href="{{ route('admin.audit.index') }}" class="shrink-0 text-[10px] font-bold text-blue-700 hover:text-blue-900">Audit log →</a></div>
        <div class="divide-y divide-slate-100 px-5 sm:px-6">
            @forelse($failures as $failure)
                <div class="flex flex-wrap items-center justify-between gap-2 py-3"><div><p class="text-xs font-semibold text-slate-700">{{ $failure->user_name ?? 'Unknown user' }} · {{ $failure->company_code ?? 'Company unavailable' }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $failure->model ?: 'Model unavailable' }} · {{ \Illuminate\Support\Carbon::parse($failure->created_at)->format('M j, Y g:i A') }}</p></div><span class="rounded-full bg-rose-50 px-2.5 py-1 text-[9px] font-bold text-rose-800">Failed</span></div>
            @empty
                <p class="py-8 text-center text-xs text-slate-500">No failed AI requests recorded.</p>
            @endforelse
        </div>
    </section>
</div>
</x-app-layout>
