<x-mi_app>

    {{-- ============================================================
        EXTERNAL ASSETS
    ============================================================ --}}

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

    <style>

        /* ============================================================
           PRODUCT EDIT CONSOLE
        ============================================================ */

        .tx-console {

            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;

            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e2e8f0;
            --tx-soft: #f1f5f9;

            --tx-primary: #2563eb;
            --tx-primary-dark: #1d4ed8;
            --tx-primary-ink: #ffffff;
            --tx-primary-soft: #eff6ff;

            --tx-green: #059669;
            --tx-green-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-purple: #7c3aed;
            --tx-purple-soft: #f5f3ff;

            --tx-orange: #d97706;
            --tx-orange-soft: #fffbeb;

            --tx-font-display: 'Space Grotesk',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-body: 'Inter',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-mono: 'JetBrains Mono',
                ui-monospace,
                SFMono-Regular,
                monospace;

            font-family: var(--tx-font-body);

            background: var(--tx-bg);
            color: var(--tx-ink);

            min-height: 100vh;
        }


        /* ============================================================
           SHELL
        ============================================================ */

        .tx-shell {

            width: 100%;
            max-width: 1450px;

            margin: 0 auto;

            padding:
                28px
                28px
                100px;
        }


        .tx-display {
            font-family: var(--tx-font-display);
            letter-spacing: -0.015em;
        }


        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.015em;
        }


        /* ============================================================
           HEADER
        ============================================================ */

        .tx-header {

            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            gap: 24px;

            padding-bottom: 22px;
            margin-bottom: 24px;

            border-bottom: 1px solid var(--tx-line);
        }


        .tx-header-main {
            min-width: 0;
        }


        .tx-kicker {

            display: flex;
            align-items: center;

            gap: 8px;

            margin-bottom: 8px;

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .08em;
            text-transform: uppercase;

            color: var(--tx-primary);
        }


        .tx-live-dot {

            width: 7px;
            height: 7px;

            border-radius: 999px;

            background: var(--tx-green);

            box-shadow:
                0 0 0 4px var(--tx-green-soft);
        }


        .tx-title {

            margin: 0;

            font-size: 32px;
            font-weight: 700;

            line-height: 1.1;

            color: var(--tx-ink);
        }


        .tx-subtitle {

            margin-top: 8px;
            margin-bottom: 0;

            max-width: 760px;

            color: var(--tx-ink-soft);

            font-size: 14px;
            line-height: 1.6;
        }


        .tx-header-actions {

            display: flex;
            align-items: center;

            gap: 10px;

            flex-shrink: 0;
        }


        .tx-back {

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            min-height: 40px;

            padding:
                0 15px;

            border: 1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

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

            color: var(--tx-primary);

            background: var(--tx-primary-soft);

            transform: translateX(-2px);
        }


        /* ============================================================
           ALERTS
        ============================================================ */

        .tx-alert {

            display: flex;

            gap: 12px;

            margin-bottom: 20px;

            padding: 14px 16px;

            border-radius: 12px;

            font-size: 13px;
            line-height: 1.55;
        }


        .tx-alert-danger {

            border: 1px solid #fecaca;

            background: var(--tx-danger-soft);

            color: #991b1b;
        }


        .tx-alert-icon {

            flex-shrink: 0;

            width: 20px;
            height: 20px;
        }


        .tx-alert ul {

            margin: 4px 0 0;
            padding-left: 18px;
        }


        /* ============================================================
           CARDS
        ============================================================ */

        .tx-card {

            margin-bottom: 18px;

            overflow: hidden;

            border:
                1px solid var(--tx-line);

            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 8px 24px rgba(15, 23, 42, .045);
        }


        .tx-card-head {

            display: flex;
            align-items: center;

            gap: 12px;

            padding:
                17px 20px;

            border-bottom:
                1px solid var(--tx-line);
        }


        .tx-card-icon {

            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 38px;
            height: 38px;

            flex-shrink: 0;

            border-radius: 10px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-family: var(--tx-font-mono);

            font-size: 12px;
            font-weight: 700;
        }


        .tx-card-head h2 {

            margin: 0;

            font-family: var(--tx-font-display);

            font-size: 16px;
            font-weight: 700;

            color: var(--tx-ink);
        }


        .tx-card-head p {

            margin: 3px 0 0;

            color: var(--tx-ink-soft);

            font-size: 12px;
        }


        .tx-card-body {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr);

            gap: 18px;

            padding: 22px;
        }


        .tx-card-body.cols-2 {

            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }


        .tx-card-body.cols-4 {

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }


        /* ============================================================
           FIELD
        ============================================================ */

        .tx-label {

            display: block;

            margin-bottom: 7px;

            color: var(--tx-ink-soft);

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }


        .tx-field {

            width: 100%;

            box-sizing: border-box;

            border:
                1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-bg);

            color: var(--tx-ink);

            outline: none;

            padding:
                10px 12px;

            font-family: var(--tx-font-body);

            font-size: 13px;

            line-height: 1.4;

            transition:
                border-color .15s ease,
                box-shadow .15s ease,
                background .15s ease;
        }


        .tx-field:hover {

            border-color: #cbd5e1;
        }


        .tx-field:focus {

            border-color: var(--tx-primary);

            background: var(--tx-surface);

            box-shadow:
                0 0 0 4px var(--tx-primary-soft);
        }


        .tx-field:disabled {

            cursor: not-allowed;

            background: var(--tx-soft);

            color: var(--tx-ink-faint);
        }


        textarea.tx-field {

            min-height: 110px;

            resize: vertical;
        }


        .tx-hint {

            margin:
                5px 0 0;

            color: var(--tx-ink-faint);

            font-size: 11px;
            line-height: 1.5;
        }


        .tx-error {

            margin-top: 6px;

            color: var(--tx-danger);

            font-size: 11px;
            font-weight: 600;
        }


        /* ============================================================
           SELECT
        ============================================================ */

        .tx-select-wrap {

            position: relative;
        }


        .tx-select-wrap select {

            appearance: none;

            padding-right: 38px;
        }


        .tx-select-wrap svg {

            position: absolute;

            top: 50%;
            right: 12px;

            width: 15px;
            height: 15px;

            transform:
                translateY(-50%);

            color: var(--tx-ink-faint);

            pointer-events: none;
        }


        /* ============================================================
           TAXONOMY
        ============================================================ */

        .tx-taxonomy-preview {

            display: flex;
            align-items: center;

            gap: 10px;

            flex-wrap: wrap;

            margin:
                0 22px 22px;

            padding:
                12px 14px;

            border:
                1px dashed #cbd5e1;

            border-radius: 11px;

            background: var(--tx-bg);
        }


        .tx-taxonomy-preview-label {

            color: var(--tx-ink-faint);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .08em;

            text-transform: uppercase;
        }


        #taxonomy-preview-path {

            color: var(--tx-ink);

            font-size: 12px;
            font-weight: 600;

            word-break: break-word;
        }


        .tx-lvl-1 .tx-card-icon {

            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }


        .tx-lvl-2 .tx-card-icon {

            background: #ecfeff;
            color: #0891b2;
        }


        .tx-lvl-3 .tx-card-icon {

            background: var(--tx-purple-soft);
            color: var(--tx-purple);
        }


        .tx-lvl-4 .tx-card-icon {

            background: var(--tx-orange-soft);
            color: var(--tx-orange);
        }


        .tx-lvl-dot {

            display: inline-block;

            width: 7px;
            height: 7px;

            margin-right: 6px;

            border-radius: 999px;

            vertical-align: middle;
        }


        /* ============================================================
           DIMENSIONS
        ============================================================ */

        .tx-subpanel {

            padding: 16px;

            border:
                1px solid var(--tx-line);

            border-radius: 13px;

            background: var(--tx-bg);
        }


        .tx-subpanel-head {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 14px;
        }


        .tx-subpanel-head h3 {

            margin: 0;

            font-family: var(--tx-font-display);

            font-size: 12px;
            font-weight: 700;

            letter-spacing: .06em;

            text-transform: uppercase;

            color: var(--tx-ink-soft);
        }


        .tx-subpanel-tag {

            display: inline-flex;
            align-items: center;

            gap: 6px;

            padding:
                5px 9px;

            border:
                1px solid var(--tx-line);

            border-radius: 999px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 600;

            white-space: nowrap;
        }


        .tx-dims-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 12px;
        }


        .tx-dim-label {

            display: block;

            margin-bottom: 6px;

            color: var(--tx-ink-soft);

            font-size: 11px;
            font-weight: 600;
        }


        .tx-dim-input-wrap {

            position: relative;
        }


        .tx-dim-input-wrap input {

            padding-right: 40px;
        }


        .tx-dim-unit {

            position: absolute;

            top: 50%;
            right: 12px;

            transform:
                translateY(-50%);

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-mono);

            font-size: 10px;
            font-weight: 600;

            pointer-events: none;
        }


        /* ============================================================
           PRICE
        ============================================================ */

        .tx-price-wrap {

            position: relative;
        }


        .tx-price-prefix {

            position: absolute;

            left: 13px;
            top: 50%;

            transform:
                translateY(-50%);

            color: var(--tx-ink-soft);

            font-family: var(--tx-font-mono);

            font-size: 13px;
            font-weight: 600;

            pointer-events: none;
        }


        .tx-price-field {

            padding-left: 34px !important;

            font-family: var(--tx-font-mono);

            font-weight: 600;
        }


        .tx-price-preview {

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            min-height: 43px;

            padding:
                10px 13px;

            border:
                1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-bg);
        }


        .tx-price-preview-label {

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }


        .tx-price-preview-value {

            color: var(--tx-primary);

            font-family: var(--tx-font-mono);

            font-size: 15px;
            font-weight: 700;
        }


        /* ============================================================
           MULTI SELECT
        ============================================================ */

        .tx-multi-select-wrap {

            display: flex;

            flex-direction: column;

            gap: 8px;
        }


        .tx-multi-toolbar {

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 10px;
        }


        .tx-multi-hint {

            color: var(--tx-ink-faint);

            font-size: 11px;
        }


        .tx-multi-clear {

            display: none;

            align-items: center;
            justify-content: center;

            border:
                1px solid var(--tx-line);

            border-radius: 999px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            padding:
                5px 9px;

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition: all .15s ease;
        }


        .tx-multi-clear:hover {

            border-color: var(--tx-primary);

            color: var(--tx-primary);

            background: var(--tx-primary-soft);
        }


        .tx-multi-select {

            min-height: 150px;
        }


        .tx-multi-chips {

            display: flex;

            flex-wrap: wrap;

            gap: 6px;

            min-height: 20px;
        }


        .tx-multi-chip {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                5px 8px;

            border-radius: 999px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10px;
            font-weight: 700;
        }


        .tx-multi-chip button {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            width: 15px;
            height: 15px;

            padding: 0;

            border: none;

            background: transparent;

            color: inherit;

            cursor: pointer;
        }


        .tx-multi-chip button:hover {

            opacity: .65;
        }


        /* ============================================================
           TOM SELECT
        ============================================================ */

        .tx-console .ts-wrapper {

            width: 100%;
        }


        .tx-console .ts-control {

            min-height: 43px;

            padding:
                7px 10px;

            border:
                1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-bg);

            box-shadow: none;

            font-size: 12px;
        }


        .tx-console .ts-control:focus,
        .tx-console .ts-wrapper.focus .ts-control {

            border-color: var(--tx-primary);

            box-shadow:
                0 0 0 4px var(--tx-primary-soft);
        }


        .tx-console .ts-dropdown {

            z-index: 100;

            overflow: hidden;

            border:
                1px solid var(--tx-line);

            border-radius: 10px;

            box-shadow:
                0 15px 35px rgba(15, 23, 42, .12);
        }


        .tx-console .ts-dropdown .option {

            padding:
                9px 12px;

            font-size: 12px;
        }


        .tx-console .ts-dropdown .active {

            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }


        .tx-console .ts-control .item {

            border-radius: 999px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            padding:
                4px 8px;

            font-size: 10px;
            font-weight: 700;
        }


        .tx-console .ts-control .remove {

            border-left: none;

            color: inherit;
        }


        .tx-console .optgroup-header {

            padding:
                7px 10px;

            background: var(--tx-soft);

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .05em;

            text-transform: uppercase;
        }


        /* ============================================================
           IMAGE LINKS
        ============================================================ */

        .tx-image-link-row {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 8px;
        }


        .tx-image-link-row .tx-field {

            flex: 1;
        }


        .tx-image-remove-link {

            flex-shrink: 0;

            min-height: 40px;

            padding:
                0 11px;

            border:
                1px solid #fecaca;

            border-radius: 9px;

            background: var(--tx-danger-soft);

            color: var(--tx-danger);

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition: all .15s ease;
        }


        .tx-image-remove-link:hover {

            border-color: var(--tx-danger);

            background: #fee2e2;
        }


        .tx-btn-ghost {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 40px;

            padding:
                0 13px;

            border: none;

            border-radius: 9px;

            background: transparent;

            color: var(--tx-ink-soft);

            font-size: 12px;
            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

            transition: all .15s ease;
        }


        .tx-btn-ghost:hover {

            background: var(--tx-soft);

            color: var(--tx-ink);
        }


        /* ============================================================
           EXISTING IMAGES
        ============================================================ */

        .tx-existing-images {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

            margin-top: 12px;
        }


        .tx-existing-image {

            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 0;

            padding: 12px;

            border:
                1px solid var(--tx-line);

            border-radius: 12px;

            background: var(--tx-bg);

            transition:
                opacity .15s ease,
                border-color .15s ease,
                transform .15s ease;
        }


        .tx-existing-image:hover {

            border-color: #cbd5e1;
        }


        .tx-existing-image.removing {

            opacity: .5;

            border-color: var(--tx-danger);

            transform: scale(.99);
        }


        .tx-existing-image-preview {

            width: 82px;
            height: 82px;

            flex: 0 0 82px;

            overflow: hidden;

            border:
                1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-surface);
        }


        .tx-existing-image-preview img {

            display: block;

            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .tx-image-placeholder {

            display: flex;

            align-items: center;
            justify-content: center;

            width: 100%;
            height: 100%;

            color: var(--tx-ink-faint);

            font-size: 10px;
        }


        .tx-existing-image-info {

            flex: 1;

            min-width: 0;
        }


        .tx-existing-image-info strong {

            display: block;

            color: var(--tx-ink);

            font-size: 12px;
            font-weight: 700;
        }


        .tx-primary-badge {

            display: inline-flex;

            margin-top: 5px;

            padding:
                3px 7px;

            border-radius: 999px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 9px;
            font-weight: 700;
        }


        .tx-image-url {

            margin-top: 5px;

            overflow: hidden;

            color: var(--tx-ink-faint);

            font-size: 9px;

            line-height: 1.4;

            word-break: break-all;

            display: -webkit-box;

            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }


        .tx-remove-existing-image {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 5px;

            flex-shrink: 0;

            min-height: 34px;

            padding:
                0 9px;

            border:
                1px solid #fecaca;

            border-radius: 8px;

            background: var(--tx-danger-soft);

            color: var(--tx-danger);

            font-size: 9px;
            font-weight: 700;

            cursor: pointer;

            transition: all .15s ease;
        }


        .tx-remove-existing-image:hover {

            border-color: var(--tx-danger);

            background: #fee2e2;
        }


        .tx-existing-image.removing .tx-remove-existing-image {

            border-color: var(--tx-danger);

            background: var(--tx-danger);

            color: white;
        }


        .tx-no-images {

            grid-column: 1 / -1;

            padding: 24px;

            border:
                1px dashed var(--tx-line);

            border-radius: 12px;

            color: var(--tx-ink-faint);

            font-size: 11px;

            text-align: center;
        }


        /* ============================================================
           NEW IMAGE PREVIEW
        ============================================================ */

        .tx-new-image-preview {

            display: grid;

            grid-template-columns:
                repeat(auto-fill, minmax(130px, 1fr));

            gap: 10px;

            margin-top: 12px;
        }


        .tx-new-image-preview:empty {

            display: none;
        }


        .tx-new-image-item {

            overflow: hidden;

            border:
                1px solid var(--tx-line);

            border-radius: 11px;

            background: var(--tx-bg);
        }


        .tx-new-image-item img {

            display: block;

            width: 100%;
            height: 120px;

            object-fit: cover;
        }


        .tx-new-image-file {

            display: flex;

            align-items: center;
            justify-content: center;

            height: 120px;

            color: var(--tx-ink-faint);

            font-size: 11px;
            font-weight: 600;
        }


        .tx-new-image-name {

            padding:
                7px 8px;

            overflow: hidden;

            color: var(--tx-ink-soft);

            font-size: 9px;

            white-space: nowrap;

            text-overflow: ellipsis;
        }


        /* ============================================================
           FOOTER
        ============================================================ */

        .tx-footer {

            position: sticky;

            bottom: 12px;

            z-index: 20;

            margin-top: 22px;
        }


        .tx-footer-inner {

            display: flex;

            align-items: center;
            justify-content: flex-end;

            gap: 8px;

            padding:
                10px;

            border:
                1px solid var(--tx-line);

            border-radius: 13px;

            background:
                rgba(255, 255, 255, .94);

            backdrop-filter: blur(12px);

            box-shadow:
                0 18px 40px rgba(15, 23, 42, .12);
        }


        .tx-btn-submit {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            min-height: 40px;

            padding:
                0 17px;

            border: none;

            border-radius: 10px;

            background: var(--tx-primary);

            color: var(--tx-primary-ink);

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background .15s ease,
                transform .15s ease,
                box-shadow .15s ease;
        }


        .tx-btn-submit:hover {

            background: var(--tx-primary-dark);

            transform: translateY(-1px);

            box-shadow:
                0 10px 24px rgba(37, 99, 235, .25);
        }


        .tx-btn-submit:disabled {

            opacity: .65;

            cursor: not-allowed;

            transform: none;

            box-shadow: none;
        }


        /* ============================================================
           RESPONSIVE
        ============================================================ */

        @media (max-width: 1100px) {

            .tx-card-body.cols-4 {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .tx-dims-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 800px) {

            .tx-shell {

                padding:
                    20px
                    16px
                    90px;
            }


            .tx-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .tx-header-actions {

                width: 100%;
            }


            .tx-back {

                width: 100%;
            }


            .tx-card-body.cols-2 {

                grid-template-columns:
                    1fr;
            }


            .tx-existing-images {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 600px) {

            .tx-title {

                font-size: 26px;
            }


            .tx-card-head {

                padding:
                    14px 15px;
            }


            .tx-card-body {

                padding:
                    15px;
            }


            .tx-card-body.cols-4 {

                grid-template-columns:
                    1fr;
            }


            .tx-dims-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .tx-taxonomy-preview {

                margin:
                    0 15px 15px;
            }


            .tx-footer-inner {

                justify-content: stretch;
            }


            .tx-footer-inner .tx-btn-ghost,
            .tx-footer-inner .tx-btn-submit {

                flex: 1;
            }


            .tx-existing-image {

                align-items: flex-start;

                flex-wrap: wrap;
            }


            .tx-remove-existing-image {

                width: 100%;
            }

        }


        @media (max-width: 420px) {

            .tx-dims-grid {

                grid-template-columns:
                    1fr;
            }

        }

    </style>


    {{-- ============================================================
         PAGE
    ============================================================ --}}

    <div class="tx-console">

        <div class="tx-shell">

            {{-- ========================================================
                 HEADER
            ======================================================== --}}

            <div class="tx-header">

                <div class="tx-header-main">

                    <div class="tx-kicker">

                        <span class="tx-live-dot"></span>

                        Product Database

                    </div>

                    <h1 class="tx-title tx-display">
                        Edit Product
                    </h1>

                    <p class="tx-subtitle">
                        Update product information, taxonomy, dimensions,
                        pricing, classification and product media.
                    </p>

                </div>


                <div class="tx-header-actions">

                    <a
                        href="{{ route('mi_app.show', $product->product_id) }}"
                        class="tx-back"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
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

                        Back to Product

                    </a>

                </div>

            </div>


            {{-- ========================================================
                 FORM
            ======================================================== --}}

            <form
                action="{{ route('mi_app.update', $product->product_id) }}"
                method="POST"
                enctype="multipart/form-data"
                id="edit_product_form"
            >

                @csrf
                @method('PUT')


                {{-- Hidden removed image container --}}

                <div id="removedImagesContainer"></div>


                {{-- ====================================================
                     VALIDATION ERRORS
                ==================================================== --}}

                @php
                    $saveError = $errors->first('error') ?: session('error');
                @endphp


                @if($saveError)

                    <div class="tx-alert tx-alert-danger">

                        <svg
                            class="tx-alert-icon"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86l-7.1 12.28A2 2 0 004.92 19h14.16a2 2 0 001.73-2.86L13.71 3.86a2 2 0 00-3.42 0Z"
                            />
                        </svg>

                        <div>

                            <strong>
                                Unable to update the product.
                            </strong>

                            <div>
                                {{ $saveError }}
                            </div>

                        </div>

                    </div>

                @endif


                @if($errors->any() && !$saveError)

                    <div class="tx-alert tx-alert-danger">

                        <svg
                            class="tx-alert-icon"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86l-7.1 12.28A2 2 0 004.92 19h14.16a2 2 0 001.73-2.86L13.71 3.86a2 2 0 00-3.42 0Z"
                            />
                        </svg>

                        <div>

                            <strong>
                                Please check the following:
                            </strong>

                            <ul>

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    </div>

                @endif


                {{-- ====================================================
                     SECTION 01
                     IDENTIFICATION
                ==================================================== --}}

                <div class="tx-card">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="17"
                                height="17"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2Z"
                                />
                            </svg>

                        </span>

                        <div>

                            <h2>
                                Product Identification
                            </h2>

                            <p>
                                System-generated product references
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-2">

                        <div>

                            <label class="tx-label">
                                SKU Number
                            </label>

                            <input
                                type="text"
                                value="{{ $product->sku }}"
                                disabled
                                class="tx-field tx-mono"
                            >

                            <p class="tx-hint">
                                Auto generated by the system.
                            </p>

                        </div>


                        <div>

                            <label class="tx-label">
                                Draft Number
                            </label>

                            <input
                                type="text"
                                value="{{ $product->draft_number }}"
                                disabled
                                class="tx-field tx-mono"
                            >

                            <p class="tx-hint">
                                Auto generated by the system.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ====================================================
                     SECTION 02
                     TAXONOMY
                ==================================================== --}}

                <div class="tx-card tx-lvl-1" id="taxonomy-section">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            02
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
                                    style="background:#2563eb;"
                                ></span>

                                Category

                            </label>


                            <div class="tx-select-wrap">

                                <select
                                    name="category_id"
                                    id="category_id"
                                    data-cascade-target="sub_category_id"
                                    class="tx-field"
                                    required
                                >

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach($categories as $category)

                                        <option
                                            value="{{ $category->id }}"
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}
                                        >
                                            {{ $category->code }} - {{ $category->name }}
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

                        </div>


                        {{-- Sub Category --}}

                        <div>

                            <label
                                for="sub_category_id"
                                class="tx-label"
                            >

                                <span
                                    class="tx-lvl-dot"
                                    style="background:#0891b2;"
                                ></span>

                                Sub Category

                            </label>


                            <div class="tx-select-wrap">

                                <select
                                    name="sub_category_id"
                                    id="sub_category_id"
                                    data-cascade-target="product_type_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Sub Category --
                                    </option>

                                    @foreach($subCategories as $sub)

                                        <option
                                            value="{{ $sub->id }}"
                                            data-parent="{{ $sub->category_id }}"
                                            {{ old('sub_category_id', $product->sub_category_id) == $sub->id ? 'selected' : '' }}
                                        >
                                            {{ $sub->code }} - {{ $sub->name }}
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

                        </div>


                        {{-- Product Type --}}

                        <div>

                            <label
                                for="product_type_id"
                                class="tx-label"
                            >

                                <span
                                    class="tx-lvl-dot"
                                    style="background:#7c3aed;"
                                ></span>

                                Sub Sub Category

                            </label>


                            <div class="tx-select-wrap">

                                <select
                                    name="product_type_id"
                                    id="product_type_id"
                                    data-cascade-target="collection_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Sub Sub Category --
                                    </option>

                                    @foreach($productTypes as $type)

                                        <option
                                            value="{{ $type->id }}"
                                            data-parent="{{ $type->sub_category_id }}"
                                            {{ old('product_type_id', $product->product_type_id) == $type->id ? 'selected' : '' }}
                                        >
                                            {{ $type->code }} - {{ $type->name }}
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

                        </div>


                        {{-- Collection --}}

                        <div>

                            <label
                                for="collection_id"
                                class="tx-label"
                            >

                                <span
                                    class="tx-lvl-dot"
                                    style="background:#d97706;"
                                ></span>

                                Collection

                            </label>


                            <div class="tx-select-wrap">

                                <select
                                    name="collection_id"
                                    id="collection_id"
                                    class="tx-field"
                                >

                                    <option value="">
                                        -- Select Collection --
                                    </option>

                                    @foreach($collections as $collection)

                                        <option
                                            value="{{ $collection->id }}"
                                            data-parent="{{ $collection->product_type_id }}"
                                            {{ old('collection_id', $product->collection_id) == $collection->id ? 'selected' : '' }}
                                        >
                                            {{ $collection->code }} - {{ $collection->name }}
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

                        </div>

                    </div>


                    <div class="tx-taxonomy-preview">

                        <span class="tx-taxonomy-preview-label">
                            Taxonomy
                        </span>

                        <span
                            id="taxonomy-preview-path"
                            class="tx-mono"
                        >
                            —
                        </span>

                    </div>

                </div>


                {{-- ====================================================
                     SECTION 03
                     GENERAL INFORMATION
                ==================================================== --}}

                <div class="tx-card tx-lvl-2">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            03
                        </span>

                        <div>

                            <h2>
                                General Information
                            </h2>

                            <p>
                                Basic identity and product details
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-2">

                        <div style="grid-column:1 / -1;">

                            <label
                                for="item_name"
                                class="tx-label"
                            >
                                Item Name
                            </label>

                            <input
                                type="text"
                                id="item_name"
                                name="item_name"
                                value="{{ old('item_name', $product->item_name) }}"
                                class="tx-field"
                                required
                            >

                            @error('item_name')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>


                        <div>

                            <label
                                for="type_of_sample"
                                class="tx-label"
                            >
                                Type of Sample
                            </label>

                            <input
                                type="text"
                                id="type_of_sample"
                                name="type_of_sample"
                                value="{{ old('type_of_sample', $product->type_of_sample) }}"
                                class="tx-field"
                                required
                            >

                            @error('type_of_sample')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>


                        <div>

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
                                value="{{ old('designed_by', $product->designed_by) }}"
                                class="tx-field"
                            >

                            @error('designed_by')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- ====================================================
                     SECTION 04
                     DIMENSIONS
                ==================================================== --}}

                <div class="tx-card tx-lvl-3">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            04
                        </span>

                        <div>

                            <h2>
                                Dimensions
                            </h2>

                            <p>
                                Product and carton measurements
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body">


                        {{-- PRODUCT DIMENSIONS --}}

                        <div class="tx-subpanel">

                            <div class="tx-subpanel-head">

                                <div>

                                    <h3>
                                        Product Dimensions
                                    </h3>

                                </div>


                                <span class="tx-subpanel-tag">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
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

                                        <label class="tx-dim-label">
                                            {{ $label }}
                                        </label>

                                        <div class="tx-dim-input-wrap">

                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="{{ $field }}"
                                                value="{{ old($field, $product->$field) }}"
                                                class="tx-field"
                                            >

                                            <span class="tx-dim-unit">
                                                cm
                                            </span>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>


                        {{-- CARTON DIMENSIONS --}}

                        <div class="tx-subpanel">

                            <div class="tx-subpanel-head">

                                <div>

                                    <h3>
                                        Carton Dimensions
                                    </h3>

                                </div>


                                <span class="tx-subpanel-tag">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
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
                                    'carton_height' => 'Height',
                                    'carton_width' => 'Width',
                                    'carton_length' => 'Length',
                                    'carton_depth' => 'Depth',
                                ] as $field => $label)

                                    <div>

                                        <label class="tx-dim-label">
                                            {{ $label }}
                                        </label>

                                        <div class="tx-dim-input-wrap">

                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="{{ $field }}"
                                                value="{{ old($field, $product->$field) }}"
                                                class="tx-field"
                                            >

                                            <span class="tx-dim-unit">
                                                cm
                                            </span>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ====================================================
                     SECTION 05
                     PRICING
                ==================================================== --}}

                <div class="tx-card tx-lvl-3">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            05
                        </span>

                        <div>

                            <h2>
                                Pricing
                            </h2>

                            <p>
                                Set the selling price for this product
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-2">

                        <div>

                            <label
                                for="price"
                                class="tx-label"
                            >
                                Price
                            </label>


                            <div class="tx-price-wrap">

                                <span class="tx-price-prefix">
                                    $
                                </span>

                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    value="{{ old('price', $product->price) }}"
                                    class="tx-field tx-price-field"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                >

                            </div>


                            <p class="tx-hint">
                                Enter the selling price per unit.
                            </p>


                            @error('price')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>


                        <div>

                            <label class="tx-label">
                                Current Price
                            </label>


                            <div class="tx-price-preview">

                                <span class="tx-price-preview-label">
                                    Unit Price
                                </span>

                                <span
                                    class="tx-price-preview-value"
                                    id="price-preview"
                                >
                                    ${{ number_format((float) old('price', $product->price), 2) }}
                                </span>

                            </div>


                            <p class="tx-hint">
                                Live preview of the price that will be saved.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ====================================================
                     SECTION 06
                     CLASSIFICATION & MEDIA
                ==================================================== --}}

                <div class="tx-card tx-lvl-4">

                    <div class="tx-card-head">

                        <span class="tx-card-icon">
                            06
                        </span>

                        <div>

                            <h2>
                                Classification & Media
                            </h2>

                            <p>
                                Materials, colors and product images
                            </p>

                        </div>

                    </div>


                    <div class="tx-card-body cols-2">


                        {{-- =================================================
                             MATERIALS
                        ================================================= --}}

                        <div>

                            @php

                                $selectedMaterials = old(
                                    'materials',
                                    is_array($product->materials)
                                        ? $product->materials
                                        : json_decode($product->materials ?? '[]', true)
                                );

                                $selectedMaterials = $selectedMaterials ?: [];

                            @endphp


                            <label
                                for="materials"
                                class="tx-label"
                            >
                                Materials
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
                                    size="8"
                                    autocomplete="off"
                                    class="tx-field tx-multi-select"
                                >

                                    <optgroup label="Solid Wood">

                                        @foreach([
                                            'Acacia Wood',
                                            'Ash Wood',
                                            'Beech Wood',
                                            'Birch Wood',
                                            'Mahogany',
                                            'Mango Wood',
                                            'Oak',
                                            'Pine',
                                            'Rubberwood',
                                            'Teak',
                                            'Walnut'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Engineered Wood">

                                        @foreach([
                                            'MDF',
                                            'Particle Board',
                                            'Plywood',
                                            'HDF',
                                            'Melamine Board'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Metal">

                                        @foreach([
                                            'Aluminum',
                                            'Brass',
                                            'Cast Iron',
                                            'Iron',
                                            'Stainless Steel',
                                            'Steel'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Glass & Stone">

                                        @foreach([
                                            'Clear Glass',
                                            'Tempered Glass',
                                            'Marble',
                                            'Granite',
                                            'Ceramic',
                                            'Concrete'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Natural Fibers">

                                        @foreach([
                                            'Bamboo',
                                            'Cane',
                                            'Rattan',
                                            'Seagrass',
                                            'Water Hyacinth',
                                            'Abaca'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Fabric & Upholstery">

                                        @foreach([
                                            'Boucle',
                                            'Canvas',
                                            'Cotton',
                                            'Leather',
                                            'PU Leather',
                                            'Linen',
                                            'Microfiber',
                                            'Polyester',
                                            'Velvet'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Plastic & Synthetic">

                                        @foreach([
                                            'ABS Plastic',
                                            'Acrylic',
                                            'Fiberglass',
                                            'Polypropylene',
                                            'PVC',
                                            'Resin'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Other">

                                        @foreach([
                                            'Composite',
                                            'Mixed Materials'
                                        ] as $material)

                                            <option
                                                value="{{ $material }}"
                                                {{ in_array($material, $selectedMaterials) ? 'selected' : '' }}
                                            >
                                                {{ $material }}
                                            </option>

                                        @endforeach

                                    </optgroup>

                                </select>


                                <div
                                    id="materials_chips"
                                    class="tx-multi-chips"
                                    aria-live="polite"
                                ></div>

                            </div>


                            @error('materials')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- =================================================
                             COLORS
                        ================================================= --}}

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


                            <label
                                for="color"
                                class="tx-label"
                            >
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
                                >

                                    <optgroup label="Basic Colors">

                                        @foreach([
                                            'Black',
                                            'White',
                                            'Gray',
                                            'Silver',
                                            'Gold',
                                            'Bronze'
                                        ] as $color)

                                            <option
                                                value="{{ $color }}"
                                                {{ in_array($color, $selectedColors) ? 'selected' : '' }}
                                            >
                                                {{ $color }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Wood Finishes">

                                        @foreach([
                                            'Natural',
                                            'Oak',
                                            'Walnut',
                                            'Teak',
                                            'Mahogany',
                                            'Espresso'
                                        ] as $color)

                                            <option
                                                value="{{ $color }}"
                                                {{ in_array($color, $selectedColors) ? 'selected' : '' }}
                                            >
                                                {{ $color }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Neutral">

                                        @foreach([
                                            'Beige',
                                            'Cream',
                                            'Ivory',
                                            'Taupe',
                                            'Brown'
                                        ] as $color)

                                            <option
                                                value="{{ $color }}"
                                                {{ in_array($color, $selectedColors) ? 'selected' : '' }}
                                            >
                                                {{ $color }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Accent Colors">

                                        @foreach([
                                            'Blue',
                                            'Green',
                                            'Red',
                                            'Yellow',
                                            'Orange',
                                            'Pink',
                                            'Purple'
                                        ] as $color)

                                            <option
                                                value="{{ $color }}"
                                                {{ in_array($color, $selectedColors) ? 'selected' : '' }}
                                            >
                                                {{ $color }}
                                            </option>

                                        @endforeach

                                    </optgroup>


                                    <optgroup label="Special Finishes">

                                        @foreach([
                                            'Matte Black',
                                            'Gloss White',
                                            'Brushed Gold',
                                            'Rose Gold',
                                            'Chrome'
                                        ] as $color)

                                            <option
                                                value="{{ $color }}"
                                                {{ in_array($color, $selectedColors) ? 'selected' : '' }}
                                            >
                                                {{ $color }}
                                            </option>

                                        @endforeach

                                    </optgroup>

                                </select>


                                <div
                                    id="color_chips"
                                    class="tx-multi-chips"
                                    aria-live="polite"
                                ></div>

                            </div>


                            @error('color')
                                <p class="tx-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- =================================================
                             IMAGE LINKS
                        ================================================= --}}

                        <div>

                            <label
                                for="image_links"
                                class="tx-label"
                            >
                                Image Links
                            </label>

                            <p class="tx-hint">
                                Add direct image URLs such as JPG, PNG or WEBP.
                            </p>


                            <div id="imageLinks">

                                @php

                                    $imageLinks = old(
                                        'image_links',
                                        $product->images
                                            ->where('image_type', 'url')
                                            ->pluck('image_url')
                                            ->filter()
                                            ->values()
                                            ->all()
                                    );

                                @endphp


                                @if(is_array($imageLinks) && count($imageLinks))

                                    @foreach($imageLinks as $link)

                                        <div class="tx-image-link-row">

                                            <input
                                                type="url"
                                                name="image_links[]"
                                                value="{{ $link }}"
                                                placeholder="https://example.com/image.jpg"
                                                class="tx-field"
                                            >


                                            <button
                                                type="button"
                                                class="tx-image-remove-link"
                                                onclick="removeImageLink(this)"
                                            >
                                                Remove
                                            </button>

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


                                        <button
                                            type="button"
                                            class="tx-image-remove-link"
                                            onclick="removeImageLink(this)"
                                        >
                                            Remove
                                        </button>

                                    </div>

                                @endif

                            </div>


                            <button
                                type="button"
                                onclick="addImageLink()"
                                class="tx-btn-ghost"
                                style="margin-top:4px;"
                            >
                                + Add Another Link
                            </button>


                            @if($errors->has('image_links') || $errors->has('image_links.*'))

                                <p class="tx-error">

                                    {{ $errors->first('image_links.*') ?? $errors->first('image_links') }}

                                </p>

                            @endif

                        </div>


                        {{-- =================================================
                             EXISTING IMAGES
                        ================================================= --}}

                        <div style="grid-column:1 / -1;">

                            <label class="tx-label">
                                Existing Product Images
                            </label>


                            <p class="tx-hint">
                                Remove images you no longer need. Changes are
                                applied when you click Update Product.
                            </p>


                            <div
                                id="existingImages"
                                class="tx-existing-images"
                            >

                                @forelse($product->images as $image)

                                    <div
                                        class="tx-existing-image"
                                        data-image-id="{{ $image->id }}"
                                    >

                                        <div class="tx-existing-image-preview">

                                            @if($image->image_type === 'upload' && $image->image_path)

                                                <img
                                                    src="{{ asset('storage/' . $image->image_path) }}"
                                                    alt="{{ $product->item_name }}"
                                                >

                                            @elseif($image->image_type === 'url' && $image->image_url)

                                                <img
                                                    src="{{ $image->image_url }}"
                                                    alt="{{ $product->item_name }}"
                                                >

                                            @else

                                                <div class="tx-image-placeholder">
                                                    No Preview
                                                </div>

                                            @endif

                                        </div>


                                        <div class="tx-existing-image-info">

                                            <strong>
                                                {{ ucfirst($image->image_type) }} Image
                                            </strong>


                                            @if($image->is_primary)

                                                <span class="tx-primary-badge">
                                                    Primary
                                                </span>

                                            @endif


                                            @if($image->image_type === 'url')

                                                <div class="tx-image-url">
                                                    {{ $image->image_url }}
                                                </div>

                                            @elseif($image->image_type === 'upload')

                                                <div class="tx-image-url">
                                                    {{ $image->image_path }}
                                                </div>

                                            @endif

                                        </div>


                                        <button
                                            type="button"
                                            class="tx-remove-existing-image"
                                            onclick="removeExistingImage({{ $image->id }}, this)"
                                        >

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M6 6l12 12M18 6L6 18"
                                                />
                                            </svg>

                                            Remove

                                        </button>

                                    </div>

                                @empty

                                    <div class="tx-no-images">
                                        No existing product images found.
                                    </div>

                                @endforelse

                            </div>

                        </div>


                        {{-- =================================================
                             NEW UPLOADS
                        ================================================= --}}

                        <div style="grid-column:1 / -1;">

                            <label
                                for="product_images"
                                class="tx-label"
                            >
                                Add New Product Images
                            </label>


                            <p class="tx-hint">
                                Select one or more image files. New files will
                                be added when you update the product.
                            </p>


                            <input
                                type="file"
                                id="product_images"
                                name="product_images[]"
                                accept="image/*,.pdf,.obj,.stl"
                                multiple
                                class="tx-field"
                                style="padding:7px 9px;"
                            >


                            @if($errors->has('product_images') || $errors->has('product_images.*'))

                                <p class="tx-error">
                                    {{ $errors->first('product_images.*') ?? $errors->first('product_images') }}
                                </p>

                            @endif


                            <div
                                id="newImagePreview"
                                class="tx-new-image-preview"
                            ></div>

                        </div>

                    </div>

                </div>


                {{-- ========================================================
                     FOOTER
                ======================================================== --}}

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
                            class="tx-btn-submit"
                            id="updateProductButton"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="15"
                                height="15"
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

                            Update Product

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- ============================================================
         TOM SELECT
    ============================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>


    <script>

        document.addEventListener('DOMContentLoaded', function () {


            /* ========================================================
               IMAGE LINKS
            ======================================================== */

            window.addImageLink = function () {

                const container =
                    document.getElementById('imageLinks');

                if (!container) {
                    return;
                }


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
                        class="tx-image-remove-link"
                        onclick="removeImageLink(this)"
                    >
                        Remove
                    </button>

                `;


                container.appendChild(row);


                const input =
                    row.querySelector('input');

                if (input) {
                    input.focus();
                }

            };


            window.removeImageLink = function (button) {

                if (!button) {
                    return;
                }


                const row =
                    button.closest('.tx-image-link-row');

                if (!row) {
                    return;
                }


                row.remove();

            };


            /* ========================================================
               EXISTING IMAGE REMOVE / UNDO
            ======================================================== */

            window.removeExistingImage = function (imageId, button) {

                if (!imageId || !button) {
                    return;
                }


                const card =
                    button.closest('.tx-existing-image');

                if (!card) {
                    return;
                }


                const container =
                    document.getElementById(
                        'removedImagesContainer'
                    );

                if (!container) {
                    return;
                }


                /*
                 * UNDO
                 */

                if (card.dataset.removing === '1') {

                    card.dataset.removing = '0';

                    card.classList.remove('removing');


                    const hidden =
                        container.querySelector(
                            'input[data-image-id="' + imageId + '"]'
                        );


                    if (hidden) {
                        hidden.remove();
                    }


                    button.innerHTML = `

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 6l12 12M18 6L6 18"
                            />
                        </svg>

                        Remove

                    `;

                    return;
                }


                /*
                 * MARK FOR REMOVAL
                 */

                card.dataset.removing = '1';

                card.classList.add('removing');


                const input =
                    document.createElement('input');

                input.type = 'hidden';

                input.name =
                    'remove_image_ids[]';

                input.value =
                    imageId;

                input.dataset.imageId =
                    imageId;


                container.appendChild(input);


                button.innerHTML = `

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 6l12 12M18 6L6 18"
                        />
                    </svg>

                    Undo

                `;

            };


            /* ========================================================
               TAXONOMY CASCADE
            ======================================================== */

            const categorySelect =
                document.getElementById('category_id');

            const subCategorySelect =
                document.getElementById('sub_category_id');

            const productTypeSelect =
                document.getElementById('product_type_id');

            const collectionSelect =
                document.getElementById('collection_id');

            const previewPath =
                document.getElementById(
                    'taxonomy-preview-path'
                );


            if (
                categorySelect &&
                subCategorySelect &&
                productTypeSelect &&
                collectionSelect
            ) {


                const initialSubCategory =
                    @json(old('sub_category_id', $product->sub_category_id));


                const initialProductType =
                    @json(old('product_type_id', $product->product_type_id));


                const initialCollection =
                    @json(old('collection_id', $product->collection_id));


                /*
                 * Filter child options based on parent.
                 */

                function filterSelect(
                    select,
                    parentValue,
                    selectedValue = null
                ) {

                    if (!select) {
                        return;
                    }


                    const options =
                        Array.from(select.options);


                    let foundSelected = false;


                    options.forEach(function (option) {

                        if (!option.value) {

                            option.hidden = false;

                            option.disabled = false;

                            return;
                        }


                        const optionParent =
                            option.getAttribute('data-parent');


                        const belongs =
                            optionParent !== null &&
                            String(optionParent) ===
                                String(parentValue);


                        option.hidden =
                            !belongs;

                        option.disabled =
                            !belongs;


                        if (
                            selectedValue !== null &&
                            String(option.value) ===
                                String(selectedValue) &&
                            belongs
                        ) {

                            option.selected = true;

                            foundSelected = true;

                        } else if (!belongs) {

                            option.selected = false;

                        }

                    });


                    if (!foundSelected) {

                        const current =
                            Array.from(select.options)
                                .find(option =>
                                    option.selected &&
                                    !option.disabled
                                );


                        if (!current) {
                            select.value = '';
                        }

                    }

                }


                /*
                 * Reset select.
                 */

                function resetSelect(select) {

                    if (!select) {
                        return;
                    }


                    select.value = '';


                    Array.from(select.options)
                        .forEach(function (option) {

                            if (!option.value) {

                                option.hidden = false;

                                option.disabled = false;

                                option.selected = true;

                                return;
                            }


                            option.hidden = true;

                            option.disabled = true;

                            option.selected = false;

                        });

                }


                /*
                 * Get selected label.
                 */

                function labelOf(select) {

                    if (!select || !select.value) {
                        return null;
                    }


                    const option =
                        select.options[
                            select.selectedIndex
                        ];


                    return option
                        ? option.textContent.trim()
                        : null;

                }


                /*
                 * Update taxonomy preview.
                 */

                function updateTaxonomyPreview() {

                    if (!previewPath) {
                        return;
                    }


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


                /*
                 * INITIALIZE
                 */

                function initializeTaxonomy() {

                    const categoryValue =
                        categorySelect.value;


                    if (!categoryValue) {

                        resetSelect(subCategorySelect);

                        resetSelect(productTypeSelect);

                        resetSelect(collectionSelect);

                        updateTaxonomyPreview();

                        return;
                    }


                    filterSelect(
                        subCategorySelect,
                        categoryValue,
                        initialSubCategory
                    );


                    const subCategoryValue =
                        subCategorySelect.value;


                    if (!subCategoryValue) {

                        resetSelect(productTypeSelect);

                        resetSelect(collectionSelect);

                        updateTaxonomyPreview();

                        return;
                    }


                    filterSelect(
                        productTypeSelect,
                        subCategoryValue,
                        initialProductType
                    );


                    const productTypeValue =
                        productTypeSelect.value;


                    if (!productTypeValue) {

                        resetSelect(collectionSelect);

                        updateTaxonomyPreview();

                        return;
                    }


                    filterSelect(
                        collectionSelect,
                        productTypeValue,
                        initialCollection
                    );


                    updateTaxonomyPreview();

                }


                /*
                 * CATEGORY CHANGE
                 */

                categorySelect.addEventListener(
                    'change',
                    function () {

                        resetSelect(productTypeSelect);

                        resetSelect(collectionSelect);


                        if (this.value) {

                            filterSelect(
                                subCategorySelect,
                                this.value
                            );

                        } else {

                            resetSelect(subCategorySelect);

                        }


                        updateTaxonomyPreview();

                    }
                );


                /*
                 * SUB CATEGORY CHANGE
                 */

                subCategorySelect.addEventListener(
                    'change',
                    function () {

                        resetSelect(collectionSelect);


                        if (this.value) {

                            filterSelect(
                                productTypeSelect,
                                this.value
                            );

                        } else {

                            resetSelect(productTypeSelect);

                        }


                        updateTaxonomyPreview();

                    }
                );


                /*
                 * PRODUCT TYPE CHANGE
                 */

                productTypeSelect.addEventListener(
                    'change',
                    function () {

                        if (this.value) {

                            filterSelect(
                                collectionSelect,
                                this.value
                            );

                        } else {

                            resetSelect(collectionSelect);

                        }


                        updateTaxonomyPreview();

                    }
                );


                /*
                 * COLLECTION CHANGE
                 */

                collectionSelect.addEventListener(
                    'change',
                    updateTaxonomyPreview
                );


                initializeTaxonomy();

            }


            /* ========================================================
               TOM SELECT
            ======================================================== */

            const materialsSelect =
                document.getElementById('materials');

            const colorSelect =
                document.getElementById('color');


            if (
                materialsSelect &&
                typeof TomSelect !== 'undefined'
            ) {

                new TomSelect(
                    materialsSelect,
                    {

                        plugins: {

                            remove_button: {
                                title: 'Remove this material'
                            }

                        },

                        create: false,

                        maxItems: 100,

                        hideSelected: true,

                        closeAfterSelect: false,

                        allowEmptyOption: true,

                        placeholder:
                            'Select one or more materials...',

                        render: {

                            optgroup_header:
                                function(data, escape) {

                                    return `
                                        <div class="optgroup-header">
                                            ${escape(data.label)}
                                        </div>
                                    `;

                                }

                        }

                    }
                );

            }


            if (
                colorSelect &&
                typeof TomSelect !== 'undefined'
            ) {

                new TomSelect(
                    colorSelect,
                    {

                        plugins: {

                            remove_button: {
                                title: 'Remove this color'
                            }

                        },

                        create: false,

                        maxItems: 100,

                        hideSelected: true,

                        closeAfterSelect: false,

                        allowEmptyOption: true,

                        placeholder:
                            'Select one or more colors...',

                        render: {

                            optgroup_header:
                                function(data, escape) {

                                    return `
                                        <div class="optgroup-header">
                                            ${escape(data.label)}
                                        </div>
                                    `;

                                }

                        }

                    }
                );

            }


            /* ========================================================
               CUSTOM CHIPS
            ======================================================== */

            function initializeChips(select) {

                if (!select) {
                    return;
                }


                const wrapper =
                    select.closest(
                        '.tx-multi-select-wrap'
                    );


                if (!wrapper) {
                    return;
                }


                const chips =
                    wrapper.querySelector(
                        '.tx-multi-chips'
                    );


                const clearButton =
                    wrapper.querySelector(
                        '.tx-multi-clear'
                    );


                if (!chips) {
                    return;
                }


                function updateChips() {

                    const selectedValues =
                        Array.from(
                            select.selectedOptions
                        )
                        .map(option => option.value)
                        .filter(Boolean);


                    chips.innerHTML = '';


                    selectedValues.forEach(
                        function(value) {

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


                            const removeButton =
                                document.createElement(
                                    'button'
                                );


                            removeButton.type =
                                'button';

                            removeButton.innerHTML =
                                '&times;';


                            removeButton.setAttribute(
                                'aria-label',
                                'Remove ' + value
                            );


                            removeButton.addEventListener(
                                'click',
                                function(event) {

                                    event.preventDefault();


                                    if (
                                        select.tomselect
                                    ) {

                                        select.tomselect
                                            .removeItem(value);

                                    } else {

                                        Array.from(
                                            select.options
                                        )
                                        .forEach(
                                            function(option) {

                                                if (
                                                    option.value ===
                                                    value
                                                ) {

                                                    option.selected =
                                                        false;

                                                }

                                            }
                                        );


                                        select.dispatchEvent(
                                            new Event(
                                                'change',
                                                {
                                                    bubbles: true
                                                }
                                            )
                                        );

                                    }

                                }
                            );


                            chip.appendChild(text);

                            chip.appendChild(
                                removeButton
                            );

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
                    updateChips
                );


                if (select.tomselect) {

                    select.tomselect.on(
                        'item_add',
                        updateChips
                    );

                    select.tomselect.on(
                        'item_remove',
                        updateChips
                    );

                    select.tomselect.on(
                        'clear',
                        updateChips
                    );

                }


                if (clearButton) {

                    clearButton.addEventListener(
                        'click',
                        function(event) {

                            event.preventDefault();


                            if (select.tomselect) {

                                select.tomselect.clear();

                            } else {

                                Array.from(
                                    select.options
                                )
                                .forEach(
                                    option => {
                                        option.selected =
                                            false;
                                    }
                                );


                                select.dispatchEvent(
                                    new Event(
                                        'change',
                                        {
                                            bubbles: true
                                        }
                                    )
                                );

                            }


                            updateChips();

                        }
                    );

                }


                updateChips();

            }


            initializeChips(materialsSelect);

            initializeChips(colorSelect);


            /* ========================================================
               PRICE PREVIEW
            ======================================================== */

            const priceInput =
                document.getElementById('price');

            const pricePreview =
                document.getElementById(
                    'price-preview'
                );


            if (priceInput && pricePreview) {

                function updatePricePreview() {

                    let value =
                        parseFloat(
                            priceInput.value
                        );


                    if (
                        Number.isNaN(value) ||
                        value < 0
                    ) {

                        value = 0;

                    }


                    pricePreview.textContent =
                        '$' +
                        value.toLocaleString(
                            'en-PH',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );

                }


                priceInput.addEventListener(
                    'input',
                    updatePricePreview
                );


                priceInput.addEventListener(
                    'change',
                    updatePricePreview
                );


                updatePricePreview();

            }


            /* ========================================================
               NEW IMAGE PREVIEW
            ======================================================== */

            const productImagesInput =
                document.getElementById(
                    'product_images'
                );

            const newImagePreview =
                document.getElementById(
                    'newImagePreview'
                );


            if (
                productImagesInput &&
                newImagePreview
            ) {

                productImagesInput.addEventListener(
                    'change',
                    function() {

                        newImagePreview.innerHTML =
                            '';


                        const files =
                            Array.from(
                                this.files || []
                            );


                        files.forEach(
                            function(file) {

                                const item =
                                    document.createElement(
                                        'div'
                                    );


                                item.className =
                                    'tx-new-image-item';


                                if (
                                    file.type.startsWith(
                                        'image/'
                                    )
                                ) {

                                    const img =
                                        document.createElement(
                                            'img'
                                        );


                                    const url =
                                        URL.createObjectURL(
                                            file
                                        );


                                    img.src =
                                        url;


                                    img.alt =
                                        file.name;


                                    img.onload =
                                        function() {

                                            URL.revokeObjectURL(
                                                url
                                            );

                                        };


                                    item.appendChild(
                                        img
                                    );

                                } else {

                                    const placeholder =
                                        document.createElement(
                                            'div'
                                        );


                                    placeholder.className =
                                        'tx-new-image-file';


                                    placeholder.textContent =
                                        'FILE';


                                    item.appendChild(
                                        placeholder
                                    );

                                }


                                const name =
                                    document.createElement(
                                        'div'
                                    );


                                name.className =
                                    'tx-new-image-name';


                                name.textContent =
                                    file.name;


                                item.appendChild(
                                    name
                                );


                                newImagePreview.appendChild(
                                    item
                                );

                            }
                        );

                    }
                );

            }


            /* ========================================================
               IMAGE URL CLEANUP
            ======================================================== */

            const imageLinksContainer =
                document.getElementById(
                    'imageLinks'
                );


            if (imageLinksContainer) {

                imageLinksContainer.addEventListener(
                    'blur',
                    function(event) {

                        if (
                            event.target &&
                            event.target.matches(
                                'input[name="image_links[]"]'
                            )
                        ) {

                            event.target.value =
                                event.target.value.trim();

                        }

                    },
                    true
                );

            }


            /* ========================================================
               FORM DOUBLE SUBMISSION PROTECTION
            ======================================================== */

            const form =
                document.getElementById(
                    'edit_product_form'
                );


            if (form) {

                form.addEventListener(
                    'submit',
                    function() {

                        const submitButton =
                            form.querySelector(
                                '.tx-btn-submit'
                            );


                        if (!submitButton) {
                            return;
                        }


                        if (
                            submitButton.dataset.submitted ===
                            '1'
                        ) {

                            return;

                        }


                        submitButton.dataset.submitted =
                            '1';


                        submitButton.disabled =
                            true;


                        submitButton.innerHTML = `

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="15"
                                height="15"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6l4 2"
                                />
                            </svg>

                            Updating...

                        `;

                    }
                );

            }

        });

    </script>

</x-mi_app>