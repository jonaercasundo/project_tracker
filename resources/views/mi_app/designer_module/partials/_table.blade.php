<style>
    .tx-products-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
    .tx-products-table thead {
        background: var(--tx-bg);
        border-bottom: 1px solid var(--tx-line);
    }
    .tx-products-table thead th {
        padding: 0.85rem 1.25rem;
        font-family: var(--tx-font-display);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--tx-ink-faint);
        white-space: nowrap;
    }
    .tx-products-table thead th.actions-col { text-align: center; width: 1%; }
    .tx-products-table tbody tr { border-bottom: 1px solid var(--tx-line); transition: background-color .12s ease; }
    .tx-products-table tbody tr:last-child { border-bottom: none; }
    .tx-products-table tbody tr:hover { background: var(--tx-bg); }
    .tx-products-table td { padding: 1rem 1.25rem; vertical-align: middle; color: var(--tx-ink); }
    .tx-row-index { font-weight: 600; color: var(--tx-ink-soft); }
    .tx-row-item { font-weight: 600; color: var(--tx-ink); }
    .tx-row-empty { color: var(--tx-ink-faint); }

    .tx-sku-chip {
        display: inline-flex; align-items: center; font-family: var(--tx-font-mono); font-size: 0.75rem;
        font-weight: 600; color: var(--tx-primary); background: var(--tx-primary-soft);
        padding: 0.3rem 0.6rem; border-radius: 7px; white-space: nowrap;
    }
    .tx-sku-chip.empty { color: var(--tx-ink-faint); background: var(--tx-bg); }

    .tx-taxo-cell { display: flex; align-items: center; gap: 0.5rem; }
    .tx-taxo-dot { width: 0.45rem; height: 0.45rem; border-radius: 999px; flex-shrink: 0; }
    .tx-taxo-dot.cat { background: #2F5D50; }
    .tx-taxo-dot.sub { background: #35618C; }
    .tx-taxo-dot.col { background: #C7703C; }

    .tx-row-actions { display: flex; align-items: center; justify-content: center; gap: 0.4rem; }
    .tx-action-btn {
        display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.8rem;
        border-radius: 8px; font-size: 0.78rem; font-weight: 600; text-decoration: none;
        border: none; cursor: pointer; transition: all .15s ease; font-family: var(--tx-font-body);
    }
    .tx-action-btn.view { background: var(--tx-primary-soft); color: var(--tx-primary); }
    .tx-action-btn.view:hover { background: var(--tx-primary); color: var(--tx-primary-ink); }
    .tx-action-btn.edit { background: var(--tx-accent-soft); color: var(--tx-accent); }
    .tx-action-btn.edit:hover { background: var(--tx-accent); color: #fff; }
    .tx-action-btn.qr { background: #E4EEF5; color: #2C6E8C; }
    .tx-action-btn.qr:hover { background: #2C6E8C; color: #fff; }
    .tx-action-btn.archive { background: #F5E4E0; color: var(--tx-danger); }
    .tx-action-btn.archive:hover { background: var(--tx-danger); color: #fff; }
    .tx-action-btn svg { width: 0.9rem; height: 0.9rem; }

    .tx-empty-row td {
        padding: 3.5rem 1.25rem;
        text-align: center;
        color: var(--tx-ink-faint);
        font-size: 0.875rem;
    }

    /* QR Modal */
    .tx-qr-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(20, 20, 20, 0.55);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .tx-qr-modal-overlay.open { display: flex; }
    .tx-qr-modal {
        background: #fff;
        border-radius: 14px;
        padding: 1.75rem;
        width: 100%;
        max-width: 320px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        position: relative;
        font-family: var(--tx-font-body);
    }
    .tx-qr-modal-close {
        position: absolute;
        top: 0.65rem;
        right: 0.65rem;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--tx-ink-faint);
        width: 1.75rem;
        height: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
    }
    .tx-qr-modal-close:hover { background: var(--tx-bg); color: var(--tx-ink); }
    .tx-qr-modal-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--tx-ink);
        margin: 0 0 0.15rem;
    }
    .tx-qr-modal-sub {
        font-size: 0.75rem;
        color: var(--tx-ink-faint);
        margin: 0 0 1rem;
        font-family: var(--tx-font-mono);
    }
    .tx-qr-canvas-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: var(--tx-bg);
        border-radius: 10px;
        margin-bottom: 1rem;
    }
    .tx-qr-btn-row {
        display: flex;
        gap: 0.5rem;
    }
    .tx-qr-download-btn,
    .tx-qr-print-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        flex: 1;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
    }
    .tx-qr-download-btn { background: var(--tx-primary); color: var(--tx-primary-ink); }
    .tx-qr-download-btn:hover { opacity: 0.9; }
    .tx-qr-print-btn { background: var(--tx-bg); color: var(--tx-ink); border: 1px solid var(--tx-line); }
    .tx-qr-print-btn:hover { background: var(--tx-line); }
    .tx-qr-download-btn svg,
    .tx-qr-print-btn svg { width: 0.9rem; height: 0.9rem; }

    .tx-empty-row td {
        padding: 3.5rem 1.25rem;
        text-align: center;
        color: var(--tx-ink-faint);
        font-size: 0.875rem;
    }
</style>

<table class="tx-products-table">
    <thead>
        <tr>
            <th>Item No.</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Sub Category</th>
            <th>Collection</th>
            <th>Item Name</th>
            <th class="actions-col">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($products as $product)
            <tr>
                <td class="tx-row-index">{{ $loop->iteration }}</td>

                <td>
                    <span class="tx-sku-chip {{ $product->sku ?? null ? '' : 'empty' }}">
                        {{ $product->sku ?? '—' }}
                    </span>
                </td>

                <td>
                    <span class="tx-taxo-cell {{ $product->category->name ?? null ? '' : 'tx-row-empty' }}">
                        @if($product->category->name ?? null)
                            <span class="tx-taxo-dot cat"></span>
                        @endif
                        {{ $product->category->name ?? '—' }}
                    </span>
                </td>

                <td>
                    <span class="tx-taxo-cell {{ $product->subCategory->name ?? null ? '' : 'tx-row-empty' }}">
                        @if($product->subCategory->name ?? null)
                            <span class="tx-taxo-dot sub"></span>
                        @endif
                        {{ $product->subCategory->name ?? '—' }}
                    </span>
                </td>

                <td>
                    <span class="tx-taxo-cell {{ $product->collection->name ?? null ? '' : 'tx-row-empty' }}">
                        @if($product->collection->name ?? null)
                            <span class="tx-taxo-dot col"></span>
                        @endif
                        {{ $product->collection->name ?? '—' }}
                    </span>
                </td>

                <td class="tx-row-item">{{ $product->item_name }}</td>

                <td>
                    <div class="tx-row-actions">
                        <a href="{{ route('mi_app.show', ['product' => $product->product_id]) }}" class="tx-action-btn view">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            View
                        </a>

                        <a href="{{ route('mi_app.edit', $product->product_id) }}" class="tx-action-btn edit">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                            Edit
                        </a>

                        <button
                            type="button"
                            class="tx-action-btn qr"
                            data-qr-url="{{ route('public.product.show', ['product' => $product->product_id]) }}"
                            data-qr-name="{{ $product->item_name }}"
                            data-qr-sub="{{ $product->sku ?? $product->product_id }}"
                            onclick="txOpenQrModal(this.dataset.qrUrl, this.dataset.qrName, this.dataset.qrSub)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h6v6h-6v-6zM3.75 13.5h6v6h-6v-6zM13.5 4.5h6v6h-6v-6zM13.5 13.5h2.25v2.25H13.5v-2.25zM17.25 13.5h2.25v2.25h-2.25v-2.25zM13.5 17.25h2.25v2.25H13.5v-2.25zM17.25 17.25h2.25v2.25h-2.25v-2.25z" /></svg>
                            QR
                        </button>

                        <form action="{{ route('mi_app.destroy', $product->product_id) }}" method="POST" onsubmit="return confirm('Delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tx-action-btn archive">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V8.25M6.375 7.5h11.25M9.75 6.75V4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V6.75" /></svg>
                                Archive
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="tx-empty-row">
                <td colspan="7">No products found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- QR Modal (shared by all rows) -->
<div class="tx-qr-modal-overlay" id="txQrModalOverlay" onclick="if(event.target === this) txCloseQrModal()">
    <div class="tx-qr-modal">
        <button type="button" class="tx-qr-modal-close" onclick="txCloseQrModal()" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <p class="tx-qr-modal-title" id="txQrModalTitle">Item Name</p>
        <p class="tx-qr-modal-sub" id="txQrModalSub">SKU</p>
        <div class="tx-qr-canvas-wrap" id="txQrCanvasWrap"></div>
        <div class="tx-qr-btn-row">
            <button type="button" class="tx-qr-download-btn" onclick="txDownloadQr()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Download
            </button>
            <button type="button" class="tx-qr-print-btn" onclick="txPrintQr()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
                Print
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            let txQrCurrentFileName = 'qr-code';
            let txQrCurrentName = '';
            let txQrCurrentSub = '';

            function txOpenQrModal(url, itemName, skuOrId) {
                const overlay = document.getElementById('txQrModalOverlay');
                const wrap = document.getElementById('txQrCanvasWrap');
                const title = document.getElementById('txQrModalTitle');
                const sub = document.getElementById('txQrModalSub');

                title.textContent = itemName || 'Product';
                sub.textContent = skuOrId || '';
                txQrCurrentName = itemName || 'Product';
                txQrCurrentSub = skuOrId || '';
                txQrCurrentFileName = 'qr-' + (skuOrId || 'product').toString().replace(/[^a-z0-9\-_]+/gi, '-');

                wrap.innerHTML = '';
                new QRCode(wrap, {
                    text: url,
                    width: 200,
                    height: 200,
                    colorDark: '#141414',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });

                overlay.classList.add('open');
            }

            function txCloseQrModal() {
                document.getElementById('txQrModalOverlay').classList.remove('open');
            }

            function txGetQrDataUrl() {
                const wrap = document.getElementById('txQrCanvasWrap');
                const canvas = wrap.querySelector('canvas');
                return canvas ? canvas.toDataURL('image/png') : null;
            }

            function txDownloadQr() {
                const dataUrl = txGetQrDataUrl();
                if (!dataUrl) return;
                const link = document.createElement('a');
                link.download = txQrCurrentFileName + '.png';
                link.href = dataUrl;
                link.click();
            }

            function txPrintQr() {
                const dataUrl = txGetQrDataUrl();
                if (!dataUrl) return;

                const printWindow = window.open('', '_blank', 'width=420,height=560');
                if (!printWindow) {
                    alert('Please allow pop-ups to print the QR code.');
                    return;
                }

                const safeName = txQrCurrentName.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const safeSub = txQrCurrentSub.replace(/</g, '&lt;').replace(/>/g, '&gt;');

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Print QR - ${safeName}</title>
                        <style>
                            @page { margin: 0.5in; }
                            body {
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;
                                text-align: center;
                                margin: 0;
                                padding: 2rem;
                            }
                            img { width: 260px; height: 260px; }
                            h1 { font-size: 1rem; margin: 1rem 0 0.15rem; }
                            p { font-size: 0.8rem; color: #666; margin: 0; font-family: monospace; }
                        </style>
                    </head>
                    <body>
                        <img src="${dataUrl}" alt="QR code" />
                        <h1>${safeName}</h1>
                        <p>${safeSub}</p>
                    </body>
                    </html>
                `);
                printWindow.document.close();

                printWindow.onload = function () {
                    printWindow.focus();
                    printWindow.print();
                };
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') txCloseQrModal();
            });
        </script>
    @endpush
@endonce