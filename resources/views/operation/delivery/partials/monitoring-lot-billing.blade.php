@if (!empty($project['lot_billing_progress']))
    <section class="mt-4 border-t border-slate-100 pt-4" aria-label="Lot billing progress">
        <div class="mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600">Billing by Lot</h3>
            <p class="mt-1 text-xs text-slate-500">Only saved delivery allocations on Billed records count. Shared invoice totals are excluded to avoid double counting. Completed cycles stay in the history below.</p>
        </div>
        <div class="space-y-3">
            @foreach ($project['lot_billing_progress'] as $lotBilling)
                <article class="w-full min-w-0 rounded-lg border border-slate-200 bg-white p-4" data-lot-billing-progress="{{ $lotBilling['lot_id'] }}">
                    <header class="border-b border-slate-100 pb-3">
                        <h4 class="text-sm font-bold text-slate-900">{{ $lotBilling['lot_name'] }}</h4>
                    </header>
                    <dl class="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase leading-4 tracking-wide text-slate-500">Contract Price</dt>
                            <dd class="mt-1 whitespace-nowrap text-sm font-semibold tabular-nums text-slate-800">@if ($lotBilling['contract_price'] === null) Not set @else &#8369;{{ number_format((float) $lotBilling['contract_price'], 2) }} @endif</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase leading-4 tracking-wide text-slate-500">Current Cycle Amount Billed</dt>
                            <dd class="mt-1 whitespace-nowrap text-sm font-semibold tabular-nums text-slate-800">&#8369;{{ number_format((float) $lotBilling['current_cycle']['amount_billed'], 2) }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase leading-4 tracking-wide text-slate-500">Current Cycle % Billed</dt>
                            <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $lotBilling['current_cycle']['percent_billed'] === null ? 'N/A' : number_format($lotBilling['current_cycle']['percent_billed'], 2).'%' }}</dd>
                        </div>
                    </dl>
                    <div class="border-t border-slate-100 pt-3">
                        <p class="text-xs font-semibold text-slate-600">Not billed this cycle</p>
                        <p class="mt-1 whitespace-nowrap text-sm tabular-nums text-slate-800">&#8369;{{ number_format((float) $lotBilling['current_cycle']['not_billed_amount'], 2) }} <span class="text-slate-500">&middot; {{ number_format($lotBilling['current_cycle']['not_billed_percent'], 2) }}%</span></p>
                    </div>

                    <section class="mt-4 border-t border-slate-100 pt-3" aria-label="Billing status breakdown for {{ $lotBilling['lot_name'] }}">
                        <h5 class="text-xs font-bold text-slate-700">Billing Status Breakdown</h5>
                        <dl class="mt-2 grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($lotBilling['billing_status_breakdown'] as $status)
                                <div class="min-w-0 rounded-md bg-slate-50 px-3 py-2" data-billing-status="{{ $status['status'] }}">
                                    <dt class="text-xs font-medium text-slate-600">{{ $status['label'] }}</dt>
                                    <dd class="mt-1 whitespace-nowrap text-sm font-semibold tabular-nums text-slate-800">&#8369;{{ number_format((float) $status['amount'], 2) }}</dd>
                                    @if ($status['status'] === 'for_billing')
                                        <dd class="mt-1 text-xs tabular-nums text-slate-600">{{ $status['percent_of_contract'] === null ? 'N/A' : number_format($status['percent_of_contract'], 2).'%' }} of Contract Price</dd>
                                    @endif
                                    <dd class="mt-1 text-xs tabular-nums text-slate-500">{{ number_format($status['dr_count']) }} {{ $status['dr_count'] === 1 ? 'DR' : 'DRs' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section class="mt-4 border-t border-slate-100 pt-3" aria-label="Completed billing cycles for {{ $lotBilling['lot_name'] }}">
                        <h5 class="text-xs font-bold text-slate-700">Completed Billing Cycles</h5>
                        @if ($lotBilling['history'] === [])
                            <p class="mt-1 text-xs text-slate-500">No completed billing cycles recorded.</p>
                        @else
                            <ol class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-100">
                                @foreach ($lotBilling['history'] as $cycle)
                                    <li class="grid min-w-0 grid-cols-1 gap-2 px-3 py-3 text-xs sm:grid-cols-2 lg:grid-cols-3" data-billing-cycle="{{ $cycle['cycle_type'] }}-{{ $cycle['cycle_identifier'] }}">
                                        <div class="min-w-0">
                                            <p class="break-words font-semibold text-slate-700">{{ $cycle['label'] }}</p>
                                            @if ($cycle['completed_at'])
                                                <p class="mt-1 text-slate-500">{{ \Illuminate\Support\Carbon::parse($cycle['completed_at'], config('app.timezone'))->format('M d, Y') }}</p>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-slate-500">Amount Billed</p>
                                            <p class="mt-1 whitespace-nowrap font-semibold tabular-nums text-slate-700">&#8369;{{ number_format((float) $cycle['amount_billed'], 2) }} <span class="font-normal">&middot; {{ $cycle['percent_billed'] === null ? 'N/A' : number_format($cycle['percent_billed'], 2).'%' }}</span></p>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-slate-500">Not Billed</p>
                                            <p class="mt-1 whitespace-nowrap tabular-nums text-slate-700">&#8369;{{ number_format((float) $cycle['not_billed_amount'], 2) }} &middot; {{ number_format($cycle['not_billed_percent'], 2) }}%</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                </article>
            @endforeach
        </div>
    </section>
@endif
