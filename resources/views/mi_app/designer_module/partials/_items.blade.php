
{{-- ============================================================
     finance/bidding/partials/_items.blade.php

     Variables:
     - $lotIndex
     - $itemIndex
     - $item (array|Model)

     Expected:
     - $catalogItems

     Used by:
     - Bidding Project Form
     - #item-template

     ============================================================ --}}

@php

    /*
    |--------------------------------------------------------------------------
    | Normalize Item Data
    |--------------------------------------------------------------------------
    */

    $itemData = is_array($item)
        ? $item
        : ($item?->toArray() ?? []);


    /*
    |--------------------------------------------------------------------------
    | Field Values
    |--------------------------------------------------------------------------
    */

    $description = old(
        "lots.{$lotIndex}.items.{$itemIndex}.description",
        $itemData['description'] ?? ''
    );

    $itemDescription = old(
        "lots.{$lotIndex}.items.{$itemIndex}.item_description",
        $itemData['item_description'] ?? $itemData['description'] ?? ''
    );

    $unit = old(
        "lots.{$lotIndex}.items.{$itemIndex}.unit",
        $itemData['unit'] ?? ''
    );

    $quantity = old(
        "lots.{$lotIndex}.items.{$itemIndex}.quantity",
        $itemData['quantity'] ?? ''
    );

    $unitCost = old(
        "lots.{$lotIndex}.items.{$itemIndex}.unit_cost",
        $itemData['unit_cost'] ?? ''
    );

    $totalAmount = old(
        "lots.{$lotIndex}.items.{$itemIndex}.total_amount",
        $itemData['total_amount'] ?? ''
    );

    $brand = old(
        "lots.{$lotIndex}.items.{$itemIndex}.brand",
        $itemData['brand'] ?? ''
    );

    $remarks = old(
        "lots.{$lotIndex}.items.{$itemIndex}.remarks",
        $itemData['remarks'] ?? ''
    );

@endphp


<div
    class="bf-item-row"
    data-lot-index="{{ $lotIndex }}"
    data-item-index="{{ $itemIndex }}"
>

    {{-- =========================================================
         ITEM
         ========================================================= --}}

    <div class="bf-item-cell">

        <select
            class="item-select"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][item_description]"
            aria-label="Select item"
        >

            <option value="">
                Select Item
            </option>

            @foreach ($catalogItems ?? [] as $catalogItem)

                @php
                    $catalogDescription = $catalogItem->description ?? '';
                    $catalogName = $catalogItem->item_name
                        ?? $catalogItem->description
                        ?? 'Unnamed Item';

                    $isSelected =
                        (string) $itemDescription ===
                        (string) $catalogDescription;
                @endphp

                <option
                    value="{{ $catalogDescription }}"
                    data-unit="{{ $catalogItem->unit ?? '' }}"
                    data-description="{{ $catalogDescription }}"
                    data-item-name="{{ $catalogName }}"
                    @selected($isSelected)
                >
                    {{ $catalogName }}
                </option>

            @endforeach

        </select>

    </div>


    {{-- =========================================================
         UNIT
         ========================================================= --}}

    <div class="bf-item-cell">

        <input
            type="text"
            class="unit-input"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit]"
            value="{{ $unit }}"
            placeholder="Unit"
            autocomplete="off"
            aria-label="Unit"
        >

    </div>


    {{-- =========================================================
         QUANTITY
         ========================================================= --}}

    <div class="bf-item-cell">

        <input
            type="number"
            class="qty"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][quantity]"
            value="{{ $quantity }}"
            placeholder="0"
            min="0"
            step="1"
            inputmode="numeric"
            aria-label="Quantity"
        >

    </div>


    {{-- =========================================================
         UNIT COST
         Hidden from the visible table.

         Kept in the form because the backend may still expect it.
         ========================================================= --}}

    <input
        type="hidden"
        class="unit-cost"
        name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][unit_cost]"
        value="{{ $unitCost }}"
    >


    {{-- =========================================================
         AMOUNT
         ========================================================= --}}

    <div class="bf-item-cell">

        <div class="bf-item-money">

            <span class="bf-item-money-symbol">
                ₱
            </span>

            <input
                type="text"
                class="item-amount"
                name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][total_amount]"
                value="{{ $totalAmount }}"
                placeholder="0.00"
                readonly
                tabindex="-1"
                aria-label="Total amount"
            >

        </div>

    </div>


    {{-- =========================================================
         DESCRIPTION
         ========================================================= --}}

    <div class="bf-item-cell">

        <input
            type="text"
            class="item-description"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][description]"
            value="{{ $description }}"
            placeholder="Description"
            readonly
            tabindex="-1"
            aria-label="Item description"
        >

    </div>


    {{-- =========================================================
         BRAND / SPECIFICATIONS
         ========================================================= --}}

    <div class="bf-item-cell">

        <input
            type="text"
            class="brand-input"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][brand]"
            value="{{ $brand }}"
            placeholder="Brand / Specs"
            autocomplete="off"
            aria-label="Brand and specifications"
        >

    </div>


    {{-- =========================================================
         REMARKS
         ========================================================= --}}

    <div class="bf-item-cell">

        <input
            type="text"
            class="remarks-input"
            name="lots[{{ $lotIndex }}][items][{{ $itemIndex }}][remarks]"
            value="{{ $remarks }}"
            placeholder="Remarks"
            autocomplete="off"
            aria-label="Remarks"
        >

    </div>


    {{-- =========================================================
         DELETE ITEM
         ========================================================= --}}

    <div class="bf-item-delete-cell">

        <button
            type="button"
            class="bf-item-delete"
            aria-label="Remove item"
            title="Remove item"
            onclick="
                const row = this.closest('.bf-item-row');
                if (row) {
                    row.remove();

                    if (typeof updateLotTotal === 'function') {
                        updateLotTotal(
                            row.dataset.lotIndex
                        );
                    }

                    if (typeof updateGrandTotals === 'function') {
                        updateGrandTotals();
                    }
                }
            "
        >

            <i
                class="ti ti-trash"
                aria-hidden="true"
            ></i>

        </button>

    </div>

