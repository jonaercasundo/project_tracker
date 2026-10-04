<div id="items" data-project-detail="items" data-endpoint="{{ route('projects.items-data', $project) }}" data-options-endpoint="{{ route('projects.detail-options', $project) }}" data-lazy="true" class="tab-content hidden space-y-4">

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
                            <span data-project-count="items">{{ $itemCount }}</span> Items
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

                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Open this tab to load records.</td></tr>

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

            
            {{-- ========================================================= --}}
            {{-- PACKAGES --}}
            {{-- ========================================================= --}}

            