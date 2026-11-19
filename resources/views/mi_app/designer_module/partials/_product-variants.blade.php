@php
    $variantRows = old('variants', isset($product) ? $product->variants->map(fn ($variant) => array_merge($variant->only(['variant_id', 'sequence', 'material', 'color', 'materials', 'colors', 'product_length', 'product_width', 'product_height', 'product_depth', 'purchase_price', 'selling_price', 'sku']), [
        'materials' => $variant->materials ?: array_filter([$variant->material]),
        'colors' => $variant->colors ?: array_filter([$variant->color]),
    ]))->all() : []);
    $variantBaseSku = $product->sku ?? '';
@endphp
<section class="tx-card lvl-2" aria-labelledby="variants_heading">
    <div class="tx-card-head">
        <span class="tx-card-icon">04</span>
        <div><h2 id="variants_heading">Product Variants</h2><p>Each variant has its own SKU, dimensions, prices, and stock. Select every material and color that belongs to that variant.</p></div>
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
        <p class="tx-hint mt-3">Each row is one sellable variant and SKU. A variant can have multiple materials and colors, all tracked together with that row's stock. New rows receive the next sequence suffix after the product base SKU is generated (for example, -01).</p>
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
        root.querySelectorAll('.variant-dropdown, .legacy-attribute-select').forEach((select) => {
            if (initializedDropdowns.has(select) || select.tomselect || !window.TomSelect) return;
            initializedDropdowns.add(select);
            const isMultiple = select.multiple;
            const normalize = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
            new TomSelect(select, {
                plugins: ['remove_button'],
                openOnFocus: true,
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
                dropdownClass: 'ts-dropdown tx-attribute-dropdown',
                copyClassesToDropdown: false,
                placeholder: select.dataset.placeholder,
                searchField: ['text'],
                onInitialize: function () {
                    this.wrapper.classList.add('tx-attribute-select');
                    const hint = select.closest('.tx-multi-select-wrap')?.querySelector('.tx-multi-hint');
                    if (hint) {
                        hint.id ||= `${select.id}_hint`;
                        this.control_input.setAttribute('aria-describedby', [hint.id, select.getAttribute('aria-describedby')].filter(Boolean).join(' '));
                    }
                    if (select.hasAttribute('aria-invalid')) this.control_input.setAttribute('aria-invalid', 'true');
                    const theme = getComputedStyle(this.wrapper);
                    for (const property of theme) {
                        if (property.startsWith('--tx-')) this.dropdown.style.setProperty(property, theme.getPropertyValue(property));
                    }
                    this.positionDropdown = function () {
                        const bounds = this.control.getBoundingClientRect();
                        const viewport = window.visualViewport;
                        const top = viewport?.offsetTop ?? 0;
                        const bottom = top + (viewport?.height ?? window.innerHeight);
                        const below = Math.max(0, bottom - bounds.bottom - 12);
                        const above = Math.max(0, bounds.top - top - 12);
                        const opensAbove = below < 240 && above > below;
                        this.dropdown_content.style.maxHeight = `${Math.min(280, opensAbove ? above : below)}px`;
                        Object.assign(this.dropdown.style, {
                            width: `${bounds.width}px`,
                            left: `${bounds.left + window.scrollX}px`,
                            top: `${(opensAbove ? bounds.top - this.dropdown.offsetHeight - 6 : bounds.bottom + 6) + window.scrollY}px`,
                        });
                    };
                    const reposition = () => { if (this.isOpen) this.positionDropdown(); };
                    const stopTracking = () => {
                        window.removeEventListener('scroll', reposition, true);
                        window.removeEventListener('resize', reposition);
                        window.visualViewport?.removeEventListener('resize', reposition);
                    };
                    this.on('dropdown_open', () => {
                        reposition();
                        window.addEventListener('scroll', reposition, true);
                        window.addEventListener('resize', reposition);
                        window.visualViewport?.addEventListener('resize', reposition);
                    });
                    this.on('type', reposition);
                    this.on('dropdown_close', stopTracking);
                    this.on('destroy', stopTracking);
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
            select.addEventListener('invalid', (event) => {
                if (!select.tomselect) return;
                event.preventDefault();
                select.tomselect.wrapper.classList.add('field-invalid');
                select.tomselect.control_input.setAttribute('aria-invalid', 'true');
                select.tomselect.focus();
                const error = select.closest('.tx-multi-select-wrap')?.querySelector('[data-multi-error]');
                if (error) error.hidden = false;
            });
            select.tomselect?.on('item_add', () => {
                select.tomselect.wrapper.classList.remove('field-invalid');
                select.tomselect.control_input.removeAttribute('aria-invalid');
                const error = select.closest('.tx-multi-select-wrap')?.querySelector('[data-multi-error]');
                if (error) error.hidden = true;
            });
            clearButton?.addEventListener('click', (event) => {
                event.preventDefault();
                select.tomselect?.clear();
                select.tomselect?.close();
            });
            syncClearButton();
        });
    };
    const refreshAutoSkus = () => rows.querySelectorAll('[data-variant-auto="true"]').forEach((input) => {
        const sequence = Number(input.dataset.sequence);
        input.value = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
    });
    const addVariantRow = () => {
        const sequences = [...rows.querySelectorAll('[data-sequence]')].map((input) => Number(input.dataset.sequence)).filter(Number.isFinite);
        const sequence = Math.max(0, ...sequences) + 1;
        const sku = base.value.trim() ? `${base.value.trim()}-${String(sequence).padStart(2, '0')}` : '';
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).replaceAll('__SEQUENCE__', String(sequence)).replaceAll('__SKU__', sku).replaceAll('__BASE__', base.value.trim());
        rows.insertAdjacentHTML('beforeend', html);
        initializeDropdowns(rows.lastElementChild);
    };
    base.addEventListener('input', refreshAutoSkus);
    document.addEventListener('DOMContentLoaded', () => initializeDropdowns(document));
    document.getElementById('add_product_variant').addEventListener('click', addVariantRow);
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
