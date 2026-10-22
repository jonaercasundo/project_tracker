<x-finance_app-layout>
@php
    $values = $data->getAttributes();
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
            <h1 class="mt-1 text-xl font-bold text-slate-900">Edit PPL Form #{{ $data->id }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ $data->project_title }} · Lot {{ $data->lot_number }}</p>
        </div>
        <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route('ppl_forms.show', ['id' => $data->id] + request()->query()) }}">View form</a>
    </div>

    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="note">
        <p class="font-semibold">Historical source record</p>
        <p class="mt-1">This page follows the workbook's <strong>1. FORM</strong> input columns. Blank saved fields remain blank. Saving does not import workbook values, change verified Operations links, or recalculate financial-plan versions.</p>
        <p class="mt-1">Rate fields use workbook decimal fractions (0.05 means 5%). Several legacy rate columns store only two decimal places; higher-precision workbook rates require a reviewed database change before they can be saved exactly.</p>
    </div>

    <form method="POST" action="{{ route('ppl_forms.update', ['id' => $data->id] + request()->query()) }}" class="space-y-5">
        @csrf
        @method('PUT')

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
            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-medium text-slate-600">Q · Factory payment terms</dt><dd>{{ $data->factory_payment_terms ?? 'Not recorded' }} <span class="text-slate-500">(workbook: 1 − downpayment)</span></dd></div>
                <div><dt class="font-medium text-slate-600">AS · Business cycle</dt><dd>{{ $data->business_cycle ?? 'Not recorded' }} <span class="text-slate-500">(workbook: (delivery + collection + 30) / 30)</span></dd></div>
                <div><dt class="font-medium text-slate-600">AZ · Bidder</dt><dd>{{ $data->bidder ?? 'Not recorded' }}</dd></div>
                <div><dt class="font-medium text-slate-600">BA · Authorized signatory</dt><dd>{{ $data->authorized_signatory ?? 'Not recorded' }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-slate-600">These saved values are shown for review and are not changed by this form. Forecast dates above are editable source values; this form does not recompute them from the workbook formulas.</p>
        </section>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('ppl_forms.show', ['id' => $data->id] + request()->query()) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:ring-2 focus:ring-blue-200">Save PPL Form</button>
        </div>
    </form>
</div>
</x-finance_app-layout>
