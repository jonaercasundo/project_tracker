<div>
    <!-- Nothing in life is to be feared, it is only to be understood. Now is the time to understand more, so that we may fear less. - Marie Curie -->
</div>
<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Project item</p>
                <h1 class="break-words text-2xl font-extrabold tracking-tight text-slate-900">{{ $item->item_name ?: 'Unnamed item' }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $project->project_name }} · Item ID {{ $item->item_id }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'items']) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Back to Items</a>
                <a href="{{ route('projects.items.edit', [$project, $item->item_id]) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700">Edit item</a>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <h2 class="text-sm font-black text-slate-900">Saved item information</h2>
                <p class="mt-1 text-xs text-slate-500">Read-only details for this project item.</p>
            </div>
            <dl class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
                @foreach ($details as $detail)
                    <div class="min-w-0 bg-white p-5">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $detail['label'] }}</dt>
                        <dd class="mt-2 break-words whitespace-pre-wrap text-sm font-medium text-slate-900">{{ $detail['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-project-app-layout>
