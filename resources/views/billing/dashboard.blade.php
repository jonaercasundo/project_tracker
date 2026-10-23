<x-project_app-layout>
    <x-slot name="header">
        <div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Metro Mobilia · Billing</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Delivery billing desk</h1>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <section aria-label="Billing totals" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'All delivery records', 'value' => $totals['total'], 'tone' => 'text-slate-950', 'key' => null],
                ['label' => 'Pending billing', 'value' => $totals['pending_billing'], 'tone' => 'text-amber-700', 'key' => 'pending_billing'],
                ['label' => 'For billing', 'value' => $totals['for_billing'], 'tone' => 'text-blue-700', 'key' => 'for_billing'],
                ['label' => 'Billed', 'value' => $totals['billed'], 'tone' => 'text-emerald-700', 'key' => 'billed'],
                ['label' => 'On hold', 'value' => $totals['on_hold'], 'tone' => 'text-rose-700', 'key' => 'on_hold'],
            ] as $card)
                <a href="{{ route('billing.records.index', $card['key'] ? ['billing_status' => $card['key']] : []) }}"
                   class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 font-mono text-3xl font-bold tabular-nums {{ $card['tone'] }}">{{ number_format($card['value']) }}</p>
                </a>
            @endforeach
        </section>

        <section aria-label="Completion" class="grid gap-4 rounded-xl border border-slate-200 bg-slate-950 p-5 text-white sm:grid-cols-[1fr_auto] sm:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-300">Billed delivery records</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-700" role="progressbar" aria-label="Billing completion" aria-valuenow="{{ $totals['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-cyan-300 transition-[width]" style="width: {{ $totals['completion_percentage'] }}%"></div>
                </div>
            </div>
            <p class="font-mono text-3xl font-bold tabular-nums">{{ number_format($totals['completion_percentage'], 1) }}<span class="ml-1 text-lg text-cyan-300">%</span></p>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">By project</h2>
                    <p class="mt-1 text-sm text-slate-500">Status counts use delivery rows, not shared legacy billing groups.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Project</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">For billing</th><th class="px-3 py-3">Billed</th><th class="px-5 py-3">On hold</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($projects as $project)
                                <tr>
                                    <td class="max-w-xs px-5 py-3 font-medium text-slate-800">{{ $project->project_name }}</td>
                                    <td class="px-3 py-3 font-mono tabular-nums">{{ number_format($project->total) }}</td>
                                    <td class="px-3 py-3 font-mono tabular-nums text-blue-700">{{ number_format($project->for_billing) }}</td>
                                    <td class="px-3 py-3 font-mono tabular-nums text-emerald-700">{{ number_format($project->billed) }}</td>
                                    <td class="px-5 py-3 font-mono tabular-nums text-rose-700">{{ number_format($project->on_hold) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No delivery records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">By delivery date</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest 14 delivery dates with a recorded delivery.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Delivery date</th><th class="px-3 py-3">Records</th><th class="px-5 py-3">Billed</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($byDate as $day)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $day->delivery_date }}</td>
                                    <td class="px-3 py-3 font-mono tabular-nums">{{ number_format($day->total) }}</td>
                                    <td class="px-5 py-3 font-mono tabular-nums text-emerald-700">{{ number_format($day->billed) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No dated delivery records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">Assigned billing personnel</h2>
                    <p class="mt-1 text-sm text-slate-500">Delivery submissions attributed to each user.</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($assignedPersonnel as $person)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <span class="font-medium text-slate-800">{{ $person->name }}</span>
                            <span class="text-right font-mono text-sm tabular-nums"><span class="text-blue-700">{{ number_format($person->submitted_count) }}</span><span class="px-1 text-slate-400">/</span><span class="text-emerald-700">{{ number_format($person->billed_count) }}</span></span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">No submissions have been recorded.</li>
                    @endforelse
                </ul>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">Recent billing activity</h2>
                    <p class="mt-1 text-sm text-slate-500">Every status change is kept in the delivery audit history.</p>
                </div>
                <ol class="divide-y divide-slate-100">
                    @forelse ($recentActivity as $activity)
                        <li class="grid gap-1 px-5 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <a href="{{ route('billing.records.show', $activity->delivery_id) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $activity->dr_no }}</a>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $activity->actor_name ?? 'Former user' }} ·
                                    {{ $statuses[$activity->previous_status] ?? 'Start' }} →
                                    {{ $statuses[$activity->new_status] ?? $activity->new_status }}
                                    @if ($activity->invoice_no) · Invoice {{ $activity->invoice_no }} @endif
                                </p>
                            </div>
                            <time class="text-xs tabular-nums text-slate-500">{{ $activity->created_at }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">No billing status changes have been recorded.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</x-project_app-layout>
