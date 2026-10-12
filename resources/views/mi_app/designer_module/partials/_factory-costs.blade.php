@php
    $removedFactoryCostIds = array_map('intval', (array) old('remove_factory_cost_ids', []));
    $savedFactoryCosts = $product->factoryCosts->map(fn (\App\Models\MI_Product_Factory_Cost $cost): array => [
        'id' => $cost->id,
        'factory_name' => $cost->factory_name,
        'factory_cost' => $cost->factory_cost,
        'currency' => $cost->currency,
        'effective_date' => $cost->effective_date?->format('Y-m-d'),
        'remarks' => $cost->remarks,
        'is_primary' => $cost->is_primary,
    ])->all();
    $hasFactoryCostInput = old('factory_costs_present') || session()->hasOldInput('factory_costs') || session()->hasOldInput('remove_factory_cost_ids');
    $factoryCosts = $hasFactoryCostInput ? old('factory_costs', []) : $savedFactoryCosts;
    $factoryCosts = array_map(
        fn (array $cost): array => array_map(fn (mixed $value): mixed => is_scalar($value) || $value === null ? $value : '', $cost),
        array_filter((array) $factoryCosts, 'is_array'),
    );
    foreach ($savedFactoryCosts as $savedFactoryCost) {
        if (in_array($savedFactoryCost['id'], $removedFactoryCostIds, true) && !in_array($savedFactoryCost['id'], array_column($factoryCosts, 'id'))) {
            $factoryCosts[] = $savedFactoryCost;
        }
    }
@endphp

<style>
    .tx-product-edit .tx-factory-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; overflow: hidden; border: 1px solid var(--tx-line); border-radius: 12px; background: var(--tx-line); }
    .tx-product-edit .tx-factory-summary > div { min-width: 0; padding: 16px; background: var(--tx-surface-soft); }
    .tx-product-edit .tx-factory-summary > div:last-child { background: var(--tx-primary-soft); }
    .tx-product-edit .tx-factory-summary dt { color: var(--tx-ink-soft); font-size: 10px; line-height: 1.5; }
    .tx-product-edit .tx-factory-summary dd { margin: 8px 0 0; overflow-wrap: anywhere; color: var(--tx-ink); font-family: var(--tx-font-mono); font-size: 15px; font-weight: 600; }
    .tx-product-edit .tx-factory-summary > div:last-child dd { color: var(--tx-primary); }
    .tx-product-edit .tx-factory-rows { display: grid; gap: 16px; }
    .tx-product-edit .tx-factory-row { min-width: 0; border: 1px solid var(--tx-line); border-radius: 12px; background: var(--tx-surface); }
    .tx-product-edit .tx-factory-row[data-primary="true"] { border-color: var(--tx-primary); }
    .tx-product-edit .tx-factory-row[data-removed="true"] { border-color: var(--tx-danger); }
    .tx-product-edit .tx-factory-row[data-removed="true"] .tx-factory-fields { opacity: .55; }
    .tx-product-edit .tx-factory-row-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--tx-line); background: var(--tx-surface-soft); border-radius: 12px 12px 0 0; }
    .tx-product-edit .tx-factory-fields { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; padding: 16px; }
    .tx-product-edit .tx-factory-fields > div { min-width: 0; }
    .tx-product-edit .tx-factory-row-head .tx-btn-small { margin: 0; }
    .tx-product-edit .tx-factory-row .tx-edit-removal-note { margin: 0 16px 16px; }
    .tx-product-edit #add_factory_cost:disabled { cursor: not-allowed; opacity: .5; }
    @media (min-width: 650px) {
        .tx-product-edit .tx-factory-summary { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .tx-product-edit .tx-factory-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<section id="section_factory_costs" class="tx-card lvl-2" aria-labelledby="factory_costs_heading" data-factory-costs data-max-entries="100">
    <div class="tx-card-head">
        <span class="tx-card-icon">04</span>
        <div><h2 id="factory_costs_heading">Factory Cost Management</h2><p>Compare supplier quotes and choose one primary cost</p></div>
        <div class="tx-edit-heading-actions"><button type="button" id="add_factory_cost" class="tx-btn-small">+ Add Factory Cost</button></div>
    </div>
    <div class="tx-card-body">
        <input type="hidden" name="factory_costs_present" value="1">
        <p class="tx-hint">All costs are in PHP. Changes and removals apply when you save the product. Add up to 100 factory costs.</p>
        @foreach(['factory_costs', 'remove_factory_cost_ids', 'remove_factory_cost_ids.*'] as $field)
            @foreach($errors->get($field) as $messages)
                @foreach((array) $messages as $message)<p class="tx-error">{{ $message }}</p>@endforeach
            @endforeach
        @endforeach
        <dl class="tx-factory-summary" aria-label="Factory cost comparison" aria-live="polite" aria-atomic="true">
            @foreach(['lowest' => 'Lowest Factory Cost', 'highest' => 'Highest Factory Cost', 'average' => 'Average Factory Cost', 'primary' => 'Primary Factory Cost'] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-factory-summary="{{ $key }}">&mdash;</dd></div>
            @endforeach
        </dl>
        <p class="tx-hint" data-factory-summary-note>Comparisons use valid costs, including zero. Average is rounded to the nearest cent.</p>
        <div class="tx-factory-rows" data-factory-cost-rows>
            @foreach($factoryCosts as $index => $cost)
                @include('mi_app.designer_module.partials._factory-cost-row', ['index' => $index, 'cost' => $cost, 'removed' => in_array((int) ($cost['id'] ?? 0), $removedFactoryCostIds, true)])
            @endforeach
        </div>
        <div data-factory-empty class="tx-edit-empty" @if(count($factoryCosts)) hidden @endif><strong>No factory costs yet</strong><p>Use Add Factory Cost to compare supplier quotes. Your first entry becomes primary.</p></div>
        <p data-factory-feedback class="tx-hint" role="status"></p>
        <noscript><p class="tx-hint">Enable JavaScript to add costs, change the primary selection, and see live comparisons.</p></noscript>
    </div>
    <template id="factory_cost_template">@include('mi_app.designer_module.partials._factory-cost-row', ['index' => '__INDEX__', 'cost' => [], 'removed' => false])</template>
</section>
