<div x-data="{ open: false }">

    <!-- Button -->
    <button
        @click="open = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">
        Add Project
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">

        <div
            @click.away="open = false"
            class="bg-white w-full max-w-7xl max-h-[95vh] overflow-y-auto rounded-2xl shadow-xl">

            <!-- Header -->
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold">Add New Project</h2>

                <button
                    @click="open = false"
                    class="text-gray-500 hover:text-red-600">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('projects.store') }}">
                @csrf

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                    <!-- Basic Information -->
                    <div>
                        <label class="text-sm font-semibold">Project Code</label>
                        <input type="text" name="project_code"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Lot #</label>
                        <input type="text" name="lot_no"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Project Title</label>
                        <input type="text" name="project_title"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Project ID No.</label>
                        <input type="text" name="project_id_no"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Region</label>
                        <input type="text" name="region"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Bid Opening</label>
                        <input type="date" name="bid_opening"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <!-- Timeline -->
                    <div>
                        <label class="text-sm font-semibold">
                            NOA (# of Months from Bid Opening)
                        </label>
                        <input type="number" name="noa_months"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            NTP (# of Months from NOA)
                        </label>
                        <input type="number" name="ntp_months"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Delivery (# of Days After Production)
                        </label>
                        <input type="number" name="delivery_days"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Production Lead Time
                        </label>
                        <input type="number" name="production_lead_time"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Collection Period
                        </label>
                        <input type="number" name="collection_period"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Delivery Period
                        </label>
                        <input type="number" name="delivery_period"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <!-- Financial -->
                    <div>
                        <label class="text-sm font-semibold">ABC</label>
                        <input type="number" step="0.01" name="abc"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">LCB (ABC)</label>
                        <input type="number" step="0.01" name="lcb"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">FOREX</label>
                        <input type="number" step="0.01" name="forex"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Factory Downpayment
                        </label>
                        <input type="number" step="0.01"
                               name="factory_downpayment"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Factory Payment Terms
                        </label>
                        <input type="text"
                               name="factory_payment_terms"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Full Payment After Delivery
                        </label>
                        <input type="number"
                               step="0.01"
                               name="full_payment_after_delivery"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <!-- Contract Amount -->
                    <div>
                        <label class="text-sm font-semibold">PF1</label>
                        <input type="number" step="0.01"
                               name="pf1"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">PF2</label>
                        <input type="number" step="0.01"
                               name="pf2"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">PF3</label>
                        <input type="number" step="0.01"
                               name="pf3"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <!-- Warehouse -->
                    <div>
                        <label class="text-sm font-semibold">
                            Warehouse Location
                        </label>
                        <input type="text"
                               name="warehouse_location"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Warehouse Area (SQM)
                        </label>
                        <input type="number"
                               step="0.01"
                               name="warehouse_area"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Warehouse Rental per SQM
                        </label>
                        <input type="number"
                               step="0.01"
                               name="warehouse_rental_per_sqm"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Warehouse Rental (# of Months)
                        </label>
                        <input type="number"
                               name="warehouse_rental_months"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <!-- Dates -->
                    <div>
                        <label class="text-sm font-semibold">NTP Date</label>
                        <input type="date"
                               name="ntp_date"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Factory Delivery
                        </label>
                        <input type="date"
                               name="factory_delivery"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            1st Delivery Date
                        </label>
                        <input type="date"
                               name="first_delivery_date"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Collection Date
                        </label>
                        <input type="date"
                               name="collection_date"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Year of Revenue Recognition
                        </label>
                        <input type="number"
                               name="year_of_revenue_recognition"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Bidder</label>
                        <input type="text"
                               name="bidder"
                               class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Authorized Signatory
                        </label>
                        <input type="text"
                               name="authorized_signatory"
                               class="w-full border rounded-lg p-2">
                    </div>

                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="open = false"
                        class="px-4 py-2 border rounded-lg">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 text-white rounded-lg">
                        Save Project
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>