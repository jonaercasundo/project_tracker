<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-100/60 sm:flex-row sm:items-center sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Project Module / Reports &amp; Analytics</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">MMC Masterlist Delivery Status</h1>
                <p class="mt-1 text-sm font-medium text-slate-500">
                    School delivery and SAP encoding progress from the MMC Masterlist for {{ $selectedProject?->project_name ?? 'the selected project' }}, across LOT 2, LOT 9, and LOT 11.
                </p>
            </div>
            <div class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right">
                <p class="inline-flex items-center gap-2 text-[11px] font-bold text-slate-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600" aria-hidden="true"></span>
                    <time id="billing-status-updated">Updated {{ $snapshotAt->format('M d, Y · g:i A') }}</time>
                </p>
                <p class="mt-0.5 text-[10px] font-medium text-slate-400">Based on the latest MMC Masterlist sync</p>
            </div>
        </header>

        <form method="GET" action="{{ route('projects.reports.analytics') }}" data-initial-project-id="{{ $selectedProjectId }}" class="grid min-w-0 grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_220px_220px] sm:items-end sm:p-5" aria-label="Project report filters">
            <div class="min-w-0">
                <x-searchable-project-filter
                    :projects="$projects"
                    :selected="$selectedProjectId"
                    name="project_id"
                    id="billing-project-filter"
                    :truncate-selected="false"
                    :submit-on-change="true"
                    wrapper-class="min-w-0 space-y-1 text-xs font-semibold text-slate-600"
                    label-class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500"
                />
            </div>
            @if ($drProgressReport)
                <div class="min-w-0 space-y-1 text-xs font-semibold text-slate-600">
                    <label for="delivery-start-date" class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Delivery Started</label>
                    <input id="delivery-start-date" name="delivery_start_date" type="date" value="{{ $drProgressReport['start_date'] }}" onchange="saveReportDate(this)" class="h-[42px] w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            @endif
            <div class="min-w-0 space-y-1 text-xs font-semibold text-slate-600">
                <label for="report-cutoff-date" class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Cutoff Date</label>
                <input id="report-cutoff-date" name="cutoff_date" type="date" value="{{ $cutoffDate->toDateString() }}" onchange="saveReportDate(this)" class="h-[42px] w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </form>

        @if ($mmcPlatformSync !== null)
            <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="mmc-platform-sync-title">
                <header class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="mmc-platform-sync-title" class="text-sm font-bold text-slate-900">MMC Platform Master List</h2>
                    @if ($mmcPlatformSync['synced_at'])
                        <p class="text-xs text-slate-600">Last synced {{ \Carbon\CarbonImmutable::parse($mmcPlatformSync['synced_at'])->format('M d, Y · g:i A') }}</p>
                    @endif
                </header>
                @if ($mmcPlatformSync['synced_at'])
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach (['records' => 'Master-list records', 'schools' => 'Schools', 'lots' => 'Lots', 'keystages' => 'Key stages', 'items' => 'Items', 'packages' => 'Packages'] as $key => $label)
                            <div>
                                <dt class="text-xs text-slate-600">{{ $label }}</dt>
                                <dd class="font-mono text-lg font-bold tabular-nums text-slate-950">{{ number_format($key === 'records' ? $mmcPlatformSync['record_count'] : ($mmcPlatformSync['entity_counts'][$key] ?? 0)) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-slate-600">MMC Platform data will appear here after the first successful sync.</p>
                @endif
            </section>
        @endif

        <div id="billing-status-refresh-error" role="alert" @if (! $summary['source_error']) hidden @endif class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-black">MMC Masterlist data is unavailable</p>
            <p id="billing-status-refresh-error-message" class="mt-1">{{ $summary['source_error'] }}</p>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="MMC Masterlist SAP encoding metrics">
            @php
                $summaryCards = [
                    ['key' => 'total', 'label' => 'Total Schools', 'value' => $summary['total'], 'description' => 'Unique schools with delivery records in the MMC Masterlist', 'color' => 'blue', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
                    ['key' => 'processed', 'label' => 'Processed', 'value' => $summary['processed'], 'description' => 'Date Received and Received By are filled for every delivery record', 'color' => 'cyan', 'icon' => 'M9 12l2 2 4-4m5-3v6a8 8 0 1 1-3-6'],
                    ['key' => 'remaining', 'label' => 'Remaining', 'value' => $summary['remaining'], 'description' => 'At least one delivery record is missing Date Received or Received By', 'color' => 'amber', 'icon' => 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['key' => 'processed_today', 'label' => 'Processed Today', 'value' => $summary['processed_today'], 'description' => 'Fully processed schools with Date Received today · '.config('app.timezone'), 'color' => 'blue', 'icon' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Zm3 11 2 2 4-4'],
                ];
                $toneClasses = [
                    'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                    'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                ];
            @endphp
            @foreach ($summaryCards as $card)
                <article class="flex min-h-[168px] flex-col justify-between rounded-2xl border border-slate-200/70 p-5 shadow-sm shadow-slate-100/50 {{ $card['label'] === 'Processed' ? 'border-slate-800 bg-slate-950 text-white shadow-slate-900/10' : 'bg-white' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['label'] }}</p>
                            <p class="mt-3 font-mono text-3xl font-bold leading-none tracking-tight tabular-nums sm:text-4xl {{ $card['label'] === 'Processed' ? 'text-white' : 'text-slate-950' }}"><span data-billing-metric="{{ $card['key'] }}">{{ $card['value'] === null ? '—' : number_format($card['value']) }}</span></p>
                        </div>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['label'] === 'Processed' ? 'bg-cyan-400/10 text-cyan-300 ring-cyan-300/20' : $toneClasses[$card['color']] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-xs leading-5 {{ $card['label'] === 'Processed' ? 'text-slate-300' : 'text-slate-500' }}">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($drProgressReport)
            @php
                $todayPosition = $drProgressReport['elapsed_percentage'];
                $todayLabelTransform = $todayPosition <= 10
                    ? 'translateX(0)'
                    : ($todayPosition >= 90 ? 'translateX(-100%)' : 'translateX(-50%)');
                $isPastCutoff = $drProgressReport['cutoff_date'] < $drProgressReport['today'];
                $isFutureCutoff = $drProgressReport['days_remaining'] > 0;
                $forecastTone = in_array($drProgressReport['pace_status'], ['On track', 'Target met'], true)
                    ? 'text-emerald-700'
                    : (in_array($drProgressReport['pace_status'], ['At this pace, cutoff will be missed', 'Cutoff is today'], true)
                        ? 'text-rose-600'
                        : 'text-slate-600');
                $progressStatus = match ($drProgressReport['pace_status']) {
                    'On track' => 'ON TRACK',
                    'Target met' => 'TARGET MET',
                    default => 'DELAYED',
                };
                $progressStatusTone = in_array($drProgressReport['pace_status'], ['On track', 'Target met'], true)
                    ? 'text-emerald-700'
                    : 'text-rose-600';
                $historicalForecastLabel = $drProgressReport['remaining'] === 0
                    ? 'COMPLETED'
                    : ($drProgressReport['historical_forecast_finish_date']
                        ? strtoupper(\Carbon\CarbonImmutable::parse($drProgressReport['historical_forecast_finish_date'])->format('M d, Y'))
                        : 'NOT ENOUGH HISTORY');
                $todayMarkerLabel = $isPastCutoff
                    ? 'TODAY - AFTER CUTOFF'
                    : 'TODAY - '.strtoupper(\Carbon\CarbonImmutable::parse($drProgressReport['today'])->format('M d'));
                $timelineStart = \Carbon\CarbonImmutable::parse($drProgressReport['start_date']);
                $timelineCutoff = \Carbon\CarbonImmutable::parse($drProgressReport['cutoff_date']);
                $timelineSpanDays = max(0, (int) $timelineStart->diffInDays($timelineCutoff));
                $timelineFirstThird = $timelineStart->addDays(intdiv($timelineSpanDays, 3));
                $timelineSecondThird = $timelineStart->addDays(intdiv($timelineSpanDays * 2, 3));
            @endphp
            <section class="space-y-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:space-y-2.5 sm:p-4" aria-labelledby="dr-progress-title">
                <header class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                    <h2 id="dr-progress-title" class="text-xs font-black uppercase tracking-[0.12em] text-slate-700 sm:text-sm">
                        {{ strtoupper(\Carbon\CarbonImmutable::parse($drProgressReport['cutoff_date'])->format('M d')) }} CUTOFF
                        @if ($isFutureCutoff)
                            <span class="px-1 text-slate-400">&middot;</span>{{ $drProgressReport['days_remaining'] }} DAYS LEFT
                        @elseif ($isPastCutoff)
                            <span class="px-1 text-slate-400">&middot;</span>CUTOFF PASSED
                        @else
                            <span class="px-1 text-slate-400">&middot;</span>CUTOFF TODAY
                        @endif
                    </h2>
                    <p class="text-xs font-black uppercase tracking-wide {{ $forecastTone }} sm:text-right">{{ $drProgressReport['pace_assessment'] }}</p>
                </header>

                <div aria-label="Cutoff timeline from report start to selected cutoff">
                    <div class="relative mb-1 h-5">
                        <span class="absolute top-0 whitespace-nowrap text-[9px] font-extrabold tracking-wide text-slate-700" style="left: {{ $todayPosition }}%; transform: {{ $todayLabelTransform }}">{{ $todayMarkerLabel }}</span>
                    </div>
                    <div class="relative h-3.5 overflow-visible border border-slate-400 bg-slate-200" role="img" aria-label="{{ number_format($drProgressReport['percentage'], 1) }} percent of delivery receipts processed; today is {{ \Carbon\CarbonImmutable::parse($drProgressReport['today'])->format('M d') }}">
                        <div class="absolute inset-y-0 left-0 bg-emerald-500" style="width: {{ $drProgressReport['percentage'] }}%"></div>
                        @if ($todayPosition > $drProgressReport['percentage'])
                            <div class="absolute inset-y-0 bg-rose-600" style="left: {{ $drProgressReport['percentage'] }}%; width: {{ $todayPosition - $drProgressReport['percentage'] }}%"></div>
                        @endif
                        <span class="absolute inset-y-[-3px] z-10 border-l-[3px] border-slate-800" style="left: {{ $todayPosition }}%" aria-hidden="true"></span>
                        <span class="absolute inset-y-[-2px] right-0 border-l-2 border-rose-500" aria-hidden="true"></span>
                    </div>
                    <div class="flex items-start justify-between gap-2 text-[9px] font-semibold leading-3 text-slate-500">
                        <span>{{ $timelineStart->format('M d') }}</span>
                        <span>{{ $timelineFirstThird->format('M d') }}</span>
                        <span>{{ $timelineSecondThird->format('M d') }}</span>
                        <span class="text-right">{{ $timelineCutoff->format('M d') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-200 pt-2 sm:grid-cols-4 sm:gap-4">
                    <div><p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Progress</p><p class="mt-0.5 font-mono text-base font-black tabular-nums text-slate-950">{{ number_format($drProgressReport['percentage'], 1) }}%</p></div>
                    <div><p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Needed per day</p><p class="mt-0.5 font-mono text-base font-black tabular-nums text-slate-950">{{ $drProgressReport['required_per_day'] === null ? 'N/A' : number_format($drProgressReport['required_per_day'], 0) }}</p></div>
                    <div><p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Progress Status</p><p class="mt-0.5 text-sm font-black uppercase {{ $progressStatusTone }}">{{ $progressStatus }}</p></div>
                    <div><p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Estimated Completion</p><p class="mt-0.5 text-sm font-black uppercase text-slate-950">{{ $historicalForecastLabel }}</p><p class="text-[9px] text-slate-500">At historical pace</p></div>
                </div>

                @if ($drProgressReport['data_limitation'])
                    <p role="status" class="text-[10px] leading-4 text-amber-800">{{ $drProgressReport['data_limitation'] }}</p>
                @endif

            </section>
        @endif
        <section class="grid grid-cols-1 gap-4 xl:grid-cols-[1.55fr_1fr]" aria-label="Billing and logistics return progress">
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="billing-lot-progress-title">
                <header>
                    <h2 id="billing-lot-progress-title" class="text-sm font-black uppercase tracking-[0.12em] text-slate-800">1st Billing · Share Processed</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Progress is calculated as processed ÷ (processed + remaining).</p>
                </header>
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ($billingLotSummaries as $lotSummary)
                        <div class="flex flex-col items-center text-center">
                            <div class="grid h-32 w-32 place-items-center rounded-full" style="background: conic-gradient(#151515 {{ $lotSummary['percentage'] }}%, #e9e4dc {{ $lotSummary['percentage'] }}% 100%)" role="img" aria-label="{{ $lotSummary['lot'] }}: {{ number_format($lotSummary['percentage'], 1) }} percent processed">
                                <div class="grid h-[6.4rem] w-[6.4rem] place-content-center rounded-full bg-white">
                                    <span class="font-mono text-2xl font-black tabular-nums text-slate-950">{{ number_format($lotSummary['percentage'], 1) }}%</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wide text-slate-500">of records</span>
                                </div>
                            </div>
                            <h3 class="mt-3 text-base font-black text-slate-950">{{ $lotSummary['lot'] }}</h3>
                            <p class="mt-1 font-mono text-xs font-bold tabular-nums text-slate-500">{{ number_format($lotSummary['processed']) }} processed · {{ number_format($lotSummary['remaining']) }} remaining</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="logistics-pouch-progress-title">
                <header>
                    <h2 id="logistics-pouch-progress-title" class="text-sm font-black uppercase tracking-[0.12em] text-slate-800">Pouches Returned by Logistics</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Unique schools assigned to each partner. Returned status uses Returned to Office At; compliance uses masterlist Status. Blank or unrecognized statuses are counted as For checking.</p>
                    <ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-semibold text-slate-600" aria-label="Pouch return status legend">
                        <li class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-emerald-600" aria-hidden="true"></span>Compliant</li>
                        <li class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-rose-600" aria-hidden="true"></span>Non-compliant</li>
                        <li class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-amber-500" aria-hidden="true"></span>For checking</li>
                        <li class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-stone-300" aria-hidden="true"></span>Still to return</li>
                    </ul>
                </header>
                @if ($logisticsPouchSummaries !== [])
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($logisticsPouchSummaries as $partnerSummary)
                            @php
                                $compliantPercentage = $partnerSummary['total'] > 0 ? ($partnerSummary['compliant'] / $partnerSummary['total']) * 100 : 0;
                                $nonCompliantPercentage = $partnerSummary['total'] > 0 ? ($partnerSummary['non_compliant'] / $partnerSummary['total']) * 100 : 0;
                                $checkingPercentage = $partnerSummary['total'] > 0 ? ($partnerSummary['checking'] / $partnerSummary['total']) * 100 : 0;
                            @endphp
                            <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
                                <div class="grid h-16 w-16 shrink-0 place-items-center rounded-full" style="background: conic-gradient(#059669 0 {{ $compliantPercentage }}%, #e11d48 {{ $compliantPercentage }}% {{ $compliantPercentage + $nonCompliantPercentage }}%, #f59e0b {{ $compliantPercentage + $nonCompliantPercentage }}% {{ $compliantPercentage + $nonCompliantPercentage + $checkingPercentage }}%, #d6d3d1 {{ $compliantPercentage + $nonCompliantPercentage + $checkingPercentage }}% 100%)" role="img" aria-label="{{ $partnerSummary['name'] }}: {{ number_format($partnerSummary['percentage'], 1) }} percent returned">
                                    <div class="grid h-12 w-12 place-content-center rounded-full bg-white">
                                        <span class="font-mono text-sm font-black tabular-nums text-slate-950">{{ number_format($partnerSummary['percentage'], 1) }}%</span>
                                        <span class="text-[8px] font-bold uppercase text-slate-500">returned</span>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-black text-slate-950">{{ $partnerSummary['name'] }}</h3>
                                    <p class="mt-1 text-[10px] leading-4 text-slate-500">Scope: {{ $partnerSummary['scope'] }}</p>
                                    <p class="mt-1 font-mono text-xs font-bold tabular-nums text-slate-600">{{ number_format($partnerSummary['returned']) }} of {{ number_format($partnerSummary['total']) }} schools</p>
                                    <div class="mt-1 grid grid-cols-2 gap-x-2 gap-y-0.5 text-[10px] leading-4 text-slate-500">
                                        <span>Compliant {{ number_format($partnerSummary['compliant']) }} · {{ number_format($compliantPercentage, 1) }}%</span>
                                        <span>Non-compliant {{ number_format($partnerSummary['non_compliant']) }} · {{ number_format($nonCompliantPercentage, 1) }}%</span>
                                        <span>For checking {{ number_format($partnerSummary['checking']) }} · {{ number_format($checkingPercentage, 1) }}%</span>
                                        <span>Still to return {{ number_format($partnerSummary['remaining']) }} · {{ number_format($partnerSummary['total'] > 0 ? ($partnerSummary['remaining'] / $partnerSummary['total']) * 100 : 0, 1) }}%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">No logistics partners are assigned to this project yet.</p>
                @endif
            </article>
        </section>

        @if ($selectedProjectId === null)
            <p class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-600">No projects are available for this report.</p>
        @endif
    </div>
    <script>
        async function saveReportDate(input) {
            const form = input.form;
            const projectId = form.querySelector('[name="project_id"]').value;
            const dateField = input.id === 'delivery-start-date' ? 'delivery_start_date' : 'cutoff_date';
            input.disabled = true;

            try {
                const response = await fetch(@js(route('projects.reports.analytics.dates.update')), {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ project_id: projectId, date_field: dateField, date: input.value }),
                });

                if (!response.ok) {
                    throw new Error('The date could not be saved. Please try again.');
                }

                form.requestSubmit();
            } catch (error) {
                window.alert(error.message);
                input.disabled = false;
            }
        }

        document.querySelector('form[aria-label="Project report filters"]')?.addEventListener('submit', function () {
            const selectedProjectId = this.querySelector('[name="project_id"]').value;

            if (selectedProjectId !== this.dataset.initialProjectId) {
                this.querySelector('[name="delivery_start_date"]')?.removeAttribute('name');
                this.querySelector('[name="cutoff_date"]')?.removeAttribute('name');
            }
        });
    </script>
</x-project_app-layout>
