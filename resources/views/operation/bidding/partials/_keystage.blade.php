@php
    $stageData = is_array($stage) ? $stage : $stage->toArray();
    $stageItems = is_array($stageData['items'] ?? null) ? $stageData['items'] : [];
@endphp
<section data-bidding-stage data-entry-index="{{ $index }}" data-name-prefix="{{ $namePrefix }}" class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
    <div class="flex flex-wrap items-end justify-between gap-3"><div class="flex min-w-0 flex-1 flex-col gap-1.5"><label for="{{ $uid }}-name" class="text-xs font-semibold text-slate-600">Key stage name</label><input id="{{ $uid }}-name" name="{{ $namePrefix }}[name]" value="{{ $fieldValue($dotPrefix.'.name', $stageData['name'] ?? '') }}" required maxlength="255" class="max-w-md rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter key stage"><x-input-error :messages="$errors->get($dotPrefix.'.name')" class="text-xs" /></div><button type="button" data-bidding-action="remove-stage" class="rounded-lg px-2 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove key stage</button></div>
    @if(!empty($stageData['id']) && is_scalar($stageData['id']))<input type="hidden" name="{{ $namePrefix }}[id]" value="{{ $stageData['id'] }}">@endif
    <input type="hidden" name="{{ $namePrefix }}[items_present]" value="1">
    @include('operation.bidding.partials._item_table', ['items' => $stageItems, 'legacy' => false, 'itemsPrefix' => $namePrefix.'[items]', 'itemsDot' => $dotPrefix.'.items', 'itemsUid' => $uid.'-item'])
    <button type="button" data-bidding-action="add-item" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Add item</button>
</section>
