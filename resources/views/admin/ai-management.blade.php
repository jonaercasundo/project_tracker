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
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">Implemented capabilities</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">Module intelligence and tool access</h3><p class="mt-1 text-[10px] text-slate-500">Status combines the global assistant setting, selected company, and current user permissions.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 text-[9px] font-extrabold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Capability</th><th class="px-5 py-3">Module</th><th class="px-5 py-3">Required access</th><th class="px-5 py-3">Current status</th></tr></thead><tbody class="divide-y divide-slate-100">
            @foreach ($tools as $tool)
                @php $available = $tool['access'] === 'Available in current company'; @endphp
                <tr><td class="whitespace-nowrap px-5 py-3.5 font-bold text-slate-800">{{ $tool['name'] }}</td><td class="px-5 py-3.5"><span class="rounded-md px-2 py-1 text-[9px] font-bold {{ $tool['module'] === 'MI' ? 'bg-blue-50 text-blue-800' : 'bg-indigo-50 text-indigo-800' }}">{{ $tool['module'] }}</span></td><td class="px-5 py-3.5 font-mono text-[10px] text-slate-500">{{ $tool['permission_label'] }}</td><td class="px-5 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-bold {{ $available ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ $tool['access'] }}</span></td></tr>
            @endforeach
        </tbody></table></div>
        <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-[10px] leading-relaxed text-slate-500">AI Assistant use does not grant business-data access. Selling prices, quotations, financial records, and workflow approvals remain separate permissions. No tool can approve or modify records autonomously.</div>
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
