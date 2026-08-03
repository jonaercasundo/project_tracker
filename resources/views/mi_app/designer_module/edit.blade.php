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
            border-bottom: 1px solid var(--tx-line); margin-bottom: 2rem;
        }
        .tx-title { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.925rem; margin-top: 0.5rem; }
        .tx-back {
            display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid var(--tx-line);
            background: var(--tx-surface); color: var(--tx-ink); font-size: 0.8125rem; font-weight: 600;
            padding: 0.6rem 1.1rem; border-radius: 999px; text-decoration: none; transition: all .15s ease;
        }
        .tx-back:hover { border-color: var(--tx-primary); color: var(--tx-primary); transform: translateX(-2px); }

        /* Cards */
        .tx-card { background: var(--tx-surface); border: 1px solid var(--tx-line); border-radius: 20px; margin-bottom: 1.5rem; overflow: hidden; }
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

        .lvl-1 .tx-card-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .lvl-2 .tx-card-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .lvl-3 .tx-card-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }

        /* Fields */
        .tx-label { display: block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-soft); margin-bottom: 0.55rem; }
        .tx-lvl-dot { display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; margin-right: 0.4rem; }
        .tx-field {
            width: 100%; border: 1px solid var(--tx-line); background: var(--tx-bg); color: var(--tx-ink);
            font-size: 0.875rem; padding: 0.72rem 1rem; border-radius: 12px; outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .tx-field:focus { border-color: var(--tx-primary); background: var(--tx-surface); box-shadow: 0 0 0 4px var(--tx-primary-soft); }
        .tx-field:disabled { background: var(--tx-line); color: var(--tx-ink-faint); cursor: not-allowed; }
        .tx-hint { font-size: 0.7rem; color: var(--tx-ink-faint); margin-top: 0.4rem; }
        .tx-multi-select-wrap { display: flex; flex-direction: column; gap: 0.6rem; }
        .tx-multi-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; }
        .tx-multi-hint { font-size: 0.72rem; color: var(--tx-ink-faint); }
        .tx-multi-clear { display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--tx-line); background: var(--tx-surface); color: var(--tx-ink-soft); border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.7rem; font-weight: 600; cursor: pointer; }
        .tx-multi-clear:hover { border-color: var(--tx-primary); color: var(--tx-primary); }
        .tx-multi-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; min-height: 1.75rem; }
        .tx-multi-chip { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.35rem 0.7rem; border-radius: 999px; background: var(--tx-primary-soft); color: var(--tx-primary); font-size: 0.74rem; font-weight: 600; }
        .tx-multi-chip button { border: none; background: transparent; color: inherit; cursor: pointer; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .tx-multi-chip button:hover { opacity: 0.75; }
        .tx-multi-select { min-height: 10rem; }
        .tx-select-wrap { position: relative; }
        .tx-select-wrap select { appearance: none; padding-right: 2.5rem; }
        .tx-select-wrap svg { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none; }

        .tx-taxonomy-preview {
            display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
            margin: 0 1.75rem 1.75rem; padding: 0.9rem 1.1rem;
            border: 1px dashed var(--tx-line); border-radius: 12px; background: var(--tx-bg);
        }
        .tx-taxonomy-preview-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--tx-ink-faint); }
        #taxonomy-preview-path { font-size: 0.8rem; font-weight: 600; color: var(--tx-ink); }

        /* Sub-panels: product / carton dimensions */
        .tx-subpanel { border: 1px solid var(--tx-line); border-radius: 16px; padding: 1.25rem; background: var(--tx-bg); }
        .tx-subpanel + .tx-subpanel { margin-top: 1.25rem; }
        .tx-subpanel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .tx-subpanel-head h3 { font-family: var(--tx-font-display); font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: var(--tx-ink-soft); }
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

        /* Current image preview */
        .tx-current-image { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
        .tx-current-image img { width: 6.5rem; height: 6.5rem; object-fit: cover; border-radius: 12px; border: 1px solid var(--tx-line); }
        .tx-current-image-meta { font-size: 0.75rem; color: var(--tx-ink-faint); }

        /* Footer */
        .tx-footer { position: sticky; bottom: 1rem; z-index: 10; margin-top: 2rem; }
        .tx-footer-inner {
            display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;
            border: 1px solid var(--tx-line); background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);
            border-radius: 18px; padding: 1rem 1.25rem; box-shadow: 0 20px 45px -25px rgba(23,27,26,0.35);
        }
        .tx-console.dark .tx-footer-inner { background: rgba(25,29,34,0.92); }
        .tx-btn-ghost { border-radius: 12px; padding: 0.65rem 1.25rem; font-size: 0.85rem; font-weight: 600; color: var(--tx-ink-soft); text-decoration: none; transition: all .15s ease; }
        .tx-btn-ghost:hover { background: var(--tx-bg); color: var(--tx-ink); }
        .tx-btn-submit {
            display: inline-flex; align-items: center; gap: 0.55rem; border: none; cursor: pointer;
            border-radius: 12px; padding: 0.75rem 1.5rem; font-size: 0.85rem; font-weight: 600;
            background: var(--tx-primary); color: var(--tx-primary-ink); transition: all .15s ease;
        }
        .tx-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -10px var(--tx-primary); }
    
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Header --}}
            <div class="tx-header">
                <div>
                    <h1 class="tx-title tx-display">Edit Product</h1>
                    <p class="tx-subtitle">Update product information.</p>
                </div>
                <a href="{{ route('mi_app.show', $product->product_id) }}" class="tx-back">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back
                </a>
            </div>

            {{-- Form --}}
            <form action="{{ route('mi_app.update', $product->product_id) }}" method="POST" enctype="multipart/form-data" id="edit_product_form">
                @csrf
                @method('PUT')

                @php
                    $saveError = $errors->first('error') ?: session('error');
                @endphp
                @if($saveError)
                    <div class="tx-error" style="margin: 0 0 1.5rem; padding: 0.85rem 1.1rem; border: 1px solid var(--tx-danger); border-radius: 12px; background: var(--tx-accent-soft);">
                        <strong>Unable to update the product.</strong>
                        <div style="margin-top: 0.35rem;">{{ $saveError }}</div>
                    </div>
                @endif
                @if($errors->any() && !$saveError)
                    <div class="tx-error" style="margin: 0 0 1.5rem; padding: 0.85rem 1.1rem; border: 1px solid var(--tx-danger); border-radius: 12px; background: var(--tx-accent-soft);">
                        <ul style="margin: 0; padding-left: 1rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- SECTION 1: Identification --}}
                <div class="tx-card">
                    <div class="tx-card-head">
                        <span class="tx-card-icon" style="background: var(--tx-line); color: var(--tx-ink-soft);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <div>
                            <h2>Product Identification</h2>
                            <p>Auto-generated reference numbers</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-2">
                        <div>
                            <label class="tx-label">SKU Number</label>
                            <input type="text" value="{{ $product->sku }}" disabled class="tx-field tx-mono">
                            <p class="tx-hint">Auto generated</p>
                        </div>
                        <div>
                            <label class="tx-label">Draft Number</label>
                            <input type="text" value="{{ $product->draft_number }}" disabled class="tx-field tx-mono">
                            <p class="tx-hint">Auto generated</p>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Taxonomy --}}
                <div class="tx-card lvl-1" id="taxonomy-section">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">01</span>
                        <div>
                            <h2>Taxonomy</h2>
                            <p>Category → Sub Category → Sub Sub Category → Collection</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="category_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-1);"></span>Category
                            </label>
                            <div class="tx-select-wrap">
                                <select name="category_id" id="category_id" data-cascade-target="sub_category_id" class="tx-field" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="sub_category_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-2);"></span>Sub Category
                            </label>
                            <div class="tx-select-wrap">
                                <select name="sub_category_id" id="sub_category_id" data-cascade-target="product_type_id" class="tx-field">
                                    @foreach($subCategories as $sub)
                                        <option value="{{ $sub->id }}" data-parent="{{ $sub->category_id }}"
                                            class="{{ $product->category_id == $sub->category_id ? '' : 'hidden' }}"
                                            {{ $product->sub_category_id == $sub->id ? 'selected' : '' }}>
                                            {{ $sub->code }} - {{ $sub->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="product_type_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-3);"></span>Sub Sub Category
                            </label>
                            <div class="tx-select-wrap">
                                <select name="product_type_id" id="product_type_id" data-cascade-target="collection_id" class="tx-field">
                                    <option value="">Select</option>
                                    @foreach($productTypes as $type)
                                        <option value="{{ $type->id }}" data-parent="{{ $type->sub_category_id }}"
                                            class="{{ $product->sub_category_id == $type->sub_category_id ? '' : 'hidden' }}"
                                            {{ $product->product_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->code }} - {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="collection_id" class="tx-label">
                                <span class="tx-lvl-dot" style="background: var(--tx-lvl-4);"></span>Collection
                            </label>
                            <div class="tx-select-wrap">
                                <select name="collection_id" id="collection_id" class="tx-field">
                                    <option value="">Select</option>
                                    @foreach($collections as $collection)
                                        <option value="{{ $collection->id }}" data-parent="{{ $collection->product_type_id }}"
                                            class="{{ $product->product_type_id == $collection->product_type_id ? '' : 'hidden' }}"
                                            {{ $product->collection_id == $collection->id ? 'selected' : '' }}>
                                            {{ $collection->code }} - {{ $collection->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>

                    <div class="tx-taxonomy-preview">
                        <span class="tx-taxonomy-preview-label">SKU preview</span>
                        <span id="taxonomy-preview-path" class="tx-mono">—</span>
                    </div>
                </div>

                {{-- SECTION 3: General Information --}}
                <div class="tx-card lvl-2">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">02</span>
                        <div>
                            <h2>General Information</h2>
                            <p>Basic identity and product details</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-2">
                        <div style="grid-column: 1 / -1;">
                            <label for="item_name" class="tx-label">Item Name</label>
                            <input type="text" id="item_name" name="item_name" value="{{ old('item_name', $product->item_name) }}" class="tx-field" required>
                        </div>

                        <div>
                            <label for="type_of_sample" class="tx-label">Type of Sample</label>
                            <input type="text" id="type_of_sample" name="type_of_sample" value="{{ old('type_of_sample', $product->type_of_sample) }}" class="tx-field" required>
                        </div>

                        <div>
                            <label for="designed_by" class="tx-label">Designed By</label>
                            <input type="text" id="designed_by" name="designed_by" value="{{ old('designed_by', $product->designed_by) }}" class="tx-field">
                        </div>
                    </div>
                </div>

                {{-- SECTION 4: Dimensions --}}
                <div class="tx-card lvl-3">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">03</span>
                        <div>
                            <h2>Dimensions</h2>
                            <p>Product and carton measurements</p>
                        </div>
                    </div>
                    <div class="tx-card-body">
                        <div class="tx-subpanel">
                            <div class="tx-subpanel-head">
                                <div><h3>Product dimensions</h3></div>
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
                                @foreach([
                                    'product_height' => 'Height',
                                    'product_width' => 'Width',
                                    'product_length' => 'Length',
                                    'product_depth' => 'Depth',
                                ] as $field => $label)
                                    <div>
                                        <label class="tx-dim-label">{{ $label }}</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.01" name="{{ $field }}" value="{{ old($field, $product->$field) }}" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="tx-subpanel">
                            <div class="tx-subpanel-head">
                                <div><h3>Carton dimensions</h3></div>
                                <span class="tx-subpanel-tag">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M3 7.5v9l9 4.5 9-4.5v-9M3 7.5l9 4.5 9-4.5" />
                                    </svg>
                                    Box size
                                </span>
                            </div>
                            <div class="tx-dims-grid">
                                @foreach([
                                    'carton_height' => 'Height',
                                    'carton_width' => 'Width',
                                    'carton_length' => 'Length',
                                    'carton_depth' => 'Depth',
                                ] as $field => $label)
                                    <div>
                                        <label class="tx-dim-label">{{ $label }}</label>
                                        <div class="tx-dim-input-wrap">
                                            <input type="number" step="0.01" name="{{ $field }}" value="{{ old($field, $product->$field) }}" class="tx-field">
                                            <span class="tx-dim-unit">cm</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 5: Attributes & Media --}}
                <div class="tx-card">
                    <div class="tx-card-head">
                        <span class="tx-card-icon" style="background: var(--tx-accent-soft); color: var(--tx-accent);">04</span>
                        <div>
                            <h2>Cost & Media</h2>
                            <p>Purchase cost and product images</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-2">
                        <div>
                            <label for="materials" class="tx-label">Materials</label>
                            @php
                                $selectedMaterials = old(
                                    'materials',
                                    is_array($product->materials)
                                        ? $product->materials
                                        : json_decode($product->materials ?? '[]', true)
                                );

                                $selectedMaterials = $selectedMaterials ?: [];
                            @endphp
                            <div class="tx-multi-select-wrap">
                                <div class="tx-multi-toolbar">
                                    <span class="tx-multi-hint">Selected values appear below</span>
                                    <button type="button" class="tx-multi-clear" data-target="materials">Clear</button>
                                </div>
                                <select id="materials" name="materials[]" multiple size="8" autocomplete="off" class="tx-field tx-multi-select">
                                    <optgroup label="Solid Wood">
                                        <option value="Acacia Wood" {{ in_array('Acacia Wood', $selectedMaterials) ? 'selected' : '' }}>Acacia Wood</option>
                                        <option value="Ash Wood" {{ in_array('Ash Wood', $selectedMaterials) ? 'selected' : '' }}>Ash Wood</option>
                                        <option value="Beech Wood" {{ in_array('Beech Wood', $selectedMaterials) ? 'selected' : '' }}>Beech Wood</option>
                                        <option value="Birch Wood" {{ in_array('Birch Wood', $selectedMaterials) ? 'selected' : '' }}>Birch Wood</option>
                                        <option value="Mahogany" {{ in_array('Mahogany', $selectedMaterials) ? 'selected' : '' }}>Mahogany</option>
                                        <option value="Mango Wood" {{ in_array('Mango Wood', $selectedMaterials) ? 'selected' : '' }}>Mango Wood</option>
                                        <option value="Oak" {{ in_array('Oak', $selectedMaterials) ? 'selected' : '' }}>Oak</option>
                                        <option value="Pine" {{ in_array('Pine', $selectedMaterials) ? 'selected' : '' }}>Pine</option>
                                        <option value="Rubberwood" {{ in_array('Rubberwood', $selectedMaterials) ? 'selected' : '' }}>Rubberwood</option>
                                        <option value="Teak" {{ in_array('Teak', $selectedMaterials) ? 'selected' : '' }}>Teak</option>
                                        <option value="Walnut" {{ in_array('Walnut', $selectedMaterials) ? 'selected' : '' }}>Walnut</option>
                                    </optgroup>
                                    <optgroup label="Engineered Wood">
                                        <option value="MDF" {{ in_array('MDF', $selectedMaterials) ? 'selected' : '' }}>MDF</option>
                                        <option value="Particle Board" {{ in_array('Particle Board', $selectedMaterials) ? 'selected' : '' }}>Particle Board</option>
                                        <option value="Plywood" {{ in_array('Plywood', $selectedMaterials) ? 'selected' : '' }}>Plywood</option>
                                        <option value="HDF" {{ in_array('HDF', $selectedMaterials) ? 'selected' : '' }}>HDF</option>
                                        <option value="Melamine Board" {{ in_array('Melamine Board', $selectedMaterials) ? 'selected' : '' }}>Melamine Board</option>
                                    </optgroup>
                                    <optgroup label="Metal">
                                        <option value="Aluminum" {{ in_array('Aluminum', $selectedMaterials) ? 'selected' : '' }}>Aluminum</option>
                                        <option value="Brass" {{ in_array('Brass', $selectedMaterials) ? 'selected' : '' }}>Brass</option>
                                        <option value="Cast Iron" {{ in_array('Cast Iron', $selectedMaterials) ? 'selected' : '' }}>Cast Iron</option>
                                        <option value="Iron" {{ in_array('Iron', $selectedMaterials) ? 'selected' : '' }}>Iron</option>
                                        <option value="Stainless Steel" {{ in_array('Stainless Steel', $selectedMaterials) ? 'selected' : '' }}>Stainless Steel</option>
                                        <option value="Steel" {{ in_array('Steel', $selectedMaterials) ? 'selected' : '' }}>Steel</option>
                                    </optgroup>
                                    <optgroup label="Glass & Stone">
                                        <option value="Clear Glass" {{ in_array('Clear Glass', $selectedMaterials) ? 'selected' : '' }}>Clear Glass</option>
                                        <option value="Tempered Glass" {{ in_array('Tempered Glass', $selectedMaterials) ? 'selected' : '' }}>Tempered Glass</option>
                                        <option value="Marble" {{ in_array('Marble', $selectedMaterials) ? 'selected' : '' }}>Marble</option>
                                        <option value="Granite" {{ in_array('Granite', $selectedMaterials) ? 'selected' : '' }}>Granite</option>
                                        <option value="Ceramic" {{ in_array('Ceramic', $selectedMaterials) ? 'selected' : '' }}>Ceramic</option>
                                        <option value="Concrete" {{ in_array('Concrete', $selectedMaterials) ? 'selected' : '' }}>Concrete</option>
                                    </optgroup>
                                    <optgroup label="Natural Fibers">
                                        <option value="Bamboo" {{ in_array('Bamboo', $selectedMaterials) ? 'selected' : '' }}>Bamboo</option>
                                        <option value="Cane" {{ in_array('Cane', $selectedMaterials) ? 'selected' : '' }}>Cane</option>
                                        <option value="Rattan" {{ in_array('Rattan', $selectedMaterials) ? 'selected' : '' }}>Rattan</option>
                                        <option value="Seagrass" {{ in_array('Seagrass', $selectedMaterials) ? 'selected' : '' }}>Seagrass</option>
                                        <option value="Water Hyacinth" {{ in_array('Water Hyacinth', $selectedMaterials) ? 'selected' : '' }}>Water Hyacinth</option>
                                        <option value="Abaca" {{ in_array('Abaca', $selectedMaterials) ? 'selected' : '' }}>Abaca</option>
                                    </optgroup>
                                    <optgroup label="Fabric & Upholstery">
                                        <option value="Boucle" {{ in_array('Boucle', $selectedMaterials) ? 'selected' : '' }}>Boucle</option>
                                        <option value="Canvas" {{ in_array('Canvas', $selectedMaterials) ? 'selected' : '' }}>Canvas</option>
                                        <option value="Cotton" {{ in_array('Cotton', $selectedMaterials) ? 'selected' : '' }}>Cotton</option>
                                        <option value="Leather" {{ in_array('Leather', $selectedMaterials) ? 'selected' : '' }}>Leather</option>
                                        <option value="PU Leather" {{ in_array('PU Leather', $selectedMaterials) ? 'selected' : '' }}>PU Leather</option>
                                        <option value="Linen" {{ in_array('Linen', $selectedMaterials) ? 'selected' : '' }}>Linen</option>
                                        <option value="Microfiber" {{ in_array('Microfiber', $selectedMaterials) ? 'selected' : '' }}>Microfiber</option>
                                        <option value="Polyester" {{ in_array('Polyester', $selectedMaterials) ? 'selected' : '' }}>Polyester</option>
                                        <option value="Velvet" {{ in_array('Velvet', $selectedMaterials) ? 'selected' : '' }}>Velvet</option>
                                    </optgroup>
                                    <optgroup label="Plastic & Synthetic">
                                        <option value="ABS Plastic" {{ in_array('ABS Plastic', $selectedMaterials) ? 'selected' : '' }}>ABS Plastic</option>
                                        <option value="Acrylic" {{ in_array('Acrylic', $selectedMaterials) ? 'selected' : '' }}>Acrylic</option>
                                        <option value="Fiberglass" {{ in_array('Fiberglass', $selectedMaterials) ? 'selected' : '' }}>Fiberglass</option>
                                        <option value="Polypropylene" {{ in_array('Polypropylene', $selectedMaterials) ? 'selected' : '' }}>Polypropylene</option>
                                        <option value="PVC" {{ in_array('PVC', $selectedMaterials) ? 'selected' : '' }}>PVC</option>
                                        <option value="Resin" {{ in_array('Resin', $selectedMaterials) ? 'selected' : '' }}>Resin</option>
                                    </optgroup>
                                    <optgroup label="Other">
                                        <option value="Composite" {{ in_array('Composite', $selectedMaterials) ? 'selected' : '' }}>Composite</option>
                                        <option value="Mixed Materials" {{ in_array('Mixed Materials', $selectedMaterials) ? 'selected' : '' }}>Mixed Materials</option>
                                    </optgroup>
                                </select>
                                <div id="materials_chips" class="tx-multi-chips" aria-live="polite"></div>
                            </div>
                            <p class="tx-hint">Select one or more materials.</p>
                        </div>

                        <div>
                            @php
                                $selectedColors = old(
                                    'color',
                                    is_array($product->color)
                                        ? $product->color
                                        : json_decode($product->color ?? '[]', true)
                                );

                                $selectedColors = $selectedColors ?: [];
                                @endphp
                            <label for="color" class="tx-label">Color</label>
                            <div class="tx-multi-select-wrap">
                                <div class="tx-multi-toolbar">
                                    <span class="tx-multi-hint">Selected values appear below</span>
                                    <button type="button" class="tx-multi-clear" data-target="color">Clear</button>
                                </div>
                                <select id="color" name="color[]" multiple size="8" class="tx-field tx-multi-select">
                                    <optgroup label="Basic Colors">
                                        <option value="Black" {{ in_array('Black', $selectedColors) ? 'selected' : '' }}>Black</option>
                                        <option value="White" {{ in_array('White', $selectedColors) ? 'selected' : '' }}>White</option>
                                        <option value="Gray" {{ in_array('Gray', $selectedColors) ? 'selected' : '' }}>Gray</option>
                                        <option value="Silver" {{ in_array('Silver', $selectedColors) ? 'selected' : '' }}>Silver</option>
                                        <option value="Gold" {{ in_array('Gold', $selectedColors) ? 'selected' : '' }}>Gold</option>
                                        <option value="Bronze" {{ in_array('Bronze', $selectedColors) ? 'selected' : '' }}>Bronze</option>
                                    </optgroup>
                                    <optgroup label="Wood Finishes">
                                        <option value="Natural" {{ in_array('Natural', $selectedColors) ? 'selected' : '' }}>Natural</option>
                                        <option value="Oak" {{ in_array('Oak', $selectedColors) ? 'selected' : '' }}>Oak</option>
                                        <option value="Walnut" {{ in_array('Walnut', $selectedColors) ? 'selected' : '' }}>Walnut</option>
                                        <option value="Teak" {{ in_array('Teak', $selectedColors) ? 'selected' : '' }}>Teak</option>
                                        <option value="Mahogany" {{ in_array('Mahogany', $selectedColors) ? 'selected' : '' }}>Mahogany</option>
                                        <option value="Espresso" {{ in_array('Espresso', $selectedColors) ? 'selected' : '' }}>Espresso</option>
                                    </optgroup>
                                    <optgroup label="Neutral">
                                        <option value="Beige" {{ in_array('Beige', $selectedColors) ? 'selected' : '' }}>Beige</option>
                                        <option value="Cream" {{ in_array('Cream', $selectedColors) ? 'selected' : '' }}>Cream</option>
                                        <option value="Ivory" {{ in_array('Ivory', $selectedColors) ? 'selected' : '' }}>Ivory</option>
                                        <option value="Taupe" {{ in_array('Taupe', $selectedColors) ? 'selected' : '' }}>Taupe</option>
                                        <option value="Brown" {{ in_array('Brown', $selectedColors) ? 'selected' : '' }}>Brown</option>
                                    </optgroup>
                                    <optgroup label="Accent Colors">
                                        <option value="Blue" {{ in_array('Blue', $selectedColors) ? 'selected' : '' }}>Blue</option>
                                        <option value="Green" {{ in_array('Green', $selectedColors) ? 'selected' : '' }}>Green</option>
                                        <option value="Red" {{ in_array('Red', $selectedColors) ? 'selected' : '' }}>Red</option>
                                        <option value="Yellow" {{ in_array('Yellow', $selectedColors) ? 'selected' : '' }}>Yellow</option>
                                        <option value="Orange" {{ in_array('Orange', $selectedColors) ? 'selected' : '' }}>Orange</option>
                                        <option value="Pink" {{ in_array('Pink', $selectedColors) ? 'selected' : '' }}>Pink</option>
                                        <option value="Purple" {{ in_array('Purple', $selectedColors) ? 'selected' : '' }}>Purple</option>
                                    </optgroup>
                                    <optgroup label="Special Finishes">
                                        <option value="Matte Black" {{ in_array('Matte Black', $selectedColors) ? 'selected' : '' }}>Matte Black</option>
                                        <option value="Gloss White" {{ in_array('Gloss White', $selectedColors) ? 'selected' : '' }}>Gloss White</option>
                                        <option value="Brushed Gold" {{ in_array('Brushed Gold', $selectedColors) ? 'selected' : '' }}>Brushed Gold</option>
                                        <option value="Rose Gold" {{ in_array('Rose Gold', $selectedColors) ? 'selected' : '' }}>Rose Gold</option>
                                        <option value="Chrome" {{ in_array('Chrome', $selectedColors) ? 'selected' : '' }}>Chrome</option>
                                    </optgroup>
                                </select>
                                <div id="color_chips" class="tx-multi-chips" aria-live="polite"></div>
                            </div>
                            <p class="tx-hint">Select one or more colors.</p>
                        </div>

                        <div>
                            <label for="image_links" class="tx-label">Image Links</label>
                            <p class="tx-hint">Add one or more direct image URLs.</p>
                            <div id="imageLinks">
                                @php
                                    $imageLinks = old('image_links', $product->images->pluck('image_url')->filter()->values()->all());
                                @endphp
                                @if (is_array($imageLinks) && count($imageLinks))
                                    @foreach ($imageLinks as $link)
                                        <input type="url" name="image_links[]" value="{{ $link }}" placeholder="https://example.com/image.jpg" class="tx-field mb-2">
                                    @endforeach
                                @else
                                    <input type="url" name="image_links[]" value="{{ old('image_links.0') }}" placeholder="https://example.com/image.jpg" class="tx-field">
                                @endif
                            </div>
                            @if ($errors->has('image_links') || $errors->has('image_links.*'))
                                <p class="tx-error">{{ $errors->first('image_links.*') ?? $errors->first('image_links') }}</p>
                            @endif
                            <button type="button" onclick="addImageLink()" class="tx-btn-ghost" style="margin-top: 0.6rem;">
                                + Add Another Link
                            </button>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label class="tx-label">Upload Product Images</label>
                            <p class="tx-hint">Select one or more files to upload alongside any links above.</p>
                            <input type="file" id="product_images" name="product_images[]" accept="image/*,.pdf,.obj,.stl" multiple class="tx-field" style="padding: 0.5rem 0.75rem;">
                            @if ($errors->has('product_images') || $errors->has('product_images.*'))
                                <p class="tx-error">{{ $errors->first('product_images.*') ?? $errors->first('product_images') }}</p>
                            @endif

                            @if($product->images->isNotEmpty())
                                <div class="tx-current-image" style="flex-wrap: wrap; margin-top: 1rem;">
                                    @foreach($product->images as $image)
                                        @if($image->image_type === 'upload' && $image->image_path)
                                            <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->item_name }}">
                                                <span class="tx-current-image-meta">Uploaded image</span>
                                            </div>
                                        @elseif($image->image_type === 'url' && $image->image_url)
                                            <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                                <img src="{{ $image->image_url }}" alt="{{ $product->item_name }}" style="object-fit:cover;">
                                                <span class="tx-current-image-meta">Linked image</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="tx-footer">
                    <div class="tx-footer-inner">
                        <a href="{{ route('mi_app.index') }}" class="tx-btn-ghost">Cancel</a>
                        <button type="submit" class="tx-btn-submit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Update Product
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <script>
        function addImageLink() {
            var container = document.getElementById('imageLinks');
            var input = document.createElement('input');
            input.type = 'url';
            input.name = 'image_links[]';
            input.placeholder = 'https://example.com/image.jpg';
            input.className = 'tx-field mb-2';
            container.appendChild(input);
        }

        (function () {
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
                    if (!opt.value) return;
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

            updateTaxonomyPreview();
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var materialsSelect = document.getElementById('materials');
            if (materialsSelect) {
                new TomSelect(materialsSelect, {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 100,
                    hideSelected: true,
                    closeAfterSelect: false,
                    placeholder: 'Select one or more materials...'
                });
            }

            var colorSelect = document.getElementById('color');
            if (colorSelect) {
                new TomSelect(colorSelect, {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 100,
                    hideSelected: true,
                    closeAfterSelect: false,
                    placeholder: 'Select one or more colors...'
                });
            }

            document.querySelectorAll('select.tx-multi-select').forEach(function (select) {
                var wrapper = select.closest('.tx-multi-select-wrap');
                if (!wrapper) return;

                var chips = wrapper.querySelector('.tx-multi-chips');
                var clearButton = wrapper.querySelector('.tx-multi-clear');

                function updateChips() {
                    if (!chips) return;

                    var selectedValues = Array.from(select.selectedOptions)
                        .map(function (option) { return option.value; })
                        .filter(Boolean);

                    chips.innerHTML = '';
                    selectedValues.forEach(function (value) {
                        var chip = document.createElement('span');
                        chip.className = 'tx-multi-chip';
                        chip.innerHTML = '<span>' + value + '</span><button type="button" aria-label="Remove ' + value + '">&times;</button>';

                        chip.querySelector('button').addEventListener('click', function (event) {
                            event.preventDefault();
                            if (select.tomselect) {
                                select.tomselect.removeItem(value);
                            } else {
                                Array.from(select.options).forEach(function (option) {
                                    if (option.value === value) {
                                        option.selected = false;
                                    }
                                });
                            }
                            window.setTimeout(updateChips, 0);
                        });

                        chips.appendChild(chip);
                    });

                    if (clearButton) {
                        clearButton.style.display = selectedValues.length ? 'inline-flex' : 'none';
                    }
                }

                select.addEventListener('change', function () {
                    window.setTimeout(updateChips, 0);
                });
                if (select.tomselect) {
                    select.tomselect.on('item_add item_remove', function () {
                        window.setTimeout(updateChips, 0);
                    });
                }
                if (clearButton) {
                    clearButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        if (select.tomselect) {
                            select.tomselect.clear();
                        } else {
                            Array.from(select.options).forEach(function (option) {
                                option.selected = false;
                            });
                        }
                        window.setTimeout(updateChips, 0);
                    });
                }

                updateChips();
            });
        });
    </script>
</x-mi_app>