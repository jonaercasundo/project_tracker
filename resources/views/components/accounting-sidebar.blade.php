@php
    use Illuminate\Support\Str;

    $user           = auth()->user();
    $companies      = $user->companies->where('is_active', true);
    $currentCompany = $user->currentCompany();
    $sections       = \App\Services\AccountingWorkspace::SECTIONS;

    $groups = [
        'Overview' => [
            'dashboard' => 'Dashboard',
            'tasks'     => 'My tasks',
        ],
        'Requests' => [
            'budgets'       => 'Budget requests',
            'budget-review' => 'For review',
            'returned'      => 'Returned / rejected',
        ],
        'Cash & releases' => [
            'releases'       => 'Fund releases',
            'acknowledgment' => 'Awaiting acknowledgment',
            'cash'           => 'Cash / PCF',
        ],
        'Liquidation' => [
            'liquidations'       => 'Liquidations',
            'liquidation-review' => 'For review',
            'missing'            => 'Missing requirements',
            'settlement'         => 'Settlement',
        ],
        'Financial records' => [
            'transactions' => 'Transactions',
            'expenses'     => 'Expense records',
            'documents'    => 'Documents & receipts',
        ],
        'Reports' => [
            'reports'             => 'Financial reports',
            'liquidation-reports' => 'Liquidation reports',
            'audit'               => 'Audit trail',
        ],
    ];

    /*
     * Resolve permission, URL and active state once per link, and drop any
     * link the user cannot see (or that has no section definition), so groups
     * with nothing visible don't render an orphan heading.
     */
    $navGroups = [];
    foreach ($groups as $groupLabel => $links) {
        $items = [];

        foreach ($links as $section => $label) {
            $isDashboard = $section === 'dashboard';
            $permission  = $isDashboard ? 'mi.accounting.dashboard.view' : ($sections[$section][1] ?? null);

            if (! $permission || ! $user->can($permission)) {
                continue;
            }

            $items[] = [
                'label'  => $label,
                'url'    => $isDashboard
                    ? route('accounting.mi.dashboard')
                    : route('accounting.mi.workspace', $section),
                'active' => $isDashboard
                    ? request()->routeIs('accounting.mi.dashboard', 'accounting.mi.home')
                    : request()->route('section') === $section,
                'count'  => $accountingCounters[$section] ?? null,
            ];
        }

        if ($items) {
            $navGroups[] = ['id' => 'nav-' . Str::slug($groupLabel), 'label' => $groupLabel, 'items' => $items];
        }
    }

    $linkBase  = 'flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-colors '
               . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600';
    $linkOn    = 'bg-blue-50 text-blue-700';
    $linkOff   = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp

<div class="flex min-h-full flex-col bg-white">

    {{-- Brand --}}
    <div class="border-b border-slate-100 px-5 py-5">
        <p class="text-sm font-extrabold text-blue-600">MI Accounting</p>
        <p class="mt-1 text-xs text-slate-500">Workflow control center</p>
    </div>

    {{-- Company switcher --}}
    <div class="border-b border-slate-100 p-4">
        <form method="POST" action="{{ route('company.switch') }}">
            @csrf
            <label for="accounting-company" class="mb-2 block text-xs font-semibold text-slate-500">
                Current company
            </label>
            <select id="accounting-company"
                    name="company_id"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-600 focus:ring-blue-600">
                @foreach ($companies as $company)
                    <option value="{{ $company->getKey() }}"
                            @selected((string) $currentCompany?->getKey() === (string) $company->getKey())>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>

            {{-- Fallback when JavaScript is unavailable --}}
            <noscript>
                <button type="submit" class="mt-2 text-xs font-semibold text-blue-700 underline">Switch company</button>
            </noscript>
        </form>
    </div>

    {{-- Navigation --}}
    <nav aria-label="Accounting navigation" class="flex-1 space-y-6 p-3">

        @if ($user->canAccessMIApprovals())
            @php $approvalsActive = request()->routeIs('mi.approvals'); @endphp
            <a href="{{ route('mi.approvals') }}"
               @if ($approvalsActive) aria-current="page" @endif
               class="block rounded-xl bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-700 transition-colors
                      hover:bg-blue-100
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                Approval queue
            </a>
        @endif

        @foreach ($navGroups as $group)
            <div role="group" aria-labelledby="{{ $group['id'] }}">
                <p id="{{ $group['id'] }}" class="px-3 pb-2 text-xs font-semibold text-slate-500">
                    {{ $group['label'] }}
                </p>

                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}"
                               @if ($item['active']) aria-current="page" @endif
                               class="{{ $linkBase }} {{ $item['active'] ? $linkOn : $linkOff }}">
                                <span>{{ $item['label'] }}</span>

                                @if ($item['count'] !== null)
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs tabular-nums text-slate-700">
                                        {{ number_format((int) $item['count']) }}
                                        <span class="sr-only">items</span>
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        @can('mi.liquidation.view')
            <a href="{{ route('accounting.mi.liquidation.index') }}"
               class="block rounded-lg px-3 py-1 text-sm text-blue-700 underline-offset-2
                      hover:underline
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                Original liquidation list
            </a>
        @endcan
    </nav>

    {{-- Account --}}
    <div class="space-y-3 border-t border-slate-100 p-4">
        <a href="{{ route('profile.edit') }}"
           class="block truncate rounded text-sm font-semibold text-slate-600
                  hover:text-slate-900
                  focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
            {{ $user->name }}
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="rounded text-sm font-semibold text-slate-500 transition-colors
                           hover:text-red-600
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                Sign out
            </button>
        </form>
    </div>
</div>