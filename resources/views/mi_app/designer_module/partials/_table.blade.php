<style>
    /* =========================================================
       PRODUCTS TABLE
       ========================================================= */

    .tx-products-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid var(--tx-line);
        border-radius: 12px;
        background: #fff;
    }

    .tx-products-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        font-size: 0.875rem;
        text-align: left;
    }

    /* ---------------------------------------------------------
       TABLE HEADER
       --------------------------------------------------------- */

    .tx-products-table thead {
        background: var(--tx-bg);
        border-bottom: 1px solid var(--tx-line);
    }

    .tx-products-table thead th {
        padding: 0.8rem 1rem;
        font-family: var(--tx-font-display);
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--tx-ink-faint);
        white-space: nowrap;
    }

    .tx-products-table thead th.actions-col {
        width: 1%;
        min-width: 190px;
        text-align: center;
    }

    /* ---------------------------------------------------------
       TABLE BODY
       --------------------------------------------------------- */

    .tx-products-table tbody tr {
        border-bottom: 1px solid var(--tx-line);
        transition:
            background-color .15s ease,
            box-shadow .15s ease;
    }

    .tx-products-table tbody tr:last-child {
        border-bottom: none;
    }

    .tx-products-table tbody tr:hover {
        background: var(--tx-bg);
    }

    .tx-products-table td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        color: var(--tx-ink);
    }

    .tx-row-index {
        width: 55px;
        font-family: var(--tx-font-mono);
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--tx-ink-soft);
    }

    .tx-row-item {
        min-width: 190px;
        font-weight: 650;
        color: var(--tx-ink);
    }

    .tx-row-empty {
        color: var(--tx-ink-faint);
    }

    /* ---------------------------------------------------------
       SKU
       --------------------------------------------------------- */

    .tx-sku-chip {
        display: inline-flex;
        align-items: center;
        max-width: 150px;
        padding: 0.3rem 0.6rem;

        overflow: hidden;
        text-overflow: ellipsis;

        border-radius: 7px;

        background: var(--tx-primary-soft);
        color: var(--tx-primary);

        font-family: var(--tx-font-mono);
        font-size: 0.7rem;
        font-weight: 700;

        white-space: nowrap;
    }

    .tx-sku-chip.empty {
        background: var(--tx-bg);
        color: var(--tx-ink-faint);
        font-family: var(--tx-font-body);
    }

    /* ---------------------------------------------------------
       CATEGORY / TAXONOMY
       --------------------------------------------------------- */

    .tx-taxo-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        max-width: 170px;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        color: var(--tx-ink-soft);
        font-size: 0.8rem;
    }

    .tx-taxo-dot {
        width: 0.42rem;
        height: 0.42rem;
        flex: 0 0 0.42rem;
        border-radius: 999px;
    }

    .tx-taxo-dot.cat {
        background: #2F5D50;
    }

    .tx-taxo-dot.sub {
        background: #35618C;
    }

    .tx-taxo-dot.col {
        background: #C7703C;
    }

    /* ---------------------------------------------------------
       ACTIONS
       --------------------------------------------------------- */

    .tx-row-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
    }

    .tx-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;

        min-height: 32px;
        padding: 0.38rem 0.65rem;

        border: 1px solid transparent;
        border-radius: 7px;

        font-family: var(--tx-font-body);
        font-size: 0.72rem;
        font-weight: 650;

        text-decoration: none;
        cursor: pointer;

        transition:
            background-color .15s ease,
            color .15s ease,
            border-color .15s ease,
            transform .15s ease;
    }

    .tx-action-btn:hover {
        transform: translateY(-1px);
    }

    .tx-action-btn:active {
        transform: translateY(0);
    }

    .tx-action-btn svg {
        width: 0.85rem;
        height: 0.85rem;
        flex-shrink: 0;
    }

    /* View */
    .tx-action-btn.view {
        background: var(--tx-primary-soft);
        color: var(--tx-primary);
    }

    .tx-action-btn.view:hover {
        background: var(--tx-primary);
        color: var(--tx-primary-ink);
    }

    /* Edit */
    .tx-action-btn.edit {
        background: var(--tx-accent-soft);
        color: var(--tx-accent);
    }

    .tx-action-btn.edit:hover {
        background: var(--tx-accent);
        color: #fff;
    }

    /* QR */
    .tx-action-btn.qr {
        background: #E4EEF5;
        color: #2C6E8C;
    }

    .tx-action-btn.qr:hover {
        background: #2C6E8C;
        color: #fff;
    }

    /* Archive */
    .tx-action-btn.archive {
        background: #F5E4E0;
        color: var(--tx-danger);
    }

    .tx-action-btn.archive:hover {
        background: var(--tx-danger);
        color: #fff;
    }

    .tx-action-form {
        margin: 0;
        padding: 0;
    }

    /* ---------------------------------------------------------
       EMPTY STATE
       --------------------------------------------------------- */

    .tx-empty-row td {
        padding: 4rem 1.25rem;
        text-align: center;
        color: var(--tx-ink-faint);
        font-size: 0.875rem;
    }

    .tx-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .tx-empty-icon {
        width: 44px;
        height: 44px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        margin-bottom: 0.25rem;

        border-radius: 12px;
        background: var(--tx-bg);
        color: var(--tx-ink-faint);
    }

    .tx-empty-icon svg {
        width: 21px;
        height: 21px;
    }

    .tx-empty-title {
        color: var(--tx-ink-soft);
        font-weight: 650;
    }

    .tx-empty-sub {
        font-size: 0.75rem;
        color: var(--tx-ink-faint);
    }


    /* =========================================================
       QR MODAL
       ========================================================= */

    .tx-qr-modal-overlay {
        display: none;

        position: fixed;
        inset: 0;
        z-index: 9999;

        align-items: center;
        justify-content: center;

        padding: 1rem;

        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(3px);
    }

    .tx-qr-modal-overlay.open {
        display: flex;
    }

    .tx-qr-modal {
        position: relative;

        width: 100%;
        max-width: 360px;

        padding: 1.5rem;

        border: 1px solid rgba(255,255,255,.4);
        border-radius: 16px;

        background: #fff;

        text-align: center;

        box-shadow:
            0 25px 60px rgba(0,0,0,.25);

        font-family: var(--tx-font-body);

        animation: txQrModalIn .16s ease-out;
    }

    @keyframes txQrModalIn {
        from {
            opacity: 0;
            transform: translateY(8px) scale(.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .tx-qr-modal-close {
        position: absolute;
        top: 0.7rem;
        right: 0.7rem;

        width: 32px;
        height: 32px;

        display: flex;
        align-items: center;
        justify-content: center;

        border: none;
        border-radius: 8px;

        background: transparent;
        color: var(--tx-ink-faint);

        cursor: pointer;

        transition: all .15s ease;
    }

    .tx-qr-modal-close:hover {
        background: var(--tx-bg);
        color: var(--tx-ink);
    }

    .tx-qr-modal-title {
        margin: 0 2rem 0.2rem;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        color: var(--tx-ink);

        font-family: var(--tx-font-display);
        font-size: 0.95rem;
        font-weight: 700;
    }

    .tx-qr-modal-sub {
        margin: 0 0 1rem;

        color: var(--tx-ink-faint);

        font-family: var(--tx-font-mono);
        font-size: 0.7rem;
    }

    /* ---------------------------------------------------------
       QR AREA
       --------------------------------------------------------- */

    .tx-qr-canvas-wrap {
        display: flex;
        align-items: center;
        justify-content: center;

        min-height: 230px;

        margin-bottom: 1rem;
        padding: 1rem;

        border: 1px solid var(--tx-line);
        border-radius: 12px;

        background:
            linear-gradient(
                45deg,
                #f8fafc 25%,
                transparent 25%
            ),
            linear-gradient(
                -45deg,
                #f8fafc 25%,
                transparent 25%
            ),
            linear-gradient(
                45deg,
                transparent 75%,
                #f8fafc 75%
            ),
            linear-gradient(
                -45deg,
                transparent 75%,
                #f8fafc 75%
            );

        background-size: 16px 16px;
        background-position:
            0 0,
            0 8px,
            8px -8px,
            -8px 0;
    }

    .tx-qr-canvas-wrap canvas,
    .tx-qr-canvas-wrap img {
        display: block;

        width: 200px;
        height: 200px;

        padding: 8px;

        background: #fff;

        border-radius: 6px;
    }

    /* ---------------------------------------------------------
       QR BUTTONS
       --------------------------------------------------------- */

    .tx-qr-btn-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .tx-qr-download-btn,
    .tx-qr-print-btn {
        min-height: 38px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;

        padding: 0.55rem 0.8rem;

        border-radius: 8px;

        font-family: var(--tx-font-body);
        font-size: 0.78rem;
        font-weight: 650;

        cursor: pointer;

        transition: all .15s ease;
    }

    .tx-qr-download-btn {
        border: 1px solid var(--tx-primary);
        background: var(--tx-primary);
        color: var(--tx-primary-ink);
    }

    .tx-qr-download-btn:hover {
        opacity: .9;
        transform: translateY(-1px);
    }

    .tx-qr-print-btn {
        border: 1px solid var(--tx-line);
        background: var(--tx-bg);
        color: var(--tx-ink);
    }

    .tx-qr-print-btn:hover {
        background: var(--tx-line);
        transform: translateY(-1px);
    }

    .tx-qr-download-btn svg,
    .tx-qr-print-btn svg {
        width: 0.9rem;
        height: 0.9rem;
    }


    /* =========================================================
       MOBILE
       ========================================================= */

    @media (max-width: 768px) {

        .tx-products-wrapper {
            border-radius: 10px;
        }

        .tx-products-table {
            min-width: 900px;
        }

        .tx-row-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 480px) {

        .tx-qr-modal {
            max-width: calc(100vw - 2rem);
            padding: 1.25rem;
        }

        .tx-qr-canvas-wrap {
            min-height: 215px;
        }

        .tx-qr-canvas-wrap canvas,
        .tx-qr-canvas-wrap img {
            width: 180px;
            height: 180px;
        }
    }
</style>


{{-- =========================================================
     PRODUCTS TABLE
     ========================================================= --}}

<div class="tx-products-wrapper">

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

                @php
                    $categoryName = $product->category?->name;
                    $subCategoryName = $product->subCategory?->name;
                    $collectionName = $product->collection?->name;

                    $sku = $product->sku;
                    $itemName = $product->item_name ?: 'Unnamed Product';

                    $productId = $product->product_id;

                    $publicUrl = route(
                        'public.product.show',
                        ['product' => $productId]
                    );
                @endphp

                <tr>

                    {{-- Item Number --}}
                    <td class="tx-row-index">
                        {{ $products->firstItem() + $loop->index }}
                    </td>


                    {{-- SKU --}}
                    <td>

                        <span
                            class="tx-sku-chip {{ $sku ? '' : 'empty' }}"
                            title="{{ $sku ?: 'No SKU assigned' }}"
                        >
                            {{ $sku ?: 'No SKU' }}
                        </span>

                    </td>


                    {{-- Category --}}
                    <td>

                        @if($categoryName)

                            <span
                                class="tx-taxo-cell"
                                title="{{ $categoryName }}"
                            >
                                <span class="tx-taxo-dot cat"></span>
                                {{ $categoryName }}
                            </span>

                        @else

                            <span class="tx-row-empty">—</span>

                        @endif

                    </td>


                    {{-- Sub Category --}}
                    <td>

                        @if($subCategoryName)

                            <span
                                class="tx-taxo-cell"
                                title="{{ $subCategoryName }}"
                            >
                                <span class="tx-taxo-dot sub"></span>
                                {{ $subCategoryName }}
                            </span>

                        @else

                            <span class="tx-row-empty">—</span>

                        @endif

                    </td>


                    {{-- Collection --}}
                    <td>

                        @if($collectionName)

                            <span
                                class="tx-taxo-cell"
                                title="{{ $collectionName }}"
                            >
                                <span class="tx-taxo-dot col"></span>
                                {{ $collectionName }}
                            </span>

                        @else

                            <span class="tx-row-empty">—</span>

                        @endif

                    </td>


                    {{-- Item Name --}}
                    <td
                        class="tx-row-item"
                        title="{{ $itemName }}"
                    >
                        {{ $itemName }}
                    </td>


                    {{-- Actions --}}
                    <td>

                        <div class="tx-row-actions">

                            {{-- View --}}
                            <a
                                href="{{ route('mi_app.show', ['product' => $productId]) }}"
                                class="tx-action-btn view"
                                title="View product"
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
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>

                                <span>View</span>
                            </a>


                            {{-- Edit --}}
                            <a
                                href="{{ route('mi_app.edit', ['product' => $productId]) }}"
                                class="tx-action-btn edit"
                                title="Edit product"
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

                                <span>Edit</span>
                            </a>


                            {{-- QR --}}
                            <button
                                type="button"
                                class="tx-action-btn qr"
                                title="Generate QR code"
                                data-qr-url="{{ $publicUrl }}"
                                data-qr-name="{{ $itemName }}"
                                data-qr-sub="{{ $sku ?: $productId }}"
                                onclick="txOpenQrModal(
                                    this.dataset.qrUrl,
                                    this.dataset.qrName,
                                    this.dataset.qrSub
                                )"
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
                                        d="M3.75 4.5h6v6h-6v-6z
                                           M3.75 13.5h6v6h-6v-6z
                                           M13.5 4.5h6v6h-6v-6z
                                           M13.5 13.5h2.25v2.25H13.5v-2.25z
                                           M17.25 13.5h2.25v2.25h-2.25v-2.25z
                                           M13.5 17.25h2.25v2.25H13.5v-2.25z
                                           M17.25 17.25h2.25v2.25h-2.25v-2.25z"
                                    />
                                </svg>

                                <span>QR</span>
                            </button>


                            {{-- Archive --}}
                            <form
                                action="{{ route('mi_app.destroy', ['product' => $productId]) }}"
                                method="POST"
                                class="tx-action-form"
                                onsubmit="return txConfirmArchive(this)"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="tx-action-btn archive"
                                    title="Archive product"
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
                                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V8.25M6.375 7.5h11.25M9.75 6.75V4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V6.75"
                                        />
                                    </svg>

                                    <span>Archive</span>
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr class="tx-empty-row">

                    <td colspan="7">

                        <div class="tx-empty-state">

                            <span class="tx-empty-icon">
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
                                        d="M20.25 7.5l-8.25-4.5-8.25 4.5m16.5 0v9l-8.25 4.5m8.25-13.5l-8.25 4.5m-8.25-4.5v9l8.25 4.5m0-9v9"
                                    />
                                </svg>
                            </span>

                            <span class="tx-empty-title">
                                No products found
                            </span>

                            <span class="tx-empty-sub">
                                Products matching your current filters will appear here.
                            </span>

                        </div>

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     QR MODAL
     Shared by all products
     ========================================================= --}}

