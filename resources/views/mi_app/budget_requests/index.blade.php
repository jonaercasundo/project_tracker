<x-mi_app>
    <div class="max-w-6xl mx-auto py-6">

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Budget Requests</h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $budgetRequests->total() }} total {{ Str::plural('request', $budgetRequests->total()) }}</p>
            </div>
            <a href="{{ route('budget_requests.create') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                New Request
            </a>
        </div>

        {{-- Search + status filter. Requires BudgetRequestController@index to accept
             'search' and 'status' query params (mirrors TravelLiquidationController's
             pattern) -- see note below the view. --}}
        <form method="GET" action="{{ route('budget_requests.index') }}" class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search control ID, employee, department..."
                       class="w-full pl-9 pr-3 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                <option value="">All statuses</option>
                @foreach(['budget_requested', 'approved', 'released', 'in_progress', 'liquidated', 'closed', 'cancelled'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>
                        {{ ucwords(str_replace('_', ' ', $statusOption)) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors">
                Filter
            </button>
            @if(request('search') || request('status'))
                <a href="{{ route('budget_requests.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                    Clear
                </a>
            @endif
        </form>

        <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th scope="col" class="p-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Control ID</th>
                        <th scope="col" class="p-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Employee</th>
                        <th scope="col" class="p-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Department</th>
                        <th scope="col" class="p-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Date Requested</th>
                        <th scope="col" class="p-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wide">Budget Total</th>
                        <th scope="col" class="p-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Status</th>
                        <th scope="col" class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($budgetRequests as $br)
                        @php
                            $statusStyles = [
                                'budget_requested' => 'bg-slate-100 text-slate-600',
                                'approved'         => 'bg-blue-50 text-blue-700',
                                'released'         => 'bg-indigo-50 text-indigo-700',
                                'in_progress'      => 'bg-amber-50 text-amber-700',
                                'liquidated'       => 'bg-green-50 text-green-700',
                                'closed'           => 'bg-slate-200 text-slate-700',
                                'cancelled'        => 'bg-red-50 text-red-700',
                            ];
                            $badgeClass = $statusStyles[$br->status] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-3 font-mono text-xs text-slate-700">{{ $br->control_id }}</td>
                            <td class="p-3 text-slate-700">{{ $br->employee->name ?? '—' }}</td>
                            <td class="p-3 text-slate-600">{{ $br->department }}</td>
                            <td class="p-3 text-slate-500 text-xs">{{ $br->created_at->format('M d, Y') }}</td>
                            <td class="p-3 text-right font-semibold text-slate-800">₱{{ number_format($br->budget_total, 2) }}</td>
                            <td class="p-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                    {{ ucwords(str_replace('_', ' ', $br->status)) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('budget_requests.show', $br) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-bold transition-colors">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center">
                                <p class="text-sm font-semibold text-slate-500">No budget requests found</p>
                                <p class="text-xs text-slate-400 mt-1">
                                    @if(request('search') || request('status'))
                                        Try adjusting your search or filter.
                                    @else
                                        Get started by creating a new request.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $budgetRequests->links() }}</div>
    </div>
</x-mi_app>