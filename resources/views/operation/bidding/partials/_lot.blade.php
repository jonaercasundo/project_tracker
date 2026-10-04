@php
    $lotData = is_array($lot) ? $lot : $lot->toArray();
    $addresses = is_array($lotData['addresses'] ?? null) ? $lotData['addresses'] : [];
    $legacyItems = is_array($lotData['legacy_items'] ?? null) ? $lotData['legacy_items'] : [];
@endphp
<div data-bidding-lot data-entry-index="{{ $index }}" data-name-prefix="{{ $namePrefix }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3">
        <div class="flex min-w-0 items-center gap-3"><label for="{{ $uid }}-number" class="shrink-0 text-xs font-semibold text-slate-600">Lot number</label><input id="{{ $uid }}-number" name="{{ $namePrefix }}[lot_no]" value="{{ $fieldValue($dotPrefix.'.lot_no', $lotData['lot_no'] ?? '') }}" required maxlength="50" data-lot-number class="w-40 rounded-lg border-slate-200 bg-white py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500"></div>
        <button type="button" data-bidding-action="remove-lot" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove lot</button>
    </div>
    <div class="space-y-5 p-5">
        @if(!empty($lotData['id']) && is_scalar($lotData['id']))<input type="hidden" name="{{ $namePrefix }}[id]" value="{{ $lotData['id'] }}">@endif
        <input type="hidden" name="{{ $namePrefix }}[addresses_present]" value="1"><input type="hidden" name="{{ $namePrefix }}[legacy_items_present]" value="1">
        <x-input-error :messages="$errors->get($dotPrefix.'.lot_no')" class="text-xs" />
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="flex flex-col gap-1.5"><label for="{{ $uid }}-country" class="text-xs font-semibold text-slate-600">Country</label><select id="{{ $uid }}-country" name="{{ $namePrefix }}[country_code]" class="rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="PH">Philippines</option></select></div>
            @foreach(['region' => 'Region', 'province' => 'Province', 'city' => 'City / municipality', 'barangay' => 'Barangay'] as $level => $label)
                @php($selectedCode = $fieldValue($dotPrefix.'.'.$level.'_code', $lotData[$level.'_code'] ?? ''))
                <div class="flex flex-col gap-1.5"><label for="{{ $uid }}-{{ $level }}" class="text-xs font-semibold text-slate-600">{{ $label }}</label><select id="{{ $uid }}-{{ $level }}" name="{{ $namePrefix }}[{{ $level }}_code]" data-location="{{ $level }}" data-selected="{{ $selectedCode }}" @disabled($level !== 'region' && $selectedCode === '') class="rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400"><option value="">Select {{ strtolower($label) }}</option>@if($selectedCode !== '')<option value="{{ $selectedCode }}" selected>{{ $lotData[$level] ?? $selectedCode }}</option>@endif</select><x-input-error :messages="$errors->get($dotPrefix.'.'.$level.'_code')" class="text-xs" /></div>
            @endforeach
        </div>
        @if(!empty($lotData['legacy_location']) || !empty($lotData['legacy_delivery_address']))
            <div class="space-y-1 rounded-lg bg-amber-50 p-3 text-xs text-amber-900">
                @if(is_scalar($lotData['legacy_location'] ?? null) && $lotData['legacy_location'] !== '')<p>Saved location: {{ $lotData['legacy_location'] }}</p>@endif
                @if(is_scalar($lotData['legacy_delivery_address'] ?? null) && $lotData['legacy_delivery_address'] !== '')<p>Saved delivery address: {{ $lotData['legacy_delivery_address'] }}</p>@endif
                <p>Existing location labels stay available when no replacement location is selected.</p>
            </div>
        @endif
        <div data-location-message role="status" aria-live="polite" class="flex items-center gap-2 text-xs text-red-700"><span></span><button type="button" data-bidding-action="retry-locations" hidden class="font-semibold underline">Retry locations</button></div>
        <div data-bidding-addresses data-collection="addresses" data-name-prefix="{{ $namePrefix }}[addresses]" class="space-y-4">
            @foreach($addresses as $addressIndex => $address)
            @continue(!is_array($address))
                @include('operation.bidding.partials._address', ['index' => $addressIndex, 'address' => is_array($address) ? $address : [], 'namePrefix' => $namePrefix.'[addresses]['.$addressIndex.']', 'dotPrefix' => $dotPrefix.'.addresses.'.$addressIndex, 'uid' => $uid.'-address-'.$addressIndex])
            @endforeach
        </div>
        <button type="button" data-bidding-action="add-address" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">+ Add delivery address</button>
        @if(count($legacyItems))
            <section class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-4"><div><h3 class="text-xs font-semibold text-amber-900">Existing lot items</h3><p class="mt-1 text-xs text-amber-800">These saved items have no address or key stage. Their existing values remain here until you change them. Add a unit cost before changing a quantity when its price is missing.</p></div>
                @include('operation.bidding.partials._item_table', ['items' => $legacyItems, 'legacy' => true, 'itemsPrefix' => $namePrefix.'[legacy_items]', 'itemsDot' => $dotPrefix.'.legacy_items', 'itemsUid' => $uid.'-legacy'])
            </section>
        @endif
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 text-sm"><span class="text-slate-500">Calculated lot total</span><strong class="font-mono tabular-nums text-slate-900">PHP <span data-bidding-lot-total>0.00</span></strong></div>
    </div>
</div>
