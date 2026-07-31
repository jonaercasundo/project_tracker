<x-mi_app>

<div class="max-w-7xl mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Product Details
            </h1>
            <p class="text-slate-500 mt-1">
                View complete product information.
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('mi_app.index') }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50">
                Back
            </a>

            <a href="{{ route('mi_app.edit', $product->product_id) }}"
               class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Edit Product
            </a>
        </div>
    </div>

{{-- Product Identification --}}
<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">

    <h2 class="text-lg font-semibold text-slate-900 mb-5">
        Product Identification
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Draft Number --}}
        <div>
            <p class="text-xs uppercase text-slate-500">
                Draft Number
            </p>

            <p class="font-semibold mt-1 font-mono text-blue-600">
                {{ $product->draft_number ?? '-' }}
            </p>
        </div>


        {{-- SKU --}}
        <div>
            <p class="text-xs uppercase text-slate-500">
                SKU Number
            </p>

            <p class="font-semibold mt-1 font-mono text-emerald-600">
                {{ $product->sku ?? '-' }}
            </p>
        </div>


        {{-- Item Code --}}
        <div>
            <p class="text-xs uppercase text-slate-500">
                Item Code
            </p>

            <p class="font-semibold mt-1 font-mono">
                {{ $product->item_code ?? '-' }}
            </p>
        </div>

    </div>

