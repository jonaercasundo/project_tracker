{{-- FILTER MATRIX AND DATA COMPASS BAR --}}
<div class="bg-white/90 p-4 rounded-2xl border border-slate-200/80 shadow-sm backdrop-blur-md">

    <form action="{{ route('bidding.index') }}"
          method="GET"
          class="flex flex-col lg:flex-row flex-wrap gap-3 items-stretch lg:items-center">

        {{-- Search --}}
        <div class="flex-1 min-w-[280px] relative">

            <label class="sr-only">
                Search
            </label>

            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">

                <svg class="h-4 w-4 stroke-[2.5]"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>

                </svg>

            </div>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search Project ID, Project Name, Procuring Entity or Lot Number..."
                class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition placeholder-slate-400 font-medium text-slate-800">

        </div>

        {{-- Status --}}
        <div class="w-full lg:w-56">

            <select
                name="status"
                class="w-full py-2 px-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none text-sm">

                <option value="">All Status</option>

                <option value="Draft"
                    {{ request('status')=='Draft' ? 'selected' : '' }}>
                    Draft
                </option>

                <option value="For Review"
                    {{ request('status')=='For Review' ? 'selected' : '' }}>
                    For Review
                </option>

                <option value="Published"
                    {{ request('status')=='Published' ? 'selected' : '' }}>
                    Published
                </option>

                <option value="Awarded"
                    {{ request('status')=='Awarded' ? 'selected' : '' }}>
                    Awarded
                </option>

                <option value="Cancelled"
                    {{ request('status')=='Cancelled' ? 'selected' : '' }}>
                    Cancelled
                </option>

                <option value="Completed"
                    {{ request('status')=='Completed' ? 'selected' : '' }}>
                    Completed
                </option>

            </select>

        </div>

        {{-- Buttons --}}
        <div class="flex items-center gap-3 w-full lg:w-auto lg:ml-auto justify-end">

            @if(request()->hasAny(['search','status']))

                <a href="{{ route('bidding.index') }}"
                   class="w-full lg:w-auto text-center px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-slate-700 transition">

                    Clear Filters

                </a>

            @endif

            <button
                type="submit"
                class="w-full lg:w-auto px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl shadow-sm shadow-blue-500/10 transition">

                Search Records

            </button>

        </div>

    </form>

</div>