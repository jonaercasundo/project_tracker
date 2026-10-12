<div class="rounded-lg border border-slate-200 bg-white">
    <table class="block w-full text-left text-xs 2xl:table"><caption class="sr-only">{{ $legacy ? 'Existing lot items' : 'Key stage items' }}</caption><thead class="hidden 2xl:table-header-group border-b border-slate-200 bg-slate-50 text-slate-500"><tr><th class="w-72 px-3 py-2 font-semibold">Catalog item / description</th><th class="w-20 px-2 py-2 font-semibold">Unit</th><th class="w-24 px-2 py-2 font-semibold">Quantity</th><th class="w-28 px-2 py-2 font-semibold">Unit cost (PHP)</th><th class="w-32 px-2 py-2 font-semibold">Amount (PHP)</th><th class="w-28 px-2 py-2 font-semibold">Brand / specs</th><th class="w-36 px-2 py-2 font-semibold">Remarks</th><th class="w-16 px-2 py-2"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody data-bidding-items data-collection="items" data-name-prefix="{{ $itemsPrefix }}" class="block divide-y divide-slate-100 2xl:table-row-group">
            @foreach($items as $itemIndex => $item)
            @continue(!is_array($item))
                @include('operation.bidding.partials._items', ['index' => $itemIndex, 'item' => is_array($item) ? $item : [], 'legacy' => $legacy, 'namePrefix' => $itemsPrefix.'['.$itemIndex.']', 'dotPrefix' => $itemsDot.'.'.$itemIndex, 'uid' => $itemsUid.'-'.$itemIndex])
            @endforeach
        </tbody>
    </table>
</div>
