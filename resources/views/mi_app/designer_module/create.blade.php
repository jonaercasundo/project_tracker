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

        .tx-shell { max-width: 68rem; margin: 0 auto; padding: 2.5rem 1.5rem 8rem; }

        /* Header */
        .tx-header {
            display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end;
            justify-content: space-between; padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line); margin-bottom: 1.75rem;
        }
        .tx-eyebrow {
            display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase; color: var(--tx-ink-faint); margin-bottom: 0.6rem;
        }
        .tx-eyebrow a { color: var(--tx-ink-soft); text-decoration: none; }
        .tx-eyebrow a:hover { color: var(--tx-primary); }
        .tx-title { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.925rem; margin-top: 0.5rem; max-width: 38rem; }
        .tx-back {
            display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid var(--tx-line);
            background: var(--tx-surface); color: var(--tx-ink); font-size: 0.8125rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border-radius: 999px; text-decoration: none; transition: all .15s ease;
        }
        .tx-back:hover { border-color: var(--tx-primary); color: var(--tx-primary); transform: translateX(-2px); }

        /* Progress */
        .tx-progress-wrap {
            display: flex; align-items: center; gap: 1rem;
            background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 999px;
            padding: 0.55rem 0.6rem 0.55rem 1.1rem; margin-bottom: 2rem;
        }
        .tx-progress-track { flex: 1 1 auto; height: 6px; border-radius: 999px; background: var(--tx-line); overflow: hidden; }
        #progress_bar { height: 100%; width: 0%; border-radius: 999px; background: var(--tx-primary); transition: width .25s ease; }
        #progress_label { font-size: 0.72rem; font-weight: 600; color: var(--tx-ink-soft); white-space: nowrap; padding-right: 0.35rem; }

        /* Cards */
        .tx-card {
            background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 20px;
            margin-bottom: 1.5rem; overflow: hidden;
        }
        @media (prefers-reduced-motion: no-preference) {
            .tx-card { animation: tx-reveal 0.45s ease-out both; }
            .tx-card:nth-of-type(1) { animation-delay: 0ms; }
            .tx-card:nth-of-type(2) { animation-delay: 60ms; }
            .tx-card:nth-of-type(3) { animation-delay: 120ms; }
            @keyframes tx-reveal { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        }
        .tx-card-head { display: flex; align-items: center; gap: 0.85rem; padding: 1.35rem 1.75rem; border-bottom: 1px solid var(--tx-line); }
        .tx-card-icon {
            width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center;
            border-radius: 10px; flex-shrink: 0; font-family: var(--tx-font-mono); font-weight: 600; font-size: 0.8rem;
        }
        .tx-card-head h2 { font-family: var(--tx-font-display); font-size: 1.02rem; font-weight: 600; }
        .tx-card-head p { font-size: 0.78rem; color: var(--tx-ink-soft); margin-top: 0.15rem; }
        .tx-card-body { padding: 1.75rem; display: grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap: 1.35rem; }
        @media (min-width: 768px) { .tx-card-body.cols-2 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (min-width: 1024px) { .tx-card-body.cols-4 { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .col-span-2 { grid-column: span 1; }
        @media (min-width: 1024px) { .col-span-2 { grid-column: span 2; } }

        .lvl-1 .tx-card-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .lvl-2 .tx-card-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .lvl-3 .tx-card-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }

        .tx-taxonomy-preview {
            display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
            margin: 0 1.75rem 1.75rem; padding: 0.9rem 1.1rem;
            border: 1px dashed var(--tx-line); border-radius: 12px; background: var(--tx-bg);
        }
        .tx-taxonomy-preview-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-faint); }
        #taxonomy-preview-path { font-size: 0.8rem; font-weight: 600; color: var(--tx-ink); }

        /* Fields */
        .tx-label { display: block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-soft); margin-bottom: 0.55rem; }
        .tx-lvl-dot { display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; margin-right: 0.4rem; }
        .tx-required { color: var(--tx-danger); }
        .tx-field {
            width: 100%; border: 1px solid var(--tx-line); background: var(--tx-bg); color: var(--tx-ink);
            font-size: 0.875rem; padding: 0.72rem 1rem; border-radius: 12px; outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .tx-field::placeholder { color: var(--tx-ink-faint); }
        .tx-field:focus { border-color: var(--tx-primary); background: var(--tx-surface); box-shadow: 0 0 0 4px var(--tx-primary-soft); }
        .tx-field.field-invalid { border-color: var(--tx-danger) !important; }
        .tx-field.field-invalid:focus { box-shadow: 0 0 0 4px var(--tx-accent-soft); }
        .tx-select-wrap { position: relative; }
        .tx-select-wrap select { appearance: none; padding-right: 2.5rem; }
        .tx-select-wrap svg { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none; }
        .tx-error { display: flex; align-items: center; gap: 0.3rem; color: var(--tx-danger); font-size: 0.75rem; font-weight: 600; margin-top: 0.45rem; }
        .tx-error svg { width: 0.9rem; height: 0.9rem; flex-shrink: 0; }
        .tx-hint { font-size: 0.72rem; color: var(--tx-ink-faint); margin-bottom: 0.75rem; }

        .tx-swatch-input { position: relative; }
        #color_swatch { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); height: 1rem; width: 1rem; border-radius: 999px; border: 1px solid var(--tx-line); background: var(--tx-line); transition: background-color .15s ease; }

        /* Sub-panels: product / carton dimensions */
        .tx-subpanel { border: 1px solid var(--tx-line); border-radius: 16px; padding: 1.25rem; background: var(--tx-bg); }
        .tx-subpanel + .tx-subpanel { margin-top: 1.25rem; }
        .tx-subpanel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .tx-subpanel-head h3 { font-family: var(--tx-font-display); font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: var(--tx-ink-soft); }
        .tx-subpanel-head p { font-size: 0.7rem; color: var(--tx-ink-faint); margin-top: 0.2rem; }
        .tx-subpanel-tag {
            display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 999px; background: var(--tx-surface);
            border: 1px solid var(--tx-line); padding: 0.3rem 0.65rem; font-size: 0.68rem; font-weight: 600; color: var(--tx-ink-soft); white-space: nowrap;
        }
        .tx-dims-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
        @media (min-width: 640px) { .tx-dims-grid { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .tx-dim-label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--tx-ink-soft); margin-bottom: 0.4rem; }
        .tx-dim-input-wrap { position: relative; }
        .tx-dim-input-wrap input { padding-right: 2.4rem; }
        .tx-dim-unit { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); font-size: 0.7rem; font-weight: 600; color: var(--tx-ink-faint); }

        /* Dropzone */
        .tx-dropzone {
            position: relative; border: 2px dashed var(--tx-line); border-radius: 18px; padding: 2.25rem 1.5rem;
            background: var(--tx-bg); cursor: pointer; transition: all .15s ease; text-align: center;
        }
        .tx-dropzone.drag-active { border-color: var(--tx-primary); background: var(--tx-primary-soft); }
        .tx-dropzone-empty { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
        .tx-dropzone-icon { width: 3rem; height: 3rem; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--tx-primary-soft); color: var(--tx-primary); }
        .tx-dropzone-empty p.tx-dz-title { font-size: 0.875rem; font-weight: 600; color: var(--tx-ink); }
        .tx-dropzone-empty p.tx-dz-sub { font-size: 0.72rem; color: var(--tx-ink-faint); margin-top: 0.2rem; }
        .tx-dropzone-filled { display: none; flex-direction: column; gap: 1rem; text-align: left; }
        .tx-file-row { display: flex; align-items: center; gap: 0.85rem; }
        .tx-file-thumb { height: 3.5rem; width: 3.5rem; flex-shrink: 0; overflow: hidden; border-radius: 10px; border: 1px solid var(--tx-line); background: var(--tx-surface); display: flex; align-items: center; justify-content: center; color: var(--tx-ink-faint); }
        .tx-file-thumb img { height: 100%; width: 100%; object-fit: cover; }
        .tx-file-meta { flex: 1; min-width: 0; }
        .tx-file-meta p.name { font-size: 0.875rem; font-weight: 600; color: var(--tx-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tx-file-meta p.size { font-size: 0.72rem; color: var(--tx-ink-faint); margin-top: 0.15rem; }
        .tx-file-remove { display: inline-flex; align-items: center; justify-content: center; height: 2rem; width: 2rem; border-radius: 8px; border: none; background: transparent; color: var(--tx-ink-faint); cursor: pointer; transition: all .15s ease; }
        .tx-file-remove:hover { background: var(--tx-accent-soft); color: var(--tx-danger); }

        /* Footer */
        .tx-footer { position: sticky; bottom: 1rem; z-index: 10; margin-top: 2rem; }
        .tx-footer-inner {
            display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;
            border: 1px solid var(--tx-line); background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);
            border-radius: 18px; padding: 1rem 1.25rem; box-shadow: 0 20px 45px -25px rgba(23,27,26,0.35);
        }
        .tx-console.dark .tx-footer-inner { background: rgba(25,29,34,0.92); }
        .tx-btn-ghost {
            border-radius: 12px; padding: 0.65rem 1.25rem; font-size: 0.85rem; font-weight: 600;
            color: var(--tx-ink-soft); text-decoration: none; transition: all .15s ease;
        }
        .tx-btn-ghost:hover { background: var(--tx-bg); color: var(--tx-ink); }
        .tx-btn-submit {
            display: inline-flex; align-items: center; gap: 0.55rem; border: none; cursor: pointer;
            border-radius: 12px; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 600;
            background: var(--tx-primary); color: var(--tx-primary-ink); transition: all .15s ease;
        }
        .tx-btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 24px -10px var(--tx-primary); }
        .tx-btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .tx-btn-submit svg.spin { animation: tx-spin 0.8s linear infinite; }
        @keyframes tx-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Page Header --}}
            <div class="tx-header">
                <div>
                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.index') }}">Product Database</a>
                        <span>/</span>
                        <span>New Product</span>
                    </div>
                    <h1 class="tx-title tx-display">Create Product</h1>
                    <p class="tx-subtitle">Fill in the specifications, dimensions, and details for the new product item.</p>
                </div>
                <a href="{{ route('mi_app.index') }}" class="tx-back">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Database
                </a>
            </div>

            {{-- Required-field progress --}}
            <div class="tx-progress-wrap">
                <span id="progress_label" class="tx-mono">0 / 0 required fields</span>
                <div class="tx-progress-track"><div id="progress_bar"></div></div>
            </div>

            {{-- Main Form Container --}}
            <form method="POST" action="{{ route('mi_app.store') }}" enctype="multipart/form-data" id="product_form" novalidate>
                @csrf

                {{-- SECTION 1: Taxonomy --}}
                <div class="tx-card lvl-1" id="taxonomy-section">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">01</span>
                        <div>
                            <h2>Taxonomy</h2>
                            <p>Place this product on the Category → Sub Category → Sub Sub Category → Collection ladder.</p>
                        </div>
                    </div>

                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="category_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-1);"></span>Category <span class="tx-required">*</span>
                            </label>
                            <div class="tx-select-wrap">
                                <select id="category_id" name="category_id" required data-required data-cascade-target="sub_category_id" class="tx-field">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('category_id')
                                <p class="tx-error">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="sub_category_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-2);"></span>Sub Category <span class="tx-required">*</span>
                            </label>
                            <div class="tx-select-wrap">
                                <select id="sub_category_id" name="sub_category_id" required data-required data-cascade-target="product_type_id" class="tx-field">
                                    <option value="">-- Select Category First --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->category_id }}"
                                            class="{{ old('category_id') == $subCategory->category_id ? '' : 'hidden' }}"
                                            {{ old('sub_category_id') == $subCategory->id ? 'selected' : '' }}>
                                            {{ $subCategory->code }} - {{ $subCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('sub_category_id')
                                <p class="tx-error">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="product_type_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-3);"></span>Sub Sub Category
                            </label>
                            <div class="tx-select-wrap">
                                <select id="product_type_id" name="product_type_id" data-cascade-target="collection_id" class="tx-field">
                                    <option value="">-- Select Sub Category First --</option>
                                    @foreach($productTypes as $productType)
                                        <option value="{{ $productType->id }}" data-parent="{{ $productType->sub_category_id }}"
                                            class="{{ old('sub_category_id') == $productType->sub_category_id ? '' : 'hidden' }}"
                                            {{ old('product_type_id') == $productType->id ? 'selected' : '' }}>
                                            {{ $productType->code }} - {{ $productType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('product_type_id') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="collection_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-4);"></span>Collection
                            </label>
                            <div class="tx-select-wrap">
                                <select id="collection_id" name="collection_id" class="tx-field">
                                    <option value="">-- Select Sub Sub Category First --</option>
                                    @foreach($collections as $collection)
                                        <option value="{{ $collection->id }}" data-parent="{{ $collection->product_type_id }}"
                                            class="{{ old('product_type_id') == $collection->product_type_id ? '' : 'hidden' }}"
                                            {{ old('collection_id') == $collection->id ? 'selected' : '' }}>
                                            {{ $collection->code }} - {{ $collection->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('collection_id') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="tx-taxonomy-preview" id="taxonomy-preview">
                        <span class="tx-taxonomy-preview-label">SKU preview</span>
                        <span id="taxonomy-preview-path" class="tx-mono">Select a category to begin</span>
                    </div>
                </div>

                {{-- SECTION 2: General Information --}}
                <div class="tx-card lvl-1">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">02</span>
                        <div>
                            <h2>General Information</h2>
                            <p>Basic identity of the product</p>
                        </div>
                    </div>

                    <div class="tx-card-body cols-4">
                        <div class="col-span-2">
                            <label for="item_name" class="tx-label">Item Name <span class="tx-required">*</span></label>
                            <input type="text" id="item_name" name="item_name" value="{{ old('item_name') }}" placeholder="e.g. Ergonomic Office Desk" required data-required class="tx-field">
                            @error('item_name')
                                <p class="tx-error">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="type_of_sample" class="tx-label">Type of Sample <span class="tx-required">*</span></label>
                            <input type="text" id="type_of_sample" name="type_of_sample" value="{{ old('type_of_sample') }}" placeholder="e.g. Prototype" required data-required class="tx-field">
                            @error('type_of_sample')
                                <p class="tx-error">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="classification" class="tx-label">Classification <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select id="classification" name="classification" required data-required class="tx-field">
                                    <option value="Available" {{ old('classification', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                                    <option value="Assigned" {{ old('classification') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                    <option value="Repair" {{ old('classification') == 'Repair' ? 'selected' : '' }}>Repair</option>
                                    <option value="Disposed" {{ old('classification') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('classification') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <label for="designed_by" class="tx-label">Designed By</label>
                            <input type="text" id="designed_by" name="designed_by" value="{{ old('designed_by') }}" placeholder="Designer full name" class="tx-field">
                            @error('designed_by') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Attributes & Product Dimensions --}}
                <div class="tx-card lvl-2">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">03</span>
                        <div>
                            <h2>Attributes & Dimensions</h2>
                            <p>Physical properties and measurements</p>
                        </div>
                    </div>

                    <div class="tx-card-body">
                        <div class="tx-card-body cols-4" style="padding: 0;">
                            <div>
                                <label for="materials" class="tx-label">Materials <span class="tx-required">*</span></label>
                                <input type="text" id="materials" name="materials" value="{{ old('materials') }}" placeholder="e.g. Oak, Aluminum" required data-required class="tx-field">
                                @error('materials')
                                    <p class="tx-error">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="type" class="tx-label">Type</label>
                                <input type="text" id="type" name="type" value="{{ old('type') }}" placeholder="Product variant type" class="tx-field">
                                @error('type') <p class="tx-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-2">
                                <label for="color" class="tx-label">Color</label>
                                <div class="tx-swatch-input">
                                    <input type="text" id="color" name="color" value="{{ old('color') }}" placeholder="e.g. Matte Black" class="tx-field" style="padding-right: 2.5rem;">
                                    <span id="color_swatch" aria-hidden="true"></span>
                                </div>
                                @error('color') <p class="tx-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <div class="tx-subpanel">
                                <div class="tx-subpanel-head">
                                    <div>
                                        <h3>Product dimensions</h3>
                                        <p>Core measurements for the physical item.</p>
                                    </div>
                                    <span class="tx-subpanel-tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M6 24 6 10 14 6 26 10 26 24 18 28 6 24Z" stroke-linejoin="round"/>
                                            <path d="M6 10 18 14 26 10" stroke-linejoin="round"/>
                                            <path d="M18 14 18 28" stroke-linejoin="round"/>
                                        </svg>
                                        H × W × L × D
                                    </span>
                                </div>
                                <div class="tx-dims-grid">
                                    <div>
                                        <label for="product_height" class="tx-dim-label">Height <span class="tx-required">*</span></label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="product_height" name="product_height" value="{{ old('product_height') }}" placeholder="45" required data-required class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('product_height')
                                            <p class="tx-error">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="product_width" class="tx-dim-label">Width</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="product_width" name="product_width" value="{{ old('product_width') }}" placeholder="60" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('product_width') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="product_length" class="tx-dim-label">Length</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="product_length" name="product_length" value="{{ old('product_length') }}" placeholder="120" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('product_length') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="product_depth" class="tx-dim-label">Depth</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="product_depth" name="product_depth" value="{{ old('product_depth') }}" placeholder="30" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('product_depth') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tx-subpanel">
                                <div class="tx-subpanel-head">
                                    <div>
                                        <h3>Carton dimensions</h3>
                                        <p>Packaging footprint for shipping and storage.</p>
                                    </div>
                                    <span class="tx-subpanel-tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M3 7.5v9l9 4.5 9-4.5v-9M3 7.5l9 4.5 9-4.5" />
                                        </svg>
                                        Box size
                                    </span>
                                </div>
                                <div class="tx-dims-grid">
                                    <div>
                                        <label for="carton_height" class="tx-dim-label">Height</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_height" name="carton_height" value="{{ old('carton_height') }}" placeholder="50" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('carton_height') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="carton_width" class="tx-dim-label">Width</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_width" name="carton_width" value="{{ old('carton_width') }}" placeholder="65" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('carton_width') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="carton_length" class="tx-dim-label">Length</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_length" name="carton_length" value="{{ old('carton_length') }}" placeholder="125" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('carton_length') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="carton_depth" class="tx-dim-label">Depth</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_depth" name="carton_depth" value="{{ old('carton_depth') }}" placeholder="35" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                        @error('carton_depth') <p class="tx-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 4: Packaging & Media --}}
                <div class="tx-card lvl-3">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">04</span>
                        <div>
                            <h2>Media & Images</h2>
                            <p>Product photos and linked imagery</p>
                        </div>
                    </div>

                    <div class="tx-card-body">
                        {{-- Image Link Input --}}
                        <div>
                            <label for="image_link" class="tx-label">Product Image Link</label>
                            <p class="tx-hint">Provide a direct URL to the product image</p>
                            <input type="url" id="image_link" name="image_link" value="{{ old('image_link') }}" placeholder="https://example.com/image.jpg" class="tx-field">
                            @error('image_link') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- File Upload Dropzone --}}
                        <div>
                            <label class="tx-label">Upload Product Image</label>
                            <p class="tx-hint">Drag and drop your image or click to browse</p>
                            <div id="dropzone" class="tx-dropzone">
                                {{-- Empty State --}}
                                <div id="dropzone_empty" class="tx-dropzone-empty">
                                    <div class="tx-dropzone-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="tx-dz-title">Click to upload or drag and drop</p>
                                        <p class="tx-dz-sub">PNG, JPG, WebP (max 5MB)</p>
                                    </div>
                                </div>

                                {{-- Filled State --}}
                                <div id="dropzone_filled" class="tx-dropzone-filled">
                                    <div class="tx-file-row">
                                        <div id="file_thumb" class="tx-file-thumb"></div>
                                        <div class="tx-file-meta">
                                            <p id="file_name" class="name"></p>
                                            <p id="file_size" class="size"></p>
                                        </div>
                                        <button id="file_remove" type="button" class="tx-file-remove" aria-label="Remove file">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <input type="file" id="product_file" name="product_file" accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" style="position:absolute; inset:0; width:100%; height:100%; cursor:pointer; opacity:0;">
                            </div>
                            @error('product_file') <p class="tx-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Footer Actions --}}
                <div class="tx-footer">
                    <div class="tx-footer-inner">
                        <a href="{{ route('mi_app.index') }}" class="tx-btn-ghost">Cancel</a>
                        <button type="submit" id="submit_btn" class="tx-btn-submit">
                            <svg id="submit_icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <svg id="submit_spinner" class="hidden spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="submit_label">Save Product</span>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        (function () {
            // ---- Taxonomy cascade: Category -> Sub Category -> Sub Sub Category -> Collection ----
            document.querySelectorAll('#taxonomy-section [data-cascade-target]').forEach(function (parentSelect) {
                parentSelect.addEventListener('change', function () {
                    cascadeFrom(parentSelect, true);
                    updateTaxonomyPreview();
                });
            });

            function cascadeFrom(parentSelect, resetValue) {
                var targetId = parentSelect.getAttribute('data-cascade-target');
                var target = document.getElementById(targetId);
                if (!target) return;

                var selectedParent = parentSelect.value;
                if (resetValue) target.value = '';

                Array.from(target.options).forEach(function (opt) {
                    if (!opt.value) return; // keep placeholder
                    var belongs = opt.getAttribute('data-parent') === selectedParent;
                    opt.classList.toggle('hidden', !belongs);
                    opt.disabled = !belongs;
                });

                var nextTargetId = target.getAttribute('data-cascade-target');
                if (nextTargetId) {
                    var nextTarget = document.getElementById(nextTargetId);
                    if (nextTarget && resetValue) {
                        nextTarget.value = '';
                        Array.from(nextTarget.options).forEach(function (opt) {
                            if (!opt.value) return;
                            opt.classList.add('hidden');
                            opt.disabled = true;
                        });
                    }
                }
            }

            // ---- Live SKU / breadcrumb preview ----
            var categorySelect = document.getElementById('category_id');
            var subCategorySelect = document.getElementById('sub_category_id');
            var productTypeSelect = document.getElementById('product_type_id');
            var collectionSelect = document.getElementById('collection_id');
            var previewPath = document.getElementById('taxonomy-preview-path');

            function labelOf(select) {
                var opt = select.options[select.selectedIndex];
                return opt && opt.value ? opt.textContent.trim() : null;
            }

            function updateTaxonomyPreview() {
                var parts = [categorySelect, subCategorySelect, productTypeSelect, collectionSelect]
                    .map(labelOf)
                    .filter(Boolean);
                previewPath.textContent = parts.length ? parts.join('  →  ') : 'Select a category to begin';
            }

            [categorySelect, subCategorySelect, productTypeSelect, collectionSelect].forEach(function (el) {
                if (el) el.addEventListener('change', updateTaxonomyPreview);
            });

            // Re-apply cascades on load so old()-repopulated selects show the right visible options
            if (categorySelect && categorySelect.value) cascadeFrom(categorySelect, false);
            if (subCategorySelect && subCategorySelect.value) cascadeFrom(subCategorySelect, false);
            if (productTypeSelect && productTypeSelect.value) cascadeFrom(productTypeSelect, false);
            updateTaxonomyPreview();

            // ---- Required-field progress indicator ----
            var requiredFields = Array.prototype.slice.call(document.querySelectorAll('[data-required]'));
            var progressBar = document.getElementById('progress_bar');
            var progressLabel = document.getElementById('progress_label');

            function updateProgress() {
                var filled = requiredFields.filter(function (el) { return el.value && el.value.trim() !== ''; }).length;
                var total = requiredFields.length;
                var pct = total ? Math.round((filled / total) * 100) : 0;
                progressBar.style.width = pct + '%';
                progressLabel.textContent = filled + ' / ' + total + ' required fields';
            }
            requiredFields.forEach(function (el) {
                el.addEventListener('input', updateProgress);
                el.addEventListener('change', updateProgress);
            });
            updateProgress();

            // ---- Color swatch preview ----
            var colorInput = document.getElementById('color');
            var colorSwatch = document.getElementById('color_swatch');
            var namedColors = { black: '#111111', white: '#ffffff', 'matte black': '#1c1c1c', oak: '#c9a066', walnut: '#5c4433', gray: '#9ca3af', grey: '#9ca3af', blue: '#3b82f6', red: '#ef4444', green: '#22c55e', beige: '#e8dfc8' };
            function updateSwatch() {
                var val = (colorInput.value || '').trim().toLowerCase();
                if (!val) { colorSwatch.style.backgroundColor = ''; return; }
                var css = namedColors[val] || val;
                var probe = new Option().style;
                probe.color = '';
                probe.color = css;
                colorSwatch.style.backgroundColor = probe.color !== '' ? css : '';
            }
            if (colorInput) { colorInput.addEventListener('input', updateSwatch); updateSwatch(); }

            // ---- File upload: drag & drop, preview, remove ----
            var dropzone = document.getElementById('dropzone');
            var fileInput = document.getElementById('product_file');
            var emptyState = document.getElementById('dropzone_empty');
            var filledState = document.getElementById('dropzone_filled');
            var fileName = document.getElementById('file_name');
            var fileSize = document.getElementById('file_size');
            var fileThumb = document.getElementById('file_thumb');
            var removeBtn = document.getElementById('file_remove');

            function formatBytes(bytes) {
                if (!bytes) return '0 KB';
                var kb = bytes / 1024;
                if (kb < 1024) return kb.toFixed(0) + ' KB';
                return (kb / 1024).toFixed(1) + ' MB';
            }

            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                emptyState.style.display = 'none';
                filledState.style.display = 'flex';

                fileThumb.innerHTML = '';
                if (file.type && file.type.indexOf('image/') === 0) {
                    var img = document.createElement('img');
                    img.alt = 'Selected file preview';
                    var reader = new FileReader();
                    reader.onload = function (e) { img.src = e.target.result; };
                    reader.readAsDataURL(file);
                    fileThumb.appendChild(img);
                } else {
                    fileThumb.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>';
                }
            }

            function clearFile() {
                fileInput.value = '';
                emptyState.style.display = 'flex';
                filledState.style.display = 'none';
            }

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) showFile(fileInput.files[0]);
            });

            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearFile();
            });

            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('drag-active');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('drag-active');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                if (dt && dt.files && dt.files[0]) {
                    fileInput.files = dt.files;
                    showFile(dt.files[0]);
                }
            });

            // ---- Inline validation styling + submit loading state ----
            var form = document.getElementById('product_form');
            var submitBtn = document.getElementById('submit_btn');
            var submitIcon = document.getElementById('submit_icon');
            var submitSpinner = document.getElementById('submit_spinner');
            var submitLabel = document.getElementById('submit_label');

            requiredFields.forEach(function (el) {
                el.addEventListener('blur', function () {
                    el.classList.toggle('field-invalid', el.value.trim() === '');
                });
            });

            form.addEventListener('submit', function (e) {
                var firstInvalid = null;
                requiredFields.forEach(function (el) {
                    var invalid = el.value.trim() === '';
                    el.classList.toggle('field-invalid', invalid);
                    if (invalid && !firstInvalid) firstInvalid = el;
                });
                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                    return;
                }
                submitBtn.disabled = true;
                submitIcon.classList.add('hidden');
                submitSpinner.classList.remove('hidden');
                submitLabel.textContent = 'Saving…';
            });
        })();
    </script>
</x-mi_app>