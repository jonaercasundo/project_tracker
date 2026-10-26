<div class="mx-auto flex max-w-[1500px] flex-col gap-6">
    <section class="relative overflow-hidden rounded-2xl border border-[#dce5f0] bg-white p-5 shadow-[0_10px_30px_-28px_rgba(15,35,65,.55)] sm:p-7">
        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-blue-700 via-blue-500 to-cyan-400"></div>
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="max-w-2xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-blue-800">System administration</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">{{ $currentCompany?->code ?? 'Global scope' }}</span>
                </div>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-[#14243b] sm:text-[28px]">A clear view of access and operations.</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">Welcome, {{ auth()->user()->name }}. Review the people, company access, and services that keep Project Tracker running.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('roleaccess.index', ['add' => 1]) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#174b8a] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#123b72] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>Add user
                </a>
                <a href="{{ route('admin.access.permissions.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Review permissions</a>
            </div>
        </div>
    </section>

    <section aria-label="Administrator summary" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @php
            $summaryCards = [
                ['label' => 'Total users', 'value' => number_format($totalUsers), 'note' => 'All system accounts', 'color' => 'blue'],
                ['label' => 'Active users', 'value' => $activeUsers === null ? '—' : number_format($activeUsers), 'note' => $activeUsers === null ? 'Status tracking unavailable' : 'Allowed to sign in', 'color' => 'teal'],
                ['label' => 'Administrators', 'value' => number_format($administratorCount), 'note' => 'System-wide roles', 'color' => 'indigo'],
                ['label' => 'Companies', 'value' => number_format($companyCount), 'note' => 'Registered scopes', 'color' => 'cyan'],
                ['label' => 'Roles', 'value' => number_format($roleCount), 'note' => 'Web guard', 'color' => 'slate'],
                ['label' => 'AI service', 'value' => $aiConfigured ? ($aiEnabled ? 'Enabled' : 'Disabled') : 'Not set up', 'note' => $aiModel ?: 'No model selected', 'color' => $aiConfigured && $aiEnabled ? 'green' : 'amber'],
            ];
        @endphp
        @foreach ($summaryCards as $card)
            <article class="min-w-0 rounded-xl border border-slate-200/80 bg-white p-4 shadow-[0_8px_24px_-24px_rgba(15,35,65,.6)] sm:p-4.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $card['label'] }}</span>
                    <span @class(['h-2 w-2 shrink-0 rounded-full', 'bg-blue-500' => $card['color'] === 'blue', 'bg-teal-500' => $card['color'] === 'teal', 'bg-indigo-500' => $card['color'] === 'indigo', 'bg-cyan-500' => $card['color'] === 'cyan', 'bg-slate-400' => $card['color'] === 'slate', 'bg-emerald-500' => $card['color'] === 'green', 'bg-amber-500' => $card['color'] === 'amber'])></span>
                </div>
                <p class="mt-3 truncate text-xl font-extrabold tracking-tight text-[#172a45]">{{ $card['value'] }}</p>
                <p class="mt-1 truncate text-[10px] text-slate-400">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-label="Administrator quick actions" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @php
            $actions = [
                ['label' => 'Manage users', 'detail' => 'Roles, companies, and status', 'url' => route('roleaccess.index'), 'symbol' => 'U'],
                ['label' => 'Role permissions', 'detail' => 'Review the access matrix', 'url' => route('admin.access.permissions.index'), 'symbol' => 'R'],
                ['label' => 'Companies', 'detail' => 'Manage company scopes', 'url' => route('companies.index'), 'symbol' => 'C'],
                ['label' => 'JARVIS API', 'detail' => 'Manage the separate token', 'url' => route('integrations.jarvis.index'), 'symbol' => 'J'],
                ['label' => 'AI management', 'detail' => 'Tools, usage, and failures', 'url' => route('admin.ai-management.index'), 'symbol' => 'AI'],
            ];
        @endphp
        @foreach ($actions as $action)
            <a href="{{ $action['url'] }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200/80 bg-white px-3.5 py-3 transition duration-150 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 motion-reduce:transition-none">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#edf4fc] text-[11px] font-extrabold text-[#174b8a] group-hover:bg-[#174b8a] group-hover:text-white">{{ $action['symbol'] }}</span>
                <span class="min-w-0"><span class="block truncate text-xs font-bold text-slate-800">{{ $action['label'] }}</span><span class="mt-0.5 block truncate text-[10px] text-slate-400">{{ $action['detail'] }}</span></span>
            </a>
        @endforeach
        @can('ai.settings.manage')
            <a href="{{ route('admin.ai-configuration.index') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-violet-100 bg-white px-3.5 py-3 transition duration-150 hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500 motion-reduce:transition-none">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-[11px] font-extrabold text-violet-800 group-hover:bg-violet-700 group-hover:text-white">AI</span>
                <span class="min-w-0"><span class="block truncate text-xs font-bold text-slate-800">AI Configuration</span><span class="mt-0.5 block truncate text-[10px] text-slate-400">Provider, model, and access</span></span>
            </a>
        @endcan
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_10px_30px_-28px_rgba(15,35,65,.55)]">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">System activity</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">Recent administration events</h3></div>
                <a href="{{ route('admin.audit.index') }}" class="shrink-0 text-[11px] font-bold text-blue-700 hover:text-blue-900">Open activity log <span aria-hidden="true">→</span></a>
            </div>
            <div class="divide-y divide-slate-100 px-5 sm:px-6">
                @forelse ($recentActivity as $activity)
                    <div class="flex items-start gap-3 py-3.5">
                        <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[9px] font-extrabold text-slate-600">{{ strtoupper(substr(str_replace('.', ' ', $activity->event), 0, 1)) }}</span>
                        <div class="min-w-0 flex-1"><p class="text-xs font-semibold leading-relaxed text-slate-700">{{ $activity->summary }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $activity->actor_name ?? 'System' }}{{ $activity->company_id ? ' · company #'.$activity->company_id : '' }} · {{ \Illuminate\Support\Carbon::parse($activity->created_at)->diffForHumans() }}</p></div>
                        <span class="hidden shrink-0 rounded-md bg-slate-50 px-2 py-1 text-[9px] font-semibold text-slate-500 sm:inline-flex">{{ $activity->event }}</span>
                    </div>
                @empty
                    <div class="py-10 text-center"><p class="text-xs font-semibold text-slate-600">No recorded activity yet</p><p class="mt-1 text-[10px] text-slate-400">Access changes and sign-ins will appear here.</p></div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_10px_30px_-28px_rgba(15,35,65,.55)] sm:p-6">
            <div class="flex items-start justify-between gap-3"><div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">Needs attention</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">Administration tasks</h3></div><span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-800">{{ $pendingTasks->count() }} open</span></div>
            <div class="mt-4 space-y-2.5">
                @forelse ($pendingTasks as $task)
                    <a href="{{ $task['url'] }}" class="flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/70 p-3 transition hover:border-amber-200 hover:bg-amber-50">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10 3h4l8 17H2l8-17z"/></svg></span>
                        <span><span class="block text-xs font-bold text-slate-800">{{ $task['title'] }}</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-500">{{ $task['detail'] }}</span></span>
                    </a>
                @empty
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-6 text-center"><p class="text-xs font-semibold text-slate-700">No pending setup tasks</p><p class="mt-1 text-[10px] text-slate-400">Configuration checks are current.</p></div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <section id="system-health" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_10px_30px_-28px_rgba(15,35,65,.55)] sm:p-6">
            <div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">Health checks</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">System health</h3><p class="mt-1 text-[10px] text-slate-400">Signals checked when this page loaded.</p></div>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-3"><span class="text-xs font-semibold text-slate-700">Application database</span><span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $databaseHealthy ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $databaseHealthy ? 'Connected' : 'Unavailable' }}</span></div>
                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-3"><span class="text-xs font-semibold text-slate-700">OpenAI configuration</span><span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $aiConfigured && $aiEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ! $aiConfigured ? 'Key required' : ($aiEnabled ? 'Enabled' : 'Disabled') }}</span></div>
            </div>
        </section>

        <section id="usage-monitoring" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_10px_30px_-28px_rgba(15,35,65,.55)] sm:p-6">
            <div class="flex items-start justify-between gap-3"><div><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">This month</p><h3 class="mt-1 text-sm font-bold text-[#172a45]">AI usage summary</h3></div><a href="{{ route('admin.ai-management.index') }}#usage-monitoring" class="text-[11px] font-bold text-blue-700 hover:text-blue-900">Details →</a></div>
            @if ($usage->request_count === null)
                <p class="mt-4 rounded-xl bg-slate-50 px-4 py-4 text-xs text-slate-500">AI usage records are not available until the assistant tables are installed.</p>
            @else
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Requests</p><p class="mt-1 text-lg font-extrabold text-slate-800">{{ number_format($usage->request_count) }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Input tokens</p><p class="mt-1 text-lg font-extrabold text-slate-800">{{ number_format($usage->input_tokens) }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Output tokens</p><p class="mt-1 text-lg font-extrabold text-slate-800">{{ number_format($usage->output_tokens) }}</p></div>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">Usage counts are system-wide. No verified billing data is connected.</p>
            @endif
        </section>
    </div>
</div>
