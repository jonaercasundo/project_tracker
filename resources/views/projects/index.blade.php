<x-project_app-layout>
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-5 text-slate-800">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Projects Directory</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage master contracts, timelines, agencies, and fulfillment status.</p>
        </div>

        <button
            data-modal-target="addProjectModal"
            data-modal-toggle="addProjectModal"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
            </svg>
            New Project
        </button>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white p-4 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">

        <div class="lg:col-span-4">
            <label for="search" class="block text-xs font-medium text-slate-500 mb-1">Search</label>
            <div class="relative">
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Ref No, title, or keywords..."
                    class="w-full text-sm rounded-lg border-slate-200 pl-8 pr-3 py-2
                           placeholder:text-slate-400
                           focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                </svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="year" class="block text-xs font-medium text-slate-500 mb-1">Year</label>
            <select id="year" class="w-full text-sm rounded-lg border-slate-200 py-2
                                     focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                <option value="">All Years</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <label for="agency" class="block text-xs font-medium text-slate-500 mb-1">Agency</label>
            <select id="agency" class="w-full text-sm rounded-lg border-slate-200 py-2
                                       focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                <option value="">All Agencies</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency }}" {{ request('agency') == $agency ? 'selected' : '' }}>{{ $agency }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <label for="status" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
            <select id="status" class="w-full text-sm rounded-lg border-slate-200 py-2
                                       focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2 flex gap-2">
            @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                <a href="{{ url()->current() }}" title="Clear filters"
                    class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors flex items-center justify-center h-9 w-9 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            @endif
            <button id="filterBtn"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors h-9">
                Apply
            </button>
        </div>

    </div>

    {{-- SORTING LOGIC HELPER --}}
    @php
        $buildSortUrl = function($column) {
            $currentSort = request('sort_by');
            $currentOrder = request('sort_order', 'asc');
            $nextOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery([
                'sort_by' => $column,
                'sort_order' => $nextOrder
            ]);
        };

        $sortIcon = function($column) {
            $active = request('sort_by') === $column;
            $order = request('sort_order', 'asc');
            return '<span class="inline-flex flex-col ml-1 ' . ($active ? 'text-slate-600' : 'text-slate-300') . '">
                <svg class="w-2.5 h-2.5 ' . ($active && $order === 'desc' ? 'opacity-30' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                <svg class="w-2.5 h-2.5 -mt-1 ' . ($active && $order === 'asc' ? 'opacity-30' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
            </span>';
        };
    @endphp

    {{-- TABLE --}}
    <div class="w-full overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left border-collapse table-auto text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wide font-medium text-xs">

                    <th class="py-3 px-5">
                        <a href="{{ $buildSortUrl('ref_no') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Ref No {!! $sortIcon('ref_no') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('agency') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Agency {!! $sortIcon('agency') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('project_name') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Project Title {!! $sortIcon('project_name') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('contract_amount') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Contract Amount {!! $sortIcon('contract_amount') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4 text-center">
                        <a href="{{ $buildSortUrl('start_date') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Timeline {!! $sortIcon('start_date') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('status') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Status {!! $sortIcon('status') !!}
                        </a>
                    </th>

                    <th class="py-3 px-5 text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($projects as $project)
                    <tr class="hover:bg-slate-50/70 transition-colors">

                        <td class="py-3 px-5">
                            <span class="font-mono text-slate-500 bg-slate-100 text-xs px-2 py-0.5 rounded-md">
                                {{ $project->ref_no }}
                            </span>
                        </td>

                        <td class="py-3 px-4 font-medium text-slate-900 max-w-[140px] truncate">
                            {{ $project->agency }}
                        </td>

                        <td class="py-3 px-4">
                            <div class="max-w-[320px] truncate" title="{{ $project->project_name }}">
                                {{ $project->project_name }}
                            </div>
                        </td>

                        <td class="py-3 px-4 font-medium text-slate-900">
                            <span class="text-slate-400 mr-0.5">₱</span>{{ number_format($project->contract_amount, 2) }}
                        </td>

                        <td class="py-3 px-4 text-slate-500 text-center whitespace-nowrap text-xs">
                            {{ $project->start_date->format('M d, Y') }}
                            <span class="text-slate-300 mx-1">→</span>
                            {{ $project->end_date->format('M d, Y') }}
                        </td>

                        <td class="py-3 px-4">
                            @php
                                $statusLower = strtolower($project->status);
                                [$badgeClasses, $dotClasses] = match(true) {
                                    str_contains($statusLower, 'complete') || str_contains($statusLower, 'deliver') => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                                    str_contains($statusLower, 'pend') || str_contains($statusLower, 'progress') => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
                                    str_contains($statusLower, 'cancel') || str_contains($statusLower, 'drop') => ['bg-rose-50 text-rose-700', 'bg-rose-500'],
                                    default => ['bg-slate-100 text-slate-600', 'bg-slate-400']
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeClasses }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ $project->status }}
                            </span>
                        </td>

                        <td class="py-3 px-5 text-right whitespace-nowrap">
                            <a href="{{ route('projects.show', $project) }}"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex"
                               title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <svg class="w-8 h-8 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                            </svg>
                            <h3 class="text-sm font-medium text-slate-700">No projects match your criteria</h3>
                            <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or search keywords.</p>
                            @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                                <a href="{{ url()->current() }}" class="inline-block mt-3 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                    Reset Filters
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($projects->hasPages())
        <div class="text-sm text-slate-500">
            {{ $projects->links() }}
        </div>
    @endif

</div>
</x-project_app-layout>