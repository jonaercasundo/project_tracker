<x-mi_app :mobile-navigation="true">
    @php
        $removedIds = array_map('intval', old('remove_image_ids', []));
        $imageOrder = array_map('intval', old('image_order', []));
        $displayImages = $product->images->sortBy(fn ($image) => ($position = array_search($image->id, $imageOrder, true)) === false ? count($imageOrder) + $image->sort_order : $position);
        $primaryId = old('primary_image_id', $product->images->firstWhere('is_primary', true)?->id ?? $product->images->first()?->id);
    @endphp
    <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-blue-700">MI product catalog</p>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Edit Product</h1>
                <p class="mt-2 break-words text-sm text-slate-600">{{ $product->item_name }} <span class="mx-2 text-slate-300">/</span> <span class="font-mono">{{ $product->sku ?: 'Product '.$product->product_id }}</span></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('mi_app.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-blue-600">Back to products</a>
                <button type="submit" form="edit_product_form" data-save class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600 disabled:opacity-60">Save Changes</button>
            </div>
        </header>
        @if(session('success'))<div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-bold">Your changes were not saved.</p>
                <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                <p class="mt-2">Review the fields below. Please select any new upload files again.</p>
            </div>
        @endif
        <form id="edit_product_form" action="{{ route('mi_app.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="product_information">
                <h2 id="product_information" class="mb-5 text-lg font-bold text-slate-900">Product information</h2>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_name', 'label' => 'Product name', 'required' => true])
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_code', 'label' => 'Product code', 'required' => false])
                    <div>
                        <label for="type_of_sample" class="mb-2 block text-sm font-semibold text-slate-700">Type of sample <span class="text-red-600">*</span></label>
                        <select id="type_of_sample" name="type_of_sample" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" @error('type_of_sample') aria-invalid="true" aria-describedby="type_of_sample_error" @enderror>
                            <option value="" @selected(!old('type_of_sample', $product->type_of_sample))>Select sample type</option>
                            @foreach(array_unique(array_filter(['Factory Design', 'Metroinc Design', old('type_of_sample', $product->type_of_sample)])) as $sampleType)
                                <option @selected(old('type_of_sample', $product->type_of_sample) === $sampleType)>{{ $sampleType }}</option>
                            @endforeach
                        </select>
                        @error('type_of_sample')<p id="type_of_sample_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'designed_by', 'label' => 'Designed by', 'required' => false])
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'type', 'label' => 'Product type / finish', 'required' => false])
                    <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                        <p>SKU <span class="font-mono font-semibold text-slate-900">{{ $product->sku ?: 'Not assigned' }}</span></p>
                        <p class="mt-1">Status: {{ $product->status }} @if($product->classification) ? {{ $product->classification }} @endif</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea id="description" name="description" rows="4" maxlength="20000" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" @error('description') aria-invalid="true" aria-describedby="description_error" @enderror>{{ old('description', $product->description) }}</textarea>
                        @error('description')<p id="description_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="taxonomy_heading">
                <h2 id="taxonomy_heading" class="mb-2 text-lg font-bold text-slate-900">Category & collection</h2>
                <p class="mb-5 text-sm text-slate-500">Choose each level in order. The existing SKU stays unchanged.</p>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('mi_app.designer_module.partials._edit-select', ['name' => 'category_id', 'label' => 'Category', 'options' => $categories, 'required' => true])
                    @include('mi_app.designer_module.partials._edit-select', ['name' => 'sub_category_id', 'label' => 'Subcategory', 'options' => $subCategories, 'required' => true, 'parentField' => 'category_id'])
                    @include('mi_app.designer_module.partials._edit-select', ['name' => 'product_type_id', 'label' => 'Sub subcategory', 'options' => $productTypes, 'required' => false, 'parentField' => 'sub_category_id'])
                    @include('mi_app.designer_module.partials._edit-select', ['name' => 'collection_id', 'label' => 'Collection', 'options' => $collections, 'required' => false, 'parentField' => 'product_type_id'])
                </div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="attributes_heading">
                <h2 id="attributes_heading" class="mb-5 text-lg font-bold text-slate-900">Materials & colors</h2>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach(['materials' => ['Materials', $product->materials], 'color' => ['Colors', $product->color]] as $field => [$label, $values])
                        @php
                            $selectedValues = old($field, $values) ?: [];
                        @endphp
                        <div>
                            <label for="{{ $field }}" class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }} @if($field === 'materials')<span class="text-red-600">*</span>@endif</label>
                            @if($field === 'color')<input type="hidden" name="color" value="">@endif
                            <select id="{{ $field }}" name="{{ $field }}[]" multiple size="8" @if($field === 'materials') required @endif class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" @if($errors->has($field) || $errors->has($field.'.*')) aria-invalid="true" aria-describedby="{{ $field }}_error" @endif>
                                @include('mi_app.designer_module.partials._attribute-options', ['attribute' => $field, 'selectedValues' => $selectedValues])
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Hold Ctrl or Command to select multiple values.</p>
                            <div class="mt-3 flex gap-2">
                                <input type="text" maxlength="255" data-custom-value="{{ $field }}" aria-label="Custom {{ strtolower($label) }}" placeholder="Add a custom value" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
                                <button type="button" data-add-value="{{ $field }}" class="rounded-lg border border-slate-300 px-3 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-blue-600">Add</button>
                            </div>
                            @foreach($errors->get($field) + $errors->get($field.'.*') as $messages) @foreach((array) $messages as $message)<p id="{{ $field }}_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@endforeach @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="dimensions_heading">
                <h2 id="dimensions_heading" class="mb-5 text-lg font-bold text-slate-900">Dimensions & cost</h2>
                @foreach(['product' => 'Product dimensions', 'carton' => 'Carton dimensions'] as $prefix => $title)
                    <h3 class="mb-3 text-sm font-bold text-slate-600">{{ $title }} <span class="font-normal">(cm)</span></h3>
                    <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach(['height', 'width', 'length', 'depth'] as $dimension)
                            @include('mi_app.designer_module.partials._edit-field', ['name' => $prefix.'_'.$dimension, 'label' => ucfirst($dimension), 'inputType' => 'number', 'required' => false])
                        @endforeach
                    </div>
                @endforeach
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'purchase_cost', 'label' => 'Purchase cost', 'inputType' => 'number', 'required' => false])
                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'price', 'label' => 'Selling price', 'inputType' => 'number', 'required' => false])
                </div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="images_heading">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="images_heading" class="text-lg font-bold text-slate-900">Product images</h2><p class="mt-1 text-sm text-slate-500">Images are kept unless you mark them for removal. Changes apply when you save.</p></div>
                    <button type="button" id="add_image" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600">Add another image</button>
                </div>
                @foreach(['images', 'primary_image_id', 'remove_image_ids', 'remove_image_ids.*', 'image_order', 'image_order.*', 'product_images', 'product_images.*', 'image_links'] as $field)
                    @foreach($errors->get($field) as $messages) @foreach((array) $messages as $message)<p class="mb-3 text-sm text-red-700">{{ $message }}</p>@endforeach @endforeach
                @endforeach
                <div id="existing_images" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($displayImages as $image)
                        @php
                            $source = $image->image_type === 'upload' ? Storage::disk('public')->url($image->image_path) : $image->image_url;
                            $safeUrl = in_array(strtolower(parse_url($source ?? '', PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) || str_starts_with($source ?? '', '/');
                            $isPhoto = $image->image_type === 'url' || in_array(strtolower(pathinfo($image->image_path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
                        @endphp
                        <article data-image-id="{{ $image->id }}" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            @if($safeUrl && $isPhoto)<img src="{{ $source }}" alt="{{ $product->item_name }} image {{ $loop->iteration }}" loading="lazy" referrerpolicy="no-referrer" data-image-preview class="h-44 w-full bg-white object-contain">
                            @else<div class="flex h-44 items-center justify-center bg-white text-sm text-slate-500">{{ $isPhoto ? 'Preview unavailable' : 'Product attachment' }}</div>@endif
                            <p data-preview-error hidden class="p-3 text-sm text-slate-500">Preview unavailable. The image record will be kept.</p>
                            <div class="space-y-3 p-4">
                                <div class="flex items-center justify-between gap-2"><span class="text-xs font-semibold text-slate-500">{{ $image->image_type === 'upload' ? 'Uploaded file' : 'Image URL' }}</span><span data-primary-badge @if((int) $primaryId !== $image->id) hidden @endif class="rounded-full bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">Primary</span></div>
                                @if($safeUrl)<a href="{{ $source }}" target="_blank" rel="noopener noreferrer" class="inline-block text-sm font-semibold text-blue-700 underline">Preview {{ $isPhoto ? 'image' : 'attachment' }}</a>@endif
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked((int) $primaryId === $image->id) @disabled(in_array($image->id, $removedIds, true))> Set as Primary</label>
                                <input type="hidden" name="image_order[]" value="{{ $image->id }}">
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    <button type="button" data-move="up" aria-label="Move image earlier" class="rounded border border-slate-300 px-2 py-1 focus-visible:ring-2 focus-visible:ring-blue-600">Move up</button>
                                    <button type="button" data-move="down" aria-label="Move image later" class="rounded border border-slate-300 px-2 py-1 focus-visible:ring-2 focus-visible:ring-blue-600">Move down</button>
                                    <label class="flex items-center gap-2 text-red-700"><input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}" @checked(in_array($image->id, $removedIds, true))> Remove</label>
                                </div>
                                <p data-removal-note @if(!in_array($image->id, $removedIds, true)) hidden @endif class="text-xs text-red-700">Marked for removal. Uncheck to keep this image.</p>
                            </div>
                        </article>
                    @empty<p class="text-sm text-slate-500 sm:col-span-2">No images yet. Add a photo or image URL below.</p>@endforelse
                </div>
                @if($product->image_link || $product->product_file)<p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Legacy media references are preserved: {{ $product->image_link }} {{ $product->product_file }}</p>@endif
                <div id="new_images" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach(old('image_links', ['']) ?: [''] as $index => $url)
                        @include('mi_app.designer_module.partials._new-image', ['index' => $index, 'url' => $url])
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">JPEG, PNG or WebP, up to 20 MB each. External images use HTTP or HTTPS. The first image becomes primary when none remain.</p>
            </section>
            <footer class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5">
                <p id="save_status" aria-live="polite" class="text-sm text-slate-500">Review your changes before saving.</p>
                <div class="flex gap-3"><a href="{{ route('mi_app.show', $product) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-blue-600">Cancel</a><button type="submit" data-save class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-60 focus-visible:ring-2 focus-visible:ring-blue-600">Save Changes</button></div>
            </footer>
        </form>
        <template id="new_image_template">@include('mi_app.designer_module.partials._new-image', ['index' => '__INDEX__', 'url' => ''])</template>
    </div>
</x-mi_app>
