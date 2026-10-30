<x-project_app-layout>
    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operations / Returns</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Return Monitoring</h1>
            <p class="mt-2 text-sm text-slate-500">Track physical item returns and delivery documents returned to the office.</p>
        </header>

        @if(session('success'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if($errors->any())
            <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</p>
        @endif

        <form method="GET" action="{{ route('deliveries.return-monitoring') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
            <x-searchable-project-filter
                :projects="$projects"
                name="project_id"
                :selected="$selectedProject ?? ''"
                all-label="Choose a project"
                id="return-monitoring-project"
                :truncate-selected="true"
                required
                wrapper-class="w-full sm:max-w-xl space-y-1 text-xs font-semibold text-slate-600"
            />
            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Open returns</button>
            @if($selectedProject)
                <a href="{{ route('deliveries.return-monitoring', ['project_id' => $selectedProject]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear filters</a>
            @endif
        </form>

        @if($selectedProject)
            @include('operation.delivery.partials.monitoring-returns', [
                'records' => $records,
                'deliveries' => $deliveries,
                'items' => $items,
                'warehouses' => $warehouses,
                'project' => $selectedProject,
            ])
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">Choose a project to view or record returns.</div>
        @endif
    </div>
</x-project_app-layout>
