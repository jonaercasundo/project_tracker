<x-finance_app-layout>
    @php
        $latest = $plan->versions->first();
        $results = $latest?->calculation_results ?? [];
        $allCosts = $latest?->costItems ?? collect();
        $costs = $allCosts->reject(fn ($cost) => $cost->category === 'factory_cost_detail');
        $factoryCostItems = $allCosts->where('category', 'factory_cost_detail');
        $money = fn ($value) => '₱'.number_format((float) ($value ?? 0), 2);
    @endphp
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('finance.plans.index') }}" class="text-xs font-semibold text-emerald-800 hover:underline">← Financial plans</a>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $plan->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $plan->project?->project_name ?? $plan->bidding?->project_name ?? 'Standalone pre-award plan' }}
                    @if($plan->lot) · Lot {{ $plan->lot->lot_no }} @endif
                    @if($plan->bidding) · Procurement: {{ $plan->bidding->status }} @endif
                    · {{ ucfirst($plan->status) }}
                </p>
            </div>
            <a href="{{ route('finance.plans.edit', $plan) }}" class="inline-flex justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Revise draft</a>
        </div>

        @if (session('success'))<div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>@endif

        @if ($latest)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Contract amount', $results['contract_amount'] ?? null], ['Net sales', $results['net_sales'] ?? null], ['Gross profit', $results['gross_profit'] ?? null], ['Projected PPL', $results['ppl'] ?? null]] as [$label, $value])
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-3 text-2xl font-semibold tabular-nums text-slate-950">{{ $money($value) }}</p></div>
                @endforeach
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-950">Version {{ $latest->version_number }} · calculated PPL</h2><p class="mt-1 text-xs text-slate-500">Formula outputs from the workbook rules, saved as a version snapshot.</p></div>
                    <dl class="divide-y divide-slate-100">
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Total cost of sales</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['total_cost_of_sales'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Gross profit · GPM</dt><dd class="text-right font-medium tabular-nums text-slate-900">{{ $money($results['gross_profit'] ?? 0) }} <span class="ml-2 text-slate-500">{{ isset($results['gpm']) ? number_format((float) $results['gpm'] * 100, 2).'%' : '—' }}</span></dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">OPEX</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['opex'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Net profit before tax</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['net_profit_before_tax'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Income tax provision</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['income_tax_provision'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Net profit after tax</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['net_profit_after_tax'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Incentive</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['incentive'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 bg-emerald-50 px-5 py-3 text-sm"><dt class="font-semibold text-emerald-900">PPL · NPM</dt><dd class="text-right font-bold tabular-nums text-emerald-950">{{ $money($results['ppl'] ?? 0) }} <span class="ml-2 font-medium text-emerald-800">{{ isset($results['npm']) ? number_format((float) $results['npm'] * 100, 2).'%' : '—' }}</span></dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Importation VAT</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['importation_vat'] ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-4 px-5 py-3 text-sm"><dt class="text-slate-600">Working capital needed</dt><dd class="font-medium tabular-nums text-slate-900">{{ $money($results['working_capital_needed'] ?? 0) }}</dd></div>
                    </dl>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-950">Cost detail</h2><p class="mt-1 text-xs text-slate-500">Workbook categories and added source-backed entries.</p></div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($costs as $cost)
                            <div class="flex justify-between gap-4 px-5 py-3 text-sm"><div><p class="font-medium text-slate-800">{{ $cost->label }}</p>@if($cost->source_reference)<p class="mt-0.5 text-xs text-slate-500">{{ $cost->source_reference }}</p>@endif</div><p class="shrink-0 font-medium tabular-nums text-slate-900">{{ $money($cost->amount) }}</p></div>
                        @endforeach
                    </div>
                </section>
            </div>

            @if ($factoryCostItems->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <details>
                        <summary class="cursor-pointer border-b border-slate-200 px-5 py-4 font-semibold text-slate-950">Imported factory-cost breakdown <span class="ml-2 text-xs font-medium text-slate-500">{{ $factoryCostItems->count() }} source item(s) · detail only, already included in the factory-cost input</span></summary>
                        <div class="divide-y divide-slate-100">
                            @foreach ($factoryCostItems as $cost)
                                <div class="flex justify-between gap-4 px-5 py-3 text-sm"><div><p class="font-medium text-slate-800">{{ $cost->label }}</p>@if($cost->source_reference)<p class="mt-0.5 text-xs text-slate-500">{{ $cost->source_reference }}</p>@endif</div><p class="shrink-0 font-medium tabular-nums text-slate-900">{{ $money($cost->amount) }}</p></div>
                            @endforeach
                        </div>
                    </details>
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-950">Version history</h2><p class="mt-1 text-xs text-slate-500">Old inputs, assumptions, factory-cost sources, and calculations are preserved.</p></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($plan->versions as $version)
                        <details class="group px-5 py-4" @if($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span><span class="font-semibold text-slate-900">Version {{ $version->version_number }}</span><span class="ml-2 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-800">{{ ucfirst($version->status) }}</span><span class="ml-2 text-sm text-slate-500">{{ $version->change_summary }}</span></span>
                                <span class="text-xs text-slate-500">{{ $version->created_at?->format('M j, Y · g:i A') }} · PPL {{ $money($version->calculation_results['ppl'] ?? 0) }}</span>
                            </summary>
                            <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 text-xs text-slate-600 sm:grid-cols-3">
                                <div><p class="font-semibold uppercase tracking-wide text-slate-500">Inputs</p><pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 font-mono">{{ json_encode($version->input_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                <div><p class="font-semibold uppercase tracking-wide text-slate-500">Assumptions and formulas</p><pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 font-mono">{{ json_encode($version->assumptions_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                <div><p class="font-semibold uppercase tracking-wide text-slate-500">Factory cost snapshot</p><pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 font-mono">{{ json_encode($version->factory_cost_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><p class="mt-3">Created by user #{{ $version->created_by }}</p></div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">This plan does not have a saved financial version yet.</div>
        @endif
    </div>
</x-finance_app-layout>
