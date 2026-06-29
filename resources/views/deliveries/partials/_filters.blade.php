<script>
    window.FILTER_DEFAULTS = {
        region:       "{{ request('region') }}",
        division:     "{{ request('division') }}",
        municipality: "{{ request('municipality') }}",
    };
</script>
<form method="GET"
      action="{{ url()->current() }}"
      class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-5">

    {{-- SEARCH --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        {{-- Search --}}
        <div class="lg:col-span-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Search
            </label>

            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M21 21l-5.2-5.2M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                </svg>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search DR No., School, Project..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        {{-- Records Per Page --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Show
            </label>

            <select name="per_page"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                @foreach([10,20,30,50,100] as $size)
                    <option value="{{ $size }}"
                        @selected(request('per_page',10)==$size)>
                        {{ $size }} Records
                    </option>
                @endforeach

            </select>
        </div>

    </div>

    <hr class="border-slate-200">

    {{-- FILTERS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Year --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Year
            </label>

            <select id="year"
                    name="year"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                <option value="">All Years</option>

                @foreach($years as $y)
                    <option value="{{ $y }}"
                        @selected(request('year')==$y)>
                        {{ $y }}
                    </option>
                @endforeach

            </select>
        </div>

        {{-- Project --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Project
            </label>

            <select id="project"
                    name="project"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                <option value="">All Projects</option>

                @foreach($projects as $project)
                    <option value="{{ $project->project_id }}"
                        @selected(request('project')==$project->project_id)>
                        {{ $project->project_name }}
                    </option>
                @endforeach

            </select>
        </div>

        {{-- Lot --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Lot
            </label>

            <select id="lot" name="lot" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <option value="">All Lots</option>
                @foreach($lots as $l)
                    <option value="{{ $l->lot_id }}" @selected(request('lot') == $l->lot_id)>
                        {{ $l->lot_name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Region --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Region
            </label>

            <select id="region"
                    name="region"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                <option value="">All Regions</option>

            </select>
        </div>

        {{-- Division --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Division
            </label>

            <select id="division"
                    name="division"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                <option value="">All Divisions</option>

            </select>
        </div>

        {{-- Municipality --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Municipality
            </label>

            <select id="municipality"
                    name="municipality"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                <option value="">All Municipalities</option>

            </select>
        </div>

    </div>

    {{-- ACTION BUTTONS --}}
    <div class="flex items-center justify-between border-t border-slate-200 pt-4">

        <a href="{{ url()->current() }}"
           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-semibold transition">
            Reset
        </a>

        <button type="submit"
                class="px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition">
            Apply Filters
        </button>

    </div>

</form>