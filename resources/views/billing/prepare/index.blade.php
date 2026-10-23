<x-project_app-layout>
    <x-slot name="header">
        <div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">Operations · Billing</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Prepare for Billing</h1>
            </div>
        </div>
    </x-slot>

    <main id="billing-preparation" class="mx-auto max-w-7xl space-y-5"
          data-scan-url="{{ route('billing.prepare.scan') }}"
          data-search-url="{{ route('billing.prepare.search') }}"
          data-range-url="{{ route('billing.prepare.range') }}"
          data-all-url="{{ route('billing.prepare.all') }}"
          data-confirm-url="{{ route('billing.prepare.confirm') }}"
          data-max-batch-size="{{ config('billing.max_batch_size', 500) }}"
          data-can-confirm="{{ auth()->user()->can('billing.for-billing.mark') ? 'true' : 'false' }}">
        <section class="rounded-xl border border-cyan-200 bg-cyan-50/70 px-5 py-4 text-sm text-cyan-950">
            <p class="font-bold">This review submits deliveries to the existing Billing queue.</p>
            <p class="mt-1 text-cyan-900">Submission does not create an invoice or change payment status. Eligible delivery states follow the current workflow: Delivered or Accepted, and not already For Billing, Billed, or On Hold.</p>
            <p class="mt-1 text-cyan-900">Proof of Delivery is shown for review. The existing For Billing transition does not require a POD record, so missing POD is not treated as a new blocker.</p>
        </section>

        <form id="billing-preparation-filters" method="GET" action="{{ route('billing.prepare') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="space-y-1 text-xs font-semibold text-slate-600">Year
                <select id="billing-year" name="year" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Years</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected(($filters['year'] ?? '') == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </label>
            <div class="min-w-0 space-y-1 text-xs font-semibold text-slate-600">
                <label for="billing-project-trigger">Project</label>
                <div x-data="{
                    options: @js($projects->map(fn ($project) => ['id' => (string) $project->project_id, 'name' => (string) $project->project_name, 'code' => (string) ($project->project_code ?: $project->ref_no ?: '')])->values()),
                    yearId: @js((string) ($filters['year'] ?? '')),
                    selectedId: @js((string) ($filters['project_id'] ?? '')),
                    search: '', open: false, activeIndex: 0, dropdownStyle: {},
                    get availableOptions() { return this.yearId ? [{ id: '', name: 'All Projects' }, ...this.options] : []; },
                    get filteredOptions() { const term = this.search.trim().toLocaleLowerCase(); return this.availableOptions.filter(option => `${option.name} ${option.code ?? ''}`.toLocaleLowerCase().includes(term)); },
                    get selectedName() { return this.availableOptions.find(option => option.id === this.selectedId)?.name ?? (this.yearId ? 'All Projects' : 'Select Year First'); },
                    positionDropdown() {
                        const trigger = this.$refs.trigger.getBoundingClientRect(); const margin = 8;
                        const width = Math.min(Math.max(trigger.width, 360), 480, window.innerWidth - margin * 2);
                        const left = Math.max(margin, Math.min(trigger.left, window.innerWidth - width - margin));
                        const below = window.innerHeight - trigger.bottom - margin; const above = trigger.top - margin;
                        const placeBelow = below >= 220 || below >= above; const availableHeight = Math.max(0, placeBelow ? below : above);
                        const maxHeight = Math.min(350, availableHeight); const top = placeBelow ? trigger.bottom + 4 : trigger.top - maxHeight - 4;
                        this.dropdownStyle = { top: `${top}px`, left: `${left}px`, width: `${width}px`, maxHeight: `${maxHeight}px` };
                    },
                    openDropdown() {
                        if (!this.yearId) return; this.open = true;
                        const selectedIndex = this.filteredOptions.findIndex(option => option.id === this.selectedId);
                        this.activeIndex = Math.max(0, selectedIndex); this.positionDropdown(); this.$nextTick(() => this.$refs.search.focus());
                    },
                    closeDropdown() { this.open = false; this.search = ''; this.$refs.trigger.focus(); },
                    moveActive(direction) {
                        if (!this.filteredOptions.length) return;
                        this.activeIndex = (this.activeIndex + direction + this.filteredOptions.length) % this.filteredOptions.length;
                        this.$nextTick(() => this.$refs.options?.querySelector(`[data-option-index='${this.activeIndex}']`)?.scrollIntoView({ block: 'nearest' }));
                    },
                    selectActive() { const option = this.filteredOptions[this.activeIndex]; if (option) this.selectOption(option); },
                    selectOption(option) {
                        this.selectedId = option.id; this.$refs.project.value = option.id;
                        this.$refs.project.dispatchEvent(new Event('change', { bubbles: true })); this.closeDropdown();
                    },
                }"
                    @click.outside="open = false; search = ''" @keydown.escape.window="open = false; search = ''" @keydown.tab="open = false; search = ''"
                    @resize.window="open && positionDropdown()" @scroll.window.capture="open && positionDropdown()" class="relative">
                    <input type="hidden" id="billing-project" name="project_id" value="{{ $filters['project_id'] ?? '' }}" x-ref="project" :disabled="!yearId" @disabled(empty($filters['year']))>
                    <button id="billing-project-trigger" type="button" x-ref="trigger" @click="open ? closeDropdown() : openDropdown()"
                        @keydown.arrow-down.prevent="open ? moveActive(1) : openDropdown()" @keydown.arrow-up.prevent="open ? moveActive(-1) : openDropdown()"
                        @keydown.enter.prevent="open ? selectActive() : openDropdown()" @keydown.space.prevent="open ? selectActive() : openDropdown()" @keydown.escape.stop="open && closeDropdown()"
                        :disabled="!yearId" @disabled(empty($filters['year'])) :aria-expanded="open.toString()" aria-haspopup="listbox" aria-controls="billing-project-options"
                        class="flex min-h-[42px] w-full items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm font-normal text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                        <span class="min-w-0 whitespace-normal break-words" x-text="selectedName">{{ $projects->firstWhere('project_id', $filters['project_id'] ?? null)?->project_name ?? (!empty($filters['year']) ? 'All Projects' : 'Select Year First') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.left :style="dropdownStyle" class="fixed z-[100] flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-700 shadow-xl ring-1 ring-black/5">
                        <div class="border-b border-slate-100 p-3"><label for="billing-project-search" class="sr-only">Search projects</label>
                            <input id="billing-project-search" x-ref="search" x-model="search" @input="activeIndex = 0" @keydown.arrow-down.prevent="moveActive(1)" @keydown.arrow-up.prevent="moveActive(-1)" @keydown.enter.prevent="selectActive()" @keydown.escape.stop="closeDropdown()" type="search" role="combobox" aria-autocomplete="list" aria-controls="billing-project-options" :aria-expanded="open.toString()" placeholder="Search projects..." class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-normal text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <ul id="billing-project-options" x-ref="options" role="listbox" aria-label="Projects" class="min-h-0 flex-1 overflow-y-auto p-1.5" :style="{ maxHeight: `${Math.max(0, parseInt(dropdownStyle.maxHeight ?? '350', 10) - 64)}px` }">
                            <template x-for="(option, index) in filteredOptions" :key="option.id || 'all-projects'">
                                <li :id="`billing-project-option-${option.id || 'all'}`" :data-option-index="index" role="option" :aria-selected="(option.id === selectedId).toString()" @click="selectOption(option)" @mouseenter="activeIndex = index" class="flex cursor-pointer items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-normal leading-5 transition" :class="option.id === selectedId ? 'bg-blue-50 font-semibold text-blue-800' : (index === activeIndex ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50')">
                                    <span class="min-w-0 whitespace-normal break-words"><span class="block" x-text="option.name"></span><span x-show="option.code" class="mt-0.5 block text-xs font-normal text-slate-500" x-text="option.code"></span></span>
                                    <span x-show="option.id === selectedId" class="shrink-0 text-blue-700" aria-hidden="true">✓</span>
                                </li>
                            </template>
                            <li x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-sm font-normal text-slate-500">No projects match your search.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Lot
                <select name="lot_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All lots</option>
                    @foreach ($lots as $lot)
                        <option value="{{ $lot->lot_id }}" @selected(($filters['lot_id'] ?? '') == $lot->lot_id)>{{ $lot->lot_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Division
                <select name="division" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All divisions</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division }}" @selected(($filters['division'] ?? '') === $division)>{{ $division }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">School
                <select name="school_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All schools</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->school_id }}" @selected(($filters['school_id'] ?? '') === $school->school_id)>{{ $school->school_name }} · {{ $school->school_id }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery date from
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery date to
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Search results
                <input id="billing-filter-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="DR, school, project, or lot" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <div class="flex items-end gap-2">
                <button class="rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Apply filters</button>
                <a href="{{ route('billing.prepare') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-blue-700">Choose how to find delivery receipts</p>
                <div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-4" role="tablist" aria-label="Delivery receipt selection method">
                    <button type="button" role="tab" aria-selected="true" data-billing-tab="scan" class="rounded-lg bg-blue-700 px-3 py-2.5 text-sm font-bold text-white">1 · Scan QR</button>
                    <button type="button" role="tab" aria-selected="false" data-billing-tab="search" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">2 · Search DR</button>
                    <button type="button" role="tab" aria-selected="false" data-billing-tab="range" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">3 · DR From–To</button>
                    <button type="button" role="tab" aria-selected="false" data-billing-tab="all" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">4 · All Delivered</button>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <section data-billing-panel="scan" role="tabpanel" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)]">
                    <div class="space-y-3">
                        <div>
                            <h2 class="font-bold text-slate-900">Scan a delivery QR code</h2>
                            <p class="mt-1 text-sm text-slate-500">Use a webcam, mobile camera, USB scanner, or paste the QR text below. Scanning only adds a record to this review.</p>
                        </div>
                        <div class="relative flex min-h-48 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950 sm:min-h-60">
                            <video id="billing-preparation-camera" class="hidden h-full max-h-80 w-full object-cover" playsinline muted aria-label="Camera preview for QR scanning"></video>
                            <div id="billing-preparation-camera-placeholder" class="px-6 text-center text-sm text-slate-300">Camera is off. Start the camera or use scanner/manual entry.</div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button id="billing-preparation-camera-start" type="button" class="rounded-lg bg-cyan-300 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-cyan-200">Start camera</button>
                            <button id="billing-preparation-camera-stop" type="button" class="hidden rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Stop camera</button>
                            <span id="billing-preparation-camera-support" class="self-center text-xs text-slate-500" role="status">Camera scanning requires HTTPS or localhost and a supported browser.</span>
                        </div>
                    </div>
                    <form id="billing-preparation-scan-form" class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <label for="billing-preparation-scan-input" class="text-sm font-bold text-slate-900">USB scanner / manual QR entry</label>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Most handheld scanners enter the decoded QR value and press Enter.</p>
                        </div>
                        <textarea id="billing-preparation-scan-input" rows="3" maxlength="2048" class="w-full rounded-lg border-slate-300 font-mono text-xs focus:border-blue-500 focus:ring-blue-500" placeholder="Scan or paste a signed delivery QR" required></textarea>
                        <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Add scanned DR</button>
                    </form>
                </section>

                <section data-billing-panel="search" role="tabpanel" class="hidden space-y-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Search and select DRs</h2>
                        <p class="mt-1 text-sm text-slate-500">Search by DR number, school, project, or lot. Results are server paginated, and selected rows stay in the review when you change pages.</p>
                    </div>
                    <form id="billing-preparation-search-form" class="flex flex-col gap-2 sm:flex-row">
                        <label for="billing-preparation-search-input" class="sr-only">Search delivery receipts</label>
                        <input id="billing-preparation-search-input" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="DR number, school, project, or lot">
                        <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Search DRs</button>
                    </form>
                </section>

                <section data-billing-panel="range" role="tabpanel" class="hidden space-y-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Preview a DR number range</h2>
                        <p class="mt-1 text-sm text-slate-500">DR numbers in the current database use numeric identifiers. Choose a Year and Project first. Missing numbers in the range are simply skipped.</p>
                    </div>
                    <form id="billing-preparation-range-form" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <label class="space-y-1 text-xs font-semibold text-slate-600">DR Number From
                            <input name="dr_from" inputmode="numeric" pattern="[0-9]{1,18}" maxlength="18" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="1001">
                        </label>
                        <label class="space-y-1 text-xs font-semibold text-slate-600">DR Number To
                            <input name="dr_to" inputmode="numeric" pattern="[0-9]{1,18}" maxlength="18" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="1050">
                        </label>
                        <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Preview range</button>
                    </form>
                </section>

                <section data-billing-panel="all" role="tabpanel" class="hidden space-y-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Select all eligible Delivered DRs</h2>
                        <p class="mt-1 text-sm text-slate-500">This uses the Year, Project, Lot, Division, School, and date filters above. At least one scope filter is required; large previews must be narrowed to the configured batch limit.</p>
                    </div>
                    <button id="billing-preparation-all-button" type="button" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Preview all eligible delivered DRs</button>
                </section>

                <div id="billing-preparation-feedback" class="hidden rounded-lg border px-4 py-3 text-sm" role="status" aria-live="polite"></div>
                <section id="billing-preparation-review" class="hidden space-y-3 border-t border-slate-200 pt-4" aria-label="Delivery receipt review">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">Shared review</p>
                            <h2 class="mt-1 text-lg font-extrabold text-slate-900">Delivery receipts</h2>
                            <p id="billing-preparation-summary" class="mt-1 text-sm text-slate-500">No deliveries loaded.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button id="billing-preparation-clear" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-rose-400 hover:text-rose-700">Clear review</button>
                            <button id="billing-preparation-confirm" type="button" disabled class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">Confirm for Billing</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-[1050px] w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-3"><span class="sr-only">Select</span></th>
                                    <th class="px-3 py-3">DR Number</th>
                                    <th class="px-3 py-3">Project</th>
                                    <th class="px-3 py-3">Lot / School</th>
                                    <th class="px-3 py-3">Delivery Date</th>
                                    <th class="px-3 py-3">Delivery Status</th>
                                    <th class="px-3 py-3">POD</th>
                                    <th class="px-3 py-3">Billing Status</th>
                                    <th class="px-3 py-3">Eligibility / Remarks</th>
                                    <th class="px-3 py-3"><span class="sr-only">Remove</span></th>
                                </tr>
                            </thead>
                            <tbody id="billing-preparation-results" class="divide-y divide-slate-100">
                                <tr><td colspan="10" class="px-5 py-10 text-center text-slate-500">Choose a method above to find deliveries.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="billing-preparation-pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600"></div>
                </section>
            </div>
        </section>
    </main>

    <dialog id="billing-preparation-confirm-dialog" class="w-[min(36rem,calc(100vw-2rem))] rounded-xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/40">
        <form method="dialog" class="space-y-4 p-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">Final confirmation</p>
                <h2 class="mt-1 text-lg font-extrabold text-slate-900">Send deliveries to Billing?</h2>
            </div>
            <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
                <dt class="font-semibold text-slate-500">Projects</dt><dd id="billing-confirm-projects" class="break-words text-slate-900"></dd>
                <dt class="font-semibold text-slate-500">Selected DRs</dt><dd id="billing-confirm-count" class="text-slate-900"></dd>
                <dt class="font-semibold text-slate-500">Eligible</dt><dd id="billing-confirm-eligible" class="text-slate-900"></dd>
                <dt class="font-semibold text-slate-500">Excluded</dt><dd id="billing-confirm-excluded" class="text-slate-900"></dd>
                <dt class="font-semibold text-slate-500">Destination</dt><dd class="text-slate-900">Existing Billing queue · no invoice will be created</dd>
            </dl>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button value="cancel" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Go back</button>
                @can('billing.for-billing.mark')
                    <button id="billing-preparation-confirm-submit" type="button" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Confirm for Billing</button>
                @else
                    <p class="text-sm font-semibold text-amber-800">You have read-only access. Ask an authorized billing user to confirm this batch.</p>
                @endcan
            </div>
        </form>
    </dialog>
</x-project_app-layout>
