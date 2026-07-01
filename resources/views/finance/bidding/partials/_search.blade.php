{{-- SEARCH & FILTER --}}
<div class="bg-white p-4 rounded-xl border border-slate-200">

    <form action="{{ route('bidding.index') }}"
          method="GET"
          class="flex flex-col lg:flex-row flex-wrap gap-3 items-stretch lg:items-center">

        {{-- Search --}}
        <div class="flex-1 min-w-[280px] relative">

            <label class="sr-only">
                Search
            </label>

            <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search Project ID, name, procuring entity, or lot number..."
                class="w-full text-sm rounded-lg border-slate-200 pl-8 pr-3 py-2
                       placeholder:text-slate-400
                       focus:ring-1 focus:ring-slate-400 focus:border-slate-400">

        </div>

        {{-- Status --}}
        <div class="w-full lg:w-52">

            <select
                name="status"
                class="w-full text-sm rounded-lg border-slate-200 py-2
                       focus:ring-1 focus:ring-slate-400 focus:border-slate-400">

                <option value="">All Statuses</option>

                <option value="Draft" {{ request('status')=='Draft' ? 'selected' : '' }}>
                    Draft
                </option>

                <option value="For Review" {{ request('status')=='For Review' ? 'selected' : '' }}>
                    For Review
                </option>

                <option value="Published" {{ request('status')=='Published' ? 'selected' : '' }}>
                    Published
                </option>

                <option value="Awarded" {{ request('status')=='Awarded' ? 'selected' : '' }}>
                    Awarded
                </option>

                <option value="Cancelled" {{ request('status')=='Cancelled' ? 'selected' : '' }}>
                    Cancelled
                </option>

                <option value="Completed" {{ request('status')=='Completed' ? 'selected' : '' }}>
                    Completed
                </option>

            </select>

        </div>

        {{-- Buttons --}}
        <div class="flex items-center gap-2 w-full lg:w-auto lg:ml-auto justify-end">

            @if(request()->hasAny(['search','status']))

                <a href="{{ route('bidding.index') }}"
                   class="w-full lg:w-auto text-center px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">

                    Clear

                </a>

            @endif

            <button
                type="submit"
                class="w-full lg:w-auto px-4 py-2 text-sm font-medium bg-slate-900 hover:bg-slate-800 text-white rounded-lg transition-colors">

                Search

            </button>

        </div>

    </form>

</div>