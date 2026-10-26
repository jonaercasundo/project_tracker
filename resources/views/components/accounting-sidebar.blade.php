@php
    use Illuminate\Support\Str;

    $user           = auth()->user();
    $companies      = $user->companies->where('is_active', true);
    $currentCompany = $user->currentCompany();
    $sections       = \App\Services\AccountingWorkspace::SECTIONS;

    $initials = fn (?string $text, int $max = 1) => collect(preg_split('/\s+/', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY))
        ->take($max)
        ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
        ->implode('');

    $companyInitial = $initials($currentCompany?->name) ?: 'C';
    $userInitials   = $initials($user->name, 2) ?: 'U';

    /* 24x24 outline icon paths, keyed by section. */
    $icons = [
        'dashboard'           => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        'tasks'               => 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'budgets'             => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6M9 12h6M9 16h4',
        'budget-review'       => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
        'returned'            => 'M9 14L4 9l5-5M4 9h10a6 6 0 010 12h-3',
        'releases'            => 'M3 7h18v10H3zM12 14a2 2 0 100-4 2 2 0 000 4zM6.5 12h.01M17.5 12h.01',
        'acknowledgment'      => 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'cash'                => 'M3 7a2 2 0 012-2h13v4M3 7v10a2 2 0 002 2h14a1 1 0 001-1V9a1 1 0 00-1-1H5a2 2 0 01-2-2zM16 14h.01',
        'liquidations'        => 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6',
        'liquidation-review'  => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
        'missing'             => 'M12 9v4M12 17h.01M10.3 4.2L2.7 17.5A2 2 0 004.4 20.5h15.2a2 2 0 001.7-3L13.7 4.2a2 2 0 00-3.4 0z',
        'settlement'          => 'M5 13l4 4L19 7',
        'transactions'        => 'M7 7h12l-3-3M17 17H5l3 3',
        'expenses'            => 'M3 6h18v12H3zM3 10h18',
        'documents'           => 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z',
        'reports'             => 'M5 21V11M12 21V4M19 21v-7',
        'liquidation-reports' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 17v-3M13 17v-5M16 17v-2',
        'audit'               => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4',
    ];
    $iconFallback = 'M12 12h.01';

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
     * Resolve permission, URL, icon and active state once per link, and drop
     * links the user cannot see so groups never render an orphan heading.
     */
    $navGroups      = [];
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
                'icon'   => $icons[$section] ?? $iconFallback,
                'url'    => $isDashboard
                    ? route('accounting.mi.dashboard')
                    : route('accounting.mi.workspace', $section),
                'active' => $isDashboard
                    ? request()->routeIs('accounting.mi.dashboard', 'accounting.mi.home')
                    : request()->route('section') === $section,
                'count'  => isset($accountingCounters[$section]) ? (int) $accountingCounters[$section] : null,
            ];
        }

        if ($items) {
            $groupId     = 'nav-' . Str::slug($groupLabel);
            $navGroups[] = ['id' => $groupId, 'label' => $groupLabel, 'items' => $items];

            if (collect($items)->contains('active', true)) {
                $activeGroupIds[] = $groupId;
            }
        }
    }

    $ring      = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-1';
    $approvalsActive = request()->routeIs('mi.approvals');
@endphp

<style>[x-cloak]{display:none!important}</style>

