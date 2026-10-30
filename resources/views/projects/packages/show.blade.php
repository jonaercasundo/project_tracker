<x-project-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Project package</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $package->code_label }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $project->project_name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'packages']) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Back to Packages</a>
                <a href="{{ route('projects.packages.edit', [$project, $package->package_id]) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Edit quantities</a>
            </div>
        </div>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-bold text-slate-900">Package details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Package</dt><dd class="mt-1 text-sm font-semibold">Package {{ $package->package_num ?? $package->package_id }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Code</dt><dd class="mt-1 text-sm font-semibold">{{ $package->code_label }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Lot</dt><dd class="mt-1 text-sm">{{ $package->lot_label ?: 'No Lot assigned' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Key Stage</dt><dd class="mt-1 text-sm">{{ $package->keystage_label }}</dd></div>
                <div class="sm:col-span-2 lg:col-span-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-wrap text-sm">{{ $package->description_label ?: 'Not provided' }}</dd></div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Package items</h2><p class="mt-1 text-xs text-slate-500">Each quantity is stored on this package’s item assignment.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3 text-right">Quantity per package</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $item)
                            <tr><td class="px-5 py-4 font-semibold">{{ $item->item_name ?? 'Item '.$item->item_id }}</td><td class="px-5 py-4 text-slate-600">{{ $item->description ?? $item->item_description ?? '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $item->unit ?? '—' }}</td><td class="px-5 py-4 text-right font-semibold tabular-nums">{{ $item->qty ?? 0 }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No items are assigned to this package.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-project-app-layout>
