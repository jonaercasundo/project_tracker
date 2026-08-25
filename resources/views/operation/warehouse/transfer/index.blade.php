<x-project_warehouse_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Warehouse Transfers</h1>
                <p class="text-sm text-slate-500">Manage stock transfers between warehouse locations.</p>
            </div>
            <a href="{{ route('warehouse.transfer.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                + New Transfer
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Transfers</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-800">{{ $totalCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-600">
                        🔄
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-700">Pending</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-amber-800">{{ $pendingCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-amber-600">
                        ⏳
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-700">Completed</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-emerald-800">{{ $completedCount ?? 0 }}</h2>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-emerald-600">
                        ✅
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Transfer List</h3>

                <form method="GET" action="{{ route('warehouse.transfer') }}" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search reference no..."
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Reference No.</th>
                            <th class="px-4 py-3 font-medium">From Warehouse</th>
                            <th class="px-4 py-3 font-medium">To Warehouse</th>
                            <th class="px-4 py-3 font-medium">Transfer Date</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transfers as $transfer)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $transfer->reference_no }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $transfer->fromWarehouse->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $transfer->toWarehouse->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($transfer->transfer_date)->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusStyles = [
                                            'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                        ];
                                        $style = $statusStyles[$transfer->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $style }}">
                                        {{ ucfirst($transfer->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- No show/edit/destroy routes defined yet — replace # with actual route() calls once added --}}
                                        <a href="#" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                            View
                                        </a>
                                        <a href="#" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                            Edit
                                        </a>
                                        <form action="#" method="POST" onsubmit="return confirm('Delete this transfer?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                    No transfers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($transfers ?? null, 'links'))
                <div class="mt-4">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>

    </div>
</x-project_warehouse_app>