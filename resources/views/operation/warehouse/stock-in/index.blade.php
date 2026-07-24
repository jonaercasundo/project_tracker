<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Warehouse Receipt
                    </div>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">📥 Stock In</h1>
                    <p class="mt-2 text-sm text-slate-500">Receive inventory into the warehouse using the approved delivery receipt details.</p>
                </div>

                <a href="{{ route('warehouse.dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Stock In Information</h2>
                <p class="mt-1 text-sm text-slate-500">Select the project, lot, and delivery receipt to begin receiving inventory.</p>
            </div>

            <div class="grid gap-6 p-6 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Project <span class="text-red-500">*</span></label>
                    <select id="project_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Project --</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Lot <span class="text-red-500">*</span></label>
                    <select id="lot_id" disabled class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select Lot --</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Delivery Receipt (DR#) <span class="text-red-500">*</span></label>
                    <select id="delivery_id" disabled class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option value="">-- Select DR Number --</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="deliveryInfo" class="hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Delivery Information</h2>
            </div>
            <div class="grid gap-6 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Project</label>
                    <div id="info_project" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lot</label>
                    <div id="info_lot" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">School</label>
                    <div id="info_school" class="mt-1 font-semibold text-slate-800"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Delivery Date</label>
                    <div id="info_date" class="mt-1 font-semibold text-slate-800"></div>
                </div>
            </div>
        </div>

        <div id="itemsSection" class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Delivery Items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Item</th>
                            <th class="px-4 py-3 text-center font-semibold">DR Quantity</th>
                            <th class="px-4 py-3 text-center font-semibold">Received Quantity</th>
                            <th class="px-4 py-3 text-left font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTable"></tbody>
                </table>
            </div>
        </div>

        <div id="saveSection" class="hidden flex justify-end">
            <button id="btnSaveStockIn" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-emerald-700">
                📥 Complete Stock In
            </button>
        </div>
    </div>
</x-project_warehouse_app>