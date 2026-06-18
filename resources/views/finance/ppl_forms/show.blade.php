<x-finance_app-layout>

{{-- HEADER --}}
<x-slot name="header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-lg font-bold">{{ $data->project_title }}</h1>
            <p class="text-xs text-slate-400">
                {{ $data->project_code }} • LOT {{ $data->lot_number }} • ID {{ $data->project_id_no }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="/ppl-forms/{{ $data->id }}/edit"
               class="px-4 py-2 text-xs bg-amber-500 text-white rounded-lg">
                Edit
            </a>

            <a href="/ppl-forms"
               class="px-4 py-2 text-xs bg-slate-900 text-white rounded-lg">
                Back
            </a>
        </div>
    </div>
</x-slot>

<div class="space-y-6">

    {{-- ================= TOP SUMMARY CARD ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="p-4 bg-white border rounded-xl">
            <div class="text-xs text-slate-400">ABC</div>
            <div class="text-xl font-bold">₱{{ number_format($data->abc, 2) }}</div>
        </div>

        <div class="p-4 bg-white border rounded-xl">
            <div class="text-xs text-slate-400">LCB</div>
            <div class="text-xl font-bold text-emerald-600">
                %{{ number_format($data->lcb_abc, 2) }}
            </div>
        </div>

        <div class="p-4 bg-white border rounded-xl">
            <div class="text-xs text-slate-400">FOREX</div>
            <div class="text-xl font-bold">{{ $data->forex ?? '-' }}</div>
        </div>

    </div>

    {{-- ================= FULL GRID ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-[12px]">

        {{-- TIMELINE --}}
        <div class="p-4 bg-white border rounded-xl space-y-2">
            <div class="font-bold text-slate-400 uppercase text-[10px]">Timeline</div>

            <div>BID OPENING: {{ $data->bid_opening }}</div>
            <div>NOA: {{ $data->noa_months }} mo</div>
            <div>NTP: {{ $data->ntp_months }} mo</div>
            <div>DELIVERY: {{ $data->delivery_days }} days</div>
            <div>PRODUCTION: {{ $data->production_lead_time }}</div>
            <div>COLLECTION: {{ $data->collection_period }}</div>
            <div>DELIVERY PERIOD: {{ $data->delivery_period }}</div>
            <div>NTP DATE: {{ $data->ntp_date }}</div>
            <div>COLLECTION DATE: {{ $data->collection_date }}</div>
        </div>

        {{-- FINANCIAL --}}
        <div class="p-4 bg-white border rounded-xl space-y-2">
            <div class="font-bold text-slate-400 uppercase text-[10px]">Financial</div>

            <div>ABC: ₱{{ number_format($data->abc, 2) }}</div>
            <div>DOWNPAYMENT: ₱{{ number_format($data->factory_downpayment, 2) }}</div>
            <div>PF1: {{ $data->pf1_contract_amt }}%</div>
            <div>PF2: {{ $data->pf2_contract_amt }}%</div>
            <div>PF3: {{ $data->pf3_contract_amt }}%</div>
            <div>LL COM: {{ $data->ll_com_factory_cost }}%</div>
            <div>INTEREST + DST: {{ $data->interest_rate_dst }}%</div>
            <div>FULL PAYMENT: {{ $data->full_payment_after_delivery }}%</div>
        </div>

        {{-- LOGISTICS --}}
        <div class="p-4 bg-white border rounded-xl space-y-2">
            <div class="font-bold text-slate-400 uppercase text-[10px]">Logistics</div>

            <div>WAREHOUSE: {{ $data->warehouse_location }}</div>
            <div>AREA: {{ $data->warehouse_area_sqm }}</div>
            <div>CONTAINER RATE: {{ $data->rate_per_container }}</div>
            <div>RENT/SQM: {{ $data->warehouse_rental_per_sqm }}</div>
            <div>RENT MONTHS: {{ $data->warehouse_rental_no_of_months }}</div>
            <div>OTHER EXPENSES: ₱{{ number_format($data->other_expenses_contract_amt, 2) }}</div>
        </div>

    </div>

    {{-- ================= OPERATIONS / TAX ================= --}}
    <div class="p-4 bg-white border rounded-xl grid grid-cols-1 md:grid-cols-2 gap-4 text-[12px]">

        <div>
            <div class="font-bold text-slate-400 uppercase text-[10px] mb-2">Operations</div>
            <div>MANPOWER: {{ $data->manpower_abc }}</div>
            <div>ASSEMBLY: {{ $data->assembly_abc }}</div>
            <div>R&D: {{ $data->rnd_abc }}</div>
            <div>FACILITATION: {{ $data->facilitation_abc }}</div>
            <div>OPEX: {{ $data->opex_net_sales }}</div>
        </div>

        <div>
            <div class="font-bold text-slate-400 uppercase text-[10px] mb-2">Tax & Finance</div>
            <div>INCOME TAX: {{ $data->income_tax_net_sales }}</div>
            <div>INCENTIVE: {{ $data->incentive_net_profit }}</div>
            <div>IMPORT VAT: {{ $data->importation_vat_cogs }}</div>
            <div>OUTPUT VAT: {{ $data->output_vat }}</div>
            <div>BUSINESS CYCLE: {{ $data->business_cycle }}</div>
        </div>

    </div>

</div>

</x-finance_app-layout>