@php
    $fieldPrefix = 'factory_costs.'.$index;
    $idPrefix = 'factory_cost_'.$index;
    $isPrimary = !$removed && in_array($cost['is_primary'] ?? false, [true, 1, '1'], true);
@endphp
<article class="tx-factory-row" data-factory-cost-row data-factory-cost-index="{{ $index }}" data-factory-cost-id="{{ $cost['id'] ?? '' }}" data-primary="{{ $isPrimary ? 'true' : 'false' }}" data-removed="{{ $removed ? 'true' : 'false' }}">
    <div class="tx-factory-row-head">
        <label class="tx-edit-choice" for="{{ $idPrefix }}_primary"><input id="{{ $idPrefix }}_primary" type="radio" name="factory_cost_primary" value="{{ $index }}" data-factory-primary @checked($isPrimary) @disabled($removed)> <span data-factory-primary-label>{{ $isPrimary ? 'Primary factory cost' : 'Set as Primary' }}</span></label>
        @if(!empty($cost['id']))
            <label class="tx-edit-choice tx-edit-danger"><input type="checkbox" name="remove_factory_cost_ids[]" value="{{ $cost['id'] }}" data-remove-saved-factory @checked($removed)> Remove</label>
        @else
            <button type="button" data-remove-new-factory class="tx-btn-small tx-edit-danger">Remove</button>
        @endif
    </div>
    <div class="tx-factory-fields">
        @if(!empty($cost['id']))<input type="hidden" name="factory_costs[{{ $index }}][id]" value="{{ $cost['id'] }}" data-factory-field @disabled($removed)>@endif
        <input type="hidden" name="factory_costs[{{ $index }}][currency]" value="PHP" data-factory-field @disabled($removed)>
        <input type="hidden" name="factory_costs[{{ $index }}][is_primary]" value="{{ $isPrimary ? '1' : '0' }}" data-factory-primary-value data-factory-field @disabled($removed)>
        <div>
            <label for="{{ $idPrefix }}_name" class="tx-label">Factory / Supplier Name <span class="tx-required">*</span></label>
            <input id="{{ $idPrefix }}_name" type="text" name="factory_costs[{{ $index }}][factory_name]" value="{{ $cost['factory_name'] ?? '' }}" required maxlength="255" data-factory-name data-factory-field @disabled($removed) class="tx-field @error($fieldPrefix.'.factory_name') field-invalid @enderror" @error($fieldPrefix.'.factory_name') aria-invalid="true" aria-describedby="{{ $idPrefix }}_name_error" @enderror>
            @error($fieldPrefix.'.factory_name')<p id="{{ $idPrefix }}_name_error" class="tx-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="{{ $idPrefix }}_amount" class="tx-label">Factory Cost (PHP) <span class="tx-required">*</span></label>
            <input id="{{ $idPrefix }}_amount" type="text" inputmode="decimal" name="factory_costs[{{ $index }}][factory_cost]" value="{{ $cost['factory_cost'] ?? '' }}" required maxlength="16" pattern="[0-9]{1,13}(\.[0-9]{1,2})?" placeholder="0.00" data-factory-amount data-factory-field @disabled($removed) class="tx-field tx-mono @error($fieldPrefix.'.factory_cost') field-invalid @enderror" aria-describedby="{{ $idPrefix }}_amount_hint {{ $idPrefix }}_amount_validation @error($fieldPrefix.'.factory_cost') {{ $idPrefix }}_amount_error @enderror" @error($fieldPrefix.'.factory_cost') aria-invalid="true" @enderror>
            <p id="{{ $idPrefix }}_amount_hint" class="tx-hint">Enter a non-negative amount with up to 2 decimal places, without commas.</p>
            <p id="{{ $idPrefix }}_amount_validation" data-factory-amount-error class="tx-error" hidden>Enter a valid PHP amount from 0 to 9,999,999,999,999.99, with up to 2 decimal places.</p>
            @error($fieldPrefix.'.factory_cost')<p id="{{ $idPrefix }}_amount_error" class="tx-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="{{ $idPrefix }}_date" class="tx-label">Effective Date <span class="tx-hint">(optional)</span></label>
            <input id="{{ $idPrefix }}_date" type="date" name="factory_costs[{{ $index }}][effective_date]" value="{{ $cost['effective_date'] ?? '' }}" data-factory-field @disabled($removed) class="tx-field @error($fieldPrefix.'.effective_date') field-invalid @enderror" @error($fieldPrefix.'.effective_date') aria-invalid="true" aria-describedby="{{ $idPrefix }}_date_error" @enderror>
            @error($fieldPrefix.'.effective_date')<p id="{{ $idPrefix }}_date_error" class="tx-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="{{ $idPrefix }}_remarks" class="tx-label">Remarks <span class="tx-hint">(optional)</span></label>
            <textarea id="{{ $idPrefix }}_remarks" name="factory_costs[{{ $index }}][remarks]" rows="2" maxlength="2000" data-factory-field @disabled($removed) class="tx-field @error($fieldPrefix.'.remarks') field-invalid @enderror" @error($fieldPrefix.'.remarks') aria-invalid="true" aria-describedby="{{ $idPrefix }}_remarks_error" @enderror>{{ $cost['remarks'] ?? '' }}</textarea>
            @error($fieldPrefix.'.remarks')<p id="{{ $idPrefix }}_remarks_error" class="tx-error">{{ $message }}</p>@enderror
        </div>
        @foreach(['id', 'currency', 'is_primary'] as $field)
            @error($fieldPrefix.'.'.$field)<p class="tx-error tx-edit-span-all">{{ $message }}</p>@enderror
        @endforeach
    </div>
    <p data-factory-removal-note class="tx-edit-removal-note" @if(!$removed) hidden @endif>Marked for removal when you save. Uncheck Remove to keep this factory cost.</p>
</article>
