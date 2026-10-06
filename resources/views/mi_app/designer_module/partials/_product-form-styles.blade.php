    <style>

        /* =========================================================
           DESIGN SYSTEM
           ========================================================= */

        .tx-console {
            --tx-bg: #f6f8fb;
            --tx-surface: #ffffff;
            --tx-surface-soft: #f8fafc;

            --tx-ink: #0f172a;
            --tx-ink-soft: #64748b;
            --tx-ink-faint: #94a3b8;

            --tx-line: #e5eaf1;
            --tx-line-soft: #eef2f7;

            --tx-primary: #2f5bef;
            --tx-primary-hover: #2447c9;
            --tx-primary-soft: #eef2ff;
            --tx-primary-ink: #ffffff;
            --tx-primary-glow: rgba(47, 91, 239, .16);

            --tx-success: #059669;
            --tx-success-soft: #ecfdf5;

            --tx-danger: #dc2626;
            --tx-danger-soft: #fef2f2;

            --tx-warning: #d97706;
            --tx-warning-soft: #fffbeb;

            --tx-purple: #7c3aed;
            --tx-purple-soft: #f5f3ff;

            --tx-teal: #0891b2;
            --tx-teal-soft: #ecfeff;

            --tx-gold: #b45309;
            --tx-gold-soft: #fffbeb;

            --tx-shadow-sm: 0 1px 2px rgba(15, 23, 42, .04);
            --tx-shadow-md: 0 1px 2px rgba(15, 23, 42, .03), 0 10px 28px rgba(15, 23, 42, .05);
            --tx-shadow-lg: 0 20px 45px -25px rgba(15, 23, 42, .35);

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
            background:
                radial-gradient(1100px 420px at 12% -10%, rgba(47,91,239,.05), transparent 60%),
                radial-gradient(900px 380px at 100% 0%, rgba(124,58,237,.045), transparent 55%),
                var(--tx-bg);
            color: var(--tx-ink);

            min-height: 100%;
        }

        /* =========================================================
           DARK MODE
           ========================================================= */

        .tx-console.dark {
            --tx-bg: #0b1220;
            --tx-surface: #101a2c;
            --tx-surface-soft: #16223a;

            --tx-ink: #f8fafc;
            --tx-ink-soft: #94a3b8;
            --tx-ink-faint: #64748b;

            --tx-line: #223049;
            --tx-line-soft: #19243a;

            --tx-primary: #5b82ff;
            --tx-primary-hover: #7d9dff;
            --tx-primary-soft: #17224a;
            --tx-primary-glow: rgba(91, 130, 255, .22);

            --tx-success: #10b981;
            --tx-success-soft: #052e24;

            --tx-danger: #f87171;
            --tx-danger-soft: #3b1212;

            --tx-warning: #f59e0b;
            --tx-warning-soft: #3b2a0b;

            --tx-purple: #a78bfa;
            --tx-purple-soft: #241a4d;

            --tx-teal: #22d3ee;
            --tx-teal-soft: #0b2530;

            --tx-gold: #fbbf24;
            --tx-gold-soft: #2c2308;

            --tx-shadow-sm: 0 1px 2px rgba(0, 0, 0, .3);
            --tx-shadow-md: 0 1px 2px rgba(0,0,0,.3), 0 14px 32px rgba(0, 0, 0, .35);
            --tx-shadow-lg: 0 25px 55px -25px rgba(0, 0, 0, .6);
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

            padding: 32px 24px 90px;
        }

        /* =========================================================
           HEADER
           ========================================================= */

        .tx-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            gap: 24px;

            padding-bottom: 26px;
            margin-bottom: 24px;

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

            margin-bottom: 11px;

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

            font-size: 32px;
            line-height: 1.15;

            font-weight: 700;

            color: var(--tx-ink);

            background: linear-gradient(135deg, var(--tx-ink) 35%, var(--tx-primary) 150%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tx-subtitle {
            max-width: 720px;

            margin: 9px 0 0;

            color: var(--tx-ink-soft);

            font-size: 13px;
            line-height: 1.65;
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

            min-height: 42px;

            padding: 0 16px;

            border: 1px solid var(--tx-line);
            border-radius: 11px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            box-shadow: var(--tx-shadow-sm);

            transition:
                border-color .18s ease,
                color .18s ease,
                background .18s ease,
                transform .18s ease,
                box-shadow .18s ease;
        }

        .tx-back:hover {
            border-color: var(--tx-primary);
            color: var(--tx-primary);

            background: var(--tx-primary-soft);

            transform: translateX(-2px);

            box-shadow: 0 6px 16px var(--tx-primary-glow);
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
            gap: 14px;

            padding: 9px 12px 9px 16px;

            margin-bottom: 24px;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);

            box-shadow: var(--tx-shadow-sm);
        }

        .tx-progress-track {
            flex: 1 1 auto;

            height: 7px;

            overflow: hidden;

            border-radius: 999px;

            background: var(--tx-line-soft);
        }

        #progress_bar {
            width: 0%;
            height: 100%;

            border-radius: 999px;

            background: linear-gradient(90deg, var(--tx-primary), #7c3aed);

            box-shadow: 0 0 10px var(--tx-primary-glow);

            transition: width .3s ease;
        }

        #progress_label {
            padding-right: 4px;

            white-space: nowrap;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;
        }

        /* =========================================================
           CARDS
           ========================================================= */

        .tx-card {
            position: relative;

            overflow: hidden;

            margin-bottom: 20px;

            border: 1px solid var(--tx-line);
            border-radius: 18px;

            background: var(--tx-surface);

            box-shadow: var(--tx-shadow-md);

            transition: box-shadow .2s ease, border-color .2s ease;
        }

        .tx-card::before {
            content: '';

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 3px;

            background: var(--tx-line);
        }

        .tx-card.lvl-1::before {
            background: linear-gradient(90deg, var(--tx-primary), #60a5fa);
        }

        .tx-card.lvl-2::before {
            background: linear-gradient(90deg, var(--tx-teal), #22d3ee);
        }

        .tx-card.lvl-3::before {
            background: linear-gradient(90deg, var(--tx-purple), #c4b5fd);
        }

        @media (prefers-reduced-motion: no-preference) {

            .tx-card {
                animation:
                    tx-reveal .4s ease-out both;
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
                    transform: translateY(9px);
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

            gap: 13px;

            padding: 19px 22px;

            border-bottom: 1px solid var(--tx-line);

            background:
                linear-gradient(180deg, var(--tx-surface-soft), var(--tx-surface));
        }

        .tx-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 38px;
            height: 38px;

            flex-shrink: 0;

            border-radius: 11px;

            font-family: var(--tx-font-mono);

            font-size: 11px;
            font-weight: 700;

            box-shadow: inset 0 0 0 1px rgba(15,23,42,.03);
        }

        .tx-card-head h2 {
            margin: 0;

            font-family: var(--tx-font-display);

            font-size: 15.5px;
            font-weight: 700;

            color: var(--tx-ink);
        }

        .tx-card-head p {
            margin: 3px 0 0;

            color: var(--tx-ink-soft);

            font-size: 11.5px;
            line-height: 1.5;
        }

        /* Section colors */

        .lvl-1 .tx-card-icon {
            background: linear-gradient(135deg, var(--tx-primary-soft), var(--tx-primary-soft));
            color: var(--tx-primary);
        }

        .lvl-2 .tx-card-icon {
            background: var(--tx-teal-soft);
            color: var(--tx-teal);
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

            padding: 22px;
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
            display: flex;
            align-items: center;

            margin-bottom: 8px;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .tx-required {
            margin-left: 3px;
            color: var(--tx-danger);
        }

        .tx-lvl-dot {
            display: inline-block;

            width: 6px;
            height: 6px;

            margin-right: 6px;

            border-radius: 999px;

            box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 15%, transparent);
        }

        .tx-hint {
            margin: -2px 0 9px;

            color: var(--tx-ink-faint);

            font-size: 10.5px;
            line-height: 1.55;
        }

        /* =========================================================
           FORM FIELDS
           ========================================================= */

        .tx-field {
            width: 100%;

            min-height: 41px;

            padding: 9px 12px;

            border: 1.5px solid var(--tx-line);
            border-radius: 10px;

            outline: none;

            background: var(--tx-bg);
            color: var(--tx-ink);

            font-family: var(--tx-font-body);

            font-size: 12.5px;

            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                background .18s ease;
        }

        .tx-field:hover {
            border-color: #c7d2e3;
        }

        .tx-field::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-field:focus {
            border-color: var(--tx-primary);

            background: var(--tx-surface);

            box-shadow:
                0 0 0 4px var(--tx-primary-glow);
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

            cursor: pointer;
        }

        .tx-select-wrap > svg {
            position: absolute;

            right: 12px;
            top: 50%;

            width: 15px;
            height: 15px;

            transform: translateY(-50%);

            color: var(--tx-ink-faint);

            pointer-events: none;

            transition: color .15s ease;
        }

        .tx-select-wrap:focus-within > svg {
            color: var(--tx-primary);
        }

        /* =========================================================
           ERRORS
           ========================================================= */

        .tx-error {
            display: flex;
            align-items: flex-start;

            gap: 5px;

            margin-top: 7px;

            color: var(--tx-danger);

            font-size: 10.5px;
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

            gap: 9px;

            margin: 0 22px 22px;

            padding: 12px 15px;

            border: 1px dashed #c9d5ec;
            border-radius: 12px;

            background:
                linear-gradient(135deg, var(--tx-primary-soft), transparent 130%);
        }

        .tx-console.dark .tx-taxonomy-preview {
            border-color: #2c3c5c;
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

            font-size: 10.5px;
            font-weight: 600;
        }

        /* =========================================================
           MULTI SELECT
           ========================================================= */

        .tx-multi-select-wrap {
            display: flex;
            flex-direction: column;

            gap: 9px;
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

            padding: 0 10px;

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

            gap: 6px;

            min-height: 22px;
        }

        .tx-multi-chip {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border: 1px solid #bfd0fb;
            border-radius: 999px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10px;
            font-weight: 600;

            box-shadow: var(--tx-shadow-sm);
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
            min-height: 41px;

            padding: 5px 10px;

            border: 1.5px solid var(--tx-line);

            border-radius: 10px;

            background: var(--tx-bg);

            box-shadow: none;

            color: var(--tx-ink);

            font-size: 12.5px;
        }

        .tx-console .ts-control input {
            color: var(--tx-ink);

            font-family: var(--tx-font-body);

            font-size: 12.5px;
        }

        .tx-console .ts-control input::placeholder {
            color: var(--tx-ink-faint);
        }

        .tx-console .ts-wrapper.focus .ts-control {
            border-color: var(--tx-primary);

            background: var(--tx-surface);

            box-shadow:
                0 0 0 4px var(--tx-primary-glow);
        }

        .tx-console .ts-dropdown {
            overflow: hidden;

            margin-top: 6px;

            border: 1px solid var(--tx-line);

            border-radius: 12px;

            background: var(--tx-surface);

            box-shadow: var(--tx-shadow-lg);

            color: var(--tx-ink);

            font-size: 12.5px;
        }

        .tx-console .ts-dropdown .option {
            padding: 8px 12px;
        }

        .tx-console .ts-dropdown .option.active {
            background: var(--tx-primary-soft);

            color: var(--tx-primary);
        }

        .tx-console .ts-dropdown .optgroup-header {
            padding: 9px 12px 5px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-display);

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .06em;

            text-transform: uppercase;
        }

        .tx-console .ts-wrapper.multi .ts-control > div {
            padding: 4px 8px;

            border: 1px solid #bfd0fb;
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
            border-left-color: rgba(47, 91, 239, .2);
        }

        /* =========================================================
           DIMENSION PANELS
           ========================================================= */

        .tx-subpanel {
            padding: 17px;

            border: 1px solid var(--tx-line);
            border-radius: 15px;

            background: var(--tx-surface-soft);

            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .tx-subpanel:focus-within {
            border-color: #cddaf5;

            box-shadow: 0 0 0 4px var(--tx-primary-glow);
        }

        .tx-subpanel + .tx-subpanel {
            margin-top: 15px;
        }

        .tx-subpanel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 14px;
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

            font-size: 9.5px;
        }

        .tx-subpanel-tag {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            flex-shrink: 0;

            padding: 6px 10px;

            border: 1px solid var(--tx-line);
            border-radius: 999px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 9px;
            font-weight: 700;

            box-shadow: var(--tx-shadow-sm);
        }

        .tx-subpanel-tag svg {
            width: 13px;
            height: 13px;

            color: var(--tx-primary);
        }

        .tx-dims-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 11px;
        }

        @media (min-width: 650px) {

            .tx-dims-grid {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }

        }

        .tx-dim-label {
            display: block;

            margin-bottom: 6px;

            color: var(--tx-ink-soft);

            font-size: 10px;
            font-weight: 600;
        }

        .tx-dim-input-wrap {
            position: relative;
        }

        .tx-dim-input-wrap input {
            padding-right: 36px;

            font-family: var(--tx-font-mono);
        }

        .tx-dim-unit {
            position: absolute;

            right: 11px;
            top: 15px;

            color: var(--tx-ink-faint);

            font-size: 9px;
            font-weight: 700;
        }

        .tx-dim-inches {
            display: block;

            margin-top: 5px;

            color: var(--tx-ink-faint);

            font-family: var(--tx-font-mono);

            font-size: 9.5px;
            font-weight: 600;

            letter-spacing: .01em;
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
            padding: 17px;

            border: 1px solid var(--tx-line);
            border-radius: 15px;

            background: var(--tx-surface-soft);
        }

        .tx-link-list {
            display: flex;
            flex-direction: column;

            gap: 9px;
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

            width: 37px;
            height: 37px;

            flex-shrink: 0;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

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

            gap: 6px;

            margin-top: 10px;

            min-height: 32px;

            padding: 0 11px;

            border: 1px solid var(--tx-line);
            border-radius: 9px;

            background: var(--tx-surface);

            color: var(--tx-ink-soft);

            font-size: 10.5px;
            font-weight: 700;

            cursor: pointer;

            transition:
                border-color .15s ease,
                color .15s ease,
                background .15s ease,
                transform .15s ease;
        }

        .tx-btn-small:hover {
            border-color: var(--tx-primary);

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            transform: translateY(-1px);
        }

        /* =========================================================
           DROPZONE
           ========================================================= */

        .tx-dropzone {
            position: relative;

            min-height: 190px;

            padding: 26px 18px;

            border: 2px dashed #cbd6ea;
            border-radius: 16px;

            background: var(--tx-surface-soft);

            text-align: center;

            cursor: pointer;

            transition:
                border-color .18s ease,
                background .18s ease;
        }

        .tx-console.dark .tx-dropzone {
            border-color: #2c3c5c;
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

            gap: 11px;

            pointer-events: none;
        }

        .tx-dropzone-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 50px;
            height: 50px;

            border-radius: 14px;

            background: linear-gradient(135deg, var(--tx-primary-soft), #dbeafe);

            color: var(--tx-primary);

            box-shadow: var(--tx-shadow-sm);
        }

        .tx-console.dark .tx-dropzone-icon {
            background: var(--tx-primary-soft);
        }

        .tx-dropzone-icon svg {
            width: 23px;
            height: 23px;
        }

        .tx-dz-title {
            margin: 0;

            color: var(--tx-ink);

            font-size: 12.5px;
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

            gap: 12px;

            padding: 11px;

            border: 1px solid var(--tx-line);
            border-radius: 12px;

            background: var(--tx-surface);

            box-shadow: var(--tx-shadow-sm);
        }

        .tx-file-thumb {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 54px;
            height: 54px;

            overflow: hidden;

            flex-shrink: 0;

            border: 1px solid var(--tx-line);
            border-radius: 10px;

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

            font-size: 11.5px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tx-file-size {
            margin-top: 3px;

            color: var(--tx-ink-faint);

            font-size: 9.5px;
        }

        .tx-file-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 33px;
            height: 33px;

            flex-shrink: 0;

            border: none;
            border-radius: 9px;

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
            padding: 9px 11px;

            border-radius: 9px;

            background: var(--tx-primary-soft);

            color: var(--tx-primary);

            font-size: 10.5px;
            font-weight: 700;

            text-align: center;
        }

        /* =========================================================
           ALERT
           ========================================================= */

        .tx-alert {
            display: flex;
            align-items: flex-start;

            gap: 10px;

            margin: 0 22px 20px;

            padding: 12px 14px;

            border: 1px solid var(--tx-danger);

            border-radius: 12px;

            background: var(--tx-danger-soft);

            color: var(--tx-danger);

            font-size: 11.5px;
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

            bottom: 14px;

            z-index: 30;

            margin-top: 24px;
        }

        .tx-footer-inner {
            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 9px;

            padding: 11px;

            border: 1px solid var(--tx-line);

            border-radius: 16px;

            background: rgba(255,255,255,.92);

            backdrop-filter: blur(14px);

            box-shadow: var(--tx-shadow-lg);
        }

        .tx-console.dark .tx-footer-inner {
            background: rgba(16,26,44,.92);
        }

        .tx-btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 41px;

            padding: 0 16px;

            border-radius: 10px;

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

            gap: 8px;

            min-height: 41px;

            padding: 0 19px;

            border: none;

            border-radius: 10px;

            background: linear-gradient(135deg, var(--tx-primary), #7c3aed);

            color: #ffffff;

            font-size: 12.5px;
            font-weight: 700;

            cursor: pointer;

            box-shadow: 0 10px 22px var(--tx-primary-glow);

            transition:
                background .18s ease,
                transform .18s ease,
                box-shadow .18s ease,
                filter .18s ease;
        }

        .tx-btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);

            filter: brightness(1.06);

            box-shadow: 0 14px 28px var(--tx-primary-glow);
        }

        .tx-btn-submit:active:not(:disabled) {
            transform: translateY(0);
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
                padding: 22px 14px 60px;
            }

            .tx-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .tx-title {
                font-size: 26px;
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
                border-radius: 15px;
            }

            .tx-card-head {
                padding: 15px;
            }

            .tx-card-body {
                padding: 15px;
            }

            .tx-taxonomy-preview {
                margin: 0 15px 15px;
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
