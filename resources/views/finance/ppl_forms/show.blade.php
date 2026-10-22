<x-finance_app-layout>
@php
    $sections = config('mmc_ppl_form.sections');
    $plan = $plans->count() === 1 ? $plans->first() : null;
    $version = $plan?->latestVersion;
    $results = $version?->calculation_results ?? [];
    $formatSourceValue = function ($value, string $kind): string {
        if ($value === null || $value === '') {
            return 'Not recorded';
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('M j, Y');
        }
        if ($kind === 'money') {
            return '₱'.number_format((float) $value, 2);
        }
        if ($kind === 'rate' && is_numeric($value) && (float) $value >= 0 && (float) $value <= 1) {
            return $value.' ('.number_format((float) $value * 100, 2).'%)';
        }

        return (string) $value;
    };
    $financialMetrics = [
        ['ABC', 'abc'], ['LCB contract amount', 'contract_amount'], ['Net sales', 'net_sales'],
        ['Factory cost', 'factory_cost'], ['PF1', 'costs.pf1'], ['PF2', 'costs.pf2'], ['PF3', 'costs.pf3'],
        ['LL commission', 'costs.ll_commission'], ['Interest and DST', 'costs.interest_dst_working_capital'],
        ['Shipping and brokerage', 'costs.shipping_brokerage'], ['Logistics', 'costs.logistics'],
        ['Warehouse rental', 'costs.warehouse_rental'], ['Other expenses', 'costs.other_expenses'],
        ['Municipal tax', 'costs.municipal_tax'], ['Performance bond', 'costs.performance_bond'],
        ['Goods insurance', 'costs.goods_insurance'], ['Total cost of sales', 'total_cost_of_sales'],
        ['Gross profit', 'gross_profit'], ['Gross profit margin', 'gpm'], ['OPEX', 'opex'],
        ['Net profit before tax', 'net_profit_before_tax'], ['Income tax', 'income_tax_provision'],
        ['Net profit after tax', 'net_profit_after_tax'], ['Incentive', 'incentive'],
        ['PPL', 'ppl'], ['Net profit margin', 'npm'], ['Working capital needed', 'working_capital_needed'],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">MMC Finance · PPL Form #{{ $data->id }}</p>
            <h1 class="mt-1 max-w-4xl break-words text-xl font-bold leading-snug text-slate-900" title="{{ $data->project_title }}">{{ $data->project_title }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $data->project_code ?: 'No project code' }} · Lot {{ $data->lot_number ?: 'Not recorded' }} · {{ $data->project_id_no ?: 'No project reference' }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="{{ route('ppl_forms.edit', ['id' => $data->id] + request()->query()) }}" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Edit source form</a>
            <a href="{{ route('ppl_forms.index', request()->query()) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to list</a>
        </div>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Historical lot ABC · 1. FORM M</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">{{ $formatSourceValue($data->abc, 'money') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saved LCB discount · 1. FORM N</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">{{ $formatSourceValue($data->lcb_abc, 'rate') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Project List operational status</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $source?->project?->status ?? 'No verified Project List link' }}</p>
        </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="source-heading">
        <h2 id="source-heading" class="text-sm font-bold text-slate-900">Verified Operations source</h2>
        @if ($source)
            <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs font-semibold text-slate-500">Project List</dt><dd class="mt-1 break-words text-slate-900">{{ $source->project?->project_name ?? 'Not linked' }} @if ($source->project) <span class="block text-xs text-slate-500">#{{ $source->project->getKey() }}</span> @endif</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Bidding record</dt><dd class="mt-1 break-words text-slate-900">{{ $source->bidding?->project_name ?? 'Not linked' }} @if ($source->bidding) <span class="block text-xs text-slate-500">#{{ $source->bidding->getKey() }}</span> @endif</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Bidding / legacy lot</dt><dd class="mt-1 text-slate-900">{{ $source->biddingLot?->lot_no ?? $source->legacyLot?->lot_name ?? 'Not linked' }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Match provenance</dt><dd class="mt-1 text-slate-900">{{ ucfirst($source->match_origin ?? 'Verified') }} · confirmed {{ $source->confirmed_at?->format('M j, Y') ?? 'date not recorded' }}</dd></div>
            </dl>
        @else
            <p class="mt-2 text-sm text-slate-600">No verified project, bidding, or lot link is recorded for this PPL Form. Its project status is not inferred from the workbook title or bidding stage.</p>
        @endif
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="results-heading">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="results-heading" class="text-sm font-bold text-slate-900">PPL calculated results</h2>
                <p class="mt-1 text-xs text-slate-600">Values below come only from a saved financial-plan version. This page does not recalculate or copy workbook figures into the historical form.</p>
            </div>
            @if ($plan)
                <a href="{{ route('finance.plans.show', $plan) }}" class="text-sm font-semibold text-blue-700 hover:underline">View plan and version history</a>
            @endif
        </div>
        @if ($plans->count() > 1)
            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Multiple active plans link to this form. Select a plan to review its saved calculations; no single result is assumed here.</p>
            <ul class="mt-3 space-y-2 text-sm">@foreach ($plans as $linkedPlan)<li><a class="font-medium text-blue-700 hover:underline" href="{{ route('finance.plans.show', $linkedPlan) }}">{{ $linkedPlan->name }} · Plan #{{ $linkedPlan->getKey() }}</a></li>@endforeach</ul>
        @elseif (! $version)
            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">No saved financial-plan calculation is linked to this PPL Form. Contract amount, net sales, costs, profit, margins, and working capital are unavailable until a verified plan version exists.</p>
        @else
            <p class="mt-3 text-xs font-semibold text-slate-600">{{ $plan->name }} · Version {{ $version->version_number }} · {{ ucfirst($version->status) }}</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($financialMetrics as [$label, $key])
                    @php
                        $value = data_get($results, $key);
                        $isMargin = in_array($key, ['gpm', 'npm'], true);
                        $isNegative = $value !== null && is_numeric($value) && (float) $value < 0;
                    @endphp
                    <div class="rounded-lg border p-3 {{ $isNegative ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50' }}">
                        <dt class="text-xs font-medium text-slate-600">{{ $label }}</dt>
                        <dd class="mt-1 text-right font-semibold tabular-nums {{ $isNegative ? 'text-rose-800' : 'text-slate-900' }}">@if ($value === null) Not calculated @elseif ($isMargin) {{ number_format((float) $value * 100, 2) }}% @else ₱{{ number_format((float) $value, 2) }} @endif</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="note">The sections below show this form's saved <strong>1. FORM</strong> inputs. “Not recorded” is distinct from zero. Workbook values are not silently imported into historical records.</div>

    @foreach ($sections as $heading => $fields)
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="section-{{ $loop->index }}">
            <h2 id="section-{{ $loop->index }}" class="border-b border-slate-100 pb-2 text-sm font-bold text-slate-800">{{ $heading }}</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($fields as [$name, $column, $label, $kind])
                    <div class="min-w-0 {{ $name === 'project_title' ? 'sm:col-span-2 lg:col-span-3' : '' }}">
                        <dt class="text-xs font-semibold text-slate-500">{{ $label }} <span class="font-normal">({{ $column }})</span></dt>
                        <dd class="mt-1 break-words text-sm tabular-nums {{ $data->getAttribute($name) === null ? 'text-slate-500' : 'text-slate-900' }}">{{ $formatSourceValue($data->getAttribute($name), $kind) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="derived-heading">
        <h2 id="derived-heading" class="text-sm font-bold text-slate-800">Workbook-derived and supplementary fields</h2>
        <dl class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs font-semibold text-slate-500">Q · Factory payment terms</dt><dd class="mt-1 text-sm text-slate-900">{{ $data->factory_payment_terms ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">AS · Business cycle</dt><dd class="mt-1 text-sm text-slate-900">{{ $data->business_cycle ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">AZ · Bidder</dt><dd class="mt-1 break-words text-sm text-slate-900">{{ $data->bidder ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">BA · Authorized signatory</dt><dd class="mt-1 break-words text-sm text-slate-900">{{ $data->authorized_signatory ?? 'Not recorded' }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-600">Q and AS are calculated in the workbook; these are historical saved values, not newly calculated results.</p>
    </section>
</div>
</x-finance_app-layout>