<div
    class="tx-qr-modal-overlay"
    id="txQrModalOverlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="txQrModalTitle"
    onclick="if (event.target === this) txCloseQrModal()"
>

    <div class="tx-qr-modal">

        {{-- Close --}}
        <button
            type="button"
            class="tx-qr-modal-close"
            onclick="txCloseQrModal()"
            aria-label="Close QR code"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                width="16"
                height="16"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>
        </button>


        {{-- Product name --}}
        <p
            class="tx-qr-modal-title"
            id="txQrModalTitle"
        >
            Product
        </p>


        {{-- SKU / Product ID --}}
        <p
            class="tx-qr-modal-sub"
            id="txQrModalSub"
        >
            —
        </p>


        {{-- QR --}}
        <div
            class="tx-qr-canvas-wrap"
            id="txQrCanvasWrap"
        ></div>


        {{-- Actions --}}
        <div class="tx-qr-btn-row">

            <button
                type="button"
                class="tx-qr-download-btn"
                onclick="txDownloadQr()"
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
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                    />
                </svg>

                Download
            </button>


            <button
                type="button"
                class="tx-qr-print-btn"
                onclick="txPrintQr()"
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
                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"
                    />
                </svg>

                Print
            </button>

        </div>

    </div>

</div>


{{-- =========================================================
     QR CODE SCRIPT
     ========================================================= --}}

