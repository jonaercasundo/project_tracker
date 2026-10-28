@if (!empty($project['lot_billing_progress']))
    <section class="mt-4 border-t border-slate-100 pt-4" aria-label="Lot billing progress">
        <div class="mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600">Billing by Lot</h3>
            <p class="mt-1 text-xs text-slate-500">Only saved delivery allocations on Billed records count. Shared invoice totals are excluded to avoid double counting. Completed cycles stay in the history below.</p>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
            @foreach ($project['lot_billing_progress'] as $lotBilling)
                <article class="min-w-0 rounded-lg border border-slate-200 bg-white p-4" data-lot-billing-progress="{{ $lotBilling['lot_id'] }}">
                    <h4 class="break-words text-sm font-bold text-slate-900">{{ $lotBilling['lot_name'] }}</h4>
                    <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Contract Price</dt>
                            <dd class="mt-1 break-words text-sm font-semibold tabular-nums text-slate-800">@if ($lotBilling['contract_price'] === null) Not set @else &#8369;{{ number_format((float) $lotBilling['contract_price'], 2) }} @endif</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current Cycle · Amount Billed</dt>
                            <dd class="mt-1 break-words text-sm font-semibold tabular-nums text-slate-800">&#8369;{{ number_format((float) $lotBilling['current_cycle']['amount_billed'], 2) }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current Cycle · % Billed</dt>
                            <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $lotBilling['current_cycle']['percent_billed'] === null ? 'N/A' : number_format($lotBilling['current_cycle']['percent_billed'], 2).'%' }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-600">Not billed this cycle: <span class="font-semibold tabular-nums">&#8369;{{ number_format((float) $lotBilling['current_cycle']['not_billed_amount'], 2) }}</span> · <span class="font-semibold tabular-nums">{{ number_format($lotBilling['current_cycle']['not_billed_percent'], 2) }}%</span></p>

                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <h5 class="text-xs font-bold text-slate-700">Completed billing cycles</h5>
                        @if ($lotBilling['history'] === [])
                            <p class="mt-1 text-xs text-slate-500">No completed billing cycles recorded.</p>
                        @else
                            <ol class="mt-2 space-y-2">
                                @foreach ($lotBilling['history'] as $cycle)
                                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 rounded-md bg-slate-50 px-3 py-2 text-xs" data-billing-cycle="{{ $cycle['cycle_type'] }}-{{ $cycle['cycle_identifier'] }}">
                                        <span class="font-semibold text-slate-700">{{ $cycle['label'] }}@if ($cycle['completed_at']) <span class="font-normal text-slate-500">· {{ \Illuminate\Support\Carbon::parse($cycle['completed_at'], config('app.timezone'))->format('M d, Y') }}</span>@endif</span>
                                        <span class="tabular-nums text-slate-700">&#8369;{{ number_format((float) $cycle['amount_billed'], 2) }} · {{ $cycle['percent_billed'] === null ? 'N/A' : number_format($cycle['percent_billed'], 2).'%' }} billed</span>
                                        <span class="w-full text-slate-500">Not billed: &#8369;{{ number_format((float) $cycle['not_billed_amount'], 2) }} · {{ number_format($cycle['not_billed_percent'], 2) }}%</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