<aside x-data="{
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
       :class="collapsed ? 'w-[4.5rem]' : 'w-full'"
       aria-label="Accounting sidebar"
       class="flex h-full min-h-full flex-col border-r border-slate-200 bg-white transition-[width] duration-200 motion-reduce:transition-none">

    {{-- Brand + sidebar toggle --}}
    <div class="flex items-center gap-3 px-4 pb-3 pt-5"
         :class="collapsed ? 'flex-col px-0' : 'justify-between'">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-600 text-sm font-bold tracking-tight text-white"
                  aria-hidden="true">MI</span>
            <div x-show="!collapsed" x-cloak class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">Accounting</p>
                <p class="truncate text-xs text-slate-500">Workflow control center</p>
            </div>
        </div>

        <button type="button"
                @click="collapsed = !collapsed"
                :aria-expanded="(!collapsed).toString()"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                aria-controls="accounting-sidebar-nav"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 {{ $ring }}">
            <svg class="h-5 w-5 transition-transform duration-200 motion-reduce:transition-none"
                 :class="collapsed ? 'rotate-180' : ''"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 6l-6 6 6 6" />
            </svg>
        </button>
    </div>

    {{-- Company switcher --}}
    <div class="px-3 pb-3">
        {{-- Rail: initial only --}}
        <button type="button"
                x-show="collapsed" x-cloak
                @click="collapsed = false"
                title="{{ $currentCompany?->name }}"
                class="mx-auto grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700
                       transition-colors hover:bg-slate-100 {{ $ring }}">
            {{ $companyInitial }}
            <span class="sr-only">Current company: {{ $currentCompany?->name }}. Expand sidebar to switch.</span>
        </button>

        {{-- Expanded: avatar + native select --}}
        <form x-show="!collapsed" x-cloak method="POST" action="{{ route('company.switch') }}">
            @csrf
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-2.5
                        transition-colors focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/20 hover:border-slate-300">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200"
                      aria-hidden="true">{{ $companyInitial }}</span>

                <div class="min-w-0 flex-1">
                    <label for="accounting-company" class="block text-xs text-slate-500">Company</label>
                    <select id="accounting-company"
                            name="company_id"
                            onchange="this.form.submit()"
                            class="block w-full truncate border-0 bg-transparent p-0 pr-6 text-sm font-semibold text-slate-900 focus:ring-0">
                        @foreach ($companies as $company)
                            <option value="{{ $company->getKey() }}"
                                    @selected((string) $currentCompany?->getKey() === (string) $company->getKey())>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Fallback when JavaScript is unavailable --}}
            <noscript>
                <button type="submit" class="mt-2 text-xs font-semibold text-blue-700 underline">Switch company</button>
            </noscript>
        </form>
    </div>

    {{-- Navigation --}}
    <nav id="accounting-sidebar-nav" aria-label="Accounting navigation"
         class="min-h-0 flex-1 space-y-4 overflow-y-auto px-3 pb-4 pt-1">

        @if ($user->canAccessMIApprovals())
            <a href="{{ route('mi.approvals') }}"
               title="Approval queue"
               @if ($approvalsActive) aria-current="page" @endif
               :class="collapsed ? 'justify-center px-0' : 'px-3'"
               class="flex items-center gap-3 rounded-xl py-2.5 text-sm font-semibold text-white shadow-sm transition-colors
                      {{ $approvalsActive ? 'bg-blue-800' : 'bg-blue-600 hover:bg-blue-700' }} {{ $ring }}">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 13l3-8h12l3 8v6H3zM3 13h5l1 3h6l1-3h5" />
                </svg>
                <span x-show="!collapsed" x-cloak>Approval queue</span>
                <span x-show="collapsed" x-cloak class="sr-only">Approval queue</span>
            </a>
        @endif

        @foreach ($navGroups as $group)
            <div role="group" aria-labelledby="{{ $group['id'] }}">

                {{-- Expanded: collapsible heading --}}
                <button type="button"
                        id="{{ $group['id'] }}"
                        x-show="!collapsed" x-cloak
                        @click="toggleGroup('{{ $group['id'] }}')"
                        :aria-expanded="isOpen('{{ $group['id'] }}').toString()"
                        aria-controls="{{ $group['id'] }}-list"
                        class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500
                               transition-colors hover:text-slate-900 {{ $ring }}">
                    <span>{{ $group['label'] }}</span>
                    <svg class="h-4 w-4 transition-transform duration-150 motion-reduce:transition-none"
                         :class="isOpen('{{ $group['id'] }}') ? 'rotate-90' : ''"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 6l6 6-6 6" />
                    </svg>
                </button>

                {{-- Rail: hairline instead of heading (label stays available to screen readers) --}}
                <div x-show="collapsed" x-cloak class="mx-3 mb-2 border-t border-slate-100">
                    <span class="sr-only">{{ $group['label'] }}</span>
                </div>

                <ul id="{{ $group['id'] }}-list"
                    x-show="collapsed || isOpen('{{ $group['id'] }}')"
                    class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}"
                               title="{{ $item['label'] }}"
                               @if ($item['active']) aria-current="page" @endif
                               :class="collapsed ? 'justify-center px-0' : 'px-3'"
                               class="group relative flex items-center gap-3 rounded-lg py-2 text-sm font-medium transition-colors {{ $ring }}
                                      {{ $item['active'] ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">

                                @if ($item['active'])
                                    <span class="absolute inset-y-2 -left-3 w-1 rounded-r-full bg-blue-600" aria-hidden="true"></span>
                                @endif

                                <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="{{ $item['icon'] }}" />
                                </svg>

                                <span x-show="!collapsed" x-cloak class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                <span x-show="collapsed" x-cloak class="sr-only">{{ $item['label'] }}</span>

                                @if ($item['count'] !== null)
                                    {{-- Expanded: count pill --}}
                                    <span x-show="!collapsed" x-cloak
                                          class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums
                                                 {{ $item['active'] ? 'bg-blue-100 text-blue-700' : ($item['count'] > 0 ? 'bg-slate-100 text-slate-700' : 'text-slate-400') }}">
                                        {{ number_format($item['count']) }}
                                        <span class="sr-only">items</span>
                                    </span>

                                    {{-- Rail: dot when there is something waiting --}}
                                    @if ($item['count'] > 0)
                                        <span x-show="collapsed" x-cloak
                                              class="absolute right-2 top-1.5 h-2 w-2 rounded-full bg-blue-600 ring-2 ring-white"
                                              aria-hidden="true"></span>
                                    @endif
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        @can('mi.liquidation.view')
            <a href="{{ route('accounting.mi.liquidation.index') }}"
               title="Original liquidation list"
               :class="collapsed ? 'justify-center px-0' : 'px-3'"
               class="group flex items-center gap-3 rounded-lg py-2 text-sm text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-900 {{ $ring }}">
                <svg class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-slate-600" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 17L17 7M8 7h9v9" />
                </svg>
                <span x-show="!collapsed" x-cloak>Original liquidation list</span>
                <span x-show="collapsed" x-cloak class="sr-only">Original liquidation list</span>
            </a>
        @endcan
        <x-ai-assistant-nav-link />
    </nav>

    {{-- Account --}}
    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-xl p-2" :class="collapsed ? 'flex-col' : ''">
            <a href="{{ route('profile.edit') }}"
               title="{{ $user->name }}"
               class="flex min-w-0 flex-1 items-center gap-3 rounded-lg {{ $ring }}">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-900 text-xs font-semibold text-white"
                      aria-hidden="true">{{ $userInitials }}</span>

                <span x-show="!collapsed" x-cloak class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                    @if (! empty($user->email))
                        <span class="block truncate text-xs text-slate-500">{{ $user->email }}</span>
                    @endif
                </span>
                <span x-show="collapsed" x-cloak class="sr-only">Profile: {{ $user->name }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        title="Sign out"
                        class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 {{ $ring }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 8l-4 4 4 4M6 12h10" />
                    </svg>
                    <span class="sr-only">Sign out</span>
                </button>
            </form>
        </div>
    </div>
</aside>
