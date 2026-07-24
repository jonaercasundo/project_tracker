<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Warehouse Dashboard</h1>
                <p class="text-sm text-slate-500">Track stock movement, outbound activity, and delivered packages at a glance.</p>
            </div>
            <a href="{{ route('warehouse.stock-out') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Stock Out
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-700">Stock In</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-emerald-800">{{ $stockInCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-emerald-600">
                        ⬆️
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-700">Stock Out</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-amber-800">{{ $stockOutCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-amber-600">
                        ⬇️
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-700">Delivered</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-blue-800">{{ $deliveredCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-blue-600">
                        ✅
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Quick actions</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <a href="{{ route('warehouse.stock-in') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="font-semibold text-slate-800">Stock In</p>
                    <p class="mt-1 text-sm text-slate-500">Record warehouse receipts and incoming inventory.</p>
                </a>
                <a href="{{ route('warehouse.stock-out') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="font-semibold text-slate-800">Stock Out</p>
                    <p class="mt-1 text-sm text-slate-500">Process outbound stock using the scanner.</p>
                </a>
                <a href="{{ route('warehouse.history') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="font-semibold text-slate-800">Delivery History</p>
                    <p class="mt-1 text-sm text-slate-500">Review recent warehouse movements and delivery status.</p>
                </a>
            </div>
        </div>
    </div>
</x-project_warehouse_app>
