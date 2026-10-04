@php
    $formatBiddingMoney = static function (mixed $amount): string {
        $parts = explode('.', (string) ($amount ?? '0'), 2);
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $parts[0]);
        return $whole.'.'.str_pad(substr($parts[1] ?? '', 0, 2), 2, '0');
    };
@endphp
<div class="space-y-5">
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-semibold text-slate-900">{{ $project->project_name }}</h2>@include('operation.bidding.partials._status-badge', ['status' => $project->status])</div>
        <dl class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(['project_code' => 'Project code', 'project_id' => 'Project ID', 'procuring_entity' => 'Procuring entity / agency', 'delivery_period' => 'Delivery period (calendar days)', 'pre_bid_conf' => 'Pre-bid conference', 'date_of_bid_opening' => 'Bid opening'] as $key => $label)<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $project->{$key} instanceof \DateTimeInterface ? $project->{$key}->format('Y-m-d') : ($project->{$key} ?? 'Not provided') }}</dd></div>@endforeach
        </dl>
        <div class="grid grid-cols-1 gap-4 border-t border-slate-100 bg-slate-50 p-5 sm:grid-cols-2"><div><p class="text-xs text-slate-500">Approved Budget for the Contract</p><p class="mt-1 font-mono text-xl font-semibold tabular-nums text-slate-900">PHP {{ $formatBiddingMoney($project->approved_budget_contract_abc) }}</p></div><div><p class="text-xs text-slate-500">Calculated item total</p><p class="mt-1 font-mono text-xl font-semibold tabular-nums text-blue-700">PHP {{ $formatBiddingMoney($calculatedTotal ?? $project->calculated_total ?? '0.00') }}</p></div></div>
    </section>
    @forelse($project->lots as $lot)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3"><h2 class="text-sm font-semibold text-slate-900">{{ $lot->lot_no }}</h2><p class="font-mono text-sm font-semibold tabular-nums text-slate-700">PHP {{ $formatBiddingMoney($lot->calculated_item_total ?? '0.00') }}</p></div>
            <div class="space-y-4 p-5"><p class="text-xs text-slate-500">{{ collect([$lot->country, $lot->region, $lot->province, $lot->city_municipality, $lot->barangay])->filter()->implode(' / ') ?: 'Location not provided' }}</p>
                @if($lot->delivery_address)<p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Existing lot delivery address: {{ $lot->delivery_address }}</p>@endif
                @foreach($lot->addresses as $address)
                    <div class="space-y-3 rounded-lg border border-slate-200 border-l-4 border-l-blue-200 p-4"><h3 class="text-sm font-semibold text-slate-800">{{ $address->delivery_address }}</h3>
                        @forelse($address->keystages as $stage)<section class="space-y-2"><h4 class="text-xs font-semibold text-slate-600">{{ $stage->name }}</h4>@include('operation.bidding.partials._show_items', ['displayItems' => $stage->items])</section>@empty<p class="text-xs text-slate-500">No key stages added.</p>@endforelse
                    </div>
                @endforeach
                @if($lot->legacyItems->isNotEmpty())<section class="space-y-2"><h3 class="text-xs font-semibold text-amber-800">Existing items without an address or key stage</h3>@include('operation.bidding.partials._show_items', ['displayItems' => $lot->legacyItems])</section>@endif
                @if($lot->addresses->isEmpty() && $lot->legacyItems->isEmpty())<p class="text-sm text-slate-500">No delivery addresses or items added.</p>@endif
            </div>
        </section>
    @empty<p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">No lots added.</p>@endforelse
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-sm font-semibold text-slate-900">Document control</h2><dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">@foreach(['prepared_by' => 'Prepared by', 'prepared_date' => 'Prepared date', 'verified_by' => 'Verified by'] as $key => $label)<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 text-sm text-slate-700">{{ $project->{$key} instanceof \DateTimeInterface ? $project->{$key}->format('Y-m-d') : ($project->{$key} ?? 'Not provided') }}</dd></div>@endforeach</dl>@if($project->notes_special_condition)<p class="mt-4 whitespace-pre-wrap border-t border-slate-100 pt-4 text-sm text-slate-600">{{ $project->notes_special_condition }}</p>@endif</section>
</div>
