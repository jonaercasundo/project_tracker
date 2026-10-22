<x-finance_app-layout>
@php
    $isEdit = isset($data);
    $values = $isEdit ? $data->getAttributes() : [];
    $sections = [
        'Project and lot — 1. FORM A–F' => [
            ['project_code', 'A', 'Project code', 'text'],
            ['lot_number', 'B', 'Lot number', 'text'],
            ['project_title', 'C', 'Project title', 'text'],
            ['project_id_no', 'D', 'Project ID / reference', 'text'],
            ['region', 'E', 'Region', 'text'],
            ['bid_opening', 'F', 'Bid opening', 'date'],
        ],
        'Schedule and collection — 1. FORM G–L' => [
            ['noa_months', 'G', 'NOA months from bid opening', 'integer'],
            ['ntp_months', 'H', 'NTP months from NOA', 'integer'],
            ['delivery_days_after_production', 'I', 'Delivery days after production', 'integer'],
            ['production_lead_time', 'J', 'Production lead time (days)', 'integer'],
            ['collection_period', 'K', 'Collection period (days)', 'integer'],
            ['delivery_period', 'L', 'Delivery period (days)', 'integer'],
        ],
        'Award and factory terms — 1. FORM M–R' => [
            ['abc', 'M', 'Lot ABC', 'money'],
            ['lcb_abc', 'N', 'LCB discount rate', 'rate'],
            ['forex', 'O', 'Forex rate', 'decimal'],
            ['factory_downpayment', 'P', 'Factory downpayment rate', 'rate'],
            ['full_payment_after_delivery', 'R', 'Full payment after delivery rate', 'rate'],
        ],
        'Financing and logistics — 1. FORM S–AD' => [
            ['pf1_contract_amt', 'S', 'PF1 / contract amount rate', 'rate'],
            ['pf2_contract_amt', 'T', 'PF2 / contract amount rate', 'rate'],
            ['pf3_contract_amt', 'U', 'PF3 / contract amount rate', 'rate'],
            ['ll_comm_factory_cost', 'V', 'LL commission / factory cost rate', 'rate'],
            ['interest_rate_dst', 'W', 'Interest and DST / net sales rate', 'rate'],
            ['warehouse_location', 'X', 'Warehouse location', 'text'],
            ['shipping_brokerage_containers', 'Y', 'Shipping / brokerage containers', 'integer'],
            ['rate_per_container', 'Z', 'Rate per container', 'money'],
            ['logistics_abc', 'AA', 'Logistics / ABC rate', 'rate'],
            ['warehouse_area_sqm', 'AB', 'Warehouse area (sqm)', 'decimal'],
            ['warehouse_rental_per_sqm', 'AC', 'Warehouse rent per sqm', 'money'],
            ['warehouse_rental_months', 'AD', 'Warehouse rental months', 'integer'],
        ],
        'Expenses, tax and cash flow — 1. FORM AE–AR' => [
            ['other_expenses', 'AE', 'Other expenses / contract amount rate', 'rate'],
            ['manpower_others', 'AF', 'Manpower / ABC rate', 'rate'],
            ['assembly_service_center', 'AG', 'Assembly or service center / ABC rate', 'rate'],
            ['rd_expenses', 'AH', 'R&D / ABC rate', 'rate'],
            ['facilitation', 'AI', 'Facilitation / ABC rate', 'rate'],
            ['allowance_tax_lawyer', 'AJ', 'Tax lawyer allowance / ABC rate', 'rate'],
            ['opex', 'AK', 'OPEX / net sales rate', 'rate'],
            ['income_tax', 'AL', 'Income tax / net sales rate', 'rate'],
            ['incentive', 'AM', 'Incentive / net profit rate', 'rate'],
            ['importation_vat', 'AN', 'Importation VAT / factory cost rate', 'rate'],
            ['output_vat', 'AO', 'Output VAT / contract amount rate', 'rate'],
            ['interest_rate_dst_per_annum', 'AP', 'Annual interest and DST rate', 'rate'],
            ['hold_out', 'AQ', 'Hold-out rate', 'rate'],
            ['retention', 'AR', 'Retention rate', 'rate'],
        ],
        'Forecast dates — 1. FORM AT–AX' => [
            ['ntp_date', 'AT', 'Forecast NTP date', 'date'],
            ['factory_delivery', 'AU', 'Forecast factory delivery', 'date'],
            ['first_delivery_date', 'AV', 'Forecast first delivery', 'date'],
            ['collection_date', 'AW', 'Forecast collection date', 'date'],
            ['year_of_revenue_recognition', 'AX', 'Revenue recognition year', 'integer'],
        ],
    ];
@endphp

