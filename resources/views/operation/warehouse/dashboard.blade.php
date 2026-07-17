<x-project_warehouse_app>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Inventory Scanner
                </h1>

                <p class="text-sm text-slate-500">
                    Scan packages or individual items for inventory movement.
                </p>
            </div>

            <a href="{{ route('inventory.index') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300">

                ← Back

            </a>

        </div>


        {{-- Current Mode --}}
        <div class="hidden rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
             id="currentMode">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Current Transaction
                    </p>

                    <h2 class="text-xl font-bold text-indigo-700">

                        <span id="transactionText">
                            -
                        </span>

                    </h2>

                </div>

                <div>

                    <p class="text-sm text-slate-500">
                        Scan Type
                    </p>

                    <h2 class="text-xl font-bold text-indigo-700">

                        <span id="scanTypeText">
                            -
                        </span>

                    </h2>

                </div>

            </div>

        </div>


        {{-- STEP 1 --}}
        <div id="step1"
             class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold mb-6">
                Step 1 — Select Transaction
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <button
                    id="btnInventoryIn"
                    class="rounded-2xl border-2 border-green-500 bg-green-50 hover:bg-green-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📥

                    </div>

                    <div class="text-2xl font-bold text-green-700">

                        Inventory In

                    </div>

                    <p class="text-slate-500 mt-2">

                        Add Quantity

                    </p>

                </button>


                <button
                    id="btnInventoryOut"
                    class="rounded-2xl border-2 border-red-500 bg-red-50 hover:bg-red-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📤

                    </div>

                    <div class="text-2xl font-bold text-red-700">

                        Inventory Out

                    </div>

                    <p class="text-slate-500 mt-2">

                        Minus Quantity

                    </p>

                </button>

            </div>

        </div>


        {{-- STEP 2 --}}
        <div id="step2"
             class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold mb-6">
                Step 2 — Select Scan Type
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <button
                    id="btnPackage"
                    class="rounded-2xl border-2 border-blue-500 bg-blue-50 hover:bg-blue-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📦

                    </div>

                    <div class="text-2xl font-bold text-blue-700">

                        Package

                    </div>

                    <p class="text-slate-500 mt-2">

                        Scan Package QR

                    </p>

                </button>


                <button
                    id="btnItem"
                    class="rounded-2xl border-2 border-amber-500 bg-amber-50 hover:bg-amber-100 transition p-8">

                    <div class="text-5xl mb-4">

                        📄

                    </div>

                    <div class="text-2xl font-bold text-amber-700">

                        Individual Item

                    </div>

                    <p class="text-slate-500 mt-2">

                        Scan Item QR

                    </p>

                </button>

            </div>

        </div>


        {{-- STEP 3 --}}
        <div id="step3"
             class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold mb-6">
                Step 3 — Scan QR Code
            </h2>

            <div class="rounded-xl border-2 border-dashed border-slate-300 h-96 flex items-center justify-center">

                <div class="text-center">

                    <div class="text-7xl">

                        📷

                    </div>

                    <p class="mt-4 text-slate-500">

                        QR Scanner will appear here

                    </p>

                </div>

            </div>

        </div>

    </div>

</x-project_warehouse_app>