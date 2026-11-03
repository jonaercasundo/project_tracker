@if($product->variants->isNotEmpty())
    <section class="tx-card lvl-2 mt-6" aria-labelledby="variant_stock_heading">
        <div class="tx-card-head"><span class="tx-card-icon">06</span><div><h2 id="variant_stock_heading">Variant Inventory</h2><p>Stock quantities and movements are recorded separately for every variant and warehouse.</p></div></div>
        <div class="tx-card-body grid grid-cols-1 gap-4">
            @foreach($product->variants as $variant)
                <article class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold">{{ $variant->sku }} <span class="font-normal text-slate-500">{{ $variant->material }}{{ $variant->color ? ' / '.$variant->color : '' }}</span></h3>
                    <p class="mt-1 text-sm text-slate-600">@forelse($variant->inventories as $inventory){{ $inventory->warehouse->warehouse_name }}: {{ number_format($inventory->quantity) }}@if(!$loop->last) · @endif @empty No stock recorded yet @endforelse</p>
                    <form action="{{ route('mi_app.variants.stock-movements.store', $variant) }}" method="POST" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                        @csrf
                        <select name="warehouse_id" required class="tx-field"><option value="">Select warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>@endforeach</select>
                        <select name="movement_type" required class="tx-field"><option value="stock_in">Stock in</option><option value="stock_out">Stock out</option></select>
                        <input name="quantity" type="number" min="1" max="100000000" required class="tx-field" placeholder="Quantity">
                        <button type="submit" class="tx-btn-small">Record movement</button>
                        <input name="remarks" maxlength="500" class="tx-field md:col-span-4" placeholder="Optional movement notes">
                    </form>
                </article>
            @endforeach
        </div>
    </section>
@endif
