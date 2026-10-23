<x-project_app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Operations / Billing</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 sm:text-[28px]">Billing Dashboard</h1>
            <p class="mt-1 max-w-2xl text-sm font-medium leading-6 text-slate-500">Monitor delivery receipts, billing readiness, processing progress, and completed billing.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <form method="GET" action="{{ route('billing.dashboard') }}" x-data="{ loading: false }" @submit="loading = true"
              class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[minmax(16rem,1.25fr)_minmax(12rem,0.8fr)_minmax(12rem,0.8fr)_auto] lg:items-end lg:p-5">
            <x-searchable-project-filter
                :projects="$projects"
                name="project_id"
                :selected="$filters['project_id'] ?? ''"
                all-label="All Projects"
                id="billing-dashboard-project"
            />
            <label class="space-y-1 text-xs font-semibold text-slate-600">
                Delivery date from
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">
                Delivery date to
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <div class="flex items-center gap-2 lg:justify-end">
                <a href="{{ route('billing.dashboard') }}" class="rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Clear</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg x-show="!loading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m14.836 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-14.837-2m14.837 2H15" /></svg>
                    <svg x-cloak x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" /></svg>
                    <span x-text="loading ? 'Refreshing…' : 'Refresh dashboard'">Refresh dashboard</span>
                </button>
            </div>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-2 px-1">
            <p class="text-xs font-medium text-slate-500">Billing status is measured separately from delivery status.</p>
            <p class="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                Data refreshed {{ $lastUpdated->format('M j, Y · g:i A') }}
            </p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @php
            $cards = [
                ['label' => 'Delivered DRs', 'value' => $totals['total'], 'description' => 'Delivered or accepted records', 'color' => 'blue', 'icon' => 'M5 12h14M12 5l7 7-7 7'],
                ['label' => 'Ready for Billing', 'value' => $totals['ready'], 'description' => 'Eligible for billing submission', 'color' => 'cyan', 'icon' => 'M9 12l2 2 4-4m5-3v6a8 8 0 1 1-3-6'],
                ['label' => 'For Billing', 'value' => $totals['for_billing'], 'description' => 'Submitted to the billing queue', 'color' => 'violet', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
                ['label' => 'Billed DRs', 'value' => $totals['billed'], 'description' => 'Confirmed as billed', 'color' => 'emerald', 'icon' => 'M5 12l4 4L19 6'],
                ['label' => 'On Hold', 'value' => $totals['blocked'], 'description' => 'Explicitly blocked in billing', 'color' => 'rose', 'icon' => 'M12 9v4m0 4h.01M10.3 3.9 2.5 17.4A1.8 1.8 0 0 0 4 20h16a1.8 1.8 0 0 0 1.5-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z'],
                ['label' => 'Active Batches', 'value' => $totals['active_batches'], 'description' => 'Batches still in For Billing', 'color' => 'amber', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
            ];
            $toneClasses = [
                'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
                'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ];
            $distributionSegments = [];
            $distributionCursor = 0;
            foreach ($statusDistribution as $segment) {
                $distributionEnd = $distributionCursor + $segment['percent'];
                $distributionSegments[] = $segment['color'].' '.$distributionCursor.'% '.$distributionEnd.'%';
                $distributionCursor = $distributionEnd;
            }
            $distributionBackground = $totals['total'] > 0
                ? 'conic-gradient('.implode(', ', $distributionSegments).')'
                : 'conic-gradient(#e2e8f0 0% 100%)';
            $maxActivity = max(1, ...array_map(fn (array $day): int => max($day['submitted'], $day['billed']), $activityTrend ?: [['submitted' => 0, 'billed' => 0]]));
        @endphp

        <section aria-label="Billing key performance indicators" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $card)
                <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 font-mono text-3xl font-bold tabular-nums tracking-tight text-slate-950 sm:text-4xl">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClasses[$card['color']] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        <section aria-label="Billing completion" class="grid gap-5 rounded-2xl border border-slate-800 bg-slate-950 p-5 text-white shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-6">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-cyan-300">Billing completion</p>
                        <h2 class="mt-1 text-lg font-bold">{{ number_format($totals['billed']) }} of {{ number_format($totals['total']) }} delivered DRs confirmed</h2>
                    </div>
                    <p class="font-mono text-3xl font-bold tabular-nums text-white">{{ number_format($totals['completion_percentage'], 1) }}<span class="ml-1 text-lg text-cyan-300">%</span></p>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-700" role="progressbar" aria-label="Billing completion" aria-valuenow="{{ $totals['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-cyan-300 transition-[width] duration-500" style="width: {{ $totals['completion_percentage'] }}%"></div>
                </div>
            </div>
            <a href="{{ route('billing.records.index', array_filter(['billing_status' => 'billed'] + $filters)) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-cyan-300 hover:text-cyan-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300">
                View billing history <span aria-hidden="true">&rarr;</span>
            </a>
        </section>

        <div class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.13em] text-blue-700">Portfolio status</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">Billing status distribution</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($totals['total']) }} delivered or accepted DRs in the selected scope.</p>
                </div>
                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-center">
                    <div class="relative h-40 w-40 shrink-0 rounded-full" role="img" aria-label="Billing status distribution: @foreach ($statusDistribution as $segment) {{ $segment['label'] }} {{ $segment['value'] }}; @endforeach" style="background: {{ $distributionBackground }}">
                        <div class="absolute inset-5 flex flex-col items-center justify-center rounded-full bg-white text-center">
                            <span class="font-mono text-3xl font-bold tabular-nums text-slate-950">{{ number_format($totals['total']) }}</span>
                            <span class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Delivered DRs</span>
                        </div>
                    </div>
                    <ul class="grid w-full gap-3 sm:max-w-xs">
                        @foreach ($statusDistribution as $segment)
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="inline-flex min-w-0 items-center gap-2.5 text-slate-600">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $segment['color'] }}" aria-hidden="true"></span>
                                    <span class="truncate">{{ $segment['label'] }}</span>
                                </span>
                                <span class="font-mono font-bold tabular-nums text-slate-900">{{ number_format($segment['value']) }} <span class="font-sans text-xs font-medium text-slate-400">{{ number_format($segment['percent'], 1) }}%</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-violet-700">Processing pace</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">Billing activity trend</h2>
                        <p class="mt-1 text-sm text-slate-500">Daily submissions and billing confirmations for matching deliveries.</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-violet-600"></span>Submitted</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-emerald-600"></span>Billed</span>
                    </div>
                </div>
                @if (count($activityTrend))
                    <div class="mt-6 grid min-h-44 grid-cols-7 items-end gap-2 sm:grid-cols-[repeat(14,minmax(0,1fr))]" role="img" aria-label="Billing activity trend with daily submitted and billed counts">
                        @foreach ($activityTrend as $day)
                            @php($submittedHeight = $day['submitted'] > 0 ? max(8, round(($day['submitted'] / $maxActivity) * 100)) : 2)
                            @php($billedHeight = $day['billed'] > 0 ? max(8, round(($day['billed'] / $maxActivity) * 100)) : 2)
                            <div class="flex h-40 min-w-0 flex-col items-center justify-end gap-2" title="{{ $day['date'] }}: {{ $day['submitted'] }} submitted, {{ $day['billed'] }} billed">
                                <div class="flex h-full w-full items-end justify-center gap-1">
                                    <span class="w-2 rounded-t bg-violet-600" style="height: {{ $submittedHeight }}%"></span>
                                    <span class="w-2 rounded-t bg-emerald-600" style="height: {{ $billedHeight }}%"></span>
                                </div>
                                <span class="w-full truncate text-center text-[9px] font-medium text-slate-400">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M j') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 flex min-h-44 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                        <p class="text-sm font-semibold text-slate-700">No billing activity in this date range</p>
                        <p class="mt-1 text-xs text-slate-500">Submissions and confirmations will appear here when recorded.</p>
                    </div>
                @endif
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <p class="text-xs font-bold uppercase tracking-[0.13em] text-blue-700">Delivery pipeline</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">By delivery date</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest 14 delivery dates in the selected scope.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-2.5">Delivery date</th><th scope="col" class="px-3 py-2.5 text-right">Delivered DRs</th><th scope="col" class="px-5 py-2.5 text-right">Billed</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($byDate as $day)
                                <tr><td class="px-5 py-3 font-medium text-slate-700">{{ $day->delivery_date }}</td><td class="px-3 py-3 text-right font-mono tabular-nums text-slate-800">{{ number_format($day->total) }}</td><td class="px-5 py-3 text-right font-mono tabular-nums text-emerald-700">{{ number_format($day->billed) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">No delivered records match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <p class="text-xs font-bold uppercase tracking-[0.13em] text-violet-700">Team throughput</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">Assigned billing personnel</h2>
                    <p class="mt-1 text-sm text-slate-500">Submitted DRs and billed DRs attributed to each user.</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($assignedPersonnel as $person)
                        <li class="flex items-center justify-between gap-4 px-5 py-3.5 sm:px-6">
                            <span class="min-w-0 truncate font-semibold text-slate-800">{{ $person->name }}</span>
                            <span class="shrink-0 text-right font-mono text-sm tabular-nums"><span class="text-violet-700">{{ number_format($person->submitted_count) }}</span><span class="px-1.5 text-slate-300">/</span><span class="text-emerald-700">{{ number_format($person->billed_count) }}</span></span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">No billing submissions in this filter scope.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.13em] text-blue-700">Project portfolio</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">Project billing performance</h2>
                    <p class="mt-1 text-sm text-slate-500">Counts use unique Delivered or Accepted delivery rows.</p>
                </div>
                @can('billing.records.view')
                    <a href="{{ route('billing.records.index', $filters) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Open billing history <span aria-hidden="true">&rarr;</span></a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="min-w-64 px-5 py-3 sm:px-6">Project</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Delivered</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Ready</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">For Billing</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Billed</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">On Hold</th>
                            <th scope="col" class="min-w-36 px-5 py-3 text-right sm:px-6">Completion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($projectPerformance as $project)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="min-w-64 px-5 py-3.5 sm:px-6">
                                    <p class="whitespace-normal break-words font-semibold leading-5 text-slate-900">{{ $project->project_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $project->project_code ?: $project->ref_no ?: 'Project '.$project->project_id }}</p>
                                </td>
                                <td class="px-3 py-3.5 text-right font-mono font-semibold tabular-nums text-slate-800">{{ number_format($project->total) }}</td>
                                <td class="px-3 py-3.5 text-right font-mono tabular-nums text-blue-700">{{ number_format($project->ready) }}</td>
                                <td class="px-3 py-3.5 text-right font-mono tabular-nums text-violet-700">{{ number_format($project->for_billing) }}</td>
                                <td class="px-3 py-3.5 text-right font-mono tabular-nums text-emerald-700">{{ number_format($project->billed) }}</td>
                                <td class="px-3 py-3.5 text-right font-mono tabular-nums text-rose-700">{{ number_format($project->blocked) }}</td>
                                <td class="px-5 py-3.5 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $project->completion_percentage }}%"></div></div>
                                        <span class="w-12 font-mono text-xs font-bold tabular-nums text-slate-700">{{ number_format($project->completion_percentage, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center sm:px-6">
                                <p class="text-sm font-semibold text-slate-700">No delivered records match these filters</p>
                                <p class="mt-1 text-xs text-slate-500">Try another project or widen the delivery date range.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-blue-700">Operations queue</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">Recent billing batches</h2>
                    </div>
                    @can('billing.records.view')
                        <a href="{{ route('billing.batches.index') }}" class="text-xs font-bold text-blue-700 hover:text-blue-900">All batches <span aria-hidden="true">&rarr;</span></a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-2.5">Batch</th><th scope="col" class="px-3 py-2.5">Project</th><th scope="col" class="px-3 py-2.5 text-right">DRs</th><th scope="col" class="px-5 py-2.5 text-right">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentBatches as $batch)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3"><a href="{{ route('billing.batches.show', $batch->id) }}" class="font-mono text-xs font-bold text-blue-700 hover:underline">{{ $batch->batch_ref }}</a><p class="mt-1 text-[11px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('M j, Y') }} · {{ $batch->created_by_name ?? 'Former user' }}</p></td>
                                    <td class="max-w-48 px-3 py-3"><span class="block whitespace-normal break-words text-xs leading-5 text-slate-600">{{ $batch->project_names ?: 'Project details unavailable' }}</span></td>
                                    <td class="px-3 py-3 text-right font-mono tabular-nums text-slate-700">{{ number_format($batch->delivery_count) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right"><span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-bold text-violet-800">{{ str_replace('_', ' ', ucfirst($batch->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">No active billing batches in this filter scope.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-emerald-700">Confirmed work</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">Recent billing confirmations</h2>
                    </div>
                    @can('billing.records.view')
                        <a href="{{ route('billing.records.index', array_filter(['billing_status' => 'billed'] + $filters)) }}" class="text-xs font-bold text-blue-700 hover:text-blue-900">Billing history <span aria-hidden="true">&rarr;</span></a>
                    @endcan
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($recentConfirmations as $confirmation)
                        <li class="flex items-start justify-between gap-4 px-5 py-3.5 sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('billing.records.show', $confirmation->delivery_id) }}" class="font-mono text-sm font-bold text-slate-900 hover:text-blue-700">{{ $confirmation->dr_no }}</a>
                                <p class="mt-1 whitespace-normal break-words text-xs leading-5 text-slate-500">{{ $confirmation->project_name ?? 'Unknown project' }} @if($confirmation->invoice_no) · Invoice {{ $confirmation->invoice_no }} @endif</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $confirmation->actor_name ?? 'Former user' }}</p>
                            </div>
                            <time class="shrink-0 text-xs tabular-nums text-slate-500">{{ \Illuminate\Support\Carbon::parse($confirmation->created_at)->format('M j, Y') }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-slate-500">No billing confirmations in this filter scope.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-rose-700">Requires review</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">Billing exceptions</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">On-hold DRs need attention. Missing POD is informational; it does not block billing submission.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-800">{{ number_format($totals['blocked']) }} on hold</span>
                        @if ($returnedBatchCount > 0)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">{{ number_format($returnedBatchCount) }} returned</span>
                        @endif
                    </div>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($blockedDeliveries as $blocked)
                        <li class="flex items-start justify-between gap-4 px-5 py-3.5 sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('billing.records.show', $blocked->delivery_id) }}" class="font-mono text-sm font-bold text-slate-900 hover:text-blue-700">{{ $blocked->dr_no }}</a>
                                <p class="mt-1 whitespace-normal break-words text-xs leading-5 text-slate-500">{{ $blocked->project_name ?? 'Unknown project' }} · {{ $blocked->billing_remarks ?: 'No reason was recorded.' }}</p>
                            </div>
                            <time class="shrink-0 text-xs tabular-nums text-slate-500">{{ $blocked->delivery_date }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-7 text-center text-sm text-slate-500">No billing records are on hold in this filter scope.</li>
                    @endforelse
                </ul>
                @if ($returnedBatches->isNotEmpty())
                    <div class="border-t border-slate-100 px-5 py-3 sm:px-6">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-amber-800">Returned batches for correction</h3>
                        <ul class="mt-2 divide-y divide-slate-100">
                            @foreach ($returnedBatches as $batch)
                                <li class="flex items-start justify-between gap-3 py-2.5">
                                    <div class="min-w-0">
                                        <a href="{{ route('billing.batches.show', $batch->id) }}" class="font-mono text-xs font-bold text-blue-700 hover:underline">{{ $batch->batch_ref }}</a>
                                        <p class="mt-1 whitespace-normal break-words text-xs leading-5 text-slate-500">{{ $batch->project_names ?: 'Project details unavailable' }} · {{ number_format($batch->delivery_count) }} DRs · {{ $batch->created_by_name ?? 'Former user' }}</p>
                                    </div>
                                    <time class="shrink-0 text-[11px] tabular-nums text-slate-500">{{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('M j, Y') }}</time>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                    {{ number_format($missingPodCount) }} delivered DRs have no recorded Proof of Delivery in this scope; this is an informational check only.
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-slate-600">Audit trail</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">Latest billing status changes</h2>
                    </div>
                    @can('billing.records.view')
                        <a href="{{ route('billing.records.index', $filters) }}" class="text-xs font-bold text-blue-700 hover:text-blue-900">View ledger <span aria-hidden="true">&rarr;</span></a>
                    @endcan
                </div>
                <ol class="divide-y divide-slate-100">
                    @forelse ($recentActivity as $activity)
                        <li class="grid gap-1 px-5 py-3.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('billing.records.show', $activity->delivery_id) }}" class="font-mono text-sm font-bold text-slate-900 hover:text-blue-700">{{ $activity->dr_no }}</a>
                                <p class="mt-1 whitespace-normal break-words text-xs leading-5 text-slate-500">
                                    {{ $activity->actor_name ?? 'Former user' }} ·
                                    {{ $statuses[$activity->previous_status] ?? 'Start' }} &rarr;
                                    {{ $statuses[$activity->new_status] ?? $activity->new_status }}
                                    @if ($activity->invoice_no) · Invoice {{ $activity->invoice_no }} @endif
                                </p>
                            </div>
                            <time class="text-xs tabular-nums text-slate-500">{{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('M j, Y · g:i A') }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-slate-500">No billing status changes have been recorded in this filter scope.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</x-project_app-layout>
