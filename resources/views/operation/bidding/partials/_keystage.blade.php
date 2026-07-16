<div class="bf-keystage">

    <input type="text"
        name="lots[{{ $lotIndex }}][addresses][{{ $addressIndex }}][keystages][{{ $stageIndex }}][name]"
        value="{{ $stage['name'] ?? '' }}"
        placeholder="Key Stage">


    <div class="items">

        @foreach($stage['items'] ?? [] as $itemIndex=>$item)

            @include('operation.bidding.partials._items', [
                'lotIndex'=>$lotIndex,
                'addressIndex'=>$addressIndex,
                'stageIndex'=>$stageIndex,
                'itemIndex'=>$itemIndex,
                'item'=>$item
            ])

        @endforeach

    </div>


    <button type="button"
        onclick="addItem(this)">
        Add Item
    </button>

</div>