</div>


{{-- ============================================================
     PARTIAL-SPECIFIC STYLES

     These styles complement the parent .bf-wrap form.
     ============================================================ --}}

<style>
    /*
    |--------------------------------------------------------------------------
    | Item Cell
    |--------------------------------------------------------------------------
    */

    .bf-item-cell {
        min-width: 0;
        display: flex;
        align-items: center;
    }

    .bf-item-cell > input,
    .bf-item-cell > select {
        width: 100%;
        min-width: 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Item Select
    |--------------------------------------------------------------------------
    */

    .bf-item-cell .item-select {
        width: 100%;
        height: 32px;

        padding: 5px 28px 5px 8px;

        border: none;
        border-right: 1px solid #f1f5f9;

        border-radius: 0;

        background-color: transparent;

        color: #0f172a;

        font-family: inherit;
        font-size: 11px;

        outline: none;

        cursor: pointer;

        appearance: auto;

        transition:
            background .12s ease,
            box-shadow .12s ease;
    }

    .bf-item-cell .item-select:hover {
        background: #f8fafc;
    }

    .bf-item-cell .item-select:focus {
        background: #f8fbff;

        box-shadow:
            inset 0 0 0 1px #bfdbfe;
    }


    /*
    |--------------------------------------------------------------------------
    | Item Inputs
    |--------------------------------------------------------------------------
    */

    .bf-item-cell input {
        width: 100%;
        height: 32px;

        padding: 6px 7px;

        border: none;
        border-right: 1px solid #f1f5f9;

        border-radius: 0;

        background: transparent;

        color: #0f172a;

        font-family: inherit;
        font-size: 11px;

        outline: none;

        transition:
            background .12s ease,
            box-shadow .12s ease;
    }

    .bf-item-cell input:hover {
        background: #fafcff;
    }

    .bf-item-cell input:focus {
        background: #f8fbff;

        box-shadow:
            inset 0 0 0 1px #bfdbfe;
    }

    .bf-item-cell input::placeholder {
        color: #c4ccd6;
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity
    |--------------------------------------------------------------------------
    */

    .bf-item-cell .qty {
        text-align: right;
    }


    /*
    |--------------------------------------------------------------------------
    | Readonly Fields
    |--------------------------------------------------------------------------
    */

    .bf-item-cell .item-amount,
    .bf-item-cell .item-description {
        background: #f8fafc;

        color: #64748b;

        cursor: default;
    }

    .bf-item-cell .item-description {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    */

    .bf-item-money {
        width: 100%;

        display: flex;
        align-items: center;

        position: relative;
    }

    .bf-item-money-symbol {
        position: absolute;
        left: 7px;
        z-index: 2;

        color: #94a3b8;

        font-size: 10px;
        font-weight: 600;

        pointer-events: none;
    }

    .bf-item-money .item-amount {
        padding-left: 21px;

        text-align: right;

        font-family: 'JetBrains Mono', monospace;
        font-size: 10.5px;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Cell
    |--------------------------------------------------------------------------
    */

    .bf-item-delete-cell {
        height: 100%;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bf-item-delete {
        width: 30px;
        height: 30px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin: 0 auto;

        border: none;
        border-radius: 6px;

        background: transparent;
        color: #94a3b8;

        cursor: pointer;

        transition:
            background .15s ease,
            color .15s ease,
            transform .15s ease;
    }

    .bf-item-delete:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .bf-item-delete:active {
        transform: scale(.94);
    }

    .bf-item-delete i {
        font-size: 15px;
    }


    /*
    |--------------------------------------------------------------------------
    | Row Hover
    |--------------------------------------------------------------------------
    */

    .bf-item-row:hover .bf-item-cell .item-description,
    .bf-item-row:hover .bf-item-cell .item-amount {
        background: #f8fafc;
    }


    /*
    |--------------------------------------------------------------------------
    | Mobile / Smaller Screens
    |--------------------------------------------------------------------------
    */

    @media (max-width: 700px) {

        .bf-item-cell input,
        .bf-item-cell .item-select {
            font-size: 10.5px;
        }

    }
</style>


{{-- ============================================================
     ITEM FIELD BEHAVIOR

     Automatically:
     1. Selects catalog item.
     2. Populates unit.
     3. Populates description.
     4. Recalculates amount if unit cost is available.
     ============================================================ --}}

<script>
(function () {

    function initializeItemRow(row) {

        if (!row || row.dataset.initialized === '1') {
            return;
        }

        row.dataset.initialized = '1';


        const itemSelect = row.querySelector('.item-select');
        const unitInput = row.querySelector('.unit-input');
        const descriptionInput = row.querySelector('.item-description');
        const quantityInput = row.querySelector('.qty');
        const unitCostInput = row.querySelector('.unit-cost');
        const amountInput = row.querySelector('.item-amount');


        /*
        |--------------------------------------------------------------------------
        | Populate Item Information
        |--------------------------------------------------------------------------
        */

        function populateItem() {

            if (!itemSelect) {
                return;
            }

            const selectedOption =
                itemSelect.options[itemSelect.selectedIndex];

            if (!selectedOption || !selectedOption.value) {
                return;
            }


            /*
            | Unit
            */

            if (unitInput) {

                const unit =
                    selectedOption.dataset.unit || '';

                /*
                 * Only automatically replace the unit when
                 * a catalog unit is actually available.
                 */

                if (unit) {
                    unitInput.value = unit;
                }

            }


            /*
            | Description
            */

            if (descriptionInput) {

                const description =
                    selectedOption.dataset.description ||
                    selectedOption.value ||
                    '';

                descriptionInput.value = description;
            }


            /*
            | Recalculate
            */

            calculateAmount();

        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Amount
        |--------------------------------------------------------------------------
        */

        function calculateAmount() {

            if (!quantityInput || !amountInput) {
                return;
            }

            const quantity =
                parseFloat(quantityInput.value) || 0;

            const unitCost =
                parseFloat(unitCostInput?.value) || 0;

            const amount =
                quantity * unitCost;


            if (unitCost > 0) {

                amountInput.value =
                    amount.toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

            } else {

                /*
                 * Do not overwrite an existing server value
                 * when there is no unit cost.
                 */

                if (!amountInput.value) {
                    amountInput.value = '';
                }

            }


            /*
            | Update lot total if the parent form provides
            | the function.
            */

            if (
                typeof updateLotTotal === 'function' &&
                row.dataset.lotIndex !== undefined
            ) {

                updateLotTotal(
                    row.dataset.lotIndex
                );

            }


            if (typeof updateGrandTotals === 'function') {
                updateGrandTotals();
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Events
        |--------------------------------------------------------------------------
        */

        if (itemSelect) {

            itemSelect.addEventListener(
                'change',
                populateItem
            );

        }


        if (quantityInput) {

            quantityInput.addEventListener(
                'input',
                calculateAmount
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Initial State
        |--------------------------------------------------------------------------
        */

        /*
         * Do not blindly call populateItem() because an existing
         * database value should not be overwritten unnecessarily.
         */

        if (
            itemSelect &&
            itemSelect.value &&
            (
                !unitInput?.value ||
                !descriptionInput?.value
            )
        ) {

            populateItem();

        }


        /*
        | Calculate existing amount when possible.
        */

        if (
            quantityInput?.value &&
            unitCostInput?.value
        ) {

            calculateAmount();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Initialize Existing Rows
    |--------------------------------------------------------------------------
    */

    function initializeExistingRows() {

        document
            .querySelectorAll('.bf-item-row')
            .forEach(initializeItemRow);

    }


    /*
    |--------------------------------------------------------------------------
    | Observe Dynamically Added Items
    |--------------------------------------------------------------------------
    */

    const observer = new MutationObserver(
        function (mutations) {

            mutations.forEach(function (mutation) {

                mutation.addedNodes.forEach(function (node) {

                    if (
                        node.nodeType !== Node.ELEMENT_NODE
                    ) {
                        return;
                    }


                    if (
                        node.classList?.contains(
                            'bf-item-row'
                        )
                    ) {

                        initializeItemRow(node);

                    }


                    node
                        .querySelectorAll?.('.bf-item-row')
                        .forEach(initializeItemRow);

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Start
    |--------------------------------------------------------------------------
    */

    function start() {

        initializeExistingRows();


        const lotsContainer =
            document.getElementById('lotsContainer');

        if (lotsContainer) {

            observer.observe(
                lotsContainer,
                {
                    childList: true,
                    subtree: true
                }
            );

        }

    }


    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            start
        );

    } else {

        start();

    }

})();
</script>
