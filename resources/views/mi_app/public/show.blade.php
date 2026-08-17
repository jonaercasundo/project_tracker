<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->item_name }} — Product Details</title>
    <style>
        :root {
            --pd-primary: #2C6E8C;
            --pd-primary-soft: #E4EEF5;
            --pd-ink: #1a1a1a;
            --pd-ink-faint: #888;
            --pd-line: #e5e5e5;
            --pd-bg: #f7f8f9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--pd-bg);
            color: var(--pd-ink);
        }

        .pd-wrap { max-width: 480px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }

        .pd-card {
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .pd-image-wrap { width: 100%; aspect-ratio: 4 / 3; background: var(--pd-bg); display: flex; align-items: center; justify-content: center; position: relative; }
        .pd-image-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pd-image-placeholder { color: var(--pd-ink-faint); font-size: 0.8rem; }

        .pd-image-nav {
            position: absolute; top: 50%; transform: translateY(-50%); width: 2.25rem; height: 2.25rem;
            display: flex; align-items: center; justify-content: center; border-radius: 999px; border: none; cursor: pointer;
            background: rgba(255,255,255,0.9); box-shadow: 0 6px 16px -6px rgba(0,0,0,0.3); color: var(--pd-ink);
        }
        .pd-image-nav.prev { left: 0.6rem; }
        .pd-image-nav.next { right: 0.6rem; }
        .pd-image-nav svg { width: 1.1rem; height: 1.1rem; }
        .pd-image-counter {
            position: absolute; bottom: 0.6rem; right: 0.6rem; background: rgba(0,0,0,0.55); color: #fff;
            font-size: 0.68rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 999px;
        }

        .pd-body { padding: 1.25rem; }

        .pd-ref-chip {
            display: inline-flex; font-family: 'SF Mono', Consolas, monospace; font-size: 0.75rem;
            font-weight: 600; color: var(--pd-primary); background: var(--pd-primary-soft);
            padding: 0.3rem 0.6rem; border-radius: 7px; margin-bottom: 0.6rem;
        }
        .pd-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.4rem; }
        .pd-taxo { font-size: 0.8rem; color: var(--pd-ink-faint); margin: 0 0 0.75rem; }
        .pd-description { font-size: 0.85rem; line-height: 1.5; color: var(--pd-ink); margin: 0 0 0.9rem; }
        .pd-price { font-size: 1.4rem; font-weight: 700; }
        .pd-price .unit { font-size: 0.75rem; font-weight: 500; color: var(--pd-ink-faint); }
        .pd-no-price { font-size: 0.85rem; color: var(--pd-ink-faint); font-style: italic; }

        .pd-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--pd-line); }
        .pd-spec-item { font-size: 0.78rem; }
        .pd-spec-label { color: var(--pd-ink-faint); display: block; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.1rem; }

        .pd-section-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--pd-ink-faint); margin: 0 0 0.75rem; }

        .pd-field { margin-bottom: 0.9rem; }
        .pd-field label { display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem; }
        .pd-field input {
            width: 100%; padding: 0.65rem 0.8rem; font-size: 0.9rem;
            border: 1px solid var(--pd-line); border-radius: 8px; font-family: inherit;
        }
        .pd-field input:focus { outline: none; border-color: var(--pd-primary); }

        .pd-total-row {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 0.9rem; margin-top: 0.4rem; border-top: 1px dashed var(--pd-line);
            font-size: 0.9rem; font-weight: 700;
        }
        .pd-total-row span.amt { font-size: 1.1rem; }

        .pd-btn-row { display: flex; gap: 0.6rem; margin-top: 1.1rem; }
        .pd-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
            padding: 0.75rem 1rem; border-radius: 9px; font-size: 0.85rem; font-weight: 700;
            border: none; cursor: pointer; text-decoration: none;
        }
        .pd-btn svg { width: 1rem; height: 1rem; }
        .pd-btn.primary { background: var(--pd-primary); color: #fff; }
        .pd-btn.primary:hover { opacity: 0.92; }
        .pd-btn.secondary { background: var(--pd-bg); color: var(--pd-ink); border: 1px solid var(--pd-line); }
        .pd-btn.secondary:hover { background: var(--pd-line); }

        .pd-note { font-size: 0.72rem; color: var(--pd-ink-faint); text-align: center; margin-top: 0.75rem; }
        .pd-footer { text-align: center; font-size: 0.72rem; color: var(--pd-ink-faint); margin-top: 1.5rem; }

        #pdPrintSheet { display: none; }

        @media print {
            body * { visibility: hidden; }
            #pdPrintSheet, #pdPrintSheet * { visibility: visible; }
            #pdPrintSheet { display: block; position: absolute; top: 0; left: 0; width: 100%; padding: 1.5rem; }
        }
    </style>
</head>
<body>

@php
    // Same resolution logic as your admin gallery: uploads use image_path via
    // storage/, links use image_url (with Google Drive URLs converted to a
    // direct thumbnail link).
    $convertImageUrl = function ($url) {
        if (empty($url)) {
            return null;
        }
        if (preg_match('/drive\.google\.com\/uc\?.*id=([^&]+)/', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }
        if (preg_match('#drive\.google\.com/file/d/([^/]+)#', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }
        if (preg_match('/drive\.google\.com\/open\?id=([^&]+)/', $url, $matches)) {
            return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
        }
        return trim($url);
    };

    $pdImages = $product->images->map(function ($image) use ($convertImageUrl) {
        return [
            'url' => $image->image_type === 'upload'
                ? asset('storage/' . $image->image_path)
                : $convertImageUrl($image->image_url),
        ];
    })->filter(fn ($img) => !empty($img['url']))->values();

    $pdRef = $product->sku ?: ('PID-' . $product->product_id);
@endphp

<div class="pd-wrap">

    <div class="pd-card">
        <div class="pd-image-wrap">
            @if($pdImages->count())
                <img id="pdGalleryImg" src="{{ $pdImages[0]['url'] }}" alt="{{ $product->item_name }}">
                @if($pdImages->count() > 1)
                    <button type="button" class="pd-image-nav prev" onclick="pdPrevImage()" aria-label="Previous image">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <button type="button" class="pd-image-nav next" onclick="pdNextImage()" aria-label="Next image">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <span class="pd-image-counter" id="pdGalleryCounter">1 / {{ $pdImages->count() }}</span>
                @endif
            @else
                <span class="pd-image-placeholder">No image available</span>
            @endif
        </div>

        <div class="pd-body">
            <span class="pd-ref-chip">{{ $pdRef }}</span>
            <h1 class="pd-title">{{ $product->item_name }}</h1>
            <p class="pd-taxo">
                {{ collect([$product->category->name ?? null, $product->subCategory->name ?? null, $product->collection->name ?? null])->filter()->implode(' · ') ?: 'Uncategorized' }}
            </p>

            @if($product->description)
                <p class="pd-description">{{ $product->description }}</p>
            @endif

            @if(!is_null($product->price ?? null))
                <div class="pd-price">
                    ${{ number_format($product->price, 2) }} <span class="unit">/ unit</span>
                </div>
            @else
                <div class="pd-no-price">Price available upon request</div>
            @endif

            @if($product->product_height || $product->product_width || $product->product_length)
                <div class="pd-specs">
                    @if($product->product_height)
                        <div class="pd-spec-item"><span class="pd-spec-label">Height</span>{{ $product->product_height }} cm</div>
                    @endif
                    @if($product->product_width)
                        <div class="pd-spec-item"><span class="pd-spec-label">Width</span>{{ $product->product_width }} cm</div>
                    @endif
                    @if($product->product_length)
                        <div class="pd-spec-item"><span class="pd-spec-label">Length</span>{{ $product->product_length }} cm</div>
                    @endif
                    @if($product->product_depth)
                        <div class="pd-spec-item"><span class="pd-spec-label">Depth</span>{{ $product->product_depth }} cm</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(!is_null($product->price ?? null))
    <div class="pd-card">
        <div class="pd-body">
            <p class="pd-section-title">Request a Quotation</p>

            <div class="pd-field">
                <label for="pdCustomerName">Customer Name</label>
                <input type="text" id="pdCustomerName" placeholder="e.g. Juan Dela Cruz" />
            </div>

            <div class="pd-field">
                <label for="pdQuantity">Quantity</label>
                <input type="number" id="pdQuantity" value="1" min="1" inputmode="numeric" />
            </div>

            <div class="pd-total-row">
                <span>Estimated Total</span>
                <span class="amt" id="pdTotalAmt">$0.00</span>
            </div>

            <div class="pd-btn-row">
                <button type="button" class="pd-btn secondary" onclick="pdPrintQuote()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
                    Print
                </button>
                <button type="button" class="pd-btn primary" onclick="pdDownloadQuotation()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Download PDF
                </button>
            </div>

            <p class="pd-note">Quotation is valid for 30 days from the date issued.</p>
        </div>
    </div>
    @endif

    <p class="pd-footer">Scanned from product tag &middot; {{ $pdRef }}</p>

</div>

{{-- Hidden sheet used only when printing --}}
<div id="pdPrintSheet">
    <h2 style="margin:0 0 4px;">{{ $product->item_name }}</h2>
    <p style="font-family:monospace; color:#666; margin:0 0 16px;">{{ $pdRef }}</p>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <tr><td style="padding:6px 0; color:#666;">Customer</td><td id="pdPrintCustomer" style="padding:6px 0; text-align:right;"></td></tr>
        <tr><td style="padding:6px 0; color:#666;">Quantity</td><td id="pdPrintQty" style="padding:6px 0; text-align:right;"></td></tr>
        <tr><td style="padding:6px 0; color:#666;">Unit Price</td><td id="pdPrintUnit" style="padding:6px 0; text-align:right;"></td></tr>
        <tr style="border-top:2px solid #000;"><td style="padding:10px 0; font-weight:bold;">Total</td><td id="pdPrintTotal" style="padding:10px 0; text-align:right; font-weight:bold;"></td></tr>
    </table>
</div>

@if($pdImages->count() > 1)
<script>
    const pdGalleryImages = @json($pdImages->pluck('url'));
    let pdCurrentImage = 0;
    const pdGalleryImg = document.getElementById('pdGalleryImg');
    const pdGalleryCounter = document.getElementById('pdGalleryCounter');

    function pdUpdateGallery() {
        pdGalleryImg.src = pdGalleryImages[pdCurrentImage];
        pdGalleryCounter.textContent = (pdCurrentImage + 1) + ' / ' + pdGalleryImages.length;
    }
    function pdNextImage() {
        pdCurrentImage = (pdCurrentImage + 1) % pdGalleryImages.length;
        pdUpdateGallery();
    }
    function pdPrevImage() {
        pdCurrentImage = (pdCurrentImage - 1 + pdGalleryImages.length) % pdGalleryImages.length;
        pdUpdateGallery();
    }
</script>
@endif

@if(!is_null($product->price ?? null))
<script>
    const PD_UNIT_PRICE = {{ (float) $product->price }};
    const PD_DOWNLOAD_URL = "{{ route('mi_app.quotation.download', $product->product_id) }}";
    const PD_PRINT_URL = "{{ route('mi_app.quotation.print', $product->product_id) }}";

    const pdQtyInput = document.getElementById('pdQuantity');
    const pdTotalAmt = document.getElementById('pdTotalAmt');

    function pdFormatCurrency(n) {
        return '$' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function pdRecalcTotal() {
        const qty = Math.max(1, parseInt(pdQtyInput.value || '1', 10));
        pdTotalAmt.textContent = pdFormatCurrency(PD_UNIT_PRICE * qty);
    }
    pdQtyInput.addEventListener('input', pdRecalcTotal);
    pdRecalcTotal();

    function pdGetFormValues() {
        const name = document.getElementById('pdCustomerName').value.trim();
        const qty = Math.max(1, parseInt(pdQtyInput.value || '1', 10));
        return { name, qty };
    }
    function pdPrintQuote() {

const { name, qty } = pdGetFormValues();

if (!name) {
    alert('Please enter the customer name before printing.');
    document.getElementById('pdCustomerName').focus();
    return;
}

const form = document.createElement('form');

form.method = 'POST';
form.action = PD_PRINT_URL;
form.target = '_blank';

const csrf = document.createElement('input');
csrf.type = 'hidden';
csrf.name = '_token';
csrf.value = '{{ csrf_token() }}';
form.appendChild(csrf);

const nameInput = document.createElement('input');
nameInput.type = 'hidden';
nameInput.name = 'customer_name';
nameInput.value = name;
form.appendChild(nameInput);

const qtyInput = document.createElement('input');
qtyInput.type = 'hidden';
qtyInput.name = 'quantity';
qtyInput.value = qty;
form.appendChild(qtyInput);

document.body.appendChild(form);

form.submit();

form.remove();
}

    function pdDownloadQuotation() {
        const { name, qty } = pdGetFormValues();
        if (!name) {
            alert('Please enter the customer name before downloading.');
            document.getElementById('pdCustomerName').focus();
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = PD_DOWNLOAD_URL;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'customer_name';
        nameInput.value = name;
        form.appendChild(nameInput);

        const qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'quantity';
        qtyInput.value = qty;
        form.appendChild(qtyInput);

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }
</script>
@endif

</body>
</html>
