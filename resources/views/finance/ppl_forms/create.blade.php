<x-finance_app-layout>

<div class="max-w-7xl mx-auto p-6 space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">
            Add PPL Form
        </h1>
        <p class="text-sm text-slate-500">
            Project Profitability and Logistics Information
        </p>
    </div>

    <form method="POST" action="{{ route('ppl-forms.store') }}">
        @csrf

        <div class="space-y-6">

            {{-- PROJECT INFORMATION --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Project Information
                </h2>

                <div class="grid md:grid-cols-3 gap-4">

                    <div>
                        <label class="form-label">Project Code</label>
                        <input type="text" name="project_code" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Lot #</label>
                        <input type="text" name="lot_number" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Project ID No.</label>
                        <input type="text" name="project_id_no" class="form-control">
                    </div>

                    <div class="md:col-span-3">
                        <label class="form-label">Project Title</label>
                        <input type="text" name="project_title" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Bid Opening</label>
                        <input type="date" name="bid_opening" class="form-control">
                    </div>

                </div>
            </div>


            {{-- PROJECT TIMELINE --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Project Timeline
                </h2>

                <div class="grid md:grid-cols-3 gap-4">

                    <div>
                        <label>NOA (# of Months from Bid Opening)</label>
                        <input type="number" step="0.01" name="noa_months" class="form-control">
                    </div>

                    <div>
                        <label>NTP (# of Months from NOA)</label>
                        <input type="number" step="0.01" name="ntp_months" class="form-control">
                    </div>

                    <div>
                        <label>Delivery (# of Days After Production)</label>
                        <input type="number" name="delivery_days" class="form-control">
                    </div>

                    <div>
                        <label>Production Lead Time</label>
                        <input type="number" name="production_lead_time" class="form-control">
                    </div>

                    <div>
                        <label>Collection Period</label>
                        <input type="number" name="collection_period" class="form-control">
                    </div>

                    <div>
                        <label>Delivery Period</label>
                        <input type="number" name="delivery_period" class="form-control">
                    </div>

                </div>
            </div>


            {{-- FINANCIAL INFORMATION --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Financial Information
                </h2>

                <div class="grid md:grid-cols-4 gap-4">

                    <div>
                        <label>ABC</label>
                        <input type="number" step="0.01" name="abc" class="form-control">
                    </div>

                    <div>
                        <label>LCB (ABC)</label>
                        <input type="number" step="0.01" name="lcb" class="form-control">
                    </div>

                    <div>
                        <label>FOREX</label>
                        <input type="number" step="0.01" name="forex" class="form-control">
                    </div>

                    <div>
                        <label>Factory Downpayment</label>
                        <input type="number" step="0.01" name="factory_downpayment" class="form-control">
                    </div>

                    <div>
                        <label>Factory Payment Terms</label>
                        <input type="text" name="factory_payment_terms" class="form-control">
                    </div>

                    <div>
                        <label>Full Payment After Delivery</label>
                        <input type="number" step="0.01" name="full_payment_after_delivery" class="form-control">
                    </div>

                    <div>
                        <label>PF1 (Contract Amount)</label>
                        <input type="number" step="0.01" name="pf1" class="form-control">
                    </div>

                    <div>
                        <label>PF2 (Contract Amount)</label>
                        <input type="number" step="0.01" name="pf2" class="form-control">
                    </div>

                    <div>
                        <label>PF3 (Contract Amount)</label>
                        <input type="number" step="0.01" name="pf3" class="form-control">
                    </div>

                </div>
            </div>


            {{-- LOGISTICS --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Logistics & Warehouse
                </h2>

                <div class="grid md:grid-cols-3 gap-4">

                    <div>
                        <label>Warehouse Location</label>
                        <input type="text" name="warehouse_location" class="form-control">
                    </div>

                    <div>
                        <label>Shipping / Brokerage (# of Container)</label>
                        <input type="number" name="shipping_container" class="form-control">
                    </div>

                    <div>
                        <label>Rate per Container</label>
                        <input type="number" step="0.01" name="rate_per_container" class="form-control">
                    </div>

                    <div>
                        <label>Warehouse Area (SQM)</label>
                        <input type="number" step="0.01" name="warehouse_area" class="form-control">
                    </div>

                    <div>
                        <label>Warehouse Rental (Per SQM)</label>
                        <input type="number" step="0.01" name="warehouse_rental" class="form-control">
                    </div>

                    <div>
                        <label>Warehouse Rental (# of Months)</label>
                        <input type="number" name="warehouse_months" class="form-control">
                    </div>

                </div>
            </div>


            {{-- DATES --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Important Dates
                </h2>

                <div class="grid md:grid-cols-3 gap-4">

                    <div>
                        <label>NTP Date</label>
                        <input type="date" name="ntp_date" class="form-control">
                    </div>

                    <div>
                        <label>Factory Delivery</label>
                        <input type="date" name="factory_delivery" class="form-control">
                    </div>

                    <div>
                        <label>1st Delivery Date</label>
                        <input type="date" name="first_delivery_date" class="form-control">
                    </div>

                    <div>
                        <label>Collection Date</label>
                        <input type="date" name="collection_date" class="form-control">
                    </div>

                    <div>
                        <label>Year of Revenue Recognition</label>
                        <input type="number" name="year_revenue_recognition" class="form-control">
                    </div>

                </div>
            </div>


            {{-- SIGNATORIES --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-5 border-b pb-2">
                    Signatories
                </h2>

                <div class="grid md:grid-cols-2 gap-4">

                    <div>
                        <label>Bidder</label>
                        <input type="text" name="bidder" class="form-control">
                    </div>

                    <div>
                        <label>Authorized Signatory</label>
                        <input type="text" name="authorized_signatory" class="form-control">
                    </div>

                </div>
            </div>


            <div class="flex justify-end">
                <button class="btn btn-primary px-8">
                    Save PPL Form
                </button>
            </div>

        </div>

    </form>

</div>

</x-finance_app-layout>