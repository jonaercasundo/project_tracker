<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <header class="border-b-2 border-slate-900 pb-4">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500">Metro · Mobilia Corporation</p>
                    <h1 class="mt-2 text-2xl font-black uppercase tracking-tight text-slate-950 sm:text-3xl">Reports &amp; Analytics</h1>
                    <p class="mt-1 text-sm text-slate-600">Project delivery progress and return monitoring at a glance.</p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated {{ $snapshotAt->format('M d, Y · g:i A') }}</p>
            </div>
        </header>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Project and delivery summary">
            @php
                $summaryCards = [
                    ['label' => 'Projects', 'value' => $totalProjects, 'tone' => 'bg-white'],
                    ['label' => 'Delivery records', 'value' => $totalDeliveries, 'tone' => 'bg-white'],
                    ['label' => 'Completed deliveries', 'value' => $completedDeliveries, 'tone' => 'bg-slate-950 text-white'],
                    ['label' => 'Open returns', 'value' => $openReturns, 'tone' => 'bg-white'],
                ];
            @endphp
            @foreach ($summaryCards as $card)
                <article class="flex min-h-24 items-end justify-between gap-3 border-2 border-slate-900 p-4 {{ $card['tone'] }}">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] opacity-70">{{ $card['label'] }}</p>
                    <p class="text-3xl font-black leading-none tracking-tight">{{ number_format($card['value']) }}</p>
                </article>
            @endforeach
        </section>

        <section class="border-2 border-slate-900 bg-white p-4 sm:p-5" aria-labelledby="delivery-progress-title">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 id="delivery-progress-title" class="text-sm font-black uppercase tracking-[0.16em] text-slate-800">Delivery completion</h2>
                    <p class="mt-1 text-xs text-slate-500">Delivery records marked delivered or accepted.</p>
                </div>
                <p class="text-right text-2xl font-black text-slate-950">{{ number_format($deliveryProgress, 1) }}%</p>
            </div>
            <div class="mt-4 h-4 overflow-hidden border border-slate-900 bg-stone-100" role="progressbar" aria-label="Delivery completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $deliveryProgress }}">
                <div class="h-full bg-amber-500 transition-[width]" style="width: {{ min(100, $deliveryProgress) }}%"></div>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                <span>{{ number_format($completedDeliveries) }} completed</span>
                <span>{{ number_format(max(0, $totalDeliveries - $completedDeliveries)) }} remaining</span>
                <span>{{ number_format($totalDeliveries) }} total</span>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="border-2 border-slate-900 bg-white p-4 sm:p-5" aria-labelledby="delivery-status-title">
                <div class="flex items-end justify-between gap-3 border-b border-stone-200 pb-3">
                    <div>
                        <h2 id="delivery-status-title" class="text-sm font-black uppercase tracking-[0.16em] text-slate-800">Delivery status</h2>
                        <p class="mt-1 text-xs text-slate-500">Distribution of delivery records by current status.</p>
                    </div>
                    <span class="text-xl font-black text-slate-950">{{ number_format($totalDeliveries) }}</span>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse ($deliveryStatuses as $deliveryStatus)
                        @php
                            $statusPercent = $totalDeliveries > 0 ? round(($deliveryStatus->total / $totalDeliveries) * 100, 1) : 0;
                            $statusColor = match (strtolower($deliveryStatus->status)) {
                                'delivered', 'accepted' => 'bg-emerald-600',
                                'released' => 'bg-blue-600',
                                'cancelled' => 'bg-rose-600',
                                default => 'bg-amber-500',
                            };
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                                <span class="font-bold capitalize text-slate-700">{{ str_replace('_', ' ', $deliveryStatus->status) }}</span>
                                <span class="font-black tabular-nums text-slate-900">{{ number_format($deliveryStatus->total) }} <span class="font-medium text-slate-500">· {{ number_format($statusPercent, 1) }}%</span></span>
                            </div>
                            <div class="h-2.5 bg-stone-100">
                                <div class="h-2.5 {{ $statusColor }}" style="width: {{ min(100, $statusPercent) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No delivery records to report yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="border-2 border-slate-900 bg-white p-4 sm:p-5" aria-labelledby="returns-title">
                <div class="flex items-end justify-between gap-3 border-b border-stone-200 pb-3">
                    <div>
                        <h2 id="returns-title" class="text-sm font-black uppercase tracking-[0.16em] text-slate-800">Returns by type</h2>
                        <p class="mt-1 text-xs text-slate-500">Received counts reflect confirmed physical receipt.</p>
                    </div>
                    <span class="text-right text-xs font-black text-slate-800">{{ number_format($returnSummary->sum('total')) }} returns logged</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (['Item Return', 'Document Return'] as $returnType)
                        @php($summary = $returnSummary->get($returnType))
                        <article class="border border-stone-300 bg-stone-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">{{ $returnType }}</h3>
                                    <p class="mt-1 text-[10px] text-slate-500">{{ number_format($summary['total']) }} recorded</p>
                                </div>
                                <div class="grid h-16 w-16 shrink-0 place-items-center rounded-full" style="background: conic-gradient(#d99a27 {{ min(100, $summary['received_percent']) }}%, #e7e5e4 0%)">
                                    <div class="grid h-12 w-12 place-items-center rounded-full bg-white text-center">
                                        <span class="text-xs font-black leading-none text-slate-900">{{ number_format($summary['received_percent'], 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2 border-t border-stone-200 pt-3 text-[10px]">
                                <div><p class="font-bold uppercase tracking-wider text-slate-500">Received</p><p class="mt-0.5 text-base font-black text-emerald-700">{{ number_format($summary['received']) }}</p></div>
                                <div><p class="font-bold uppercase tracking-wider text-slate-500">Open</p><p class="mt-0.5 text-base font-black text-amber-700">{{ number_format($summary['open']) }}</p></div>
                                <div><p class="font-bold uppercase tracking-wider text-slate-500">Rejected</p><p class="mt-0.5 text-base font-black text-rose-700">{{ number_format($summary['rejected']) }}</p></div>
                            </div>
                            @if ($returnType === 'Item Return')
                                <p class="mt-3 border-t border-stone-200 pt-2 text-[10px] font-semibold text-slate-600">{{ number_format($summary['quantity_received']) }} items added after receipt confirmation</p>
                            @else
                                <p class="mt-3 border-t border-stone-200 pt-2 text-[10px] font-semibold text-slate-600">Document returns are tracked separately from inventory.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

    </div>
</x-project_app-layout>
