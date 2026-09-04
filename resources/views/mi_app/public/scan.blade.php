<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Product</title>

    <style>
        :root {
            --sc-primary: #2C6E8C;
            --sc-primary-soft: #E4EEF5;
            --sc-ink: #1a1a1a;
            --sc-ink-faint: #888;
            --sc-line: #e5e5e5;
            --sc-bg: #f7f8f9;
            --sc-danger: #dc2626;
            --sc-danger-soft: #fee2e2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI',
                Roboto, sans-serif;
            background: var(--sc-bg);
            color: var(--sc-ink);
        }

        .sc-wrap {
            max-width: 480px;
            margin: 0 auto;
            padding: 1.25rem 1rem 3rem;
        }

        .sc-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .sc-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 0.3rem;
        }

        .sc-subtitle {
            font-size: 0.85rem;
            color: var(--sc-ink-faint);
            margin: 0;
        }

        .sc-card {
            background: #fff;
            border: 1px solid var(--sc-line);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .sc-body {
            padding: 1.25rem;
        }

        .sc-error {
            background: var(--sc-danger-soft);
            color: var(--sc-danger);
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        /* ================================
           CAMERA SCANNER
        ================================= */

        #scReaderWrap {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        #scReader {
            width: 100%;
            height: 100%;
        }

        .sc-scanner-hint {
            text-align: center;
            font-size: 0.78rem;
            color: var(--sc-ink-faint);
            margin-top: 0.75rem;
        }

        .sc-camera-error {
            display: none;
            text-align: center;
            padding: 1.5rem 1rem;
            color: var(--sc-ink-faint);
            font-size: 0.85rem;
        }

        .sc-camera-error.visible {
            display: block;
        }

        /* ================================
           MANUAL ENTRY
        ================================= */

        .sc-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--sc-ink-faint);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sc-divider::before,
        .sc-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--sc-line);
        }

        .sc-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .sc-field input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            font-size: 0.95rem;
            border: 1px solid var(--sc-line);
            border-radius: 8px;
            font-family: inherit;
        }

        .sc-field input:focus {
            outline: none;
            border-color: var(--sc-primary);
        }

        .sc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 9px;
            font-size: 0.88rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            margin-top: 0.8rem;
            background: var(--sc-primary);
            color: #fff;
        }

        .sc-btn:hover {
            opacity: 0.92;
        }

        .sc-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .sc-footer {
            text-align: center;
            font-size: 0.72rem;
            color: var(--sc-ink-faint);
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>

<div class="sc-wrap">

    <div class="sc-header">
        <h1 class="sc-title">Scan a Product</h1>
        <p class="sc-subtitle">
            Point your camera at a product tag, or enter the code manually.
        </p>
    </div>

    @if(!empty($error))
        <div class="sc-error">
            {{ $error }}
        </div>
    @endif

    {{-- =========================================================
         CAMERA SCANNER
    ========================================================== --}}

    <div class="sc-card">
        <div class="sc-body">

            <div id="scReaderWrap">
                <div id="scReader"></div>
            </div>

            <p class="sc-scanner-hint" id="scHint">
                Requesting camera access…
            </p>

            <div class="sc-camera-error" id="scCameraError">
                Camera access isn't available. You can still enter the
                product code manually below.
            </div>

        </div>
    </div>

    {{-- =========================================================
         MANUAL ENTRY
    ========================================================== --}}

    <div class="sc-card">
        <div class="sc-body">

            <div class="sc-divider">or enter manually</div>

            <form id="scManualForm" method="GET" action="{{ route('mi_app.scan') }}">

                <div class="sc-field">
                    <label for="scCodeInput">Product Code / SKU</label>
                    <input
                        type="text"
                        id="scCodeInput"
                        name="code"
                        placeholder="e.g. MI-1042"
                        autocomplete="off"
                        autocapitalize="characters"
                        required
                    >
                </div>

                <button type="submit" class="sc-btn">
                    Find Product
                </button>

            </form>

        </div>
    </div>

    <p class="sc-footer">
        Having trouble? Make sure the tag is well lit and centered
        in the frame.
    </p>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>

    const SC_SCAN_BASE_URL = @json(route('mi_app.scan'));

    let scHasNavigated = false;

    function scNavigateToCode(code) {

        if (scHasNavigated) {
            return;
        }

        code = String(code || '').trim();

        if (!code) {
            return;
        }

        scHasNavigated = true;

        const url = new URL(SC_SCAN_BASE_URL, window.location.origin);
        url.searchParams.set('code', code);

        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', function () {

        const hint = document.getElementById('scHint');
        const cameraError = document.getElementById('scCameraError');
        const readerWrap = document.getElementById('scReaderWrap');

        if (typeof Html5Qrcode === 'undefined') {

            hint.style.display = 'none';
            readerWrap.style.display = 'none';
            cameraError.classList.add('visible');
            return;
        }

        const scanner = new Html5Qrcode('scReader');

        Html5Qrcode.getCameras()
            .then(function (cameras) {

                if (!cameras || !cameras.length) {
                    throw new Error('No camera found');
                }

                // Prefer a rear-facing camera on mobile devices.
                const preferred =
                    cameras.find(function (cam) {
                        return /back|rear|environment/i.test(cam.label);
                    }) || cameras[0];

                return scanner.start(
                    preferred.id,
                    {
                        fps: 10,
                        qrbox: { width: 240, height: 240 },
                    },
                    function onScanSuccess(decodedText) {
                        hint.textContent = 'Product found — loading…';
                        scanner.stop().catch(function () {});
                        scNavigateToCode(decodedText);
                    },
                    function onScanFailure() {
                        // Fired continuously while no code is in frame —
                        // intentionally ignored, not an actual error.
                    }
                );

            })
            .then(function () {
                hint.textContent = 'Center the product tag in the frame.';
            })
            .catch(function (err) {
                console.warn('Camera scanner unavailable:', err);
                hint.style.display = 'none';
                readerWrap.style.display = 'none';
                cameraError.classList.add('visible');
            });

        window.addEventListener('beforeunload', function () {
            scanner.stop().catch(function () {});
        });

    });

</script>

</body>
</html>