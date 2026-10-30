<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-100/60 sm:flex-row sm:items-center sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Project Module / Reports &amp; Analytics</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">Billing Document Status</h1>
                <p class="mt-1 text-sm font-medium text-slate-500">Billing document processing by project and lot.</p>
            </div>
            <p class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-600">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-600" aria-hidden="true"></span>
                Updated {{ $snapshotAt->format('M d, Y · g:i A') }}
            </p>
        </header>

        <form method="GET" action="{{ route('projects.reports.analytics') }}" @change="$event.target.name === 'project_id' && $el.requestSubmit()" class="grid min-w-0 gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 sm:items-end sm:p-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(12rem,0.65fr)]" aria-label="Billing report filters">
            <div class="min-w-0">
                <x-searchable-project-filter
                    :projects="$projects"
                    :selected="$selectedProjectId"
                    name="project_id"
                    id="billing-project-filter"
                    :truncate-selected="false"
                    wrapper-class="min-w-0 space-y-1 text-xs font-semibold text-slate-600"
                    label-class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500"
                />
            </div>
            <div>
                <label for="billing-lot" class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Lot</label>
                <select id="billing-lot" name="lot" onchange="this.form.requestSubmit()" class="w-full rounded-lg border-slate-300 bg-white text-sm font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach (['2', '9', '11'] as $lotNumber)
                        <option value="{{ $lotNumber }}" @selected($selectedLot === $lotNumber)>LOT {{ $lotNumber }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($summary['source_error'])
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <p class="font-black">Billing sheet data is unavailable</p>
                <p class="mt-1">{{ $summary['source_error'] }}</p>
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Billing document processing metrics">
            @php
                $summaryCards = [
                    ['label' => 'Total DRs', 'value' => $summary['total'], 'description' => 'Applicable to this project and lot', 'color' => 'blue', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
                    ['label' => 'Processed', 'value' => $summary['processed'], 'description' => 'Received date is filled in', 'color' => 'cyan', 'icon' => 'M9 12l2 2 4-4m5-3v6a8 8 0 1 1-3-6'],
                    ['label' => 'Remaining', 'value' => $summary['remaining'], 'description' => 'Still without a received date', 'color' => 'amber', 'icon' => 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['label' => 'Processed Today', 'value' => $summary['processed_today'], 'description' => 'Received today · '.config('app.timezone'), 'color' => 'blue', 'icon' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Zm3 11 2 2 4-4'],
                ];
                $toneClasses = [
                    'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                    'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                ];
            @endphp
            @foreach ($summaryCards as $card)
                <article class="flex min-h-[168px] flex-col justify-between rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-100/50 {{ $card['label'] === 'Processed' ? 'border-slate-800 bg-slate-950 text-white shadow-slate-900/10' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['label'] }}</p>
                            <p class="mt-3 font-mono text-3xl font-bold leading-none tracking-tight tabular-nums sm:text-4xl {{ $card['label'] === 'Processed' ? 'text-white' : 'text-slate-950' }}">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['label'] === 'Processed' ? 'bg-cyan-400/10 text-cyan-300 ring-cyan-300/20' : $toneClasses[$card['color']] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-xs leading-5 {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($selectedProjectId === null)
            <p class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-600">No projects are available for this report.</p>
        @endif
    </div>
</x-project_app-layout>
