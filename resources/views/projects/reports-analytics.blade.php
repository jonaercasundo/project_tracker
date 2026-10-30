<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <header class="border-b-2 border-slate-900 pb-4">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500">Metro · Mobilia Corporation</p>
                    <h1 class="mt-2 text-2xl font-black uppercase tracking-tight text-slate-950 sm:text-3xl">Billing Document Status</h1>
                    <p class="mt-1 text-sm text-slate-600">Billing document processing by project and lot.</p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated {{ $snapshotAt->format('M d, Y · g:i A') }}</p>
            </div>
        </header>

        <form method="GET" action="{{ route('projects.reports.analytics') }}" class="grid min-w-0 gap-4 border-2 border-slate-900 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_14rem] sm:items-end" aria-label="Billing report filters">
            <div>
                <label for="billing-project" class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.16em] text-slate-600">Project</label>
                <select id="billing-project" name="project_id" onchange="this.form.requestSubmit()" class="w-full min-w-0 whitespace-normal border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach ($projects as $project)
                        <option value="{{ $project->project_id }}" @selected((string) $selectedProjectId === (string) $project->project_id)>{{ $project->project_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="billing-lot" class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.16em] text-slate-600">Lot</label>
                <select id="billing-lot" name="lot" onchange="this.form.requestSubmit()" class="w-full border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach (['2', '9', '11'] as $lotNumber)
                        <option value="{{ $lotNumber }}" @selected($selectedLot === $lotNumber)>LOT {{ $lotNumber }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($summary['source_error'])
            <div role="alert" class="border border-amber-400 bg-amber-50 p-4 text-sm text-amber-950">
                <p class="font-black">Billing sheet data is unavailable</p>
                <p class="mt-1">{{ $summary['source_error'] }}</p>
            </div>
        @endif

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Billing document processing metrics">
            @php
                $summaryCards = [
                    ['label' => 'Total DRs', 'value' => $summary['total'], 'tone' => 'bg-white', 'detail' => 'Applicable to this project and lot'],
                    ['label' => 'Processed', 'value' => $summary['processed'], 'tone' => 'bg-slate-950 text-white', 'detail' => 'Received date is filled in'],
                    ['label' => 'Remaining', 'value' => $summary['remaining'], 'tone' => 'bg-white', 'detail' => 'Still without a received date'],
                    ['label' => 'Processed Today', 'value' => $summary['processed_today'], 'tone' => 'bg-white', 'detail' => 'Received today · '.config('app.timezone')],
                ];
            @endphp
            @foreach ($summaryCards as $card)
                <article class="flex min-h-36 flex-col justify-between gap-4 border-2 border-slate-900 p-4 {{ $card['tone'] }}">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] opacity-70">{{ $card['label'] }}</p>
                        <span class="h-2 w-2 rounded-full {{ $card['label'] === 'Processed' ? 'bg-emerald-400' : ($card['label'] === 'Processed Today' ? 'bg-amber-500' : 'bg-slate-300') }}" aria-hidden="true"></span>
                    </div>
                    <div>
                        <p class="text-4xl font-black leading-none tracking-tight tabular-nums">{{ number_format($card['value']) }}</p>
                        <p class="mt-2 text-[11px] font-medium opacity-65">{{ $card['detail'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        @if ($selectedProjectId === null)
            <p class="border border-slate-300 bg-white p-6 text-center text-sm text-slate-600">No projects are available for this report.</p>
        @endif
    </div>
</x-project_app-layout>