@once

    @push('scripts')

        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
        ></script>

        <script>
            let txQrCurrentFileName = 'qr-code';
            let txQrCurrentName = 'Product';
            let txQrCurrentSub = '';
            let txQrCurrentUrl = '';

            /**
             * ----------------------------------------------------
             * Open QR Modal
             * ----------------------------------------------------
             */
            function txOpenQrModal(url, itemName, skuOrId) {

                const overlay = document.getElementById('txQrModalOverlay');
                const wrap = document.getElementById('txQrCanvasWrap');
                const title = document.getElementById('txQrModalTitle');
                const sub = document.getElementById('txQrModalSub');

                if (!overlay || !wrap || !title || !sub) {
                    return;
                }

                txQrCurrentUrl = url || '';
                txQrCurrentName = itemName || 'Product';
                txQrCurrentSub = skuOrId || '';

                const safeFilePart = (
                    skuOrId ||
                    itemName ||
                    'product'
                )
                    .toString()
                    .trim()
                    .replace(/[^a-z0-9\-_]+/gi, '-')
                    .replace(/^-+|-+$/g, '')
                    .toLowerCase();

                txQrCurrentFileName = 'qr-' + (
                    safeFilePart || 'product'
                );

                title.textContent = txQrCurrentName;
                sub.textContent = txQrCurrentSub || 'Product QR Code';

                wrap.innerHTML = '';

                if (!txQrCurrentUrl) {

                    wrap.innerHTML = `
                        <div style="
                            padding: 2rem;
                            color: #64748b;
                            font-size: .8rem;
                        ">
                            QR code URL is unavailable.
                        </div>
                    `;

                    overlay.classList.add('open');
                    return;
                }

                if (typeof QRCode === 'undefined') {

                    wrap.innerHTML = `
                        <div style="
                            padding: 2rem;
                            color: #dc2626;
                            font-size: .8rem;
                        ">
                            QR generator could not be loaded.
                        </div>
                    `;

                    overlay.classList.add('open');
                    return;
                }

                new QRCode(wrap, {
                    text: txQrCurrentUrl,
                    width: 200,
                    height: 200,
                    colorDark: '#141414',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });

                overlay.classList.add('open');

                document.body.style.overflow = 'hidden';
            }


            /**
             * ----------------------------------------------------
             * Close QR Modal
             * ----------------------------------------------------
             */
            function txCloseQrModal() {

                const overlay = document.getElementById(
                    'txQrModalOverlay'
                );

                if (!overlay) {
                    return;
                }

                overlay.classList.remove('open');

                document.body.style.overflow = '';
            }


            /**
             * ----------------------------------------------------
             * Get QR Data URL
             * ----------------------------------------------------
             */
            function txGetQrDataUrl() {

                const wrap = document.getElementById(
                    'txQrCanvasWrap'
                );

                if (!wrap) {
                    return null;
                }

                const canvas = wrap.querySelector('canvas');

                if (canvas) {
                    return canvas.toDataURL('image/png');
                }

                const image = wrap.querySelector('img');

                if (image && image.src) {
                    return image.src;
                }

                return null;
            }


            /**
             * ----------------------------------------------------
             * Download QR
             * ----------------------------------------------------
             */
            function txDownloadQr() {

                const dataUrl = txGetQrDataUrl();

                if (!dataUrl) {
                    alert('QR code is not ready yet.');
                    return;
                }

                const link = document.createElement('a');

                link.download =
                    txQrCurrentFileName + '.png';

                link.href = dataUrl;

                document.body.appendChild(link);
                link.click();
                link.remove();
            }


            /**
             * ----------------------------------------------------
             * Print QR
             * ----------------------------------------------------
             */
            function txPrintQr() {

                const dataUrl = txGetQrDataUrl();

                if (!dataUrl) {
                    alert('QR code is not ready yet.');
                    return;
                }

                const printWindow = window.open(
                    '',
                    '_blank',
                    'width=500,height=650'
                );

                if (!printWindow) {

                    alert(
                        'Please allow pop-ups to print the QR code.'
                    );

                    return;
                }

                const safeName = txEscapeHtml(
                    txQrCurrentName
                );

                const safeSub = txEscapeHtml(
                    txQrCurrentSub
                );

                printWindow.document.write(`
                    <!DOCTYPE html>

                    <html>

                    <head>

                        <meta charset="UTF-8">

                        <title>
                            Print QR - ${safeName}
                        </title>

                        <style>

                            @page {
                                margin: .5in;
                            }

                            * {
                                box-sizing: border-box;
                            }

                            body {
                                margin: 0;
                                padding: 2rem;

                                display: flex;
                                flex-direction: column;
                                align-items: center;

                                text-align: center;

                                font-family:
                                    -apple-system,
                                    BlinkMacSystemFont,
                                    "Segoe UI",
                                    sans-serif;

                                color: #141414;
                            }

                            .qr-box {
                                padding: 14px;
                                border: 1px solid #ddd;
                                border-radius: 10px;
                            }

                            img {
                                display: block;
                                width: 260px;
                                height: 260px;
                            }

                            h1 {
                                max-width: 420px;
                                margin: 1rem 0 .25rem;

                                font-size: 1rem;
                                line-height: 1.4;
                            }

                            p {
                                margin: 0;

                                color: #666;

                                font-family: monospace;
                                font-size: .8rem;
                            }

                            .url {
                                max-width: 420px;
                                margin-top: .75rem;

                                color: #888;
                                font-family: monospace;
                                font-size: .65rem;
                                word-break: break-all;
                            }

                        </style>

                    </head>

                    <body>

                        <div class="qr-box">
                            <img
                                src="${dataUrl}"
                                alt="Product QR Code"
                            >
                        </div>

                        <h1>
                            ${safeName}
                        </h1>

                        <p>
                            ${safeSub}
                        </p>

                        <div class="url">
                            ${txEscapeHtml(txQrCurrentUrl)}
                        </div>

                    </body>

                    </html>
                `);

                printWindow.document.close();

                printWindow.onload = function () {

                    printWindow.focus();

                    printWindow.print();

                };
            }


            /**
             * ----------------------------------------------------
             * Escape HTML
             * ----------------------------------------------------
             */
            function txEscapeHtml(value) {

                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }


            /**
             * ----------------------------------------------------
             * Archive Confirmation
             * ----------------------------------------------------
             */
            function txConfirmArchive(form) {

                return confirm(
                    'Archive this product?\n\n' +
                    'The product will be removed from the active product list.'
                );
            }


            /**
             * ----------------------------------------------------
             * Keyboard Controls
             * ----------------------------------------------------
             */
            document.addEventListener('keydown', function (event) {

                if (event.key === 'Escape') {
                    txCloseQrModal();
                }

            });


            /**
             * ----------------------------------------------------
             * Close modal when browser/page navigation occurs
             * ----------------------------------------------------
             */
            window.addEventListener('beforeunload', function () {

                document.body.style.overflow = '';

            });
        </script>

    @endpush

@endonce
