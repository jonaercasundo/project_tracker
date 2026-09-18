<x-project_app-layout>
@php
    $schools = collect($schools ?? ($project->schools ?? []));
    $lots = collect($lots ?? ($project->lots ?? []));
    $items = collect($items ?? ($project->items ?? []));
    $keystages = collect($keystages ?? ($project->keystages ?? []));
    $packages = collect($packages ?? []);

    $schoolCount = $schools->count();
    $lotCount = $lots->count();
    $itemCount = $items->count();
    $keystageCount = $keystages->count();
    $packageCount = $packages->count();
@endphp
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
            <span class="relative text-2xl font-extrabold tabular-nums text-slate-900">{{ $schoolCount }}</span>
            <span class="relative text-[11px] text-slate-400">Total assigned</span>
        </div>

        {{-- LOTS --}}
        <div class="group relative flex flex-col gap-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:shadow-md">
            <div class="relative">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Lots
                </p>
                <p class="text-2xl font-bold text-slate-900 mt-1">
                    {{ $lotCount }}
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
                    {{ $itemCount }}
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
                    {{ $packageCount }}
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

            <div id="overview" class="tab-content space-y-6">

                <div class="grid lg:grid-cols-2 gap-6">

                    {{-- PROJECT INFORMATION --}}
                    <div class="border border-slate-200 rounded-2xl p-5">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Project Information
                        </h2>

                        <div class="grid sm:grid-cols-2 gap-5">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Reference No.
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->ref_no ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Project Code
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->project_code ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Agency
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->agency ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Key Stage
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->keystage == 1 ? 'Enabled' : 'Not enabled' }}
                                </p>
                            </div>

                        </div>

                    </div>


                    {{-- FINANCIAL INFORMATION --}}
                    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Financial Information
                        </h2>

                        <div class="space-y-4">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Contract Amount
                                </p>

                                <p class="text-2xl font-black text-slate-900">
                                    &#8369;{{ number_format($project->contract_amount ?? 0, 2) }}
                                </p>
                            </div>

                            <div class="border-t border-slate-200 pt-3">

                                <p class="text-xs text-slate-400 uppercase">
                                    ABC
                                </p>

                                <p class="font-bold text-lg">
                                    &#8369;{{ number_format($project->ABC ?? 0, 2) }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- PROJECT STRUCTURE --}}
                <div>

                    <h2 class="text-sm font-black text-slate-900 mb-4">
                        Project Structure
                    </h2>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Schools
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $schoolCount }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Lots
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $lotCount }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Keystages
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $keystageCount }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Items
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $itemCount }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Packages
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $packageCount }}
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- SCHOOLS --}}
            {{-- ========================================================= --}}
            @php
                $schoolCollection = collect($schools ?? []);
                $schoolCount = $schoolCollection->count();

                $regions = $schoolCollection
                    ->pluck('region')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $divisions = $schoolCollection
                    ->pluck('division')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $municipalities = $schoolCollection
                    ->pluck('municipality')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
            @endphp


            <div id="schools" class="tab-content hidden space-y-6">

                {{-- ===================================================== --}}
                {{-- HEADER --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-900">
                            Schools
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            Manage schools associated with this project
                        </p>
                    </div>


                    {{-- ACTIONS --}}
                    <div class="flex items-center gap-2 flex-wrap">

                        {{-- Upload Schools --}}
                        <button
                            type="button"
                            id="uploadSchoolsButton"
                            class="inline-flex items-center gap-1.5
                                px-4 py-2 text-xs font-bold rounded-xl
                                whitespace-nowrap
                                bg-gradient-to-b from-blue-500 to-blue-600
                                text-white
                                hover:from-blue-600 hover:to-blue-700
                                active:scale-95
                                shadow-sm hover:shadow-md
                                ring-1 ring-inset ring-white/10
                                transition-all duration-200">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round">

                                <path d="M12 16V3"/>
                                <path d="m7 8 5-5 5 5"/>
                                <path d="M5 21h14a2 2 0 0 0 2-2v-3"/>
                                <path d="M3 16v3a2 2 0 0 0 2 2"/>

                            </svg>

                            Upload Schools

                        </button>

                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- SUMMARY CARDS --}}
                {{-- ===================================================== --}}

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">

                    {{-- TOTAL SCHOOLS --}}
                    <div
                        class="group relative
                            bg-white
                            border border-slate-200
                            rounded-2xl
                            shadow-sm
                            hover:shadow-md
                            hover:border-blue-200
                            transition-all duration-200
                            p-4
                            flex flex-col gap-1
                            overflow-hidden">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="absolute -right-2 -bottom-2
                                w-16 h-16
                                text-blue-50
                                group-hover:text-blue-100
                                transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">

                            <path d="M3 21h18"/>
                            <path d="M5 21V7l7-4 7 4v14"/>
                            <path d="M9 21v-6h6v6"/>
                            <path d="M9 9h.01"/>
                            <path d="M12 9h.01"/>
                            <path d="M15 9h.01"/>

                        </svg>

                        <div class="relative flex items-center justify-between">

                            <span
                                class="text-[11px] font-bold
                                    uppercase tracking-wide
                                    text-blue-600">

                                Total Schools

                            </span>

                            <span
                                class="w-2 h-2 rounded-full
                                    bg-blue-400 shrink-0">
                            </span>

                        </div>

                        <span
                            id="schoolTotalCount"
                            class="relative
                                text-2xl font-extrabold
                                text-slate-900
                                tabular-nums truncate">

                            {{ number_format($schoolCount) }}

                        </span>

                        <span class="relative text-[11px] text-slate-400">
                            Schools associated with project
                        </span>

                    </div>


                    {{-- REGIONS --}}
                    <div
                        class="group relative
                            bg-white
                            border border-slate-200
                            rounded-2xl
                            shadow-sm
                            hover:shadow-md
                            hover:border-indigo-200
                            transition-all duration-200
                            p-4
                            flex flex-col gap-1
                            overflow-hidden">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="absolute -right-2 -bottom-2
                                w-16 h-16
                                text-indigo-50
                                group-hover:text-indigo-100
                                transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">

                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18"/>
                            <path d="M12 3c2.5 2.5 3.5 5.5 3.5 9s-1 6.5-3.5 9"/>
                            <path d="M12 3c-2.5 2.5-3.5 5.5-3.5 9s1 6.5 3.5 9"/>

                        </svg>

                        <div class="relative flex items-center justify-between">

                            <span
                                class="text-[11px] font-bold
                                    uppercase tracking-wide
                                    text-indigo-600">

                                Regions

                            </span>

                            <span
                                class="w-2 h-2 rounded-full
                                    bg-indigo-400 shrink-0">
                            </span>

                        </div>

                        <span
                            id="schoolRegionCount"
                            class="relative
                                text-2xl font-extrabold
                                text-slate-900
                                tabular-nums">

                            {{ number_format($regions->count()) }}

                        </span>

                        <span class="relative text-[11px] text-slate-400">
                            Covered regions
                        </span>

                    </div>


                    {{-- DIVISIONS --}}
                    <div
                        class="group relative
                            bg-white
                            border border-slate-200
                            rounded-2xl
                            shadow-sm
                            hover:shadow-md
                            hover:border-cyan-200
                            transition-all duration-200
                            p-4
                            flex flex-col gap-1
                            overflow-hidden">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="absolute -right-2 -bottom-2
                                w-16 h-16
                                text-cyan-50
                                group-hover:text-cyan-100
                                transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">

                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>

                        </svg>

                        <div class="relative flex items-center justify-between">

                            <span
                                class="text-[11px] font-bold
                                    uppercase tracking-wide
                                    text-cyan-600">

                                Divisions

                            </span>

                            <span
                                class="w-2 h-2 rounded-full
                                    bg-cyan-400 shrink-0">
                            </span>

                        </div>

                        <span
                            id="schoolDivisionCount"
                            class="relative
                                text-2xl font-extrabold
                                text-slate-900
                                tabular-nums">

                            {{ number_format($divisions->count()) }}

                        </span>

                        <span class="relative text-[11px] text-slate-400">
                            DepEd divisions covered
                        </span>

                    </div>


                    {{-- MUNICIPALITIES --}}
                    <div
                        class="group relative
                            bg-white
                            border border-slate-200
                            rounded-2xl
                            shadow-sm
                            hover:shadow-md
                            hover:border-emerald-200
                            transition-all duration-200
                            p-4
                            flex flex-col gap-1
                            overflow-hidden">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="absolute -right-2 -bottom-2
                                w-16 h-16
                                text-emerald-50
                                group-hover:text-emerald-100
                                transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">

                            <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/>
                            <circle cx="12" cy="10" r="2.5"/>

                        </svg>

                        <div class="relative flex items-center justify-between">

                            <span
                                class="text-[11px] font-bold
                                    uppercase tracking-wide
                                    text-emerald-600">

                                Municipalities

                            </span>

                            <span
                                class="w-2 h-2 rounded-full
                                    bg-emerald-400 shrink-0">
                            </span>

                        </div>

                        <span
                            id="schoolMunicipalityCount"
                            class="relative
                                text-2xl font-extrabold
                                text-slate-900
                                tabular-nums">

                            {{ number_format($municipalities->count()) }}

                        </span>

                        <span class="relative text-[11px] text-slate-400">
                            Municipalities covered
                        </span>

                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- FILTERS --}}
                {{-- ===================================================== --}}

                <div
                    class="bg-white
                        border border-slate-200
                        rounded-2xl
                        shadow-sm
                        overflow-hidden">

                    {{-- FILTER HEADER --}}
                    <div
                        class="px-5 py-4
                            bg-slate-50/70
                            border-b border-slate-100
                            flex flex-col sm:flex-row
                            sm:items-center
                            sm:justify-between gap-3">

                        <div>

                            <h3 class="text-sm font-extrabold text-slate-800">
                                School Filters
                            </h3>

                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Search and filter schools by location or identification.
                            </p>

                        </div>

                        <span
                            id="schoolActiveFilterBadge"
                            class="hidden items-center gap-1.5
                                px-2.5 py-1
                                rounded-full
                                bg-blue-50
                                text-blue-700
                                border border-blue-100
                                text-[10px]
                                font-bold">

                            <span
                                class="w-1.5 h-1.5
                                    rounded-full
                                    bg-blue-500">
                            </span>

                            Filters Active

                        </span>

                    </div>


                    {{-- FILTER BODY --}}
                    <div class="p-5">

                        <div
                            class="grid grid-cols-1
                                md:grid-cols-2
                                lg:grid-cols-4
                                gap-3">

                            {{-- SEARCH --}}
                            <div class="lg:col-span-2">

                                <label
                                    for="schoolSearch"
                                    class="block
                                        text-[11px]
                                        font-bold
                                        text-slate-600
                                        mb-1.5">

                                    Search

                                </label>

                                <div class="relative">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="absolute left-3 top-1/2
                                            -translate-y-1/2
                                            w-4 h-4
                                            text-slate-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m21 21-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/>

                                    </svg>

                                    <input
                                        type="text"
                                        id="schoolSearch"
                                        autocomplete="off"
                                        placeholder="School name, ID, municipality..."
                                        class="w-full
                                            pl-9 pr-3
                                            py-2.5
                                            text-sm
                                            bg-white
                                            border border-slate-200
                                            rounded-xl
                                            text-slate-700
                                            placeholder:text-slate-400
                                            focus:ring-2
                                            focus:ring-blue-500/20
                                            focus:border-blue-500
                                            outline-none
                                            transition-all">

                                </div>

                            </div>


                            {{-- REGION --}}
                            <div>

                                <label
                                    for="schoolRegion"
                                    class="block
                                        text-[11px]
                                        font-bold
                                        text-slate-600
                                        mb-1.5">

                                    Region

                                </label>

                                <select
                                    id="schoolRegion"
                                    class="w-full
                                        px-3 py-2.5
                                        text-sm
                                        bg-white
                                        border border-slate-200
                                        rounded-xl
                                        text-slate-700
                                        focus:ring-2
                                        focus:ring-blue-500/20
                                        focus:border-blue-500
                                        outline-none
                                        transition-all">

                                    <option value="">
                                        All Regions
                                    </option>

                                    @foreach($regions as $region)

                                        <option value="{{ strtolower($region) }}">
                                            {{ $region }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- DIVISION --}}
                            <div>

                                <label
                                    for="schoolDivision"
                                    class="block
                                        text-[11px]
                                        font-bold
                                        text-slate-600
                                        mb-1.5">

                                    Division

                                </label>

                                <select
                                    id="schoolDivision"
                                    class="w-full
                                        px-3 py-2.5
                                        text-sm
                                        bg-white
                                        border border-slate-200
                                        rounded-xl
                                        text-slate-700
                                        focus:ring-2
                                        focus:ring-blue-500/20
                                        focus:border-blue-500
                                        outline-none
                                        transition-all">

                                    <option value="">
                                        All Divisions
                                    </option>

                                    @foreach($divisions as $division)

                                        <option value="{{ strtolower($division) }}">
                                            {{ $division }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- SECOND ROW --}}
                        <div
                            class="grid grid-cols-1
                                md:grid-cols-2
                                lg:grid-cols-4
                                gap-3 mt-3">

                            {{-- MUNICIPALITY --}}
                            <div>

                                <label
                                    for="schoolMunicipality"
                                    class="block
                                        text-[11px]
                                        font-bold
                                        text-slate-600
                                        mb-1.5">

                                    Municipality

                                </label>

                                <select
                                    id="schoolMunicipality"
                                    class="w-full
                                        px-3 py-2.5
                                        text-sm
                                        bg-white
                                        border border-slate-200
                                        rounded-xl
                                        text-slate-700
                                        focus:ring-2
                                        focus:ring-blue-500/20
                                        focus:border-blue-500
                                        outline-none
                                        transition-all">

                                    <option value="">
                                        All Municipalities
                                    </option>

                                    @foreach($municipalities as $municipality)

                                        <option value="{{ strtolower($municipality) }}">
                                            {{ $municipality }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- FILTER ACTIONS --}}
                            <div
                                class="flex items-end
                                    gap-2
                                    lg:col-span-3">

                                <button
                                    type="button"
                                    id="applySchoolFilters"
                                    class="inline-flex
                                        items-center
                                        gap-1.5
                                        px-4 py-2.5
                                        rounded-xl
                                        bg-gradient-to-b
                                        from-blue-500
                                        to-blue-600
                                        text-white
                                        text-xs
                                        font-bold
                                        hover:from-blue-600
                                        hover:to-blue-700
                                        active:scale-95
                                        shadow-sm
                                        hover:shadow-md
                                        transition-all">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m21 21-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/>

                                    </svg>

                                    Apply Filters

                                </button>


                                <button
                                    type="button"
                                    id="clearSchoolFilters"
                                    class="inline-flex
                                        items-center
                                        gap-1.5
                                        px-4 py-2.5
                                        rounded-xl
                                        bg-white
                                        border border-slate-200
                                        text-slate-700
                                        text-xs
                                        font-bold
                                        hover:bg-slate-50
                                        hover:border-slate-300
                                        active:scale-95
                                        transition-all">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12"/>

                                    </svg>

                                    Clear

                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- SCHOOL DIRECTORY TABLE --}}
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

                    {{-- TABLE HEADER --}}
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-200
                                flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">
                                School Directory
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Schools associated with this project
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                id="schoolResultCount"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg
                                    bg-white border border-slate-200
                                    text-[11px] font-bold text-slate-600">
                                {{ collect($schools ?? [])->count() }} Schools
                            </span>
                        </div>
                    </div>


                    {{-- TABLE --}}
                    <div class="overflow-x-auto">

                        <table class="w-full text-left border-collapse">

                            <thead>
                                <tr class="bg-slate-100/80 border-b border-slate-200">

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 whitespace-nowrap">
                                        School ID
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 min-w-[220px]">
                                        School Name
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 min-w-[220px]">
                                        Address
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 whitespace-nowrap">
                                        Municipality
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 whitespace-nowrap">
                                        Division
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 whitespace-nowrap">
                                        Region
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 min-w-[160px]">
                                        Contact Person
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 whitespace-nowrap">
                                        Telephone
                                    </th>

                                    <th class="px-4 py-3 text-[10px] font-extrabold uppercase
                                            tracking-wider text-slate-500 text-right whitespace-nowrap">
                                        Action
                                    </th>

                                </tr>
                            </thead>


                            <tbody
                                id="schoolsTableBody"
                                class="divide-y divide-slate-100 bg-white">

                                @forelse($schools ?? [] as $school)

                                    <tr
                                        class="school-row group hover:bg-blue-50/40 transition-colors duration-150"

                                        data-school-id="{{ $school->school_id }}"
                                        data-school-name="{{ $school->school_name }}"
                                        data-region="{{ $school->region }}"
                                        data-division="{{ $school->division }}"
                                        data-municipality="{{ $school->municipality }}"
                                    >

                                        {{-- SCHOOL ID --}}
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">

                                            <span class="inline-flex items-center
                                                        px-2 py-1 rounded-lg
                                                        bg-slate-100
                                                        border border-slate-200
                                                        text-[11px] font-bold
                                                        text-slate-700">

                                                {{ $school->school_id }}

                                            </span>

                                        </td>


                                        {{-- SCHOOL NAME --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="font-bold text-xs text-slate-900
                                                        group-hover:text-blue-700
                                                        transition-colors">

                                                {{ $school->school_name }}

                                            </div>

                                        </td>


                                        {{-- ADDRESS --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="text-xs text-slate-600 leading-5 max-w-[280px]">

                                                {{ $school->address ?: '—' }}

                                            </div>

                                        </td>


                                        {{-- MUNICIPALITY --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span class="text-xs font-semibold text-slate-700">

                                                {{ $school->municipality ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- DIVISION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span
                                                class="inline-flex items-center
                                                    px-2.5 py-1 rounded-lg
                                                    bg-indigo-50
                                                    border border-indigo-100
                                                    text-[10px] font-bold
                                                    text-indigo-700">

                                                {{ $school->division ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- REGION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span
                                                class="inline-flex items-center
                                                    px-2.5 py-1 rounded-lg
                                                    bg-blue-50
                                                    border border-blue-100
                                                    text-[10px] font-bold
                                                    text-blue-700">

                                                {{ $school->region ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- CONTACT PERSON --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="flex items-center gap-2">

                                                <div class="w-7 h-7 rounded-lg
                                                            bg-slate-100
                                                            border border-slate-200
                                                            flex items-center justify-center
                                                            flex-shrink-0">

                                                    <svg class="w-3.5 h-3.5 text-slate-500"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0z
                                                            M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />

                                                    </svg>

                                                </div>

                                                <span class="text-xs font-semibold text-slate-700">
                                                    {{ $school->contact_person ?: '—' }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- TELEPHONE --}}
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">

                                            @php
                                                $telephone = $school->telephone
                                                    ?? $school->telephone_no
                                                    ?? $school->contact_number
                                                    ?? $school->phone
                                                    ?? null;
                                            @endphp

                                            @if($telephone)

                                                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">

                                                    <svg class="w-3.5 h-3.5 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68
                                                            l1.5 4.49a1 1 0 01-.5 1.18l-2.12
                                                            1.06a11.04 11.04 0 005.46 5.46l1.06
                                                            -2.12a1 1 0 011.18-.5l4.49 1.5a1
                                                            1 0 01.68.95V19a2 2 0 01-2 2h-1
                                                            C10.61 21 3 13.39 3 4V5z" />
                                                    </svg>

                                                    {{ $telephone }}

                                                </div>

                                            @else

                                                <span class="text-xs text-slate-400">—</span>

                                            @endif

                                        </td>


                                        {{-- ACTION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="flex items-center justify-end gap-1.5">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    class="edit-school-btn
                                                        inline-flex items-center justify-center
                                                        w-8 h-8 rounded-lg
                                                        bg-white
                                                        border border-slate-200
                                                        text-slate-500
                                                        hover:bg-blue-50
                                                        hover:border-blue-200
                                                        hover:text-blue-600
                                                        active:scale-95
                                                        transition-all duration-150"
                                                    data-school-id="{{ $school->school_id }}"
                                                    title="Edit School">

                                                    <svg class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 3.487a2.1 2.1 0 013 3L7.5
                                                            18.85 3 20l1.15-4.5L16.862 3.487z" />

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    class="delete-school-btn
                                                        inline-flex items-center justify-center
                                                        w-8 h-8 rounded-lg
                                                        bg-white
                                                        border border-slate-200
                                                        text-slate-500
                                                        hover:bg-red-50
                                                        hover:border-red-200
                                                        hover:text-red-600
                                                        active:scale-95
                                                        transition-all duration-150"
                                                    data-school-id="{{ $school->school_id }}"
                                                    data-school-name="{{ $school->school_name }}"
                                                    title="Delete School">

                                                    <svg class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M6 7h12M9 7V5a1 1 0 011-1h4
                                                            a1 1 0 011 1v2m2 0v12a1 1 0
                                                            01-1 1H8a1 1 0 01-1-1V7m3
                                                            4v6m4-6v6" />

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="noSchoolsRow">

                                        <td colspan="9" class="px-6 py-14 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-2xl
                                                            bg-slate-100
                                                            border border-slate-200
                                                            flex items-center justify-center">

                                                    <svg class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.5">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2
                                                            0 00-2 2v16m14 0H5m14 0h2
                                                            M9 7h1m4 0h1m-6 4h1m4 0h1
                                                            m-6 4h1m4 0h1" />

                                                    </svg>

                                                </div>

                                                <h3 class="mt-3 text-sm font-bold text-slate-800">
                                                    No schools found
                                                </h3>

                                                <p class="mt-1 text-xs text-slate-500">
                                                    Add or upload schools associated with this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- TABLE FOOTER --}}
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200
                                flex flex-col sm:flex-row sm:items-center
                                sm:justify-between gap-3">

                        <p
                            id="schoolTableFooterCount"
                            class="text-[11px] font-semibold text-slate-500">
                            Showing 0 schools
                        </p>

                        <div
                            id="schoolsPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- SCHOOL FILTER + PAGINATION SCRIPT --}}
            {{-- ========================================================= --}}
            <script>
            document.addEventListener('DOMContentLoaded', function () {

                /* =========================================================
                ELEMENTS
                ========================================================= */

                const searchInput = document.getElementById('schoolSearch');
                const regionSelect = document.getElementById('schoolRegion');
                const divisionSelect = document.getElementById('schoolDivision');
                const municipalitySelect = document.getElementById('schoolMunicipality');

                const applyButton = document.getElementById('applySchoolFilters');
                const clearButton = document.getElementById('clearSchoolFilters');

                const tableBody = document.getElementById('schoolsTableBody');
                const resultCount = document.getElementById('schoolResultCount');
                const footerCount = document.getElementById('schoolTableFooterCount');
                const paginationContainer = document.getElementById('schoolsPaginationButtons');

                const activeFilterBadge = document.getElementById('schoolActiveFilterBadge');

                const rows = Array.from(
                    document.querySelectorAll('#schoolsTableBody .school-row')
                );


                /* =========================================================
                PAGINATION SETTINGS
                ========================================================= */

                let currentPage = 1;
                let rowsPerPage = 10;

                let filteredRows = [...rows];


                /* =========================================================
                FILTER
                ========================================================= */

                function getFilteredRows() {

                    const search = searchInput?.value.toLowerCase().trim() || '';
                    const region = regionSelect?.value.toLowerCase() || '';
                    const division = divisionSelect?.value.toLowerCase() || '';
                    const municipality = municipalitySelect?.value.toLowerCase() || '';

                    return rows.filter(row => {

                        const schoolId =
                            (row.dataset.schoolId || '').toLowerCase();

                        const schoolName =
                            (row.dataset.schoolName || '').toLowerCase();

                        const rowRegion =
                            (row.dataset.region || '').toLowerCase();

                        const rowDivision =
                            (row.dataset.division || '').toLowerCase();

                        const rowMunicipality =
                            (row.dataset.municipality || '').toLowerCase();


                        const matchesSearch =
                            !search ||
                            schoolId.includes(search) ||
                            schoolName.includes(search) ||
                            rowMunicipality.includes(search);


                        const matchesRegion =
                            !region ||
                            rowRegion === region;


                        const matchesDivision =
                            !division ||
                            rowDivision === division;


                        const matchesMunicipality =
                            !municipality ||
                            rowMunicipality === municipality;


                        return (
                            matchesSearch &&
                            matchesRegion &&
                            matchesDivision &&
                            matchesMunicipality
                        );

                    });

                }


                /* =========================================================
                APPLY FILTERS
                ========================================================= */

                function filterSchools(resetPage = true) {

                    if (resetPage) {
                        currentPage = 1;
                    }

                    filteredRows = getFilteredRows();

                    updateActiveFilterBadge();

                    renderTable();

                }


                /* =========================================================
                RENDER TABLE
                ========================================================= */

                function renderTable() {

                    const totalRows = filteredRows.length;

                    const totalPages = Math.max(
                        1,
                        Math.ceil(totalRows / rowsPerPage)
                    );


                    /* Prevent invalid page */
                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }


                    const startIndex =
                        (currentPage - 1) * rowsPerPage;

                    const endIndex =
                        startIndex + rowsPerPage;


                    const visibleRows =
                        filteredRows.slice(startIndex, endIndex);


                    /* Hide all rows first */
                    rows.forEach(row => {
                        row.classList.add('hidden');
                    });


                    /* Show current page */
                    visibleRows.forEach(row => {
                        row.classList.remove('hidden');
                    });


                    updateCounts(
                        totalRows,
                        startIndex,
                        visibleRows.length
                    );


                    renderPagination(
                        totalRows,
                        totalPages
                    );

                }


                /* =========================================================
                COUNTS
                ========================================================= */

                function updateCounts(
                    totalRows,
                    startIndex,
                    visibleCount
                ) {

                    const start =
                        totalRows === 0
                            ? 0
                            : startIndex + 1;

                    const end =
                        totalRows === 0
                            ? 0
                            : startIndex + visibleCount;


                    /* Header result count */
                    if (resultCount) {

                        resultCount.innerHTML = `
                            Showing
                            <span class="font-bold text-slate-700">
                                ${totalRows}
                            </span>
                            ${totalRows === 1 ? 'School' : 'Schools'}
                        `;

                    }


                    /* Footer result count */
                    if (footerCount) {

                        footerCount.innerHTML = `
                            Showing
                            <span class="font-bold text-slate-700">
                                ${start}
                            </span>
                            –
                            <span class="font-bold text-slate-700">
                                ${end}
                            </span>
                            of
                            <span class="font-bold text-slate-700">
                                ${totalRows}
                            </span>
                            ${totalRows === 1 ? 'school' : 'schools'}
                        `;

                    }

                }


                /* =========================================================
                PAGINATION
                ========================================================= */

                function renderPagination(
                    totalRows,
                    totalPages
                ) {

                    if (!paginationContainer) {
                        return;
                    }


                    paginationContainer.innerHTML = '';


                    /* No pagination needed */
                    if (totalRows === 0 || totalPages <= 1) {
                        return;
                    }


                    /* -----------------------------------------------------
                    Previous Button
                    ----------------------------------------------------- */

                    const previousButton =
                        createPaginationButton(
                            '‹',
                            currentPage > 1,
                            () => {

                                if (currentPage > 1) {

                                    currentPage--;

                                    renderTable();

                                }

                            },
                            'Previous'
                        );


                    paginationContainer.appendChild(previousButton);


                    /* -----------------------------------------------------
                    Page Numbers
                    ----------------------------------------------------- */

                    const pages = getPaginationPages(
                        currentPage,
                        totalPages
                    );


                    pages.forEach(page => {

                        if (page === '...') {

                            const ellipsis =
                                document.createElement('span');

                            ellipsis.className =
                                'inline-flex items-center justify-center ' +
                                'w-8 h-8 text-xs font-semibold text-slate-400';

                            ellipsis.textContent = '…';

                            paginationContainer.appendChild(ellipsis);

                            return;
                        }


                        const button =
                            createPaginationButton(
                                page,
                                true,
                                () => {

                                    currentPage = page;

                                    renderTable();

                                },
                                `Page ${page}`
                            );


                        if (page === currentPage) {

                            button.className =
                                'inline-flex items-center justify-center ' +
                                'w-8 h-8 rounded-lg ' +
                                'bg-blue-600 text-white ' +
                                'text-xs font-bold ' +
                                'shadow-sm';

                        }


                        paginationContainer.appendChild(button);

                    });


                    /* -----------------------------------------------------
                    Next Button
                    ----------------------------------------------------- */

                    const nextButton =
                        createPaginationButton(
                            '›',
                            currentPage < totalPages,
                            () => {

                                if (currentPage < totalPages) {

                                    currentPage++;

                                    renderTable();

                                }

                            },
                            'Next'
                        );


                    paginationContainer.appendChild(nextButton);

                }


                /* =========================================================
                PAGINATION PAGE RANGE
                ========================================================= */

                function getPaginationPages(
                    current,
                    total
                ) {

                    if (total <= 7) {

                        return Array.from(
                            { length: total },
                            (_, index) => index + 1
                        );

                    }


                    const pages = [];


                    pages.push(1);


                    if (current > 4) {
                        pages.push('...');
                    }


                    const start =
                        Math.max(2, current - 1);

                    const end =
                        Math.min(total - 1, current + 1);


                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }


                    if (current < total - 3) {
                        pages.push('...');
                    }


                    pages.push(total);


                    return pages;

                }


                /* =========================================================
                PAGINATION BUTTON
                ========================================================= */

                function createPaginationButton(
                    text,
                    enabled,
                    callback,
                    ariaLabel
                ) {

                    const button =
                        document.createElement('button');

                    button.type = 'button';

                    button.textContent = text;

                    button.setAttribute(
                        'aria-label',
                        ariaLabel
                    );


                    button.className =
                        'inline-flex items-center justify-center ' +
                        'w-8 h-8 rounded-lg ' +
                        'bg-white border border-slate-200 ' +
                        'text-xs font-bold text-slate-600 ' +
                        'hover:bg-blue-50 ' +
                        'hover:border-blue-200 ' +
                        'hover:text-blue-600 ' +
                        'active:scale-95 ' +
                        'transition-all duration-150';


                    if (!enabled) {

                        button.disabled = true;

                        button.classList.add(
                            'opacity-40',
                            'cursor-not-allowed'
                        );

                    } else {

                        button.addEventListener(
                            'click',
                            callback
                        );

                    }


                    return button;

                }


                /* =========================================================
                ACTIVE FILTER BADGE
                ========================================================= */

                function updateActiveFilterBadge() {

                    if (!activeFilterBadge) {
                        return;
                    }


                    const hasSearch =
                        searchInput?.value.trim() !== '';

                    const hasRegion =
                        regionSelect?.value !== '';

                    const hasDivision =
                        divisionSelect?.value !== '';

                    const hasMunicipality =
                        municipalitySelect?.value !== '';


                    const hasFilters =
                        hasSearch ||
                        hasRegion ||
                        hasDivision ||
                        hasMunicipality;


                    activeFilterBadge.classList.toggle(
                        'hidden',
                        !hasFilters
                    );

                    activeFilterBadge.classList.toggle(
                        'inline-flex',
                        hasFilters
                    );

                }


                /* =========================================================
                ROWS PER PAGE SELECTOR
                ========================================================= */

                function createRowsPerPageSelector() {

                    if (!footerCount || !paginationContainer) {
                        return;
                    }


                    const footer =
                        footerCount.parentElement;


                    if (!footer) {
                        return;
                    }


                    const existing =
                        footer.querySelector(
                            '#schoolRowsPerPage'
                        );


                    if (existing) {
                        return;
                    }


                    const wrapper =
                        document.createElement('div');

                    wrapper.className =
                        'flex items-center gap-2';


                    const label =
                        document.createElement('span');

                    label.className =
                        'text-[11px] font-semibold text-slate-400';

                    label.textContent =
                        'Rows';


                    const select =
                        document.createElement('select');

                    select.id =
                        'schoolRowsPerPage';

                    select.className =
                        'px-2 py-1.5 rounded-lg ' +
                        'border border-slate-200 ' +
                        'bg-white ' +
                        'text-[11px] font-bold text-slate-600 ' +
                        'focus:ring-2 focus:ring-blue-500/20 ' +
                        'focus:border-blue-500 ' +
                        'outline-none';


                    [10, 20, 30, 50, 100].forEach(value => {

                        const option =
                            document.createElement('option');

                        option.value = value;

                        option.textContent = value;

                        if (value === rowsPerPage) {
                            option.selected = true;
                        }

                        select.appendChild(option);

                    });


                    select.addEventListener(
                        'change',
                        function () {

                            rowsPerPage =
                                parseInt(this.value, 10);

                            currentPage = 1;

                            renderTable();

                        }
                    );


                    wrapper.appendChild(label);

                    wrapper.appendChild(select);


                    footer.insertBefore(
                        wrapper,
                        paginationContainer
                    );

                }


                /* =========================================================
                REGION CHANGE
                ========================================================= */

                regionSelect?.addEventListener(
                    'change',
                    function () {

                        const selectedRegion =
                            this.value.toLowerCase();


                        if (divisionSelect) {
                            divisionSelect.value = '';
                        }

                        if (municipalitySelect) {
                            municipalitySelect.value = '';
                        }


                        filterSchools();

                    }
                );


                /* =========================================================
                DIVISION CHANGE
                ========================================================= */

                divisionSelect?.addEventListener(
                    'change',
                    function () {

                        if (municipalitySelect) {
                            municipalitySelect.value = '';
                        }


                        filterSchools();

                    }
                );


                /* =========================================================
                MUNICIPALITY CHANGE
                ========================================================= */

                municipalitySelect?.addEventListener(
                    'change',
                    function () {

                        filterSchools();

                    }
                );


                /* =========================================================
                SEARCH
                ========================================================= */

                searchInput?.addEventListener(
                    'input',
                    function () {

                        filterSchools();

                    }
                );


                searchInput?.addEventListener(
                    'keydown',
                    function (event) {

                        if (event.key === 'Enter') {

                            event.preventDefault();

                            filterSchools();

                        }

                    }
                );


                /* =========================================================
                APPLY BUTTON
                ========================================================= */

                applyButton?.addEventListener(
                    'click',
                    function () {

                        filterSchools();

                    }
                );


                /* =========================================================
                CLEAR BUTTON
                ========================================================= */

                clearButton?.addEventListener(
                    'click',
                    function () {

                        if (searchInput) {
                            searchInput.value = '';
                        }

                        if (regionSelect) {
                            regionSelect.value = '';
                        }

                        if (divisionSelect) {
                            divisionSelect.value = '';
                        }

                        if (municipalitySelect) {
                            municipalitySelect.value = '';
                        }


                        /* Reset hidden division options */
                        if (divisionSelect) {

                            Array.from(
                                divisionSelect.options
                            ).forEach(option => {

                                option.hidden = false;

                            });

                        }


                        currentPage = 1;

                        filterSchools();

                    }
                );


                /* =========================================================
                INITIALIZE
                ========================================================= */

                createRowsPerPageSelector();

                filterSchools(false);

            });
            </script>

            {{-- ========================================================= --}}
            {{-- LOTS --}}
            {{-- ========================================================= --}}

            <div id="lots" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Lots
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Lots assigned to this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- LOT COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">
                            {{ $lotCount ?? collect($lots ?? [])->count() }} Lots
                        </span>

                        {{-- ADD LOT --}}
                        <button
                            type="button"
                            class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl bg-emerald-600
                                text-white hover:bg-emerald-700
                                transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>

                            </svg>

                            Add New Lot
                        </button>

                    </div>
                </div>


                {{-- LOT TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot Number
                                    </th>

                                    @if(($project->keystage ?? 0) == 1)

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Keystage
                                        </th>

                                    @else

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Cartons
                                        </th>

                                    @endif

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Contract No.
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="lotsTableBody" class="divide-y divide-slate-100">

                                @forelse($lots ?? [] as $lot)

                                    <tr class="lot-row hover:bg-slate-50 transition">

                                        {{-- LOT NUMBER --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center justify-center">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-blue-600"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                    </svg>

                                                </div>

                                                <div>

                                                    <p class="font-black text-slate-900">
                                                        {{ $lot->lot_name ?? 'Lot' }}
                                                    </p>

                                                    <p class="text-[11px] text-slate-400">
                                                        Lot ID: {{ $lot->lot_id }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE / CARTONS --}}
                                        <td class="px-4 py-4">

                                            @if(($project->keystage ?? 0) == 1)

                                                {{-- KEYSTAGE PROJECT --}}
                                                @if(!empty($lot->keystages) && $lot->keystages->count())

                                                    <div class="space-y-1">

                                                        @foreach($lot->keystages as $ks)

                                                            <div>
                                                                <span class="font-bold text-slate-700">
                                                                    Keystage {{ $ks->keystage_num }}
                                                                </span>

                                                                @if(!empty($ks->description))
                                                                    <span class="text-slate-500">
                                                                        — {{ $ks->description }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @else

                                                {{-- CARTON / PACKAGE PROJECT --}}
                                                @php
                                                    $cartonCount =
                                                        $lot->packages_count
                                                        ?? $lot->carton_count
                                                        ?? 0;
                                                @endphp

                                                @if($cartonCount > 0)

                                                    <span class="inline-flex items-center
                                                                px-2.5 py-1 rounded-lg
                                                                bg-blue-50 text-blue-700
                                                                text-xs font-bold">

                                                        {{ number_format($cartonCount) }}
                                                        {{ $cartonCount == 1 ? 'Carton' : 'Cartons' }}

                                                    </span>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @endif

                                        </td>


                                        {{-- CONTRACT NUMBER --}}
                                        <td class="px-4 py-4">

                                            @if(!empty($lot->contract_no))

                                                <span class="font-medium text-slate-700">
                                                    {{ $lot->contract_no }}
                                                </span>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    Not assigned
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center justify-center
                                                        gap-2">

                                                {{-- KEYSTAGE / PACKAGES --}}
                                                @if(($project->keystage ?? 0) == 1)

                                                    <button
                                                        type="button"
                                                        onclick="openTab(event, 'keystage')"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>

                                                            <circle cx="12"
                                                                    cy="12"
                                                                    r="3"/>

                                                        </svg>

                                                        Keystage

                                                    </button>

                                                @else

                                                    <button
                                                        type="button"
                                                        onclick="openTab(event, 'packages')"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                        </svg>

                                                        Packages

                                                    </button>

                                                @endif


                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditLotModal(
                                                        {{ $lot->lot_id }},
                                                        @js($lot->lot_name),
                                                        @js($lot->project_id),
                                                        @js($lot->contract_no)
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50 text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688a2.25 2.25 0 113.182 3.182l-1.688 1.687M16.862 4.487L7.5 13.85V17h3.15l9.36-9.332M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteLotModal({{ $lot->lot_id }})"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No lots found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No lots are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row">
                        <p id="lotResultCount" class="text-xs text-slate-400"></p>

                        <div id="lotsPaginationButtons" class="flex items-center gap-1"></div>
                    </div>

                </div>

            </div>
            {{-- ========================================================= --}}
            {{-- LOT MODAL HELPERS --}}
            {{-- ========================================================= --}}

            <script>
                function openEditLotModal(lotId, lotName, projectId, contractNo)
                {
                    /*
                    * These IDs should match your Laravel edit-lot modal.
                    */

                    const lotIdInput =
                        document.getElementById('editlotid');

                    const lotNameInput =
                        document.getElementById('editlotname');

                    const projectIdInput =
                        document.getElementById('editprojectid');

                    const contractNoInput =
                        document.getElementById('editcontractno');


                    if (lotIdInput) {
                        lotIdInput.value = lotId;
                    }

                    if (lotNameInput) {
                        lotNameInput.value = lotName ?? '';
                    }

                    if (projectIdInput) {
                        projectIdInput.value = projectId ?? '';
                    }

                    if (contractNoInput) {
                        contractNoInput.value = contractNo ?? '';
                    }


                    /*
                    * If your modal has a specific ID,
                    * open it here.
                    */

                    const modal =
                        document.getElementById('editLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
                function openDeleteLotModal(lotId)
                {
                    const deleteInput =
                        document.getElementById('delete_lot');

                    if (deleteInput) {
                        deleteInput.value = lotId;
                    }

                    const modal =
                        document.getElementById('deleteLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
            </script>

            {{-- ========================================================= --}}
            {{-- KEYSTAGES --}}
            {{-- ========================================================= --}}

            <div id="keystage" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Keystages
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Keystages assigned to the lots in this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">

                            {{ $keystageCount ?? collect($keystages ?? [])->count() }}
                            Keystages

                        </span>


                        {{-- ADD / UPLOAD --}}
                        <button
                            type="button"
                            class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl bg-emerald-600
                                text-white hover:bg-emerald-700
                                transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>

                            </svg>

                            Add Keystage
                        </button>

                    </div>

                </div>


                {{-- SEARCH --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl p-4 shadow-sm">

                    <div class="flex flex-col md:flex-row gap-3">

                        <div class="flex-1">

                            <div class="relative">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-3 top-1/2
                                            -translate-y-1/2
                                            w-4 h-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.35-5.65
                                            a7 7 0 11-14 0
                                            7 7 0 0114 0z"/>

                                </svg>

                                <input
                                    type="text"
                                    id="keystageSearch"
                                    placeholder="Search keystage, description, lot..."
                                    class="w-full pl-9 pr-3 py-2.5
                                        text-sm border border-slate-200
                                        rounded-xl
                                        focus:ring-2 focus:ring-blue-500
                                        focus:border-blue-500
                                        outline-none">

                            </div>

                        </div>


                        {{-- LOT FILTER --}}
                        <div class="w-full md:w-64">

                            <select
                                id="keystageLotFilter"
                                class="w-full px-3 py-2.5
                                    text-sm border border-slate-200
                                    rounded-xl bg-white
                                    focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500
                                    outline-none">

                                <option value="">
                                    All Lots
                                </option>

                                @foreach(collect($keystages ?? [])
                                    ->map(fn($item) => $item->lot_name ?? $item->lot_id ?? null)
                                    ->filter()
                                    ->unique()
                                    ->sort()
                                    as $lot)

                                    <option value="{{ strtolower($lot) }}">
                                        {{ $lot }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>


                {{-- TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Keystage
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold">
                                        Description
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="keystageTableBody"
                                class="divide-y divide-slate-100">

                                @forelse($keystages ?? [] as $keystage)

                                    @php

                                        $lotName =
                                            $keystage->lot_name
                                            ?? $keystage->lot_no
                                            ?? $keystage->lot_id
                                            ?? '—';

                                        $keystageNumber =
                                            $keystage->keystage_num
                                            ?? $keystage->keystage_no
                                            ?? $keystage->keystage_id
                                            ?? '—';

                                        $description =
                                            $keystage->description
                                            ?? $keystage->keystage_name
                                            ?? 'No description';

                                    @endphp


                                    <tr
                                        class="keystage-row hover:bg-slate-50 transition"

                                        data-search="
                                            {{ strtolower(
                                                ($lotName ?? '') . ' ' .
                                                ($keystageNumber ?? '') . ' ' .
                                                ($description ?? '')
                                            ) }}
                                        "

                                        data-lot="{{ strtolower($lotName) }}"
                                    >

                                        {{-- LOT --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center
                                                            justify-center">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-blue-600"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                    </svg>

                                                </div>

                                                <div>

                                                    <p class="font-bold text-slate-900">
                                                        {{ $lotName }}
                                                    </p>

                                                    @if(!empty($keystage->lot_id))

                                                        <p class="text-[11px] text-slate-400">
                                                            Lot ID: {{ $keystage->lot_id }}
                                                        </p>

                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE NUMBER --}}
                                        <td class="px-4 py-4">

                                            <span class="inline-flex items-center
                                                        px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        text-xs font-black">

                                                Keystage {{ $keystageNumber }}

                                            </span>

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-4 py-4">

                                            @if(
                                                !empty($keystage->description) ||
                                                !empty($keystage->keystage_name)
                                            )

                                                <p class="text-slate-700">
                                                    {{ $description }}
                                                </p>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    No description
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center
                                                        justify-center gap-2">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }},
                                                        @js($keystageNumber),
                                                        @js($description),
                                                        @js($keystage->lot_id ?? '')
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50
                                                        text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688
                                                                a2.25 2.25 0 113.182 3.182
                                                                l-1.688 1.687M16.862 4.487
                                                                L7.5 13.85V17h3.15l9.36-9.332
                                                                M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }}
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142
                                                                A2 2 0 0116.138 21H7.862
                                                                a2 2 0 01-1.995-1.858L5 7
                                                                m5 4v6m4-6v6M9 7V4
                                                                a1 1 0 011-1h4a1 1 0 011 1v3
                                                                m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="4"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center
                                                            justify-center mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14l9-5-9-5-9 5 9 5z"/>

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14v7m0-7L3 9m9 5l9-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No keystages found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No keystages are currently assigned
                                                    to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- FOOTER --}}
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row">

                        <p id="keystageResultCount"
                        class="text-xs text-slate-400">

                            Showing
                            <span class="font-bold text-slate-600">
                                {{ collect($keystages ?? [])->count() }}
                            </span>
                            keystages

                        </p>

                        <div id="keystagesPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- KEYSTAGE FILTER SCRIPT --}}
            {{-- ========================================================= --}}

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const searchInput =
                        document.getElementById('keystageSearch');

                    const lotFilter =
                        document.getElementById('keystageLotFilter');

                    const rows =
                        document.querySelectorAll('.keystage-row');

                    const resultCount =
                        document.getElementById('keystageResultCount');


                    function filterKeystages()
                    {
                        const search =
                            searchInput?.value
                                .toLowerCase()
                                .trim() || '';

                        const selectedLot =
                            lotFilter?.value
                                .toLowerCase()
                                .trim() || '';

                        let visible = 0;


                        rows.forEach(row => {

                            const rowSearch =
                                row.dataset.search || '';

                            const rowLot =
                                row.dataset.lot || '';


                            const matchesSearch =
                                !search ||
                                rowSearch.includes(search);


                            const matchesLot =
                                !selectedLot ||
                                rowLot === selectedLot;


                            const show =
                                matchesSearch &&
                                matchesLot;


                            row.classList.toggle(
                                'hidden',
                                !show
                            );


                            if (show) {
                                visible++;
                            }

                        });


                        if (resultCount) {

                            resultCount.innerHTML = `
                                Showing
                                <span class="font-bold text-slate-600">
                                    ${visible}
                                </span>
                                keystages
                            `;

                        }

                    }


                    searchInput?.addEventListener(
                        'input',
                        filterKeystages
                    );


                    lotFilter?.addEventListener(
                        'change',
                        filterKeystages
                    );

                });


                /*
                |--------------------------------------------------------------------------
                | Edit Keystage
                |--------------------------------------------------------------------------
                */

                function openEditKeystageModal(
                    keystageId,
                    keystageNumber,
                    description,
                    lotId
                ) {

                    const id =
                        document.getElementById(
                            'editkeystageid'
                        );

                    const number =
                        document.getElementById(
                            'editkeystagenum'
                        );

                    const desc =
                        document.getElementById(
                            'editkeystagedescription'
                        );

                    const lot =
                        document.getElementById(
                            'editkeystagelotid'
                        );


                    if (id) {
                        id.value = keystageId;
                    }

                    if (number) {
                        number.value = keystageNumber ?? '';
                    }

                    if (desc) {
                        desc.value = description ?? '';
                    }

                    if (lot) {
                        lot.value = lotId ?? '';
                    }


                    const modal =
                        document.getElementById(
                            'editKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }
                /*
                |--------------------------------------------------------------------------
                | Delete Keystage
                |--------------------------------------------------------------------------
                */
                function openDeleteKeystageModal(
                    keystageId
                ) {

                    const input =
                        document.getElementById(
                            'delete_keystage'
                        );

                    if (input) {
                        input.value = keystageId;
                    }


                    const modal =
                        document.getElementById(
                            'deleteKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }

            </script>

            {{-- ========================================================= --}}
            {{-- ITEMS --}}
            {{-- ========================================================= --}}

            <div id="items" class="tab-content hidden space-y-4">

                {{-- HEADER --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Items
                        </h2>

                        <p class="text-xs text-slate-400">
                            Items assigned to this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                            {{ $itemCount ?? collect($items ?? [])->count() }} Items
                        </span>

                        <a href="#"
                        class="inline-flex items-center gap-2 px-4 py-2
                                text-xs font-bold rounded-xl
                                bg-orange-600 text-white
                                hover:bg-orange-700
                                shadow-sm transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>
                            </svg>

                            Upload Items
                        </a>

                    </div>

                </div>


                {{-- SEARCH / FILTERS --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        {{-- Search --}}
                        <div class="md:col-span-2 relative">

                            <input
                                type="text"
                                id="itemSearch"
                                placeholder="Search item name, description..."
                                class="w-full pl-10 pr-4 py-2.5
                                    text-sm rounded-xl
                                    border border-slate-200
                                    focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="absolute left-3 top-3 w-4 h-4 text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>

                        </div>


                        {{-- Item Type / Category --}}
                        <select
                            id="itemTypeFilter"
                            class="w-full py-2.5 px-3
                                text-sm rounded-xl
                                border border-slate-200
                                focus:ring-2 focus:ring-blue-500
                                focus:border-blue-500">

                            <option value="">All Items</option>

                            @foreach(($items ?? collect())->pluck('item_type')->filter()->unique()->sort() as $type)
                                <option value="{{ strtolower($type) }}">
                                    {{ $type }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- ITEMS TABLE --}}
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wide">

                                    <th class="px-5 py-3">
                                        #
                                    </th>

                                    <th class="px-5 py-3">
                                        Item
                                    </th>

                                    <th class="px-5 py-3">
                                        Description
                                    </th>

                                    <th class="px-5 py-3">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="itemsTableBody"
                                class="divide-y divide-slate-100">

                                @forelse(($items ?? []) as $index => $item)

                                    @php
                                        $itemName =
                                            $item->item_name
                                            ?? $item->name
                                            ?? $item->item
                                            ?? 'Unnamed Item';

                                        $description =
                                            $item->description
                                            ?? $item->item_description
                                            ?? '—';

                                        $itemType =
                                            $item->item_type
                                            ?? $item->type
                                            ?? '—';

                                        $itemId =
                                            $item->item_id
                                            ?? $item->id
                                            ?? null;
                                    @endphp

                                    <tr
                                        class="item-row hover:bg-slate-50 transition"
                                        data-search="{{ strtolower($itemName . ' ' . $description . ' ' . $itemType) }}"
                                        data-type="{{ strtolower($itemType) }}">

                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4 text-xs text-slate-400 font-semibold item-number">
                                            {{ $index + 1 }}
                                        </td>


                                        {{-- ITEM --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $itemName }}
                                            </div>

                                            @if($itemId)
                                                <div class="text-[11px] text-slate-400 mt-1">
                                                    ID: {{ $itemId }}
                                                </div>
                                            @endif

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-5 py-4 text-slate-600 max-w-md">

                                            <div class="truncate"
                                                title="{{ $description }}">
                                                {{ $description }}
                                            </div>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex px-2.5 py-1
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-600
                                                        text-xs font-semibold">
                                                {{ $itemType }}
                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex items-center gap-2">

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-blue-50 text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs font-bold transition">
                                                    View
                                                </a>

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-slate-100 text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs font-bold transition">
                                                    Edit
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="itemsEmptyRow">
                                        <td colspan="5"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>
                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No items found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No items are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- PAGINATION --}}
                    {{-- ========================================================= --}}

                    <div id="itemsPagination"
                        class="flex flex-col sm:flex-row
                                items-center justify-between
                                gap-3 px-5 py-4
                                border-t border-slate-200
                                bg-slate-50">

                        {{-- Result information --}}
                        <div class="text-xs text-slate-500">
                            Showing
                            <span id="itemsShowingStart"
                                class="font-bold text-slate-700">0</span>
                            -
                            <span id="itemsShowingEnd"
                                class="font-bold text-slate-700">0</span>
                            of
                            <span id="itemsTotal"
                                class="font-bold text-slate-700">0</span>
                            items
                        </div>


                        {{-- Pagination buttons --}}
                        <div id="itemsPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- ITEMS SEARCH / FILTER / PAGINATION --}}
            {{-- ========================================================= --}}

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const searchInput = document.getElementById('itemSearch');
                    const typeFilter = document.getElementById('itemTypeFilter');

                    const rows = Array.from(
                        document.querySelectorAll('.item-row')
                    );

                    const paginationButtons =
                        document.getElementById('itemsPaginationButtons');

                    const showingStart =
                        document.getElementById('itemsShowingStart');

                    const showingEnd =
                        document.getElementById('itemsShowingEnd');

                    const totalItems =
                        document.getElementById('itemsTotal');


                    // =========================================================
                    // SETTINGS
                    // =========================================================

                    const itemsPerPage = 10;

                    let currentPage = 1;

                    let filteredRows = [...rows];


                    // =========================================================
                    // FILTER
                    // =========================================================

                    function filterItems() {

                        const search =
                            (searchInput?.value || '')
                                .toLowerCase()
                                .trim();

                        const type =
                            (typeFilter?.value || '')
                                .toLowerCase();

                        filteredRows = rows.filter(row => {

                            const rowSearch =
                                row.dataset.search || '';

                            const rowType =
                                row.dataset.type || '';

                            const matchesSearch =
                                !search ||
                                rowSearch.includes(search);

                            const matchesType =
                                !type ||
                                rowType === type;

                            return matchesSearch && matchesType;

                        });


                        currentPage = 1;

                        renderPagination();

                    }


                    // =========================================================
                    // PAGINATION
                    // =========================================================

                    function renderPagination() {

                        const total =
                            filteredRows.length;

                        const totalPages =
                            Math.max(
                                1,
                                Math.ceil(total / itemsPerPage)
                            );


                        // Prevent invalid page
                        if (currentPage > totalPages) {
                            currentPage = totalPages;
                        }


                        // Hide all rows first
                        rows.forEach(row => {
                            row.classList.add('hidden');
                        });


                        // Calculate current page
                        const start =
                            (currentPage - 1) * itemsPerPage;

                        const end =
                            Math.min(
                                start + itemsPerPage,
                                total
                            );


                        // Show current page rows
                        for (let i = start; i < end; i++) {

                            filteredRows[i].classList.remove('hidden');

                            const number =
                                filteredRows[i].querySelector('.item-number');

                            if (number) {
                                number.textContent = i + 1;
                            }

                        }


                        // Result information
                        showingStart.textContent =
                            total > 0 ? start + 1 : 0;

                        showingEnd.textContent =
                            end;

                        totalItems.textContent =
                            total;


                        // Render buttons
                        renderPaginationButtons(totalPages);

                    }


                    // =========================================================
                    // PAGINATION BUTTONS
                    // =========================================================

                    function renderPaginationButtons(totalPages) {

                        paginationButtons.innerHTML = '';


                        // No results
                        if (filteredRows.length === 0) {
                            return;
                        }


                        // Previous
                        const previous =
                            createPaginationButton(
                                '‹',
                                currentPage - 1,
                                currentPage === 1
                            );

                        paginationButtons.appendChild(previous);


                        // Page numbers
                        const maxVisiblePages = 5;

                        let startPage =
                            Math.max(
                                1,
                                currentPage - 2
                            );

                        let endPage =
                            Math.min(
                                totalPages,
                                startPage + maxVisiblePages - 1
                            );


                        if (
                            endPage - startPage + 1 <
                            maxVisiblePages
                        ) {
                            startPage =
                                Math.max(
                                    1,
                                    endPage - maxVisiblePages + 1
                                );
                        }


                        // First page + ellipsis
                        if (startPage > 1) {

                            paginationButtons.appendChild(
                                createPaginationButton('1', 1)
                            );

                            if (startPage > 2) {

                                const dots =
                                    document.createElement('span');

                                dots.className =
                                    'px-2 text-slate-400 text-sm';

                                dots.textContent = '...';

                                paginationButtons.appendChild(dots);
                            }

                        }


                        // Pages
                        for (
                            let page = startPage;
                            page <= endPage;
                            page++
                        ) {

                            paginationButtons.appendChild(
                                createPaginationButton(
                                    page,
                                    page,
                                    false,
                                    page === currentPage
                                )
                            );

                        }


                        // Last page + ellipsis
                        if (endPage < totalPages) {

                            if (endPage < totalPages - 1) {

                                const dots =
                                    document.createElement('span');

                                dots.className =
                                    'px-2 text-slate-400 text-sm';

                                dots.textContent = '...';

                                paginationButtons.appendChild(dots);
                            }


                            paginationButtons.appendChild(
                                createPaginationButton(
                                    totalPages,
                                    totalPages
                                )
                            );

                        }


                        // Next
                        const next =
                            createPaginationButton(
                                '›',
                                currentPage + 1,
                                currentPage === totalPages
                            );

                        paginationButtons.appendChild(next);

                    }


                    // =========================================================
                    // CREATE PAGINATION BUTTON
                    // =========================================================

                    function createPaginationButton(
                        label,
                        page,
                        disabled = false,
                        active = false
                    ) {

                        const button =
                            document.createElement('button');

                        button.type = 'button';

                        button.textContent = label;

                        button.className =
                            'min-w-[32px] h-8 px-2 rounded-lg ' +
                            'text-xs font-bold transition ' +
                            (
                                active
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-slate-600 ' +
                                    'border border-slate-200 ' +
                                    'hover:bg-slate-100'
                            );


                        if (disabled) {

                            button.disabled = true;

                            button.classList.add(
                                'opacity-40',
                                'cursor-not-allowed'
                            );

                        } else {

                            button.addEventListener(
                                'click',
                                function () {

                                    currentPage = page;

                                    renderPagination();

                                }
                            );

                        }

                        return button;

                    }


                    // =========================================================
                    // EVENTS
                    // =========================================================

                    searchInput?.addEventListener(
                        'input',
                        filterItems
                    );

                    typeFilter?.addEventListener(
                        'change',
                        filterItems
                    );


                    // =========================================================
                    // INITIAL LOAD
                    // =========================================================

                    renderPagination();

                });
            </script>
            {{-- ========================================================= --}}
            {{-- PACKAGES --}}
            {{-- ========================================================= --}}

            <div id="packages" class="tab-content hidden space-y-4">

                {{-- HEADER --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Packages
                        </h2>

                        <p class="text-xs text-slate-400">
                            Packages associated with this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        <span class="px-3 py-1 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">
                            {{ $packageCount ?? collect($packages ?? [])->count() }} Packages
                        </span>

                        <a href="#"
                        class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl
                                bg-cyan-600 text-white
                                hover:bg-cyan-700
                                shadow-sm transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>
                            </svg>

                            Upload Packages
                        </a>

                    </div>

                </div>


                {{-- SEARCH / FILTERS --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl p-4 shadow-sm">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        {{-- SEARCH --}}
                        <div class="md:col-span-2 relative">

                            <input
                                type="text"
                                id="packageSearch"
                                placeholder="Search package name, code..."
                                class="w-full pl-10 pr-4 py-2.5
                                    text-sm rounded-xl
                                    border border-slate-200
                                    focus:ring-2 focus:ring-cyan-500
                                    focus:border-cyan-500">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="absolute left-3 top-3 w-4 h-4
                                        text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>

                        </div>


                        {{-- PACKAGE TYPE --}}
                        <select
                            id="packageTypeFilter"
                            class="w-full py-2.5 px-3
                                text-sm rounded-xl
                                border border-slate-200
                                focus:ring-2 focus:ring-cyan-500
                                focus:border-cyan-500">

                            <option value="">
                                All Packages
                            </option>

                            @foreach(($packages ?? collect())->map(function ($package) {
                                return $package->package_type
                                    ?? $package->type
                                    ?? null;
                            })->filter()->unique()->sort() as $type)

                                <option value="{{ strtolower($type) }}">
                                    {{ $type }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- PACKAGES TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold
                                        text-slate-500 uppercase tracking-wide">

                                    <th class="px-5 py-3">
                                        #
                                    </th>

                                    <th class="px-5 py-3">
                                        Package
                                    </th>

                                    <th class="px-5 py-3">
                                        Code
                                    </th>

                                    <th class="px-5 py-3">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="packagesTableBody"
                                class="divide-y divide-slate-100">

                                @forelse(($packages ?? []) as $index => $package)

                                    @php

                                        $packageName =
                                            $package->package_name
                                            ?? $package->name
                                            ?? $package->package
                                            ?? 'Unnamed Package';

                                        $packageCode =
                                            $package->package_code
                                            ?? $package->code
                                            ?? $package->package_id
                                            ?? '—';

                                        $packageType =
                                            $package->package_type
                                            ?? $package->type
                                            ?? '—';

                                        $packageId =
                                            $package->package_id
                                            ?? $package->id
                                            ?? null;

                                    @endphp


                                    <tr
                                        class="package-row
                                            hover:bg-slate-50
                                            transition"

                                        data-search="{{ strtolower(
                                            $packageName . ' ' .
                                            $packageCode . ' ' .
                                            $packageType
                                        ) }}"

                                        data-type="{{ strtolower($packageType) }}">


                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4
                                                text-xs text-slate-400
                                                font-semibold package-number">

                                            {{ $index + 1 }}

                                        </td>


                                        {{-- PACKAGE --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $packageName }}
                                            </div>

                                            @if($packageId)

                                                <div class="text-[11px]
                                                            text-slate-400 mt-1">

                                                    ID: {{ $packageId }}

                                                </div>

                                            @endif

                                        </td>


                                        {{-- CODE --}}
                                        <td class="px-5 py-4">

                                            <span class="text-xs
                                                        font-semibold
                                                        text-slate-600">

                                                {{ $packageCode }}

                                            </span>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex
                                                        px-2.5 py-1
                                                        rounded-lg
                                                        bg-cyan-50
                                                        text-cyan-700
                                                        text-xs
                                                        font-semibold">

                                                {{ $packageType }}

                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex
                                                        items-center gap-2">

                                                <a href="#"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    View

                                                </a>

                                                <a href="#"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    Edit

                                                </a>

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="5"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col
                                                        items-center">

                                                <div class="w-12 h-12
                                                            rounded-full
                                                            bg-slate-100
                                                            flex items-center
                                                            justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6
                                                                text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>

                                                    </svg>

                                                </div>


                                                <p class="font-bold
                                                        text-slate-600">

                                                    No packages found

                                                </p>


                                                <p class="text-xs
                                                        text-slate-400 mt-1">

                                                    No packages are currently
                                                    associated with this project.

                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- PAGINATION --}}
                    {{-- ===================================================== --}}

                    <div id="packagesPagination"
                        class="flex flex-col sm:flex-row
                                items-center justify-between
                                gap-3 px-5 py-4
                                border-t border-slate-200
                                bg-slate-50">


                        {{-- RESULT INFORMATION --}}
                        <div class="text-xs text-slate-500">

                            Showing

                            <span id="packagesShowingStart"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            -

                            <span id="packagesShowingEnd"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            of

                            <span id="packagesTotal"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            packages

                        </div>


                        {{-- PAGINATION BUTTONS --}}
                        <div id="packagesPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- PACKAGES SEARCH / FILTER / PAGINATION --}}
            {{-- ========================================================= --}}

            <script>
            document.addEventListener('DOMContentLoaded', function () {

                const searchInput =
                    document.getElementById('packageSearch');

                const typeFilter =
                    document.getElementById('packageTypeFilter');

                const rows =
                    Array.from(
                        document.querySelectorAll('.package-row')
                    );

                const paginationButtons =
                    document.getElementById(
                        'packagesPaginationButtons'
                    );

                const showingStart =
                    document.getElementById(
                        'packagesShowingStart'
                    );

                const showingEnd =
                    document.getElementById(
                        'packagesShowingEnd'
                    );

                const totalPackages =
                    document.getElementById(
                        'packagesTotal'
                    );


                // =========================================================
                // SETTINGS
                // =========================================================

                const packagesPerPage = 10;

                let currentPage = 1;

                let filteredRows = [...rows];


                // =========================================================
                // FILTER
                // =========================================================

                function filterPackages() {

                    const search =
                        (searchInput?.value || '')
                            .toLowerCase()
                            .trim();

                    const type =
                        (typeFilter?.value || '')
                            .toLowerCase();


                    filteredRows = rows.filter(row => {

                        const rowSearch =
                            row.dataset.search || '';

                        const rowType =
                            row.dataset.type || '';


                        const matchesSearch =
                            !search ||
                            rowSearch.includes(search);


                        const matchesType =
                            !type ||
                            rowType === type;


                        return matchesSearch && matchesType;

                    });


                    currentPage = 1;

                    renderPagination();

                }


                // =========================================================
                // PAGINATION
                // =========================================================

                function renderPagination() {

                    const total =
                        filteredRows.length;


                    const totalPages =
                        Math.max(
                            1,
                            Math.ceil(
                                total / packagesPerPage
                            )
                        );


                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }


                    // Hide every row
                    rows.forEach(row => {
                        row.classList.add('hidden');
                    });


                    const start =
                        (currentPage - 1) *
                        packagesPerPage;


                    const end =
                        Math.min(
                            start + packagesPerPage,
                            total
                        );


                    // Show current page
                    for (
                        let i = start;
                        i < end;
                        i++
                    ) {

                        filteredRows[i]
                            .classList.remove('hidden');


                        const number =
                            filteredRows[i]
                                .querySelector(
                                    '.package-number'
                                );


                        if (number) {
                            number.textContent =
                                i + 1;
                        }

                    }


                    // Result information
                    showingStart.textContent =
                        total > 0
                            ? start + 1
                            : 0;

                    showingEnd.textContent =
                        end;

                    totalPackages.textContent =
                        total;


                    renderPaginationButtons(
                        totalPages
                    );

                }


                // =========================================================
                // PAGINATION BUTTONS
                // =========================================================

                function renderPaginationButtons(
                    totalPages
                ) {

                    paginationButtons.innerHTML = '';


                    if (filteredRows.length === 0) {
                        return;
                    }


                    // PREVIOUS
                    paginationButtons.appendChild(
                        createPaginationButton(
                            '‹',
                            currentPage - 1,
                            currentPage === 1
                        )
                    );


                    const maxVisiblePages = 5;


                    let startPage =
                        Math.max(
                            1,
                            currentPage - 2
                        );


                    let endPage =
                        Math.min(
                            totalPages,
                            startPage +
                            maxVisiblePages -
                            1
                        );


                    if (
                        endPage -
                        startPage +
                        1 <
                        maxVisiblePages
                    ) {

                        startPage =
                            Math.max(
                                1,
                                endPage -
                                maxVisiblePages +
                                1
                            );

                    }


                    // FIRST PAGE
                    if (startPage > 1) {

                        paginationButtons.appendChild(
                            createPaginationButton(
                                '1',
                                1
                            )
                        );


                        if (startPage > 2) {

                            const dots =
                                document.createElement(
                                    'span'
                                );

                            dots.className =
                                'px-2 text-slate-400 text-sm';

                            dots.textContent =
                                '...';

                            paginationButtons.appendChild(
                                dots
                            );

                        }

                    }


                    // PAGE NUMBERS
                    for (
                        let page = startPage;
                        page <= endPage;
                        page++
                    ) {

                        paginationButtons.appendChild(
                            createPaginationButton(
                                page,
                                page,
                                false,
                                page === currentPage
                            )
                        );

                    }


                    // LAST PAGE
                    if (endPage < totalPages) {

                        if (endPage < totalPages - 1) {

                            const dots =
                                document.createElement(
                                    'span'
                                );

                            dots.className =
                                'px-2 text-slate-400 text-sm';

                            dots.textContent =
                                '...';

                            paginationButtons.appendChild(
                                dots
                            );

                        }


                        paginationButtons.appendChild(
                            createPaginationButton(
                                totalPages,
                                totalPages
                            )
                        );

                    }


                    // NEXT
                    paginationButtons.appendChild(
                        createPaginationButton(
                            '›',
                            currentPage + 1,
                            currentPage === totalPages
                        )
                    );

                }


                // =========================================================
                // CREATE BUTTON
                // =========================================================

                function createPaginationButton(
                    label,
                    page,
                    disabled = false,
                    active = false
                ) {

                    const button =
                        document.createElement(
                            'button'
                        );


                    button.type = 'button';

                    button.textContent = label;


                    button.className =
                        'min-w-[32px] h-8 px-2 ' +
                        'rounded-lg text-xs font-bold ' +
                        'transition ' +
                        (
                            active
                                ? 'bg-cyan-600 text-white'
                                : 'bg-white text-slate-600 ' +
                                'border border-slate-200 ' +
                                'hover:bg-slate-100'
                        );


                    if (disabled) {

                        button.disabled = true;

                        button.classList.add(
                            'opacity-40',
                            'cursor-not-allowed'
                        );

                    } else {

                        button.addEventListener(
                            'click',
                            function () {

                                currentPage = page;

                                renderPagination();

                            }
                        );

                    }


                    return button;

                }


                // =========================================================
                // EVENTS
                // =========================================================

                searchInput?.addEventListener(
                    'input',
                    filterPackages
                );

                typeFilter?.addEventListener(
                    'change',
                    filterPackages
                );


                // =========================================================
                // INITIAL LOAD
                // =========================================================

                renderPagination();

            });
            </script>
            {{-- ========================================================= --}}
            {{-- PROJECT SETTINGS --}}
            {{-- ========================================================= --}}

            <div id="setting" class="tab-content hidden space-y-6">

                {{-- HEADER --}}
                <div>
                    <h2 class="text-lg font-black text-slate-900">
                        Project Settings
                    </h2>

                    <p class="text-xs text-slate-400">
                        Configure AR and label settings for this project.
                    </p>
                </div>


                {{-- ========================================================= --}}
                {{-- SETTINGS FORM --}}
                {{-- ========================================================= --}}

                <form method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6">

                    @csrf
                    @method('PUT')

                    <input type="hidden"
                        name="project_id"
                        value="{{ $project->project_id }}">


                    {{-- ===================================================== --}}
                    {{-- AR SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                AR Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-5">


                            {{-- PROJECT NAME --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Project Name

                                </label>

                                <input
                                    type="text"
                                    name="project_name"
                                    value="{{ old(
                                        'project_name',
                                        $arSettings->project_name
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('project_name')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Company

                                </label>

                                <input
                                    type="text"
                                    name="company"
                                    value="{{ old(
                                        'company',
                                        $arSettings->company
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('company')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CLIENT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Client

                                </label>

                                <input
                                    type="text"
                                    name="client"
                                    value="{{ old(
                                        'client',
                                        $arSettings->client
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('client')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- DISPLAY LABEL --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_label"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_label"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_label',
                                            $arSettings->display_label
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Label

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Enable label information on AR output.
                                    </p>

                                </div>

                            </div>


                            {{-- DISPLAY SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_school_id',
                                            $arSettings->display_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Include the school ID in the AR.
                                    </p>

                                </div>

                            </div>


                            {{-- FOOTER COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Company Name

                                </label>

                                <input
                                    type="text"
                                    name="ar_company_footer"
                                    value="{{ old(
                                        'ar_company_footer',
                                        $arSettings->ar_company_footer
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    This will appear under the signature
                                    in the AR PDF.
                                </p>

                                @error('ar_company_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER ADDRESS --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Address

                                </label>

                                <textarea
                                    name="ar_address_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_address_footer',
                                                $arSettings->ar_address_footer
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Full address shown at the bottom of the AR.
                                </p>

                                @error('ar_address_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER CONTACT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Contact

                                </label>

                                <textarea
                                    name="ar_contact_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_contact_footer',
                                                $arSettings->ar_contact_footer ?? ''
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Contact details shown at the bottom of the AR.
                                </p>

                                @error('ar_contact_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- SELECT LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Select Logo

                                </label>

                                <select
                                    name="ar_logo"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                    @forelse(($logoFiles ?? []) as $logo)

                                        <option
                                            value="{{ $logo }}"
                                            @selected(
                                                ($arSettings->ar_logo ?? 'logo.webp')
                                                === $logo
                                            )>

                                            {{ $logo }}

                                        </option>

                                    @empty

                                        <option value="logo.webp">
                                            logo.webp
                                        </option>

                                    @endforelse

                                </select>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Choose from existing logos.
                                </p>

                                @error('ar_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- UPLOAD NEW LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Or Upload New Logo

                                </label>

                                <input
                                    type="file"
                                    name="new_logo"
                                    accept=".png,.jpg,.jpeg,.webp"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        file:mr-4
                                        file:py-1.5
                                        file:px-3
                                        file:rounded-lg
                                        file:border-0
                                        file:text-xs
                                        file:font-bold
                                        file:bg-blue-50
                                        file:text-blue-700">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Uploading a file will override the selected logo.
                                </p>

                                @error('new_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CURRENT LOGO --}}
                            @if (!empty($arSettings->ar_logo))

                                <div class="pt-4 border-t border-slate-200">

                                    <label class="block text-xs
                                                font-bold uppercase
                                                text-slate-500 mb-3">

                                        Current Logo Preview

                                    </label>

                                    <div class="inline-flex
                                                items-center justify-center
                                                p-4
                                                bg-slate-50
                                                border border-slate-200
                                                rounded-xl">

                                        <img
                                            src="{{ asset(
                                                'assets/uploads/logo/' .
                                                $arSettings->ar_logo
                                            ) }}"
                                            alt="Current Logo"
                                            class="max-h-24 max-w-xs
                                                object-contain">

                                    </div>

                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- LABEL SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                Label Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-3">


                            {{-- SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_school_id',
                                            $arSettings->label_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the school ID on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- MUNICIPALITY --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_municipality"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_municipality"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_municipality',
                                            $arSettings->label_municipality
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Municipality

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the municipality on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- DIVISION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_division"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_division"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_division',
                                            $arSettings->label_division
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Division

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the division on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- REGION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_region"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_region"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_region',
                                            $arSettings->label_region
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Region

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the region on generated labels.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- SAVE --}}
                    {{-- ===================================================== --}}

                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2
                                px-6 py-3
                                text-sm font-bold
                                rounded-xl
                                bg-emerald-600
                                text-white
                                hover:bg-emerald-700
                                shadow-sm
                                transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"/>

                            </svg>

                            Save Settings

                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
</div>


{{-- PROJECT LIST PAGINATION --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const createPaginator = ({ rowSelector, resultCountId, buttonsId, label }) => {
            const rows = Array.from(document.querySelectorAll(rowSelector));
            const resultCount = document.getElementById(resultCountId);
            const buttons = document.getElementById(buttonsId);
            const perPage = 10;
            let currentPage = 1;

            if (!buttons) {
                return;
            }

            const render = () => {
                const visibleRows = rows.filter((row) => !row.classList.contains('hidden'));
                const totalPages = Math.max(1, Math.ceil(visibleRows.length / perPage));

                currentPage = Math.min(currentPage, totalPages);

                const start = (currentPage - 1) * perPage;
                const end = Math.min(start + perPage, visibleRows.length);

                rows.forEach((row) => {
                    row.style.display = 'none';
                });

                visibleRows.slice(start, end).forEach((row) => {
                    row.style.display = '';
                });

                if (resultCount) {
                    resultCount.innerHTML = visibleRows.length
                        ? `Showing <span class="font-bold text-slate-600">${start + 1}-${end}</span> of <span class="font-bold text-slate-600">${visibleRows.length}</span> ${label}`
                        : `No ${label} found`;
                }

                buttons.innerHTML = '';

                if (totalPages <= 1) {
                    return;
                }

                const addButton = (text, page, disabled = false, active = false) => {
                    const button = document.createElement('button');

                    button.type = 'button';
                    button.textContent = text;
                    button.disabled = disabled;
                    button.className = active
                        ? 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-blue-600 px-2 text-xs font-bold text-white'
                        : 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40';

                    button.addEventListener('click', () => {
                        currentPage = page;
                        render();
                    });

                    buttons.appendChild(button);
                };

                addButton('Prev', currentPage - 1, currentPage === 1);

                for (let page = 1; page <= totalPages; page++) {
                    if (page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1) {
                        addButton(page, page, false, page === currentPage);
                    }
                }

                addButton('Next', currentPage + 1, currentPage === totalPages);
            };

            const tableBody = rows[0]?.parentElement;

            if (tableBody) {
                new MutationObserver(render).observe(tableBody, {
                    attributes: true,
                    attributeFilter: ['class'],
                    subtree: true,
                });
            }

            render();
        };

        createPaginator({
            rowSelector: '.school-row',
            resultCountId: 'schoolResultCount',
            buttonsId: 'schoolsPaginationButtons',
            label: 'schools',
        });

        createPaginator({
            rowSelector: '.lot-row',
            resultCountId: 'lotResultCount',
            buttonsId: 'lotsPaginationButtons',
            label: 'lots',
        });

        createPaginator({
            rowSelector: '.keystage-row',
            resultCountId: 'keystageResultCount',
            buttonsId: 'keystagesPaginationButtons',
            label: 'keystages',
        });
    });
</script>

{{-- ============================================================= --}}
{{-- TAB SCRIPT --}}
{{-- ============================================================= --}}

<script>
function openTab(event, tabName) {

    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove(
            'border-blue-600',
            'text-blue-600',
            'bg-white'
        );

        btn.classList.add(
            'border-transparent',
            'text-slate-500'
        );
    });

    const content = document.getElementById(tabName);

    if (content) {
        content.classList.remove('hidden');
    }

    const btn = event.currentTarget;

    btn.classList.remove(
        'border-transparent',
        'text-slate-500'
    );

    btn.classList.add(
        'border-blue-600',
        'text-blue-600',
        'bg-white'
    );

    localStorage.setItem(
        'activeProjectTab',
        tabName
    );
}


document.addEventListener('DOMContentLoaded', () => {

    const saved = localStorage.getItem('activeProjectTab');

    if (saved) {

        const btn = document.querySelector(
            `[data-tab="${saved}"]`
        );

        if (btn) {
            btn.click();
        }

    }

});
</script>

</x-project_app-layout>
