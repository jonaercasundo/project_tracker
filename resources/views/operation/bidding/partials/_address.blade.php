@php
    $addressData = is_array($address) ? $address : $address->toArray();
    $stages = is_array($addressData['keystages'] ?? null) ? $addressData['keystages'] : [];
@endphp
<section data-bidding-address data-entry-index="{{ $index }}" data-name-prefix="{{ $namePrefix }}" class="space-y-3 rounded-lg border border-slate-200 border-l-4 border-l-blue-200 p-4">
    <div class="flex items-center justify-between gap-3"><label for="{{ $uid }}-destination" class="text-xs font-semibold text-slate-700">Delivery address</label><button type="button" data-bidding-action="remove-address" class="text-xs font-semibold text-red-600 hover:underline">Remove address</button></div>
    @if(!empty($addressData['id']) && is_scalar($addressData['id']))<input type="hidden" name="{{ $namePrefix }}[id]" value="{{ $addressData['id'] }}">@endif
    <input type="hidden" name="{{ $namePrefix }}[keystages_present]" value="1">
    <textarea id="{{ $uid }}-destination" name="{{ $namePrefix }}[delivery_address]" rows="2" required maxlength="2000" class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="School, building or complete delivery destination">{{ $fieldValue($dotPrefix.'.delivery_address', $addressData['delivery_address'] ?? '') }}</textarea>
    <x-input-error :messages="$errors->get($dotPrefix.'.delivery_address')" class="text-xs" />
    <div data-bidding-stages data-collection="keystages" data-name-prefix="{{ $namePrefix }}[keystages]" class="space-y-3">
        @foreach($stages as $stageIndex => $stage)
            @continue(!is_array($stage))
            @include('operation.bidding.partials._keystage', ['index' => $stageIndex, 'stage' => is_array($stage) ? $stage : [], 'namePrefix' => $namePrefix.'[keystages]['.$stageIndex.']', 'dotPrefix' => $dotPrefix.'.keystages.'.$stageIndex, 'uid' => $uid.'-stage-'.$stageIndex])
        @endforeach
    </div>
    <button type="button" data-bidding-action="add-stage" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">+ Add key stage</button>
</section>
