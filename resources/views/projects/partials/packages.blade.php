<div id="packages" data-project-detail="packages" data-endpoint="{{ route('projects.packages-data', $project) }}" data-options-endpoint="{{ route('projects.detail-options', $project) }}" data-lazy="true" class="tab-content hidden space-y-4">

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
                            <span data-project-count="packages">{{ $packageCount }}</span> Packages
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

                            

                        </select>

                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <select id="packageLotFilter" aria-label="Filter packages by lot" class="w-full py-2.5 px-3 text-sm rounded-xl border border-slate-200"><option value="">All Lots</option></select>
                        <select id="packageKeystageFilter" aria-label="Filter packages by keystage" class="w-full py-2.5 px-3 text-sm rounded-xl border border-slate-200"><option value="">All Keystages</option></select>
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

                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Open this tab to load records.</td></tr>

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

            
            {{-- ========================================================= --}}
            {{-- PROJECT SETTINGS --}}
            {{-- ========================================================= --}}

            