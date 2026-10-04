@php
    $fieldValue = static function (string $key, mixed $default = ''): string {
        $value = old($key, $default);
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return is_scalar($value) ? (string) $value : '';
    };
    $formLots = session()->hasOldInput('hierarchy_present') ? old('lots', []) : ($biddingLots ?? [['lot_no' => 'Lot 1', 'country_code' => 'PH', 'addresses' => []]]);
    $formLots = is_array($formLots) ? $formLots : [];
    $legacyOriginals = [];
    foreach ($biddingLots ?? [] as $savedLot) {
        foreach ($savedLot['legacy_items'] ?? [] as $savedItem) {
            $legacyOriginals[$savedItem['id']] = $savedItem;
        }
    }
@endphp
<input type="hidden" name="hierarchy_present" value="1">
<div class="space-y-6">
    @if($errors->any())
        <div role="alert" tabindex="-1" data-bidding-errors class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct these fields before saving.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-semibold text-slate-900">Project information</h2><p class="mt-1 text-xs text-slate-500">Identifiers, budget and bidding schedule.</p></div>
        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex flex-col gap-1.5">
                <label for="bidding-project_code" class="text-xs font-semibold text-slate-600">Project code <span class="text-red-600" aria-hidden="true">*</span></label>
                <select id="bidding-project_code" name="project_code" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select project code</option>
                    @foreach(['SME', 'SFP', 'MT', 'Textbook', 'DCP'] as $code)<option value="{{ $code }}" @selected($fieldValue('project_code', $project->project_code ?? '') === $code)>{{ $code }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('project_code')" class="text-xs" />
            </div>
            @include('operation.bidding.partials._field', ['field' => ['key' => 'project_id', 'label' => 'Project ID', 'required' => true]])
            <div class="flex flex-col gap-1.5">
                <label for="bidding-status" class="text-xs font-semibold text-slate-600">Status</label>
                <select id="bidding-status" name="status" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach(['Draft', 'For Review', 'Published', 'Awarded', 'Cancelled', 'Completed'] as $status)<option value="{{ $status }}" @selected($fieldValue('status', $project->status ?? 'Draft') === $status)>{{ $status }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="text-xs" />
            </div>
            @include('operation.bidding.partials._field', ['field' => ['key' => 'project_name', 'label' => 'Project name', 'type' => 'textarea', 'required' => true, 'wide' => true]])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'procuring_entity', 'label' => 'Procuring entity / agency']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'approved_budget_contract_abc', 'label' => 'Approved Budget for the Contract (PHP)', 'type' => 'number']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'delivery_period', 'label' => 'Delivery period (calendar days)', 'type' => 'number', 'step' => '1', 'max' => 36500]])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'date_of_pre_bid_conference', 'label' => 'Pre-bid conference', 'type' => 'date']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'date_of_bid_opening', 'label' => 'Bid opening', 'type' => 'date']])
        </div>
    </section>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Lots and delivery destinations</h2><p class="mt-1 text-xs text-slate-500">Add addresses, key stages and item quantities within each lot.</p></div><button type="button" data-bidding-action="add-lot" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">+ Add lot</button></div>
        <div data-bidding-lots data-collection="lots" data-name-prefix="lots" class="space-y-4">
            @foreach($formLots as $index => $lot)
                @continue(!is_array($lot))
                @include('operation.bidding.partials._lot', ['index' => $index, 'lot' => is_array($lot) ? $lot : [], 'namePrefix' => 'lots['.$index.']', 'dotPrefix' => 'lots.'.$index, 'uid' => 'lot-'.$index])
            @endforeach
        </div>
        <p data-bidding-empty-lots @if(count($formLots)) hidden @endif class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Add a lot to continue.</p>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 px-5 py-4"><div><p class="text-xs font-semibold text-blue-900">Calculated item total</p><p class="mt-1 text-xs text-blue-700">Quantity x unit cost across all lots. The approved budget stays separate.</p></div><p class="font-mono text-xl font-semibold tabular-nums text-blue-900">PHP <span data-bidding-calculated-total>{{ $calculatedTotal ?? '0.00' }}</span></p></div>
        <p data-bidding-feedback role="status" aria-live="polite" class="text-sm text-red-700"></p>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-semibold text-slate-900">Document control</h2></div>
        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
            @include('operation.bidding.partials._field', ['field' => ['key' => 'prepared_by', 'label' => 'Prepared by']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'prepared_date', 'label' => 'Prepared date', 'type' => 'date']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'verified_by', 'label' => 'Verified by']])
            @include('operation.bidding.partials._field', ['field' => ['key' => 'notes_special_condition', 'label' => 'Notes / special conditions', 'type' => 'textarea', 'max' => 10000, 'wide' => true, 'rows' => 3]])
        </div>
    </section>
    <input type="hidden" name="hierarchy_complete" value="1">
    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5"><span data-bidding-save-status role="status" class="text-xs text-slate-500"></span><a href="{{ route($biddingRoutePrefix.'.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a><button type="submit" data-bidding-save class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60">{{ isset($project) ? 'Save changes' : 'Save bidding document' }}</button></div>
</div>
<template data-bidding-template="lot">@include('operation.bidding.partials._lot', ['index' => '__INDEX__', 'lot' => [], 'namePrefix' => '__PREFIX__', 'dotPrefix' => '__DOT__', 'uid' => '__UID__'])</template>
<template data-bidding-template="address">@include('operation.bidding.partials._address', ['index' => '__INDEX__', 'address' => [], 'namePrefix' => '__PREFIX__', 'dotPrefix' => '__DOT__', 'uid' => '__UID__'])</template>
<template data-bidding-template="stage">@include('operation.bidding.partials._keystage', ['index' => '__INDEX__', 'stage' => [], 'namePrefix' => '__PREFIX__', 'dotPrefix' => '__DOT__', 'uid' => '__UID__'])</template>
<template data-bidding-template="item">@include('operation.bidding.partials._items', ['index' => '__INDEX__', 'item' => [], 'legacy' => false, 'namePrefix' => '__PREFIX__', 'dotPrefix' => '__DOT__', 'uid' => '__UID__'])</template>
