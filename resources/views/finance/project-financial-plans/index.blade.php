<x-finance_app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Project finance</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Financial plans</h1>
                <p class="mt-1 text-sm text-slate-500">Saved estimates remain drafts. Every save creates a new version.</p>
            </div>
            <a href="{{ route('finance.plans.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Create financial draft</a>
        </div>

        @if (session('success'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Financial plan</th>
                            <th class="px-5 py-3">Linked project</th>
                            <th class="px-5 py-3">Latest version</th>
                            <th class="px-5 py-3 text-right">Projected PPL</th>
                            <th class="px-5 py-3">Updated</th>
                            <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($plans as $plan)
                            @php($version = $plan->latestVersion)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <a href="{{ route('finance.plans.show', $plan) }}" class="font-semibold text-slate-900 hover:text-emerald-800">{{ $plan->name }}</a>
                                    <div class="mt-1 text-xs text-slate-500">{{ ucfirst($plan->status) }} · {{ $plan->versions_count ?? $plan->versions()->count() }} version(s)</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $plan->project?->project_name ?? $plan->bidding?->project_name ?? 'Standalone pre-award' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $version ? 'v'.$version->version_number : '—' }}</td>
                                <td class="px-5 py-4 text-right font-semibold tabular-nums text-slate-900">₱{{ number_format((float) ($version?->calculation_results['ppl'] ?? 0), 2) }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $plan->updated_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-semibold text-emerald-800 hover:underline" href="{{ route('finance.plans.show', $plan) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-14 text-center">
                                <p class="font-semibold text-slate-900">No financial drafts yet</p>
                                <p class="mt-1 text-sm text-slate-500">Create a project estimate or start a standalone pre-award plan.</p>
                                <a href="{{ route('finance.plans.create') }}" class="mt-4 inline-flex rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Start a draft</a>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($plans->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $plans->links() }}</div>
            @endif
        </div>
    </div>
</x-finance_app-layout>
