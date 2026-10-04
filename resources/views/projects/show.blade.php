<x-project_app-layout>
<div class="max-w-7xl mx-auto space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Top Action Row --}}
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                {{ $project->project_name }}
                </h1>

                @if($project->status)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-700 whitespace-nowrap">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        {{ $project->status }}
                    </span>
                @endif
            </div>

            <p class="mt-1 text-sm text-slate-500">
                Project overview, structure, and configuration
            </p>
        </div>

        <a href="{{ route('projects.index') }}"
            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 active:scale-95 whitespace-nowrap">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5m6 6-6-6 6-6"/>
            </svg>
            Back to Projects
        </a>

    </div>


    {{-- QUICK STATISTICS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- SCHOOLS --}}
        <div class="group relative flex flex-col gap-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-blue-200 hover:shadow-md">
            <svg class="absolute -bottom-2 -right-2 h-16 w-16 text-blue-50 transition-colors group-hover:text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16M3 21h18M9 21v-6h6v6"/></svg>
            <div class="relative flex items-center justify-between"><span class="text-[11px] font-bold uppercase tracking-wide text-blue-600">Schools</span><span class="h-2 w-2 rounded-full bg-blue-400"></span></div>
            <span class="relative text-2xl font-extrabold tabular-nums text-slate-900"><span data-project-count="schools">{{ $schoolCount }}</span></span>
            <span class="relative text-[11px] text-slate-400">Total assigned</span>
        </div>

        {{-- LOTS --}}
        <div class="group relative flex flex-col gap-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:shadow-md">
            <div class="relative">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Lots
                </p>
                <p class="text-2xl font-bold text-slate-900 mt-1">
                    <span data-project-count="lots">{{ $lotCount }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Project lots
                </p>
            </div>
            <div class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
        </div>

        {{-- ITEMS --}}
        <div class="group relative flex flex-col gap-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-amber-200 hover:shadow-md">
            <div class="relative">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Items
                </p>
                <p class="text-2xl font-bold text-slate-900 mt-1">
                    <span data-project-count="items">{{ $itemCount }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Catalog items
                </p>
            </div>
            <div class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        {{-- PACKAGES --}}
        <div class="group relative flex flex-col gap-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:shadow-md">
            <div class="relative">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Packages
                </p>
                <p class="text-2xl font-bold text-slate-900 mt-1">
                    <span data-project-count="packages">{{ $packageCount }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Bundled units
                </p>
            </div>
            <div class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
            </div>
        </div>

    </div>


    {{-- TABS --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- TAB NAV --}}
        <div role="tablist"
             class="flex overflow-x-auto border-b border-slate-100 bg-slate-50/70
                    text-xs font-bold text-slate-500">

            @php
                $tabs = [
                    'overview' => 'Overview',
                    'schools'  => 'Schools',
                    'lots'     => 'Lots',
                    'keystage' => 'Keystages',
                    'items'    => 'Items',
                    'packages' => 'Packages',
                    'setting'  => 'Project Setting',
                ];
            @endphp

            @foreach($tabs as $key => $label)

                <button
                    type="button"
                    role="tab"
                    data-tab="{{ $key }}"
                    onclick="openTab(event, '{{ $key }}')"
                    class="tab-btn whitespace-nowrap px-5 py-3.5
                           border-b-2 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500
                           {{ $loop->first
                                ? 'border-blue-600 text-blue-600 bg-white'
                                : 'border-transparent hover:bg-slate-50' }}">

                    {{ $label }}

                </button>

            @endforeach

        </div>


        {{-- CONTENT --}}
        <div class="p-6">


            {{-- ========================================================= --}}
            {{-- OVERVIEW --}}
            {{-- ========================================================= --}}

            @include('projects.partials.overview')
            @include('projects.partials.schools')
            @include('projects.partials.lots')
            @include('projects.partials.keystages')
            @include('projects.partials.items')
            @include('projects.partials.packages')
            @include('projects.partials.ar-settings')
                    </div>
    </div>
</div>


@include('projects.partials.scripts')
</x-project_app-layout>
