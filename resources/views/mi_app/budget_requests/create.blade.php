<x-mi_app>

    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-0 pb-28">

        {{-- =========================================================
            HEADER
        ========================================================== --}}

        <div class="flex flex-wrap items-start justify-between gap-3 mb-6">

            <div class="flex items-center gap-3">

                <a
                    href="{{ route('budget_requests.index') }}"
                    class="flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-400 hover:text-slate-700 hover:border-slate-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200"
                    aria-label="Back to budget requests"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div>
                    <h1 class="text-xl font-bold text-slate-900 leading-tight">
                        New budget request
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tell us where you're going, then itemize what it will cost.
                    </p>
                </div>

            </div>

            {{-- FX STATUS PILL --}}

            <div
                id="fx-pill"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 bg-white text-[11px] text-slate-500"
            >
                <span id="fx-dot" class="w-2 h-2 rounded-full bg-slate-300 animate-pulse"></span>
                <span id="fx-status">Loading exchange rates...</span>
            </div>

        </div>


        {{-- =========================================================
            VALIDATION ERRORS
        ========================================================== --}}

        @if ($errors->any())

            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex gap-3" role="alert">

                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
                </svg>

                <div>
                    <p class="font-bold mb-1">
                        Please fix the following before submitting:
                    </p>

                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            </div>

        @endif


        {{-- =========================================================
            FORM
        ========================================================== --}}

        <form
            method="POST"
            action="{{ route('budget_requests.store') }}"
            id="budget-form"
            novalidate
        >

            @csrf


            {{-- =====================================================
                TRIP DETAILS
            ====================================================== --}}

            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-5">

                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.7 8.3a8 8 0 10-11.4 0L12 14l5.7-5.7zM12 11a1 1 0 100-2 1 1 0 000 2z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Trip details</h2>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-x-4 gap-y-4">

                    {{-- DEPARTMENT --}}

                    <div class="sm:col-span-2">

                        <label for="department" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Department <span class="text-red-500" aria-hidden="true">*</span>
                        </label>

                        <select
                            id="department"
                            name="department"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                            required
                        >
                            <option value="" disabled {{ old('department') ? '' : 'selected' }}>
                                Select department
                            </option>

                            @foreach (['Design', 'Sourcing', 'Trading', 'Sales / Merchandising', 'Accounting', 'Management', 'Other'] as $dept)
                                <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>

                    </div>


                    {{-- COUNTRY --}}

                    <div class="sm:col-span-2">

                        <label for="country" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Country
                        </label>

                        <select
                            id="country"
                            name="country"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                        >
                            <option value="">Select country</option>

                            @foreach (['Philippines', 'United States', 'Canada', 'United Kingdom', 'Japan', 'China', 'South Korea', 'Singapore', 'Australia', 'Other'] as $country)
                                <option value="{{ $country }}" {{ old('country') == $country ? 'selected' : '' }}>
                                    {{ $country }}
                                </option>
                            @endforeach
                        </select>

                    </div>


                    {{-- CITY / PLACE --}}

                    <div class="sm:col-span-2">

                        <label for="place" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            City / place
                        </label>

                        <select
                            id="place"
                            name="place"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-400"
                        >
                            <option value="">Select city / place</option>
                        </select>

                    </div>


                    {{-- TRAVEL DATES --}}

                    <div class="sm:col-span-6">

                        <div class="grid grid-cols-1 sm:grid-cols-6 gap-x-4 gap-y-4 items-start">

                            <div class="sm:col-span-2">

                                <label for="travel_date_from" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Departure date
                                </label>

                                <input
                                    id="travel_date_from"
                                    type="date"
                                    name="travel_date_from"
                                    value="{{ old('travel_date_from') }}"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                >

                            </div>

                            <div class="sm:col-span-2">

                                <label for="travel_date_to" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Return date
                                </label>

                                <input
                                    id="travel_date_to"
                                    type="date"
                                    name="travel_date_to"
                                    value="{{ old('travel_date_to') }}"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                >

                                <p id="date-range-error" class="hidden text-[11px] text-red-600 mt-1" role="alert">
                                    Return date can't be before the departure date.
                                </p>

                            </div>

                            <div class="sm:col-span-2 sm:pt-6">
                                <div
                                    id="trip-length"
                                    class="hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600"
                                >
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- OBJECTIVES --}}

                    <div class="sm:col-span-6">

                        <label for="objectives" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Objectives
                        </label>

                        <textarea
                            id="objectives"
                            name="objectives"
                            rows="2"
                            placeholder="What do you need to accomplish on this trip?"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                        >{{ old('objectives') }}</textarea>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                BUDGET BREAKDOWN
            ====================================================== --}}

            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-5">

                <div class="px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">

                    <div class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">Budget breakdown</h2>
                    </div>

                    {{-- FX RATES --}}

                    <div id="fx-rates" class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">

                        <div>
                            <span class="text-slate-400">USD → PHP</span>
                            <span id="usd-rate" class="font-bold text-slate-700 ml-1 tabular-nums">Loading...</span>
                        </div>

                        <div>
                            <span class="text-slate-400">VND → PHP</span>
                            <span id="vnd-rate" class="font-bold text-slate-700 ml-1 tabular-nums">Loading...</span>
                        </div>

                        <div id="fx-updated" class="text-slate-400"></div>

                    </div>

                </div>


                <div class="p-5">

                    <div
                        id="fx-warning"
                        class="hidden mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800"
                        role="alert"
                    >
                        Exchange rates aren't available right now. PHP amounts for USD and VND lines can't be calculated until they load.
                        <button type="button" id="fx-retry" class="font-bold underline ml-1">Try again</button>
                    </div>


                    {{-- =============================================
                        LINE ITEMS
                    ============================================== --}}

                    <div id="items-list" class="space-y-3">

                        @foreach(is_array(old('items')) && old('items') ? old('items') : [[]] as $originalIndex => $row)
                        @php($i = $loop->index)

                        <div class="item-row rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">

                            <div class="grid grid-cols-12 gap-x-3 gap-y-3 items-end">

                                {{-- EXPENSE CATEGORY --}}

                                <div class="col-span-12 sm:col-span-4">

                                    <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 mb-1">
                                        <span class="row-number inline-flex items-center justify-center w-5 h-5 rounded-md bg-slate-200 text-slate-600 text-[10px] font-bold">{{ $i + 1 }}</span>
                                        Expense category
                                    </label>

                                    <select
                                        name="items[{{ $i }}][expense_category]"
                                        class="w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                        required
                                    >
                                        <option value="">Select expense category</option>

                                        @foreach (['Airfare', 'Airport Tax', 'Hotel And Accommodation', 'Per Diem', 'Transportation', 'Communication And Petty Cash', 'Travel Insurance', 'Visa And Permit', 'Other'] as $category)
                                            <option value="{{ $category }}" @selected(($row['expense_category'] ?? '') === $category)>
                                                {{ $category }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>


                                {{-- PARTICULARS --}}

                                <div class="col-span-12 sm:col-span-5">

                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">
                                        Particulars <span class="text-red-500" aria-hidden="true">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="items[{{ $i }}][particular]"
                                        value="{{ $row['particular'] ?? '' }}"
                                        required
                                        maxlength="255"
                                        aria-label="Particulars for expense {{ $i + 1 }}"
                                        placeholder="Describe this expense"
                                        class="particular-input w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                    >

                                    @error("items.$originalIndex.particular")
                                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                    @enderror

                                </div>


                                {{-- CURRENCY --}}

                                <div class="col-span-9 sm:col-span-2">

                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">
                                        Currency
                                    </label>

                                    <select
                                        name="items[{{ $i }}][currency]"
                                        class="currency-select w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                    >
                                        <option value="PHP" @selected(($row['currency'] ?? 'PHP') === 'PHP')>PHP ₱</option>
                                        <option value="USD" @selected(($row['currency'] ?? 'PHP') === 'USD')>USD $</option>
                                        <option value="VND" @selected(($row['currency'] ?? 'PHP') === 'VND')>VND ₫</option>
                                    </select>

                                </div>


                                {{-- REMOVE --}}

                                <div class="col-span-3 sm:col-span-1 flex justify-end">

                                    <button
                                        type="button"
                                        class="remove-row flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors focus:outline-none focus:ring-2 focus:ring-red-100"
                                        aria-label="Remove line"
                                    >
                                        <svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12a2 2 0 01-2 1.8H7.9a2 2 0 01-2-1.8L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                        </svg>
                                    </button>

                                </div>

                            </div>


                            {{-- AMOUNTS --}}

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-3 pt-3 border-t border-dashed border-slate-200">

                                @foreach ([
                                    'budget_cash' => 'Cash',
                                    'budget_credit_card' => 'Credit card',
                                    'budget_travel_agent' => 'Travel agent',
                                ] as $field => $label)

                                    <div>

                                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">
                                            {{ $label }}
                                        </label>

                                        <div class="relative">

                                            <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">
                                                ₱
                                            </span>

                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                name="items[{{ $i }}][{{ $field }}]"
                                                value="{{ $row[$field] ?? '' }}"
                                                class="amount w-full py-2 pl-7 pr-2.5 text-sm text-right tabular-nums border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                                                placeholder="0.00"
                                                autocomplete="off"
                                            >

                                        </div>

                                    </div>

                                @endforeach


                                {{-- PHP EQUIVALENT --}}

                                <div>

                                    <span class="block text-[11px] font-semibold text-slate-500 mb-1">
                                        PHP equivalent
                                    </span>

                                    <input
                                        type="hidden"
                                        name="items[{{ $i }}][php_equivalent]"
                                        class="php-equivalent-input"
                                        value="0"
                                    >

                                    <div class="px-2.5 py-2 rounded-lg bg-blue-50 border border-blue-100 text-right">
                                        <span class="php-equivalent font-bold text-blue-900 text-sm tabular-nums">₱0.00</span>
                                    </div>

                                </div>

                            </div>

                        </div>

                        @endforeach

                    </div>


                    {{-- ADD LINE --}}

                    <button
                        type="button"
                        id="add-row"
                        class="mt-3 w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl border border-dashed border-slate-300 text-xs font-bold text-blue-600 hover:text-blue-800 hover:border-blue-300 hover:bg-blue-50/50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add expense line
                    </button>


                    {{-- TOTALS --}}

                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-4 gap-3">

                        <div class="rounded-xl border border-slate-200 px-3.5 py-2.5">
                            <p class="text-[11px] text-slate-500">Cash</p>
                            <p id="sum-cash" class="text-sm font-bold text-slate-800 tabular-nums">₱0.00</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 px-3.5 py-2.5">
                            <p class="text-[11px] text-slate-500">Credit card</p>
                            <p id="sum-card" class="text-sm font-bold text-slate-800 tabular-nums">₱0.00</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 px-3.5 py-2.5">
                            <p class="text-[11px] text-slate-500">Travel agent</p>
                            <p id="sum-agent" class="text-sm font-bold text-slate-800 tabular-nums">₱0.00</p>
                        </div>

                        <div class="rounded-xl bg-slate-900 px-3.5 py-2.5">
                            <p class="text-[11px] text-slate-400">Total PHP</p>
                            <p id="grand-total" class="text-sm font-bold text-white tabular-nums">₱0.00</p>
                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                REMARKS
            ====================================================== --}}

            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-5">

                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M5 4h14a1 1 0 011 1v11a1 1 0 01-1 1h-6l-4 4v-4H5a1 1 0 01-1-1V5a1 1 0 011-1z" />
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Remarks</h2>
                </div>

                <div class="p-5">
                    <label for="remarks" class="sr-only">Remarks</label>

                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="2"
                        placeholder="Anything the approver should know (optional)"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                    >{{ old('remarks') }}</textarea>
                </div>

            </section>


            {{-- =====================================================
                ACTION BAR
            ====================================================== --}}

            <div class="sticky bottom-3 z-10">

                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-white/95 backdrop-blur border border-slate-200 shadow-lg">

                    <div>
                        <p class="text-[11px] text-slate-500">Total budget</p>
                        <p id="sticky-total" class="text-base font-bold text-slate-900 tabular-nums">₱0.00</p>
                    </div>

                    <div class="flex items-center gap-3">

                        <a
                            href="{{ route('budget_requests.index') }}"
                            class="text-sm text-slate-500 hover:text-slate-700 transition-colors px-2 py-1"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="submit-btn"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2"
                        >
                            Submit request
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- =============================================================
        TEMPLATE FOR NEW LINES
    ============================================================= --}}

    <template id="row-template">

        <div class="item-row rounded-xl border border-slate-200 bg-slate-50/40 p-3.5">

            <div class="grid grid-cols-12 gap-x-3 gap-y-3 items-end">

                <div class="col-span-12 sm:col-span-4">
                    <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 mb-1">
                        <span class="row-number inline-flex items-center justify-center w-5 h-5 rounded-md bg-slate-200 text-slate-600 text-[10px] font-bold">1</span>
                        Expense category
                    </label>
                    <select
                        name="items[__I__][expense_category]"
                        class="w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                        required
                    >
                        <option value="">Select expense category</option>
                        <option value="Airfare">Airfare</option>
                        <option value="Airport Tax">Airport Tax</option>
                        <option value="Hotel And Accommodation">Hotel And Accommodation</option>
                        <option value="Per Diem">Per Diem</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Communication And Petty Cash">Communication And Petty Cash</option>
                        <option value="Travel Insurance">Travel Insurance</option>
                        <option value="Visa And Permit">Visa And Permit</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="col-span-12 sm:col-span-5">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">
                        Particulars <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="text"
                        name="items[__I__][particular]"
                        required
                        maxlength="255"
                        aria-label="Particulars for expense"
                        placeholder="Describe this expense"
                        class="particular-input w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                    >
                </div>

                <div class="col-span-9 sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Currency</label>
                    <select
                        name="items[__I__][currency]"
                        class="currency-select w-full px-2.5 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                    >
                        <option value="PHP">PHP ₱</option>
                        <option value="USD">USD $</option>
                        <option value="VND">VND ₫</option>
                    </select>
                </div>

                <div class="col-span-3 sm:col-span-1 flex justify-end">
                    <button
                        type="button"
                        class="remove-row flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors focus:outline-none focus:ring-2 focus:ring-red-100"
                        aria-label="Remove line"
                    >
                        <svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12a2 2 0 01-2 1.8H7.9a2 2 0 01-2-1.8L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-3 pt-3 border-t border-dashed border-slate-200">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Cash</label>
                    <div class="relative">
                        <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">₱</span>
                        <input type="text" inputmode="decimal" name="items[__I__][budget_cash]" placeholder="0.00" autocomplete="off"
                            class="amount w-full py-2 pl-7 pr-2.5 text-sm text-right tabular-nums border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Credit card</label>
                    <div class="relative">
                        <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">₱</span>
                        <input type="text" inputmode="decimal" name="items[__I__][budget_credit_card]" placeholder="0.00" autocomplete="off"
                            class="amount w-full py-2 pl-7 pr-2.5 text-sm text-right tabular-nums border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Travel agent</label>
                    <div class="relative">
                        <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">₱</span>
                        <input type="text" inputmode="decimal" name="items[__I__][budget_travel_agent]" placeholder="0.00" autocomplete="off"
                            class="amount w-full py-2 pl-7 pr-2.5 text-sm text-right tabular-nums border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>

                <div>
                    <span class="block text-[11px] font-semibold text-slate-500 mb-1">PHP equivalent</span>
                    <input type="hidden" name="items[__I__][php_equivalent]" class="php-equivalent-input" value="0">
                    <div class="px-2.5 py-2 rounded-lg bg-blue-50 border border-blue-100 text-right">
                        <span class="php-equivalent font-bold text-blue-900 text-sm tabular-nums">₱0.00</span>
                    </div>
                </div>

            </div>

        </div>

    </template>


    {{-- =============================================================
        JAVASCRIPT
    ============================================================= --}}

    <script>

        /* =========================================================
           COUNTRY → CITY
        ========================================================== */

        const cities = {
            "Philippines": ["Manila", "Quezon City", "Makati", "Pasig", "Taguig", "Parañaque", "Pasay", "Las Piñas", "Muntinlupa", "Caloocan", "Cavite", "Bacoor", "Imus", "Dasmariñas", "Cebu City", "Davao City", "Iloilo City", "Other"],
            "United States": ["New York", "Los Angeles", "Chicago", "Houston", "San Francisco", "Seattle", "Other"],
            "Canada": ["Toronto", "Vancouver", "Montreal", "Calgary", "Ottawa", "Other"],
            "United Kingdom": ["London", "Manchester", "Birmingham", "Liverpool", "Edinburgh", "Other"],
            "Japan": ["Tokyo", "Osaka", "Kyoto", "Yokohama", "Nagoya", "Other"],
            "China": ["Shanghai", "Beijing", "Guangzhou", "Shenzhen", "Hong Kong", "Other"],
            "South Korea": ["Seoul", "Busan", "Incheon", "Daegu", "Daejeon", "Other"],
            "Singapore": ["Singapore", "Other"],
            "Australia": ["Sydney", "Melbourne", "Brisbane", "Perth", "Adelaide", "Other"]
        };

        const countrySelect = document.getElementById('country');
        const placeSelect = document.getElementById('place');
        const oldPlace = @json(old('place'));

        function updateCities() {

            const country = countrySelect.value;

            placeSelect.innerHTML = '<option value="">Select city / place</option>';

            if (cities[country]) {

                cities[country].forEach(city => {

                    const option = document.createElement('option');

                    option.value = city;
                    option.textContent = city;

                    if (city === oldPlace) {
                        option.selected = true;
                    }

                    placeSelect.appendChild(option);

                });

            }

            placeSelect.disabled = !cities[country];

        }

        countrySelect.addEventListener('change', updateCities);

        updateCities();


        /* =========================================================
           CURRENCY + FX
        ========================================================== */

        const currencySymbols = { PHP: '₱', USD: '$', VND: '₫' };

        let fxRates = { PHP: 1, USD: null, VND: null };

        let fxReady = false;

        function setFxState(state, message) {

            const status = document.getElementById('fx-status');
            const dot = document.getElementById('fx-dot');

            status.textContent = message;

            dot.className = 'w-2 h-2 rounded-full ' + ({
                loading: 'bg-slate-300 animate-pulse',
                ok: 'bg-emerald-500',
                error: 'bg-red-500'
            }[state]);

        }

        async function loadExchangeRates() {

            const usdRate = document.getElementById('usd-rate');
            const vndRate = document.getElementById('vnd-rate');
            const updated = document.getElementById('fx-updated');

            setFxState('loading', 'Updating exchange rates...');

            try {

                const [usdResponse, vndResponse] = await Promise.all([
                    fetch('https://api.frankfurter.dev/v2/rate/usd/php'),
                    fetch('https://api.frankfurter.dev/v2/rate/vnd/php')
                ]);

                if (!usdResponse.ok || !vndResponse.ok) {
                    throw new Error('Unable to retrieve exchange rates.');
                }

                const usdData = await usdResponse.json();
                const vndData = await vndResponse.json();

                fxRates.USD = Number(usdData.rate);
                fxRates.VND = Number(vndData.rate);

                fxReady = true;

                usdRate.textContent = '₱' + fxRates.USD.toLocaleString('en-US', {
                    minimumFractionDigits: 4,
                    maximumFractionDigits: 4
                });

                vndRate.textContent = '₱' + fxRates.VND.toLocaleString('en-US', {
                    minimumFractionDigits: 6,
                    maximumFractionDigits: 6
                });

                updated.textContent = 'As of ' + (usdData.date || vndData.date || 'latest');

                setFxState('ok', 'FX rates loaded');

                recalcAllRows();

            } catch (error) {

                console.error('FX Error:', error);

                fxReady = false;

                setFxState('error', 'Unable to load FX rates');

                usdRate.textContent = 'Unavailable';
                vndRate.textContent = 'Unavailable';

                recalcAllRows();

            }

        }

        function convertToPHP(amount, currency) {

            if (!amount) return 0;

            if (currency === 'PHP') return amount;

            if (!fxRates[currency]) return 0;

            return amount * fxRates[currency];

        }


        /* =========================================================
           NUMBER HELPERS
        ========================================================== */

        function formatNumber(number, decimals = 2) {

            return Number(number || 0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });

        }

        function getRawAmount(input) {

            if (!input) return 0;

            const value = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');

            return parseFloat(value) || 0;

        }

        function formatAmountInput(input) {

            let value = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');

            const parts = value.split('.');

            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            const formattedParts = value.split('.');

            if (formattedParts[0]) {

                formattedParts[0] = Number(formattedParts[0]).toLocaleString('en-US');

                value = formattedParts.length > 1
                    ? formattedParts[0] + '.' + formattedParts[1]
                    : formattedParts[0];

            }

            input.value = value;

        }


        /* =========================================================
           ROW CALCULATIONS
        ========================================================== */

        const itemsList = document.getElementById('items-list');

        function getRows() {
            return itemsList.querySelectorAll('.item-row');
        }

        function updateRowCurrency(row) {

            const currency = row.querySelector('.currency-select').value;
            const symbol = currencySymbols[currency] || '₱';

            row.querySelectorAll('.currency-symbol').forEach(el => {
                el.textContent = symbol;
            });

            recalcRow(row);

        }

        /* Returns { cash, card, agent, total } already converted to PHP. */

        function recalcRow(row) {

            const currency = row.querySelector('.currency-select').value;

            const cash = convertToPHP(getRawAmount(row.querySelector('[name*="[budget_cash]"]')), currency);
            const card = convertToPHP(getRawAmount(row.querySelector('[name*="[budget_credit_card]"]')), currency);
            const agent = convertToPHP(getRawAmount(row.querySelector('[name*="[budget_travel_agent]"]')), currency);

            const total = cash + card + agent;

            const display = row.querySelector('.php-equivalent');
            const hidden = row.querySelector('.php-equivalent-input');

            if (display) display.textContent = '₱' + formatNumber(total);

            if (hidden) hidden.value = total.toFixed(2);

            return { cash, card, agent, total };

        }

        function needsFxButMissing() {

            if (fxReady) return false;

            return Array.from(getRows()).some(row => {

                const currency = row.querySelector('.currency-select').value;

                return currency !== 'PHP';

            });

        }

        function recalcAllRows() {

            const sums = { cash: 0, card: 0, agent: 0, total: 0 };

            getRows().forEach(row => {

                const r = recalcRow(row);

                sums.cash += r.cash;
                sums.card += r.card;
                sums.agent += r.agent;
                sums.total += r.total;

            });

            document.getElementById('sum-cash').textContent = '₱' + formatNumber(sums.cash);
            document.getElementById('sum-card').textContent = '₱' + formatNumber(sums.card);
            document.getElementById('sum-agent').textContent = '₱' + formatNumber(sums.agent);
            document.getElementById('grand-total').textContent = '₱' + formatNumber(sums.total);
            document.getElementById('sticky-total').textContent = '₱' + formatNumber(sums.total);

            document.getElementById('fx-warning').classList.toggle('hidden', !needsFxButMissing());

        }


        /* =========================================================
           ROW MANAGEMENT
        ========================================================== */

        let rowIndex = getRows().length;

        function refreshRows() {

            const rows = getRows();

            rows.forEach((row, n) => {

                const number = row.querySelector('.row-number');

                if (number) number.textContent = n + 1;

                const particular = row.querySelector('.particular-input');

                if (particular) {
                    particular.setAttribute('aria-label', 'Particulars for expense ' + (n + 1));
                }

                const btn = row.querySelector('.remove-row');

                btn.disabled = rows.length <= 1;
                btn.classList.toggle('opacity-30', rows.length <= 1);
                btn.classList.toggle('cursor-not-allowed', rows.length <= 1);

            });

        }

        document.getElementById('add-row').addEventListener('click', () => {

            const template = document.getElementById('row-template');

            const html = template.innerHTML.replace(/__I__/g, rowIndex++);

            itemsList.insertAdjacentHTML('beforeend', html);

            refreshRows();

            recalcAllRows();

            const newRow = itemsList.lastElementChild;

            const firstField = newRow.querySelector('select');

            if (firstField) firstField.focus();

        });

        itemsList.addEventListener('click', e => {

            const btn = e.target.closest('.remove-row');

            if (!btn || btn.disabled) return;

            if (getRows().length > 1) {

                btn.closest('.item-row').remove();

                refreshRows();

                recalcAllRows();

            }

        });

        itemsList.addEventListener('input', e => {

            if (!e.target.classList.contains('amount')) return;

            formatAmountInput(e.target);

            recalcAllRows();

        });

        itemsList.addEventListener('change', e => {

            if (!e.target.classList.contains('currency-select')) return;

            const row = e.target.closest('.item-row');

            if (row) updateRowCurrency(row);

            recalcAllRows();

        });

        document.getElementById('fx-retry').addEventListener('click', loadExchangeRates);


        /* =========================================================
           TRAVEL DATES
        ========================================================== */

        const dateFrom = document.getElementById('travel_date_from');
        const dateTo = document.getElementById('travel_date_to');
        const dateRangeError = document.getElementById('date-range-error');
        const tripLength = document.getElementById('trip-length');

        function updateTripLength() {

            if (dateFrom.value && dateTo.value && dateTo.value >= dateFrom.value) {

                const ms = new Date(dateTo.value) - new Date(dateFrom.value);
                const days = Math.round(ms / 86400000) + 1;
                const nights = days - 1;

                tripLength.textContent =
                    days + (days === 1 ? ' day' : ' days') +
                    ' · ' + nights + (nights === 1 ? ' night' : ' nights');

                tripLength.classList.remove('hidden');

            } else {

                tripLength.classList.add('hidden');

            }

        }

        function validateDateRange() {

            if (dateFrom.value) {
                dateTo.min = dateFrom.value;
            }

            if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {

                dateRangeError.classList.remove('hidden');

                dateTo.classList.add('border-red-300');

                dateTo.setCustomValidity("Return date can't be before the departure date.");

                updateTripLength();

                return false;

            }

            dateRangeError.classList.add('hidden');

            dateTo.classList.remove('border-red-300');

            dateTo.setCustomValidity('');

            updateTripLength();

            return true;

        }

        dateFrom.addEventListener('change', validateDateRange);
        dateTo.addEventListener('change', validateDateRange);


        /* =========================================================
           SUBMIT
        ========================================================== */

        const form = document.getElementById('budget-form');

        form.addEventListener('submit', function (e) {

            if (!validateDateRange()) {

                e.preventDefault();

                dateTo.scrollIntoView({ behavior: 'smooth', block: 'center' });

                return;

            }

            if (!this.checkValidity()) {

                e.preventDefault();

                this.reportValidity();

                return;

            }

            if (needsFxButMissing()) {

                e.preventDefault();

                document.getElementById('fx-warning').scrollIntoView({ behavior: 'smooth', block: 'center' });

                return;

            }

            recalcAllRows();

            this.querySelectorAll('.amount').forEach(input => {
                input.value = input.value.replace(/,/g, '');
            });

            const btn = document.getElementById('submit-btn');

            btn.disabled = true;

            btn.textContent = 'Submitting...';

        });


        /* =========================================================
           INITIALIZE
        ========================================================== */

        getRows().forEach(updateRowCurrency);

        refreshRows();

        validateDateRange();

        recalcAllRows();

        loadExchangeRates();

        /* Refresh latest published FX rates every 60 minutes. */

        setInterval(loadExchangeRates, 60 * 60 * 1000);

    </script>

</x-mi_app>