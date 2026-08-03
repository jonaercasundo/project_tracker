<x-mi_app>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <style>
        .tx-console {
            --tx-bg: #F5F6F3;
            --tx-surface: #FFFFFF;
            --tx-ink: #171B1A;
            --tx-ink-soft: #616B66;
            --tx-ink-faint: #9AA39C;
            --tx-line: #E2E5DF;
            --tx-primary: #2F5D50;
            --tx-primary-ink: #FFFFFF;
            --tx-primary-soft: #E5EEE9;
            --tx-accent: #C7703C;
            --tx-accent-soft: #F5E7DB;
            --tx-danger: #B3432E;
            --tx-lvl-1: #2F5D50;
            --tx-lvl-1-soft: #E5EEE9;
            --tx-lvl-2: #35618C;
            --tx-lvl-2-soft: #E3EBF2;
            --tx-lvl-3: #7A4F98;
            --tx-lvl-3-soft: #ECE4F1;
            --tx-lvl-4: #C7703C;
            --tx-lvl-4-soft: #F5E7DB;
            --tx-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --tx-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --tx-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
        }
        .tx-console.dark {
            --tx-bg: #12151A;
            --tx-surface: #191D22;
            --tx-ink: #EDEFEA;
            --tx-ink-soft: #9AA39C;
            --tx-ink-faint: #6B746E;
            --tx-line: #262B31;
            --tx-primary-soft: #1C2723;
            --tx-lvl-1-soft: #1C2723;
            --tx-lvl-2-soft: #1A222B;
            --tx-lvl-3-soft: #221C29;
            --tx-lvl-4-soft: #2A2019;
        }

        .tx-display { font-family: var(--tx-font-display); letter-spacing: -0.01em; }
        .tx-mono { font-family: var(--tx-font-mono); letter-spacing: 0.02em; }

        .tx-shell { max-width: 68rem; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

        /* Header */
        .tx-header {
            display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end;
            justify-content: space-between; padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line); margin-bottom: 2rem;
        }
        .tx-eyebrow {
            display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase; color: var(--tx-ink-faint); margin-bottom: 0.6rem;
        }
        .tx-eyebrow a { color: var(--tx-ink-soft); text-decoration: none; }
        .tx-eyebrow a:hover { color: var(--tx-primary); }
        .tx-title { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.925rem; margin-top: 0.5rem; max-width: 38rem; }
        .tx-header-actions { display: flex; gap: 0.75rem; flex-shrink: 0; }
        .tx-back {
            display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid var(--tx-line);
            background: var(--tx-surface); color: var(--tx-ink); font-size: 0.8125rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border-radius: 999px; text-decoration: none; transition: all .15s ease;
        }
        .tx-back:hover { border-color: var(--tx-primary); color: var(--tx-primary); transform: translateX(-2px); }
        .tx-edit {
            display: inline-flex; align-items: center; gap: 0.5rem; border: none;
            background: var(--tx-primary); color: var(--tx-primary-ink); font-size: 0.8125rem; font-weight: 600;
            padding: 0.6rem 1.25rem; border-radius: 999px; text-decoration: none; transition: all .15s ease;
        }
        .tx-edit:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -10px var(--tx-primary); }

        /* Cards */
        .tx-card { background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 20px; margin-bottom: 1.5rem; overflow: hidden; }
        .tx-card-head { display: flex; align-items: center; gap: 0.85rem; padding: 1.35rem 1.75rem; border-bottom: 1px solid var(--tx-line); }
        .tx-card-icon {
            width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center;
            border-radius: 10px; flex-shrink: 0; font-family: var(--tx-font-mono); font-weight: 600; font-size: 0.8rem;
        }
        .tx-card-head h2 { font-family: var(--tx-font-display); font-size: 1.02rem; font-weight: 600; }
        .tx-card-head p { font-size: 0.78rem; color: var(--tx-ink-soft); margin-top: 0.15rem; }
        .tx-card-body { padding: 1.75rem; }

        .lvl-1 .tx-card-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .lvl-2 .tx-card-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .lvl-3 .tx-card-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }
        .lvl-4 .tx-card-icon { background: var(--tx-lvl-4-soft); color: var(--tx-lvl-4); }

        /* Data grid */
        .tx-data-grid { display: grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap: 1.5rem; }
        @media (min-width: 768px) { .tx-data-grid.cols-2 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (min-width: 768px) { .tx-data-grid.cols-3 { grid-template-columns: repeat(3, minmax(0,1fr)); } }
        @media (min-width: 640px) { .tx-data-grid.cols-4 { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .tx-datum-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-faint); display: flex; align-items: center; }
        .tx-datum-value { font-size: 0.95rem; font-weight: 600; color: var(--tx-ink); margin-top: 0.35rem; }
        .tx-datum-value.empty { color: var(--tx-ink-faint); font-weight: 500; }

        /* Identifier badges */
        .tx-id-badge {
            display: inline-flex; align-items: center; gap: 0.4rem; font-family: var(--tx-font-mono);
            font-weight: 600; font-size: 1rem; padding: 0.4rem 0.85rem; border-radius: 10px; margin-top: 0.4rem;
        }
        .tx-id-badge.draft { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .tx-id-badge.sku { background: var(--tx-primary-soft); color: var(--tx-primary); }

        /* Taxonomy dots */
        .tx-lvl-dot { display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; margin-right: 0.4rem; }

        /* Chips */
        .tx-chip { display: inline-flex; align-items: center; padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; margin: 0 0.5rem 0.5rem 0; }
        .tx-chip.material { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .tx-chip.color { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .tx-chip-empty { font-size: 0.8rem; color: var(--tx-ink-faint); }

        /* Dimension tables */
        .tx-dim-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .tx-dim-table td { padding: 0.65rem 0; border-bottom: 1px solid var(--tx-line); }
        .tx-dim-table tr:last-child td { border-bottom: none; }
        .tx-dim-table td.k { color: var(--tx-ink-soft); font-weight: 500; }
        .tx-dim-table td.v { text-align: right; font-family: var(--tx-font-mono); font-weight: 600; color: var(--tx-ink); }
        .tx-dims-row { display: grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap: 1.5rem; }
        @media (min-width: 1024px) { .tx-dims-row { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        .tx-dim-tag {
            display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 999px; background: var(--tx-bg);
            border: 1px solid var(--tx-line); padding: 0.3rem 0.65rem; font-size: 0.68rem; font-weight: 600; color: var(--tx-ink-soft);
        }

        /* Gallery */
        .tx-gallery-count {
            display: inline-flex; align-items: center; border-radius: 999px; background: var(--tx-bg);
            border: 1px solid var(--tx-line); padding: 0.45rem 1rem; font-size: 0.8rem; font-weight: 600; color: var(--tx-ink-soft);
        }
        .tx-gallery-preview-wrap { position: relative; }
        #galleryPreview {
            width: 100%; height: 420px; object-fit: contain; border-radius: 16px;
            border: 1px solid var(--tx-line); background: var(--tx-bg);
        }
        .tx-gallery-nav {
            position: absolute; top: 50%; transform: translateY(-50%); width: 2.75rem; height: 2.75rem;
            display: flex; align-items: center; justify-content: center; border-radius: 999px; border: none; cursor: pointer;
            background: var(--tx-surface); box-shadow: 0 12px 30px -12px rgba(23,27,26,0.35); color: var(--tx-ink); transition: all .15s ease;
        }
        .tx-gallery-nav:hover { color: var(--tx-primary); transform: translateY(-50%) scale(1.05); }
        .tx-gallery-nav.prev { left: 0.85rem; }
        .tx-gallery-nav.next { right: 0.85rem; }
        .tx-gallery-counter { margin-top: 0.85rem; text-align: center; font-size: 0.8rem; font-weight: 600; color: var(--tx-ink-soft); }
        .tx-gallery-thumbs { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 1rem; }
        .tx-thumb {
            border: 2px solid transparent; border-radius: 14px; overflow: hidden; cursor: pointer;
            background: var(--tx-surface); transition: all .15s ease; padding: 0;
        }
        .tx-thumb img { width: 6.5rem; height: 6.5rem; object-fit: cover; display: block; }
        .tx-thumb-label { font-size: 0.7rem; font-weight: 600; text-align: center; padding: 0.45rem; color: var(--tx-ink-soft); background: var(--tx-surface); }
        .tx-thumb.active { border-color: var(--tx-primary); box-shadow: 0 10px 24px -12px var(--tx-primary); }

        .tx-empty-gallery { text-align: center; padding: 4rem 1rem; }
        .tx-empty-gallery-icon { width: 3.5rem; height: 3.5rem; margin: 0 auto 1rem; color: var(--tx-ink-faint); }
        .tx-empty-gallery h3 { font-family: var(--tx-font-display); font-size: 1.05rem; font-weight: 600; color: var(--tx-ink); }
        .tx-empty-gallery p { font-size: 0.85rem; color: var(--tx-ink-faint); margin-top: 0.4rem; }
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Header --}}
            <div class="tx-header">
                <div>
                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.index') }}">Product Database</a>
                        <span>/</span>
                        <span>{{ $product->item_name }}</span>
                    </div>
                    <h1 class="tx-title tx-display">Product Details</h1>
                    <p class="tx-subtitle">View complete product information.</p>
                </div>

                <div class="tx-header-actions">
                    <a href="{{ route('mi_app.index') }}" class="tx-back">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        Back
                    </a>
                    <a href="{{ route('mi_app.edit', $product->product_id) }}" class="tx-edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        Edit Product
                    </a>
                </div>
            </div>

            {{-- Product Identification --}}
            <div class="tx-card">
                <div class="tx-card-head">
                    <span class="tx-card-icon" style="background: var(--tx-line); color: var(--tx-ink-soft);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                    <div>
                        <h2>Product Identification</h2>
                        <p>Draft and SKU reference numbers</p>
                    </div>
                </div>
                <div class="tx-card-body">
                    <div class="tx-data-grid cols-3">
                        <div>
                            <p class="tx-datum-label">Draft Number</p>
                            <p class="tx-id-badge draft tx-mono">{{ $product->draft_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="tx-datum-label">SKU Number</p>
                            <p class="tx-id-badge sku tx-mono">{{ $product->sku ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Taxonomy --}}
            <div class="tx-card lvl-1">
                <div class="tx-card-head">
                    <span class="tx-card-icon">01</span>
                    <div>
                        <h2>Taxonomy</h2>
                        <p>Where this item sits in the catalog hierarchy</p>
                    </div>
                </div>
                <div class="tx-card-body">
                    <div class="tx-data-grid cols-4">
                        <div>
                            <p class="tx-datum-label"><span class="tx-lvl-dot" style="background: var(--tx-lvl-1);"></span>Category</p>
                            <p class="tx-datum-value {{ $product->category->name ?? null ? '' : 'empty' }}">
                                {{ $product->category->name ?? 'Not set' }}
                            </p>
                        </div>
                        <div>
                            <p class="tx-datum-label"><span class="tx-lvl-dot" style="background: var(--tx-lvl-2);"></span>Sub Category</p>
                            <p class="tx-datum-value {{ $product->subCategory->name ?? null ? '' : 'empty' }}">
                                {{ $product->subCategory->name ?? 'Not set' }}
                            </p>
                        </div>
                        <div>
                            <p class="tx-datum-label"><span class="tx-lvl-dot" style="background: var(--tx-lvl-3);"></span>Product Type</p>
                            <p class="tx-datum-value {{ $product->productType->name ?? null ? '' : 'empty' }}">
                                {{ $product->productType->name ?? 'Not set' }}
                            </p>
                        </div>
                        <div>
                            <p class="tx-datum-label"><span class="tx-lvl-dot" style="background: var(--tx-lvl-4);"></span>Collection</p>
                            <p class="tx-datum-value {{ $product->collection->name ?? null ? '' : 'empty' }}">
                                {{ $product->collection->name ?? 'Not set' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- General Information --}}
            <div class="tx-card lvl-2">
                <div class="tx-card-head">
                    <span class="tx-card-icon">02</span>
                    <div>
                        <h2>General Information</h2>
                        <p>Basic identity of the product</p>
                    </div>
                </div>
                <div class="tx-card-body">
                    <div class="tx-data-grid cols-3">
                        <div>
                            <p class="tx-datum-label">Item Name</p>
                            <p class="tx-datum-value">{{ $product->item_name }}</p>
                        </div>
                        <div>
                            <p class="tx-datum-label">Type of Sample</p>
                            <p class="tx-datum-value {{ $product->type_of_sample ?? null ? '' : 'empty' }}">
                                {{ $product->type_of_sample ?? 'Not set' }}
                            </p>
                        </div>
                        <div>
                            <p class="tx-datum-label">Designed By</p>
                            <p class="tx-datum-value {{ $product->designed_by ?? null ? '' : 'empty' }}">
                                {{ $product->designed_by ?? 'Not set' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Materials & Colors --}}
            <div class="tx-card lvl-3">
                <div class="tx-card-head">
                    <span class="tx-card-icon">03</span>
                    <div>
                        <h2>Materials & Colors</h2>
                        <p>Physical composition and finish options</p>
                    </div>
                </div>
                <div class="tx-card-body">
                    <div class="tx-data-grid cols-2">
                        <div>
                            <p class="tx-datum-label" style="margin-bottom: 0.75rem;">Materials</p>
                            @php $materialList = json_decode($product->materials ?? '[]', true) ?: []; @endphp
                            @forelse($materialList as $material)
                                <span class="tx-chip material">{{ $material }}</span>
                            @empty
                                <span class="tx-chip-empty">No materials listed</span>
                            @endforelse
                        </div>
                        <div>
                            <p class="tx-datum-label" style="margin-bottom: 0.75rem;">Colors</p>
                            @php $colorList = json_decode($product->color ?? '[]', true) ?: []; @endphp
                            @forelse($colorList as $color)
                                <span class="tx-chip color">{{ $color }}</span>
                            @empty
                                <span class="tx-chip-empty">No colors listed</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dimensions --}}
            <div class="tx-dims-row">
                <div class="tx-card">
                    <div class="tx-card-head">
                        <span class="tx-card-icon" style="background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M6 24 6 10 14 6 26 10 26 24 18 28 6 24Z" stroke-linejoin="round"/>
                                <path d="M6 10 18 14 26 10" stroke-linejoin="round"/>
                                <path d="M18 14 18 28" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Product Dimensions</h2>
                            <p>Core measurements of the physical item</p>
                        </div>
                    </div>
                    <div class="tx-card-body">
                        <table class="tx-dim-table">
                            <tr><td class="k">Height</td><td class="v">{{ $product->product_height ?? '—' }} cm</td></tr>
                            <tr><td class="k">Width</td><td class="v">{{ $product->product_width ?? '—' }} cm</td></tr>
                            <tr><td class="k">Length</td><td class="v">{{ $product->product_length ?? '—' }} cm</td></tr>
                            <tr><td class="k">Depth</td><td class="v">{{ $product->product_depth ?? '—' }} cm</td></tr>
                        </table>
                    </div>
                </div>

                <div class="tx-card">
                    <div class="tx-card-head">
                        <span class="tx-card-icon" style="background: var(--tx-accent-soft); color: var(--tx-accent);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M3 7.5v9l9 4.5 9-4.5v-9M3 7.5l9 4.5 9-4.5" />
                            </svg>
                        </span>
                        <div>
                            <h2>Carton Dimensions</h2>
                            <p>Packaging footprint for shipping and storage</p>
                        </div>
                    </div>
                    <div class="tx-card-body">
                        <table class="tx-dim-table">
                            <tr><td class="k">Height</td><td class="v">{{ $product->carton_height ?? '—' }} cm</td></tr>
                            <tr><td class="k">Width</td><td class="v">{{ $product->carton_width ?? '—' }} cm</td></tr>
                            <tr><td class="k">Length</td><td class="v">{{ $product->carton_length ?? '—' }} cm</td></tr>
                            <tr><td class="k">Depth</td><td class="v">{{ $product->carton_depth ?? '—' }} cm</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Product Images Gallery --}}
            <div class="tx-card">
                <div class="tx-card-head" style="justify-content: space-between; flex-wrap: wrap;">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <span class="tx-card-icon" style="background: var(--tx-primary-soft); color: var(--tx-primary);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        </span>
                        <div>
                            <h2>Product Images</h2>
                            <p>Uploaded product photo and image links</p>
                        </div>
                    </div>

                    @php
                        $images = $product->images->map(function ($image) {
                            return [
                                'title' => $image->image_type === 'upload' ? 'Uploaded Image' : 'Image Link',
                                'url' => $image->image_type === 'upload'
                                    ? asset('storage/'.$image->image_path)
                                    : $image->image_url,
                            ];
                        })->filter(function ($image) {
                            return !empty($image['url']);
                        })->toArray();

                        if (empty($images)) {
                            if (!empty($product->product_file)) {
                                $images[] = [
                                    'title' => 'Uploaded Image',
                                    'url' => asset('storage/'.$product->product_file),
                                ];
                            }

                            if (!empty($product->image_link)) {
                                $images[] = [
                                    'title' => 'Image Link',
                                    'url' => $product->image_link,
                                ];
                            }
                        }

                        $count = count($images);
                    @endphp

                    <span class="tx-gallery-count">
                        {{ $count }} Image{{ $count != 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="tx-card-body">

                    @if(count($images))

                        {{-- Large Preview --}}
                        <div class="tx-gallery-preview-wrap">
                            <img id="galleryPreview" src="{{ $images[0]['url'] }}" alt="{{ $product->item_name }}">

                            @if(count($images) > 1)
                                <button type="button" onclick="previousImage()" class="tx-gallery-nav prev" aria-label="Previous image">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <button type="button" onclick="nextImage()" class="tx-gallery-nav next" aria-label="Next image">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            @endif
                        </div>

                        {{-- Counter --}}
                        <div class="tx-gallery-counter">
                            <span id="galleryCounter" class="tx-mono">1 / {{ count($images) }}</span>
                        </div>

                        {{-- Thumbnails --}}
                        <div class="tx-gallery-thumbs">
                            @foreach($images as $index => $image)
                                <button type="button" onclick="showImage({{ $index }})" class="tx-thumb gallery-thumb" data-index="{{ $index }}">
                                    <img src="{{ $image['url'] }}" alt="{{ $image['title'] }}">
                                    <div class="tx-thumb-label">{{ $image['title'] }}</div>
                                </button>
                            @endforeach
                        </div>

                    @else

                        <div class="tx-empty-gallery">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tx-empty-gallery-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16l4-4a2 2 0 012.828 0L16 18m-2-2l1-1a2 2 0 012.828 0L21 18"/>
                            </svg>
                            <h3>No Images Available</h3>
                            <p>This product has no uploaded image or image link.</p>
                        </div>

                    @endif
                </div>
            </div>

        </div>
    </div>

    @if(count($images))
    <script>
        const galleryImages = @json(array_column($images, 'url'));
        let currentImage = 0;

        const preview = document.getElementById('galleryPreview');
        const counter = document.getElementById('galleryCounter');

        function updateGallery() {
            preview.src = galleryImages[currentImage];
            counter.innerHTML = (currentImage + 1) + " / " + galleryImages.length;

            document.querySelectorAll('.gallery-thumb').forEach(function (item) {
                item.classList.remove('active');
            });

            var activeThumb = document.querySelector('[data-index="' + currentImage + '"]');
            if (activeThumb) activeThumb.classList.add('active');
        }

        function showImage(index) {
            currentImage = index;
            updateGallery();
        }

        function nextImage() {
            currentImage++;
            if (currentImage >= galleryImages.length) currentImage = 0;
            updateGallery();
        }

        function previousImage() {
            currentImage--;
            if (currentImage < 0) currentImage = galleryImages.length - 1;
            updateGallery();
        }

        updateGallery();
    </script>
    @endif
</x-mi_app>