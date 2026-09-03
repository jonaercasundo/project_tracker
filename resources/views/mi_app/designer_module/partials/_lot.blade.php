
{{-- ============================================================
     finance/bidding/partials/_lot.blade.php

     Variables:
     - $index : int|string
     - $lot   : array|Model

     Used by:
     finance/bidding/create.blade.php
     ============================================================ --}}

@php
    /*
    |--------------------------------------------------------------------------
    | Normalize lot data
    |--------------------------------------------------------------------------
    */
    $lotData = is_array($lot)
        ? $lot
        : ($lot?->toArray() ?? []);

    /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    | Keep two blank rows by default so a new lot is immediately usable.
    */
    $items = $lotData['items'] ?? [[], []];

    /*
    |--------------------------------------------------------------------------
    | Lot fields
    |--------------------------------------------------------------------------
    */
    $lotName = old(
        "lots.{$index}.lot_name",
        $lotData['lot_name'] ?? ''
    );

    $countryCode = old(
        "lots.{$index}.country_code",
        $lotData['country_code'] ?? 'PH'
    );

    $regionCode = old(
        "lots.{$index}.region_code",
        $lotData['region_code'] ?? ''
    );

    $provinceCode = old(
        "lots.{$index}.province_code",
        $lotData['province_code'] ?? ''
    );

    $cityCode = old(
        "lots.{$index}.city_code",
        $lotData['city_code'] ?? ''
    );

    $barangayCode = old(
        "lots.{$index}.barangay_code",
        $lotData['barangay_code'] ?? ''
    );

    $deliveryAddress = old(
        "lots.{$index}.delivery_address",
        $lotData['delivery_address'] ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | Display lot number
    |--------------------------------------------------------------------------
    */
    $displayLotNumber = is_numeric($index)
        ? ((int) $index + 1)
        : $index;
@endphp


<div
    class="bf-lot"
    id="lot-{{ $index }}"
    data-lot-index="{{ $index }}"
>

    {{-- ========================================================
         LOT HEADER
         ======================================================== --}}
    <div class="bf-lot-head">

        <div class="bf-lot-title">

            <span class="bf-lot-badge">
                <i class="ti ti-package" aria-hidden="true"></i>
                Lot {{ $displayLotNumber }}
            </span>

            @if($lotName)
                <span class="bf-lot-name">
                    {{ $lotName }}
                </span>
            @else
                <span class="bf-lot-name bf-lot-name-empty">
                    New Procurement Lot
                </span>
            @endif

        </div>

        {{-- Lot number submitted to backend --}}
        <input
            type="hidden"
            name="lots[{{ $index }}][lot_no]"
            value="Lot {{ $displayLotNumber }}"
        >

        {{-- Lot name --}}
        <input
            type="hidden"
            name="lots[{{ $index }}][lot_name]"
            value="{{ $lotName }}"
        >

        <button
            type="button"
            class="bf-lot-del"
            aria-label="Remove Lot {{ $displayLotNumber }}"
            title="Remove this lot"
            onclick="
                if (confirm('Remove Lot {{ $displayLotNumber }}?')) {
                    this.closest('.bf-lot').remove();

                    if (typeof updateGrandTotals === 'function') {
                        updateGrandTotals();
                    }
                }
            "
        >
            <i class="ti ti-trash" aria-hidden="true"></i>
        </button>

    </div>


    {{-- ========================================================
         LOT BODY
         ======================================================== --}}
    <div class="bf-lot-body">


        {{-- ====================================================
             DELIVERY LOCATION
             ==================================================== --}}
        <div class="bf-location-card">

            <div class="bf-section-heading">

                <div class="bf-section-heading-left">
                    <span class="bf-section-icon">
                        <i class="ti ti-map-pin" aria-hidden="true"></i>
                    </span>

                    <div>
                        <h4>Delivery Location</h4>
                        <p>
                            Specify where the items for this lot will be delivered.
                        </p>
                    </div>
                </div>

            </div>


            <div class="bf-loc-row">

                {{-- Country --}}
                <div class="bf-field">

                    <label
                        class="bf-label"
                        for="country_{{ $index }}"
                    >
                        <i class="ti ti-flag" aria-hidden="true"></i>
                        Country
                    </label>

                    <div class="bf-select-wrap">

                        <select
                            class="bf-select country"
                            id="country_{{ $index }}"
                            name="lots[{{ $index }}][country_code]"
                        >
                            <option
                                value="PH"
                                @selected($countryCode === 'PH')
                            >
                                Philippines
                            </option>
                        </select>

                    </div>

                </div>


                {{-- Region --}}
                <div class="bf-field">

                    <label
                        class="bf-label"
                        for="region_{{ $index }}"
                    >
                        <i class="ti ti-map" aria-hidden="true"></i>
                        Region
                    </label>

                    <div class="bf-select-wrap">

                        <select
                            class="bf-select region"
                            id="region_{{ $index }}"
                            name="lots[{{ $index }}][region_code]"
                            data-lot="{{ $index }}"
                            data-selected="{{ $regionCode }}"
                        >
                            <option value="">
                                — Select Region —
                            </option>
                        </select>

                    </div>

                </div>


                {{-- Province --}}
                <div class="bf-field">

                    <label
                        class="bf-label"
                        for="province_{{ $index }}"
                    >
                        <i class="ti ti-map-2" aria-hidden="true"></i>
                        Province
                    </label>

                    <div class="bf-select-wrap">

                        <select
                            class="bf-select province"
                            id="province_{{ $index }}"
                            name="lots[{{ $index }}][province_code]"
                            data-lot="{{ $index }}"
                            data-selected="{{ $provinceCode }}"
                            disabled
                        >
                            <option value="">
                                — Select Province —
                            </option>
                        </select>

                    </div>

                </div>


                {{-- City / Municipality --}}
                <div class="bf-field">

                    <label
                        class="bf-label"
                        for="city_{{ $index }}"
                    >
                        <i
                            class="ti ti-building-community"
                            aria-hidden="true"
                        ></i>
                        City / Municipality
                    </label>

                    <div class="bf-select-wrap">

                        <select
                            class="bf-select city"
                            id="city_{{ $index }}"
                            name="lots[{{ $index }}][city_code]"
                            data-lot="{{ $index }}"
                            data-selected="{{ $cityCode }}"
                            disabled
                        >
                            <option value="">
                                — Select City / Municipality —
                            </option>
                        </select>

                    </div>

                </div>


                {{-- Barangay --}}
                <div class="bf-field">

                    <label
                        class="bf-label"
                        for="barangay_{{ $index }}"
                    >
                        <i
                            class="ti ti-map-pin"
                            aria-hidden="true"
                        ></i>
                        Barangay
                    </label>

                    <div class="bf-select-wrap">

                        <select
                            class="bf-select barangay"
                            id="barangay_{{ $index }}"
                            name="lots[{{ $index }}][barangay_code]"
                            data-lot="{{ $index }}"
                            data-selected="{{ $barangayCode }}"
                            disabled
                        >
                            <option value="">
                                — Select Barangay —
                            </option>
                        </select>

                    </div>

                </div>

            </div>


            {{-- Delivery Address --}}
            <div class="bf-address-field">

                <label
                    class="bf-label"
                    for="delivery_address_{{ $index }}"
                >
                    <i class="ti ti-home" aria-hidden="true"></i>
                    Complete Delivery Address
                </label>

                <textarea
                    id="delivery_address_{{ $index }}"
                    name="lots[{{ $index }}][delivery_address]"
                    class="bf-input auto-expand"
                    rows="2"
                    placeholder="House/Building No., Street, Subdivision, Landmark, etc."
                >{{ $deliveryAddress }}</textarea>

                <div class="bf-field-hint">
                    <i class="ti ti-info-circle" aria-hidden="true"></i>
                    Include additional delivery instructions or landmarks when necessary.
                </div>

            </div>

        </div>


        {{-- ====================================================
             ITEMS SECTION
             ==================================================== --}}
        <div class="bf-items-section">

            <div class="bf-items-section-head">

                <div>

                    <div class="bf-items-title">
                        <i class="ti ti-list-details" aria-hidden="true"></i>
                        Lot Items
                    </div>

                    <div class="bf-items-subtitle">
                        Add the products, quantities, specifications, and remarks
                        required for this lot.
                    </div>

                </div>

            </div>


            {{-- =================================================
                 ITEMS TABLE
                 ================================================= --}}
            <div
                class="bf-items"
                id="items-{{ $index }}"
                data-lot-index="{{ $index }}"
            >

                {{-- Header must match _items.blade.php --}}
                <div class="bf-items-head">

                    <span>
                        <i class="ti ti-package" aria-hidden="true"></i>
                        Item
                    </span>

                    <span>
                        Unit
                    </span>

                    <span>
                        Qty
                    </span>

                    <span>
                        Unit Cost
                    </span>

                    <span>
                        Amount
                    </span>

                    <span>
                        Brand / Specs
                    </span>

                    <span>
                        Remarks
                    </span>

                    <span
                        aria-hidden="true"
                        class="bf-items-action-head"
                    ></span>

                </div>


                {{-- =================================================
                     EXISTING / BLANK ITEMS
                     ================================================= --}}
                @foreach($items as $itemIndex => $item)

                    @include(
                        'finance.bidding.partials._items',
                        [
                            'lotIndex'  => $index,
                            'itemIndex' => $itemIndex,
                            'item'      => $item,
                        ]
                    )

                @endforeach

            </div>


            {{-- =================================================
                 LOT TOTAL
                 ================================================= --}}
            <div class="bf-items-total">

                <div class="bf-items-total-label">

                    <span class="bf-total-icon">
                        <i class="ti ti-calculator" aria-hidden="true"></i>
                    </span>

                    <div>
                        <span class="bf-total-title">
                            Lot Total
                        </span>

                        <small>
                            Total amount for all items in this lot
                        </small>
                    </div>

                </div>

                <strong class="bf-lot-total-value">
                    <span class="bf-currency">₱</span>
                    <span class="lot-grand-total">0.00</span>
                </strong>

            </div>


            {{-- =================================================
                 ADD ITEM
                 ================================================= --}}
            <button
                type="button"
                class="bf-btn-add-item"
                onclick="addItem(this, '{{ $index }}')"
            >
                <i
                    class="ti ti-plus"
                    aria-hidden="true"
                ></i>

                <span>Add Item</span>
            </button>

        </div>

    </div>

</div>


{{-- ============================================================
     LOT-SPECIFIC STYLES
     These styles are safe for dynamically generated lots.
     ============================================================ --}}
<style>
    .bf-lot {
        position: relative;
        margin-bottom: 24px;
        border: 1px solid #dbe4f0;
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    /* ---------------------------------------------------------
       LOT HEADER
       --------------------------------------------------------- */

    .bf-lot-head {
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 16px;
        background: linear-gradient(
            135deg,
            #f8fbff 0%,
            #eef5ff 100%
        );
        border-bottom: 1px solid #dbe7f5;
    }

    .bf-lot-title {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .bf-lot-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 999px;
        background: #2563eb;
        color: #ffffff;
        font-family: "Space Grotesk", sans-serif;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .bf-lot-badge i {
        font-size: 15px;
    }

    .bf-lot-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
    }

    .bf-lot-name-empty {
        color: #94a3b8;
        font-weight: 500;
    }

    .bf-lot-del {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #fecaca;
        border-radius: 9px;
        background: #ffffff;
        color: #dc2626;
        cursor: pointer;
        transition: all .18s ease;
    }

    .bf-lot-del:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        transform: translateY(-1px);
    }

    .bf-lot-del i {
        font-size: 18px;
    }


    /* ---------------------------------------------------------
       LOT BODY
       --------------------------------------------------------- */

    .bf-lot-body {
        padding: 20px;
    }


    /* ---------------------------------------------------------
       LOCATION CARD
       --------------------------------------------------------- */

    .bf-location-card {
        margin-bottom: 22px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
    }

    .bf-section-heading {
        margin-bottom: 18px;
    }

    .bf-section-heading-left {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .bf-section-icon {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #dbeafe;
        color: #2563eb;
    }

    .bf-section-icon i {
        font-size: 19px;
    }

    .bf-section-heading h4 {
        margin: 0;
        color: #0f172a;
        font-family: "Space Grotesk", sans-serif;
        font-size: 14px;
        font-weight: 700;
    }

    .bf-section-heading p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 12px;
    }


    /* ---------------------------------------------------------
       LOCATION FIELDS
       --------------------------------------------------------- */

    .bf-loc-row {
        display: grid;
        grid-template-columns:
            minmax(150px, .8fr)
            minmax(170px, 1fr)
            minmax(170px, 1fr)
            minmax(190px, 1.1fr)
            minmax(170px, 1fr);
        gap: 12px;
    }

    .bf-field {
        min-width: 0;
    }

    .bf-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 7px;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .bf-label i {
        color: #2563eb;
        font-size: 14px;
    }

    .bf-select-wrap {
        position: relative;
    }

    .bf-select-wrap::after {
        content: "\ea5f";
        position: absolute;
        top: 50%;
        right: 11px;
        transform: translateY(-50%);
        font-family: "tabler-icons";
        font-size: 15px;
        color: #64748b;
        pointer-events: none;
    }

    .bf-select {
        width: 100%;
        min-height: 40px;
        padding: 8px 34px 8px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #ffffff;
        color: #0f172a;
        font-family: "Inter", sans-serif;
        font-size: 12px;
        outline: none;
        appearance: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .bf-select:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
    }

    .bf-select:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }


    /* ---------------------------------------------------------
       DELIVERY ADDRESS
       --------------------------------------------------------- */

    .bf-address-field {
        margin-top: 16px;
    }

    .bf-input {
        width: 100%;
        min-height: 40px;
        padding: 9px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #ffffff;
        color: #0f172a;
        font-family: "Inter", sans-serif;
        font-size: 12px;
        line-height: 1.5;
        outline: none;
        resize: vertical;
        transition: border-color .18s ease, box-shadow .18s ease;
        box-sizing: border-box;
    }

    .bf-input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
    }

    .bf-field-hint {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 10px;
    }

    .bf-field-hint i {
        font-size: 13px;
    }


    /* ---------------------------------------------------------
       ITEMS SECTION
       --------------------------------------------------------- */

    .bf-items-section {
        min-width: 0;
    }

    .bf-items-section-head {
        margin-bottom: 12px;
    }

    .bf-items-title {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #0f172a;
        font-family: "Space Grotesk", sans-serif;
        font-size: 14px;
        font-weight: 700;
    }

    .bf-items-title i {
        color: #2563eb;
        font-size: 18px;
    }

    .bf-items-subtitle {
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
    }


    /* ---------------------------------------------------------
       ITEMS HEADER
       --------------------------------------------------------- */

    .bf-items-head {
        display: grid;
        grid-template-columns:
            3.2fr
            .55fr
            .7fr
            1fr
            1fr
            1.25fr
            1.15fr
            48px;

        gap: 8px;
        align-items: center;

        padding: 9px 10px;

        border: 1px solid #dbe4f0;
        border-bottom: 0;

        border-radius: 10px 10px 0 0;

        background: #f1f5f9;

        color: #475569;
        font-family: "Space Grotesk", sans-serif;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .035em;
    }

    .bf-items-head span {
        min-width: 0;
    }

    .bf-items-head span:first-child {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .bf-items-head i {
        color: #2563eb;
        font-size: 14px;
    }

    .bf-items-action-head {
        text-align: center;
    }


    /* ---------------------------------------------------------
       LOT TOTAL
       --------------------------------------------------------- */

    .bf-items-total {
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;

        margin-top: 10px;
        padding: 10px 14px;

        border: 1px solid #bfdbfe;
        border-radius: 10px;

        background: #eff6ff;
    }

    .bf-items-total-label {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .bf-total-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #dbeafe;
        color: #2563eb;
    }

    .bf-total-icon i {
        font-size: 17px;
    }

    .bf-total-title {
        display: block;
        color: #1e3a8a;
        font-family: "Space Grotesk", sans-serif;
        font-size: 12px;
        font-weight: 700;
    }

    .bf-items-total small {
        display: block;
        margin-top: 1px;
        color: #64748b;
        font-size: 10px;
    }

    .bf-lot-total-value {
        display: inline-flex;
        align-items: baseline;
        gap: 5px;
        color: #1d4ed8;
        font-family: "JetBrains Mono", monospace;
        font-size: 17px;
        font-weight: 700;
        white-space: nowrap;
    }

    .bf-currency {
        font-family: "Inter", sans-serif;
        font-size: 12px;
    }


    /* ---------------------------------------------------------
       ADD ITEM BUTTON
       --------------------------------------------------------- */

    .bf-btn-add-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;

        margin-top: 10px;
        padding: 9px 13px;

        border: 1px dashed #93c5fd;
        border-radius: 9px;

        background: #f8fbff;
        color: #2563eb;

        font-family: "Inter", sans-serif;
        font-size: 12px;
        font-weight: 700;

        cursor: pointer;
        transition: all .18s ease;
    }

    .bf-btn-add-item:hover {
        border-color: #60a5fa;
        background: #eff6ff;
        transform: translateY(-1px);
    }

    .bf-btn-add-item i {
        font-size: 16px;
    }


    /* ---------------------------------------------------------
       RESPONSIVE
       --------------------------------------------------------- */

    @media (max-width: 1100px) {

        .bf-loc-row {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .bf-items {
            overflow-x: auto;
        }

        .bf-items-head,
        .bf-item-row {
            min-width: 1050px;
        }
    }


    @media (max-width: 700px) {

        .bf-lot-body {
            padding: 12px;
        }

        .bf-lot-head {
            padding: 10px 12px;
        }

        .bf-lot-title {
            gap: 8px;
        }

        .bf-lot-name {
            display: none;
        }

        .bf-location-card {
            padding: 13px;
        }

        .bf-loc-row {
            grid-template-columns: 1fr;
        }

        .bf-items-total {
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
        }

        .bf-lot-total-value {
            align-self: flex-end;
        }
    }
</style>
