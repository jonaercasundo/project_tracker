@php
    $variantRows = old('variants', isset($product) ? $product->variants->map(fn ($variant) => $variant->only(['variant_id', 'sequence', 'material', 'color', 'product_length', 'product_width', 'product_height', 'product_depth', 'purchase_price', 'selling_price', 'sku']))->all() : []);
    $variantBaseSku = $product->sku ?? '';
@endphp
<section class="tx-card lvl-2" aria-labelledby="variants_heading">
    <div class="tx-card-head">
        <span class="tx-card-icon">04</span>
        <div><h2 id="variants_heading">Product Variants</h2><p>Each sellable material and color combination has its own SKU, dimensions, and prices.</p></div>
        <button type="button" id="add_product_variant" class="tx-btn-small">+ Add variant</button>
    </div>
    <div class="tx-card-body">
        @if(isset($product))<input type="hidden" name="variants_present" value="1">@endif
        <div class="mb-5 max-w-md">
            <label for="sku" class="tx-label">Base SKU <span class="text-slate-500">(auto-generated)</span></label>
            <input id="sku" value="{{ $variantBaseSku }}" maxlength="255" class="tx-field bg-slate-100 text-slate-600" placeholder="Assigned after the product is saved" readonly aria-readonly="true">
        </div>
        <datalist id="variant_material_options">@foreach($materialOptions as $option)<option value="{{ $option->material_name }}">@endforeach</datalist>
        <datalist id="variant_color_options">@foreach($colorOptions as $option)<option value="{{ $option->color_name }}">@endforeach</datalist>
        <div id="product_variants" class="grid grid-cols-1 gap-4">
            @foreach($variantRows as $index => $variant)
                @include('mi_app.designer_module.partials._product-variant-row', ['index' => $index, 'variant' => $variant, 'baseSku' => $variantBaseSku])
            @endforeach
        </div>
        @foreach($errors->getMessages() as $field => $messages)
            @if(str_starts_with($field, 'variants'))
                @foreach($messages as $message)<p class="tx-error">{{ $message }}</p>@endforeach
            @endif
        @endforeach
        <p class="tx-hint mt-3">New rows receive the next sequence suffix after the product base SKU is generated (for example, -01). You can edit a variant SKU if it remains unique.</p>
    </div>
</section>
<template id="product_variant_template">@include('mi_app.designer_module.partials._product-variant-row', ['index' => '__INDEX__', 'variant' => ['sequence' => '__SEQUENCE__', 'material' => '', 'color' => '', 'sku' => '__SKU__'], 'baseSku' => '__BASE__'])</template>
<script>
(() => {
    const rows = document.getElementById('product_variants');
    const base = document.getElementById('sku');
    const template = document.getElementById('product_variant_template');
    let nextIndex = rows.querySelectorAll('[data-variant-row]').length;
    const refreshAutoSkus = () => rows.querySelectorAll('[data-variant-auto="true"]').forEach((input) => {
        const sequence = Number(input.dataset.sequence);
        input.value = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
    });
    base.addEventListener('input', refreshAutoSkus);
    document.getElementById('add_product_variant').addEventListener('click', () => {
        const sequences = [...rows.querySelectorAll('[data-sequence]')].map((input) => Number(input.dataset.sequence)).filter(Number.isFinite);
        const sequence = Math.max(0, ...sequences) + 1;
        const sku = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).replaceAll('__SEQUENCE__', String(sequence)).replaceAll('__SKU__', sku).replaceAll('__BASE__', base.value.trim());
        rows.insertAdjacentHTML('beforeend', html);
    });
    rows.addEventListener('input', (event) => {
        if (event.target.matches('[data-variant-sku]')) event.target.dataset.variantAuto = 'false';
    });
    rows.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-variant]');
        if (remove) remove.closest('[data-variant-row]').remove();
    });
})();
</script>
