<x-mi_app>

    {{-- =========================================================
        METROINC CENTRALIZED DATABASE
        CREATE PRODUCT
        ========================================================= --}}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css"
        rel="stylesheet"
    >

    @include('mi_app.designer_module.partials._product-form-styles')


    <div class="tx-console">

        <div class="tx-shell">

            {{-- =====================================================
                HEADER
                ===================================================== --}}

            <header class="tx-header">

                <div class="tx-header-content">

                    <div class="tx-eyebrow">

                        <a href="{{ route('mi_app.index') }}">
                            Product Database
                        </a>

                        <span>/</span>

                        <span>
                            New Product
                        </span>

                    </div>

                    <h1 class="tx-title tx-display">
                        Create Product
                    </h1>

                    <p class="tx-subtitle">
                        Add the product classification, specifications,
                        dimensions, materials, and product media.
                    </p>

                </div>


                <a
                    href="{{ route('mi_app.index') }}"
                    class="tx-back"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
                        />
                    </svg>

                    Back to Database

                </a>

            </header>


            {{-- =====================================================
                REQUIRED FIELD PROGRESS
                ===================================================== --}}

            <div class="tx-progress-wrap">

                <span
                    id="progress_label"
                    class="tx-mono"
                >
                    0 / 0 required fields
                </span>

                <div class="tx-progress-track">

                    <div id="progress_bar"></div>

                </div>

            </div>


            {{-- =====================================================
                FORM
                ===================================================== --}}

            <form
                method="POST"
                action="{{ route('mi_app.store_1') }}"
                enctype="multipart/form-data"
                id="product_form"
                novalidate
            >

                @csrf

                @php
                    $saveError = $errors->first('error') ?: session('error');
                @endphp


                {{-- =================================================
                    SAVE ERROR
                    ================================================= --}}

                @if($saveError)

                    <div class="tx-alert">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z"
                            />
                        </svg>

                        <div>
                            <strong>
                                Unable to save the product.
                            </strong>

                            <div>
                                {{ $saveError }}
                            </div>
                        </div>

                    </div>

                @endif


                {{-- =================================================
                    VALIDATION ERRORS
                    ================================================= --}}

                @if($errors->any() && !$saveError)

                    <div class="tx-alert">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z"
                            />
                        </svg>

                        <ul style="margin:0; padding-left:15px;">

                            @foreach($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                @endif


                {{-- =================================================
                    SECTION 01 — TAXONOMY
                    ================================================= --}}

                <section
                    class="tx-card lvl-1"
                    id="taxonomy-section"
                >

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            01
                        </span>

                        <div>

                            <h2>
                                Taxonomy
                            </h2>

                            <p>
                                Category → Sub Category → Sub Sub Category → Collection
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        {{-- Category --}}
                        <div>

                            <label
                                for="category_id"
                                class="tx-label"
                            >
                                <span
                                    class="tx-lvl-dot"
                                    style="background:#2f5bef; color:#2f5bef;"
                                ></span>

                                Category

                                <span class="tx-required">
                                    *
                                </span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    id="category_id"
                                    name="category_id"
                                    required
                                    data-required
                                    data-cascade-target="sub_category_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach($categories as $category)

                                        <option
                                            value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}
                                        >
                                            {{ $category->code }} -
                                            {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('category_id')

                                <p class="tx-error">

                                    {{ $message }}

                                </p>

                            @enderror

                        </div>


                        {{-- Sub Category --}}
                        <div>

                            <label
                                for="sub_category_id"
                                class="tx-label"
                            >
                                <span
                                    class="tx-lvl-dot"
                                    style="background:#3b82f6; color:#3b82f6;"
                                ></span>

                                Sub Category

                                <span class="tx-required">
                                    *
                                </span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    id="sub_category_id"
                                    name="sub_category_id"
                                    required
                                    data-required
                                    data-cascade-target="product_type_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Category First --
                                    </option>

                                    @foreach($subCategories as $subCategory)

                                        <option
                                            value="{{ $subCategory->id }}"
                                            data-parent="{{ $subCategory->category_id }}"
                                            {{ old('sub_category_id') == $subCategory->id ? 'selected' : '' }}
                                        >
                                            {{ $subCategory->code }} -
                                            {{ $subCategory->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('sub_category_id')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Product Type --}}
                        <div>

                            <label
                                for="product_type_id"
                                class="tx-label"
                            >
                                <span
                                    class="tx-lvl-dot"
                                    style="background:#7c3aed; color:#7c3aed;"
                                ></span>

                                Sub Sub Category
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    id="product_type_id"
                                    name="product_type_id"
                                    data-cascade-target="collection_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Sub Category First --
                                    </option>

                                    @foreach($productTypes as $productType)

                                        <option
                                            value="{{ $productType->id }}"
                                            data-parent="{{ $productType->sub_category_id }}"
                                            {{ old('product_type_id') == $productType->id ? 'selected' : '' }}
                                        >
                                            {{ $productType->code }} -
                                            {{ $productType->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('product_type_id')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Collection --}}
                        <div>

                            <label
                                for="collection_id"
                                class="tx-label"
                            >
                                <span
                                    class="tx-lvl-dot"
                                    style="background:#8b5cf6; color:#8b5cf6;"
                                ></span>

                                Collection
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    id="collection_id"
                                    name="collection_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Sub Sub Category First --
                                    </option>

                                    @foreach($collections as $collection)

                                        <option
                                            value="{{ $collection->id }}"
                                            data-parent="{{ $collection->product_type_id }}"
                                            {{ old('collection_id') == $collection->id ? 'selected' : '' }}
                                        >
                                            {{ $collection->code }} -
                                            {{ $collection->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('collection_id')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>


                    <div
                        class="tx-taxonomy-preview"
                        id="taxonomy-preview"
                    >

                        <span class="tx-taxonomy-preview-label">
                            SKU / Taxonomy
                        </span>

                        <span
                            id="taxonomy-preview-path"
                            class="tx-mono"
                        >
                            Select a category to begin
                        </span>

                    </div>

                </section>


                {{-- =================================================
                    SECTION 02 — GENERAL INFORMATION
                    ================================================= --}}

                <section class="tx-card lvl-1">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            02
                        </span>

                        <div>

                            <h2>
                                General Information
                            </h2>

                            <p>
                                Basic identity and ownership information
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-4">

                        {{-- Item Name --}}
                        <div class="col-span-2">

                            <label
                                for="item_name"
                                class="tx-label"
                            >
                                Item Name

                                <span class="tx-required">
                                    *
                                </span>
                            </label>

                            <input
                                type="text"
                                id="item_name"
                                name="item_name"
                                value="{{ old('item_name') }}"
                                placeholder="e.g. Ergonomic Office Desk"
                                required
                                data-required
                                class="tx-field"
                            >

                            @error('item_name')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Sample Type --}}
                        <div class="col-span-2">

                            <label
                                for="type_of_sample"
                                class="tx-label"
                            >
                                Type of Sample

                                <span class="tx-required">
                                    *
                                </span>
                            </label>

                            <div class="tx-select-wrap">

                                <select
                                    id="type_of_sample"
                                    name="type_of_sample"
                                    required
                                    data-required
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Type of Sample --
                                    </option>

                                    <option
                                        value="Factory Design"
                                        {{ old('type_of_sample') == 'Factory Design' ? 'selected' : '' }}
                                    >
                                        Factory Design
                                    </option>

                                    <option
                                        value="Metroinc Design"
                                        {{ old('type_of_sample') == 'Metroinc Design' ? 'selected' : '' }}
                                    >
                                        Metroinc Design
                                    </option>

                                </select>

                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>

                            </div>

                            @error('type_of_sample')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Designer --}}
                        <div class="col-span-2">

                            <label
                                for="designed_by"
                                class="tx-label"
                            >
                                Designed By
                            </label>

                            <input
                                type="text"
                                id="designed_by"
                                name="designed_by"
                                value="{{ old('designed_by') }}"
                                placeholder="Designer full name"
                                class="tx-field"
                            >

                            @error('designed_by')

                                <p class="tx-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>

                </section>


                {{-- =================================================
                    SECTION 03 — ATTRIBUTES & DIMENSIONS
                    ================================================= --}}

                <section class="tx-card lvl-2">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            03
                        </span>

                        <div>

                            <h2>
                                Attributes & Dimensions
                            </h2>

                            <p>
                                Materials, colors, product measurements and packaging
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body">

                        {{-- Material / Color --}}
                        <div class="tx-card-body cols-2" style="padding:0;">

                            {{-- ================================================================
                                MATERIALS MULTI-SELECT
                                Single source of truth: $materialGroups
                                Near-duplicate names have been collapsed to one canonical value
                                per material. See materials-migration-mapping.md if you have
                                existing product data using the old names.
                            ================================================================= --}}

                            @php


                                // Values already chosen: old input first, then the model when editing.
                                $selectedMaterials = collect(old('materials', $materials ?? []))
                                    ->map(fn ($m) => (string) $m)
                                    ->all();
                            @endphp

                            {{-- Materials --}}
                            <div>

                                <label for="materials" class="tx-label">
                                    Materials
                                    <span class="tx-required">*</span>
                                </label>

                                <div class="tx-multi-select-wrap">

                                    <div class="tx-multi-toolbar">

                                        <span class="tx-multi-hint">
                                            Select one or more materials
                                        </span>

                                        <button
                                            type="button"
                                            class="tx-multi-clear"
                                            data-target="materials"
                                        >
                                            Clear
                                        </button>

                                    </div>

                                    <select
                                        id="materials"
                                        name="materials[]"
                                        multiple
                                        size="12"
                                        required
                                        data-required
                                        class="tx-field tx-multi-select materials-select"
                                        @error('materials') aria-invalid="true" aria-describedby="materials_error" @enderror
                                    >

                                        @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'materials', 'selectedValues' => $selectedMaterials])

                                    </select>

                                    <div
                                        id="materials_chips"
                                        class="tx-multi-chips"
                                        aria-live="polite"
                                    ></div>

                                </div>

                                @error('materials')
                                    <p id="materials_error" class="tx-error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- ================================================================
                                COLOR MULTI-SELECT
                                Single source of truth: $colorGroups
                            ================================================================= --}}

                            @php


                                // Values already chosen: old input first, then the model when editing.
                                $selectedColors = collect(old('color', $color ?? []))
                                    ->map(fn ($c) => (string) $c)
                                    ->all();
                            @endphp

                            {{-- Color --}}
                            <div>

                                <label for="color" class="tx-label">
                                    Color
                                </label>

                                <div class="tx-multi-select-wrap">

                                    <div class="tx-multi-toolbar">

                                        <span class="tx-multi-hint">
                                            Select one or more colors
                                        </span>

                                        <button
                                            type="button"
                                            class="tx-multi-clear"
                                            data-target="color"
                                        >
                                            Clear
                                        </button>

                                    </div>

                                    <select
                                        id="color"
                                        name="color[]"
                                        multiple
                                        size="8"
                                        class="tx-field tx-multi-select"
                                        @error('color') aria-invalid="true" aria-describedby="color_error" @enderror
                                    >

                                        @include('mi_app.designer_module.partials._attribute-options', ['attribute' => 'color', 'selectedValues' => $selectedColors])

                                    </select>

                                    <div
                                        id="color_chips"
                                        class="tx-multi-chips"
                                        aria-live="polite"
                                    ></div>

                                </div>

                                @error('color')
                                    <p id="color_error" class="tx-error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                        </div>


                        {{-- =================================================
                            PRODUCT / CARTON DIMENSIONS
                            ================================================= --}}

                        <div>

                            {{-- Product Dimensions --}}
                            <div class="tx-subpanel">

                                <div class="tx-subpanel-head">

                                    <div>

                                        <h3>
                                            Product Dimensions
                                        </h3>

                                        <p>
                                            Core measurements of the physical product.
                                        </p>

                                    </div>

                                    <span class="tx-subpanel-tag">

                                        <svg
                                            viewBox="0 0 32 32"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                d="M6 24 6 10 14 6 26 10 26 24 18 28 6 24Z"
                                                stroke-linejoin="round"
                                            />

                                            <path
                                                d="M6 10 18 14 26 10"
                                                stroke-linejoin="round"
                                            />

                                            <path
                                                d="M18 14 18 28"
                                                stroke-linejoin="round"
                                            />
                                        </svg>

                                        L × W × H × D

                                    </span>

                                </div>


                                <div class="tx-dims-grid">

                                    @foreach([
                                        'product_length' => ['Length', '120', false],
                                        'product_width'  => ['Width', '60', false],
                                        'product_height' => ['Height', '45', true],
                                        'product_depth'  => ['Depth', '30', false],
                                    ] as $field => $config)

                                        <div>

                                            <label
                                                for="{{ $field }}"
                                                class="tx-dim-label"
                                            >
                                                {{ $config[0] }}

                                                @if($config[2])
                                                    <span class="tx-required">*</span>
                                                @endif
                                            </label>

                                            <div class="tx-dim-input-wrap">

                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    inputmode="decimal"
                                                    id="{{ $field }}"
                                                    name="{{ $field }}"
                                                    value="{{ old($field) }}"
                                                    placeholder="{{ $config[1] }}"
                                                    class="tx-field"
                                                    data-unit-source="cm"
                                                    @if($config[2])
                                                        required
                                                        data-required
                                                    @endif
                                                >

                                                <span class="tx-dim-unit">
                                                    cm
                                                </span>

                                            </div>

                                            <span
                                                class="tx-dim-inches"
                                                id="{{ $field }}_in"
                                                data-inches-for="{{ $field }}"
                                            >
                                                — in
                                            </span>

                                            @error($field)

                                                <p class="tx-error">
                                                    {{ $message }}
                                                </p>

                                            @enderror

                                        </div>

                                    @endforeach

                                </div>

                            </div>


                            {{-- Carton Dimensions --}}
                            <div class="tx-subpanel">

                                <div class="tx-subpanel-head">

                                    <div>

                                        <h3>
                                            Carton Dimensions
                                        </h3>

                                        <p>
                                            Packaging footprint for shipping and storage.
                                        </p>

                                    </div>

                                    <span class="tx-subpanel-tag">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 7.5 12 3l9 4.5M3 7.5v9l9 4.5 9-4.5v-9M3 7.5l9 4.5 9-4.5"
                                            />
                                        </svg>

                                        Box Size

                                    </span>

                                </div>


                                <div class="tx-dims-grid">

                                    @foreach([
                                        'carton_length' => ['Length', '125'],
                                        'carton_width'  => ['Width', '65'],
                                        'carton_height' => ['Height', '50'],
                                        'carton_depth'  => ['Depth', '35'],
                                    ] as $field => $config)

                                        <div>

                                            <label
                                                for="{{ $field }}"
                                                class="tx-dim-label"
                                            >
                                                {{ $config[0] }}
                                            </label>

                                            <div class="tx-dim-input-wrap">

                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    inputmode="decimal"
                                                    id="{{ $field }}"
                                                    name="{{ $field }}"
                                                    value="{{ old($field) }}"
                                                    placeholder="{{ $config[1] }}"
                                                    class="tx-field"
                                                    data-unit-source="cm"
                                                >

                                                <span class="tx-dim-unit">
                                                    cm
                                                </span>

                                            </div>

                                            <span
                                                class="tx-dim-inches"
                                                id="{{ $field }}_in"
                                                data-inches-for="{{ $field }}"
                                            >
                                                — in
                                            </span>

                                            @error($field)

                                                <p class="tx-error">
                                                    {{ $message }}
                                                </p>

                                            @enderror

                                        </div>

                                    @endforeach

                                </div>

                            </div>

                            <script>
                                (function () {
                                    const CM_TO_IN = 2.54;

                                    function updateInches(input) {
                                        const display = document.getElementById(input.id + '_in');
                                        if (!display) return;

                                        const cm = parseFloat(input.value);

                                        if (isNaN(cm) || cm <= 0) {
                                            display.textContent = '— in';
                                            return;
                                        }

                                        const inches = cm / CM_TO_IN;
                                        display.textContent = inches.toFixed(2) + ' in';
                                    }

                                    document
                                        .querySelectorAll('.tx-field[data-unit-source="cm"]')
                                        .forEach(function (input) {
                                            // convert on page load in case of old() values
                                            updateInches(input);

                                            input.addEventListener('input', function () {
                                                updateInches(input);
                                            });
                                        });
                                })();
                            </script>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                    SECTION 04 — MEDIA
                    ================================================= --}}

                <section class="tx-card lvl-3">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            04
                        </span>

                        <div>

                            <h2>
                                Media & Images
                            </h2>

                            <p>
                                Product image links and uploaded product files
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body">

                        <div class="tx-upload-section">

                            {{-- =================================================
                                IMAGE LINKS
                                ================================================= --}}

                            <div class="tx-link-box">

                                <label
                                    for="image_link"
                                    class="tx-label"
                                >
                                    Product Image Links
                                </label>

                                <p class="tx-hint">
                                    Add direct URLs to product images.
                                </p>

                                @php
                                    $imageLinks = old('image_links', []);
                                @endphp

                                <div
                                    id="imageLinks"
                                    class="tx-link-list"
                                >

                                    @if(is_array($imageLinks) && count($imageLinks))

                                        @foreach($imageLinks as $index => $link)

                                            <div class="tx-image-link-row">

                                                <input
                                                    type="url"
                                                    name="image_links[]"
                                                    value="{{ $link }}"
                                                    placeholder="https://example.com/image.jpg"
                                                    class="tx-field"
                                                >

                                                @if($index > 0)

                                                    <button
                                                        type="button"
                                                        class="tx-link-remove"
                                                        onclick="removeImageLink(this)"
                                                        aria-label="Remove image link"
                                                    >
                                                        ×
                                                    </button>

                                                @endif

                                            </div>

                                        @endforeach

                                    @else

                                        <div class="tx-image-link-row">

                                            <input
                                                type="url"
                                                name="image_links[]"
                                                value="{{ old('image_links.0') }}"
                                                placeholder="https://example.com/image.jpg"
                                                class="tx-field"
                                            >

                                        </div>

                                    @endif

                                </div>


                                <button
                                    type="button"
                                    onclick="addImageLink()"
                                    class="tx-btn-small"
                                >
                                    + Add Image Link
                                </button>


                                @if(
                                    $errors->has('image_links') ||
                                    $errors->has('image_links.*')
                                )

                                    <p class="tx-error">
                                        {{ $errors->first('image_links.*') ?? $errors->first('image_links') }}
                                    </p>

                                @endif

                            </div>


                            {{-- =================================================
                                UPLOAD
                                ================================================= --}}

                            <div>

                                <label class="tx-label">
                                    Upload Product Files
                                </label>

                                <p class="tx-hint">
                                    PNG, JPG, WebP, PDF, OBJ or STL — maximum 5MB per file.
                                </p>


                                <div
                                    id="dropzone"
                                    class="tx-dropzone"
                                >

                                    {{-- Empty --}}
                                    <div
                                        id="dropzone_empty"
                                        class="tx-dropzone-empty"
                                    >

                                        <div class="tx-dropzone-icon">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.6"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z"
                                                />
                                            </svg>

                                        </div>

                                        <div>

                                            <p class="tx-dz-title">
                                                Click to upload or drag and drop
                                            </p>

                                            <p class="tx-dz-sub">
                                                Multiple files supported
                                            </p>

                                        </div>

                                    </div>


                                    {{-- Filled --}}
                                    <div
                                        id="dropzone_filled"
                                        class="tx-dropzone-filled"
                                    >

                                        <div class="tx-file-summary">

                                            <div
                                                id="file_thumb"
                                                class="tx-file-thumb"
                                            ></div>

                                            <div class="tx-file-meta">

                                                <div
                                                    id="file_name"
                                                    class="tx-file-name"
                                                ></div>

                                                <div
                                                    id="file_size"
                                                    class="tx-file-size"
                                                ></div>

                                            </div>

                                            <button
                                                id="file_remove"
                                                type="button"
                                                class="tx-file-remove"
                                                aria-label="Remove selected files"
                                            >

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12"
                                                    />
                                                </svg>

                                            </button>

                                        </div>

                                        <div
                                            id="file_count"
                                            class="tx-file-count"
                                        ></div>

                                    </div>


                                    <input
                                        type="file"
                                        id="product_file"
                                        name="product_images[]"
                                        accept="image/*,.pdf,.obj,.stl"
                                        multiple
                                        style="
                                            position:absolute;
                                            inset:0;
                                            width:100%;
                                            height:100%;
                                            cursor:pointer;
                                            opacity:0;
                                        "
                                    >

                                </div>


                                @if(
                                    $errors->has('product_images') ||
                                    $errors->has('product_images.*')
                                )

                                    <p class="tx-error">
                                        {{ $errors->first('product_images.*') ?? $errors->first('product_images') }}
                                    </p>

                                @endif

                            </div>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                    FOOTER
                    ================================================= --}}

                <div class="tx-footer">

                    <div class="tx-footer-inner">

                        <a
                            href="{{ route('mi_app.index') }}"
                            class="tx-btn-ghost"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="submit_btn"
                            class="tx-btn-submit"
                        >

                            <svg
                                id="submit_icon"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 12.75l6 6 9-13.5"
                                />
                            </svg>

                            <svg
                                id="submit_spinner"
                                class="hidden spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    opacity=".25"
                                />

                                <path
                                    fill="currentColor"
                                    opacity=".75"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />

                            </svg>

                            <span id="submit_label">
                                Save Product
                            </span>

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- =============================================================
        TOM SELECT
        ============================================================= --}}

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>


    <script>

        /* =========================================================
           IMAGE LINKS
           ========================================================= */

        function addImageLink() {

            const container =
                document.getElementById('imageLinks');

            if (!container) return;

            const row =
                document.createElement('div');

            row.className =
                'tx-image-link-row';

            row.innerHTML = `
                <input
                    type="url"
                    name="image_links[]"
                    placeholder="https://example.com/image.jpg"
                    class="tx-field"
                >

                <button
                    type="button"
                    class="tx-link-remove"
                    onclick="removeImageLink(this)"
                    aria-label="Remove image link"
                >
                    ×
                </button>
            `;

            container.appendChild(row);

        }


        function removeImageLink(button) {

            const row =
                button.closest('.tx-image-link-row');

            if (row) {
                row.remove();
            }

        }


        /* =========================================================
           INITIALIZE
           ========================================================= */

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                /* -------------------------------------------------
                   TOM SELECT — COLOR
                   ------------------------------------------------- */

                const colorSelect =
                    document.getElementById('color');

                if (colorSelect) {

                    new TomSelect(
                        colorSelect,
                        {
                            plugins: ['remove_button'],

                            maxItems: 100,

                            create: false,

                            closeAfterSelect: false,

                            hideSelected: true,

                            placeholder:
                                'Select one or more colors...'
                        }
                    );

                }


                /* -------------------------------------------------
                   TOM SELECT — MATERIALS
                   ------------------------------------------------- */

                const materialsSelect =
                    document.getElementById('materials');

                if (materialsSelect) {

                    new TomSelect(
                        materialsSelect,
                        {
                            plugins: ['remove_button'],

                            create: false,

                            maxItems: 100,

                            hideSelected: true,

                            closeAfterSelect: false,

                            placeholder:
                                'Select one or more materials...',

                            searchField: ['text'],

                            render: {

                                no_results:
                                    function (data, escape) {

                                        return `
                                            <div class="no-results">
                                                No material found
                                            </div>
                                        `;

                                    }

                            }

                        }
                    );

                }

            }
        );

    </script>


    <script>

        (function () {

            /* =====================================================
               TAXONOMY CASCADE
               ===================================================== */

            const taxonomySection =
                document.getElementById(
                    'taxonomy-section'
                );

            if (!taxonomySection) return;


            const categorySelect =
                document.getElementById(
                    'category_id'
                );

            const subCategorySelect =
                document.getElementById(
                    'sub_category_id'
                );

            const productTypeSelect =
                document.getElementById(
                    'product_type_id'
                );

            const collectionSelect =
                document.getElementById(
                    'collection_id'
                );


            function cascadeFrom(
                parentSelect,
                resetValue
            ) {

                const targetId =
                    parentSelect.getAttribute(
                        'data-cascade-target'
                    );

                const target =
                    document.getElementById(targetId);

                if (!target) return;


                const selectedParent =
                    parentSelect.value;


                if (resetValue) {
                    target.value = '';
                }


                Array
                    .from(target.options)
                    .forEach(function (option) {

                        if (!option.value) return;

                        const belongs =
                            option.getAttribute(
                                'data-parent'
                            ) === selectedParent;

                        option.hidden =
                            !belongs;

                        option.disabled =
                            !belongs;

                    });


                const nextTargetId =
                    target.getAttribute(
                        'data-cascade-target'
                    );


                if (
                    nextTargetId &&
                    resetValue
                ) {

                    const nextTarget =
                        document.getElementById(
                            nextTargetId
                        );

                    if (nextTarget) {

                        nextTarget.value = '';

                        Array
                            .from(nextTarget.options)
                            .forEach(function (option) {

                                if (!option.value) return;

                                option.hidden = true;
                                option.disabled = true;

                            });

                    }

                }

            }


            [
                categorySelect,
                subCategorySelect,
                productTypeSelect
            ].forEach(function (select) {

                if (!select) return;

                select.addEventListener(
                    'change',
                    function () {

                        cascadeFrom(
                            select,
                            true
                        );

                        updateTaxonomyPreview();

                    }
                );

            });


            /* =====================================================
               TAXONOMY PREVIEW
               ===================================================== */

            const previewPath =
                document.getElementById(
                    'taxonomy-preview-path'
                );


            function labelOf(select) {

                if (!select) return null;

                const option =
                    select.options[
                        select.selectedIndex
                    ];

                return option &&
                    option.value
                    ? option.textContent.trim()
                    : null;

            }


            function updateTaxonomyPreview() {

                if (!previewPath) return;

                const parts = [
                    categorySelect,
                    subCategorySelect,
                    productTypeSelect,
                    collectionSelect
                ]
                    .map(labelOf)
                    .filter(Boolean);


                previewPath.textContent =
                    parts.length
                        ? parts.join('  →  ')
                        : 'Select a category to begin';

            }


            [
                categorySelect,
                subCategorySelect,
                productTypeSelect,
                collectionSelect
            ].forEach(function (select) {

                if (!select) return;

                select.addEventListener(
                    'change',
                    updateTaxonomyPreview
                );

            });


            /* -----------------------------------------------------
               REAPPLY OLD VALUES
               ----------------------------------------------------- */

            if (
                categorySelect &&
                categorySelect.value
            ) {

                cascadeFrom(
                    categorySelect,
                    false
                );

            }


            if (
                subCategorySelect &&
                subCategorySelect.value
            ) {

                cascadeFrom(
                    subCategorySelect,
                    false
                );

            }


            if (
                productTypeSelect &&
                productTypeSelect.value
            ) {

                cascadeFrom(
                    productTypeSelect,
                    false
                );

            }


            updateTaxonomyPreview();


            /* =====================================================
               REQUIRED FIELD PROGRESS
               ===================================================== */

            const requiredFields =
                Array
                    .prototype
                    .slice
                    .call(
                        document.querySelectorAll(
                            '[data-required]'
                        )
                    );


            const progressBar =
                document.getElementById(
                    'progress_bar'
                );

            const progressLabel =
                document.getElementById(
                    'progress_label'
                );


            function fieldHasValue(field) {

                if (
                    field.tagName === 'SELECT' &&
                    field.multiple
                ) {

                    return Array
                        .from(field.selectedOptions)
                        .some(function (option) {

                            return option.value.trim() !== '';

                        });

                }


                return (
                    field.value &&
                    field.value.trim() !== ''
                );

            }


            function updateProgress() {

                const filled =
                    requiredFields.filter(
                        fieldHasValue
                    ).length;


                const total =
                    requiredFields.length;


                const percentage =
                    total
                        ? Math.round(
                            (filled / total) * 100
                        )
                        : 0;


                if (progressBar) {

                    progressBar.style.width =
                        percentage + '%';

                }


                if (progressLabel) {

                    progressLabel.textContent =
                        filled +
                        ' / ' +
                        total +
                        ' required fields';

                }

            }


            requiredFields.forEach(
                function (field) {

                    field.addEventListener(
                        'input',
                        updateProgress
                    );

                    field.addEventListener(
                        'change',
                        updateProgress
                    );

                }
            );


            updateProgress();


            /* =====================================================
               MULTI SELECT CHIPS
               ===================================================== */

            document
                .querySelectorAll(
                    'select.tx-multi-select'
                )
                .forEach(function (select) {

                    const wrapper =
                        select.closest(
                            '.tx-multi-select-wrap'
                        );

                    if (!wrapper) return;


                    const chips =
                        wrapper.querySelector(
                            '.tx-multi-chips'
                        );

                    const clearButton =
                        wrapper.querySelector(
                            '.tx-multi-clear'
                        );


                    function updateChips() {

                        if (!chips) return;


                        const selectedValues =
                            Array
                                .from(
                                    select.selectedOptions
                                )
                                .map(
                                    option =>
                                        option.value
                                )
                                .filter(Boolean);


                        chips.innerHTML = '';


                        selectedValues.forEach(
                            function (value) {

                                const chip =
                                    document.createElement(
                                        'span'
                                    );

                                chip.className =
                                    'tx-multi-chip';


                                const text =
                                    document.createElement(
                                        'span'
                                    );

                                text.textContent =
                                    value;


                                const remove =
                                    document.createElement(
                                        'button'
                                    );

                                remove.type =
                                    'button';

                                remove.setAttribute(
                                    'aria-label',
                                    'Remove ' + value
                                );

                                remove.innerHTML =
                                    '&times;';


                                remove.addEventListener(
                                    'click',
                                    function (event) {

                                        event.preventDefault();

                                        if (
                                            select.tomselect
                                        ) {

                                            select.tomselect
                                                .removeItem(
                                                    value
                                                );

                                        } else {

                                            Array
                                                .from(
                                                    select.options
                                                )
                                                .forEach(
                                                    function (
                                                        option
                                                    ) {

                                                        if (
                                                            option.value ===
                                                            value
                                                        ) {

                                                            option.selected =
                                                                false;

                                                        }

                                                    }
                                                );

                                        }

                                        window.setTimeout(
                                            updateChips,
                                            0
                                        );

                                    }
                                );


                                chip.appendChild(text);
                                chip.appendChild(remove);

                                chips.appendChild(chip);

                            }
                        );


                        if (clearButton) {

                            clearButton.style.display =
                                selectedValues.length
                                    ? 'inline-flex'
                                    : 'none';

                        }

                    }


                    select.addEventListener(
                        'change',
                        function () {

                            window.setTimeout(
                                updateChips,
                                0
                            );

                            updateProgress();

                        }
                    );


                    if (select.tomselect) {

                        select.tomselect.on(
                            'item_add item_remove',
                            function () {

                                window.setTimeout(
                                    updateChips,
                                    0
                                );

                                updateProgress();

                            }
                        );

                    }


                    if (clearButton) {

                        clearButton.addEventListener(
                            'click',
                            function (event) {

                                event.preventDefault();

                                if (
                                    select.tomselect
                                ) {

                                    select.tomselect.clear();

                                } else {

                                    Array
                                        .from(
                                            select.options
                                        )
                                        .forEach(
                                            function (option) {

                                                option.selected =
                                                    false;

                                            }
                                        );

                                }

                                window.setTimeout(
                                    updateChips,
                                    0
                                );

                                updateProgress();

                            }
                        );

                    }


                    updateChips();

                });


            /* =====================================================
               FILE UPLOAD
               ===================================================== */

            const dropzone =
                document.getElementById(
                    'dropzone'
                );

            const fileInput =
                document.getElementById(
                    'product_file'
                );

            const emptyState =
                document.getElementById(
                    'dropzone_empty'
                );

            const filledState =
                document.getElementById(
                    'dropzone_filled'
                );

            const fileName =
                document.getElementById(
                    'file_name'
                );

            const fileSize =
                document.getElementById(
                    'file_size'
                );

            const fileThumb =
                document.getElementById(
                    'file_thumb'
                );

            const fileCount =
                document.getElementById(
                    'file_count'
                );

            const removeBtn =
                document.getElementById(
                    'file_remove'
                );


            function formatBytes(bytes) {

                if (!bytes) {
                    return '0 KB';
                }


                const kb =
                    bytes / 1024;


                if (kb < 1024) {

                    return (
                        kb.toFixed(0) +
                        ' KB'
                    );

                }


                return (
                    (kb / 1024).toFixed(1) +
                    ' MB'
                );

            }


            function showFiles(fileList) {

                if (
                    !fileList ||
                    !fileList.length
                ) {
                    return;
                }


                const files =
                    Array.from(fileList);


                const totalSize =
                    files.reduce(
                        function (
                            total,
                            file
                        ) {

                            return (
                                total +
                                file.size
                            );

                        },
                        0
                    );


                fileName.textContent =
                    files.length === 1
                        ? files[0].name
                        : files.length +
                          ' files selected';


                fileSize.textContent =
                    formatBytes(totalSize);


                fileCount.textContent =
                    files.length === 1
                        ? '1 file ready for upload'
                        : files.length +
                          ' files ready for upload';


                emptyState.style.display =
                    'none';

                filledState.style.display =
                    'flex';


                fileThumb.innerHTML =
                    '';


                const firstFile =
                    files[0];


                if (
                    firstFile.type &&
                    firstFile.type.indexOf(
                        'image/'
                    ) === 0
                ) {

                    const image =
                        document.createElement(
                            'img'
                        );

                    image.alt =
                        'Selected product image preview';


                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            image.src =
                                event.target.result;

                        };


                    reader.readAsDataURL(
                        firstFile
                    );


                    fileThumb.appendChild(
                        image
                    );

                } else {

                    fileThumb.innerHTML = `
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25"
                            />
                        </svg>
                    `;

                }

            }


            function clearFile() {

                fileInput.value =
                    '';

                emptyState.style.display =
                    'flex';

                filledState.style.display =
                    'none';

                fileThumb.innerHTML =
                    '';

                fileCount.textContent =
                    '';

            }


            if (fileInput) {

                fileInput.addEventListener(
                    'change',
                    function () {

                        if (
                            fileInput.files &&
                            fileInput.files.length
                        ) {

                            showFiles(
                                fileInput.files
                            );

                        }

                    }
                );

            }


            if (removeBtn) {

                removeBtn.addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();

                        event.stopPropagation();

                        clearFile();

                    }
                );

            }


            if (dropzone) {

                [
                    'dragenter',
                    'dragover'
                ].forEach(
                    function (eventName) {

                        dropzone.addEventListener(
                            eventName,
                            function (event) {

                                event.preventDefault();

                                event.stopPropagation();

                                dropzone.classList.add(
                                    'drag-active'
                                );

                            }
                        );

                    }
                );


                [
                    'dragleave',
                    'drop'
                ].forEach(
                    function (eventName) {

                        dropzone.addEventListener(
                            eventName,
                            function (event) {

                                event.preventDefault();

                                event.stopPropagation();

                                dropzone.classList.remove(
                                    'drag-active'
                                );

                            }
                        );

                    }
                );


                dropzone.addEventListener(
                    'drop',
                    function (event) {

                        const dataTransfer =
                            event.dataTransfer;


                        if (
                            dataTransfer &&
                            dataTransfer.files &&
                            dataTransfer.files.length
                        ) {

                            try {

                                fileInput.files =
                                    dataTransfer.files;

                            } catch (error) {

                                console.warn(
                                    'Unable to assign dropped files.',
                                    error
                                );

                            }


                            showFiles(
                                dataTransfer.files
                            );

                        }

                    }
                );

            }


            /* =====================================================
               VALIDATION
               ===================================================== */

            const form =
                document.getElementById(
                    'product_form'
                );

            const submitBtn =
                document.getElementById(
                    'submit_btn'
                );

            const submitIcon =
                document.getElementById(
                    'submit_icon'
                );

            const submitSpinner =
                document.getElementById(
                    'submit_spinner'
                );

            const submitLabel =
                document.getElementById(
                    'submit_label'
                );


            requiredFields.forEach(
                function (field) {

                    field.addEventListener(
                        'blur',
                        function () {

                            field.classList.toggle(
                                'field-invalid',
                                !fieldHasValue(field)
                            );

                        }
                    );

                }
            );


            if (form) {

                form.addEventListener(
                    'submit',
                    function (event) {

                        let firstInvalid =
                            null;


                        requiredFields.forEach(
                            function (field) {

                                const invalid =
                                    !fieldHasValue(
                                        field
                                    );


                                field.classList.toggle(
                                    'field-invalid',
                                    invalid
                                );


                                if (
                                    invalid &&
                                    !firstInvalid
                                ) {

                                    firstInvalid =
                                        field;

                                }

                            }
                        );


                        if (firstInvalid) {

                            event.preventDefault();


                            firstInvalid
                                .scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });


                            if (
                                firstInvalid.tomselect
                            ) {

                                firstInvalid
                                    .tomselect
                                    .focus();

                            } else {

                                firstInvalid.focus();

                            }


                            return;

                        }


                        if (submitBtn) {

                            submitBtn.disabled =
                                true;

                        }


                        if (submitIcon) {

                            submitIcon.classList.add(
                                'hidden'
                            );

                        }


                        if (submitSpinner) {

                            submitSpinner.classList.remove(
                                'hidden'
                            );

                        }


                        if (submitLabel) {

                            submitLabel.textContent =
                                'Saving…';

                        }

                    }
                );

            }

        })();

    </script>

</x-mi_app>
