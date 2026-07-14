
{{-- ============================================================
     finance/bidding/partials/_lot.blade.php
     Variables: $index (int|string), $lot (array|Model)
     ============================================================ --}}

@php
    $lotData  = is_array($lot) ? $lot : $lot->toArray();
    $items    = $lotData['items'] ?? [[], []]; // default 2 blank rows
    $lotName  = old("lots.{$index}.lot_name",  $lotData['lot_name']  ?? '');
    $location = old("lots.{$index}.delivery_location", $lotData['delivery_location'] ?? '');
@endphp

<div class="bf-lot" id="lot-{{ $index }}" data-lot-index="{{ $index }}">

    {{-- ── Lot Header ──────────────────────────────────────── --}}
    <div class="bf-lot-head">

        <span class="bf-lot-badge">Lot {{ is_numeric($index) ? $index + 1 : $index }}</span>
        <input
            type="hidden"
            name="lots[{{ $index }}][lot_no]"
            value="Lot {{ is_numeric($index) ? $index + 1 : $index }}">
        <button type="button" class="bf-lot-del" aria-label="Remove lot" onclick="this.closest('.bf-lot').remove()">
            <i class="ti ti-trash" aria-hidden="true">X</i>
        </button>

    </div>

    {{-- ── Lot Body ─────────────────────────────────────────── --}}
    <div class="bf-lot-body">
        
        <div class="bf-loc-row">
            {{-- Country --}}
            <div class="bf-field">
                <label class="bf-label" for="country_{{ $index }}">
                    <i class="ti ti-flag"></i> Country
                </label>
                <div class="bf-select-wrap">
                    <select
                        class="bf-select country"
                        id="country_{{ $index }}"
                        name="lots[{{ $index }}][country_code]">

                        <option value="PH" selected>Philippines</option>

                    </select>
                </div>
            </div>

            <div class="bf-field">
                <label class="bf-label" for="region_{{ $index }}">
                    <i class="ti ti-map" aria-hidden="true"></i> Region
                </label>
                <div class="bf-select-wrap">
                    <select class="bf-select region" id="region_{{ $index }}"
                            name="lots[{{ $index }}][region_code]"
                            data-lot="{{ $index }}">
                        <option value="">— Region —</option>
                    </select>
                </div>
            </div>

            <div class="bf-field">
                <label class="bf-label" for="province_{{ $index }}">
                    <i class="ti ti-map-2" aria-hidden="true"></i> Province
                </label>
                <div class="bf-select-wrap">
                    <select class="bf-select province" id="province_{{ $index }}"
                            name="lots[{{ $index }}][province_code]"
                            data-lot="{{ $index }}" disabled>
                        <option value="">— Province —</option>
                    </select>
                </div>
            </div>

            <div class="bf-field">
                <label class="bf-label" for="city_{{ $index }}">
                    <i class="ti ti-building-community" aria-hidden="true"></i> City / Municipality
                </label>
                <div class="bf-select-wrap">
                    <select class="bf-select city" id="city_{{ $index }}"
                            name="lots[{{ $index }}][city_code]"
                            data-lot="{{ $index }}" disabled>
                        <option value="">— City / Municipality —</option>
                    </select>
                </div>
            </div>

            <div class="bf-field">
                <label class="bf-label" for="barangay_{{ $index }}">
                    <i class="ti ti-map-pin" aria-hidden="true"></i> Barangay
                </label>
                <div class="bf-select-wrap">
                    <select class="bf-select barangay" id="barangay_{{ $index }}"
                            name="lots[{{ $index }}][barangay_code]"
                            data-lot="{{ $index }}" disabled>
                        <option value="">— Barangay —</option>
                    </select>
                </div>
            </div>
            {{-- Delivery Address --}}
            <div class="bf-field mt-3">
                <label class="bf-label" for="delivery_address_{{ $index }}">
                    <i class="ti ti-home"></i> Address
                </label>

                <textarea
                    id="delivery_address_{{ $index }}"
                    name="lots[{{ $index }}][delivery_address]"
                    class="bf-input auto-expand"
                    rows="2"
                    placeholder="House No., Street, Subdivision, Landmark, etc.">{{ old("lots.$index.delivery_address", $lotData['delivery_address'] ?? '') }}</textarea>
            </div>
        </div>

        {{-- Items table --}}
        <div class="bf-items" id="items-{{ $index }}">

            <div class="bf-items-head">
                <span>Item description</span>
                <span>Unit</span>
                <span>Qty</span>
                <span>Total Amount (PHP)</span>
                <span>Brand / Specs</span>
                <span>Remarks</span>
                <span></span>
            </div>

            @foreach($items as $itemIndex => $item)
                @include('finance.bidding.partials._items', [
                    'lotIndex'  => $index,
                    'itemIndex' => $itemIndex,
                    'item'      => $item,
                ])
            @endforeach

        </div>
        <div class="bf-items-total">
            <span>Total Amount</span>

            <strong>
                ₱ <span class="lot-grand-total">0.00</span>
            </strong>
        </div>
        {{-- Add item --}}
        <button type="button"
                class="bf-btn-add-item"
                onclick="addItem(this, '{{ $index }}')">
            <i class="ti ti-row-insert-bottom" aria-hidden="true"></i>
            Add item
        </button>

    </div>

</div>