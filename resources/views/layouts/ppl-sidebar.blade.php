@php
    $currentUser = Auth::user();
    $currentCompany = $currentUser?->currentCompany();
    $companyId = $currentCompany?->company_id;
    $financialPlansReady = \Illuminate\Support\Facades\Schema::hasTable('project_financial_plans')
        && \Illuminate\Support\Facades\Schema::hasColumn('project_financial_plans', 'deleted_at');

    $sections = [
        [
            'title' => 'Overview',
            'items' => [
                ['label' => 'PPL Dashboard', 'route' => 'finance.ppl.dashboard', 'url' => route('finance.ppl.dashboard'), 'icon' => 'dashboard', 'view' => null],
                ['label' => 'PPL Forms', 'route' => 'ppl_forms.*', 'url' => route('ppl_forms.index'), 'icon' => 'forms', 'view' => null],
            ],
        ],
        [
            'title' => 'Financial Planning',
            'items' => [
                ['label' => 'Excel Import & Preview', 'route' => 'finance.ppl-import.*', 'url' => route('finance.ppl-import.index'), 'icon' => 'import', 'view' => null],
                ['label' => 'Project Source Mapping', 'route' => 'finance.ppl.sources.*', 'url' => route('finance.ppl.sources.index'), 'icon' => 'link', 'view' => null],
                ['label' => 'Cost & Financial Planning', 'route' => 'finance.plans.*', 'url' => $financialPlansReady ? route('finance.plans.index') : route('finance.ppl.dashboard', ['view' => 'schema']).'#schema', 'icon' => 'calculator', 'view' => $financialPlansReady ? null : 'schema'],
                ['label' => 'Plan Versions & History', 'route' => 'finance.ppl.analytics', 'url' => route('finance.ppl.analytics', ['view' => 'history']).'#history', 'icon' => 'history', 'view' => 'history'],
            ],
        ],
        [
            'title' => 'Monitoring & Intelligence',
            'items' => [
                ['label' => 'PPL Monitoring', 'route' => 'finance.ppl.dashboard', 'url' => route('finance.ppl.dashboard', ['view' => 'monitoring']).'#monitoring', 'icon' => 'chart', 'view' => 'monitoring'],
                ['label' => 'Financial Variance Analysis', 'route' => 'finance.ppl.analytics', 'url' => route('finance.ppl.analytics', ['view' => 'variance']).'#variance', 'icon' => 'variance', 'view' => 'variance'],
                ['label' => 'PPL Reports', 'route' => 'finance.ppl.analytics', 'url' => route('finance.ppl.analytics', ['view' => 'reports']).'#reports', 'icon' => 'report', 'view' => 'reports'],
                ['label' => 'AI Analytics & Chart Studio', 'route' => 'finance.ppl.analytics', 'url' => route('finance.ppl.analytics'), 'icon' => 'spark', 'view' => null],
            ],
        ],
    ];
@endphp

<div class="flex h-full flex-col select-none bg-white">
    <div class="flex h-16 shrink-0 items-center border-b border-slate-100 px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 shadow-sm shadow-emerald-500/20">
                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 6-7" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[12px] font-extrabold uppercase tracking-wider text-slate-900">MMC PPL Management</p>
                <p class="mt-0.5 truncate text-[11px] font-medium text-slate-500">Financial Planning &amp; Monitoring</p>
            </div>
        </div>
    </div>

    @if ($currentUser && $currentUser->companies->count() > 0)
        <div class="shrink-0 border-b border-slate-100 bg-slate-50/40 px-4 py-3">
            <div class="mb-2 flex items-center gap-2">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Current Company</span>
            </div>
            <form method="POST" action="{{ route('company.switch') }}">
                @csrf
                <select name="company_id" onchange="this.form.submit()" class="w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 pr-8 text-xs font-bold text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                    @foreach ($currentUser->companies->where('is_active', true) as $company)
                        <option value="{{ $company->company_id }}" @selected($companyId == $company->company_id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    @endif

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden p-4" aria-label="PPL Management">
        <a href="{{ route('finance.dashboard') }}" class="group mb-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-500 transition-all duration-150 hover:bg-slate-50 hover:text-slate-900">
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-colors group-hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18" />
            </svg>
            <span>Back to Finance Dashboard</span>
        </a>

        @foreach ($sections as $section)
            <div class="px-3 pb-1 pt-3 text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $section['title'] }}</div>
            <div class="space-y-0.5">
                @foreach ($section['items'] as $item)
                    @php
                        $active = request()->routeIs($item['route']) && request()->query('view') === $item['view'];
                        $iconClass = 'h-4 w-4 shrink-0 transition-colors '.($active ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600');
                    @endphp
                    <a href="{{ $item['url'] }}" @if ($active) aria-current="page" @endif class="group relative flex items-center gap-3 overflow-hidden rounded-xl px-3 py-2.5 text-xs font-bold transition-all duration-150 {{ $active ? 'bg-emerald-50/80 text-emerald-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 active:bg-slate-100/80' }}">
                        @if ($active)<span class="absolute bottom-2 left-0 top-2 w-[3px] rounded-r-md bg-emerald-600"></span>@endif
                        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            @switch ($item['icon'])
                                @case('dashboard')<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/>@break
                                @case('forms')<path stroke-linecap="round" stroke-linejoin="round" d="M8 3.75h6l4 4v12.5A1.75 1.75 0 0116.25 22h-8.5A1.75 1.75 0 016 20.25V5.5A1.75 1.75 0 017.75 3.75H8zM14 4v4h4M9 13h6m-6 4h6"/>@break
                                @case('import')<path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3m0 0L7 8m5-5l5 5M5 14v5a2 2 0 002 2h10a2 2 0 002-2v-5"/>@break
                                @case('link')<path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71m2.25 5.82a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>@break
                                @case('calculator')<rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h2m4 0h2m-8 4h2m4 0h2m-8 3h2"/>@break
                                @case('history')<path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.8L3 7m0-4v4h4m5-1v6l4 2"/>@break
                                @case('chart')<path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h17M8 15v-4m4 4V7m4 8v-6m4 6V4"/>@break
                                @case('variance')<path stroke-linecap="round" stroke-linejoin="round" d="M4 5h6v5H4zM14 14h6v5h-6zM10 7.5h3a3 3 0 013 3V14m0 0l-2-2m2 2l2-2M7 10v8h7"/>@break
                                @case('report')<path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h5m-9 4h4m-4 4h6m-6 3h6"/>@break
                                @case('spark')<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.6 5.4L19 10l-5.4 1.6L12 17l-1.6-5.4L5 10l5.4-1.6L12 3zm7 12l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15zM5 16l.7 1.8L7.5 18.5l-1.8.7L5 21l-.7-1.8-1.8-.7 1.8-.7L5 16z"/>@break
                            @endswitch
                        </svg>
                        <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 space-y-1 border-t border-slate-100 bg-slate-50/30 p-3">
        <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 rounded-xl p-2 transition-all duration-150 hover:bg-slate-50">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-xs font-bold text-slate-700">{{ fallback_avatar_initials($currentUser?->name ?? 'User') }}</div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-[11px] font-bold text-slate-800">{{ $currentUser?->name }}</p>
                <p class="truncate text-[10px] text-slate-400">{{ $currentUser?->email }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-xs font-bold text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-700">Logout</button>
        </form>
    </div>
</div>
