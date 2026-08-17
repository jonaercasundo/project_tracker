@extends('layouts.app') {{-- adjust to your actual layout --}}

@section('content')
<style>
    .pd-wrap { max-width: 480px; margin: 0 auto; padding: 1.25rem 1rem 3rem; font-family: var(--tx-font-body, sans-serif); }

    .pd-card {
        background: #fff;
        border: 1px solid var(--tx-line, #e5e5e5);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .pd-image-wrap { width: 100%; aspect-ratio: 4 / 3; background: var(--tx-bg, #f5f5f5); display: flex; align-items: center; justify-content: center; }
    .pd-image-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .pd-body { padding: 1.25rem; }

    .pd-sku-chip {
        display: inline-flex; font-family: var(--tx-font-mono, monospace); font-size: 0.75rem;
        font-weight: 600; color: var(--tx-primary, #2C6E8C); background: var(--tx-primary-soft, #E4EEF5);
        padding: 0.3rem 0.6rem; border-radius: 7px; margin-bottom: 0.6rem;
    }
    .pd-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.4rem; color: var(--tx-ink, #1a1a1a); }
    .pd-taxo { font-size: 0.8rem; color: var(--tx-ink-faint, #888); margin-bottom: 0.75rem; }
    .pd-price { font-size: 1.4rem; font-weight: 700; color: var(--tx-ink, #1a1a1a); }
    .pd-price .unit { font-size: 0.75rem; font-weight: 500; color: var(--tx-ink-faint, #888); }

    .pd-section-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--tx-ink-faint, #888); margin: 0 0 0.75rem; }

    .pd-field { margin-bottom: 0.9rem; }
    .pd-field label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--tx-ink, #1a1a1a); margin-bottom: 0.3rem; }
    .pd-field input {
        width: 100%; box-sizing: border-box; padding: 0.65rem 0.8rem; font-size: 0.9rem;
        border: 1px solid var(--tx-line, #ddd); border-radius: 8px; font-family: inherit;
    }
    .pd-field input:focus { outline: none; border-color: var(--tx-primary, #2C6E8C); }

    .pd-total-row {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 0.9rem; margin-top: 0.4rem; border-top: 1px dashed var(--tx-line, #ddd);
        font-size: 0.9rem; font-weight: 700; color: var(--tx-ink, #1a1a1a);
    }
    .pd-total-row span.amt { font-size: 1.1rem; }

    .pd-btn-row { display: flex; gap: 0.6rem; margin-top: 1.1rem; }
    .pd-btn {
        flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
        padding: 0.75rem 1rem; border-radius: 9px; font-size: 0.85rem; font-weight: 700;
        border: none; cursor: pointer; text-decoration: none;
    }
    .pd-btn svg { width: 1rem; height: 1rem; }
    .pd-btn.primary { background: var(--tx-primary, #2C6E8C); color: #fff; }
    .pd-btn.primary:hover { opacity: 0.92; }
    .pd-btn.secondary { background: var(--tx-bg, #f2f2f2); color: var(--tx-ink, #1a1a1a); border: 1px solid var(--tx-line, #ddd); }
    .pd-btn.secondary:hover { background: var(--tx-line, #e5e5e5); }

    .pd-note { font-size: 0.72rem; color: var(--tx-ink-faint, #999); text-align: center; margin-top: 0.75rem; }

    /* Print-only quote sheet, hidden on screen */
    #pdPrintSheet { display: none; }

    @media print {
        body * { visibility: hidden; }
        #pdPrintSheet, #pdPrintSheet * { visibility: visible; }
        #pdPrintSheet { display: block; position: absolute; top: 0; left: 0; width: 100%; padding: 1.5rem; }
        .pd-btn-row, .pd-note { display: none !important; }
    }
</style>

<div class="pd-wrap">

    <div class="pd-card">
        @if($product->image_path ?? false)
            <div class="pd-image-wrap">
                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->item_name }}">
            </div>
        @endif

        <div class="pd-body">
            <span class="pd-sku-chip">{{ $product->sku ?? '—' }}</span>
            <h1 class="pd-title">{{ $product->item_name }}</h1>
            <p class="pd-taxo">
                {{ collect([$product->category->name ?? null, $product->subCategory->name ?? null, $product->collection->name ?? null])->filter()->implode(' · ') ?: 'Uncategorized' }}
            </p>

            @if(!is_null($product->price ?? null))
                <div class="pd-price">
                    ₱{{ number_format($product->price, 2) }} <span class="unit">/ unit</span>
                </div>
            @endif
        </div>
    </div>

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
                <span class="amt" id="pdTotalAmt">₱0.00</span>
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

</div>

{{-- Hidden sheet used only when printing --}}
<div id="pdPrintSheet">
    <h2 style="margin:0 0 4px;">{{ $product->item_name }}</h2>
    <p style="font-family:monospace; color:#666; margin:0 0 16px;">{{ $product->sku ?? '—' }}</p>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <tr><td style="padding:6px 0; color:#666;">Customer</td><td id="pdPrintCustomer" style="padding:6px 0; text-align:right;"></td></tr>
        <tr><td style="padding:6px 0; color:#666;">Quantity</td><td id="pdPrintQty" style="padding:6px 0; text-align:right;"></td></tr>
        <tr><td style="padding:6px 0; color:#666;">Unit Price</td><td id="pdPrintUnit" style="padding:6px 0; text-align:right;"></td></tr>
        <tr style="border-top:2px solid #000;"><td style="padding:10px 0; font-weight:bold;">Total</td><td id="pdPrintTotal" style="padding:10px 0; text-align:right; font-weight:bold;"></td></tr>
    </table>
</div>

@push('scripts')
<script>
    const PD_UNIT_PRICE = {{ (float) ($product->price ?? 0) }};
    const PD_DOWNLOAD_URL = "{{ route('mi_app.quotation.download', $product->product_id) }}";

    const pdQtyInput = document.getElementById('pdQuantity');
    const pdTotalAmt = document.getElementById('pdTotalAmt');

    function pdFormatCurrency(n) {
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        document.getElementById('pdPrintCustomer').textContent = name;
        document.getElementById('pdPrintQty').textContent = qty;
        document.getElementById('pdPrintUnit').textContent = pdFormatCurrency(PD_UNIT_PRICE);
        document.getElementById('pdPrintTotal').textContent = pdFormatCurrency(PD_UNIT_PRICE * qty);
        window.print();
    }

    function pdDownloadQuotation() {
        const { name, qty } = pdGetFormValues();
        if (!name) {
            alert('Please enter the customer name before downloading.');
            document.getElementById('pdCustomerName').focus();
            return;
        }

        // Submit as a real POST so the browser downloads the PDF response directly.
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
@endpush
@endsection
