<x-finance_app-layout>

<div class="max-w-5xl mx-auto px-4 py-6 space-y-5">

    {{-- HEADER --}}
    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Add PPL Form</h1>
            <p class="text-xs text-slate-500 mt-0.5">Project Profitability and Logistics</p>
        </div>
        <a href="{{ route('ppl_forms.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 flex items-center gap-1.5 transition">
            &#8592; Back to list
        </a>
    </div>

    <form method="POST" action="{{ route('ppl_forms.store') }}" class="space-y-5">
        @csrf

        {{-- TWO COLUMN DISPOSITION TO REDUCE VERTICAL SCROLL --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            
            {{-- LEFT COLUMN --}}
            <div class="space-y-5">
                {{-- PROJECT INFORMATION --}}
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-1 h-3.5 bg-blue-600 rounded-full"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Project Information</h2>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="col-span-1">
                            <label for="project_code" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Project Code</label>
                            <input type="text" id="project_code" name="project_code" value="{{ old('project_code') }}" placeholder="PRJ-2024-001"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('project_code') border-red-400 @enderror">
                            @error('project_code') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-1">
                            <label for="lot_number" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Lot #</label>
                            <input type="text" id="lot_number" name="lot_number" value="{{ old('lot_number') }}" placeholder="LOT-01"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('lot_number') border-red-400 @enderror">
                            @error('lot_number') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-1">
                            <label for="project_id_no" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Project ID No.</label>
                            <input type="text" id="project_id_no" name="project_id_no" value="{{ old('project_id_no') }}" placeholder="ID-00123"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('project_id_no') border-red-400 @enderror">
                            @error('project_id_no') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label for="project_title" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Project Title</label>
                            <input type="text" id="project_title" name="project_title" value="{{ old('project_title') }}" placeholder="Full project title"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('project_title') border-red-400 @enderror">
                            @error('project_title') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-1">
                            <label for="region" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Region</label>
                            <input type="text" id="region" name="region" value="{{ old('region') }}" placeholder="Region III"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('region') border-red-400 @enderror">
                            @error('region') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-1">
                            <label for="bid_opening" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Bid Opening</label>
                            <input type="date" id="bid_opening" name="bid_opening" value="{{ old('bid_opening') }}"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('bid_opening') border-red-400 @enderror">
                            @error('bid_opening') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- PROJECT TIMELINE --}}
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-1 h-3.5 bg-indigo-600 rounded-full"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Project Timeline</h2>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label for="noa_months" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">NOA <span class="normal-case font-normal text-slate-400">(mo from bid)</span></label>
                            <input type="number" step="0.01" id="noa_months" name="noa_months" value="{{ old('noa_months') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('noa_months') border-red-400 @enderror">
                            @error('noa_months') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ntp_months" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">NTP <span class="normal-case font-normal text-slate-400">(mo from NOA)</span></label>
                            <input type="number" step="0.01" id="ntp_months" name="ntp_months" value="{{ old('ntp_months') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('ntp_months') border-red-400 @enderror">
                            @error('ntp_months') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="delivery_days" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Delivery <span class="normal-case font-normal text-slate-400">(days post-prod)</span></label>
                            <input type="number" id="delivery_days" name="delivery_days" value="{{ old('delivery_days') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('delivery_days') border-red-400 @enderror">
                            @error('delivery_days') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="production_lead_time" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Prod. Lead Time</label>
                            <input type="number" id="production_lead_time" name="production_lead_time" value="{{ old('production_lead_time') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('production_lead_time') border-red-400 @enderror">
                            @error('production_lead_time') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="collection_period" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Collection Period</label>
                            <input type="number" id="collection_period" name="collection_period" value="{{ old('collection_period') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('collection_period') border-red-400 @enderror">
                            @error('collection_period') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="delivery_period" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Delivery Period</label>
                            <input type="number" id="delivery_period" name="delivery_period" value="{{ old('delivery_period') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition @error('delivery_period') border-red-400 @enderror">
                            @error('delivery_period') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-5">
                {{-- FINANCIAL INFORMATION --}}
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-1 h-3.5 bg-emerald-600 rounded-full"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Financial Information</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label for="abc" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">ABC</label>
                            <input type="number" step="0.01" id="abc" name="abc" value="{{ old('abc') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('abc') border-red-400 @enderror">
                            @error('abc') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="lcb" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">LCB (ABC)</label>
                            <input type="number" step="0.01" id="lcb" name="lcb" value="{{ old('lcb') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('lcb') border-red-400 @enderror">
                            @error('lcb') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="forex" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">FOREX</label>
                            <input type="number" step="0.01" id="forex" name="forex" value="{{ old('forex') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('forex') border-red-400 @enderror">
                            @error('forex') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="factory_downpayment" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Factory Downpayment</label>
                            <input type="number" step="0.01" id="factory_downpayment" name="factory_downpayment" value="{{ old('factory_downpayment') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('factory_downpayment') border-red-400 @enderror">
                            @error('factory_downpayment') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label for="factory_payment_terms" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Factory Payment Terms</label>
                            <input type="text" id="factory_payment_terms" name="factory_payment_terms" value="{{ old('factory_payment_terms') }}" placeholder="e.g. 30/70 split"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('factory_payment_terms') border-red-400 @enderror">
                            @error('factory_payment_terms') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label for="full_payment_after_delivery" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Full Payment After Delivery</label>
                            <input type="number" step="0.01" id="full_payment_after_delivery" name="full_payment_after_delivery" value="{{ old('full_payment_after_delivery') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('full_payment_after_delivery') border-red-400 @enderror">
                            @error('full_payment_after_delivery') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pf1" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">PF1 <span class="normal-case font-normal text-slate-300">Contract</span></label>
                            <input type="number" step="0.01" id="pf1" name="pf1" value="{{ old('pf1') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('pf1') border-red-400 @enderror">
                            @error('pf1') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pf2" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">PF2 <span class="normal-case font-normal text-slate-300">Contract</span></label>
                            <input type="number" step="0.01" id="pf2" name="pf2" value="{{ old('pf2') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('pf2') border-red-400 @enderror">
                            @error('pf2') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label for="pf3" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">PF3 <span class="normal-case font-normal text-slate-300">Contract</span></label>
                            <input type="number" step="0.01" id="pf3" name="pf3" value="{{ old('pf3') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition @error('pf3') border-red-400 @enderror">
                            @error('pf3') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- LOGISTICS --}}
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-1 h-3.5 bg-amber-500 rounded-full"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Logistics & Warehouse</h2>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="col-span-1">
                            <label for="warehouse_location" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Location</label>
                            <input type="text" id="warehouse_location" name="warehouse_location" value="{{ old('warehouse_location') }}" placeholder="City, Prov."
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('warehouse_location') border-red-400 @enderror">
                            @error('warehouse_location') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="shipping_container" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Containers</label>
                            <input type="number" id="shipping_container" name="shipping_container" value="{{ old('shipping_container') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('shipping_container') border-red-400 @enderror">
                            @error('shipping_container') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rate_per_container" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Rate/Container</label>
                            <input type="number" step="0.01" id="rate_per_container" name="rate_per_container" value="{{ old('rate_per_container') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('rate_per_container') border-red-400 @enderror">
                            @error('rate_per_container') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="warehouse_area" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Area <span class="normal-case font-normal text-slate-300">(sqm)</span></label>
                            <input type="number" step="0.01" id="warehouse_area" name="warehouse_area" value="{{ old('warehouse_area') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('warehouse_area') border-red-400 @enderror">
                            @error('warehouse_area') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="warehouse_rental" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Rate/sqm</label>
                            <input type="number" step="0.01" id="warehouse_rental" name="warehouse_rental" value="{{ old('warehouse_rental') }}" placeholder="0.00"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('warehouse_rental') border-red-400 @enderror">
                            @error('warehouse_rental') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="warehouse_months" class="Duration block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Duration <span class="normal-case font-normal text-slate-300">(mo)</span></label>
                            <input type="number" id="warehouse_months" name="warehouse_months" value="{{ old('warehouse_months') }}" placeholder="0"
                                class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition @error('warehouse_months') border-red-400 @enderror">
                            @error('warehouse_months') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- LOWER FULL-WIDTH ROW --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- IMPORTANT DATES --}}
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm lg:col-span-2">
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                    <span class="w-1 h-3.5 bg-rose-500 rounded-full"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Important Dates</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                    <div>
                        <label for="ntp_date" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">NTP Date</label>
                        <input type="date" id="ntp_date" name="ntp_date" value="{{ old('ntp_date') }}"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2 text-xs text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 transition @error('ntp_date') border-red-400 @enderror">
                        @error('ntp_date') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="factory_delivery" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Factory Del.</label>
                        <input type="date" id="factory_delivery" name="factory_delivery" value="{{ old('factory_delivery') }}"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2 text-xs text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 transition @error('factory_delivery') border-red-400 @enderror">
                        @error('factory_delivery') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="first_delivery_date" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">1st Del. Date</label>
                        <input type="date" id="first_delivery_date" name="first_delivery_date" value="{{ old('first_delivery_date') }}"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2 text-xs text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 transition @error('first_delivery_date') border-red-400 @enderror">
                        @error('first_delivery_date') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="collection_date" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Collection Date</label>
                        <input type="date" id="collection_date" name="collection_date" value="{{ old('collection_date') }}"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2 text-xs text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 transition @error('collection_date') border-red-400 @enderror">
                        @error('collection_date') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="year_revenue_recognition" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Rev. Rec. Year</label>
                        <input type="number" id="year_revenue_recognition" name="year_revenue_recognition" value="{{ old('year_revenue_recognition', '2026') }}" placeholder="2026"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 transition @error('year_revenue_recognition') border-red-400 @enderror">
                        @error('year_revenue_recognition') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- SIGNATORIES --}}
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm lg:col-span-1">
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                    <span class="w-1 h-3.5 bg-purple-600 rounded-full"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Signatories</h2>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label for="bidder" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Bidder</label>
                        <input type="text" id="bidder" name="bidder" value="{{ old('bidder') }}" placeholder="Full name"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition @error('bidder') border-red-400 @enderror">
                        @error('bidder') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="authorized_signatory" class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Auth Signatory</label>
                        <input type="text" id="authorized_signatory" name="authorized_signatory" value="{{ old('authorized_signatory') }}" placeholder="Full name"
                            class="w-full h-9 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition @error('authorized_signatory') border-red-400 @enderror">
                        @error('authorized_signatory') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ACTIONS FOOTER --}}
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
            <a href="{{ route('ppl_forms.index') }}"
                class="inline-flex items-center h-9 px-4 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center h-9 px-5 rounded-lg bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-200 transition shadow-sm shadow-blue-100">
                Save PPL Form
            </button>
        </div>

    </form>
</div>

</x-finance_app-layout>