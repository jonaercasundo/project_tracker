<x-project_app-layout>
<div
    x-data="{ addProjectOpen: false }"
    @keydown.escape.window="addProjectOpen = false"
    class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
>

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Projects Directory
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Manage master contracts, timelines, agencies, and fulfillment status
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button
                @click="addProjectOpen = true"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl whitespace-nowrap
                    bg-gradient-to-b from-blue-500 to-blue-600 text-white
                    hover:from-blue-600 hover:to-blue-700
                    active:scale-95
                    shadow-sm hover:shadow-md
                    ring-1 ring-inset ring-white/10
                    transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                New Project
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">

        <div class="lg:col-span-4">
            <label for="search" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Search</label>
            <div class="relative">
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Ref No, contract no, title, or keywords..."
                    class="w-full text-sm rounded-xl border-slate-200 pl-8 pr-3 py-2
                           placeholder:text-slate-400
                           focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                </svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="year" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Year</label>
            <select id="year" class="w-full text-sm rounded-xl border-slate-200 py-2
                                     focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Years</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <label for="agency" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Agency</label>
            <select id="agency" class="w-full text-sm rounded-xl border-slate-200 py-2
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Agencies</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency }}" {{ request('agency') == $agency ? 'selected' : '' }}>{{ $agency }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <label for="status" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Status</label>
            <select id="status" class="w-full text-sm rounded-xl border-slate-200 py-2
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2 flex gap-2">
            @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                <a href="{{ url()->current() }}" title="Clear filters"
                    class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors flex items-center justify-center h-9 w-9 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            @endif
            <button id="filterBtn"
                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl h-9
                    bg-gradient-to-b from-blue-500 to-blue-600 text-white
                    hover:from-blue-600 hover:to-blue-700
                    active:scale-95
                    shadow-sm hover:shadow-md
                    ring-1 ring-inset ring-white/10
                    transition-all duration-200">
                Apply
            </button>
        </div>

    </div>

    {{-- SORTING LOGIC HELPER --}}
    @php
        $buildSortUrl = function($column) {
            $currentSort = request('sort_by');
            $currentOrder = request('sort_order', 'asc');
            $nextOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery([
                'sort_by' => $column,
                'sort_order' => $nextOrder
            ]);
        };

        $sortIcon = function($column) {
            $active = request('sort_by') === $column;
            $order = request('sort_order', 'asc');
            return '<span class="inline-flex flex-col ml-1 ' . ($active ? 'text-slate-600' : 'text-slate-300') . '">
                <svg class="w-2.5 h-2.5 ' . ($active && $order === 'desc' ? 'opacity-30' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M4 14h16L12 6z"/></svg>
                <svg class="w-2.5 h-2.5 -mt-1 ' . ($active && $order === 'asc' ? 'opacity-30' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10h16l-8 8z"/></svg>
            </span>';
        };
    @endphp

    {{-- TABLE --}}
    <div class="w-full overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left border-collapse table-auto text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/70 text-slate-500 uppercase tracking-wide font-bold text-[11px]">

                    <th class="py-3 px-5">
                        <a href="{{ $buildSortUrl('project_name') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Project {!! $sortIcon('project_name') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('contract_amount') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Contract Amount {!! $sortIcon('contract_amount') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4 text-center">
                        <a href="{{ $buildSortUrl('start_date') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Timeline {!! $sortIcon('start_date') !!}
                        </a>
                    </th>

                    <th class="py-3 px-4">
                        <a href="{{ $buildSortUrl('status') }}" class="inline-flex items-center hover:text-slate-800 transition-colors">
                            Status {!! $sortIcon('status') !!}
                        </a>
                    </th>

                    <th class="py-3 px-5 text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($projects as $project)
                    <tr class="hover:bg-slate-50/50 transition-colors">

                        {{-- PROJECT: title + ref no / contract no / agency as a meta line underneath --}}
                        <td class="py-3 px-5">
                            <div class="max-w-[380px]">
                                <div class="font-semibold text-slate-900 truncate" title="{{ $project->project_name }}">
                                    {{ $project->project_name }}
                                </div>
                                <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 mt-1 text-[11px] text-slate-400 font-medium">
                                    <span class="inline-flex items-center gap-1 font-mono text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                        {{ $project->ref_no }}
                                    </span>
                                    @if(!empty($project->contract_number))
                                        <span class="text-slate-300">·</span>
                                        <span>Contract No. {{ $project->contract_number }}</span>
                                    @endif
                                    @if(!empty($project->agency))
                                        <span class="text-slate-300">·</span>
                                        <span>{{ $project->agency }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-4 font-extrabold text-slate-900 tabular-nums whitespace-nowrap">
                            <span class="text-slate-400 mr-0.5 font-medium">₱</span>{{ number_format($project->contract_amount, 2) }}
                        </td>

                        <td class="py-3 px-4 text-slate-500 text-center whitespace-nowrap text-xs font-medium">
                            {{ $project->start_date->format('M d, Y') }}
                            <span class="text-slate-300 mx-1">→</span>
                            {{ $project->end_date->format('M d, Y') }}
                        </td>

                        <td class="py-3 px-4">
                            @php
                                $statusLower = strtolower($project->status);
                                [$badgeClasses, $dotClasses] = match(true) {
                                    str_contains($statusLower, 'complete') || str_contains($statusLower, 'deliver') => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                                    str_contains($statusLower, 'pend') || str_contains($statusLower, 'progress') => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
                                    str_contains($statusLower, 'cancel') || str_contains($statusLower, 'drop') => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
                                    default => ['bg-slate-100 text-slate-600 border-slate-300', 'bg-slate-400']
                                };
                            @endphp
                            <span class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold border {{ $badgeClasses }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ strtoupper($project->status) }}
                            </span>
                        </td>

                        <td class="py-3 px-5 text-right whitespace-nowrap">
                            <a href="{{ route('projects.show', $project) }}"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex"
                               title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.603 10.601z"></path>
                            </svg>
                            <h3 class="text-sm font-semibold text-slate-600">No projects match your criteria</h3>
                            <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or search keywords.</p>
                            @if(request()->anyFilled(['search', 'year', 'agency', 'status', 'sort_by']))
                                <a href="{{ url()->current() }}" class="inline-block mt-3 px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                                    Reset Filters
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($projects->hasPages())
        <div class="flex items-center justify-center mt-2">
            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl">
                {{ $projects->links() }}
            </span>
        </div>
    @endif

    {{-- ADD PROJECT MODAL (Alpine.js) --}}
    <div
        x-show="addProjectOpen"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        {{-- backdrop --}}
        <div
            x-show="addProjectOpen"
            x-transition.opacity
            @click="addProjectOpen = false"
            class="fixed inset-0 bg-slate-900/50"
        ></div>

        {{-- panel --}}
        <div
            x-show="addProjectOpen"
            x-transition
            @click.outside="addProjectOpen = false"
            x-data="projectForm()"
            class="relative bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
        >
            <form
                id="addProjectForm"
                method="POST"
                action="{{ route('projects.store') }}"
                enctype="multipart/form-data"
                @submit="onSubmit($event)"
            >
                @csrf

                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70 rounded-t-2xl">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900">New Project</h3>
                    <button type="button" @click="addProjectOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">PhilGEPS Ref No</label>
                            <input type="text" name="ref_no" required
                                class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Contract No.</label>
                            <input type="text" name="contract_number"
                                class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Project Name</label>
                        <input type="text" name="project_name" required
                            class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- ===================================================== --}}
                    {{-- CURRENCY FIELDS --}}
                    {{-- ===================================================== --}}
                    <div class="grid grid-cols-2 gap-4">

                        {{-- CONTRACT AMOUNT --}}
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">
                                Contract Amount <span class="text-rose-500">*</span>
                            </label>

                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                                    ₱
                                </span>

                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    placeholder="0.00"
                                    x-ref="amountInput"
                                    @input="formatCurrencyInput('amount', $event)"
                                    @blur="formatCurrencyBlur('amount', $event)"
                                    @focus="selectAllOnFocus($event)"
                                    class="w-full text-sm rounded-xl border-slate-200 py-2 pl-7 pr-3
                                           tabular-nums
                                           focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    :class="errors.amount && 'border-rose-300 focus:border-rose-500 focus:ring-rose-500'">

                                <input type="hidden" name="contract_amount" :value="amount.raw">
                            </div>

                            <p x-show="errors.amount" x-cloak x-text="errors.amount"
                               class="mt-1 text-[11px] font-semibold text-rose-500"></p>
                        </div>

                        {{-- ABC --}}
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">
                                ABC <span class="text-rose-500">*</span>
                            </label>

                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                                    ₱
                                </span>

                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    placeholder="0.00"
                                    x-ref="abcInput"
                                    @input="formatCurrencyInput('abc', $event)"
                                    @blur="formatCurrencyBlur('abc', $event)"
                                    @focus="selectAllOnFocus($event)"
                                    class="w-full text-sm rounded-xl border-slate-200 py-2 pl-7 pr-3
                                           tabular-nums
                                           focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    :class="errors.abc && 'border-rose-300 focus:border-rose-500 focus:ring-rose-500'">

                                <input type="hidden" name="ABC" :value="abc.raw">
                            </div>

                            <p x-show="errors.abc" x-cloak x-text="errors.abc"
                               class="mt-1 text-[11px] font-semibold text-rose-500"></p>
                        </div>

                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Agency</label>
                        <select name="agency" x-model="agency" required
                            class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Agency</option>
                            <option value="Deped">Deped</option>
                            <option value="Dpwh">Dpwh</option>
                        </select>
                    </div>

                    <div x-show="agency === 'Deped'" x-cloak class="flex items-center gap-2">
                        <input type="checkbox" name="keystage" value="1" id="keystage"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="keystage" class="text-sm text-slate-600">Include Keystage</label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Start Date</label>
                            <input type="date" name="start_date" required
                                class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">End Date</label>
                            <input type="date" name="end_date" required
                                class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Status</label>
                        <select name="status" required
                            class="w-full text-sm rounded-xl border-slate-200 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Status</option>
                            <option value="Pending" selected>Pending</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Awarded</option>
                            <option value="For NOA">For NOA</option>
                            <option value="For RTA">For RTA</option>
                            <option value="For Contract Signing">For Contract Signing</option>
                            <option value="For NTP">For NTP</option>
                            <option value="Bid Evaluation">Bid Evaluation</option>
                            <option value="Bidding">Bidding</option>
                            <option value="Post Qualification">Post Qualification</option>
                            <option value="Implementation">Implementation</option>
                            <option value="Delivered">Delivered</option>
                            <option value="For Billing">For Billing</option>
                            <option value="For Collection">For Collection</option>
                            <option value="Collected">Collected</option>
                            <option value="For Development">For Development</option>
                            <option value="For Procurement">Pre Procurement</option>
                            <option value="Upcoming">Upcoming</option>
                            <option value="On Going">On Going</option>
                            <option value="For Rebid">For Rebid</option>
                        </select>
                    </div>

                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100">
                    <button type="button" @click="addProjectOpen = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl whitespace-nowrap
                            bg-gradient-to-b from-blue-500 to-blue-600 text-white
                            hover:from-blue-600 hover:to-blue-700
                            active:scale-95
                            shadow-sm hover:shadow-md
                            ring-1 ring-inset ring-white/10
                            transition-all duration-200">
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

