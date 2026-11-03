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
        <div class="tx-subpanel tx-variant-bulk-builder">
            <div class="tx-subpanel-head">
                <div>
                    <h3>Add multiple variants</h3>
                    <p>Select materials and colors in order. Each matching pair becomes a separate SKU row.</p>
                </div>
            </div>
            <div class="tx-variant-grid">
                <div>
                    <label for="variant_option_materials" class="tx-label">Materials</label>
                    <div class="tx-multi-select-wrap">
                        <div class="tx-multi-toolbar">
                            <span class="tx-multi-hint">Select or type several materials</span>
                            <button type="button" class="tx-multi-clear" data-clear-variant-options="materials" hidden>Clear</button>
                        </div>
                        <select id="variant_option_materials" multiple class="tx-field variant-option-builder" data-placeholder="Search or add materials...">
                            @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'materials', 'selectedValues' => []])
                        </select>
                    </div>
                </div>
                <div>
                    <label for="variant_option_colors" class="tx-label">Colors</label>
                    <div class="tx-multi-select-wrap">
                        <div class="tx-multi-toolbar">
                            <span class="tx-multi-hint">Select matching colors in the same order</span>
                            <button type="button" class="tx-multi-clear" data-clear-variant-options="colors" hidden>Clear</button>
                        </div>
                        <select id="variant_option_colors" multiple class="tx-field variant-option-builder" data-placeholder="Search or add colors...">
                            @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'color', 'selectedValues' => []])
                        </select>
                    </div>
                </div>
            </div>
            <div class="tx-variant-bulk-actions">
                <button type="button" id="generate_product_variants" class="tx-btn-small">Create variant rows</button>
                <p id="variant_builder_message" class="tx-hint" role="status" aria-live="polite" hidden></p>
            </div>
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
        <p class="tx-hint mt-3">Each row is one sellable variant with its own stock and SKU. New rows receive the next sequence suffix after the product base SKU is generated (for example, -01). You can edit a variant SKU if it remains unique.</p>
    </div>
</section>
<template id="product_variant_template">@include('mi_app.designer_module.partials._product-variant-row', ['index' => '__INDEX__', 'variant' => ['sequence' => '__SEQUENCE__', 'material' => '', 'color' => '', 'sku' => '__SKU__'], 'baseSku' => '__BASE__'])</template>
<script>
(() => {
    const rows = document.getElementById('product_variants');
    const base = document.getElementById('sku');
    const template = document.getElementById('product_variant_template');
    const existingIndices = [...rows.querySelectorAll('[data-variant-row]')].map((row) => {
        const fieldName = row.querySelector('[name^="variants["]')?.name ?? '';
        return Number(fieldName.match(/^variants\[(\d+)\]/)?.[1]);
    }).filter(Number.isFinite);
    let nextIndex = Math.max(-1, ...existingIndices) + 1;
    const initializedDropdowns = new WeakSet();
    const initializeDropdowns = (root) => {
        root.querySelectorAll('.variant-dropdown, .legacy-attribute-select, .variant-option-builder').forEach((select) => {
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
    const addVariantRow = (material = '', color = '') => {
        const sequences = [...rows.querySelectorAll('[data-sequence]')].map((input) => Number(input.dataset.sequence)).filter(Number.isFinite);
        const sequence = Math.max(0, ...sequences) + 1;
        const sku = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).replaceAll('__SEQUENCE__', String(sequence)).replaceAll('__SKU__', sku).replaceAll('__BASE__', base.value.trim());
        rows.insertAdjacentHTML('beforeend', html);
        const row = rows.lastElementChild;
        initializeDropdowns(row);

        [['materials', material], ['colors', color]].forEach(([attribute, value]) => {
            if (!value) return;
            const source = document.getElementById(`variant_option_${attribute}`)?.tomselect;
            const target = row.querySelector(`select[name$="[${attribute === 'materials' ? 'material' : 'color'}]"]`)?.tomselect;
            if (!target) return;
            const option = source?.options[value];
            if (!target.options[value]) target.addOption({ value, text: option?.text ?? value });
            target.addItem(value);
        });
    };
    base.addEventListener('input', refreshAutoSkus);
    document.addEventListener('DOMContentLoaded', () => initializeDropdowns(document));
    document.getElementById('add_product_variant').addEventListener('click', () => addVariantRow());
    document.getElementById('generate_product_variants').addEventListener('click', () => {
        const materialSelect = document.getElementById('variant_option_materials').tomselect;
        const colorSelect = document.getElementById('variant_option_colors').tomselect;
        const materials = materialSelect.items;
        const colors = colorSelect.items;
        const message = document.getElementById('variant_builder_message');
        message.hidden = false;
        const setMessage = (text, isError = false) => {
            message.textContent = text;
            message.classList.toggle('tx-error', isError);
            message.classList.toggle('tx-hint', !isError);
        };

        if (materials.length === 0) {
            setMessage('Select at least one material to create variant rows.', true);
            return;
        }
        if (colors.length > 0 && colors.length !== materials.length) {
            setMessage('Select one color for each material, in the same order, or clear Colors to leave them blank.', true);
            return;
        }
        if (rows.querySelectorAll('[data-variant-row]').length + materials.length > 100) {
            setMessage('A product can have at most 100 variant rows.', true);
            return;
        }

        const generatedCount = materials.length;
        materials.forEach((material, index) => addVariantRow(material, colors[index] ?? ''));
        materialSelect.clear();
        colorSelect.clear();
        setMessage(`${generatedCount} variant row${generatedCount === 1 ? '' : 's'} created. Enter the dimensions and prices for each row.`);
    });
    document.querySelectorAll('[data-clear-variant-options]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(`variant_option_${button.dataset.clearVariantOptions}`)?.tomselect?.clear());
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
