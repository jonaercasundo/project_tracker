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
        <div id="product_variants">
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
    const initializedDropdowns = new WeakSet();
    const initializeDropdowns = (root) => {
        root.querySelectorAll('.variant-dropdown, .legacy-attribute-select').forEach((select) => {
            if (initializedDropdowns.has(select) || select.tomselect || !window.TomSelect) return;
            initializedDropdowns.add(select);
            const isMultiple = select.multiple;
            const normalize = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
            new TomSelect(select, {
                plugins: ['remove_button'],
                createOnBlur: false,
                persist: false,
                create: (input) => {
                    const value = input.trim().replace(/\s+/g, ' ');
                    return value ? { value, text: value } : false;
                },
                createFilter: (input) => {
                    const normalizedInput = normalize(input);
                    return normalizedInput !== '' && !Array.from(select.options).some((option) => normalize(option.value) === normalizedInput);
                },
                allowEmptyOption: isMultiple,
                maxItems: isMultiple ? 100 : 1,
                hideSelected: true,
                closeAfterSelect: false,
                dropdownParent: 'body',
                placeholder: select.dataset.placeholder,
                searchField: ['text'],
                onInitialize: function () {
                    const selectControl = this;
                    this.wrapper.classList.add('tx-variant-select');
                    this.dropdown_content.addEventListener('click', (event) => {
                        const target = event.target;
                        if (!(target instanceof Element) || !target.closest('.create')) return;

                        event.preventDefault();
                        event.stopPropagation();
                        selectControl.createItem();
                    });
                },
                render: {
                    option_create: (data, escape) => `<div class="create">+ Add "${escape(data.input.trim().replace(/\s+/g, ' '))}"</div>`,
                    no_results: () => '<div class="no-results">No saved option found. Type a new value to add it.</div>',
                },
            });
            const clearButton = select.closest('.tx-multi-select-wrap')?.querySelector('.tx-multi-clear');
            const syncClearButton = () => {
                if (clearButton) clearButton.hidden = select.tomselect.items.length === 0;
            };
            select.tomselect?.on('item_add item_remove clear', syncClearButton);
            clearButton?.addEventListener('click', (event) => {
                event.preventDefault();
                select.tomselect?.clear();
            });
            syncClearButton();
        });
    };
    const refreshAutoSkus = () => rows.querySelectorAll('[data-variant-auto="true"]').forEach((input) => {
        const sequence = Number(input.dataset.sequence);
        input.value = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
    });
    base.addEventListener('input', refreshAutoSkus);
    document.addEventListener('DOMContentLoaded', () => initializeDropdowns(document));
    document.getElementById('add_product_variant').addEventListener('click', () => {
        const sequences = [...rows.querySelectorAll('[data-sequence]')].map((input) => Number(input.dataset.sequence)).filter(Number.isFinite);
        const sequence = Math.max(0, ...sequences) + 1;
        const sku = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).replaceAll('__SEQUENCE__', String(sequence)).replaceAll('__SKU__', sku).replaceAll('__BASE__', base.value.trim());
        rows.insertAdjacentHTML('beforeend', html);
        initializeDropdowns(rows.lastElementChild);
    });
    rows.addEventListener('input', (event) => {
        if (event.target.matches('[data-variant-sku]')) event.target.dataset.variantAuto = 'false';
    });
    rows.addEventListener('click', (event) => {
        const editSku = event.target.closest('[data-edit-variant-sku]');
        if (editSku) {
            const input = editSku.parentElement.querySelector('[data-variant-sku]');
            const useGenerated = !input.readOnly;
            input.readOnly = useGenerated;
            input.dataset.variantAuto = useGenerated ? 'true' : 'false';
            editSku.textContent = useGenerated ? 'Edit SKU' : 'Use generated SKU';
            if (useGenerated) refreshAutoSkus(); else input.focus();
            return;
        }
        const remove = event.target.closest('[data-remove-variant]');
        if (remove) {
            remove.closest('[data-variant-row]').querySelectorAll('.variant-dropdown').forEach((select) => select.tomselect?.destroy());
            remove.closest('[data-variant-row]').remove();
        }
    });
})();
</script>
