<x-mi_app :mobile-navigation="true">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    @include('mi_app.designer_module.partials._product-form-styles')

    <style>
        .tx-product-edit [hidden] { display: none !important; }
        .tx-product-edit :is(a, button, input[type="radio"], input[type="checkbox"]):focus-visible {
            outline: 2px solid var(--tx-primary);
            outline-offset: 3px;
        }
        .tx-product-edit .tx-header-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .tx-product-edit .tx-edit-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
        .tx-product-edit .tx-edit-badge { padding: 5px 9px; border: 1px solid var(--tx-line); border-radius: 8px; background: var(--tx-surface); color: var(--tx-ink-soft); font-size: 10px; font-weight: 600; }
        .tx-product-edit .tx-edit-status { color: var(--tx-success); background: var(--tx-success-soft); border-color: var(--tx-success-soft); }
        .tx-product-edit .tx-edit-span-all { grid-column: 1 / -1; }
        .tx-product-edit .tx-edit-inline { display: flex; align-items: center; gap: 8px; }
        .tx-product-edit .tx-edit-inline .tx-field { min-width: 0; flex: 1; }
        .tx-product-edit .tx-edit-inline .tx-btn-small { margin: 0; min-height: 41px; flex-shrink: 0; }
        .tx-product-edit .tx-edit-heading-actions { margin-left: auto; }
        .tx-product-edit .tx-edit-heading-actions .tx-btn-small { margin-top: 0; }
        .tx-product-edit .tx-edit-image-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
        .tx-product-edit .tx-edit-image { min-width: 0; overflow: hidden; border: 1px solid var(--tx-line); border-radius: 14px; background: var(--tx-surface-soft); }
        .tx-product-edit .tx-edit-image:has(input[type="checkbox"]:checked) { border-color: var(--tx-danger); }
        .tx-product-edit .tx-edit-image-preview { position: relative; border-bottom: 1px solid var(--tx-line); background: var(--tx-surface); }
        .tx-product-edit .tx-edit-image-preview img { width: 100%; height: 190px; padding: 12px; object-fit: contain; }
        .tx-product-edit .tx-edit-image:has(input[type="checkbox"]:checked) .tx-edit-image-preview img { opacity: .45; }
        .tx-product-edit .tx-edit-image-placeholder { display: flex; align-items: center; justify-content: center; min-height: 190px; font-size: 12px; color: var(--tx-ink-faint); }
        .tx-product-edit .tx-edit-primary { position: absolute; top: 12px; left: 12px; padding: 5px 9px; border-radius: 8px; background: var(--tx-primary); color: var(--tx-primary-ink); font-size: 10px; font-weight: 700; }
        .tx-product-edit .tx-edit-image-body { display: flex; flex-direction: column; gap: 12px; padding: 16px; }
        .tx-product-edit .tx-edit-image-type { font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--tx-ink-faint); }
        .tx-product-edit .tx-edit-preview-link { font-size: 11px; font-weight: 600; color: var(--tx-primary); text-decoration: underline; text-underline-offset: 3px; }
        .tx-product-edit .tx-edit-choice { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 600; color: var(--tx-ink-soft); cursor: pointer; }
        .tx-product-edit .tx-edit-choice input { accent-color: var(--tx-primary); }
        .tx-product-edit .tx-edit-choice:has(input:disabled) { opacity: .55; cursor: not-allowed; }
        .tx-product-edit .tx-edit-danger { color: var(--tx-danger); }
        .tx-product-edit .tx-edit-danger input { accent-color: var(--tx-danger); }
        .tx-product-edit .tx-edit-image-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid var(--tx-line); padding-top: 12px; }
        .tx-product-edit .tx-edit-image-actions .tx-btn-small { margin: 0; }
        .tx-product-edit .tx-edit-removal-note { margin: 0; padding: 9px 10px; border-radius: 8px; background: var(--tx-danger-soft); color: var(--tx-danger); font-size: 10px; line-height: 1.5; }
        .tx-product-edit .tx-edit-empty { grid-column: 1 / -1; padding: 28px; border: 1px dashed var(--tx-line); border-radius: 14px; text-align: center; background: var(--tx-surface-soft); color: var(--tx-ink-soft); font-size: 12px; }
        .tx-product-edit .tx-edit-empty p { margin: 5px 0 0; }
        .tx-product-edit .tx-edit-new-images { margin-top: 5px; padding-top: 20px; border-top: 1px solid var(--tx-line); }
        .tx-product-edit .tx-edit-new-images h3 { margin: 0 0 14px; font-family: var(--tx-font-display); font-size: 13px; font-weight: 700; color: var(--tx-ink); }
        .tx-product-edit .tx-edit-new-image { display: grid; gap: 10px; padding: 17px; border: 1px dashed var(--tx-line); border-radius: 14px; background: var(--tx-surface-soft); }
        .tx-product-edit .tx-edit-new-image-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
        .tx-product-edit .tx-edit-new-image-head .tx-label { margin: 0; }
        .tx-product-edit .tx-edit-new-image-head button { padding: 0; background: transparent; border: 0; font-size: 10px; font-weight: 600; color: var(--tx-danger); cursor: pointer; }
        .tx-product-edit .tx-edit-new-image .tx-label { margin-bottom: 0; }
        .tx-product-edit .tx-edit-new-image img { width: 100%; height: 160px; border: 1px solid var(--tx-line); border-radius: 10px; background: var(--tx-surface); object-fit: contain; }
        .tx-product-edit .tx-edit-success { border-color: var(--tx-success); background: var(--tx-success-soft); color: var(--tx-success); }
        .tx-product-edit .tx-edit-footer-status { margin: 0 0 10px; padding: 0 4px; font-size: 11px; color: var(--tx-ink-soft); }
        .tx-product-edit .tx-edit-reference { word-break: break-word; }
        @media (min-width: 650px) { .tx-product-edit .tx-edit-image-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1100px) { .tx-product-edit #existing_images { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 800px) {
            .tx-product-edit .tx-header-actions { width: 100%; }
            .tx-product-edit .tx-header-actions > * { flex: 1; }
            .tx-product-edit .tx-card-head { flex-wrap: wrap; }
            .tx-product-edit .tx-edit-heading-actions { margin-left: 51px; }
        }
    </style>

    @php
        $removedIds = array_map('intval', old('remove_image_ids', []));
        $imageOrder = array_map('intval', old('image_order', []));
        $displayImages = $product->images->sortBy(fn ($image) => ($position = array_search($image->id, $imageOrder, true)) === false ? count($imageOrder) + $image->sort_order : $position);
        $primaryId = old('primary_image_id', $product->images->firstWhere('is_primary', true)?->id ?? $product->images->first()?->id);
    @endphp

    <div class="tx-console tx-product-edit">
        <div class="tx-shell">
            <header class="tx-header">
                <div class="tx-header-content">
                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.index') }}">Product Database</a>
                        <span>/</span>
                        <span>Edit Product</span>
                    </div>
                    <h1 class="tx-title tx-display">Edit Product</h1>
                    <p class="tx-subtitle">{{ $product->item_name }}</p>
                    <div class="tx-edit-meta">
                        <span class="tx-edit-badge tx-mono">{{ $product->sku ?: 'Product '.$product->product_id }}</span>
                        @if($product->status)<span class="tx-edit-badge tx-edit-status">{{ $product->status }}</span>@endif
                        @if($product->classification)<span class="tx-edit-badge">{{ $product->classification }}</span>@endif
                    </div>
                </div>
                <div class="tx-header-actions">
                    <a href="{{ route('mi_app.index') }}" class="tx-back">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to Database
                    </a>
                    <button type="submit" form="edit_product_form" data-save class="tx-btn-submit">Save Changes</button>
                </div>
            </header>

            @if(session('success'))
                <div role="status" class="tx-alert tx-edit-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div role="alert" class="tx-alert">
                    <div>
                        <strong>Your changes were not saved.</strong>
                        <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                        <p class="mt-2">Review the fields below. Please select any new upload files again.</p>
                    </div>
                </div>
            @endif

            <form id="edit_product_form" action="{{ route('mi_app.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <section id="section_taxonomy" class="tx-card lvl-1" aria-labelledby="taxonomy_heading">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">01</span>
                        <div><h2 id="taxonomy_heading">Taxonomy</h2><p>Category &rarr; Sub Category &rarr; Sub Sub Category &rarr; Collection</p></div>
                    </div>
                    <div class="tx-card-body cols-4">
                        @include('mi_app.designer_module.partials._edit-select', ['name' => 'category_id', 'label' => 'Category', 'options' => $categories, 'required' => true])
                        @include('mi_app.designer_module.partials._edit-select', ['name' => 'sub_category_id', 'label' => 'Sub Category', 'options' => $subCategories, 'required' => true, 'parentField' => 'category_id'])
                        @include('mi_app.designer_module.partials._edit-select', ['name' => 'product_type_id', 'label' => 'Sub Sub Category', 'options' => $productTypes, 'required' => false, 'parentField' => 'sub_category_id'])
                        @include('mi_app.designer_module.partials._edit-select', ['name' => 'collection_id', 'label' => 'Collection', 'options' => $collections, 'required' => false, 'parentField' => 'product_type_id'])
                    </div>
                    <div class="tx-taxonomy-preview">
                        <span class="tx-taxonomy-preview-label">Product SKU</span>
                        <span class="tx-mono">{{ $product->sku ?: 'Not assigned' }}</span>
                        <span class="tx-hint">The existing SKU stays unchanged when you edit the taxonomy.</span>
                    </div>
                </section>

                <section id="section_info" class="tx-card lvl-1" aria-labelledby="product_information">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">02</span>
                        <div><h2 id="product_information">General Information</h2><p>Product name, sample type, designer, and description</p></div>
                    </div>
                    <div class="tx-card-body cols-2">
                        @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_name', 'label' => 'Item Name', 'required' => true])
                        @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_code', 'label' => 'Product Code', 'required' => false])
                        <div>
                            <label for="type_of_sample" class="tx-label">Type of Sample <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select id="type_of_sample" name="type_of_sample" required class="tx-field @error('type_of_sample') field-invalid @enderror" @error('type_of_sample') aria-invalid="true" aria-describedby="type_of_sample_error" @enderror>
                                    <option value="" @selected(!old('type_of_sample', $product->type_of_sample))>Select sample type</option>
                                    @foreach(array_unique(array_filter(['Factory Design', 'Metroinc Design', old('type_of_sample', $product->type_of_sample)])) as $sampleType)
                                        <option @selected(old('type_of_sample', $product->type_of_sample) === $sampleType)>{{ $sampleType }}</option>
                                    @endforeach
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                            </div>
                            @error('type_of_sample')<p id="type_of_sample_error" class="tx-error">{{ $message }}</p>@enderror
                        </div>
                        @include('mi_app.designer_module.partials._edit-field', ['name' => 'designed_by', 'label' => 'Designed By', 'required' => false])
                        @include('mi_app.designer_module.partials._edit-field', ['name' => 'type', 'label' => 'Product Type / Finish', 'required' => false])
                        <div class="tx-edit-span-all">
                            <label for="description" class="tx-label">Description</label>
                            <textarea id="description" name="description" rows="4" maxlength="20000" class="tx-field @error('description') field-invalid @enderror" @error('description') aria-invalid="true" aria-describedby="description_error" @enderror>{{ old('description', $product->description) }}</textarea>
                            @error('description')<p id="description_error" class="tx-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section id="section_attributes" class="tx-card lvl-2" aria-labelledby="attributes_heading">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">03</span>
                        <div><h2 id="attributes_heading">Attributes &amp; Dimensions</h2><p>Materials, colors, product measurements, and pricing</p></div>
                    </div>
                    <div class="tx-card-body">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach(['materials' => ['Materials', $product->materials], 'color' => ['Color', $product->color]] as $field => [$label, $values])
                                @php
                                    $selectedValues = old($field, $values) ?: [];
                                @endphp
                                <div>
                                    <label for="{{ $field }}" class="tx-label">{{ $label }} @if($field === 'materials')<span class="tx-required">*</span>@endif</label>
                                    <div class="tx-multi-select-wrap">
                                        <p class="tx-multi-hint">Select one or more {{ strtolower($label) }} values</p>
                                        @if($field === 'color')<input type="hidden" name="color" value="">@endif
                                        <select id="{{ $field }}" name="{{ $field }}[]" multiple size="8" @if($field === 'materials') required @endif class="tx-field tx-multi-select {{ $errors->has($field) || $errors->has($field.'.*') ? 'field-invalid' : '' }}" @if($errors->has($field) || $errors->has($field.'.*')) aria-invalid="true" aria-describedby="{{ $field }}_error" @endif>
                                            @include('mi_app.designer_module.partials._attribute-options', ['attribute' => $field, 'selectedValues' => $selectedValues])
                                        </select>
                                        <p class="tx-hint">Hold Ctrl or Command to select multiple values.</p>
                                        <div class="tx-edit-inline">
                                            <input type="text" maxlength="255" data-custom-value="{{ $field }}" aria-label="Custom {{ strtolower($label) }}" placeholder="Add a custom value" class="tx-field">
                                            <button type="button" data-add-value="{{ $field }}" class="tx-btn-small">Add</button>
                                        </div>
                                    </div>
                                    @foreach($errors->get($field) + $errors->get($field.'.*') as $messages)
                                        @foreach((array) $messages as $message)<p id="{{ $field }}_error" class="tx-error">{{ $message }}</p>@endforeach
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div id="section_dimensions">
                            @foreach(['product' => 'Product Dimensions', 'carton' => 'Carton Dimensions'] as $prefix => $title)
                                <div class="tx-subpanel">
                                    <div class="tx-subpanel-head"><div><h3>{{ $title }}</h3><p>Measurements in centimeters</p></div><span class="tx-subpanel-tag tx-mono">CM</span></div>
                                    <div class="tx-dims-grid">
                                        @foreach(['height', 'width', 'length', 'depth'] as $dimension)
                                            @include('mi_app.designer_module.partials._edit-field', ['name' => $prefix.'_'.$dimension, 'label' => ucfirst($dimension), 'inputType' => 'number', 'required' => false, 'unit' => 'cm'])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <div class="tx-subpanel">
                                <div class="tx-subpanel-head"><div><h3>Pricing</h3><p>Purchase cost and selling price</p></div></div>
                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'purchase_cost', 'label' => 'Purchase Cost', 'inputType' => 'number', 'required' => false])
                                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'price', 'label' => 'Selling Price', 'inputType' => 'number', 'required' => false])
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="section_images" class="tx-card lvl-3" aria-labelledby="images_heading">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">04</span>
                        <div><h2 id="images_heading">Product Media</h2><p>Manage existing photos, upload images, or add image links</p></div>
                        <div class="tx-edit-heading-actions"><button type="button" id="add_image" class="tx-btn-small">+ Add another image</button></div>
                    </div>
                    <div class="tx-card-body">
                        <p class="tx-hint">Images are kept unless you mark them for removal. Changes apply when you save.</p>
                        @foreach(['images', 'primary_image_id', 'remove_image_ids', 'remove_image_ids.*', 'image_order', 'image_order.*', 'product_images', 'product_images.*', 'image_links'] as $field)
                            @foreach($errors->get($field) as $messages)
                                @foreach((array) $messages as $message)<p class="tx-error">{{ $message }}</p>@endforeach
                            @endforeach
                        @endforeach
                        <div id="existing_images" class="tx-edit-image-grid">
                            @forelse($displayImages as $image)
                                @php
                                    $source = $image->image_type === 'upload' ? Storage::disk('public')->url($image->image_path) : $image->image_url;
                                    $safeUrl = in_array(strtolower(parse_url($source ?? '', PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) || str_starts_with($source ?? '', '/');
                                    $isPhoto = $image->image_type === 'url' || in_array(strtolower(pathinfo($image->image_path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
                                @endphp
                                <article data-image-id="{{ $image->id }}" class="tx-edit-image">
                                    <div class="tx-edit-image-preview">
                                        @if($safeUrl && $isPhoto)<img src="{{ $source }}" alt="{{ $product->item_name }} image {{ $loop->iteration }}" loading="lazy" referrerpolicy="no-referrer" data-image-preview>
                                        @else<div class="tx-edit-image-placeholder">{{ $isPhoto ? 'Preview unavailable' : 'Product attachment' }}</div>@endif
                                        <span data-primary-badge @if((int) $primaryId !== $image->id) hidden @endif class="tx-edit-primary">Primary</span>
                                        <p data-preview-error hidden class="tx-hint p-3">Preview unavailable. The image record will be kept.</p>
                                    </div>
                                    <div class="tx-edit-image-body">
                                        <span class="tx-edit-image-type">{{ $image->image_type === 'upload' ? 'Uploaded file' : 'Image URL' }}</span>
                                        @if($safeUrl)<a href="{{ $source }}" target="_blank" rel="noopener noreferrer" class="tx-edit-preview-link">Preview {{ $isPhoto ? 'image' : 'attachment' }}</a>@endif
                                        <label class="tx-edit-choice"><input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked((int) $primaryId === $image->id) @disabled(in_array($image->id, $removedIds, true))> Set as Primary</label>
                                        <input type="hidden" name="image_order[]" value="{{ $image->id }}">
                                        <div class="tx-edit-image-actions">
                                            <div class="tx-edit-inline"><button type="button" data-move="up" aria-label="Move image earlier" class="tx-btn-small">Move up</button><button type="button" data-move="down" aria-label="Move image later" class="tx-btn-small">Move down</button></div>
                                            <label class="tx-edit-choice tx-edit-danger"><input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}" @checked(in_array($image->id, $removedIds, true))> Remove</label>
                                        </div>
                                        <p data-removal-note @if(!in_array($image->id, $removedIds, true)) hidden @endif class="tx-edit-removal-note">Marked for removal. Uncheck to keep this image.</p>
                                    </div>
                                </article>
                            @empty
                                <div class="tx-edit-empty"><strong>No images yet</strong><p>Add a photo or image URL below.</p></div>
                            @endforelse
                        </div>
                        @if($product->image_link || $product->product_file)<p class="tx-hint tx-edit-reference">Legacy media references are preserved: {{ $product->image_link }} {{ $product->product_file }}</p>@endif
                        <div class="tx-edit-new-images">
                            <h3>Add new images</h3>
                            <div id="new_images" class="tx-edit-image-grid">
                                @foreach(old('image_links', ['']) ?: [''] as $index => $url)
                                    @include('mi_app.designer_module.partials._new-image', ['index' => $index, 'url' => $url])
                                @endforeach
                            </div>
                            <p class="tx-hint mt-3">JPEG, PNG or WebP, up to 20 MB each. External images use HTTP or HTTPS. The first image becomes primary when none remain.</p>
                        </div>
                    </div>
                </section>

                <footer class="tx-footer">
                    <p id="save_status" aria-live="polite" class="tx-edit-footer-status">Review your changes before saving.</p>
                    <div class="tx-footer-inner">
                        <a href="{{ route('mi_app.show', $product) }}" class="tx-btn-ghost">Cancel</a>
                        <button type="submit" data-save class="tx-btn-submit">Save Changes</button>
                    </div>
                </footer>
            </form>
            <template id="new_image_template">@include('mi_app.designer_module.partials._new-image', ['index' => '__INDEX__', 'url' => ''])</template>
        </div>
    </div>
</x-mi_app>