<div class="mx-auto max-w-6xl space-y-5 px-4 py-6">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">MMC Finance · 1. FORM source</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $isEdit ? 'Edit PPL Form #'.$data->id : 'Add PPL Form' }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ $isEdit ? $data->project_title.' · Lot '.$data->lot_number : 'Enter Finance-controlled workbook inputs for one project and lot.' }}</p>
        </div>
        <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ $isEdit ? route('ppl_forms.show', ['id' => $data->id] + request()->query()) : route('ppl_forms.index', request()->query()) }}">{{ $isEdit ? 'View form' : 'Back to list' }}</a>
    </div>

    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="note">
        <p class="font-semibold">{{ $isEdit ? 'Historical source record' : 'Workbook-aligned source form' }}</p>
        <p class="mt-1">This page follows the workbook's <strong>1. FORM</strong> input columns. {{ $isEdit ? 'Blank saved fields remain blank. Saving does not import workbook values, change verified Operations links, or recalculate financial-plan versions.' : 'Select a verified Operations source when available. Project identifiers, lot ABC, rates and costs require Finance review; no workbook values are assumed.' }}</p>
        <p class="mt-1">Rate fields use workbook decimal fractions (0.05 means 5%). Several legacy rate columns store only two decimal places; higher-precision workbook rates require a reviewed database change before they can be saved exactly.</p>
    </div>

    <form method="POST" action="{{ $isEdit ? route('ppl_forms.update', ['id' => $data->id] + request()->query()) : route('ppl_forms.store') }}" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @else
            <input type="hidden" id="source_project_id" name="source_project_id" value="{{ old('source_project_id') }}">
            <input type="hidden" id="source_project_information_id" name="source_project_information_id" value="{{ old('source_project_information_id') }}">
            <input type="hidden" id="source_bidding_lot_id" name="source_bidding_lot_id" value="{{ old('source_bidding_lot_id') }}">
            <input type="hidden" id="source_legacy_lot_id" name="source_legacy_lot_id" value="{{ old('source_legacy_lot_id') }}">

            <section class="rounded-xl border border-blue-200 bg-blue-50 p-4" aria-labelledby="bidding-source-heading">
                <h2 id="bidding-source-heading" class="text-sm font-bold text-blue-900">Use existing Operations Bidding Form</h2>
                <p class="mt-1 text-xs text-blue-800">Choose a verified project and lot to prefill title, lot, recorded region, and bid opening. Project Code, Project ID No., ABC, and financial assumptions stay Finance-controlled.</p>
                <label for="bidding_source" class="mt-3 block text-xs font-semibold text-slate-700">Bidding project and lot</label>
                <select id="bidding_source" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-200">
                    <option value="">Select a project and lot</option>
                    @foreach ($biddingProjects as $biddingProject)
                        @foreach ($biddingProject->lots as $biddingLot)
                            <option value="bidding:{{ $biddingProject->id }}:{{ $biddingLot->id }}" @selected(old('source_project_information_id') == $biddingProject->id && old('source_bidding_lot_id') == $biddingLot->id)>{{ $biddingProject->project_name }} — {{ $biddingProject->project_id }} — Lot {{ $biddingLot->lot_no }}</option>
                        @endforeach
                    @endforeach
                    @foreach ($legacyProjectLots as $legacyProjectLot)
                        <option value="legacy:{{ $legacyProjectLot->bidding_id }}:{{ $legacyProjectLot->project_id }}:{{ $legacyProjectLot->lot_id }}" @selected(old('source_project_information_id') == $legacyProjectLot->bidding_id && old('source_legacy_lot_id') == $legacyProjectLot->lot_id)>{{ $legacyProjectLot->bidding_name }} — {{ $legacyProjectLot->bidding_identifier }} — Project List Lot {{ $legacyProjectLot->lot_name }}</option>
                    @endforeach
                </select>
                <p id="bidding_source_status" class="mt-2 text-xs text-slate-700" role="status" aria-live="polite">Source mapping is optional when no verified Operations record is available. Finance must confirm the project and lot before saving.</p>
            </section>
        @endif

        @foreach ($sections as $heading => $fields)
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="section-{{ $loop->index }}">
                <h2 id="section-{{ $loop->index }}" class="mb-4 border-b border-slate-100 pb-2 text-sm font-bold text-slate-800">{{ $heading }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($fields as [$name, $column, $label, $kind])
                        @php
                            $savedValue = $values[$name] ?? '';
                            if ($kind === 'money' && $savedValue !== '' && is_numeric($savedValue)) {
                                [$whole, $fraction] = array_pad(explode('.', (string) $savedValue, 2), 2, '');
                                $savedValue = $whole.'.'.str_pad($fraction, 2, '0');
                            }
                        @endphp
                        <div class="{{ $name === 'project_title' ? 'sm:col-span-2 lg:col-span-3' : '' }}">
                            <label for="{{ $name }}" class="block text-xs font-semibold text-slate-700">{{ $label }} <span class="font-normal text-slate-500">({{ $column }})</span></label>
                            <input id="{{ $name }}" name="{{ $name }}"
                                type="{{ $kind === 'text' ? 'text' : ($kind === 'date' ? 'date' : 'number') }}"
                                @if ($kind === 'integer') step="1" @elseif ($kind === 'rate' || $kind === 'decimal') step="any" @elseif ($kind === 'money') step="0.01" @endif
                                @if (in_array($name, ['project_code', 'lot_number', 'project_title', 'abc'], true)) required @endif
                                value="{{ old($name, $savedValue) }}"
                                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has($name) ? 'border-red-500' : 'border-slate-300' }}"
                                @if ($kind === 'rate') aria-describedby="rate-help" @endif>
                            @error($name)<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <p id="rate-help" class="text-xs text-slate-600">Enter rates as decimal fractions. Do not enter a percent sign.</p>

        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4" aria-labelledby="derived-heading">
            <h2 id="derived-heading" class="text-sm font-bold text-slate-800">Workbook-derived and supplementary fields</h2>
            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="font-medium text-slate-600">Q · Factory payment terms</dt><dd>{{ $isEdit ? ($data->factory_payment_terms ?? 'Not recorded') : 'Calculated in the workbook' }} <span class="text-slate-500">(1 − downpayment)</span></dd></div>
                <div><dt class="font-medium text-slate-600">AS · Business cycle</dt><dd>{{ $isEdit ? ($data->business_cycle ?? 'Not recorded') : 'Calculated in the workbook' }} <span class="text-slate-500">((delivery + collection + 30) / 30)</span></dd></div>
                @if ($isEdit)
                    <div><dt class="font-medium text-slate-600">AZ · Bidder</dt><dd>{{ $data->bidder ?? 'Not recorded' }}</dd></div>
                    <div><dt class="font-medium text-slate-600">BA · Authorized signatory</dt><dd>{{ $data->authorized_signatory ?? 'Not recorded' }}</dd></div>
                @endif
            </dl>
            @unless ($isEdit)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="bidder" class="block text-xs font-semibold text-slate-700">AZ · Bidder</label>
                        <input id="bidder" name="bidder" type="text" value="{{ old('bidder') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-200">
                        @error('bidder')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="authorized_signatory" class="block text-xs font-semibold text-slate-700">BA · Authorized signatory</label>
                        <input id="authorized_signatory" name="authorized_signatory" type="text" value="{{ old('authorized_signatory') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-200">
                        @error('authorized_signatory')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endunless
            <p class="mt-3 text-xs text-slate-600">Forecast dates above are source values. This form does not recompute workbook formulas or create financial-plan versions.</p>
        </section>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
            <a href="{{ $isEdit ? route('ppl_forms.show', ['id' => $data->id] + request()->query()) : route('ppl_forms.index', request()->query()) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:ring-2 focus:ring-blue-200">Save PPL Form</button>
        </div>
    </form>
