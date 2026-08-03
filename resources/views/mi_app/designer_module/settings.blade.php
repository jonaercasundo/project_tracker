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

        .tx-shell {
            max-width: 78rem;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 5rem;
        }

        /* Header */
        .tx-header {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line);
            margin-bottom: 2rem;
        }
        .tx-eyebrow {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--tx-ink-faint);
            margin-bottom: 0.6rem;
        }
        .tx-eyebrow a { color: var(--tx-ink-soft); text-decoration: none; }
        .tx-eyebrow a:hover { color: var(--tx-primary); }
        .tx-title { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.925rem; margin-top: 0.5rem; max-width: 38rem; }
        .tx-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--tx-line);
            background: var(--tx-surface);
            color: var(--tx-ink);
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.6rem 1.1rem;
            border-radius: 999px;
            text-decoration: none;
            transition: all .15s ease;
        }
        .tx-back:hover { border-color: var(--tx-primary); color: var(--tx-primary); transform: translateX(-2px); }

        /* Ladder / signature nav */
        .tx-ladder {
            display: flex;
            align-items: stretch;
            gap: 0;
            margin-bottom: 2.5rem;
            border: 1px solid var(--tx-line);
            border-radius: 18px;
            overflow: hidden;
            background: var(--tx-surface);
        }
        .tx-rung {
            flex: 1 1 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            text-decoration: none;
            color: var(--tx-ink);
            position: relative;
            border-right: 1px solid var(--tx-line);
            transition: background .15s ease;
        }
        .tx-rung:last-child { border-right: none; }
        .tx-rung:hover { background: var(--tx-primary-soft); }
        .tx-rung-dot {
            width: 0.65rem; height: 0.65rem; border-radius: 999px; flex-shrink: 0;
            box-shadow: 0 0 0 4px var(--dot-soft, transparent);
        }
        .tx-rung-label { font-size: 0.8rem; font-weight: 600; }
        .tx-rung-sub { font-size: 0.7rem; color: var(--tx-ink-faint); }
        .tx-rung::after {
            content: '';
            position: absolute;
            right: -1px; top: 50%;
            width: 6px; height: 6px;
            transform: translateY(-50%) rotate(45deg);
            border-top: 1px solid var(--tx-line);
            border-right: 1px solid var(--tx-line);
            background: var(--tx-surface);
            z-index: 1;
        }
        .tx-rung:last-child::after { display: none; }

        /* Cards */
        .tx-card {
            background: var(--tx-surface);
            border: 1px solid var(--tx-line);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            scroll-margin-top: 1.5rem;
        }
        .tx-card-head {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 1.35rem 1.75rem;
            border-bottom: 1px solid var(--tx-line);
        }
        .tx-card-icon {
            width: 2.25rem; height: 2.25rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px;
            flex-shrink: 0;
            font-family: var(--tx-font-mono);
            font-weight: 600;
            font-size: 0.8rem;
        }
        .tx-card-head h2 { font-family: var(--tx-font-display); font-size: 1.02rem; font-weight: 600; }
        .tx-card-head p { font-size: 0.78rem; color: var(--tx-ink-soft); margin-top: 0.15rem; }
        .tx-card-body {
            padding: 1.75rem;
            display: grid;
            grid-template-columns: repeat(1, minmax(0,1fr));
            gap: 1.35rem;
        }
        @media (min-width: 768px) { .tx-card-body.cols-2 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (min-width: 1024px) {
            .tx-card-body.cols-4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
            .tx-card-body.cols-4-btn { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }

        /* Fields */
        .tx-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--tx-ink-soft);
            margin-bottom: 0.55rem;
        }
        .tx-required { color: var(--tx-danger); }
        .tx-field {
            width: 100%;
            border: 1px solid var(--tx-line);
            background: var(--tx-bg);
            color: var(--tx-ink);
            font-size: 0.875rem;
            padding: 0.72rem 1rem;
            border-radius: 12px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .tx-field::placeholder { color: var(--tx-ink-faint); }
        .tx-field:focus {
            border-color: var(--tx-primary);
            background: var(--tx-surface);
            box-shadow: 0 0 0 4px var(--tx-primary-soft);
        }
        .tx-select-wrap { position: relative; }
        .tx-select-wrap select { appearance: none; padding-right: 2.5rem; }
        .tx-select-wrap svg {
            position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
            width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none;
        }
        .tx-error {
            color: var(--tx-danger);
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.4rem;
        }

        /* Buttons */
        .tx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            background: var(--tx-primary);
            color: var(--tx-primary-ink);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.75rem 1.1rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        .tx-btn:hover { opacity: 0.92; box-shadow: 0 8px 20px -8px var(--tx-primary); transform: translateY(-1px); }
        .tx-btn-static {
            width: auto;
        }

        /* Level accents per section */
        .lvl-1 .tx-card-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .lvl-1 .tx-rung-dot { background: var(--tx-lvl-1); --dot-soft: var(--tx-lvl-1-soft); }
        .lvl-2 .tx-card-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .lvl-2 .tx-rung-dot { background: var(--tx-lvl-2); --dot-soft: var(--tx-lvl-2-soft); }
        .lvl-3 .tx-card-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }
        .lvl-3 .tx-rung-dot { background: var(--tx-lvl-3); --dot-soft: var(--tx-lvl-3-soft); }
        .lvl-4 .tx-card-icon { background: var(--tx-lvl-4-soft); color: var(--tx-lvl-4); }
        .lvl-4 .tx-rung-dot { background: var(--tx-lvl-4); --dot-soft: var(--tx-lvl-4-soft); }
        .lvl-m .tx-card-icon { background: var(--tx-accent-soft); color: var(--tx-accent); }

        /* Search / filter toolbar */
        .tx-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid var(--tx-line);
            align-items: center;
        }
        .tx-search { position: relative; flex: 1 1 16rem; }
        .tx-search svg {
            position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
            width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none;
        }
        .tx-search input.tx-field { padding-left: 2.5rem; }
        .tx-toolbar-select { flex: 0 1 13rem; }
        .tx-toolbar-select select.tx-field { padding-top: 0.72rem; padding-bottom: 0.72rem; }
        .tx-toolbar-reset {
            border: 1px solid var(--tx-line);
            background: transparent;
            color: var(--tx-ink-soft);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.68rem 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .tx-toolbar-reset:hover { border-color: var(--tx-danger); color: var(--tx-danger); }
        .tx-result-count {
            font-size: 0.75rem;
            color: var(--tx-ink-faint);
            padding: 0.85rem 1.75rem 0;
        }
        .tx-empty-state {
            padding: 3rem 1.75rem;
            text-align: center;
            color: var(--tx-ink-faint);
            font-size: 0.875rem;
        }

        /* Pagination */
        .tx-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 1.75rem;
            border-top: 1px solid var(--tx-line);
        }
        .tx-pagination-info { font-size: 0.78rem; color: var(--tx-ink-soft); }
        .tx-pagination-controls { display: flex; align-items: center; gap: 0.6rem; }
        .tx-page-size.tx-field { width: auto; padding: 0.5rem 0.85rem; font-size: 0.78rem; }
        .tx-page-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.1rem; height: 2.1rem;
            border: 1px solid var(--tx-line);
            background: var(--tx-surface);
            border-radius: 10px;
            cursor: pointer;
            color: var(--tx-ink);
            transition: all .15s ease;
        }
        .tx-page-btn svg { width: 1rem; height: 1rem; }
        .tx-page-btn:hover:not(:disabled) { border-color: var(--tx-primary); color: var(--tx-primary); }
        .tx-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .tx-page-current { font-size: 0.8rem; font-weight: 600; color: var(--tx-ink-soft); min-width: 3.5rem; text-align: center; }

        /* Table / tree */
        .tx-table-wrap { overflow-x: auto; }
        table.tx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table.tx-table thead th {
            text-align: left;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--tx-ink-faint);
            padding: 0.9rem 1.2rem;
            border-bottom: 1px solid var(--tx-line);
            background: var(--tx-bg);
        }
        table.tx-table tbody td {
            padding: 0.85rem 1.2rem;
            border-bottom: 1px solid var(--tx-line);
            vertical-align: middle;
        }
        table.tx-table tbody tr:last-child td { border-bottom: none; }
        table.tx-table tbody tr:hover { background: var(--tx-bg); }
        .tx-code-badge {
            font-family: var(--tx-font-mono);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 7px;
            background: var(--tx-ink);
            color: var(--tx-bg);
            display: inline-block;
            white-space: nowrap;
        }
        .tx-dash { color: var(--tx-ink-faint); }
        .tx-crumb { display: flex; align-items: center; flex-wrap: wrap; gap: 0.35rem; font-size: 0.8rem; }
        .tx-crumb span.node { color: var(--tx-ink); }
        .tx-crumb span.arrow { color: var(--tx-ink-faint); }
        .tx-swatch {
            width: 0.5rem; height: 0.5rem; border-radius: 999px; display: inline-block; margin-right: 0.5rem; flex-shrink: 0;
        }
        .tx-cell-primary { display:flex; align-items:center; }
    </style>

    <div class="tx-console">
        <div class="tx-shell">

            {{-- Page Header --}}
            <div class="tx-header">
                <div>
                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.index') }}">Product Database</a>
                        <span>/</span>
                        <span>Settings</span>
                    </div>
                    <h1 class="tx-title tx-display">Taxonomy Console</h1>
                    <p class="tx-subtitle">Build out the catalog hierarchy — Category, Sub Category, Sub Sub Category and Collection — plus the shared Materials list used across products.</p>
                </div>
                <a href="{{ route('mi_app.index') }}" class="tx-back">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Database
                </a>
            </div>

            {{-- Signature: hierarchy ladder / anchor nav --}}
            <nav class="tx-ladder" aria-label="Taxonomy levels">
                <a href="#level-category" class="tx-rung lvl-1">
                    <span class="tx-rung-dot"></span>
                    <span>
                        <span class="tx-rung-label tx-display">Category</span><br>
                        <span class="tx-rung-sub">{{ $categories->count() }} defined</span>
                    </span>
                </a>
                <a href="#level-subcategory" class="tx-rung lvl-2">
                    <span class="tx-rung-dot"></span>
                    <span>
                        <span class="tx-rung-label tx-display">Sub Category</span><br>
                        <span class="tx-rung-sub">{{ $subCategories->count() }} defined</span>
                    </span>
                </a>
                <a href="#level-subsubcategory" class="tx-rung lvl-3">
                    <span class="tx-rung-dot"></span>
                    <span>
                        <span class="tx-rung-label tx-display">Sub Sub Category</span><br>
                        <span class="tx-rung-sub">{{ $productTypes->count() }} defined</span>
                    </span>
                </a>
                <a href="#level-collection" class="tx-rung lvl-4">
                    <span class="tx-rung-dot"></span>
                    <span>
                        <span class="tx-rung-label tx-display">Collection</span><br>
                        <span class="tx-rung-sub">Newest tier</span>
                    </span>
                </a>
            </nav>

            {{-- SECTION 1: Category --}}
            <div class="tx-card lvl-1" id="level-category">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="category">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">01</span>
                        <div>
                            <h2>Category</h2>
                            <p>Top-level grouping — the root of every product's taxonomy code.</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="category_name" class="tx-label">Category Name <span class="tx-required">*</span></label>
                            <input type="text" id="category_name" name="category_name" value="{{ old('category_name') }}" placeholder="e.g. Furniture" required class="tx-field">
                            @error('category_name')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div style="align-self:end;">
                            <button type="submit" class="tx-btn">Add Category</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 2: Sub Category --}}
            <div class="tx-card lvl-2" id="level-subcategory">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="sub_category">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">02</span>
                        <div>
                            <h2>Sub Category</h2>
                            <p>Nested one level under a Category.</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="subcat_category_id" class="tx-label">Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="category_id" id="subcat_category_id" required class="tx-field">
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
                                <p class="tx-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sub_category_name" class="tx-label">Sub Category Name <span class="tx-required">*</span></label>
                            <input type="text" id="sub_category_name" name="sub_category_name" value="{{ old('sub_category_name') }}" placeholder="e.g. Chairs" required class="tx-field">
                            @error('sub_category_name')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="align-self:end;">
                            <button type="submit" class="tx-btn">Add Sub Category</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 3: Sub Sub Category --}}
            <div class="tx-card lvl-3" id="level-subsubcategory">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="product_type">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">03</span>
                        <div>
                            <h2>Sub Sub Category</h2>
                            <p>Nested under a Sub Category — the most specific product type tier.</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="ssc_category_id" class="tx-label">Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="category_id" id="ssc_category_id" required data-cascade-target="ssc_subcategory_id" class="tx-field">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"> {{ $category->code }} - {{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="ssc_subcategory_id" class="tx-label">Sub Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="sub_category_id" id="ssc_subcategory_id" required class="tx-field">
                                    <option value="">-- Select Category First --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->category_id }}" class="hidden">
                                            {{ $subCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="product_type_name" class="tx-label">Sub Sub Category Name <span class="tx-required">*</span></label>
                            <input type="text" id="product_type_name" name="product_type_name" value="{{ old('product_type_name') }}" placeholder="e.g. Dining Chairs" required class="tx-field">
                        </div>

                        <div style="align-self:end;">
                            <button type="submit" class="tx-btn">Add Sub Sub Category</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 4: Collection --}}
            <div class="tx-card lvl-4" id="level-collection">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="collection">
                    <div class="tx-card-head">
                        <span class="tx-card-icon">04</span>
                        <div>
                            <h2>Collection</h2>
                            <p>Nested under a Sub Sub Category — usually a seasonal or thematic drop.</p>
                        </div>
                    </div>
                    <div class="tx-card-body cols-4">
                        <div>
                            <label for="col_category_id" class="tx-label">Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="category_id" id="col_category_id" required data-cascade-target="col_subcategory_id" class="tx-field">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="col_subcategory_id" class="tx-label">Sub Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="sub_category_id" id="col_subcategory_id" required data-cascade-target="col_subsubcategory_id" class="tx-field">
                                    <option value="">-- Select Category First --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->category_id }}" class="hidden">
                                           {{ $subCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="col_subsubcategory_id" class="tx-label">Sub Sub Category <span class="tx-required">*</span></label>
                            <div class="tx-select-wrap">
                                <select name="product_type_id" id="col_subsubcategory_id" required class="tx-field">
                                    <option value="">-- Select Sub Category First --</option>
                                    @foreach($productTypes as $productType)
                                        <option value="{{ $productType->id }}"
                                            data-parent="{{ $productType->sub_category_id }}"
                                            class="hidden">
                                            {{ $productType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="collection_name" class="tx-label">Collection Name <span class="tx-required">*</span></label>
                            <input type="text" id="collection_name" name="collection_name" value="{{ old('collection_name') }}" placeholder="e.g. Spring 2026" required class="tx-field">
                        </div>
                    </div>

                    <div style="padding: 0 1.75rem 1.75rem;">
                        <button type="submit" class="tx-btn tx-btn-static">Add Collection</button>
                    </div>
                </form>
            </div>

            {{-- Hierarchy Overview Table --}}
            <div class="tx-card">
                <div class="tx-card-head">
                    <span class="tx-card-icon" style="background: var(--tx-line); color: var(--tx-ink-soft);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h12M3 17h6" /></svg>
                    </span>
                    <div>
                        <h2>Taxonomy Structure</h2>
                        <p>Full mapping from Category down to Collection, with generated taxonomy codes.</p>
                    </div>
                </div>

                {{-- Search / filter toolbar --}}
                <div class="tx-toolbar">
                    <div class="tx-search">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="tx-search-input" class="tx-field" placeholder="Search category, sub category, type, collection or code…" autocomplete="off">
                    </div>
                    <div class="tx-select-wrap tx-toolbar-select">
                        <select id="tx-filter-category" class="tx-field">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>
                            @endforeach
                        </select>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <div class="tx-select-wrap tx-toolbar-select">
                        <select id="tx-filter-level" class="tx-field">
                            <option value="">All Levels</option>
                            <option value="1">Category only</option>
                            <option value="2">Sub Category</option>
                            <option value="3">Sub Sub Category</option>
                            <option value="4">Collection</option>
                        </select>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <button type="button" id="tx-filter-reset" class="tx-toolbar-reset">Clear</button>
                </div>
                <p id="tx-result-count" class="tx-result-count"></p>
                <div class="tx-table-wrap">
                    <table class="tx-table" id="tx-table">
                        <thead>
                            <tr>
                                <th>Taxonomy Code</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Product Type</th>
                                <th>Collection</th>
                                <th>Hierarchy</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="tx-tbody">
                        @foreach($categories as $category)

                            {{-- Category only --}}
                            @if($category->subCategories->isEmpty())
                                <tr data-level="1" data-cat="{{ $category->id }}" data-search="{{ strtolower($category->code.' '.$category->name) }}">
                                    <td><span class="tx-code-badge tx-mono">{{ $category->code }}</span></td>
                                    <td>
                                        <span class="tx-cell-primary"><span class="tx-swatch" style="background: var(--tx-lvl-1);"></span>{{ $category->name }}</span>
                                    </td>
                                    <td class="tx-dash">—</td>
                                    <td class="tx-dash">—</td>
                                    <td class="tx-dash">—</td>
                                    <td>
                                        <span class="tx-crumb"><span class="node">{{ $category->name }}</span></span>
                                    </td>
                                    <td class="tx-actions">
                                        <a href="{{ route('categories.edit', $category->id) }}" class="tx-btn tx-btn-edit" title="Edit">Edit</a>
                                        <form action="{{ route('categories.archive', $category->id) }}" method="POST" class="tx-inline-form" onsubmit="return confirm('Archive this category?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="tx-btn tx-btn-archive" title="Archive">Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif

                            @foreach($category->subCategories as $subCategory)

                                {{-- Category + Sub Category --}}
                                @if($subCategory->productTypes->isEmpty())
                                    <tr data-level="2" data-cat="{{ $category->id }}" data-search="{{ strtolower($category->code.' '.$subCategory->code.' '.$category->name.' '.$subCategory->name) }}">
                                        <td><span class="tx-code-badge tx-mono">{{ $category->code }}-{{ $subCategory->code }}</span></td>
                                        <td>{{ $category->name }}</td>
                                        <td>
                                            <span class="tx-cell-primary"><span class="tx-swatch" style="background: var(--tx-lvl-2);"></span>{{ $subCategory->name }}</span>
                                        </td>
                                        <td class="tx-dash">—</td>
                                        <td class="tx-dash">—</td>
                                        <td>
                                            <span class="tx-crumb">
                                                <span class="node">{{ $category->name }}</span>
                                                <span class="arrow">→</span>
                                                <span class="node">{{ $subCategory->name }}</span>
                                            </span>
                                        </td>
                                        <td class="tx-actions">
                                            <a href="{{ route('sub-categories.edit', $subCategory->id) }}" class="tx-btn tx-btn-edit" title="Edit">Edit</a>
                                            <form action="{{ route('sub-categories.archive', $subCategory->id) }}" method="POST" class="tx-inline-form" onsubmit="return confirm('Archive this sub category?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="tx-btn tx-btn-archive" title="Archive">Archive</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif

                                @foreach($subCategory->productTypes as $productType)

                                    {{-- Category + Sub Category + Product Type --}}
                                    @if($productType->collections->isEmpty())
                                        <tr data-level="3" data-cat="{{ $category->id }}" data-search="{{ strtolower($category->code.' '.$subCategory->code.' '.$productType->code.' '.$category->name.' '.$subCategory->name.' '.$productType->name) }}">
                                            <td><span class="tx-code-badge tx-mono">{{ $category->code }}-{{ $subCategory->code }}-{{ $productType->code }}</span></td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $subCategory->name }}</td>
                                            <td>
                                                <span class="tx-cell-primary"><span class="tx-swatch" style="background: var(--tx-lvl-3);"></span>{{ $productType->name }}</span>
                                            </td>
                                            <td class="tx-dash">—</td>
                                            <td>
                                                <span class="tx-crumb">
                                                    <span class="node">{{ $category->name }}</span>
                                                    <span class="arrow">→</span>
                                                    <span class="node">{{ $subCategory->name }}</span>
                                                    <span class="arrow">→</span>
                                                    <span class="node">{{ $productType->name }}</span>
                                                </span>
                                            </td>
<td class="tx-actions">
    <a href="{{ route('taxonomy.edit', ['type' => 'product_type', 'product' => $productType->id]) }}"
       class="tx-btn tx-btn-edit">
        Edit
    </a>

    <form action="{{ route('taxonomy.destroy', ['type' => 'product_type', 'product' => $productType->id]) }}"
          method="POST"
          class="tx-inline-form"
          onsubmit="return confirm('Archive this Product Type?');">
        @csrf
        @method('DELETE')

        <button type="submit" class="tx-btn tx-btn-archive">
            Archive
        </button>
    </form>
</td>
                                        </tr>
                                    @endif

                                    @foreach($productType->collections as $collection)

                                        {{-- Complete taxonomy --}}
                                        <tr data-level="4" data-cat="{{ $category->id }}" data-search="{{ strtolower($category->code.' '.$subCategory->code.' '.$productType->code.' '.$collection->code.' '.$category->name.' '.$subCategory->name.' '.$productType->name.' '.$collection->name) }}">
                                            <td><span class="tx-code-badge tx-mono">{{ $category->code }}-{{ $subCategory->code }}-{{ $productType->code }}-{{ $collection->code }}</span></td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $subCategory->name }}</td>
                                            <td>{{ $productType->name }}</td>
                                            <td>
                                                <span class="tx-cell-primary"><span class="tx-swatch" style="background: var(--tx-lvl-4);"></span>{{ $collection->name }}</span>
                                            </td>
                                            <td>
                                                <span class="tx-crumb">
                                                    <span class="node">{{ $category->name }}</span>
                                                    <span class="arrow">→</span>
                                                    <span class="node">{{ $subCategory->name }}</span>
                                                    <span class="arrow">→</span>
                                                    <span class="node">{{ $productType->name }}</span>
                                                    <span class="arrow">→</span>
                                                    <span class="node">{{ $collection->name }}</span>
                                                </span>
                                            </td>
                                            <td class="tx-actions">
                                                <a href="{{ route('collections.edit', $collection->id) }}" class="tx-btn tx-btn-edit" title="Edit">Edit</a>
                                                <form action="{{ route('collections.archive', $collection->id) }}" method="POST" class="tx-inline-form" onsubmit="return confirm('Archive this collection?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="tx-btn tx-btn-archive" title="Archive">Archive</button>
                                                </form>
                                            </td>
                                        </tr>

                                    @endforeach

                                @endforeach

                            @endforeach

                        @endforeach
                        </tbody>
                    </table>
                    <div id="tx-empty-state" class="tx-empty-state" hidden>
                        <p>No taxonomy rows match your search or filters.</p>
                    </div>
                </div>

                <div class="tx-pagination" id="tx-pagination">
                    <div class="tx-pagination-info">
                        Showing <span id="tx-page-from">0</span>–<span id="tx-page-to">0</span> of <span id="tx-page-total">0</span>
                    </div>
                    <div class="tx-pagination-controls">
                        <select id="tx-page-size" class="tx-field tx-page-size">
                            <option value="10">10 / page</option>
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                        <button type="button" id="tx-prev-page" class="tx-page-btn" aria-label="Previous page">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <span class="tx-page-current"><span id="tx-page-num">1</span> / <span id="tx-page-count">1</span></span>
                        <button type="button" id="tx-next-page" class="tx-page-btn" aria-label="Next page">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cascading dropdown logic (unchanged behavior) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-cascade-target]').forEach(function (parentSelect) {
                parentSelect.addEventListener('change', function () {
                    var targetId = parentSelect.getAttribute('data-cascade-target');
                    var target = document.getElementById(targetId);
                    if (!target) return;

                    var selectedParent = parentSelect.value;
                    target.value = '';

                    Array.from(target.options).forEach(function (opt) {
                        if (!opt.value) return; // keep placeholder
                        var belongs = opt.getAttribute('data-parent') === selectedParent;
                        opt.classList.toggle('hidden', !belongs);
                        opt.disabled = !belongs;
                    });

                    // If this select also drives a further cascade (e.g. subcategory -> subsubcategory), reset it
                    var nextTargetId = target.getAttribute('data-cascade-target');
                    if (nextTargetId) {
                        var nextTarget = document.getElementById(nextTargetId);
                        if (nextTarget) {
                            nextTarget.value = '';
                            Array.from(nextTarget.options).forEach(function (opt) {
                                if (!opt.value) return;
                                opt.classList.add('hidden');
                                opt.disabled = true;
                            });
                        }
                    }
                });
            });
        });
    </script>

    {{-- Taxonomy table: search, filter, pagination (client-side) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tbody = document.getElementById('tx-tbody');
            if (!tbody) return;

            var allRows = Array.from(tbody.querySelectorAll('tr'));
            var searchInput = document.getElementById('tx-search-input');
            var categoryFilter = document.getElementById('tx-filter-category');
            var levelFilter = document.getElementById('tx-filter-level');
            var resetBtn = document.getElementById('tx-filter-reset');
            var pageSizeSelect = document.getElementById('tx-page-size');
            var prevBtn = document.getElementById('tx-prev-page');
            var nextBtn = document.getElementById('tx-next-page');
            var pageNumEl = document.getElementById('tx-page-num');
            var pageCountEl = document.getElementById('tx-page-count');
            var pageFromEl = document.getElementById('tx-page-from');
            var pageToEl = document.getElementById('tx-page-to');
            var pageTotalEl = document.getElementById('tx-page-total');
            var resultCountEl = document.getElementById('tx-result-count');
            var emptyState = document.getElementById('tx-empty-state');
            var tableWrap = document.querySelector('.tx-table-wrap');
            var paginationBar = document.getElementById('tx-pagination');

            var currentPage = 1;
            var pageSize = parseInt(pageSizeSelect.value, 10);

            function getFiltered() {
                var term = searchInput.value.trim().toLowerCase();
                var cat = categoryFilter.value;
                var level = levelFilter.value;

                return allRows.filter(function (row) {
                    if (term && row.getAttribute('data-search').indexOf(term) === -1) return false;
                    if (cat && row.getAttribute('data-cat') !== cat) return false;
                    if (level && row.getAttribute('data-level') !== level) return false;
                    return true;
                });
            }

            function render() {
                var filtered = getFiltered();
                var total = filtered.length;
                var pageCount = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage > pageCount) currentPage = pageCount;
                if (currentPage < 1) currentPage = 1;

                // hide every row, then show only the current page's slice of the filtered set
                allRows.forEach(function (row) { row.style.display = 'none'; });

                var start = (currentPage - 1) * pageSize;
                var end = Math.min(start + pageSize, total);
                filtered.slice(start, end).forEach(function (row) { row.style.display = ''; });

                pageNumEl.textContent = currentPage;
                pageCountEl.textContent = pageCount;
                pageFromEl.textContent = total === 0 ? 0 : start + 1;
                pageToEl.textContent = end;
                pageTotalEl.textContent = total;

                prevBtn.disabled = currentPage <= 1;
                nextBtn.disabled = currentPage >= pageCount;

                var term = searchInput.value.trim();
                var hasFilter = term || categoryFilter.value || levelFilter.value;
                resultCountEl.textContent = hasFilter
                    ? total + ' matching row' + (total === 1 ? '' : 's') + ' of ' + allRows.length + ' total'
                    : total + ' row' + (total === 1 ? '' : 's') + ' total';

                var isEmpty = total === 0;
                emptyState.hidden = !isEmpty;
                tableWrap.querySelector('table').style.display = isEmpty ? 'none' : '';
                paginationBar.style.display = isEmpty ? 'none' : '';
            }

            searchInput.addEventListener('input', function () { currentPage = 1; render(); });
            categoryFilter.addEventListener('change', function () { currentPage = 1; render(); });
            levelFilter.addEventListener('change', function () { currentPage = 1; render(); });
            pageSizeSelect.addEventListener('change', function () {
                pageSize = parseInt(pageSizeSelect.value, 10);
                currentPage = 1;
                render();
            });
            resetBtn.addEventListener('click', function () {
                searchInput.value = '';
                categoryFilter.value = '';
                levelFilter.value = '';
                currentPage = 1;
                render();
            });
            prevBtn.addEventListener('click', function () { currentPage--; render(); });
            nextBtn.addEventListener('click', function () { currentPage++; render(); });

            render();
        });
    </script>
</x-mi_app>