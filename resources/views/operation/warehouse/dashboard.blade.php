<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm shadow-slate-200/60 backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Warehouse Overview
                    </div>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Warehouse Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-500">Track incoming stock, outbound movement, pending projects, and deliveries in one view.</p>
                </div>
                <a href="{{ route('warehouse.stock-out') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Open Stock Out Scanner
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">


            <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm shadow-emerald-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Stock In</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-emerald-900">{{ $stockInCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm">
                        ⬆️
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm shadow-amber-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-amber-700">Stock Out</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-amber-900">{{ $stockOutCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm">
                        ⬇️
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm shadow-blue-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-blue-700">Deliver</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-blue-900">{{ $deliveredCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm">
                        ✅
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Quick actions</h3>
                    <p class="mt-1 text-sm text-slate-500">Jump to the most common warehouse tasks.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <a href="{{ route('warehouse.stock-in') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/60">
                    <p class="font-semibold text-slate-800">Stock In</p>
                    <p class="mt-1 text-sm text-slate-500">Record warehouse receipts and incoming inventory.</p>
                </a>
                <a href="{{ route('warehouse.stock-out') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/60">
                    <p class="font-semibold text-slate-800">Stock Out</p>
                    <p class="mt-1 text-sm text-slate-500">Process outbound stock using the scanner.</p>
                </a>
                <a href="{{ route('warehouse.history') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/60">
                    <p class="font-semibold text-slate-800">Delivery History</p>
                    <p class="mt-1 text-sm text-slate-500">Review recent warehouse movements and delivery status.</p>
                </a>
            </div>
        </div>
    </div>
</x-project_warehouse_app>
