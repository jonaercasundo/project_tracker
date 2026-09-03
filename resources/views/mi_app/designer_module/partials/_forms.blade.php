
{{-- ============================================================
     Bidding Project Form — Enhanced UI
     Modern Desktop App Style

     Drop this inside your <form> tag as the main form body.

     Requires:
     - Tailwind CSS (optional utility usage)
     - Tabler Icons
     - Laravel Blade
     - Existing partials:
         finance.bidding.partials._lot
         finance.bidding.partials._items
     ============================================================ --}}

<style>
    /* =========================================================
       BASE
       ========================================================= */

    .bf-wrap,
    .bf-wrap * {
        box-sizing: border-box;
    }

    .bf-wrap {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;

        display: flex;
        flex-direction: column;
        gap: 12px;

        padding: 16px;

        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont,
            "Segoe UI", sans-serif;

        font-size: 12px;
        color: #1e293b;

        background: #f8fafc;
    }

    /* =========================================================
       SECTION CARD
       ========================================================= */

    .bf-section {
        width: 100%;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 9px;

        overflow: hidden;

        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04);
    }

    /* =========================================================
       SECTION HEADER
       ========================================================= */

    .bf-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;

        min-height: 42px;
        padding: 9px 14px;

        background: #f8fafc;

        border-bottom: 1px solid #e2e8f0;
    }

    .bf-section-title {
        display: flex;
        align-items: center;
        gap: 7px;

        font-size: 11px;
        font-weight: 700;

        color: #334155;

        letter-spacing: .045em;
        text-transform: uppercase;
    }

    .bf-section-title i {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 22px;
        height: 22px;

        border-radius: 6px;

        background: #eff6ff;
        color: #2563eb;

        font-size: 13px;
    }

    .bf-section-sub {
        margin-left: 3px;

        font-size: 10px;
        font-weight: 400;

        color: #94a3b8;

        letter-spacing: 0;
        text-transform: none;
    }

    /* =========================================================
       BODY / GRID
       ========================================================= */

    .bf-body {
        display: grid;
        gap: 10px;

        padding: 12px 14px;
    }

    .bf-grid-1-3 {
        grid-template-columns: 1fr 3fr;
    }

    .bf-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bf-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .bf-grid-4 {
        grid-template-columns:
            1.5fr
            1fr
            1fr
            1fr;
    }

    .bf-grid-auto {
        grid-template-columns: 160px minmax(0, 1fr);
    }

    @media (max-width: 850px) {
        .bf-grid-1-3,
        .bf-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .bf-wrap {
            padding: 10px;
        }

        .bf-grid-1-3,
        .bf-grid-2,
        .bf-grid-3,
        .bf-grid-4,
        .bf-grid-auto {
            grid-template-columns: 1fr;
        }

        .bf-section-head {
            align-items: flex-start;
        }

        .bf-section-title {
            flex-wrap: wrap;
        }

        .bf-section-sub {
            width: 100%;
            margin-left: 29px;
        }
    }

    /* =========================================================
       FIELD
       ========================================================= */

    .bf-field {
        min-width: 0;

        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .bf-label {
        display: flex;
        align-items: center;
        gap: 4px;

        min-height: 15px;

        font-size: 9.5px;
        font-weight: 700;

        color: #64748b;

        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .bf-label i {
        font-size: 11px;
        color: #94a3b8;
    }

    .bf-label .bf-required {
        color: #ef4444;
        font-size: 10px;
    }

    /* =========================================================
       INPUTS
       ========================================================= */

    .bf-input,
    .bf-select {
        width: 100%;
        min-width: 0;

        height: 32px;

        padding: 6px 9px;

        font-family: inherit;
        font-size: 12px;
        font-weight: 400;

        color: #0f172a;

        background: #ffffff;

        border: 1px solid #cbd5e1;
        border-radius: 6px;

        outline: none;

        transition:
            border-color .15s ease,
            box-shadow .15s ease,
            background .15s ease;
    }

    .bf-input::placeholder {
        color: #b8c1cc;
    }

    .bf-input:hover,
    .bf-select:hover {
        border-color: #94a3b8;
        background: #ffffff;
    }

    .bf-input:focus,
    .bf-select:focus {
        border-color: #2563eb;

        background: #ffffff;

        box-shadow:
            0 0 0 3px rgba(37, 99, 235, .10);
    }

    textarea.bf-input {
        height: auto;
        min-height: 32px;
    }

    .bf-select {
        cursor: pointer;

        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;

        padding-right: 30px;
    }

    .bf-select-wrap {
        position: relative;
        width: 100%;
    }

    .bf-select-wrap::after {
        content: '';

        position: absolute;

        right: 11px;
        top: 50%;

        width: 0;
        height: 0;

        transform: translateY(-35%);

        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #94a3b8;

        pointer-events: none;
    }

    .bf-select:disabled {
        color: #94a3b8;
        background: #f1f5f9;

        cursor: not-allowed;

        border-color: #e2e8f0;
    }

    .bf-select-wrap:has(.bf-select:disabled)::after {
        border-top-color: #cbd5e1;
    }

    /* =========================================================
       CURRENCY
       ========================================================= */

    .bf-currency-wrap {
        position: relative;

        display: flex;
        align-items: center;
    }

    .bf-currency-symbol {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 32px;

        font-size: 12px;
        font-weight: 700;

        color: #64748b;
    }

    .bf-currency-input {
        padding-left: 2px;
    }

    /* =========================================================
       ERROR BOX
       ========================================================= */

    .bf-errors {
        display: flex;
        align-items: flex-start;
        gap: 9px;

        padding: 10px 12px;

        background: #fef2f2;

        border: 1px solid #fecaca;
        border-radius: 8px;
    }

    .bf-errors > i {
        flex-shrink: 0;

        margin-top: 1px;

        font-size: 15px;
        color: #dc2626;
    }

    .bf-errors-title {
        margin-bottom: 3px;

        font-size: 11px;
        font-weight: 700;

        color: #b91c1c;
    }

    .bf-errors ul {
        list-style: disc;

        margin: 0;
        padding-left: 15px;
    }

    .bf-errors li {
        font-size: 11px;
        line-height: 1.6;

        color: #dc2626;
    }

    /* =========================================================
       LOTS
       ========================================================= */

    .bf-lots-body {
        display: flex;
        flex-direction: column;
        gap: 9px;

        padding: 10px 14px;
    }

    .bf-lot {
        background: #ffffff;

        border: 1px solid #dbe2ea;
        border-radius: 8px;

        overflow: hidden;
    }

    .bf-lot-head {
        display: flex;
        align-items: center;
        gap: 9px;

        padding: 8px 10px;

        background: #f8fafc;

        border-bottom: 1px solid #e2e8f0;
    }

    .bf-lot-badge {
        flex-shrink: 0;

        padding: 3px 8px;

        border-radius: 999px;

        background: #eff6ff;
        color: #2563eb;

        font-size: 9.5px;
        font-weight: 700;

        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .bf-lot-name-input {
        flex: 1;
        min-width: 0;

        padding: 4px 0;

        background: transparent;

        border: none;
        outline: none;

        font-family: inherit;
        font-size: 11.5px;
        font-weight: 600;

        color: #334155;
    }

    .bf-lot-name-input::placeholder {
        color: #b8c1cc;
    }

    .bf-lot-name-input:focus {
        color: #0f172a;
    }

    .bf-lot-del {
        width: 30px;
        height: 30px;

        flex-shrink: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        border: none;
        border-radius: 50%;

        background: #fee2e2;
        color: #dc2626;

        cursor: pointer;

        transition:
            background .15s ease,
            color .15s ease,
            transform .15s ease;
    }

    .bf-lot-del:hover {
        background: #dc2626;
        color: #ffffff;
    }

    .bf-lot-del:active {
        transform: scale(.95);
    }

    .bf-lot-del i {
        font-size: 15px;
    }

    .bf-lot-body {
        display: flex;
        flex-direction: column;
        gap: 8px;

        padding: 10px;
    }

    /* =========================================================
       ITEMS TABLE
       ========================================================= */

    .bf-items {
        width: 100%;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 7px;

        max-height: 330px;

        overflow-x: auto;
        overflow-y: auto;
    }

    .bf-items-head,
    .bf-item-row {
        display: grid;

        min-width: 1150px;

        grid-template-columns:
            3.2fr
            .55fr
            .7fr
            1fr
            1fr
            1.25fr
            1.15fr
            48px;
    }

    .bf-items-head {
        position: sticky;
        top: 0;
        z-index: 10;

        padding: 7px 8px;

        background: #f8fafc;

        border-bottom: 1px solid #e2e8f0;
    }

    .bf-items-head span {
        display: flex;
        align-items: center;

        padding: 0 5px;

        font-size: 9px;
        font-weight: 700;

        color: #64748b;

        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .bf-item-row {
        min-height: 39px;

        align-items: center;

        background: #ffffff;

        border-bottom: 1px solid #f1f5f9;
    }

    .bf-item-row:last-child {
        border-bottom: none;
    }

    .bf-item-row:hover {
        background: #fafcff;
    }

    .bf-item-row input,
    .bf-item-row textarea,
    .bf-item-row select {
        width: 100%;
        min-width: 0;

        padding: 6px 7px;

        font-family: inherit;
        font-size: 11px;

        color: #0f172a;

        background: transparent;

        border: none;
        border-right: 1px solid #f1f5f9;

        outline: none;

        transition:
            background .12s ease,
            box-shadow .12s ease;
    }

    .bf-item-row input:focus,
    .bf-item-row textarea:focus,
    .bf-item-row select:focus {
        background: #f8fbff;

        box-shadow:
            inset 0 0 0 1px #bfdbfe;
    }

    .bf-item-row input::placeholder,
    .bf-item-row textarea::placeholder {
        color: #c4ccd6;
    }

    .bf-item-row textarea {
        min-height: 32px;

        resize: none;
        overflow: hidden;

        line-height: 1.4;
    }

    .bf-item-row input[type="number"] {
        text-align: right;
    }

    .bf-item-row input[type="number"]::-webkit-inner-spin-button,
    .bf-item-row input[type="number"]::-webkit-outer-spin-button {
        opacity: .5;
    }

    .bf-item-delete {
        width: 32px;
        height: 32px;

        margin: 0 auto;

        display: flex;
        align-items: center;
        justify-content: center;

        border: none;
        border-radius: 6px;

        background: transparent;
        color: #94a3b8;

        cursor: pointer;

        transition:
            background .15s ease,
            color .15s ease;
    }

    .bf-item-delete:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .bf-item-delete:active {
        transform: scale(.95);
    }

    .bf-item-delete i {
        font-size: 16px;
    }

    /* =========================================================
       TABLE SCROLLBAR
       ========================================================= */

    .bf-items::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .bf-items::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .bf-items::-webkit-scrollbar-thumb {
        background: #cbd5e1;

        border-radius: 10px;
    }

    .bf-items::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* =========================================================
       ITEMS TOTAL
       ========================================================= */

    .bf-items-total {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;

        padding: 9px 14px;

        background: #f8fafc;

        border-top: 1px solid #e2e8f0;

        font-size: 11px;
        font-weight: 600;

        color: #64748b;
    }

    .lot-grand-total {
        font-family: 'JetBrains Mono', monospace;

        font-size: 14px;
        font-weight: 700;

        color: #2563eb;
    }

    /* =========================================================
       ADD ITEM BUTTON
       ========================================================= */

    .bf-btn-add-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        width: fit-content;

        padding: 5px 9px;

        border: 1px solid #bfdbfe;
        border-radius: 6px;

        background: #eff6ff;
        color: #2563eb;

        font-family: inherit;
        font-size: 10.5px;
        font-weight: 600;

        cursor: pointer;

        transition:
            background .15s ease,
            border-color .15s ease,
            color .15s ease;
    }

    .bf-btn-add-item:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .bf-btn-add-item i {
        font-size: 12px;
    }

    /* =========================================================
       ADD LOT BUTTON
       ========================================================= */

    .bf-btn-add-lot {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        flex-shrink: 0;

        padding: 6px 11px;

        border: 1px solid #2563eb;
        border-radius: 6px;

        background: #2563eb;
        color: #ffffff;

        font-family: inherit;
        font-size: 10.5px;
        font-weight: 600;

        cursor: pointer;

        box-shadow: 0 1px 2px rgba(37, 99, 235, .12);

        transition:
            background .15s ease,
            border-color .15s ease,
            transform .15s ease;
    }

    .bf-btn-add-lot:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .bf-btn-add-lot:active {
        transform: translateY(1px);
    }

    .bf-btn-add-lot i {
        font-size: 13px;
    }

    /* =========================================================
       LOCATION ROW
       ========================================================= */

    .bf-loc-row {
        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 8px;
    }

    @media (max-width: 700px) {
        .bf-loc-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 420px) {
        .bf-loc-row {
            grid-template-columns: 1fr;
        }
    }

    /* =========================================================
       AUTO EXPAND
       ========================================================= */

    .auto-expand {
        width: 100%;

        min-height: 32px;

        resize: none;
        overflow: hidden;

        line-height: 1.5;
    }

    /* =========================================================
       FOOTER
       ========================================================= */

    .bf-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;

        padding: 2px 0 4px;
    }

    .bf-btn-cancel,
    .bf-btn-save {
        min-height: 34px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        padding: 6px 15px;

        border-radius: 6px;

        font-family: inherit;
        font-size: 11.5px;
        font-weight: 600;

        text-decoration: none;

        cursor: pointer;

        transition:
            background .15s ease,
            border-color .15s ease,
            color .15s ease,
            transform .15s ease;
    }

    .bf-btn-cancel {
        background: #ffffff;

        border: 1px solid #cbd5e1;

        color: #475569;
    }

    .bf-btn-cancel:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #334155;
    }

    .bf-btn-save {
        background: #2563eb;

        border: 1px solid #2563eb;

        color: #ffffff;

        box-shadow:
            0 1px 2px rgba(37, 99, 235, .12);
    }

    .bf-btn-save:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .bf-btn-save:active,
    .bf-btn-cancel:active {
        transform: translateY(1px);
    }

    .bf-btn-save i,
    .bf-btn-cancel i {
        font-size: 13px;
    }

    /* =========================================================
       MOBILE FOOTER
       ========================================================= */

    @media (max-width: 480px) {
        .bf-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .bf-btn-cancel,
        .bf-btn-save {
            width: 100%;
        }
    }
</style>


<div class="bf-wrap">

    {{-- =========================================================
         VALIDATION ERRORS
         ========================================================= --}}

    @if ($errors->any())
        <div class="bf-errors">

            <i class="ti ti-alert-triangle" aria-hidden="true"></i>

            <div>
                <div class="bf-errors-title">
                    Please correct the following errors:
                </div>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>

        </div>
    @endif


    {{-- =========================================================
         PROJECT INFORMATION
         ========================================================= --}}

    <div class="bf-section">

        <div class="bf-section-head">

            <div class="bf-section-title">
                <i class="ti ti-clipboard-list" aria-hidden="true"></i>

                Project Information
            </div>

        </div>


        {{-- =====================================================
             PROJECT BASIC INFORMATION
             ===================================================== --}}

        <div class="bf-body bf-grid-2">

            {{-- Project Code --}}
            <div class="bf-field">

                <label class="bf-label">
                    <i class="ti ti-hash"></i>
                    Project Code
                    <span class="bf-required">*</span>
                </label>

                <div class="bf-select-wrap">

                    <select
                        class="bf-select"
                        name="project_code"
                        required
                    >
                        <option value="">
                            -- Select Project --
                        </option>

                        @foreach([
                            'SME',
                            'SFP',
                            'MT',
                            'Textbook',
                            'DCP'
                        ] as $projectCode)

                            <option
                                value="{{ $projectCode }}"
                                @selected(
                                    old(
                                        'project_code',
                                        $project->project_code ?? ''
                                    ) === $projectCode
                                )
                            >
                                {{ $projectCode }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- Project ID --}}
            <div class="bf-field">

                <label class="bf-label">
                    <i class="ti ti-hash"></i>
                    Project ID No.
                </label>

                <input
                    class="bf-input"
                    type="text"
                    name="project_id"
                    value="{{ old('project_id', $project->project_id ?? '') }}"
                    placeholder="Enter project ID number"
                >

            </div>


            {{-- Procuring Entity --}}
            <div class="bf-field">

                <label class="bf-label">
                    <i class="ti ti-building-bank"></i>
                    Procuring Entity / Agency
                </label>

                <input
                    class="bf-input"
                    type="text"
                    name="procuring_entity"
                    value="{{ old('procuring_entity', $project->procuring_entity ?? '') }}"
                    placeholder="Agency or office"
                >

            </div>


            {{-- Project Name --}}
            <div class="bf-field">

                <label class="bf-label">
                    <i class="ti ti-file-description"></i>
                    Project Name
                    <span class="bf-required">*</span>
                </label>

                <textarea
                    class="bf-input auto-expand"
                    name="project_name"
                    rows="1"
                    placeholder="Enter full project name"
                    required
                >{{ old('project_name', $project->project_name ?? '') }}</textarea>

            </div>

        </div>


        {{-- =====================================================
             FINANCIAL / SCHEDULE INFORMATION
             ===================================================== --}}

        <div class="bf-body bf-grid-4">

            {{-- ABC --}}
            <div class="bf-field">

                <label class="bf-label">
                    <i class="ti ti-coin"></i>
                    Approved Budget for the Contract (ABC)
                </label>

                <div class="bf-currency-wrap">

                    <span class="bf-currency-symbol">
                        ₱
                    </span>

                    <input
                        type="text"
                        id="approved_budget_contract_abc"
                        name="approved_budget_contract_abc"
                        class="bf-input bf-currency-input currency"
                        value="{{ old(
                            'approved_budget_contract_abc',
                            $project->approved_budget_contract_abc ?? ''
                        ) }}"
                        placeholder="0.00"
                        readonly
                    >

                </div>

            </div>


            {{-- Delivery Period --}}
            <div class="bf-field">

                <label class="bf-label">

                    <i class="ti ti-clock"></i>

                    Delivery Period

                    <span style="font-weight:400;text-transform:none;">
                        (days)
                    </span>

                </label>

                <input
                    class="bf-input"
                    type="number"
                    min="0"
                    name="delivery_period"
                    value="{{ old(
                        'delivery_period',
                        $project->delivery_period ?? ''
                    ) }}"
                    placeholder="e.g. 30"
                >

            </div>


            {{-- Pre-Bid --}}
            <div class="bf-field">

                <label class="bf-label">

                    <i class="ti ti-calendar-event"></i>

                    Pre-Bid Conference

                </label>

                <input
                    class="bf-input"
                    type="date"
                    name="date_of_pre_bid_conference"
                    value="{{ old(
                        'date_of_pre_bid_conference',
                        $project->date_of_pre_bid_conference ?? ''
                    ) }}"
                >

            </div>


            {{-- Bid Opening --}}
            <div class="bf-field">

                <label class="bf-label">

                    <i class="ti ti-calendar-event"></i>

                    Bid Opening

                </label>

                <input
                    class="bf-input"
                    type="date"
                    name="date_of_bid_opening"
                    value="{{ old(
                        'date_of_bid_opening',
                        $project->date_of_bid_opening ?? ''
                    ) }}"
                >

            </div>


            {{-- Status --}}
            <div class="bf-field">

                <label class="bf-label">

                    <i class="ti ti-toggle-right"></i>

                    Status

                </label>

                <div class="bf-select-wrap">

                    @php
                        $currentStatus = old(
                            'status',
                            $project->status ?? 'Draft'
                        );
                    @endphp

                    <select
                        class="bf-select"
                        name="status"
                    >

                        @foreach([
                            'Draft',
                            'For Review',
                            'Published',
                            'Awarded',
                            'Cancelled',
                            'Completed'
                        ] as $status)

                            <option
                                value="{{ $status }}"
                                @selected($currentStatus === $status)
                            >
                                {{ $status }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         LOTS
         ========================================================= --}}

    <div class="bf-section">

        <div class="bf-section-head">

            <div class="bf-section-title">

                <i
                    class="ti ti-layers-intersect"
                    aria-hidden="true"
                ></i>

                Lots

                <span class="bf-section-sub">
                    Each lot has its own delivery location and items.
                </span>

            </div>


            <button
                type="button"
                id="addLot"
                class="bf-btn-add-lot"
            >
                <i
                    class="ti ti-plus"
                    aria-hidden="true"
                ></i>

                Add Lot
            </button>

        </div>


        <div
            id="lotsContainer"
            class="bf-lots-body"
        >

            @php

                $lots = old('lots');

                if (!$lots) {

                    if (
                        isset($project) &&
                        $project->lots &&
                        $project->lots->count()
                    ) {
                        $lots = $project->lots;
                    } else {
                        $lots = [[]];
                    }

                }

            @endphp


            @foreach($lots as $index => $lot)

                @include(
                    'finance.bidding.partials._lot',
                    [
                        'index' => $index,
                        'lot' => $lot
                    ]
                )

            @endforeach

        </div>

    </div>


    {{-- =========================================================
         FORM FOOTER
         ========================================================= --}}

    <div class="bf-footer">

        <a
            href="{{ route('bidding.index') }}"
            class="bf-btn-cancel"
        >
            <i
                class="ti ti-arrow-left"
                aria-hidden="true"
            ></i>

            Cancel
        </a>


        <button
            type="submit"
            class="bf-btn-save"
        >
            <i
                class="ti ti-device-floppy"
                aria-hidden="true"
            ></i>

            Save Project
        </button>

    </div>

</div>


{{-- =============================================================
     JAVASCRIPT TEMPLATES
     ============================================================= --}}

<template id="lot-template">

    @include(
        'finance.bidding.partials._lot',
        [
            'index' => '__INDEX__',
            'lot' => []
        ]
    )

</template>


<template id="item-template">

    @include(
        'finance.bidding.partials._items',
        [
            'lotIndex' => '__LOTINDEX__',
            'itemIndex' => '__ITEMINDEX__',
            'item' => []
        ]
    )

</template>
