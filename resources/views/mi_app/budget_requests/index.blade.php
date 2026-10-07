@php
    $statuses = [
        'budget_requested' => ['label' => 'Budget Requested', 'badge' => 'bg-slate-100 text-slate-600',    'dot' => 'bg-slate-400'],
        'approved'         => ['label' => 'Approved',         'badge' => 'bg-blue-50 text-blue-700',       'dot' => 'bg-blue-500'],
        'released'         => ['label' => 'Released',         'badge' => 'bg-indigo-50 text-indigo-700',   'dot' => 'bg-indigo-500'],
        'in_progress'      => ['label' => 'In Progress',      'badge' => 'bg-amber-50 text-amber-700',     'dot' => 'bg-amber-500'],
        'liquidated'       => ['label' => 'Liquidated',       'badge' => 'bg-green-50 text-green-700',     'dot' => 'bg-green-500'],
        'closed'           => ['label' => 'Closed',           'badge' => 'bg-slate-200 text-slate-700',    'dot' => 'bg-slate-500'],
        'cancelled'        => ['label' => 'Cancelled',        'badge' => 'bg-red-50 text-red-700',         'dot' => 'bg-red-500'],
    ];

    $activeStatus = request('status');
    $hasFilters   = request('search') || $activeStatus;
@endphp

<x-mi_app>
    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-0">

        {{-- =========================================================
            HEADER
        ========================================================== --}}

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">

            <div>
                <h1 class="text-xl font-bold text-slate-900 leading-tight">Budget requests</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $budgetRequests->total() }} {{ Str::plural('request', $budgetRequests->total()) }}
                    @if($hasFilters)
                        matching your filters
                    @endif
                </p>
            </div>

            <a href="{{ route('budget_requests.create') }}"
               class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                New request
            </a>

        </div>


        {{-- =========================================================
            SEARCH + STATUS FILTERS
            Uses the controller's existing 'search' and 'status' params.
        ========================================================== --}}

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-4">

            <form method="GET" action="{{ route('budget_requests.index') }}" class="flex gap-2 p-3">

                @if($activeStatus)
                    <input type="hidden" name="status" value="{{ $activeStatus }}">
                @endif

                <div class="relative flex-1">
                    <label for="search" class="sr-only">Search budget requests</label>
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                    <input id="search" type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search control ID, employee, department..."
                           class="w-full pl-9 pr-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                </div>

                <button type="submit"
                        class="px-4 py-2.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                    Search
                </button>

                @if($hasFilters)
                    <a href="{{ route('budget_requests.index') }}"
                       class="inline-flex items-center px-3 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                        Clear
                    </a>
                @endif

            </form>

            {{-- STATUS CHIPS --}}

            <div class="flex items-center gap-2 px-3 pb-3 overflow-x-auto" role="group" aria-label="Filter by status">

                <a href="{{ route('budget_requests.index', array_filter(['search' => request('search')])) }}"
                   @if(!$activeStatus) aria-current="true" @endif
                   class="shrink-0 px-3 py-1.5 rounded-full text-[11px] font-bold border transition-colors
                          {{ !$activeStatus ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                    All
                </a>

                @foreach($statuses as $key => $meta)
                    <a href="{{ route('budget_requests.index', array_filter(['search' => request('search'), 'status' => $key])) }}"
                       @if($activeStatus === $key) aria-current="true" @endif
                       class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold border transition-colors
                              {{ $activeStatus === $key ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                        {{ $meta['label'] }}
                    </a>
                @endforeach

            </div>

        </div>


        {{-- =========================================================
            TABLE (tablet and up)
        ========================================================== --}}

        <div class="hidden md:block bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

            <table class="w-full text-sm">

                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500">Control ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500">Employee</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500">Department</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500">Requested</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold text-slate-500">Budget total</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($budgetRequests as $br)

                        @php
                            $meta     = $statuses[$br->status] ?? ['label' => ucwords(str_replace('_', ' ', $br->status)), 'badge' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'];
                            $employee = $br->employee->name ?? null;
                            $initials = $employee
                                ? collect(preg_split('/\s+/', trim($employee)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
                                : '?';
                        @endphp

                        <tr class="group hover:bg-blue-50/40 transition-colors">

                            <td class="px-4 py-3.5">
                                <a href="{{ route('budget_requests.show', $br) }}"
                                   class="font-mono text-xs font-semibold text-slate-800 group-hover:text-blue-700 transition-colors">
                                    {{ $br->control_id }}
                                </a>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <span class="text-slate-700">{{ $employee ?? '—' }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-slate-600">{{ $br->department }}</td>

                            <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap">
                                {{ $br->created_at->format('M d, Y') }}
                            </td>

                            <td class="px-4 py-3.5 text-right font-semibold text-slate-900 tabular-nums whitespace-nowrap">
                                ₱{{ number_format($br->budget_total, 2) }}
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap {{ $meta['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('budget_requests.show', $br) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-bold transition-colors"
                                   aria-label="View {{ $br->control_id }}">
                                    View
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="flex items-center justify-center w-11 h-11 rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h7l5 5v10a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-semibold text-slate-600">No budget requests found</p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        @if($hasFilters)
                                            Try a different search or status, or clear the filters.
                                        @else
                                            Create your first request to get started.
                                        @endif
                                    </p>
                                    @unless($hasFilters)
                                        <a href="{{ route('budget_requests.create') }}" class="mt-4 text-xs font-bold text-blue-600 hover:text-blue-800">New request</a>
                                    @endunless
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =========================================================
            CARDS (mobile)
        ========================================================== --}}

        <div class="md:hidden space-y-3">

            @forelse($budgetRequests as $br)

                @php
                    $meta = $statuses[$br->status] ?? ['label' => ucwords(str_replace('_', ' ', $br->status)), 'badge' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'];
                @endphp

                <a href="{{ route('budget_requests.show', $br) }}"
                   class="block bg-white border border-slate-200 rounded-2xl shadow-sm p-4 hover:border-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200">

                    <div class="flex items-start justify-between gap-3">
                        <span class="font-mono text-xs font-semibold text-slate-800">{{ $br->control_id }}</span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap {{ $meta['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                            {{ $meta['label'] }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-slate-700">{{ $br->employee->name ?? '—' }}</p>

                    <p class="text-xs text-slate-500">
                        {{ $br->department }} · {{ $br->created_at->format('M d, Y') }}
                    </p>

                    <p class="mt-3 pt-3 border-t border-dashed border-slate-200 text-right text-base font-bold text-slate-900 tabular-nums">
                        ₱{{ number_format($br->budget_total, 2) }}
                    </p>

                </a>

            @empty

                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm px-4 py-12 text-center">
                    <div class="flex flex-col items-center">
                                    <span class="flex items-center justify-center w-11 h-11 rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h7l5 5v10a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-semibold text-slate-600">No budget requests found</p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        @if($hasFilters)
                                            Try a different search or status, or clear the filters.
                                        @else
                                            Create your first request to get started.
                                        @endif
                                    </p>
                                    @unless($hasFilters)
                                        <a href="{{ route('budget_requests.create') }}" class="mt-4 text-xs font-bold text-blue-600 hover:text-blue-800">New request</a>
                                    @endunless
                                </div>
                </div>

            @endforelse

        </div>


        <div class="mt-5">{{ $budgetRequests->links() }}</div>

    </div>
</x-mi_app>