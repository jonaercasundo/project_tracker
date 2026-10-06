<div class="flex min-h-full flex-col bg-white">
    <div class="border-b border-slate-100 px-5 py-5"><p class="text-xs font-extrabold uppercase tracking-widest text-blue-600">MI Accounting</p><p class="mt-1 text-xs text-slate-500">Workflow control center</p></div>
    <div class="border-b border-slate-100 p-4">
        <form method="POST" action="{{ route('company.switch') }}">@csrf
            <label for="accounting-company" class="mb-2 block text-xs font-semibold text-slate-500">Current company</label>
            <select id="accounting-company" name="company_id" onchange="this.form.submit()" class="w-full rounded-lg border-slate-200 text-sm">
                @foreach(auth()->user()->companies->where('is_active', true) as $company)
                    <option value="{{ $company->getKey() }}" @selected(auth()->user()->currentCompany()?->getKey() == $company->getKey())>{{ $company->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <nav aria-label="Accounting navigation" class="flex-1 space-y-6 p-3">
        @if(auth()->user()->canAccessMIApprovals())
            <a href="{{ route('mi.approvals') }}" class="block rounded-xl bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-700">Approval Queue</a>
        @endif
        @php
            $groups = [
                'Overview' => ['dashboard' => 'Dashboard', 'tasks' => 'My Tasks'],
                'Requests' => ['budgets' => 'Budget Requests', 'budget-review' => 'For Review', 'returned' => 'Returned / Rejected'],
                'Cash & Releases' => ['releases' => 'Fund Releases', 'acknowledgment' => 'Awaiting Acknowledgment', 'cash' => 'Cash / PCF'],
                'Liquidation' => ['liquidations' => 'Liquidations', 'liquidation-review' => 'For Review', 'missing' => 'Missing Requirements', 'settlement' => 'Settlement'],
                'Financial Records' => ['transactions' => 'Transactions', 'expenses' => 'Expense Records', 'documents' => 'Documents & Receipts'],
                'Reports' => ['reports' => 'Financial Reports', 'liquidation-reports' => 'Liquidation Reports', 'audit' => 'Audit Trail'],
            ];
        @endphp
        @foreach($groups as $group => $links)
            <div><p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $group }}</p>
                @foreach($links as $section => $label)
                    @php
                        $permission = $section === 'dashboard' ? 'mi.accounting.dashboard.view' : \App\Services\AccountingWorkspace::SECTIONS[$section][1];
                        $active = $section === 'dashboard' ? request()->routeIs('accounting.mi.dashboard', 'accounting.mi.home') : request()->route('section') === $section;
                    @endphp
                    @can($permission)
                        <a href="{{ $section === 'dashboard' ? route('accounting.mi.dashboard') : route('accounting.mi.workspace', $section) }}" @if($active) aria-current="page" @endif class="mb-1 flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-xs font-semibold {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"><span>{{ $label }}</span>@if(isset($accountingCounters[$section]))<span class="rounded-md bg-slate-100 px-2 py-0.5 tabular-nums">{{ $accountingCounters[$section] }}</span>@endif</a>
                    @endcan
                @endforeach
            </div>
        @endforeach
        @can('mi.liquidation.view')<a class="block px-3 text-xs text-blue-700" href="{{ route('accounting.mi.liquidation.index') }}">Original Liquidation List ?</a>@endcan
    </nav>
    <div class="space-y-3 border-t border-slate-100 p-4"><a class="block truncate text-xs font-semibold text-slate-600" href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs font-semibold text-slate-500 hover:text-red-600" type="submit">Sign out</button></form></div>
</div>
