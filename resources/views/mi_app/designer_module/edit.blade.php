<x-mi_app :mobile-navigation="true">
    @php
        $removedIds = array_map('intval', old('remove_image_ids', []));
        $imageOrder = array_map('intval', old('image_order', []));
        $displayImages = $product->images->sortBy(fn ($image) => ($position = array_search($image->id, $imageOrder, true)) === false ? count($imageOrder) + $image->sort_order : $position);
        $primaryId = old('primary_image_id', $product->images->firstWhere('is_primary', true)?->id ?? $product->images->first()?->id);

        // Shared design tokens (kept in one place so every section stays consistent)
        $card = 'scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7';
        $iconWrap = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100';
        $selectClass = 'w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm transition focus:border-blue-600 focus:ring-blue-600';
        $btnGhost = 'inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2';
        $btnPrimary = 'inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

        $icons = [
            'info' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
            'tag' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z',
            'brush' => 'M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42',
            'size' => 'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15',
            'photo' => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
        ];

        $sections = [
            ['id' => 'section_info', 'label' => 'Product information', 'icon' => 'info'],
            ['id' => 'section_taxonomy', 'label' => 'Category & collection', 'icon' => 'tag'],
            ['id' => 'section_attributes', 'label' => 'Materials & colors', 'icon' => 'brush'],
            ['id' => 'section_dimensions', 'label' => 'Dimensions & cost', 'icon' => 'size'],
            ['id' => 'section_images', 'label' => 'Product images', 'icon' => 'photo'],
        ];
    @endphp

    <div class="bg-slate-50/70">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-8 sm:py-8">

            {{-- Header --}}
            <header class="mb-8">
                <nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500">
                    <ol class="flex flex-wrap items-center gap-1.5">
                        <li><a href="{{ route('mi_app.index') }}" class="rounded font-medium text-slate-600 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600">Products</a></li>
                        <li aria-hidden="true" class="text-slate-300">/</li>
                        <li><a href="{{ route('mi_app.show', $product) }}" class="max-w-[16rem] truncate rounded font-medium text-slate-600 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600 sm:max-w-xs">{{ $product->item_name }}</a></li>
                        <li aria-hidden="true" class="text-slate-300">/</li>
                        <li aria-current="page" class="font-semibold text-slate-900">Edit</li>
                    </ol>
                </nav>

                <div class="flex flex-wrap items-end justify-between gap-5">
                    <div class="min-w-0">
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Edit product</h1>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                            <span class="break-words font-semibold text-slate-800">{{ $product->item_name }}</span>
                            <span class="rounded-md bg-slate-900 px-2 py-0.5 font-mono text-xs font-semibold text-white">{{ $product->sku ?: 'Product '.$product->product_id }}</span>
                            @if($product->status)<span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">{{ $product->status }}</span>@endif
                            @if($product->classification)<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">{{ $product->classification }}</span>@endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('mi_app.index') }}" class="{{ $btnGhost }}">Back to products</a>
                        <button type="submit" form="edit_product_form" data-save class="{{ $btnPrimary }}">Save changes</button>
                    </div>
                </div>
            </header>

            @if(session('success'))
                <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 border-l-4 border-l-red-500 bg-red-50 p-4 text-sm text-red-800">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="font-bold">Your changes were not saved.</p>
                        <ul class="mt-2 list-inside list-disc space-y-0.5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                        <p class="mt-3 text-red-700">Review the fields below. Please select any new upload files again.</p>
                    </div>
                </div>
            @endif

            <div class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10">

                {{-- Section navigation: horizontal chips on mobile, sticky list on desktop --}}
                <aside class="mb-6 lg:mb-0">
                    <nav aria-label="Form sections" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:-mx-8 sm:px-8 lg:sticky lg:top-6 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0">
                        @foreach($sections as $section)
                            <a href="#{{ $section['id'] }}" class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600 lg:border-transparent lg:bg-transparent lg:shadow-none">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$section['icon']] }}"/></svg>
                                {{ $section['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <form id="edit_product_form" action="{{ route('mi_app.update', $product) }}" method="POST" enctype="multipart/form-data" class="min-w-0 space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Product information --}}
                    <section id="section_info" class="{{ $card }}" aria-labelledby="product_information">
                        <div class="mb-6 flex items-start gap-3.5">
                            <span class="{{ $iconWrap }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['info'] }}"/></svg></span>
                            <div>
                                <h2 id="product_information" class="text-lg font-bold text-slate-900">Product information</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Name, sample type and a description for the catalog.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_name', 'label' => 'Product name', 'required' => true])
                            @include('mi_app.designer_module.partials._edit-field', ['name' => 'item_code', 'label' => 'Product code', 'required' => false])
                            <div>
                                <label for="type_of_sample" class="mb-2 block text-sm font-semibold text-slate-700">Type of sample <span class="text-red-600" aria-hidden="true">*</span></label>
                                <select id="type_of_sample" name="type_of_sample" required class="{{ $selectClass }}" @error('type_of_sample') aria-invalid="true" aria-describedby="type_of_sample_error" @enderror>
                                    <option value="" @selected(!old('type_of_sample', $product->type_of_sample))>Select sample type</option>
                                    @foreach(array_unique(array_filter(['Factory Design', 'Metroinc Design', old('type_of_sample', $product->type_of_sample)])) as $sampleType)
                                        <option @selected(old('type_of_sample', $product->type_of_sample) === $sampleType)>{{ $sampleType }}</option>
                                    @endforeach
                                </select>
                                @error('type_of_sample')<p id="type_of_sample_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            @include('mi_app.designer_module.partials._edit-field', ['name' => 'designed_by', 'label' => 'Designed by', 'required' => false])
                            @include('mi_app.designer_module.partials._edit-field', ['name' => 'type', 'label' => 'Product type / finish', 'required' => false])
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-700">SKU</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Assigned automatically and not editable.</p>
                                </div>
                                <span class="font-mono text-base font-bold text-slate-900">{{ $product->sku ?: 'Not assigned' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                                <textarea id="description" name="description" rows="5" maxlength="20000" class="{{ $selectClass }} leading-relaxed" @error('description') aria-invalid="true" aria-describedby="description_error" @enderror>{{ old('description', $product->description) }}</textarea>
                                @error('description')<p id="description_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    {{-- Category & collection --}}
                    <section id="section_taxonomy" class="{{ $card }}" aria-labelledby="taxonomy_heading">
                        <div class="mb-6 flex items-start gap-3.5">
                            <span class="{{ $iconWrap }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['tag'] }}"/></svg></span>
                            <div>
                                <h2 id="taxonomy_heading" class="text-lg font-bold text-slate-900">Category & collection</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Choose each level in order. The existing SKU stays unchanged.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @include('mi_app.designer_module.partials._edit-select', ['name' => 'category_id', 'label' => 'Category', 'options' => $categories, 'required' => true])
                            @include('mi_app.designer_module.partials._edit-select', ['name' => 'sub_category_id', 'label' => 'Subcategory', 'options' => $subCategories, 'required' => true, 'parentField' => 'category_id'])
                            @include('mi_app.designer_module.partials._edit-select', ['name' => 'product_type_id', 'label' => 'Sub subcategory', 'options' => $productTypes, 'required' => false, 'parentField' => 'sub_category_id'])
                            @include('mi_app.designer_module.partials._edit-select', ['name' => 'collection_id', 'label' => 'Collection', 'options' => $collections, 'required' => false, 'parentField' => 'product_type_id'])
                        </div>
                    </section>

                    {{-- Materials & colors --}}
                    <section id="section_attributes" class="{{ $card }}" aria-labelledby="attributes_heading">
                        <div class="mb-6 flex items-start gap-3.5">
                            <span class="{{ $iconWrap }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['brush'] }}"/></svg></span>
                            <div>
                                <h2 id="attributes_heading" class="text-lg font-bold text-slate-900">Materials & colors</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Select every value that applies, or add your own.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            @foreach(['materials' => ['Materials', $product->materials], 'color' => ['Colors', $product->color]] as $field => [$label, $values])
                                @php
                                    $selectedValues = old($field, $values) ?: [];
                                @endphp
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <label for="{{ $field }}" class="mb-2 flex items-center justify-between text-sm font-semibold text-slate-700">
                                        <span>{{ $label }} @if($field === 'materials')<span class="text-red-600" aria-hidden="true">*</span>@endif</span>
                                        <span class="text-xs font-medium text-slate-500">{{ count((array) $selectedValues) }} selected</span>
                                    </label>
                                    @if($field === 'color')<input type="hidden" name="color" value="">@endif
                                    <select id="{{ $field }}" name="{{ $field }}[]" multiple size="8" @if($field === 'materials') required @endif class="{{ $selectClass }}" @if($errors->has($field) || $errors->has($field.'.*')) aria-invalid="true" aria-describedby="{{ $field }}_error" @endif>
                                        @include('mi_app.designer_module.partials._attribute-options', ['attribute' => $field, 'selectedValues' => $selectedValues])
                                    </select>
                                    <p class="mt-2 text-xs text-slate-500">Hold Ctrl or Command to select multiple values.</p>
                                    <div class="mt-3 flex gap-2">
                                        <input type="text" maxlength="255" data-custom-value="{{ $field }}" aria-label="Custom {{ strtolower($label) }}" placeholder="Add a custom value" class="min-w-0 flex-1 rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                        <button type="button" data-add-value="{{ $field }}" class="rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-blue-600">Add</button>
                                    </div>
                                    @foreach($errors->get($field) + $errors->get($field.'.*') as $messages) @foreach((array) $messages as $message)<p id="{{ $field }}_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@endforeach @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Dimensions & cost --}}
                    <section id="section_dimensions" class="{{ $card }}" aria-labelledby="dimensions_heading">
                        <div class="mb-6 flex items-start gap-3.5">
                            <span class="{{ $iconWrap }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['size'] }}"/></svg></span>
                            <div>
                                <h2 id="dimensions_heading" class="text-lg font-bold text-slate-900">Dimensions & cost</h2>
                                <p class="mt-0.5 text-sm text-slate-500">All measurements are in centimeters.</p>
                            </div>
                        </div>
                        <div class="space-y-5">
                            @foreach(['product' => 'Product dimensions', 'carton' => 'Carton dimensions'] as $prefix => $title)
                                <fieldset class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                    <legend class="px-2 text-sm font-bold text-slate-700">{{ $title }} <span class="font-normal text-slate-500">(cm)</span></legend>
                                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                                        @foreach(['height', 'width', 'length', 'depth'] as $dimension)
                                            @include('mi_app.designer_module.partials._edit-field', ['name' => $prefix.'_'.$dimension, 'label' => ucfirst($dimension), 'inputType' => 'number', 'required' => false])
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
                            <fieldset class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                <legend class="px-2 text-sm font-bold text-slate-700">Pricing</legend>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'purchase_cost', 'label' => 'Purchase cost', 'inputType' => 'number', 'required' => false])
                                    @include('mi_app.designer_module.partials._edit-field', ['name' => 'price', 'label' => 'Selling price', 'inputType' => 'number', 'required' => false])
                                </div>
                            </fieldset>
                        </div>
                    </section>

                    {{-- Product images --}}
                    <section id="section_images" class="{{ $card }}" aria-labelledby="images_heading">
                        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <span class="{{ $iconWrap }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['photo'] }}"/></svg></span>
                                <div>
                                    <h2 id="images_heading" class="text-lg font-bold text-slate-900">Product images</h2>
                                    <p class="mt-0.5 text-sm text-slate-500">Images are kept unless you mark them for removal. Changes apply when you save.</p>
                                </div>
                            </div>
                            <button type="button" id="add_image" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100 focus-visible:ring-2 focus-visible:ring-blue-600">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                                Add another image
                            </button>
                        </div>

                        @foreach(['images', 'primary_image_id', 'remove_image_ids', 'remove_image_ids.*', 'image_order', 'image_order.*', 'product_images', 'product_images.*', 'image_links'] as $field)
                            @foreach($errors->get($field) as $messages) @foreach((array) $messages as $message)<p class="mb-3 text-sm text-red-700">{{ $message }}</p>@endforeach @endforeach
                        @endforeach

                        <div id="existing_images" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            @forelse($displayImages as $image)
                                @php
                                    $source = $image->image_type === 'upload' ? Storage::disk('public')->url($image->image_path) : $image->image_url;
                                    $safeUrl = in_array(strtolower(parse_url($source ?? '', PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) || str_starts_with($source ?? '', '/');
                                    $isPhoto = $image->image_type === 'url' || in_array(strtolower(pathinfo($image->image_path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
                                @endphp
                                <article data-image-id="{{ $image->id }}" class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition has-[input[type=checkbox]:checked]:border-red-300 has-[input[type=checkbox]:checked]:bg-red-50/60">
                                    <div class="relative border-b border-slate-200 bg-slate-100">
                                        @if($safeUrl && $isPhoto)<img src="{{ $source }}" alt="{{ $product->item_name }} image {{ $loop->iteration }}" loading="lazy" referrerpolicy="no-referrer" data-image-preview class="h-48 w-full bg-white object-contain p-2 transition group-has-[input[type=checkbox]:checked]:opacity-40 group-has-[input[type=checkbox]:checked]:grayscale">
                                        @else<div class="flex h-48 items-center justify-center bg-white text-sm text-slate-500">{{ $isPhoto ? 'Preview unavailable' : 'Product attachment' }}</div>@endif
                                        <span data-primary-badge @if((int) $primaryId !== $image->id) hidden @endif class="absolute left-3 top-3 rounded-full bg-blue-600 px-2.5 py-1 text-xs font-bold text-white shadow">Primary</span>
                                        <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $image->image_type === 'upload' ? 'Uploaded file' : 'Image URL' }}</span>
                                    </div>
                                    <p data-preview-error hidden class="p-3 text-sm text-slate-500">Preview unavailable. The image record will be kept.</p>
                                    <div class="flex flex-1 flex-col gap-3 p-4">
                                        @if($safeUrl)<a href="{{ $source }}" target="_blank" rel="noopener noreferrer" class="inline-block self-start rounded text-sm font-semibold text-blue-700 underline decoration-blue-300 underline-offset-2 hover:decoration-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600">Preview {{ $isPhoto ? 'image' : 'attachment' }}</a>@endif
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-800 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
                                            <input type="radio" name="primary_image_id" value="{{ $image->id }}" class="text-blue-600 focus:ring-blue-600" @checked((int) $primaryId === $image->id) @disabled(in_array($image->id, $removedIds, true))>
                                            Set as primary
                                        </label>
                                        <input type="hidden" name="image_order[]" value="{{ $image->id }}">
                                        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-sm">
                                            <div class="flex gap-2">
                                                <button type="button" data-move="up" aria-label="Move image earlier" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-blue-600 disabled:opacity-40">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd"/></svg>
                                                    Move up
                                                </button>
                                                <button type="button" data-move="down" aria-label="Move image later" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-blue-600 disabled:opacity-40">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd"/></svg>
                                                    Move down
                                                </button>
                                            </div>
                                            <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 font-semibold text-red-700 transition hover:bg-red-50 has-[:checked]:bg-red-100">
                                                <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}" class="rounded border-slate-300 text-red-600 focus:ring-red-600" @checked(in_array($image->id, $removedIds, true))>
                                                Remove
                                            </label>
                                        </div>
                                        <p data-removal-note @if(!in_array($image->id, $removedIds, true)) hidden @endif class="rounded-md bg-red-100 px-3 py-2 text-xs font-medium text-red-800">Marked for removal. Uncheck to keep this image.</p>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">
                                    <p class="font-semibold text-slate-700">No images yet</p>
                                    <p class="mt-1">Add a photo or image URL below.</p>
                                </div>
                            @endforelse
                        </div>

                        @if($product->image_link || $product->product_file)<p class="mt-5 break-words rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Legacy media references are preserved: {{ $product->image_link }} {{ $product->product_file }}</p>@endif

                        <div class="mt-8 border-t border-slate-200 pt-6">
                            <h3 class="mb-4 text-sm font-bold text-slate-700">Add new images</h3>
                            <div id="new_images" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach(old('image_links', ['']) ?: [''] as $index => $url)
                                    @include('mi_app.designer_module.partials._new-image', ['index' => $index, 'url' => $url])
                                @endforeach
                            </div>
                            <p class="mt-4 text-xs text-slate-500">JPEG, PNG or WebP, up to 20 MB each. External images use HTTP or HTTPS. The first image becomes primary when none remain.</p>
                        </div>
                    </section>

                    {{-- Sticky save bar --}}
                    <footer class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-lg shadow-slate-900/10 backdrop-blur supports-[backdrop-filter]:bg-white/75 sm:px-6">
                        <p id="save_status" aria-live="polite" class="text-sm text-slate-500">Review your changes before saving.</p>
                        <div class="flex gap-3">
                            <a href="{{ route('mi_app.show', $product) }}" class="{{ $btnGhost }}">Cancel</a>
                            <button type="submit" data-save class="{{ $btnPrimary }}">Save changes</button>
                        </div>
                    </footer>
                </form>
            </div>

            <template id="new_image_template">@include('mi_app.designer_module.partials._new-image', ['index' => '__INDEX__', 'url' => ''])</template>
        </div>
    </div>
</x-mi_app>