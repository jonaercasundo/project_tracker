<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-bold text-slate-900">AI Analytics & Chart Studio</h1></x-slot>
    <div class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Verified MMC financial data</h2>
            <p class="mt-2 text-sm text-slate-600">Charts and comparisons below are calculated from saved financial-plan versions and PPL Form recognition years. They are descriptive; no AI estimate or missing value is inserted.</p>
            @unless ($hasPlans) <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Financial-plan schema is unavailable. Historical PPL Forms remain visible, but plan metrics cannot be calculated.</p> @endunless
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold text-slate-600">Historical PPL Forms</p><p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($formCount) }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold text-slate-600">Forms with one saved plan version</p><p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($summary['plan_count'] ?? 0) }}</p></div>
                <div class="rounded-lg bg-amber-50 p-4"><p class="text-xs font-semibold text-amber-900">Without one usable saved plan</p><p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($formCount - ($summary['plan_count'] ?? 0)) }}</p><p class="text-xs text-amber-900">Includes missing and multiple plans; a valid saved zero remains included.</p></div>
            </div>
        </section>

        <section id="chart-studio" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Chart Studio · Revenue and PPL by recognition year</h2>
            <p class="mt-1 text-xs text-slate-600">One current saved version per PPL Form. Bars represent net sales; PPL figures come from the same versions. The year is the historical PPL Form recognition year.</p>
            <div class="mt-5 space-y-4">
                @forelse ($annual as $row)
                    @php($revenue = $row['summary']['amounts']['net_sales'])
                    @php($ppl = $row['summary']['amounts']['ppl'])
                    <div class="grid gap-2 border-b border-slate-100 pb-4 sm:grid-cols-[5rem_1fr_11rem] sm:items-center">
                        <span class="text-sm font-semibold text-slate-800">{{ $row['year'] }}</span>
                        <div class="h-5 overflow-hidden rounded bg-slate-100" role="img" aria-label="{{ $row['year'] }} net sales ₱{{ number_format((float) $revenue, 2) }}">
                            <div class="h-full rounded {{ (float) $revenue < 0 ? 'bg-rose-600' : 'bg-emerald-600' }}" style="width: {{ $maxRevenue > 0 ? min(100, abs((float) $revenue) / $maxRevenue * 100) : 0 }}%"></div>
                        </div>
                        <div class="text-right text-xs tabular-nums"><p>Sales ₱{{ number_format((float) $revenue, 2) }}</p><p class="{{ (float) $ppl < 0 ? 'font-semibold text-rose-700' : 'text-slate-600' }}">PPL ₱{{ number_format((float) $ppl, 2) }}</p></div>
                    </div>
                @empty <p class="text-sm text-slate-500">No saved plan data with a PPL Form recognition year is available for charting.</p> @endforelse
            </div>
        </section>

        <section id="variance" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Financial Variance Analysis</h2>
            <p class="mt-1 text-xs text-slate-600">Change in projected PPL between the two latest saved versions of each plan. This is a version comparison, not actual-versus-budget performance.</p>
            <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="p-3">Plan</th><th class="p-3">Versions</th><th class="p-3 text-right">PPL change</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($variance as $row)<tr><td class="p-3"><a href="{{ route('finance.plans.show', $row['plan']) }}" class="font-semibold text-emerald-800 hover:underline">{{ $row['plan']->name }}</a></td><td class="p-3">v{{ $row['previous']->version_number }} → v{{ $row['latest']->version_number }}</td><td class="p-3 text-right tabular-nums {{ $row['delta'] !== null && (float) $row['delta'] < 0 ? 'font-semibold text-rose-700' : '' }}">{{ $row['delta'] === null ? 'Missing saved result' : '₱'.number_format((float) $row['delta'], 2) }}</td></tr>@empty <tr><td colspan="3" class="p-5 text-center text-slate-500">No plan has two comparable saved versions.</td></tr> @endforelse</tbody></table></div>
        </section>

        <section id="history" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Plan Versions & History</h2>
            <div class="mt-4 divide-y divide-slate-100">@forelse ($history as $version)<a href="{{ route('finance.plans.show', $version->plan) }}" class="flex flex-wrap justify-between gap-2 py-3 text-sm hover:bg-slate-50"><span class="font-semibold text-emerald-800">{{ $version->plan?->name }} · v{{ $version->version_number }}</span><span class="text-slate-600">{{ $version->change_summary ?: 'Saved version' }} · {{ $version->created_at?->format('Y-m-d H:i') }}</span></a>@empty <p class="py-5 text-sm text-slate-500">No saved financial-plan versions are available.</p> @endforelse</div>
        </section>

        <section id="reports" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">PPL Reports</h2>
            <p class="mt-1 text-sm text-slate-600">Download the existing MMC monitoring report or filter the live PPL Forms table before reviewing totals.</p>
            <div class="mt-4 flex flex-wrap gap-2">@if ($hasPlans)<a href="{{ route('finance.plans.monitoring.export') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Download monitoring CSV</a>@else <span class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-500">Report unavailable until financial-plan migration</span>@endif <a href="{{ route('ppl_forms.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Review PPL Forms</a></div>
        </section>
    </div>
</x-finance_app-layout>
