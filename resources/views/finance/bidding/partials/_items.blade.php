{{-- ============================================================
     finance/bidding/partials/_items.blade.php
     Variables: $lotIndex, $itemIndex, $item (array|Model)
     ============================================================ --}}

@php
    $itemData   = is_array($item) ? $item : $item->toArray();
    $desc       = old("lots.{$lotIndex}.items.{$itemIndex}.item_description",  $itemData['item_description']  ?? '');
    $unit       = old("lots.{$lotIndex}.items.{$itemIndex}.unit",              $itemData['unit']              ?? '');
    $qty        = old("lots.{$lotIndex}.items.{$itemIndex}.quantity",          $itemData['quantity']          ?? '');
    $unitCost   = old("lots.{$lotIndex}.items.{$itemIndex}.unit_cost",         $itemData['unit_cost']         ?? '');
    $brand       = old("lots.{$lotIndex}.items.{$itemIndex}.brand",              $itemData['brand']              ?? '');
    $remarks       = old("lots.{$lotIndex}.items.{$itemIndex}.remarks",              $itemData['remarks']              ?? '');
@endphp

<div class="bf-item-row">
@dd($items);
<select
    name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][item_description]"
    class="w-full rounded-md border-gray-300">

    <option value="">Select Item</option>

    @foreach ($catalogItems as $catalogItem)
        <option
            value="{{ $catalogItem->description }}"
            {{ $desc == $catalogItem->description ? 'selected' : '' }}>
            {{ $catalogItem->item_name }}
        </option>
    @endforeach
</select>

    <input type="text"
           name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit]"
           value="{{ $unit }}"
           placeholder="laptop">

    <input type="number"
           class="qty"
           name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][quantity]"
           value="{{ $qty }}"
           placeholder="0"
           min="0">

        <input type="number"
           class="unit-cost"
           step="0.01"
           name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit_cost]"
           value="{{ $unitCost }}"
           placeholder="0.00"
           min="0">
        <input
            type="text"
            class="item-amount"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][total_amount]"
            value="0.00"
            readonly>
        <input type="text"
           name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][brand]"
           value="{{ $brand }}"
           placeholder="lenovo">
        <input type="text"
           name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][remarks]"
           value="{{ $remarks }}"
           placeholder="Remarks">
        <button
            type="button"
            class="bf-lot-del"
            aria-label="Remove item"
            onclick="this.closest('.bf-item-row').remove()">
            <i class="ti ti-trash" aria-hidden="true">X</i>
        </button>

</div>