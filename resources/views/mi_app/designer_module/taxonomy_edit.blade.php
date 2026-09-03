<x-mi_app>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    <style>
        .tx-console {
            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;
            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;
            --tx-line: #e2e8f0;

            --tx-primary: #2563eb;
            --tx-primary-dark: #1d4ed8;
            --tx-primary-ink: #ffffff;
            --tx-primary-soft: #eff6ff;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-green: #059669;
            --tx-green-soft: #ecfdf5;

            --tx-lvl-1: #2563eb;
            --tx-lvl-1-soft: #eff6ff;

            --tx-lvl-2: #0891b2;
            --tx-lvl-2-soft: #ecfeff;

            --tx-lvl-3: #7c3aed;
            --tx-lvl-3-soft: #f5f3ff;

            --tx-lvl-4: #d97706;
            --tx-lvl-4-soft: #fffbeb;

            --tx-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --tx-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --tx-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;

            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
        }

        .tx-console.dark {
            --tx-bg: #0f172a;
            --tx-surface: #111827;
            --tx-ink: #f1f5f9;
            --tx-ink-soft: #94a3b8;
            --tx-ink-faint: #64748b;
            --tx-line: #1e293b;

            --tx-primary-soft: #172554;

            --tx-lvl-1-soft: #172554;
            --tx-lvl-2-soft: #083344;
            --tx-lvl-3-soft: #2e1065;
            --tx-lvl-4-soft: #451a03;

            --tx-danger-soft: #450a0a;
            --tx-green-soft: #022c22;
        }

        .tx-display {
            font-family: var(--tx-font-display);
            letter-spacing: -0.01em;
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.02em;
        }

        /* =========================================================
           PAGE
        ========================================================= */

        .tx-shell {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 28px 28px 60px;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .tx-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;

            padding-bottom: 24px;
            margin-bottom: 24px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-header-left {
            min-width: 0;
        }

        .tx-eyebrow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;

            margin-bottom: 9px;

            color: var(--tx-ink-faint);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tx-eyebrow a {
            color: var(--tx-ink-soft);
            text-decoration: none;
            transition: color .15s ease;
        }

        .tx-eyebrow a:hover {
            color: var(--tx-primary);
        }

        .tx-title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .tx-title {
            margin: 0;

            color: var(--tx-ink);
            font-family: var(--tx-font-display);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.1;
        }

        .tx-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 5px 10px;

            border: 1px solid #bbf7d0;
            border-radius: 999px;

            background: var(--tx-green-soft);
            color: var(--tx-green);

            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tx-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: currentColor;
        }

        .tx-subtitle {
            max-width: 650px;
            margin: 8px 0 0;

            color: var(--tx-ink-soft);
            font-size: 14px;
            line-height: 1.6;
        }

        .tx-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            flex-shrink: 0;

            padding: 10px 16px;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            background: var(--tx-surface);
            color: var(--tx-ink);

            font-size: 13px;
            font-weight: 600;
            text-decoration: none;

            transition:
                border-color .15s ease,
                color .15s ease,
                background .15s ease,
                transform .15s ease;
        }

        .tx-back:hover {
            border-color: var(--tx-primary);
            background: var(--tx-primary-soft);
            color: var(--tx-primary);
            transform: translateX(-2px);
        }

        /* =========================================================
           STATUS STRIP
        ========================================================= */

        .tx-status-strip {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 20px;
            padding: 12px 16px;

            border: 1px solid var(--tx-line);
            border-radius: 12px;

            background: var(--tx-surface);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .tx-status-icon {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 9px;

            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }

        .tx-status-icon svg {
            width: 17px;
            height: 17px;
        }

        .tx-status-copy {
            min-width: 0;
        }

        .tx-status-title {
            color: var(--tx-ink);
            font-size: 13px;
            font-weight: 700;
        }

        .tx-status-text {
            margin-top: 2px;
            color: var(--tx-ink-soft);
            font-size: 12px;
        }

        /* =========================================================
           CARD
        ========================================================= */

        .tx-card {
            margin-bottom: 20px;

            overflow: hidden;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .04),
                0 8px 24px rgba(15, 23, 42, .03);
        }

        .tx-card-head {
            display: flex;
            align-items: center;
            gap: 13px;

            padding: 18px 22px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-card-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            font-family: var(--tx-font-mono);
            font-size: 15px;
            font-weight: 600;
        }

        .tx-card-head h2 {
            margin: 0;

            color: var(--tx-ink);
            font-family: var(--tx-font-display);
            font-size: 16px;
            font-weight: 600;
        }

        .tx-card-head p {
            margin: 3px 0 0;

            color: var(--tx-ink-soft);
            font-size: 12px;
            line-height: 1.5;
        }

        .tx-card-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr);

            gap: 18px;

            padding: 22px;
        }

        .tx-card-body.cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        /* =========================================================
           LEVEL COLORS
        ========================================================= */

        .lvl-1 .tx-card-icon {
            background: var(--tx-lvl-1-soft);
            color: var(--tx-lvl-1);
        }

        .lvl-2 .tx-card-icon {
            background: var(--tx-lvl-2-soft);
            color: var(--tx-lvl-2);
        }

        .lvl-3 .tx-card-icon {
            background: var(--tx-lvl-3-soft);
            color: var(--tx-lvl-3);
        }

        .lvl-4 .tx-card-icon {
            background: var(--tx-lvl-4-soft);
            color: var(--tx-lvl-4);
        }

        /* =========================================================
           FIELDS
        ========================================================= */

        .tx-field-group {
            min-width: 0;
        }

        .tx-label {
            display: block;

            margin-bottom: 7px;

            color: var(--tx-ink-soft);

            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .tx-required {
            color: var(--tx-danger);
        }

        .tx-field {
            width: 100%;
            min-height: 42px;

            box-sizing: border-box;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            outline: none;

            background: var(--tx-bg);
            color: var(--tx-ink);

            padding: 10px 12px;

            font-family: var(--tx-font-body);
            font-size: 13px;
            line-height: 1.4;

            transition:
                border-color .15s ease,
                box-shadow .15s ease,
                background .15s ease;
        }

        .tx-field::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-field:hover {
            border-color: #cbd5e1;
        }

        .tx-field:focus {
            border-color: var(--tx-primary);
            background: var(--tx-surface);

            box-shadow: 0 0 0 4px var(--tx-primary-soft);
        }

        .tx-field[readonly] {
            cursor: not-allowed;

            background: var(--tx-bg);
            color: var(--tx-ink-soft);
        }

        .tx-field.tx-code-field {
            font-family: var(--tx-font-mono);
            font-size: 12px;
            font-weight: 600;
        }

        .tx-select-wrap {
            position: relative;
        }

        .tx-select-wrap select {
            appearance: none;
            padding-right: 40px;
            cursor: pointer;
        }

        .tx-select-arrow {
            position: absolute;
            top: 50%;
            right: 12px;

            width: 16px;
            height: 16px;

            color: var(--tx-ink-faint);

            pointer-events: none;

            transform: translateY(-50%);
        }

        .tx-help {
            margin-top: 6px;

            color: var(--tx-ink-faint);
            font-size: 11px;
            line-height: 1.45;
        }

        .tx-error {
            margin-top: 6px;

            color: var(--tx-danger);
            font-size: 11px;
            font-weight: 600;
        }

        /* =========================================================
           ACTION BAR
        ========================================================= */

        .tx-form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            padding: 16px 22px;

            border-top: 1px solid var(--tx-line);
            background: var(--tx-bg);
        }

        .tx-form-actions-left {
            color: var(--tx-ink-faint);
            font-size: 11px;
        }

        .tx-form-actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            min-height: 40px;

            padding: 9px 16px;

            border: 1px solid transparent;
            border-radius: 10px;

            cursor: pointer;

            font-family: var(--tx-font-body);
            font-size: 13px;
            font-weight: 600;

            text-decoration: none;

            transition:
                background .15s ease,
                border-color .15s ease,
                color .15s ease,
                box-shadow .15s ease,
                transform .15s ease;
        }

        .tx-btn svg {
            width: 16px;
            height: 16px;
        }

        .tx-btn-primary {
            background: var(--tx-primary);
            color: var(--tx-primary-ink);
        }

        .tx-btn-primary:hover {
            background: var(--tx-primary-dark);
            box-shadow: 0 8px 18px -8px var(--tx-primary);
            transform: translateY(-1px);
        }

        .tx-btn-secondary {
            border-color: var(--tx-line);
            background: var(--tx-surface);
            color: var(--tx-ink-soft);
        }

        .tx-btn-secondary:hover {
            border-color: var(--tx-primary);
            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }

        .tx-btn:focus-visible,
        .tx-back:focus-visible,
        .tx-field:focus-visible {
            outline: 2px solid var(--tx-primary);
            outline-offset: 2px;
        }

        /* =========================================================
           ENTITY BADGE
        ========================================================= */

        .tx-entity-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            margin-left: auto;

            padding: 6px 10px;

            border: 1px solid var(--tx-line);
            border-radius: 8px;

            background: var(--tx-bg);
            color: var(--tx-ink-soft);

            font-family: var(--tx-font-mono);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tx-entity-badge-dot {
            width: 6px;
            height: 6px;

            border-radius: 999px;

            background: var(--tx-primary);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {
            .tx-shell {
                padding: 22px 18px 48px;
            }

            .tx-card-body.cols-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .tx-shell {
                padding: 18px 14px 40px;
            }

            .tx-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .tx-title {
                font-size: 27px;
            }

            .tx-back {
                width: 100%;
            }

            .tx-card-head {
                padding: 16px;
            }

            .tx-card-body {
                padding: 16px;
            }

            .tx-form-actions {
                align-items: stretch;
                flex-direction: column;
                padding: 14px 16px;
            }

            .tx-form-actions-left {
                order: 2;
            }

            .tx-form-actions-right {
                width: 100%;
            }

            .tx-form-actions-right .tx-btn {
                flex: 1;
            }

            .tx-entity-badge {
                display: none;
            }
        }
    </style>


    <div class="tx-console">

        <div class="tx-shell">

            {{-- =====================================================
                 HEADER
            ====================================================== --}}
            <div class="tx-header">

                <div class="tx-header-left">

                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.settings') }}">
                            Taxonomy Console
                        </a>

                        <span>/</span>

                        <span>Edit</span>
                    </div>

                    <div class="tx-title-row">

                        <h1 class="tx-title">
                            Edit {{ ucwords(str_replace('_', ' ', $entityType)) }}
                        </h1>

                        <span class="tx-live">
                            <span class="tx-live-dot"></span>
                            Live
                        </span>

                    </div>

                    <p class="tx-subtitle">
                        Update the taxonomy information below. Changes will be applied
                        to the selected catalog record.
                    </p>

                </div>


                <a
                    href="{{ route('mi_app.settings') }}"
                    class="tx-back"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                        />
                    </svg>

                    Back to Taxonomy
                </a>

            </div>


            {{-- =====================================================
                 STATUS STRIP
            ====================================================== --}}
            <div class="tx-status-strip">

                <div class="tx-status-icon">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.862 4.487 18.549 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487ZM19.5 7.125 17.25 4.875M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                        />
                    </svg>

                </div>

                <div class="tx-status-copy">
                    <div class="tx-status-title">
                        Editing existing taxonomy
                    </div>

                    <div class="tx-status-text">
                        Review the current values, make your changes, then select
                        <strong>Save Changes</strong>.
                    </div>
                </div>

            </div>


            {{-- =====================================================
                 EDIT FORM
            ====================================================== --}}
            <div class="tx-card">

                <form
                    method="POST"
                    action="{{ route('taxonomy.update', [
                        'type' => $entityType,
                        'product' => $item->id
                    ]) }}"
                >

                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="entity_type"
                        value="{{ $entityType }}"
                    >


                    {{-- =================================================
                         CARD HEADER
                    ================================================== --}}
                    <div class="
                        tx-card-head
                        @if($entityType === 'category')
                            lvl-1
                        @elseif($entityType === 'sub_category')
                            lvl-2
                        @elseif($entityType === 'product_type')
                            lvl-3
                        @elseif($entityType === 'collection')
                            lvl-4
                        @endif
                    ">

                        <span class="tx-card-icon">

                            @if($entityType === 'category')
                                C
                            @elseif($entityType === 'sub_category')
                                SC
                            @elseif($entityType === 'product_type')
                                SS
                            @elseif($entityType === 'collection')
                                CL
                            @else
                                ✎
                            @endif

                        </span>

                        <div>

                            <h2>
                                Edit {{ ucwords(str_replace('_', ' ', $entityType)) }}
                            </h2>

                            <p>
                                Modify the information for this taxonomy level.
                            </p>

                        </div>

                        <span class="tx-entity-badge">

                            <span class="tx-entity-badge-dot"></span>

                            {{ $entityType }}

                        </span>

                    </div>


                    {{-- =================================================
                         FORM BODY
                    ================================================== --}}
                    <div class="tx-card-body cols-2">


                        {{-- =================================================
                             CATEGORY
                        ================================================== --}}
                        @if($entityType === 'category')

                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Category Code
                                </label>

                                <input
                                    type="text"
                                    class="tx-field tx-code-field"
                                    value="{{ $item->code }}"
                                    readonly
                                >

                                <div class="tx-help">
                                    Category codes are system-generated and cannot be edited.
                                </div>

                            </div>


                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Category Name
                                    <span class="tx-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $item->name) }}"
                                    class="tx-field"
                                    placeholder="Enter category name"
                                    required
                                    autocomplete="off"
                                >

                                @error('name')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        @endif


                        {{-- =================================================
                             SUB CATEGORY
                        ================================================== --}}
                        @if($entityType === 'sub_category')

                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Parent Category
                                    <span class="tx-required">*</span>
                                </label>

                                <div class="tx-select-wrap">

                                    <select
                                        name="category_id"
                                        class="tx-field"
                                        required
                                    >

                                        <option value="">
                                            Select category
                                        </option>

                                        @foreach($categories as $category)

                                            <option
                                                value="{{ $category->id }}"
                                                {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}
                                            >
                                                {{ $category->code }} — {{ $category->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                    <svg
                                        class="tx-select-arrow"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>

                                </div>

                                @error('category_id')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Sub Category Name
                                    <span class="tx-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="tx-field"
                                    value="{{ old('name', $item->name) }}"
                                    placeholder="Enter sub category name"
                                    required
                                    autocomplete="off"
                                >

                                @error('name')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        @endif


                        {{-- =================================================
                             PRODUCT TYPE / SUB SUB CATEGORY
                        ================================================== --}}
                        @if($entityType === 'product_type')

                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Parent Sub Category
                                    <span class="tx-required">*</span>
                                </label>

                                <div class="tx-select-wrap">

                                    <select
                                        name="sub_category_id"
                                        class="tx-field"
                                        required
                                    >

                                        <option value="">
                                            Select sub category
                                        </option>

                                        @foreach($subCategories as $sub)

                                            <option
                                                value="{{ $sub->id }}"
                                                {{ old('sub_category_id', $item->sub_category_id) == $sub->id ? 'selected' : '' }}
                                            >
                                                {{ $sub->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                    <svg
                                        class="tx-select-arrow"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>

                                </div>

                                @error('sub_category_id')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Sub Sub Category Name
                                    <span class="tx-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="tx-field"
                                    value="{{ old('name', $item->name) }}"
                                    placeholder="Enter sub sub category name"
                                    required
                                    autocomplete="off"
                                >

                                @error('name')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        @endif


                        {{-- =================================================
                             COLLECTION
                        ================================================== --}}
                        @if($entityType === 'collection')

                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Product Type
                                    <span class="tx-required">*</span>
                                </label>

                                <div class="tx-select-wrap">

                                    <select
                                        name="product_type_id"
                                        class="tx-field"
                                        required
                                    >

                                        <option value="">
                                            Select product type
                                        </option>

                                        @foreach($productTypes as $type)

                                            <option
                                                value="{{ $type->id }}"
                                                {{ old('product_type_id', $item->product_type_id) == $type->id ? 'selected' : '' }}
                                            >
                                                {{ $type->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                    <svg
                                        class="tx-select-arrow"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>

                                </div>

                                @error('product_type_id')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            <div class="tx-field-group">

                                <label class="tx-label">
                                    Collection Name
                                    <span class="tx-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="tx-field"
                                    value="{{ old('name', $item->name) }}"
                                    placeholder="Enter collection name"
                                    required
                                    autocomplete="off"
                                >

                                @error('name')
                                    <div class="tx-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        @endif

                    </div>


                    {{-- =================================================
                         FORM ACTIONS
                    ================================================== --}}
                    <div class="tx-form-actions">

                        <div class="tx-form-actions-left">
                            <span class="tx-required">*</span>
                            Required fields
                        </div>

                        <div class="tx-form-actions-right">

                            <a
                                href="{{ route('mi_app.settings') }}"
                                class="tx-btn tx-btn-secondary"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="tx-btn tx-btn-primary"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185A48.507 48.507 0 0 1 12 3c1.998 0 3.94.12 5.593.322Z"
                                    />
                                </svg>

                                Save Changes

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-mi_app>