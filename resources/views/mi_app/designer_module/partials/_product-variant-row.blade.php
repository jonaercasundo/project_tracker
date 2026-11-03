@php
    $variantId = $variant['variant_id'] ?? $variant['id'] ?? null;
    $sequence = $variant['sequence'] ?? 1;
    $variantSku = $variant['sku'] ?? '';
    $isAutoSku = $variantSku === '__SKU__' || ($baseSku !== '' && str_starts_with($variantSku, $baseSku.'-'));
@endphp
<article data-variant-row class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    @if($variantId)<input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variantId }}">@endif
    <input type="hidden" name="variants[{{ $index }}][sequence]" value="{{ $sequence }}" data-sequence="{{ $sequence }}">
    <div class="mb-3 flex items-center justify-between gap-3"><h3 class="font-semibold text-slate-800">Variant {{ str_pad((string) $sequence, 2, '0', STR_PAD_LEFT) }}</h3><button type="button" data-remove-variant class="tx-btn-small">Remove</button></div>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm font-medium text-slate-700">Material
            <select required name="variants[{{ $index }}][material]" class="tx-field mt-1 variant-dropdown" data-placeholder="Select material">
                <option value="">Select material</option>
                @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'materials', 'selectedValues' => array_filter([(string) ($variant['material'] ?? '')])])
            </select>
        </label>
        <label class="text-sm font-medium text-slate-700">Color
            <select name="variants[{{ $index }}][color]" class="tx-field mt-1 variant-dropdown" data-placeholder="Select color">
                <option value="">Select color</option>
                @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'color', 'selectedValues' => array_filter([(string) ($variant['color'] ?? '')])])
            </select>
        </label>
        @foreach(['product_length' => 'Length (cm)', 'product_width' => 'Width (cm)', 'product_height' => 'Height (cm)', 'product_depth' => 'Depth (cm)'] as $field => $label)
            <label class="text-sm font-medium text-slate-700">{{ $label }}<input type="number" min="0" step="0.01" name="variants[{{ $index }}][{{ $field }}]" value="{{ $variant[$field] ?? '' }}" class="tx-field mt-1"></label>
        @endforeach
        <label class="text-sm font-medium text-slate-700">Purchase price<input type="number" min="0" step="0.01" name="variants[{{ $index }}][purchase_price]" value="{{ $variant['purchase_price'] ?? '' }}" class="tx-field mt-1"></label>
        <label class="text-sm font-medium text-slate-700">Selling price<input type="number" min="0" step="0.01" name="variants[{{ $index }}][selling_price]" value="{{ $variant['selling_price'] ?? '' }}" class="tx-field mt-1"></label>
        <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700">Variant SKU<input name="variants[{{ $index }}][sku]" value="{{ $variantSku }}" data-variant-sku data-variant-auto="{{ $isAutoSku ? 'true' : 'false' }}" data-sequence="{{ $sequence }}" class="tx-field mt-1" placeholder="Generated after saving" readonly aria-readonly="true"></label>
            <button type="button" data-edit-variant-sku class="tx-btn-small mt-2">Edit SKU</button>
        </div>
    </div>
</article>
