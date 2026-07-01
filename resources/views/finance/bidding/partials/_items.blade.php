{{-- ============================================================
     finance/bidding/partials/_items.blade.php
     Variables: $lotIndex, $itemIndex, $item (array|Model)
     ============================================================ --}}

@php
    $itemData = is_array($item) ? $item : $item->toArray();
    $desc     = old("lots.{$lotIndex}.items.{$itemIndex}.item_description", $itemData['item_description'] ?? '');
    $unit     = old("lots.{$lotIndex}.items.{$itemIndex}.unit",             $itemData['unit'] ?? '');
    $qty      = old("lots.{$lotIndex}.items.{$itemIndex}.quantity",         $itemData['quantity'] ?? '');
    $unitCost = old("lots.{$lotIndex}.items.{$itemIndex}.unit_cost",        $itemData['unit_cost'] ?? '');
    $brand    = old("lots.{$lotIndex}.items.{$itemIndex}.brand",            $itemData['brand'] ?? '');
    $remarks  = old("lots.{$lotIndex}.items.{$itemIndex}.remarks",          $itemData['remarks'] ?? '');
@endphp

<div class="bf-item-row flex flex-wrap items-center gap-2 py-2">

    {{-- Item Description --}}
    <select
        class="item-select text-sm rounded-lg border-slate-200 py-1.5
               focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][item_description]">

        <option value="">Select Item</option>

        @foreach ($catalogItems as $catalogItem)
            <option
                value="{{ $catalogItem->description }}"
                data-unit="{{ $catalogItem->unit }}"
                data-price="{{ $catalogItem->price }}"
                {{ $desc == $catalogItem->description ? 'selected' : '' }}>
                {{ $catalogItem->item_name }}
            </option>
        @endforeach
    </select>

    {{-- Unit --}}
    <input
        type="text"
        class="unit-input w-20 text-sm rounded-lg border-slate-200 py-1.5 px-2.5
               placeholder:text-slate-400
               focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit]"
        value="{{ $unit }}"
        placeholder="Unit">

    {{-- Quantity --}}
    <input
        type="number"
        class="qty w-16 text-sm rounded-lg border-slate-200 py-1.5 px-2.5
               placeholder:text-slate-400
               focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][quantity]"
        value="{{ $qty }}"
        placeholder="0"
        min="0">

    {{-- Unit Cost --}}
    <div class="flex items-center gap-2 w-28">
        <span class="text-slate-400 text-xs">₱</span>

        <input
            type="number"
            class="unit-cost w-full text-sm rounded-lg border-slate-200 py-1.5 px-2
                placeholder:text-slate-400
                focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
            step="0.01"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit_cost]"
            value="{{ $unitCost }}"
            placeholder="0.00"
            min="0"
            readonly
        >
    </div>

    {{-- Amount (computed, readonly) --}}
    <div class="flex items-center gap-2 w-28">
        <span class="text-slate-400 text-xs">₱</span>
        <input
            type="text"
            class="item-amount w-full text-sm rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-10 pr-2
                   text-slate-600 cursor-not-allowed"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][total_amount]"
            value="0.00"
            readonly>
    </div>

    {{-- Brand --}}
    <input
        type="text"
        class="w-28 text-sm rounded-lg border-slate-200 py-1.5 px-2.5
               placeholder:text-slate-400
               focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][brand]"
        value="{{ $brand }}"
        placeholder="Brand">

    {{-- Remarks --}}
    <input
        type="text"
        class="flex-1 min-w-[140px] text-sm rounded-lg border-slate-200 py-1.5 px-2.5
               placeholder:text-slate-400
               focus:ring-1 focus:ring-slate-400 focus:border-slate-400"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][remarks]"
        value="{{ $remarks }}"
        placeholder="Remarks">

    {{-- Delete --}}
    <button
        type="button"
        class="bf-lot-del p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0"
        aria-label="Remove item"
        onclick="this.closest('.bf-item-row').remove()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </button>

</div>