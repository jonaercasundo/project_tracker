
<form method="GET" action="{{ url()->current() }}" id="filter-form" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-5">
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
    <div id="filter-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">

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
        <div class="xl:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Project
            </label>

            <div
                x-data="{
                    options: @js($projects->map(fn ($project) => ['id' => (string) $project->project_id, 'name' => (string) $project->project_name])->values()),
                    yearId: @js((string) request('year', '')),
                    selectedId: @js((string) request('project', '')),
                    search: '',
                    open: false,
                    activeIndex: 0,
                    dropdownStyle: {},
                    get availableOptions() {
                        return this.yearId ? [{ id: '', name: 'All Projects' }, ...this.options] : [];
                    },
                    get filteredOptions() {
                        const term = this.search.trim().toLocaleLowerCase();

                        return this.availableOptions.filter(option => option.name.toLocaleLowerCase().includes(term));
                    },
                    get selectedName() {
                        return this.availableOptions.find(option => option.id === this.selectedId)?.name ?? (this.yearId ? 'All Projects' : 'Select Year First');
                    },
                    positionDropdown() {
                        const trigger = this.$refs.trigger.getBoundingClientRect();
                        const margin = 8;
                        const width = Math.min(Math.max(trigger.width, 360), 480, window.innerWidth - margin * 2);
                        const left = Math.max(margin, Math.min(trigger.left, window.innerWidth - width - margin));
                        const below = window.innerHeight - trigger.bottom - margin;
                        const above = trigger.top - margin;
                        const placeBelow = below >= 220 || below >= above;
                        const availableHeight = Math.max(0, placeBelow ? below : above);
                        const maxHeight = Math.min(350, availableHeight);
                        const top = placeBelow ? trigger.bottom + 4 : trigger.top - maxHeight - 4;

                        this.dropdownStyle = { top: `${top}px`, left: `${left}px`, width: `${width}px`, maxHeight: `${maxHeight}px` };
                    },
                    openDropdown() {
                        if (!this.yearId) return;

                        this.open = true;
                        const selectedIndex = this.filteredOptions.findIndex(option => option.id === this.selectedId);
                        this.activeIndex = Math.max(0, selectedIndex);
                        this.positionDropdown();
                        this.$nextTick(() => this.$refs.search.focus());
                    },
                    closeDropdown() {
                        this.open = false;
                        this.search = '';
                        this.$refs.trigger.focus();
                    },
                    moveActive(direction) {
                        if (!this.filteredOptions.length) return;

                        this.activeIndex = (this.activeIndex + direction + this.filteredOptions.length) % this.filteredOptions.length;
                        this.$nextTick(() => this.$refs.options?.querySelector(`[data-option-index='${this.activeIndex}']`)?.scrollIntoView({ block: 'nearest' }));
                    },
                    selectActive() {
                        const option = this.filteredOptions[this.activeIndex];

                        if (option) this.selectOption(option);
                    },
                    selectOption(option) {
                        this.selectedId = option.id;
                        this.$refs.project.value = option.id;
                        this.$refs.project.dispatchEvent(new Event('change', { bubbles: true }));
                        this.closeDropdown();
                    },
                    handleSearch() {
                        this.activeIndex = 0;
                    },
                }"
                @click.outside="open = false; search = ''"
                @keydown.escape.window="open = false; search = ''"
                @keydown.tab="open = false; search = ''"
                @resize.window="open && positionDropdown()"
                @scroll.window.capture="open && positionDropdown()"
                class="relative">
                <input type="hidden" id="project" name="project" value="{{ request('project', '') }}" x-ref="project" :disabled="!yearId" @disabled(!request()->filled('year'))>

                <button
                    type="button"
                    x-ref="trigger"
                    @click="open ? closeDropdown() : openDropdown()"
                    @keydown.arrow-down.prevent="open ? moveActive(1) : openDropdown()"
                    @keydown.arrow-up.prevent="open ? moveActive(-1) : openDropdown()"
                    @keydown.enter.prevent="open ? selectActive() : openDropdown()"
                    @keydown.space.prevent="open ? selectActive() : openDropdown()"
                    @keydown.escape.stop="open && closeDropdown()"
                    :disabled="!yearId"
                    @disabled(!request()->filled('year'))
                    :aria-expanded="open.toString()"
                    aria-haspopup="listbox"
                    aria-controls="project-options"
                    class="flex min-h-[42px] w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-left text-sm text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:text-slate-400 disabled:hover:border-slate-200">
                    <span class="min-w-0 whitespace-normal break-words" x-text="selectedName">{{ $projects->firstWhere('project_id', request('project'))?->project_name ?? (request()->filled('year') ? 'All Projects' : 'Select Year First') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition.origin.top.left
                    :style="dropdownStyle"
                    class="fixed z-[100] flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5">
                    <div class="border-b border-slate-100 p-3">
                        <label for="project-search" class="sr-only">Search projects</label>
                        <input
                            id="project-search"
                            x-ref="search"
                            x-model="search"
                            @input="handleSearch()"
                            @keydown.arrow-down.prevent="moveActive(1)"
                            @keydown.arrow-up.prevent="moveActive(-1)"
                            @keydown.enter.prevent="selectActive()"
                            @keydown.escape.stop="closeDropdown()"
                            type="search"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-controls="project-options"
                            :aria-activedescendant="filteredOptions[activeIndex] ? `project-option-${filteredOptions[activeIndex].id || 'all'}` : null"
                            :aria-expanded="open.toString()"
                            placeholder="Search projects..."
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <ul
                        id="project-options"
                        x-ref="options"
                        role="listbox"
                        aria-label="Projects"
                        class="min-h-0 flex-1 overflow-y-auto p-1.5"
                        :style="{ maxHeight: `${Math.max(0, parseInt(dropdownStyle.maxHeight ?? '350', 10) - 64)}px` }">
                        <template x-for="(option, index) in filteredOptions" :key="option.id || 'all-projects'">
                            <li
                                :id="`project-option-${option.id || 'all'}`"
                                :data-option-index="index"
                                role="option"
                                :aria-selected="(option.id === selectedId).toString()"
                                @click="selectOption(option)"
                                @mouseenter="activeIndex = index"
                                class="flex cursor-pointer items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-sm leading-5 transition"
                                :class="option.id === selectedId ? 'bg-blue-50 font-semibold text-blue-800' : (index === activeIndex ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50')">
                                <span class="min-w-0 whitespace-normal break-words" x-text="option.name"></span>
                                <svg x-show="option.id === selectedId" class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.415l-7.1 7.16a1 1 0 0 1-1.42.003l-3.9-3.9a1 1 0 1 1 1.414-1.414l3.19 3.19 6.393-6.448a1 1 0 0 1 1.417-.006Z" clip-rule="evenodd" />
                                </svg>
                            </li>
                        </template>

                        <li x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">
                            No projects match your search.
                        </li>
                    </ul>
                </div>
            </div>
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

        {{-- Logistics Status --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Logistics Status
            </label>
            <select name="logistics_status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <option value="">All Logistics Statuses</option>
                <option value="pending" @selected(request('logistics_status') === 'pending')>Pending Logistics</option>
                <option value="logistics_received" @selected(request('logistics_status') === 'logistics_received')>Logistics Received</option>
                <option value="delivered" @selected(request('logistics_status') === 'delivered')>Logistics Delivered</option>
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
