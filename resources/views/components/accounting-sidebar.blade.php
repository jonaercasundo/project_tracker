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
    $navGroups     = [];
    $activeGroupIds = [];
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
            $groupId = 'nav-' . Str::slug($groupLabel);
            $navGroups[] = ['id' => $groupId, 'label' => $groupLabel, 'items' => $items];

            if (collect($items)->contains('active', true)) {
                $activeGroupIds[] = $groupId;
            }
        }
    }

    $linkBase  = 'flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-colors '
               . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600';
    $linkOn    = 'bg-blue-50 text-blue-700';
    $linkOff   = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp

<style>[x-cloak]{display:none!important}</style>

<div x-data="{
         collapsed: false,
         closed: [],
         activeGroups: @js($activeGroupIds),
         storageKey: 'accounting.sidebar',
         init() {
             try {
                 const saved = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
                 this.collapsed = saved.collapsed === true;
                 this.closed = Array.isArray(saved.closed) ? saved.closed : [];
             } catch (e) {}
             // The group holding the current page always starts open.
             this.closed = this.closed.filter(id => !this.activeGroups.includes(id));
             this.$watch('collapsed', () => this.save());
             this.$watch('closed', () => this.save());
         },
         save() {
             try {
                 localStorage.setItem(this.storageKey, JSON.stringify({ collapsed: this.collapsed, closed: this.closed }));
             } catch (e) {}
         },
         isOpen(id) { return !this.closed.includes(id); },
         toggleGroup(id) {
             this.closed = this.isOpen(id) ? [...this.closed, id] : this.closed.filter(g => g !== id);
         },
     }"
     :class="collapsed ? 'w-16' : 'w-full'"
     class="flex min-h-full flex-col bg-white transition-[width] duration-150 motion-reduce:transition-none">


    {{-- Brand + sidebar toggle --}}
    <div class="flex items-center gap-2 border-b border-slate-100 py-4"
         :class="collapsed ? 'justify-center px-2' : 'justify-between px-5'">
        <div x-show="!collapsed" x-cloak>
            <p class="text-sm font-extrabold text-blue-600">MI Accounting</p>
            <p class="mt-1 text-xs text-slate-500">Workflow control center</p>
        </div>

        <button type="button"
                @click="collapsed = !collapsed"
                :aria-expanded="(!collapsed).toString()"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                aria-controls="accounting-sidebar-body"
                class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-900
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
            <svg class="h-5 w-5 transition-transform motion-reduce:transition-none" :class="collapsed ? 'rotate-180' : ''"
                 viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5l-5 5 5 5" />
            </svg>
        </button>
    </div>

    <div id="accounting-sidebar-body" x-show="!collapsed" x-cloak class="flex flex-1 flex-col">

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
                <button type="button"
                        id="{{ $group['id'] }}"
                        @click="toggleGroup('{{ $group['id'] }}')"
                        :aria-expanded="isOpen('{{ $group['id'] }}').toString()"
                        aria-controls="{{ $group['id'] }}-list"
                        class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500
                               transition-colors hover:text-slate-900
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                    <span>{{ $group['label'] }}</span>
                    <svg class="h-4 w-4 transition-transform motion-reduce:transition-none"
                         :class="isOpen('{{ $group['id'] }}') ? 'rotate-90' : ''"
                         viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 5l5 5-5 5" />
                    </svg>
                </button>

                <ul id="{{ $group['id'] }}-list" x-show="isOpen('{{ $group['id'] }}')" class="space-y-1">
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
    </div>{{-- /#accounting-sidebar-body --}}
</div>