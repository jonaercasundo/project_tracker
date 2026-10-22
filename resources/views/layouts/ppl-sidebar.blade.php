@php
    $financialPlansReady = \Illuminate\Support\Facades\Schema::hasColumn('project_financial_plans', 'deleted_at');
    $items = [
        ['PPL Dashboard', route('finance.ppl.dashboard'), 'finance.ppl.dashboard'],
        ['PPL Forms', route('ppl_forms.index'), 'ppl_forms.*'],
        ['Excel Import & Preview', route('finance.ppl-import.index'), 'finance.ppl-import.*'],
        ['Project Source Mapping', route('finance.ppl.sources.index'), 'finance.ppl.sources.*'],
        ['Cost & Financial Planning', $financialPlansReady ? route('finance.plans.index') : route('finance.ppl.dashboard', ['view' => 'schema']).'#schema', 'finance.plans.*'],
        ['PPL Monitoring', route('finance.ppl.dashboard', ['view' => 'monitoring']).'#monitoring', 'finance.ppl.dashboard'],
        ['Financial Variance Analysis', route('finance.ppl.analytics', ['view' => 'variance']).'#variance', 'finance.ppl.analytics'],
        ['Plan Versions & History', route('finance.ppl.analytics', ['view' => 'history']).'#history', 'finance.ppl.analytics'],
        ['PPL Reports', route('finance.ppl.analytics', ['view' => 'reports']).'#reports', 'finance.ppl.analytics'],
        ['AI Analytics & Chart Studio', route('finance.ppl.analytics'), 'finance.ppl.analytics'],
    ];
@endphp
<div class="flex h-full flex-col bg-white">
    <div class="border-b border-slate-100 px-6 py-5">
        <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">MMC PPL Management</p>
        <p class="mt-1 text-xs text-slate-500">Financial planning and monitoring</p>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="PPL Management">
        <a href="{{ route('finance.dashboard') }}" class="mb-3 flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900">&larr; Finance Dashboard</a>
        @foreach ($items as [$label, $href, $routePattern])
            @php
                $view = match ($label) {
                    'PPL Monitoring' => 'monitoring',
                    'Financial Variance Analysis' => 'variance',
                    'Plan Versions & History' => 'history',
                    'PPL Reports' => 'reports',
                    'PPL Dashboard', 'AI Analytics & Chart Studio' => null,
                    default => request()->query('view'),
                };
                $active = request()->routeIs($routePattern) && request()->query('view') === $view;
            @endphp
            <a href="{{ $href }}" class="block rounded-xl px-4 py-2.5 text-xs font-bold transition-colors {{ $active ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="border-t border-slate-100 p-4">
        <p class="truncate text-xs font-semibold text-slate-700">{{ Auth::user()?->name }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf <button type="submit" class="text-xs font-bold text-slate-500 hover:text-rose-700">Logout</button></form>
    </div>
</div>
