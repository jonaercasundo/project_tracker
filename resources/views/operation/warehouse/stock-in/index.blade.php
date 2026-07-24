<x-project_warehouse_app>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    📥 Stock In
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Receive warehouse inventory based on the approved Delivery Receipt (DR).
                </p>
            </div>

            <a href="{{ route('warehouse.dashboard') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">
                ← Back
            </a>
        </div>
    </div>

    {{-- Stock In Form --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm">

        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">
                Stock In Information
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                Select the Project, Lot, and Delivery Receipt to begin receiving inventory.
            </p>
        </div>

        <div class="p-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Project --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Project <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="project_id"
                        class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">

                        <option value="">
                            -- Select Project --
                        </option>

                        {{-- Projects --}}
                    </select>
                </div>

                {{-- Lot --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Lot <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="lot_id"
                        disabled
                        class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">

                        <option value="">
                            -- Select Lot --
                        </option>

                    </select>
                </div>

                {{-- DR --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Delivery Receipt (DR#) <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="delivery_id"
                        disabled
                        class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">

                        <option value="">
                            -- Select DR Number --
                        </option>

                    </select>
                </div>

            </div>

        </div>

    </div>

    {{-- Delivery Information --}}
    <div id="deliveryInfo"
         class="hidden bg-white rounded-3xl border border-slate-200 shadow-sm">

        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">
                Delivery Information
            </h2>
        </div>

        <div class="p-6 grid grid-cols-2 lg:grid-cols-4 gap-6">

            <div>
                <label class="text-xs uppercase text-slate-500">Project</label>
                <div id="info_project" class="font-semibold mt-1"></div>
            </div>

            <div>
                <label class="text-xs uppercase text-slate-500">Lot</label>
                <div id="info_lot" class="font-semibold mt-1"></div>
            </div>

            <div>
                <label class="text-xs uppercase text-slate-500">School</label>
                <div id="info_school" class="font-semibold mt-1"></div>
            </div>

            <div>
                <label class="text-xs uppercase text-slate-500">Delivery Date</label>
                <div id="info_date" class="font-semibold mt-1"></div>
            </div>

        </div>

    </div>

    {{-- Items --}}
    <div id="itemsSection"
         class="hidden bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">
                Delivery Items
            </h2>
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-100">

                <tr>

                    <th class="px-4 py-3 text-left">Item</th>

                    <th class="px-4 py-3 text-center">DR Quantity</th>

                    <th class="px-4 py-3 text-center">Received Quantity</th>

                    <th class="px-4 py-3 text-left">Remarks</th>

                </tr>

                </thead>

                <tbody id="itemsTable">

                    {{-- Loaded using AJAX --}}

                </tbody>

            </table>

        </div>

    </div>

    {{-- Footer --}}
    <div id="saveSection"
         class="hidden flex justify-end">

        <button
            id="btnSaveStockIn"
            class="inline-flex items-center px-8 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold shadow">

            📥 Complete Stock In

        </button>

    </div>

</div>

</x-project_warehouse_app>