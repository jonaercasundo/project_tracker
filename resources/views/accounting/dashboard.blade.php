<x-accounting_app>
    <x-slot name="header">Accounting Control Center</x-slot>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-widest text-blue-600">{{ $workflowCompany->name }} &middot; Accounting</p><h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Your workflow, at a glance.</h1><p class="mt-2 text-sm text-slate-500">Review requests, follow releases, and keep financial evidence together.</p></div>
            <a class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" href="{{ route('accounting.mi.workspace', 'tasks') }}">Open My Tasks &rarr;</a>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach(['budget-review' => 'Needs Review', 'releases' => 'Ready to Release', 'acknowledgment' => 'Awaiting Acknowledgment', 'liquidation-review' => 'Liquidations for Review', 'settlement' => 'Settlement Required', 'tasks' => 'Action Required'] as $key => $label)
                @can(\App\Services\AccountingWorkspace::SECTIONS[$key][1])
                    <a href="{{ route('accounting.mi.workspace', $key) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300 focus:ring-2 focus:ring-blue-600"><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ $counters[$key] }}</p><p class="mt-2 text-xs text-slate-500">{{ $key === 'tasks' ? 'Actions available to you' : ($key === 'settlement' ? 'Pending settlement or recorded balance' : 'Open queue') }}</p></a>
                @endcan
            @endforeach
        </div>
        @include('accounting.summary')
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="text-lg font-bold text-slate-900">Action Required</h2><p class="mt-1 text-sm text-slate-500">Your permitted Accounting actions, oldest waiting first.</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $rows->total() }} tasks</span></div>
            @include('accounting.filters')
            @include('accounting.rows')
        </section>
        @if($workflowQueues['Budget requests awaiting approval'])
            <p class="text-sm text-slate-500">{{ $workflowQueues['Budget requests awaiting approval'] }} budget request(s) await a designated approver. Accounting review and approval are separate steps.</p>
        @endif
        <p class="text-xs text-slate-500">Waiting time is shown from recorded timestamps. Overdue labels require a confirmed due-date or service-level policy.</p>
    </div>
</x-accounting_app>
