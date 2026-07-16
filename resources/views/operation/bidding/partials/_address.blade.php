<div class="bf-address"
     data-address="{{ $addressIndex }}">

    <label>
        Delivery Address
    </label>

    <textarea
        name="lots[{{ $lotIndex }}][addresses][{{ $addressIndex }}][delivery_address]"
        class="bf-input">

        {{ $address['delivery_address'] ?? '' }}

    </textarea>


    <div class="keystages">

        @foreach($address['keystages'] ?? [] as $stageIndex => $stage)

            @include('operation.bidding.partials._keystage', [
                'lotIndex'=>$lotIndex,
                'addressIndex'=>$addressIndex,
                'stageIndex'=>$stageIndex,
                'stage'=>$stage
            ])

        @endforeach

    </div>


    <button type="button"
        onclick="addKeystage(this)">
        Add Key Stage
    </button>

</div>