</div>
@unless ($isEdit)
<script>
    (() => {
        const selector = document.getElementById('bidding_source');
        const status = document.getElementById('bidding_source_status');
        const biddingSourceUrl = @js(route('ppl_forms.source', ['bidding' => 987654321, 'lot' => 987654322]));
        const legacySourceUrl = @js(route('ppl_forms.legacy_source', ['bidding' => 987654321, 'project' => 987654323, 'lot' => 987654322]));
        const mappedFields = ['project_title', 'lot_number', 'region', 'bid_opening'];
        const sourceFields = ['source_project_id', 'source_project_information_id', 'source_bidding_lot_id', 'source_legacy_lot_id'];

        selector.addEventListener('change', async () => {
            sourceFields.forEach((field) => { document.getElementById(field).value = ''; });
            if (!selector.value) {
                status.textContent = 'No Operations source selected. Confirm the project and lot manually.';
                return;
            }

            const [sourceType, biddingId, projectOrLotId, legacyLotId] = selector.value.split(':');
            const url = sourceType === 'legacy'
                ? legacySourceUrl.replace('987654321', biddingId).replace('987654323', projectOrLotId).replace('987654322', legacyLotId)
                : biddingSourceUrl.replace('987654321', biddingId).replace('987654322', projectOrLotId);
            status.textContent = 'Loading verified Operations project and lot…';

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) {
                    throw new Error('The selected lot is unavailable for this project.');
                }
                const source = await response.json();
                document.getElementById('source_project_information_id').value = biddingId;
                document.getElementById('source_project_id').value = source.project_id ?? (sourceType === 'legacy' ? projectOrLotId : '');
                document.getElementById('source_bidding_lot_id').value = sourceType === 'bidding' ? projectOrLotId : '';
                document.getElementById('source_legacy_lot_id').value = sourceType === 'legacy' ? legacyLotId : '';
                mappedFields.forEach((field) => {
                    const input = document.getElementById(field);
                    if (input && source[field] !== null && source[field] !== undefined) {
                        input.value = source[field];
                    }
                });
                status.textContent = 'Prefilled from the selected Operations Bidding Form and lot. Review all fields before saving.';
            } catch (error) {
                status.textContent = error.message;
            }
        });
    })();
</script>
@endunless
</x-finance_app-layout>