{{-- ============================================================= --}}
{{-- ADD PROJECT FORM / CURRENCY INPUT LOGIC --}}
{{-- ============================================================= --}}
<script>
    /**
     * Shared Alpine component for the "New Project" modal.
     * Handles live currency formatting for Contract Amount and ABC:
     *  - digits + a single decimal point only
     *  - max 2 decimal places
     *  - thousands separators as you type
     *  - cursor position preserved while typing (no jump-to-end)
     *  - normalizes to X.XX on blur
     *  - hidden inputs always carry the clean numeric value the server expects
     */
    function projectForm() {
        return {
            agency: '',
            amount: { raw: '', display: '' },
            abc: { raw: '', display: '' },
            errors: { amount: '', abc: '' },

            selectAllOnFocus(event) {
                event.target.select();
            },

            // Formats live as the user types, preserving cursor position.
            formatCurrencyInput(field, event) {
                const input = event.target;
                const cursorPos = input.selectionStart;

                // How many digits sit before the caret, so we can restore
                // the caret to the same "digit position" after reformatting.
                const digitsBeforeCursor =
                    input.value.slice(0, cursorPos).replace(/[^\d]/g, '').length;

                // Strip everything except digits and dots.
                let value = input.value.replace(/[^\d.]/g, '');

                // Keep only the first decimal point.
                const firstDot = value.indexOf('.');
                if (firstDot !== -1) {
                    value =
                        value.slice(0, firstDot + 1) +
                        value.slice(firstDot + 1).replace(/\./g, '');
                }

                // Limit to 2 decimal places.
                let [wholePart, decimalPart] = value.split('.');
                if (decimalPart !== undefined) {
                    decimalPart = decimalPart.slice(0, 2);
                }

                // Strip leading zeros on the whole part (but allow "0" itself).
                wholePart = wholePart ? wholePart.replace(/^0+(?=\d)/, '') : '';

                const cleanRaw =
                    decimalPart !== undefined ? `${wholePart}.${decimalPart}` : wholePart;

                const formattedWhole = wholePart
                    ? Number(wholePart).toLocaleString('en-US')
                    : '';

                const formatted =
                    decimalPart !== undefined
                        ? `${formattedWhole}.${decimalPart}`
                        : formattedWhole;

                this[field] = { raw: cleanRaw, display: formatted };
                input.value = formatted;
                this.errors[field] = '';

                // Restore cursor to the same digit position.
                let digitCount = 0;
                let newPos = formatted.length;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) digitCount++;
                    if (digitCount >= digitsBeforeCursor) {
                        newPos = i + 1;
                        break;
                    }
                }
                requestAnimationFrame(() => input.setSelectionRange(newPos, newPos));
            },

            // Normalizes the value to always show 2 decimal places once the
            // user leaves the field, e.g. "500" -> "500.00", "12." -> "12.00".
            formatCurrencyBlur(field, event) {
                const raw = this[field].raw;

                if (raw === '' || raw === '.') {
                    this[field] = { raw: '', display: '' };
                    event.target.value = '';
                    return;
                }

                const num = Number(raw);

                if (isNaN(num)) {
                    this[field] = { raw: '', display: '' };
                    event.target.value = '';
                    return;
                }

                const fixedRaw = num.toFixed(2);
                const display = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                this[field] = { raw: fixedRaw, display };
                event.target.value = display;
            },

            onSubmit(event) {
                this.errors = { amount: '', abc: '' };
                let hasError = false;

                if (!this.amount.raw || Number(this.amount.raw) <= 0) {
                    this.errors.amount = 'Enter a contract amount greater than ₱0.00';
                    hasError = true;
                }

                if (!this.abc.raw || Number(this.abc.raw) <= 0) {
                    this.errors.abc = 'Enter an ABC greater than ₱0.00';
                    hasError = true;
                }

                if (hasError) {
                    event.preventDefault();
                }
            },
        };
    }
</script>
</x-project_app-layout>