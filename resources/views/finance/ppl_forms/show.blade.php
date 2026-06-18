<x-finance_app-layout>

{{-- HEADER --}}
<x-slot name="header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-base font-semibold text-slate-900">{{ $data->project_title }}</h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ $data->project_code }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-100">
                    Lot {{ $data->lot_number }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    ID {{ $data->project_id_no }}
                </span>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="/ppl-forms/{{ $data->id }}/edit"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="/ppl-forms"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-slate-800 hover:bg-slate-900 text-white rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>
</x-slot>

<div class="space-y-5">

    {{-- ================= TOP SUMMARY STRIP ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-1">ABC</div>
            <div class="text-xl font-semibold text-slate-900">₱{{ number_format($data->abc, 2) }}</div>
        </div>

        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-1">LCB / ABC</div>
            <div class="text-xl font-semibold text-emerald-600">{{ number_format($data->lcb_abc, 2) }}%</div>
        </div>

        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-1">Forex</div>
            <div class="text-xl font-semibold text-slate-900">{{ $data->forex ?? '—' }}</div>
        </div>

    </div>

    {{-- ================= MAIN GRID ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- TIMELINE --}}
        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-3">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Timeline</span>
            </div>
            <div class="space-y-0 divide-y divide-slate-50">
                @foreach ([
                    'Bid opening'     => $data->bid_opening,
                    'NOA'             => $data->noa_months . ' mo',
                    'NTP'             => $data->ntp_months . ' mo',
                    'Delivery'        => $data->delivery_days . ' days',
                    'Production'      => $data->production_lead_time,
                    'Collection'      => $data->collection_period,
                    'Delivery period' => $data->delivery_period,
                    'NTP date'        => $data->ntp_date,
                    'Collection date' => $data->collection_date,
                ] as $label => $value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[12px] text-slate-800 font-medium text-right">{{ $value ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- FINANCIAL --}}
        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-3">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Financial</span>
            </div>
            <div class="space-y-0 divide-y divide-slate-50">
                @foreach ([
                    'ABC'              => '₱' . number_format($data->abc, 2),
                    'Downpayment'      => '₱' . number_format($data->factory_downpayment, 2),
                    'PF1'              => $data->pf1_contract_amt . '%',
                    'PF2'              => $data->pf2_contract_amt . '%',
                    'PF3'              => $data->pf3_contract_amt . '%',
                    'LL com'           => $data->ll_com_factory_cost . '%',
                    'Interest + DST'   => $data->interest_rate_dst . '%',
                    'Full payment'     => $data->full_payment_after_delivery . '%',
                ] as $label => $value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[12px] text-slate-800 font-medium text-right">{{ $value ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- LOGISTICS --}}
        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-3">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Logistics</span>
            </div>
            <div class="space-y-0 divide-y divide-slate-50">
                @foreach ([
                    'Warehouse'       => $data->warehouse_location,
                    'Area'            => $data->warehouse_area_sqm,
                    'Container rate'  => $data->rate_per_container,
                    'Rent / sqm'      => $data->warehouse_rental_per_sqm,
                    'Rent months'     => $data->warehouse_rental_no_of_months,
                    'Other expenses'  => '₱' . number_format($data->other_expenses_contract_amt, 2),
                ] as $label => $value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[12px] text-slate-800 font-medium text-right">{{ $value ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ================= OPERATIONS / TAX ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- OPERATIONS --}}
        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-3">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Operations</span>
            </div>
            <div class="space-y-0 divide-y divide-slate-50">
                @foreach ([
                    'Manpower'     => $data->manpower_abc,
                    'Assembly'     => $data->assembly_abc,
                    'R&D'          => $data->rnd_abc,
                    'Facilitation' => $data->facilitation_abc,
                    'OPEX'         => $data->opex_net_sales,
                ] as $label => $value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[12px] text-slate-800 font-medium text-right">{{ $value ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- TAX & FINANCE --}}
        <div class="bg-white border border-slate-100 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-3">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Tax & Finance</span>
            </div>
            <div class="space-y-0 divide-y divide-slate-50">
                @foreach ([
                    'Income tax'     => $data->income_tax_net_sales,
                    'Incentive'      => $data->incentive_net_profit,
                    'Import VAT'     => $data->importation_vat_cogs,
                    'Output VAT'     => $data->output_vat,
                    'Business cycle' => $data->business_cycle,
                ] as $label => $value)
                <div class="flex justify-between items-center py-2">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[12px] text-slate-800 font-medium text-right">{{ $value ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

</x-finance_app-layout>