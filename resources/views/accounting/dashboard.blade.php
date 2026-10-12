@php
    use Illuminate\Support\Str;

    /*
     * Card definitions live in one place. Each card: key => label + hint.
     * The ability is resolved from the existing AccountingWorkspace::SECTIONS
     * map, so no controller changes are required.
     */
    $cards = [
        'budget-review'      => ['label' => 'Needs review',              'hint' => 'Open queue'],
        'releases'           => ['label' => 'Ready to release',          'hint' => 'Open queue'],
        'acknowledgment'     => ['label' => 'Awaiting acknowledgment',   'hint' => 'Open queue'],
        'liquidation-review' => ['label' => 'Liquidations for review',   'hint' => 'Open queue'],
        'settlement'         => ['label' => 'Settlement required',       'hint' => 'Pending settlement or recorded balance'],
        'tasks'              => ['label' => 'Action required',           'hint' => 'Actions available to you'],
    ];

    $taskTotal      = $rows->total();
    $pendingBudgets = (int) ($workflowQueues['Budget requests awaiting approval'] ?? 0);
@endphp

<x-accounting_app>
    <x-slot name="header">Accounting Control Center</x-slot>

    <div class="space-y-6">

        {{-- Page header --}}
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-blue-600">
                    {{ $workflowCompany->name }} &middot; Accounting
                </p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                    Your workflow at a glance
                </h1>
                <p class="mt-2 max-w-prose text-sm text-slate-500">
                    Review requests, follow releases, and keep financial evidence together.
                </p>
            </div>

            <a href="{{ route('accounting.mi.workspace', 'tasks') }}"
               class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors
                      hover:bg-blue-700
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                Open my tasks
            </a>
        </header>

        {{-- Counters --}}
        <section aria-label="Workflow queues" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $key => $card)
                @can(\App\Services\AccountingWorkspace::SECTIONS[$key][1])
                    @php $count = (int) ($counters[$key] ?? 0); @endphp

                    <a href="{{ route('accounting.mi.workspace', $key) }}"
                       aria-label="{{ $card['label'] }}: {{ $count }}. {{ $card['hint'] }}"
                       class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors
                              hover:border-blue-300
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($count) }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $card['hint'] }}</p>
                    </a>
                @endcan
            @endforeach
        </section>

        @include('accounting.summary')

        {{-- Task queue --}}
        <section aria-labelledby="action-required-heading"
                 class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h2 id="action-required-heading" class="text-lg font-bold text-slate-900">Action required</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Your permitted Accounting actions, oldest waiting first.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    {{ number_format($taskTotal) }} {{ Str::plural('task', $taskTotal) }}
                </span>
            </div>

            @include('accounting.filters')

            @if ($taskTotal === 0)
                <div class="px-5 py-12 text-center">
                    <p class="text-sm font-semibold text-slate-900">No tasks match your filters</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Clear the filters or check back when new requests arrive.
                    </p>
                </div>
            @else
                @include('accounting.rows')
            @endif
        </section>

        {{-- Notes --}}
        <div class="space-y-2">
            @if ($pendingBudgets > 0)
                <p class="text-sm text-slate-500">
                    {{ number_format($pendingBudgets) }} budget {{ Str::plural('request', $pendingBudgets) }}
                    {{ $pendingBudgets === 1 ? 'awaits' : 'await' }} a designated approver.
                    Accounting review and approval are separate steps.
                </p>
            @endif

            <p class="text-xs text-slate-500">
                Waiting time is shown from recorded timestamps. Overdue labels require a confirmed due-date or service-level policy.
            </p>
        </div>
    </div>
</x-accounting_app>