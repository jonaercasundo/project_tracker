{{-- ============================================================
     Bidding Project Form — Enhanced UI (Modern Desktop App Style)
     Drop this inside your <form> tag as the main form body.
     Requires: Tailwind CSS + any icon font (Tabler Icons used here)
     ============================================================ --}}

<style>
    /* ── Reset & Base ─────────────────────────────────────────── */
    .bf-wrap * { box-sizing: border-box; }

    /* ── Layout ───────────────────────────────────────────────── */
    .bf-wrap {
        max-width: 860px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 12px;
        color: #1e1f24;
        background: #f3f4f6;
        min-height: 100vh;
    }

    /* ── Section Card ─────────────────────────────────────────── */
    .bf-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }

    /* ── Section Header ───────────────────────────────────────── */
    .bf-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 14px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    .bf-section-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 600;
        color: #374151;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .bf-section-title i {
        font-size: 13px;
        color: #6366f1;
    }
    .bf-section-sub {
        font-size: 10px;
        font-weight: 400;
        color: #9ca3af;
        text-transform: none;
        letter-spacing: 0;
        margin-left: 4px;
    }

    /* ── Grid / Body ──────────────────────────────────────────── */
    .bf-body {
        padding: 12px 14px;
        display: grid;
        gap: 10px;
    }
    .bf-grid-1-3 { grid-template-columns: 1fr 3fr; }
    .bf-grid-2   { grid-template-columns: repeat(2, 1fr); }
    .bf-grid-3   { grid-template-columns: repeat(3, 1fr); }
    .bf-grid-4   { grid-template-columns: 1.5fr 1.5fr 1fr 1fr; }
    .bf-grid-auto { grid-template-columns: 160px 1fr; }
    @media (max-width: 600px) {
        .bf-grid-1-3,
        .bf-grid-2,
        .bf-grid-3,
        .bf-grid-4,
        .bf-grid-auto { grid-template-columns: 1fr; }
    }

    /* ── Field ────────────────────────────────────────────────── */
    .bf-field { display: flex; flex-direction: column; gap: 3px; }
    .bf-label {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .bf-label i { font-size: 11px; color: #9ca3af; }

    /* ── Inputs ───────────────────────────────────────────────── */
    .bf-input,
    .bf-select {
        width: 100%;
        padding: 5px 9px;
        font-size: 12px;
        font-family: inherit;
        color: #111827;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
        line-height: 1.4;
    }
    .bf-input::placeholder { color: #c4c9d4; }
    .bf-input:hover,
    .bf-select:hover  { border-color: #a5b4fc; background: #fff; }
    .bf-input:focus,
    .bf-select:focus  {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 2px rgba(99,102,241,.12);
    }
    .bf-select { cursor: pointer; appearance: none; }
    .bf-select-wrap { position: relative; }
    .bf-select-wrap::after {
        content: '';
        position: absolute;
        right: 9px;
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-top-color: #9ca3af;
        border-bottom: none;
        pointer-events: none;
    }

    /* ── Error Box ────────────────────────────────────────────── */
    .bf-errors {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 10px 12px;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .bf-errors i { font-size: 14px; color: #ef4444; flex-shrink: 0; margin-top: 1px; }
    .bf-errors-title { font-size: 11px; font-weight: 600; color: #b91c1c; margin-bottom: 3px; }
    .bf-errors ul  { list-style: disc; padding-left: 14px; }
    .bf-errors li  { font-size: 11px; color: #dc2626; line-height: 1.6; }

    /* ── Lots Container ───────────────────────────────────────── */
    .bf-lots-body {
        padding: 10px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* ── Lot Card ─────────────────────────────────────────────── */
    .bf-lot {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        overflow: hidden;
    }
    .bf-lot-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .75rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .bf-lot-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        background: #eef2ff;
        color: #4f46e5;
        letter-spacing: .03em;
        flex-shrink: 0;
    }
    .bf-lot-name-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 11.5px;
        font-family: inherit;
        color: #374151;
        outline: none;
    }
    .bf-lot-name-input::placeholder { color: #c4c9d4; }
    .bf-lot-del {
        font-size: 13px;
        color: #d1d5db;
        cursor: pointer;
        padding: 2px;
        border-radius: 4px;
        transition: color .15s, background .15s;
        border: none;
        background: none;
        line-height: 1;
    }
    .bf-lot-del:hover { color: #ef4444; background: #fef2f2; }

    .bf-lot-body {
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* ── Items Table ──────────────────────────────────────────── */
    .bf-items {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
        max-height: 300px;      /* adjust height */
        overflow-y: auto;
        overflow-x: auto;
    }
    .bf-items-head, 
    .bf-item-row{
        display: grid;
        min-width:1300px;   /* adjust as needed */
        grid-template-columns:  
            3.5fr   /* Item */
            .5fr    /* Unit */
            .8fr    /* Qty */
            1fr     /* Unit Cost */
            1fr     /* Amount */
            1fr     /* Brand and Specs */
            1fr     /* Remarks */
            50px;   /* Delete */
            ;
        padding: 5px 8px;
        background: #f3f4f6;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .bf-items-head span {
        font-size: 9.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .bf-item-row {
        display: grid;
        grid-template-columns:  
            3.5fr   /* Item */
            .5fr    /* Unit */
            .8fr    /* Qty */
            1fr     /* Unit Cost */
            1fr     /* Amount */
            1fr     /* Brand and Specs */
            1fr     /* Remarks */
            50px;   /* Delete */
            ;
        border-bottom: 1px solid #f3f4f6;
        align-items: center;
        border-bottom: 1px solid #f3f4f6;
    }
    .bf-items::-webkit-scrollbar {
        width: 8px;
    }
    .bf-items-total{
        display:flex;
        justify-content:flex-end;
        align-items:center;
        gap:10px;
        padding:10px 15px;
        border-top:1px solid #e5e7eb;
        background:#f8fafc;
        font-size:13px;
        font-weight:600;
    }

    .lot-grand-total{
        color:#2563eb;
        font-size:15px;
    }

    .bf-items::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .bf-items::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .bf-item-row:last-child { border-bottom: none; }
    .bf-item-row input {
        width: 100%;
        padding: 5px 8px;
        font-size: 11.5px;
        font-family: inherit;
        color: #111827;
        background: transparent;
        border: none;
        border-right: 1px solid #f3f4f6;
        outline: none;
        transition: background .1s;
    }
    .bf-item-row input:last-of-type { border-right: none; }
    .bf-item-row input:focus { background: #fafbff; }
    .bf-item-row input::placeholder { color: #d1d5db; }
    .bf-lot-del {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .2s;
    }

    .bf-lot-del:hover {
        background: #dc2626;
        color: #fff;
    }
    .bf-item-del:active {
        transform: scale(.95);
    }
    .bf-item-del i {
        font-size: 18px;
    }
    /* ── Buttons ──────────────────────────────────────────────── */
    .bf-btn-add-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 500;
        font-family: inherit;
        padding: 4px 9px;
        border-radius: 5px;
        cursor: pointer;
        border: 1px solid #c7d2fe;
        color: #4f46e5;
        background: #eef2ff;
        transition: background .15s, border-color .15s;
    }
    .bf-btn-add-item:hover { background: #e0e7ff; border-color: #a5b4fc; }
    .bf-btn-add-item i { font-size: 12px; }

    .bf-btn-add-lot {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        font-family: inherit;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        border: none;
        color: #fff;
        background: #6366f1;
        transition: background .15s;
        letter-spacing: .01em;
    }
    .bf-btn-add-lot:hover { background: #4f46e5; }
    .bf-btn-add-lot i { font-size: 13px; }

    /* ── Footer ───────────────────────────────────────────────── */
    .bf-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        padding-top: 2px;
    }
    .bf-btn-cancel {
        padding: 6px 16px;
        border-radius: 6px;
        background: #fff;
        border: 1px solid #d1d5db;
        font-size: 12px;
        font-weight: 500;
        font-family: inherit;
        color: #374151;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: background .15s, border-color .15s;
    }
    .bf-btn-cancel:hover { background: #f9fafb; border-color: #9ca3af; }

    .bf-btn-save {
        padding: 6px 18px;
        border-radius: 6px;
        background: #6366f1;
        border: none;
        font-size: 12px;
        font-weight: 600;
        font-family: inherit;
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background .15s;
        letter-spacing: .01em;
    }
    .bf-btn-save:hover { background: #4f46e5; }
    .bf-btn-save i { font-size: 13px; }
    .auto-expand {
        width: 100%;
        min-height: 38px;
        resize: none;
        overflow: hidden;
        line-height: 1.5;
    }


    .bf-loc-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    @media (max-width: 640px) { .bf-loc-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 400px) { .bf-loc-row { grid-template-columns: 1fr; } }

    .bf-select:disabled {
        color: var(--text-disabled, #c4c9d4);
        background: #f3f4f6;
        cursor: not-allowed;
        border-color: #e5e7eb;
    }
    .bf-select-wrap:has(.bf-select:disabled)::after {
        border-top-color: #d1d5db;
    }
</style>

<div class="bf-wrap">

    {{-- ── Validation Errors ──────────────────────────────────── --}}
    @if ($errors->any())
    <div class="bf-errors">
        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
        <div>
            <div class="bf-errors-title">Please correct the following errors:</div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif


    {{-- ── PROJECT INFORMATION ─────────────────────────────────── --}}
    <div class="bf-section">

        <div class="bf-section-head">
            <div class="bf-section-title">
                <i class="ti ti-clipboard-list" aria-hidden="true"></i>
                Project information
            </div>
        </div>

        {{-- Row 1: ID + Name --}}
        <div class="bf-body bf-grid-1-3" style="padding-bottom:0">

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-hash"></i> Project ID</label>
                <input class="bf-input" type="text" name="project_id"
                       value="{{ old('project_id', $project->project_id ?? '') }}"
                       placeholder="PRJ-2024-001">
            </div>

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-file-description"></i> Project name</label>
                <textarea
                    class="bf-input auto-expand"
                    name="project_name"
                    rows="1"
                    placeholder="Enter full project name"
                >{{ old('project_name', $project->project_name ?? '') }}</textarea>
            </div>

        </div>

        {{-- Row 2: Entity + ABC + Delivery + Bid Opening --}}
        <div class="bf-body bf-grid-4" style="padding-bottom:0">

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-building-bank"></i> Procuring entity / Agency</label>
                <input class="bf-input" type="text" name="procuring_entity"
                       value="{{ old('procuring_entity', $project->procuring_entity ?? '') }}"
                       placeholder="Agency or office">
            </div>

            <div class="bf-field">
                <label class="bf-label" style="font-size:8.5px;"><i class="ti ti-coin"></i> Approved Budget for the Contract (ABC)</label>
                <div class=" flex items-center gap-2">
                    <span class="text-slate-600 font-medium">₱</span>
                        <input
                            type="text"
                            id="approved_budget_contract_abc"
                            name="approved_budget_contract_abc"
                            class="bf-input currency"
                            value="{{ old('approved_budget_contract_abc', $project->approved_budget_contract_abc ?? '') }}"
                            placeholder="0.00" readonly
                        >
                </div>
            </div>

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-clock"></i> Delivery period <span style="font-weight:400;text-transform:none">(days)</span></label>
                <input class="bf-input" type="number" name="delivery_period"
                       value="{{ old('delivery_period', $project->delivery_period ?? '') }}"
                       placeholder="e.g. 30">
            </div>

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-calendar-event"></i> Bid opening</label>
                <input class="bf-input" type="date" name="date_of_bid_opening"
                       value="{{ old('date_of_bid_opening', $project->date_of_bid_opening ?? '') }}">
            </div>

        </div>

        {{-- Row 3: Status --}}
        <div class="bf-body bf-grid-auto">

            <div class="bf-field">
                <label class="bf-label"><i class="ti ti-toggle-right"></i> Status</label>
                <div class="bf-select-wrap">
                    <select class="bf-select" name="status">
                        @foreach(['Draft','For Review','Published','Awarded','Cancelled','Completed'] as $status)
                            <option value="{{ $status }}"
                                @selected(old('status', $project->status ?? 'Draft') == $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

    </div>


    {{-- ── LOTS ─────────────────────────────────────────────────── --}}
    <div class="bf-section">

        <div class="bf-section-head">
            <div class="bf-section-title">
                <i class="ti ti-layers-intersect" aria-hidden="true"></i>
                Lots
                <span class="bf-section-sub">Each lot has its own delivery location and items.</span>
            </div>
            <button type="button" id="addLot" class="bf-btn-add-lot">
                <i class="ti ti-plus" aria-hidden="true"></i> Add lot
            </button>
        </div>

        <div id="lotsContainer" class="bf-lots-body">

            @php
                $lots = old('lots');
                if (!$lots) {
                    if (isset($project) && $project->lots->count()) {
                        $lots = $project->lots;
                    } else {
                        $lots = [[]];
                    }
                }
            @endphp

            @foreach($lots as $index => $lot)
                @include('finance.bidding.partials._lot', ['index' => $index, 'lot' => $lot])
            @endforeach

        </div>

    </div>

    {{-- ── Footer Buttons ───────────────────────────────────────── --}}
    <div class="bf-footer">

        <a href="{{ route('bidding.index') }}" class="bf-btn-cancel">Cancel</a>

        <button type="submit" class="bf-btn-save">
            <i class="ti ti-device-floppy" aria-hidden="true"></i>
            Save project
        </button>

    </div>

</div>


{{-- ── Templates for JS ─────────────────────────────────────────── --}}

<template id="lot-template">
    @include('finance.bidding.partials._lot', ['index' => '__INDEX__', 'lot' => []])
</template>

<template id="item-template">
    @include('finance.bidding.partials._items', ['lotIndex' => '__LOTINDEX__', 'itemIndex' => '__ITEMINDEX__', 'item' => []])
</template>