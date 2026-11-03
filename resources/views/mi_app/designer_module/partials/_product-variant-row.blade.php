@php
    $variantId = $variant['variant_id'] ?? $variant['id'] ?? null;
    $sequence = $variant['sequence'] ?? 1;
    $variantSku = $variant['sku'] ?? '';
    $isAutoSku = $variantSku === '__SKU__' || ($baseSku !== '' && str_starts_with($variantSku, $baseSku.'-'));
    $fieldId = 'variant_'.$index;
    $materials = $variant['materials'] ?? array_filter([(string) ($variant['material'] ?? '')]);
    $colors = $variant['colors'] ?? array_filter([(string) ($variant['color'] ?? '')]);
    $materials = is_array($materials) ? $materials : [$materials];
    $colors = is_array($colors) ? $colors : [$colors];
@endphp
<article data-variant-row class="tx-subpanel">
    @if($variantId)<input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variantId }}">@endif
    <input type="hidden" name="variants[{{ $index }}][sequence]" value="{{ $sequence }}" data-sequence="{{ $sequence }}">

    <div class="tx-subpanel-head tx-variant-head">
        <div>
            <h3>Variant {{ str_pad((string) $sequence, 2, '0', STR_PAD_LEFT) }}</h3>
            <p>This variant can have several materials and colors with its own dimensions and pricing</p>
        </div>
        <button type="button" data-remove-variant class="tx-btn-small">Remove</button>
    </div>

    <div class="tx-variant-grid">
        <div class="md:col-span-2">
            <label for="{{ $fieldId }}_materials" class="tx-label">Materials <span class="tx-required">*</span></label>
            <div class="tx-multi-select-wrap">
                <div class="tx-multi-toolbar">
                    <span class="tx-multi-hint">Select multiple or type to add a material</span>
                    <button type="button" class="tx-multi-clear" hidden>Clear</button>
                </div>
                <select id="{{ $fieldId }}_materials" required multiple name="variants[{{ $index }}][materials][]" class="tx-field variant-dropdown" data-placeholder="Search or add materials...">
                    @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'materials', 'selectedValues' => $materials])
                </select>
            </div>
        </div>

        <div class="md:col-span-2">
            <label for="{{ $fieldId }}_colors" class="tx-label">Colors</label>
            <div class="tx-multi-select-wrap">
                <div class="tx-multi-toolbar">
                    <span class="tx-multi-hint">Select multiple or type to add a color</span>
                    <button type="button" class="tx-multi-clear" hidden>Clear</button>
                </div>
                <select id="{{ $fieldId }}_colors" multiple name="variants[{{ $index }}][colors][]" class="tx-field variant-dropdown" data-placeholder="Search or add colors...">
                    @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'color', 'selectedValues' => $colors])
                </select>
            </div>
        </div>

        @foreach(['product_length' => 'Length (cm)', 'product_width' => 'Width (cm)', 'product_height' => 'Height (cm)', 'product_depth' => 'Depth (cm)'] as $field => $label)
            <div>
                <label for="{{ $fieldId }}_{{ $field }}" class="tx-label">{{ $label }}</label>
                <input id="{{ $fieldId }}_{{ $field }}" type="number" min="0" step="0.01" name="variants[{{ $index }}][{{ $field }}]" value="{{ $variant[$field] ?? '' }}" class="tx-field" placeholder="0.00">
            </div>
        @endforeach

        <div>
            <label for="{{ $fieldId }}_purchase_price" class="tx-label">Purchase price</label>
            <input id="{{ $fieldId }}_purchase_price" type="number" min="0" step="0.01" name="variants[{{ $index }}][purchase_price]" value="{{ $variant['purchase_price'] ?? '' }}" class="tx-field" placeholder="0.00">
        </div>

        <div>
            <label for="{{ $fieldId }}_selling_price" class="tx-label">Selling price</label>
            <input id="{{ $fieldId }}_selling_price" type="number" min="0" step="0.01" name="variants[{{ $index }}][selling_price]" value="{{ $variant['selling_price'] ?? '' }}" class="tx-field" placeholder="0.00">
        </div>

        <div class="md:col-span-2">
            <label for="{{ $fieldId }}_sku" class="tx-label">Variant SKU</label>
            <div class="tx-variant-sku-row">
                <input id="{{ $fieldId }}_sku" name="variants[{{ $index }}][sku]" value="{{ $variantSku }}" data-variant-sku data-variant-auto="{{ $isAutoSku ? 'true' : 'false' }}" data-sequence="{{ $sequence }}" class="tx-field" placeholder="Generated after saving" readonly aria-readonly="true">
                <button type="button" data-edit-variant-sku class="tx-btn-small">Edit SKU</button>
            </div>
        </div>
    </div>
</article>
