<div>
    <!-- No surplus words or unnecessary actions. - Marcus Aurelius -->
</div>
<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Project lot · ID {{ $lot->lot_id }}</p>
                <h1 class="break-words text-2xl font-extrabold tracking-tight text-slate-900">{{ $lot->lot_name ?: 'Unnamed lot' }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $project->project_name }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'lots']) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Back to Lots</a>
                <a href="{{ route('projects.lots.edit', [$project, $lot->lot_id]) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700">Edit lot</a>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <h2 class="text-sm font-black text-slate-900">Saved lot information</h2>
                <p class="mt-1 text-xs text-slate-500">Read-only details for this project lot.</p>
            </div>
            <dl class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
                <div class="min-w-0 bg-white p-5">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Lot number</dt>
                    <dd class="mt-2 break-words text-sm font-medium text-slate-900">{{ $lot->lot_name ?: 'Not set' }}</dd>
                </div>
                <div class="min-w-0 bg-white p-5">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Contract number</dt>
                    <dd class="mt-2 break-words text-sm font-medium text-slate-900">{{ $lot->contract_no ?: 'Not assigned' }}</dd>
                </div>
                <div class="min-w-0 bg-white p-5">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Contract price</dt>
                    <dd class="mt-2 text-sm font-medium tabular-nums text-slate-900">
                        @if ($lot->contract_price !== null)
                            &#8369;{{ number_format((float) $lot->contract_price, 2) }}
                        @else
                            Not set
                        @endif
                    </dd>
                </div>
                <div class="min-w-0 bg-white p-5 sm:col-span-2">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Assigned keystages</dt>
                    @if ($keystages->isNotEmpty())
                        <ul class="mt-3 space-y-2">
                            @foreach ($keystages as $keystage)
                                <li class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                    <span class="font-semibold text-slate-800">Keystage {{ $keystage->keystage_num ?? $keystage->keystage_id }}</span>
                                    <span class="text-slate-500">{{ $keystage->description ?: 'No description' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-slate-400">No keystages assigned.</p>
                    @endif
                </div>
            </dl>
        </section>
    </div>
</x-project-app-layout>
