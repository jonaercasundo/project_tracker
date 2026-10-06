<x-mi_app :mobile-navigation="true">
    @php
        $statuses = [
            'draft' => ['Draft', 'Not submitted', 'bg-slate-100 text-slate-600'],
            'submitted' => ['Submitted', 'Awaiting accounting review', 'bg-blue-50 text-blue-700'],
            'noted' => ['Reviewed', 'Awaiting approval', 'bg-amber-50 text-amber-700'],
            'approved' => ['Approved', 'Approval recorded', 'bg-violet-50 text-violet-700'],
            'closed' => ['Closed', 'Report closed', 'bg-emerald-50 text-emerald-700'],
        ];
        $summaries = [
            ['', 'All reports', $statusCounts->sum(), 'border-t-blue-600', 'Your travel expense reports'],
            ['submitted', 'In review', $statusCounts->get('submitted', 0), 'border-t-blue-300', 'With accounting'],
            ['noted', 'Awaiting approval', $statusCounts->get('noted', 0), 'border-t-amber-400', 'Accounting review complete'],
            ['closed', 'Closed', $statusCounts->get('closed', 0), 'border-t-emerald-400', 'Completed reports'],
        ];
    @endphp
    <div class="space-y-7 travel-workspace">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-600">MI · Travel expenses</p>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Travel liquidation</h1>
                <p class="max-w-xl text-sm leading-6 text-slate-500">Keep your expenses, budget comparisons, and review progress in one place.</p>
            </div>
            <a href="{{ route('travel_liquidation.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                New liquidation
            </a>
        </div>

        @if(session('success') || session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') ?? session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Summary of all your reports">
            @foreach($summaries as [$filterStatus, $label, $count, $accent, $caption])
                <a href="{{ route('travel_liquidation.index', $filterStatus ? ['status' => $filterStatus] : []) }}" class="rounded-xl border border-slate-200 border-t-2 {{ $accent }} bg-white p-4 transition hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 sm:p-5">
                    <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ $count }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">{{ $caption }}</p>
                </a>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="reports-title">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                <h2 id="reports-title" class="text-base font-bold text-slate-900">Your reports <span class="ml-2 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">{{ $liquidations->total() }}</span></h2>
                <p class="text-xs text-slate-500">Most recent first</p>
            </div>
            <form action="{{ route('travel_liquidation.index') }}" method="GET" class="grid gap-3 border-b border-slate-100 bg-slate-50/60 p-5 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end sm:px-6">
                <div>
                    <label for="travel-search" class="mb-2 block text-xs font-semibold text-slate-600">Find a report</label>
                    <input id="travel-search" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search control ID or department" class="w-full rounded-lg border-slate-200 bg-white text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="travel-status" class="mb-2 block text-xs font-semibold text-slate-600">Status</label>
                    <select id="travel-status" name="status" class="w-full rounded-lg border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        @foreach($statuses as $value => [$label])
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">Apply filters</button>
                    @if($search !== '' || $status !== '')
                        <a href="{{ route('travel_liquidation.index') }}" class="text-xs font-semibold text-blue-600 hover:underline focus-visible:ring-2 focus-visible:ring-blue-600">Clear</a>
                    @endif
                </div>
            </form>

            @if($liquidations->isEmpty())
                <div class="flex flex-col items-center gap-4 px-6 py-16 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="6" width="16" height="15" rx="2"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2M4 12h16M10 12v3h4v-3"/></svg>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-base font-bold text-slate-900">{{ $search !== '' || $status !== '' ? 'No reports match your filters' : 'Your first trip starts with a budget' }}</h3>
                        <p class="max-w-md text-sm leading-6 text-slate-500">{{ $search !== '' || $status !== '' ? 'Try another control ID, department, or status to find your report.' : 'Once you have received an approved budget, choose it to report your travel expenses.' }}</p>
                    </div>
                    <a href="{{ $search !== '' || $status !== '' ? route('travel_liquidation.index') : route('budget_requests.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-blue-600 hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-blue-600">{{ $search !== '' || $status !== '' ? 'Clear filters' : 'View budget requests' }}</a>
                </div>
            @else
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Travel reports table">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left">Report</th>
                                <th scope="col" class="px-4 py-3 text-right">Requested budget</th>
                                <th scope="col" class="px-4 py-3 text-right">Actual expenses</th>
                                <th scope="col" class="px-4 py-3 text-right">Variance</th>
                                <th scope="col" class="px-5 py-3 text-left">Review status</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($liquidations as $liquidation)
                                @php
                                    [$statusLabel, $nextAction, $badge] = $statuses[$liquidation->status] ?? [ucfirst($liquidation->status), '', 'bg-slate-100 text-slate-600'];
                                    $variance = \Brick\Math\BigDecimal::of($liquidation->variance);
                                @endphp
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-6 py-5">
                                        <p class="font-mono text-xs font-semibold text-slate-900">{{ $liquidation->budgetRequest->control_id }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $liquidation->budgetRequest->department }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ $liquidation->submitted_at ? 'Submitted' : 'Created' }} {{ ($liquidation->submitted_at ?? $liquidation->created_at)?->format('d M Y') }}</p>
                                    </td>
                                    <td class="px-4 py-5 text-right font-mono text-xs tabular-nums text-slate-500">{{ number_format($liquidation->budgetRequest->budget_total, 2) }}</td>
                                    <td class="px-4 py-5 text-right font-mono text-xs font-semibold tabular-nums text-slate-900">{{ number_format($liquidation->actual_total, 2) }}</td>
                                    <td class="px-4 py-5 text-right">
                                        <p @class(['font-mono text-xs font-semibold tabular-nums', 'text-rose-600' => $variance->isNegative(), 'text-slate-700' => ! $variance->isNegative()])>{{ number_format($liquidation->variance, 2) }}</p>
                                        <p class="mt-1 text-[10px] text-slate-400">{{ $variance->isNegative() ? 'Over budget' : ($variance->isZero() ? 'Matches budget' : 'Unused budget') }}</p>
                                    </td>
                                    <td class="px-5 py-5">
                                        <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $badge }}"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $statusLabel }}</span>
                                        <p class="mt-2 text-[10px] text-slate-400">{{ $nextAction }}</p>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        @can('view', $liquidation)
                                            <a href="{{ route('travel_liquidation.show', $liquidation) }}" aria-label="View report {{ $liquidation->budgetRequest->control_id }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600">View <span aria-hidden="true">→</span></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-4 border-t border-slate-100 px-6 py-4">
                    <p class="text-[11px] text-slate-400">Variance = requested budget − actual expenses. A variance does not confirm a refund or reimbursement.</p>
                    {{ $liquidations->links() }}
                </div>
            @endif
        </section>
    </div>
</x-mi_app>
