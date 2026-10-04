<div id="schools" data-project-detail="schools" data-endpoint="{{ route('projects.schools-data', $project) }}" data-options-endpoint="{{ route('projects.detail-options', $project) }}" data-lazy="true" class="tab-content hidden space-y-6">

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

                            <span data-project-count="schools">{{ number_format($schoolCount) }}</span>

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

                            {{ number_format($regionCount) }}

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

                            {{ number_format($divisionCount) }}

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

                            {{ number_format($municipalityCount) }}

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
                                <span data-project-count="schools">{{ $schoolCount }}</span> Schools
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
                                            tracking-wider text-slate-500 text-right whitespace-nowrap">
                                        Action
                                    </th>

                                </tr>
                            </thead>


                            <tbody
                                id="schoolsTableBody"
                                class="divide-y divide-slate-100 bg-white">

                                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">Open this tab to load records.</td></tr>

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
            

            {{-- ========================================================= --}}
            {{-- LOTS --}}
            {{-- ========================================================= --}}

            