</div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">

        <h2 class="text-lg font-semibold text-slate-900 mb-5">
            Taxonomy
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div>
                <p class="text-xs uppercase text-slate-500">Category</p>
                <p class="font-semibold mt-1">
                    {{ $product->category->name ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-xs uppercase text-slate-500">Sub Category</p>
                <p class="font-semibold mt-1">
                    {{ $product->subCategory->name ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-xs uppercase text-slate-500">Product Type</p>
                <p class="font-semibold mt-1">
                    {{ $product->productType->name ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-xs uppercase text-slate-500">Collection</p>
                <p class="font-semibold mt-1">
                    {{ $product->collection->name ?? '-' }}
                </p>
            </div>

        </div>

    </div>

    {{-- General --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">

        <h2 class="text-lg font-semibold mb-5">
            General Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <p class="text-xs uppercase text-slate-500">Item Name</p>
                <p class="font-semibold">
                    {{ $product->item_name }}
                </p>
            </div>

            <div>
                <p class="text-xs uppercase text-slate-500">Type of Sample</p>
                <p>{{ $product->type_of_sample ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs uppercase text-slate-500">Designed By</p>
                <p>{{ $product->designed_by ?? '-' }}</p>
            </div>

        </div>

    </div>

    {{-- Attributes --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">

        <h2 class="text-lg font-semibold mb-5">
            Materials & Colors
        </h2>

        <div class="grid md:grid-cols-2 gap-8">

            <div>

                <p class="text-xs uppercase text-slate-500 mb-3">
                    Materials
                </p>

                @foreach(json_decode($product->materials ?? '[]', true) as $material)
                    <span class="inline-flex px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm mr-2 mb-2">
                        {{ $material }}
                    </span>
                @endforeach

            </div>

            <div>

                <p class="text-xs uppercase text-slate-500 mb-3">
                    Colors
                </p>

                @foreach(json_decode($product->color ?? '[]', true) as $color)
                    <span class="inline-flex px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm mr-2 mb-2">
                        {{ $color }}
                    </span>
                @endforeach

            </div>

        </div>

    </div>

    {{-- Dimensions --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">

        <div class="bg-white rounded-xl border border-slate-200 p-6">

            <h2 class="text-lg font-semibold mb-5">
                Product Dimensions
            </h2>

            <table class="w-full">

                <tr>
                    <td class="py-2 text-slate-500">Height</td>
                    <td>{{ $product->product_height }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Width</td>
                    <td>{{ $product->product_width }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Length</td>
                    <td>{{ $product->product_length }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Depth</td>
                    <td>{{ $product->product_depth }} cm</td>
                </tr>

            </table>

        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">

            <h2 class="text-lg font-semibold mb-5">
                Carton Dimensions
            </h2>

            <table class="w-full">

                <tr>
                    <td class="py-2 text-slate-500">Height</td>
                    <td>{{ $product->carton_height }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Width</td>
                    <td>{{ $product->carton_width }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Length</td>
                    <td>{{ $product->carton_length }} cm</td>
                </tr>

                <tr>
                    <td class="py-2 text-slate-500">Depth</td>
                    <td>{{ $product->carton_depth }} cm</td>
                </tr>

            </table>

        </div>

    </div>

{{-- ========================= --}}
{{-- Product Images Gallery --}}
{{-- ========================= --}}
<div class="bg-white rounded-xl border border-slate-200 p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

        <div>
            <h2 class="text-xl font-semibold text-slate-900">
                Product Images
            </h2>

            <p class="text-sm text-slate-500 mt-1">
                Uploaded product photo and image links.
            </p>
        </div>

        <span
            class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">

            @php
                $count = 0;
                if(!empty($product->product_file)) $count++;
                if(!empty($product->image_link)) $count++;
            @endphp

            {{ $count }} Image{{ $count != 1 ? 's' : '' }}

        </span>

    </div>

    @php

        $images = [];

        if(!empty($product->product_file)){
            $images[] = [
                'title' => 'Uploaded Image',
                'url'   => asset('storage/'.$product->product_file)
            ];
        }

        if(!empty($product->image_link)){
            $images[] = [
                'title' => 'Image Link',
                'url'   => $product->image_link
            ];
        }

    @endphp

    @if(count($images))

        {{-- Large Preview --}}
        <div class="relative">

            <img
                id="galleryPreview"
                src="{{ $images[0]['url'] }}"
                class="w-full h-[520px] object-contain rounded-xl border border-slate-200 bg-slate-50">

            {{-- Previous --}}
            @if(count($images) > 1)
            <button
                type="button"
                onclick="previousImage()"
                class="absolute left-3 top-1/2 -translate-y-1/2 bg-white shadow-lg rounded-full w-12 h-12 flex items-center justify-center hover:bg-slate-100">

                &#10094;

            </button>

            {{-- Next --}}
            <button
                type="button"
                onclick="nextImage()"
                class="absolute right-3 top-1/2 -translate-y-1/2 bg-white shadow-lg rounded-full w-12 h-12 flex items-center justify-center hover:bg-slate-100">

                &#10095;

            </button>
            @endif

        </div>

        {{-- Counter --}}
        <div class="mt-3 text-center text-sm text-slate-500">

            <span id="galleryCounter">
                1 / {{ count($images) }}
            </span>

        </div>

        {{-- Thumbnails --}}
        <div class="mt-6 flex gap-4 flex-wrap">

            @foreach($images as $index => $image)

                <button
                    type="button"
                    onclick="showImage({{ $index }})"
                    class="gallery-thumb border-2 border-transparent rounded-xl overflow-hidden hover:border-blue-500 transition"
                    data-index="{{ $index }}">

                    <img
                        src="{{ $image['url'] }}"
                        class="w-28 h-28 object-cover">

                    <div class="bg-white px-2 py-2 text-xs text-center">

                        {{ $image['title'] }}

                    </div>

                </button>

            @endforeach

        </div>

    @else

        <div class="py-20 text-center">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-16 w-16 mx-auto text-slate-300"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      d="M3 16l4-4a2 2 0 012.828 0L16 18m-2-2l1-1a2 2 0 012.828 0L21 18"/>

            </svg>

            <h3 class="mt-4 text-lg font-semibold text-slate-700">
                No Images Available
            </h3>

            <p class="text-slate-400 mt-2">
                This product has no uploaded image or image link.
            </p>

        </div>

    @endif

</div>

@if(count($images))

<script>

const galleryImages = @json(array_column($images,'url'));

let currentImage = 0;

const preview = document.getElementById('galleryPreview');

const counter = document.getElementById('galleryCounter');

function updateGallery(){

    preview.src = galleryImages[currentImage];

    counter.innerHTML = (currentImage + 1) + " / " + galleryImages.length;

    document.querySelectorAll('.gallery-thumb').forEach(function(item){

        item.classList.remove(
            'border-blue-600',
            'shadow-lg'
        );

    });

    document.querySelector('[data-index="'+currentImage+'"]')
        .classList.add(
            'border-blue-600',
            'shadow-lg'
        );

}

function showImage(index){

    currentImage = index;

    updateGallery();

}

function nextImage(){

    currentImage++;

    if(currentImage >= galleryImages.length){

        currentImage = 0;

    }

    updateGallery();

}

function previousImage(){

    currentImage--;

    if(currentImage < 0){

        currentImage = galleryImages.length - 1;

    }

    updateGallery();

}

updateGallery();

</script>

@endif

</div>

</x-mi_app>