<x-project_app-layout>
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6 antialiased text-slate-800 selection:bg-blue-500/10 selection:text-blue-600">

    {{-- TOP ACTION HEADER BAR --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300 hover:shadow-md hover:shadow-slate-100/50">
        <div>
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                <h2 class="text-xl font-black text-slate-900 tracking-tight sm:text-2xl">Projects Directory</h2>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1">Manage master contracts, timelines, agencies, and fulfillment status pipelines.</p>
        </div>
        
        <button
            data-modal-target="addProjectModal"
            data-modal-toggle="addProjectModal"
            class="inline-flex items-center justify-center px-4.5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-sm shadow-blue-500/20 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-500/20 active:scale-[0.98] shrink-0 gap-2 group">
            <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
            </svg>
            Add New Project
        </button>
    </div>

    {{-- FILTER & SEARCH MATRIX BLOCK --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/40 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
        
        {{-- Live Search Input --}}
        <div class="space-y-1.5 lg:col-span-4">
            <label for="search" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider px-0.5">Search Directory</label>
            <div class="relative group">
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search Ref No, title, or keywords..." 
                    class="w-full text-xs border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 bg-slate-50/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all duration-200 font-medium">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Dropdowns --}}
        <div class="space-y-1.5 lg:col-span-2">
            <label for="year" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider px-0.5">Timeline Year</label>
            <div class="relative">
                <select id="year" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 text-slate-800 appearance-none focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all pr-8 cursor-pointer font-medium">
                    <option value="">All Years</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="space-y-1.5 lg:col-span-2">
            <label for="agency" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider px-0.5">Target Agency</label>
            <div class="relative">
                <select id="agency" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 text-slate-800 appearance-none focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all pr-8 cursor-pointer font-medium">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency }}" {{ request('agency') == $agency ? 'selected' : '' }}>{{ $agency }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="space-y-1.5 lg:col-span-2">
            <label for="status" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider px-0.5">System Status</label>
            <div class="relative">
                <select id="status" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 text-slate-800 appearance-none focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all pr-8 cursor-pointer font-medium">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Filter Actions --}}
        <div class="lg:col-span-2 flex gap-2">
            @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                <a href="{{ url()->current() }}" title="Clear filters & sorting"
                    class="p-2 text-slate-400 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 rounded-xl transition-all duration-150 flex items-center justify-center h-[38px] w-[38px] shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            @endif
            <button id="filterBtn"
                class="w-full px-4 py-2.5 text-xs font-bold text-blue-600 bg-blue-50/60 hover:bg-blue-600 hover:text-white active:bg-blue-700 rounded-xl transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-500/10 flex items-center justify-center gap-1.5 h-[38px] shadow-sm shadow-blue-500/5 active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"></path>
                </svg>
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
    @endphp

    {{-- MASTER DIRECTORY DATATABLE --}}
    <div class="w-full overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-sm shadow-slate-100/40">
        <table class="w-full text-left border-collapse table-auto">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 uppercase tracking-wider font-bold text-[10px] select-none">
                    
                    {{-- Ref No Header --}}
                    <th class="py-3.5 px-6">
                        <a href="{{ $buildSortUrl('ref_no') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Ref No</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'ref_no' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'ref_no' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'ref_no' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    {{-- Agency Header --}}
                    <th class="py-3.5 px-4">
                        <a href="{{ $buildSortUrl('agency') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Agency</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'agency' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'agency' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'agency' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    {{-- Title Header --}}
                    <th class="py-3.5 px-4">
                        <a href="{{ $buildSortUrl('project_name') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Project Title</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'project_name' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'project_name' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'project_name' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    {{-- Amount Header --}}
                    <th class="py-3.5 px-4">
                        <a href="{{ $buildSortUrl('contract_amount') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Contract Amount</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'contract_amount' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'contract_amount' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'contract_amount' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    {{-- Timeline Header --}}
                    <th class="py-3.5 px-4 text-center">
                        <a href="{{ $buildSortUrl('start_date') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Timeline Period</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'start_date' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'start_date' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'start_date' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    {{-- Status Header --}}
                    <th class="py-3.5 px-4">
                        <a href="{{ $buildSortUrl('status') }}" class="inline-flex items-center gap-1 hover:text-slate-700 transition-colors group/head">
                            <span>Status</span>
                            <span class="flex flex-col text-slate-300 group-hover/head:text-slate-400 transition-colors {{ request('sort_by') === 'status' ? 'text-blue-500' : '' }}">
                                <svg class="w-2.5 h-2.5 {{ request('sort_by') === 'status' && request('sort_order') === 'desc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                                <svg class="w-2.5 h-2.5 -mt-1 {{ request('sort_by') === 'status' && request('sort_order') === 'asc' ? 'opacity-30' : '' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
                            </span>
                        </a>
                    </th>

                    <th class="py-3.5 px-6 text-right select-none text-slate-400 font-bold text-[10px]">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                @forelse($projects as $project)
                    <tr class="hover:bg-slate-50/40 transition-colors duration-150 group">
                        
                        {{-- Reference Number --}}
                        <td class="py-4 px-6">
                            <span class="font-mono text-slate-500 bg-slate-100 text-[10px] px-2 py-1 rounded-md tracking-normal border border-slate-200/30 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                {{ $project->ref_no }}
                            </span>
                        </td>

                        {{-- Agency --}}
                        <td class="py-4 px-4 text-slate-900 font-bold max-w-[140px] truncate">
                            {{ $project->agency }}
                        </td>

                        {{-- Project Title --}}
                        <td class="py-4 px-4">
                            <div class="max-w-[320px] truncate text-slate-800 font-semibold group-hover:text-blue-600 transition-colors" title="{{ $project->project_name }}">
                                {{ $project->project_name }}
                            </div>
                        </td>

                        {{-- Contract Amount --}}
                        <td class="py-4 px-4 font-bold text-slate-900 font-mono tracking-tight text-[13px]">
                            <span class="text-slate-400 font-sans text-xs font-normal mr-0.5">₱</span>{{ number_format($project->contract_amount, 2) }}
                        </td>

                        {{-- Merged Timeline Period --}}
                        <td class="py-4 px-4 text-slate-500 font-normal text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg text-[11px] font-medium text-slate-600 border border-slate-100">
                                <span>{{ $project->start_date->format('M d, Y') }}</span>
                                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                </svg>
                                <span>{{ $project->end_date->format('M d, Y') }}</span>
                            </div>
                        </td>

                        {{-- Enhanced Badges with dynamic status dots --}}
                        <td class="py-4 px-4">
                            @php
                                $statusLower = strtolower($project->status);
                                [$badgeClasses, $dotClasses] = match(true) {
                                    str_contains($statusLower, 'complete') || str_contains($statusLower, 'deliver') => ['bg-emerald-50 text-emerald-700 border-emerald-100/80', 'bg-emerald-500'],
                                    str_contains($statusLower, 'pend') || str_contains($statusLower, 'progress') => ['bg-amber-50 text-amber-700 border-amber-100/80', 'bg-amber-500'],
                                    str_contains($statusLower, 'cancel') || str_contains($statusLower, 'drop') => ['bg-rose-50 text-rose-700 border-rose-100/80', 'bg-rose-500'],
                                    default => ['bg-blue-50 text-blue-700 border-blue-100/80', 'bg-blue-500']
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] uppercase font-bold tracking-wider border {{ $badgeClasses }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ $project->status }}
                            </span>
                        </td>

                        {{-- Action Buttons --}}
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <a href="{{ route('project.show', $project) }}"
                               class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl border border-slate-200/60 hover:border-blue-200 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-sm">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                View Details
                            </a>
                        </td>

                    </tr>
                @empty
                    {{-- Premium Minimal Empty State --}}
                    <tr>
                        <td colspan="7" class="py-16 text-center select-none">
                            <div class="max-w-sm mx-auto flex flex-col items-center">
                                <div class="h-12 w-12 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-4 shadow-sm">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800">No projects match criteria</h3>
                                <p class="text-xs text-slate-400 font-medium mt-1 px-4">We couldn't find any active tracking contracts that match your filters or keywords.</p>
                                @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                                    <a href="{{ url()->current() }}" class="mt-4 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all">
                                        Reset All Data Filters
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION LAYOUT WRAPPER --}}
    @if($projects->hasPages())
        <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/40 mt-4">
            {{ $projects->links() }}
        </div>
    @endif

</div>
</x-project_app-layout>