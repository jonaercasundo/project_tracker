<x-finance_app-layout>
    <div class="mx-auto max-w-7xl space-y-7 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Finance workspace</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Project profitability</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Versioned PPL estimates for linked projects, bidding lots, and standalone pre-award work. Each plan stays a draft until a later workflow is introduced.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('finance.plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">View all plans</a>
                <a href="{{ route('finance.plans.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">New financial draft</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Financial plans</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-slate-950">{{ number_format($planCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">In this company workspace</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Drafts</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-amber-700">{{ number_format($draftCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Not implementation status</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Linked projects</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-slate-950">{{ number_format($projectCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Standalone plans remain available</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-800">Latest projected PPL</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-emerald-950">₱{{ number_format((float) $projectedPplTotal, 2) }}</p>
                <p class="mt-1 text-xs text-emerald-800">Sum of each plan’s current version</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div><h2 class="font-semibold text-slate-950">Recently updated</h2><p class="mt-1 text-xs text-slate-500">Each saved change becomes a separate financial version.</p></div>
                <a href="{{ route('finance.plans.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">All plans</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentPlans as $plan)
                    @php($version = $plan->latestVersion)
                    <a href="{{ route('finance.plans.show', $plan) }}" class="flex flex-col gap-2 px-5 py-4 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-semibold text-slate-900">{{ $plan->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $plan->project?->project_name ?? $plan->bidding?->project_name ?? 'Standalone pre-award' }} · {{ $version ? 'Version '.$version->version_number : 'No saved version' }}</p></div>
                        <div class="text-left sm:text-right"><p class="font-semibold tabular-nums text-slate-900">₱{{ number_format((float) ($version?->calculation_results['ppl'] ?? 0), 2) }}</p><p class="mt-1 text-xs text-slate-500">Projected PPL</p></div>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center"><p class="font-semibold text-slate-900">Your plan ledger is ready</p><p class="mt-1 text-sm text-slate-500">Start a standalone estimate or link one to a project or bidding lot.</p><a href="{{ route('finance.plans.create') }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create your first draft</a></div>
                @endforelse
            </div>
        </section>
    </div>
</x-finance_app-layout>
