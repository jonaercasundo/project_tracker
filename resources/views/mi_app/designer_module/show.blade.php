<x-mi_app>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet"
    >

    @php
        /*
        |--------------------------------------------------------------------------
        | Image URL Helper
        |--------------------------------------------------------------------------
        */
        $convertImageUrl = function ($url) {
            if (empty($url)) {
                return null;
            }

            // Google Drive: uc?id=...
            if (preg_match('/drive\.google\.com\/uc\?.*id=([^&]+)/', $url, $matches)) {
                return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
            }

            // Google Drive: file/d/...
            if (preg_match('#drive\.google\.com/file/d/([^/]+)#', $url, $matches)) {
                return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
            }

            // Google Drive: open?id=...
            if (preg_match('/drive\.google\.com\/open\?id=([^&]+)/', $url, $matches)) {
                return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
            }

            return trim($url);
        };

        /*
        |--------------------------------------------------------------------------
        | Product Images
        |--------------------------------------------------------------------------
        */
        $images = ($product->images ?? collect())
            ->map(function ($image) use ($convertImageUrl) {
                return [
                    'title' => $image->image_type === 'upload'
                        ? 'Uploaded Image'
                        : 'Image Link',

                    'url' => $image->image_type === 'upload'
                        ? asset('storage/' . $image->image_path)
                        : $convertImageUrl($image->image_url),
                ];
            })
            ->filter(function ($image) {
                return !empty($image['url']);
            })
            ->values()
            ->toArray();

        $imageCount = count($images);

        /*
        |--------------------------------------------------------------------------
        | Safe Display Values
        |--------------------------------------------------------------------------
        */
        $categoryName = $product->category?->name;
        $subCategoryName = $product->subCategory?->name;
        $productTypeName = $product->productType?->name;
        $collectionName = $product->collection?->name;

        $materials = is_array($product->materials)
            ? $product->materials
            : (empty($product->materials) ? [] : [$product->materials]);

        $colors = is_array($product->color)
            ? $product->color
            : (empty($product->color) ? [] : [$product->color]);
    @endphp

    <style>
        /* =========================================================
           PRODUCT DETAILS
        ========================================================= */

        .product-page {
            --pd-bg: #f8fafc;
            --pd-surface: #ffffff;
            --pd-ink: #111827;
            --pd-secondary: #64748b;
            --pd-muted: #94a3b8;
            --pd-border: #e2e8f0;
            --pd-soft: #f1f5f9;

            --pd-blue: #2563eb;
            --pd-blue-soft: #eff6ff;

            --pd-green: #059669;
            --pd-green-soft: #ecfdf5;

            --pd-purple: #7c3aed;
            --pd-purple-soft: #f5f3ff;

            --pd-orange: #d97706;
            --pd-orange-soft: #fffbeb;

            --pd-red: #dc2626;
            --pd-red-soft: #fef2f2;

            --pd-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --pd-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --pd-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;

            min-height: 100vh;
            background: var(--pd-bg);
            color: var(--pd-ink);
            font-family: var(--pd-font-body);
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        .product-page.dark {
            --pd-bg: #0f172a;
            --pd-surface: #111827;
            --pd-ink: #f8fafc;
            --pd-secondary: #94a3b8;
            --pd-muted: #64748b;
            --pd-border: #263244;
            --pd-soft: #1e293b;

            --pd-blue-soft: #172554;
            --pd-green-soft: #052e26;
            --pd-purple-soft: #2e1065;
            --pd-orange-soft: #451a03;
            --pd-red-soft: #450a0a;
        }

        .product-page *,
        .product-page *::before,
        .product-page *::after {
            box-sizing: border-box;
        }

        .pd-display {
            font-family: var(--pd-font-display);
            letter-spacing: -0.02em;
        }

        .pd-mono {
            font-family: var(--pd-font-mono);
            letter-spacing: 0.01em;
        }

        /* =========================================================
           PAGE CONTAINER
        ========================================================= */

        .pd-container {
            width: min(1450px, calc(100% - 40px));
            margin: 0 auto;
            padding: 28px 0 60px;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .pd-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;

            padding-bottom: 22px;
            margin-bottom: 22px;

            border-bottom: 1px solid var(--pd-border);
        }

        .pd-header-left {
            min-width: 0;
        }

        .pd-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;

            margin-bottom: 9px;

            color: var(--pd-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .pd-breadcrumb a {
            color: var(--pd-secondary);
            text-decoration: none;
            transition: color .15s ease;
        }

        .pd-breadcrumb a:hover {
            color: var(--pd-blue);
        }

        .pd-breadcrumb-current {
            max-width: 360px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pd-title {
            margin: 0;
            color: var(--pd-ink);
            font-size: clamp(1.65rem, 3vw, 2.25rem);
            font-weight: 700;
            line-height: 1.08;
        }

        .pd-subtitle {
            margin: 8px 0 0;
            max-width: 650px;

            color: var(--pd-secondary);
            font-size: 14px;
            line-height: 1.6;
        }

        .pd-header-actions {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-shrink: 0;
        }

        .pd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            min-height: 40px;
            padding: 0 15px;

            border-radius: 10px;
            border: 1px solid transparent;

            font-family: var(--pd-font-body);
            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
            cursor: pointer;

            transition:
                background .15s ease,
                border-color .15s ease,
                color .15s ease,
                transform .15s ease,
                box-shadow .15s ease;
        }

        .pd-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .pd-btn-back {
            color: var(--pd-ink);
            background: var(--pd-surface);
            border-color: var(--pd-border);
        }

        .pd-btn-back:hover {
            color: var(--pd-blue);
            border-color: #bfdbfe;
            transform: translateX(-2px);
        }

        .pd-btn-edit {
            color: #ffffff;
            background: var(--pd-blue);
            border-color: var(--pd-blue);
        }

        .pd-btn-edit:hover {
            background: #1d4ed8;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .20);
            transform: translateY(-1px);
        }

        /* =========================================================
           SUMMARY STRIP
        ========================================================= */

        .pd-summary {
            display: grid;
            grid-template-columns: minmax(220px, 1.6fr) repeat(3, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .pd-summary-card {
            min-height: 92px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            padding: 15px 17px;

            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 14px;
        }

        .pd-summary-card.primary {
            background: var(--pd-blue-soft);
            border-color: #dbeafe;
        }

        .product-page.dark .pd-summary-card.primary {
            border-color: #1e3a8a;
        }

        .pd-summary-label {
            color: var(--pd-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .pd-summary-value {
            margin-top: 5px;

            color: var(--pd-ink);
            font-size: 15px;
            font-weight: 700;
            line-height: 1.3;
        }

        .pd-summary-card.primary .pd-summary-value {
            color: var(--pd-blue);
        }

        .pd-summary-id {
            margin-top: 5px;
            color: var(--pd-blue);
            font-family: var(--pd-font-mono);
            font-size: 13px;
            font-weight: 600;
        }

        /* =========================================================
           CARDS
        ========================================================= */

        .pd-card {
            overflow: hidden;

            margin-bottom: 18px;

            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 16px;

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03),
                0 8px 24px rgba(15, 23, 42, .025);
        }

        .pd-card-header {
            display: flex;
            align-items: center;
            gap: 12px;

            min-height: 70px;
            padding: 14px 18px;

            border-bottom: 1px solid var(--pd-border);
        }

        .pd-card-icon {
            width: 36px;
            height: 36px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            font-family: var(--pd-font-mono);
            font-size: 11px;
            font-weight: 700;
        }

        .pd-card-icon.blue {
            color: var(--pd-blue);
            background: var(--pd-blue-soft);
        }

        .pd-card-icon.green {
            color: var(--pd-green);
            background: var(--pd-green-soft);
        }

        .pd-card-icon.purple {
            color: var(--pd-purple);
            background: var(--pd-purple-soft);
        }

        .pd-card-icon.orange {
            color: var(--pd-orange);
            background: var(--pd-orange-soft);
        }

        .pd-card-icon.gray {
            color: var(--pd-secondary);
            background: var(--pd-soft);
        }

        .pd-card-heading {
            min-width: 0;
        }

        .pd-card-heading h2 {
            margin: 0;

            color: var(--pd-ink);
            font-family: var(--pd-font-display);
            font-size: 15px;
            font-weight: 700;
        }

        .pd-card-heading p {
            margin: 3px 0 0;

            color: var(--pd-secondary);
            font-size: 11px;
            line-height: 1.4;
        }

        .pd-card-body {
            padding: 20px;
        }

        /* =========================================================
           DATA GRID
        ========================================================= */

        .pd-grid {
            display: grid;
            gap: 20px;
        }

        .pd-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pd-grid-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pd-field-label {
            display: block;

            margin-bottom: 6px;

            color: var(--pd-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .pd-field-value {
            min-height: 22px;

            color: var(--pd-ink);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .pd-field-value.empty {
            color: var(--pd-muted);
            font-weight: 500;
        }

        /* =========================================================
           REFERENCE BADGES
        ========================================================= */

        .pd-reference {
            display: inline-flex;
            align-items: center;

            max-width: 100%;

            padding: 7px 10px;

            border: 1px solid transparent;
            border-radius: 8px;

            font-family: var(--pd-font-mono);
            font-size: 12px;
            font-weight: 600;

            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pd-reference.blue {
            color: var(--pd-blue);
            background: var(--pd-blue-soft);
            border-color: #dbeafe;
        }

        .pd-reference.green {
            color: var(--pd-green);
            background: var(--pd-green-soft);
            border-color: #d1fae5;
        }

        .product-page.dark .pd-reference.blue {
            border-color: #1e3a8a;
        }

        .product-page.dark .pd-reference.green {
            border-color: #065f46;
        }

        /* =========================================================
           TAXONOMY
        ========================================================= */

        .pd-taxonomy-item {
            position: relative;
            padding-left: 15px;
        }

        .pd-taxonomy-item::before {
            content: "";

            position: absolute;
            top: 5px;
            left: 0;

            width: 6px;
            height: 6px;

            border-radius: 50%;
            background: var(--pd-blue);
        }

        .pd-taxonomy-item.green::before {
            background: var(--pd-green);
        }

        .pd-taxonomy-item.purple::before {
            background: var(--pd-purple);
        }

        .pd-taxonomy-item.orange::before {
            background: var(--pd-orange);
        }

        /* =========================================================
           CHIPS
        ========================================================= */

        .pd-chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .pd-chip {
            display: inline-flex;
            align-items: center;

            min-height: 29px;
            padding: 0 10px;

            border: 1px solid transparent;
            border-radius: 999px;

            font-size: 11px;
            font-weight: 700;
        }

        .pd-chip.material {
            color: var(--pd-blue);
            background: var(--pd-blue-soft);
            border-color: #dbeafe;
        }

        .pd-chip.color {
            color: var(--pd-green);
            background: var(--pd-green-soft);
            border-color: #d1fae5;
        }

        .product-page.dark .pd-chip.material {
            border-color: #1e3a8a;
        }

        .product-page.dark .pd-chip.color {
            border-color: #065f46;
        }

        .pd-empty-text {
            color: var(--pd-muted);
            font-size: 12px;
        }

        /* =========================================================
           DIMENSIONS
        ========================================================= */

        .pd-dimensions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .pd-dim-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pd-dim-table td {
            padding: 10px 0;
            border-bottom: 1px solid var(--pd-border);
            font-size: 13px;
        }

        .pd-dim-table tr:last-child td {
            border-bottom: none;
        }

        .pd-dim-key {
            color: var(--pd-secondary);
            font-weight: 500;
        }

        .pd-dim-value {
            text-align: right;
            color: var(--pd-ink);
            font-family: var(--pd-font-mono);
            font-size: 12px !important;
            font-weight: 600;
        }

        /* =========================================================
           IMAGE GALLERY
        ========================================================= */

        .pd-gallery-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            width: 100%;
        }

        .pd-gallery-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .pd-gallery-count {
            display: inline-flex;
            align-items: center;

            flex-shrink: 0;

            min-height: 28px;
            padding: 0 10px;

            border: 1px solid var(--pd-border);
            border-radius: 999px;

            color: var(--pd-secondary);
            background: var(--pd-soft);

            font-family: var(--pd-font-mono);
            font-size: 10px;
            font-weight: 600;
        }

        .pd-gallery-main {
            position: relative;

            display: flex;
            align-items: center;
            justify-content: center;

            min-height: 500px;

            border: 1px solid var(--pd-border);
            border-radius: 14px;

            background:
                linear-gradient(
                    45deg,
                    var(--pd-soft) 25%,
                    transparent 25%
                ),
                linear-gradient(
                    -45deg,
                    var(--pd-soft) 25%,
                    transparent 25%
                ),
                linear-gradient(
                    45deg,
                    transparent 75%,
                    var(--pd-soft) 75%
                ),
                linear-gradient(
                    -45deg,
                    transparent 75%,
                    var(--pd-soft) 75%
                );

            background-size: 24px 24px;
            background-position:
                0 0,
                0 12px,
                12px -12px,
                -12px 0;

            overflow: hidden;
        }

        #galleryPreview {
            display: block;

            width: 100%;
            height: 500px;

            object-fit: contain;

            padding: 12px;

            border-radius: 14px;

            background: var(--pd-surface);

            cursor: zoom-in;

            transition: opacity .2s ease;
        }

        .pd-gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);

            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid var(--pd-border);
            border-radius: 50%;

            background: rgba(255, 255, 255, .94);
            color: var(--pd-ink);

            box-shadow: 0 8px 24px rgba(15, 23, 42, .12);

            cursor: pointer;

            transition:
                transform .15s ease,
                color .15s ease,
                border-color .15s ease;
        }

        .product-page.dark .pd-gallery-nav {
            background: rgba(17, 24, 39, .94);
        }

        .pd-gallery-nav:hover {
            color: var(--pd-blue);
            border-color: #bfdbfe;
        }

        .pd-gallery-nav.prev {
            left: 14px;
        }

        .pd-gallery-nav.next {
            right: 14px;
        }

        .pd-gallery-counter {
            margin-top: 10px;

            text-align: center;

            color: var(--pd-secondary);
            font-family: var(--pd-font-mono);
            font-size: 11px;
            font-weight: 600;
        }

        .pd-gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;

            margin-top: 15px;
        }

        .pd-thumb {
            padding: 0;

            overflow: hidden;

            border: 2px solid transparent;
            border-radius: 11px;

            background: var(--pd-surface);

            cursor: pointer;

            transition:
                border-color .15s ease,
                box-shadow .15s ease,
                transform .15s ease;
        }

        .pd-thumb:hover {
            transform: translateY(-2px);
        }

        .pd-thumb.active {
            border-color: var(--pd-blue);
            box-shadow: 0 6px 18px rgba(37, 99, 235, .15);
        }

        .pd-thumb img {
            display: block;

            width: 100%;
            height: 92px;

            object-fit: cover;

            background: var(--pd-soft);
        }

        .pd-thumb-label {
            padding: 7px 5px;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

            color: var(--pd-secondary);
            background: var(--pd-surface);

            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }

        /* =========================================================
           EMPTY GALLERY
        ========================================================= */

        .pd-empty-gallery {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;

            min-height: 360px;

            padding: 40px;

            text-align: center;

            border: 1px dashed var(--pd-border);
            border-radius: 14px;

            background: var(--pd-soft);
        }

        .pd-empty-gallery-icon {
            width: 50px;
            height: 50px;

            margin-bottom: 14px;

            color: var(--pd-muted);
        }

        .pd-empty-gallery h3 {
            margin: 0;

            color: var(--pd-ink);
            font-family: var(--pd-font-display);
            font-size: 15px;
            font-weight: 700;
        }

        .pd-empty-gallery p {
            margin: 6px 0 0;

            color: var(--pd-secondary);
            font-size: 12px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {
            .pd-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pd-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .pd-container {
                width: min(100% - 28px, 1450px);
                padding-top: 20px;
            }

            .pd-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .pd-header-actions {
                width: 100%;
            }

            .pd-header-actions .pd-btn {
                flex: 1;
            }

            .pd-grid-3,
            .pd-grid-2,
            .pd-dimensions {
                grid-template-columns: 1fr;
            }

            .pd-gallery-main,
            #galleryPreview {
                min-height: 380px;
                height: 380px;
            }
        }

        @media (max-width: 600px) {
            .pd-container {
                width: calc(100% - 20px);
                padding-bottom: 35px;
            }

            .pd-title {
                font-size: 1.55rem;
            }

            .pd-summary {
                grid-template-columns: 1fr;
            }

            .pd-grid-4 {
                grid-template-columns: 1fr;
            }

            .pd-card-body {
                padding: 15px;
            }

            .pd-card-header {
                padding: 13px 15px;
            }

            .pd-gallery-thumbs {
                grid-template-columns: repeat(3, 1fr);
            }

            .pd-thumb img {
                height: 75px;
            }

            .pd-gallery-main,
            #galleryPreview {
                min-height: 300px;
                height: 300px;
            }

            .pd-gallery-nav {
                width: 36px;
                height: 36px;
            }

            .pd-gallery-nav.prev {
                left: 8px;
            }

            .pd-gallery-nav.next {
                right: 8px;
            }
        }

        @media (max-width: 420px) {
            .pd-header-actions {
                flex-direction: column;
            }

            .pd-header-actions .pd-btn {
                width: 100%;
            }

            .pd-gallery-thumbs {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <div class="product-page">

        <div class="pd-container">

            {{-- =====================================================
                 HEADER
            ====================================================== --}}
            <header class="pd-header">

                <div class="pd-header-left">

                    <div class="pd-breadcrumb">
                        <a href="{{ route('mi_app.index') }}">
                            Product Database
                        </a>

                        <span>/</span>

                        <span class="pd-breadcrumb-current">
                            {{ $product->item_name }}
                        </span>
                    </div>

                    <h1 class="pd-title pd-display">
                        Product Details
                    </h1>

                    <p class="pd-subtitle">
                        Complete product information, specifications,
                        references, and product images.
                    </p>

                </div>

                <div class="pd-header-actions">

                    <a
                        href="{{ route('mi_app.index') }}"
                        class="pd-btn pd-btn-back"
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

                        Back
                    </a>

                    <a
                        href="{{ route('mi_app.edit', $product->product_id) }}"
                        class="pd-btn pd-btn-edit"
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
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                            />
                        </svg>

                        Edit Product
                    </a>

                </div>
            </header>


            {{-- =====================================================
                 PRODUCT SUMMARY
            ====================================================== --}}
            <section class="pd-summary">

                <div class="pd-summary-card primary">

                    <span class="pd-summary-label">
                        Product
                    </span>

                    <div class="pd-summary-value">
                        {{ $product->item_name }}
                    </div>

                    <div class="pd-summary-id">
                        #{{ $product->product_id }}
                    </div>

                </div>

                <div class="pd-summary-card">

                    <span class="pd-summary-label">
                        SKU
                    </span>

                    <div class="pd-summary-value pd-mono">
                        {{ $product->sku ?? '—' }}
                    </div>

                </div>

                <div class="pd-summary-card">

                    <span class="pd-summary-label">
                        Category
                    </span>

                    <div class="pd-summary-value">
                        {{ $categoryName ?? 'Not set' }}
                    </div>

                </div>

                <div class="pd-summary-card">

                    <span class="pd-summary-label">
                        Images
                    </span>

                    <div class="pd-summary-value">
                        {{ $imageCount }}
                        {{ $imageCount === 1 ? 'Image' : 'Images' }}
                    </div>

                </div>

            </section>


            {{-- =====================================================
                 PRODUCT IDENTIFICATION
            ====================================================== --}}
            <section class="pd-card">

                <div class="pd-card-header">

                    <span class="pd-card-icon gray">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="17"
                            height="17"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>

                    </span>

                    <div class="pd-card-heading">

                        <h2>Product Identification</h2>

                        <p>
                            Official reference numbers used to identify this product.
                        </p>

                    </div>

                </div>

                <div class="pd-card-body">

                    <div class="pd-grid pd-grid-2">

                        <div>

                            <span class="pd-field-label">
                                Draft Number
                            </span>

                            @if($product->draft_number)
                                <span class="pd-reference blue">
                                    {{ $product->draft_number }}
                                </span>
                            @else
                                <span class="pd-field-value empty">
                                    Not set
                                </span>
                            @endif

                        </div>

                        <div>

                            <span class="pd-field-label">
                                SKU Number
                            </span>

                            @if($product->sku)
                                <span class="pd-reference green">
                                    {{ $product->sku }}
                                </span>
                            @else
                                <span class="pd-field-value empty">
                                    Not set
                                </span>
                            @endif

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                 TAXONOMY
            ====================================================== --}}
            <section class="pd-card">

                <div class="pd-card-header">

                    <span class="pd-card-icon blue">
                        01
                    </span>

                    <div class="pd-card-heading">

                        <h2>Taxonomy</h2>

                        <p>
                            Product classification within the catalog hierarchy.
                        </p>

                    </div>

                </div>

                <div class="pd-card-body">

                    <div class="pd-grid pd-grid-4">

                        <div class="pd-taxonomy-item">

                            <span class="pd-field-label">
                                Category
                            </span>

                            <div class="pd-field-value {{ $categoryName ? '' : 'empty' }}">
                                {{ $categoryName ?? 'Not set' }}
                            </div>

                        </div>

                        <div class="pd-taxonomy-item green">

                            <span class="pd-field-label">
                                Sub Category
                            </span>

                            <div class="pd-field-value {{ $subCategoryName ? '' : 'empty' }}">
                                {{ $subCategoryName ?? 'Not set' }}
                            </div>

                        </div>

                        <div class="pd-taxonomy-item purple">

                            <span class="pd-field-label">
                                Product Type
                            </span>

                            <div class="pd-field-value {{ $productTypeName ? '' : 'empty' }}">
                                {{ $productTypeName ?? 'Not set' }}
                            </div>

                        </div>

                        <div class="pd-taxonomy-item orange">

                            <span class="pd-field-label">
                                Collection
                            </span>

                            <div class="pd-field-value {{ $collectionName ? '' : 'empty' }}">
                                {{ $collectionName ?? 'Not set' }}
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                 GENERAL INFORMATION
            ====================================================== --}}
            <section class="pd-card">

                <div class="pd-card-header">

                    <span class="pd-card-icon green">
                        02
                    </span>

                    <div class="pd-card-heading">

                        <h2>General Information</h2>

                        <p>
                            Basic identity and design information.
                        </p>

                    </div>

                </div>

                <div class="pd-card-body">

                    <div class="pd-grid pd-grid-3">

                        <div>

                            <span class="pd-field-label">
                                Item Name
                            </span>

                            <div class="pd-field-value">
                                {{ $product->item_name }}
                            </div>

                        </div>

                        <div>

                            <span class="pd-field-label">
                                Type of Sample
                            </span>

                            <div class="pd-field-value {{ $product->type_of_sample ? '' : 'empty' }}">
                                {{ $product->type_of_sample ?? 'Not set' }}
                            </div>

                        </div>

                        <div>

                            <span class="pd-field-label">
                                Designed By
                            </span>

                            <div class="pd-field-value {{ $product->designed_by ? '' : 'empty' }}">
                                {{ $product->designed_by ?? 'Not set' }}
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                 MATERIALS & COLORS
            ====================================================== --}}
            <section class="pd-card">

                <div class="pd-card-header">

                    <span class="pd-card-icon purple">
                        03
                    </span>

                    <div class="pd-card-heading">

                        <h2>Materials & Colors</h2>

                        <p>
                            Physical composition and available finish options.
                        </p>

                    </div>

                </div>

                <div class="pd-card-body">

                    <div class="pd-grid pd-grid-2">

                        <div>

                            <span class="pd-field-label">
                                Materials
                            </span>

                            @if(count($materials))

                                <div class="pd-chip-group">

                                    @foreach($materials as $material)

                                        @if(!empty($material))
                                            <span class="pd-chip material">
                                                {{ $material }}
                                            </span>
                                        @endif

                                    @endforeach

                                </div>

                            @else

                                <span class="pd-empty-text">
                                    No materials listed
                                </span>

                            @endif

                        </div>


                        <div>

                            <span class="pd-field-label">
                                Colors
                            </span>

                            @if(count($colors))

                                <div class="pd-chip-group">

                                    @foreach($colors as $color)

                                        @if(!empty($color))
                                            <span class="pd-chip color">
                                                {{ $color }}
                                            </span>
                                        @endif

                                    @endforeach

                                </div>

                            @else

                                <span class="pd-empty-text">
                                    No colors listed
                                </span>

                            @endif

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                 DIMENSIONS
            ====================================================== --}}
            <div class="pd-dimensions">

                {{-- Product Dimensions --}}
                <section class="pd-card">

                    <div class="pd-card-header">

                        <span class="pd-card-icon blue">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="17"
                                height="17"
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

                        </span>

                        <div class="pd-card-heading">

                            <h2>Product Dimensions</h2>

                            <p>
                                Physical measurements of the product.
                            </p>

                        </div>

                    </div>

                    <div class="pd-card-body">

                        <table class="pd-dim-table">

                            <tbody>

                                <tr>
                                    <td class="pd-dim-key">Height</td>
                                    <td class="pd-dim-value">
                                        {{ $product->product_height ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Width</td>
                                    <td class="pd-dim-value">
                                        {{ $product->product_width ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Length</td>
                                    <td class="pd-dim-value">
                                        {{ $product->product_length ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Depth</td>
                                    <td class="pd-dim-value">
                                        {{ $product->product_depth ?? '—' }} cm
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                {{-- Carton Dimensions --}}
                <section class="pd-card">

                    <div class="pd-card-header">

                        <span class="pd-card-icon orange">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="17"
                                height="17"
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

                        </span>

                        <div class="pd-card-heading">

                            <h2>Carton Dimensions</h2>

                            <p>
                                Packaging measurements for shipping and storage.
                            </p>

                        </div>

                    </div>

                    <div class="pd-card-body">

                        <table class="pd-dim-table">

                            <tbody>

                                <tr>
                                    <td class="pd-dim-key">Height</td>
                                    <td class="pd-dim-value">
                                        {{ $product->carton_height ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Width</td>
                                    <td class="pd-dim-value">
                                        {{ $product->carton_width ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Length</td>
                                    <td class="pd-dim-value">
                                        {{ $product->carton_length ?? '—' }} cm
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pd-dim-key">Depth</td>
                                    <td class="pd-dim-value">
                                        {{ $product->carton_depth ?? '—' }} cm
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>

            </div>


            {{-- =====================================================
                 PRODUCT IMAGE GALLERY
            ====================================================== --}}
            <section class="pd-card">

                <div class="pd-card-header">

                    <div class="pd-gallery-header">

                        <div class="pd-gallery-header-left">

                            <span class="pd-card-icon blue">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="17"
                                    height="17"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                                    />
                                </svg>

                            </span>

                            <div class="pd-card-heading">

                                <h2>Product Images</h2>

                                <p>
                                    Product photos and external image links.
                                </p>

                            </div>

                        </div>

                        <span class="pd-gallery-count">
                            {{ $imageCount }}
                            {{ $imageCount === 1 ? 'IMAGE' : 'IMAGES' }}
                        </span>

                    </div>

                </div>


                <div class="pd-card-body">

                    @if($imageCount > 0)

                        {{-- Main Image --}}
                        <div class="pd-gallery-main">

                            <img
                                id="galleryPreview"
                                src="{{ $images[0]['url'] }}"
                                alt="{{ $product->item_name }}"
                            >

                            @if($imageCount > 1)

                                <button
                                    type="button"
                                    onclick="previousImage()"
                                    class="pd-gallery-nav prev"
                                    aria-label="Previous image"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 19l-7-7 7-7"
                                        />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    onclick="nextImage()"
                                    class="pd-gallery-nav next"
                                    aria-label="Next image"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </button>

                            @endif

                        </div>


                        {{-- Counter --}}
                        <div class="pd-gallery-counter">
                            <span id="galleryCounter">
                                1 / {{ $imageCount }}
                            </span>
                        </div>


                        {{-- Thumbnails --}}
                        @if($imageCount > 1)

                            <div class="pd-gallery-thumbs">

                                @foreach($images as $index => $image)

                                    <button
                                        type="button"
                                        onclick="showImage({{ $index }})"
                                        class="pd-thumb gallery-thumb {{ $index === 0 ? 'active' : '' }}"
                                        data-index="{{ $index }}"
                                        aria-label="View image {{ $index + 1 }}"
                                    >

                                        <img
                                            src="{{ $image['url'] }}"
                                            alt="{{ $image['title'] }} {{ $index + 1 }}"
                                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                        >

                                        <div class="pd-thumb-label">
                                            {{ $image['title'] }}
                                        </div>

                                    </button>

                                @endforeach

                            </div>

                        @endif

                    @else

                        <div class="pd-empty-gallery">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="pd-empty-gallery-icon"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M3 16l4-4a2 2 0 012.828 0L16 18m-2-2l1-1a2 2 0 012.828 0L21 18"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"
                                />
                            </svg>

                            <h3>
                                No Images Available
                            </h3>

                            <p>
                                This product does not have an uploaded image or image link.
                            </p>

                        </div>

                    @endif

                </div>

            </section>

        </div>
    </div>


    {{-- =============================================================
         GALLERY JAVASCRIPT
    ============================================================== --}}
    @if($imageCount > 0)

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const galleryImages = @json(array_column($images, 'url'));

                let currentImage = 0;

                const preview = document.getElementById('galleryPreview');
                const counter = document.getElementById('galleryCounter');

                const thumbs = document.querySelectorAll('.gallery-thumb');


                function updateGallery() {

                    if (!preview || !counter || !galleryImages.length) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Small fade effect
                    |--------------------------------------------------------------------------
                    */
                    preview.style.opacity = '0.35';

                    setTimeout(function () {

                        preview.src = galleryImages[currentImage];

                        preview.onload = function () {
                            preview.style.opacity = '1';
                        };

                    }, 100);


                    /*
                    |--------------------------------------------------------------------------
                    | Counter
                    |--------------------------------------------------------------------------
                    */
                    counter.textContent =
                        (currentImage + 1) + ' / ' + galleryImages.length;


                    /*
                    |--------------------------------------------------------------------------
                    | Active Thumbnail
                    |--------------------------------------------------------------------------
                    */
                    thumbs.forEach(function (item) {
                        item.classList.remove('active');
                    });

                    const activeThumb = document.querySelector(
                        '[data-index="' + currentImage + '"]'
                    );

                    if (activeThumb) {
                        activeThumb.classList.add('active');
                    }

                }


                window.showImage = function (index) {

                    if (index < 0 || index >= galleryImages.length) {
                        return;
                    }

                    currentImage = index;

                    updateGallery();

                };


                window.nextImage = function () {

                    if (galleryImages.length <= 1) {
                        return;
                    }

                    currentImage++;

                    if (currentImage >= galleryImages.length) {
                        currentImage = 0;
                    }

                    updateGallery();

                };


                window.previousImage = function () {

                    if (galleryImages.length <= 1) {
                        return;
                    }

                    currentImage--;

                    if (currentImage < 0) {
                        currentImage = galleryImages.length - 1;
                    }

                    updateGallery();

                };


                /*
                |--------------------------------------------------------------------------
                | Keyboard Navigation
                |--------------------------------------------------------------------------
                */
                document.addEventListener('keydown', function (event) {

                    if (galleryImages.length <= 1) {
                        return;
                    }

                    if (event.key === 'ArrowRight') {
                        window.nextImage();
                    }

                    if (event.key === 'ArrowLeft') {
                        window.previousImage();
                    }

                });


                /*
                |--------------------------------------------------------------------------
                | Initialize
                |--------------------------------------------------------------------------
                */
                updateGallery();

            });
        </script>

    @endif

</x-mi_app>