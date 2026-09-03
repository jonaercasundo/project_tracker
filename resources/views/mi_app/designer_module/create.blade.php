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

    <style>

        /* =========================================================
           DESIGN SYSTEM
           ========================================================= */

        .tx-console {
            --tx-bg: #f8fafc;
            --tx-surface: #ffffff;
            --tx-surface-soft: #f8fafc;

            --tx-ink: #111827;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e2e8f0;
            --tx-line-soft: #eef2f7;

            --tx-primary: #2563eb;
            --tx-primary-hover: #1d4ed8;
            --tx-primary-soft: #eff6ff;
            --tx-primary-ink: #ffffff;

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-warning: #d97706;
            --tx-warning-soft: #fffbeb;

            --tx-purple: #7c3aed;
            --tx-purple-soft: #f5f3ff;

            --tx-font-display:
                'Space Grotesk',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-body:
                'Inter',
                ui-sans-serif,
                system-ui,
                sans-serif;

            --tx-font-mono:
                'JetBrains Mono',
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;

            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);

            min-height: 100%;
        }

        /* =========================================================
           DARK MODE
           ========================================================= */

        .tx-console.dark {
            --tx-bg: #0f172a;
            --tx-surface: #111827;
            --tx-surface-soft: #172033;

            --tx-ink: #f8fafc;
            --tx-ink-soft: #94a3b8;
            --tx-ink-faint: #64748b;

            --tx-line: #273449;
            --tx-line-soft: #1e293b;

            --tx-primary: #3b82f6;
            --tx-primary-hover: #60a5fa;
            --tx-primary-soft: #172554;

            --tx-success: #10b981;
            --tx-success-soft: #052e24;

            --tx-danger: #ef4444;
            --tx-danger-soft: #3b1212;

            --tx-warning: #f59e0b;
            --tx-warning-soft: #3b2a0b;

            --tx-purple: #a78bfa;
            --tx-purple-soft: #2e1065;
        }

        /* =========================================================
           RESET / GLOBAL
           ========================================================= */

        .tx-console *,
        .tx-console *::before,
        .tx-console *::after {
            box-sizing: border-box;
        }

        .tx-display {
            font-family: var(--tx-font-display);
            letter-spacing: -0.02em;
        }

        .tx-mono {
            font-family: var(--tx-font-mono);
            letter-spacing: 0.01em;
        }

        /* =========================================================
           PAGE
           ========================================================= */

        .tx-shell {
            width: 100%;
            max-width: 1450px;

            margin: 0 auto;

            padding: 28px 24px 80px;
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
            margin-bottom: 22px;

            border-bottom: 1px solid var(--tx-line);
        }

        .tx-header-content {
            min-width: 0;
        }

        .tx-eyebrow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

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

        .tx-title {
            margin: 0;

            font-family: var(--tx-font-display);

            font-size: 30px;
            line-height: 1.15;

            font-weight: 700;

            color: var(--tx-ink);
        }

        .tx-subtitle {
            max-width: 720px;

            margin: 8px 0 0;

            color: var(--tx-ink-soft);

            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================================================
           BACK BUTTON
           ========================================================= */

        .tx-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            flex-shrink: 0;

            min-height: 40px;

            padding: 0 15px;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            transition:
                border-color .18s ease,
                color .18s ease,
                background .18s ease,
                transform .18s ease;
        }

        .tx-back:hover {
            border-color: var(--tx-primary);
            color: var(--tx-primary);

            background: var(--tx-primary-soft);

            transform: translateX(-2px);
        }

        .tx-back svg {
            width: 16px;
            height: 16px;
        }

        /* =========================================================
           PROGRESS
           ========================================================= */

        .tx-progress-wrap {
            display: flex;
            align-items: center;
            gap: 13px;

            padding: 8px 10px 8px 14px;

            margin-bottom: 22px;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .03);
        }

        .tx-progress-track {
            flex: 1 1 auto;

            height: 6px;

            overflow: hidden;

            border-radius: 999px;

            background: var(--tx-line);
        }

        #progress_bar {
            width: 0%;
            height: 100%;

            border-radius: 999px;

            background: var(--tx-primary);

            transition: width .25s ease;
        }

        #progress_label {
            padding-right: 4px;

            white-space: nowrap;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 600;
        }

        /* =========================================================
           CARDS
           ========================================================= */

        .tx-card {
            overflow: hidden;

            margin-bottom: 18px;

            border: 1px solid var(--tx-line);
            border-radius: 16px;

            background: var(--tx-surface);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .025),
                0 8px 25px rgba(15, 23, 42, .025);
        }

        @media (prefers-reduced-motion: no-preference) {

            .tx-card {
                animation:
                    tx-reveal .35s ease-out both;
            }

            .tx-card:nth-of-type(1) {
                animation-delay: 0ms;
            }

            .tx-card:nth-of-type(2) {
                animation-delay: 50ms;
            }

            .tx-card:nth-of-type(3) {
                animation-delay: 100ms;
            }

            .tx-card:nth-of-type(4) {
                animation-delay: 150ms;
            }

            @keyframes tx-reveal {

                from {
                    opacity: 0;
                    transform: translateY(7px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }

            }

        }

        /* =========================================================
           CARD HEADER
           ========================================================= */

        .tx-card-head {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 17px 20px;

            border-bottom: 1px solid var(--tx-line);

            background: var(--tx-surface);
        }

        .tx-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 36px;
            height: 36px;

            flex-shrink: 0;

            border-radius: 10px;

            font-family: var(--tx-font-mono);

            font-size: 11px;
            font-weight: 700;
        }

        .tx-card-head h2 {
            margin: 0;

            font-family: var(--tx-font-display);

            font-size: 15px;
            font-weight: 700;

            color: var(--tx-ink);
        }

        .tx-card-head p {
            margin: 3px 0 0;

            color: var(--tx-ink-soft);

            font-size: 11px;
            line-height: 1.5;
        }

        /* Section colors */

        .lvl-1 .tx-card-icon {
            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }

        .lvl-2 .tx-card-icon {
            background: #eff6ff;
            color: #2563eb;
        }

        .lvl-3 .tx-card-icon {
            background: var(--tx-purple-soft);
            color: var(--tx-purple);
        }

        /* =========================================================
           CARD BODY
           ========================================================= */

        .tx-card-body {
            display: grid;

            grid-template-columns:
                repeat(1, minmax(0, 1fr));

            gap: 18px;

            padding: 20px;
        }

        .tx-card-body.cols-2 {
            grid-template-columns:
                repeat(1, minmax(0, 1fr));
        }

        .tx-card-body.cols-4 {
            grid-template-columns:
                repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 700px) {

            .tx-card-body.cols-2 {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (min-width: 900px) {

            .tx-card-body.cols-4 {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }

        }

        .col-span-2 {
            grid-column: span 1;
        }

        @media (min-width: 900px) {

            .col-span-2 {
                grid-column: span 2;
            }

        }

        /* =========================================================
           LABELS
           ========================================================= */

        .tx-label {
            display: block;

            margin-bottom: 7px;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .tx-required {
            color: var(--tx-danger);
        }

        .tx-lvl-dot {
            display: inline-block;

            width: 6px;
            height: 6px;

            margin-right: 5px;

            border-radius: 999px;
        }

        .tx-hint {
            margin: -2px 0 8px;

            color: var(--tx-ink-faint);

            font-size: 10px;
            line-height: 1.5;
        }

        /* =========================================================
           FORM FIELDS
           ========================================================= */

        .tx-field {
            width: 100%;

            min-height: 40px;

            padding: 9px 11px;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            outline: none;

            background: var(--tx-bg);
            color: var(--tx-ink);

            font-family: var(--tx-font-body);

            font-size: 12px;

            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                background .18s ease;
        }

        .tx-field:hover {
            border-color: #cbd5e1;
        }

        .tx-field::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-field:focus {
            border-color: var(--tx-primary);

            background: var(--tx-surface);

            box-shadow:
                0 0 0 4px var(--tx-primary-soft);
        }

        .tx-field.field-invalid {
            border-color: var(--tx-danger) !important;
        }

        .tx-field.field-invalid:focus {
            box-shadow:
                0 0 0 4px var(--tx-danger-soft);
        }

        /* =========================================================
           SELECT
           ========================================================= */

        .tx-select-wrap {
            position: relative;
        }

        .tx-select-wrap select {
            appearance: none;

            padding-right: 36px;
        }

        .tx-select-wrap > svg {
            position: absolute;

            right: 11px;
            top: 50%;

            width: 15px;
            height: 15px;

            transform: translateY(-50%);

            color: var(--tx-ink-faint);

            pointer-events: none;
        }

        /* =========================================================
           ERRORS
           ========================================================= */

        .tx-error {
            display: flex;
            align-items: flex-start;

            gap: 5px;

            margin-top: 6px;

            color: var(--tx-danger);

            font-size: 10px;
            font-weight: 600;

            line-height: 1.5;
        }

        .tx-error svg {
            width: 13px;
            height: 13px;

            flex-shrink: 0;

            margin-top: 1px;
        }

        /* =========================================================
           TAXONOMY PREVIEW
           ========================================================= */

        .tx-taxonomy-preview {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin: 0 20px 20px;

            padding: 11px 13px;

            border: 1px dashed var(--tx-line);
            border-radius: 10px;

            background: var(--tx-bg);
        }

        .tx-taxonomy-preview-label {
            color: var(--tx-ink-faint);

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        #taxonomy-preview-path {
            color: var(--tx-ink);

            font-size: 10px;
            font-weight: 600;
        }

        /* =========================================================
           MULTI SELECT
           ========================================================= */

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

            font-size: 10px;
        }

        .tx-multi-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 27px;

            padding: 0 9px;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition:
                border-color .15s ease,
                color .15s ease,
                background .15s ease;
        }

        .tx-multi-clear:hover {
            border-color: var(--tx-primary);

            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }

        .tx-multi-chips {
            display: flex;
            flex-wrap: wrap;

            gap: 5px;

            min-height: 22px;
        }

        .tx-multi-chip {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border: 1px solid #bfdbfe;
            border-radius: 999px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10px;
            font-weight: 600;
        }

        .tx-multi-chip button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0;

            border: none;

            background: transparent;

            color: inherit;

            font-size: 13px;
            line-height: 1;

            cursor: pointer;
        }

        .tx-multi-chip button:hover {
            opacity: .65;
        }

        /* =========================================================
           TOM SELECT
           ========================================================= */

        .tx-console .ts-wrapper {
            width: 100%;

            font-family: var(--tx-font-body);
        }

        .tx-console .ts-control {
            min-height: 40px;

            padding: 5px 9px;

            border: 1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-bg);

            box-shadow: none;

            color: var(--tx-ink);

            font-size: 12px;
        }

        .tx-console .ts-control input {
            color: var(--tx-ink);

            font-family: var(--tx-font-body);

            font-size: 12px;
        }

        .tx-console .ts-control input::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-console .ts-wrapper.focus .ts-control {
            border-color: var(--tx-primary);

            background: var(--tx-surface);

            box-shadow:
                0 0 0 4px var(--tx-primary-soft);
        }

        .tx-console .ts-dropdown {
            overflow: hidden;

            margin-top: 5px;

            border: 1px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-surface);

            box-shadow:
                0 15px 35px rgba(15, 23, 42, .12);

            color: var(--tx-ink);

            font-size: 12px;
        }

        .tx-console .ts-dropdown .option {
            padding: 8px 11px;
        }

        .tx-console .ts-dropdown .option.active {
            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }

        .tx-console .ts-dropdown .optgroup-header {
            padding: 8px 11px 5px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-display);

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .06em;

            text-transform: uppercase;
        }

        .tx-console .ts-wrapper.multi .ts-control > div {
            padding: 4px 7px;

            border: 1px solid #bfdbfe;
            border-radius: 7px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10px;
            font-weight: 600;
        }

        .tx-console .ts-wrapper.multi .ts-control > div.active {
            background: var(--tx-primary);

            color: #ffffff;
        }

        .tx-console .ts-wrapper.multi .ts-control > div .remove {
            border-left-color: rgba(37, 99, 235, .2);
        }

        /* =========================================================
           DIMENSION PANELS
           ========================================================= */

        .tx-subpanel {
            padding: 15px;

            border: 1px solid var(--tx-line);
            border-radius: 13px;

            background: var(--tx-bg);
        }

        .tx-subpanel + .tx-subpanel {
            margin-top: 14px;
        }

        .tx-subpanel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 13px;
        }

        .tx-subpanel-head h3 {
            margin: 0;

            color: var(--tx-ink-soft);

            font-family: var(--tx-font-display);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .tx-subpanel-head p {
            margin: 3px 0 0;

            color: var(--tx-ink-faint);

            font-size: 9px;
        }

        .tx-subpanel-tag {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            flex-shrink: 0;

            padding: 5px 8px;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 9px;
            font-weight: 700;
        }

        .tx-subpanel-tag svg {
            width: 13px;
            height: 13px;
        }

        .tx-dims-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 10px;
        }

        @media (min-width: 650px) {

            .tx-dims-grid {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }

        }

        .tx-dim-label {
            display: block;

            margin-bottom: 5px;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 600;
        }

        .tx-dim-input-wrap {
            position: relative;
        }

        .tx-dim-input-wrap input {
            padding-right: 34px;
        }

        .tx-dim-unit {
            position: absolute;

            right: 10px;
            top: 50%;

            transform: translateY(-50%);

            color: var(--tx-ink-faint);

            font-size: 9px;
            font-weight: 700;
        }

        /* =========================================================
           MEDIA / UPLOAD
           ========================================================= */

        .tx-upload-section {
            display: grid;

            grid-template-columns:
                repeat(1, minmax(0, 1fr));

            gap: 18px;
        }

        @media (min-width: 900px) {

            .tx-upload-section {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        .tx-link-box {
            padding: 15px;

            border: 1px solid var(--tx-line);
            border-radius: 13px;

            background: var(--tx-bg);
        }

        .tx-link-list {
            display: flex;
            flex-direction: column;

            gap: 8px;
        }

        .tx-image-link-row {
            display: flex;
            align-items: center;

            gap: 8px;
        }

        .tx-image-link-row .tx-field {
            flex: 1;
        }

        .tx-link-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 36px;
            height: 36px;

            flex-shrink: 0;

            border: 1px solid var(--tx-line);
            border-radius: 9px;

            background: var(--tx-surface);

            color: var(--tx-ink-faint);

            cursor: pointer;

            transition:
                color .15s ease,
                background .15s ease,
                border-color .15s ease;
        }

        .tx-link-remove:hover {
            border-color: var(--tx-danger);

            background: var(--tx-danger-soft);

            color: var(--tx-danger);
        }

        .tx-btn-small {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 5px;

            margin-top: 9px;

            min-height: 31px;

            padding: 0 10px;

            border: 1px solid var(--tx-line);
            border-radius: 8px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition:
                border-color .15s ease,
                color .15s ease,
                background .15s ease;
        }

        .tx-btn-small:hover {
            border-color: var(--tx-primary);

            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }

        /* =========================================================
           DROPZONE
           ========================================================= */

        .tx-dropzone {
            position: relative;

            min-height: 185px;

            padding: 25px 18px;

            border: 2px dashed var(--tx-line);
            border-radius: 14px;

            background: var(--tx-bg);

            text-align: center;

            cursor: pointer;

            transition:
                border-color .18s ease,
                background .18s ease;
        }

        .tx-dropzone:hover,
        .tx-dropzone.drag-active {
            border-color: var(--tx-primary);

            background: var(--tx-primary-soft);
        }

        .tx-dropzone-empty {
            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            min-height: 130px;

            gap: 10px;

            pointer-events: none;
        }

        .tx-dropzone-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 48px;
            height: 48px;

            border-radius: 13px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }

        .tx-dropzone-icon svg {
            width: 23px;
            height: 23px;
        }

        .tx-dz-title {
            margin: 0;

            color: var(--tx-ink);

            font-size: 12px;
            font-weight: 700;
        }

        .tx-dz-sub {
            margin: 4px 0 0;

            color: var(--tx-ink-faint);

            font-size: 10px;
        }

        .tx-dropzone-filled {
            display: none;

            flex-direction: column;

            gap: 10px;

            text-align: left;
        }

        .tx-file-summary {
            display: flex;
            align-items: center;

            gap: 11px;

            padding: 10px;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

            background: var(--tx-surface);
        }

        .tx-file-thumb {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 52px;
            height: 52px;

            overflow: hidden;

            flex-shrink: 0;

            border: 1px solid var(--tx-line);
            border-radius: 9px;

            background: var(--tx-bg);

            color: var(--tx-ink-faint);
        }

        .tx-file-thumb img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .tx-file-meta {
            flex: 1;

            min-width: 0;
        }

        .tx-file-name {
            overflow: hidden;

            color: var(--tx-ink);

            font-size: 11px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tx-file-size {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-size: 9px;
        }

        .tx-file-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 32px;
            height: 32px;

            flex-shrink: 0;

            border: none;
            border-radius: 8px;

            background: transparent;

            color: var(--tx-ink-faint);

            cursor: pointer;

            transition:
                color .15s ease,
                background .15s ease;
        }

        .tx-file-remove:hover {
            background: var(--tx-danger-soft);

            color: var(--tx-danger);
        }

        .tx-file-count {
            padding: 8px 10px;

            border-radius: 8px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10px;
            font-weight: 700;

            text-align: center;
        }

        /* =========================================================
           ALERT
           ========================================================= */

        .tx-alert {
            display: flex;
            align-items: flex-start;

            gap: 9px;

            margin: 0 20px 18px;

            padding: 11px 13px;

            border: 1px solid var(--tx-danger);

            border-radius: 10px;

            background: var(--tx-danger-soft);

            color: var(--tx-danger);

            font-size: 11px;
            line-height: 1.5;
        }

        .tx-alert svg {
            width: 15px;
            height: 15px;

            flex-shrink: 0;
        }

        .tx-alert strong {
            font-weight: 700;
        }

        /* =========================================================
           FOOTER
           ========================================================= */

        .tx-footer {
            position: sticky;

            bottom: 12px;

            z-index: 30;

            margin-top: 22px;
        }

        .tx-footer-inner {
            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 8px;

            padding: 10px;

            border: 1px solid var(--tx-line);

            border-radius: 14px;

            background: rgba(255,255,255,.94);

            backdrop-filter: blur(10px);

            box-shadow:
                0 20px 45px -25px rgba(15, 23, 42, .35);
        }

        .tx-console.dark .tx-footer-inner {
            background: rgba(17,24,39,.94);
        }

        .tx-btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 40px;

            padding: 0 15px;

            border-radius: 9px;

            color: var(--tx-ink-soft);

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            transition:
                background .15s ease,
                color .15s ease;
        }

        .tx-btn-ghost:hover {
            background: var(--tx-bg);

            color: var(--tx-ink);
        }

        .tx-btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            min-height: 40px;

            padding: 0 17px;

            border: 1px solid var(--tx-primary);

            border-radius: 9px;

            background: var(--tx-primary);

            color: #ffffff;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background .18s ease,
                transform .18s ease,
                box-shadow .18s ease;
        }

        .tx-btn-submit:hover:not(:disabled) {
            background: var(--tx-primary-hover);

            transform: translateY(-1px);

            box-shadow:
                0 10px 22px rgba(37,99,235,.2);
        }

        .tx-btn-submit:disabled {
            opacity: .7;

            cursor: not-allowed;
        }

        .tx-btn-submit svg {
            width: 15px;
            height: 15px;
        }

        .spin {
            animation:
                tx-spin .8s linear infinite;
        }

        @keyframes tx-spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* =========================================================
           MOBILE
           ========================================================= */

        @media (max-width: 800px) {

            .tx-shell {
                padding: 20px 14px 60px;
            }

            .tx-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .tx-title {
                font-size: 25px;
            }

            .tx-back {
                width: 100%;
            }

        }

        @media (max-width: 600px) {

            .tx-shell {
                padding: 16px 10px 55px;
            }

            .tx-title {
                font-size: 22px;
            }

            .tx-subtitle {
                font-size: 11px;
            }

            .tx-card {
                border-radius: 13px;
            }

            .tx-card-head {
                padding: 14px;
            }

            .tx-card-body {
                padding: 14px;
            }

            .tx-taxonomy-preview {
                margin: 0 14px 14px;
            }

            .tx-progress-wrap {
                gap: 8px;
            }

            .tx-footer-inner {
                justify-content: stretch;
            }

            .tx-footer-inner > * {
                flex: 1;
            }

        }

        @media (prefers-reduced-motion: reduce) {

            .tx-card {
                animation: none;
            }

            .spin {
                animation: none;
            }

        }

    </style>


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
                                    style="background:#2563eb;"
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
                                    style="background:#3b82f6;"
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
                                    style="background:#7c3aed;"
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
                                    style="background:#8b5cf6;"
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

                            {{-- Materials --}}
                            <div>

                                <label
                                    for="materials"
                                    class="tx-label"
                                >
                                    Materials

                                    <span class="tx-required">
                                        *
                                    </span>
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
                                        required
                                        data-required
                                        class="tx-field tx-multi-select materials-select"
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($material, old('materials', [])) ? 'selected' : '' }}
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

                                    <p class="tx-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- Color --}}
                            <div>

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
                                                    {{ in_array($color, old('color', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($color, old('color', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($color, old('color', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($color, old('color', [])) ? 'selected' : '' }}
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
                                                    {{ in_array($color, old('color', [])) ? 'selected' : '' }}
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

                                    <p class="tx-error">
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

                                        H × W × L × D

                                    </span>

                                </div>


                                <div class="tx-dims-grid">

                                    @foreach([
                                        'product_height' => ['Height', '45', true],
                                        'product_width'  => ['Width', '60', false],
                                        'product_length' => ['Length', '120', false],
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
                                                    @if($config[2])
                                                        required
                                                        data-required
                                                    @endif
                                                >

                                                <span class="tx-dim-unit">
                                                    cm
                                                </span>

                                            </div>

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
                                        'carton_height' => ['Height', '50'],
                                        'carton_width'  => ['Width', '65'],
                                        'carton_length' => ['Length', '125'],
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
                                                >

                                                <span class="tx-dim-unit">
                                                    cm
                                                </span>

                                            </div>

                                            @error($field)

                                                <p class="tx-error">
                                                    {{ $message }}
                                                </p>

                                            @enderror

                                        </div>

                                    @endforeach

                                </div>

                            </div>

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