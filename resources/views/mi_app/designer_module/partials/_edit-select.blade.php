<div>
    <label for="{{ $name }}" class="tx-label">{{ $label }} @if($required ?? false)<span class="tx-required">*</span>@endif</label>
    <div class="tx-select-wrap">
    <select id="{{ $name }}" name="{{ $name }}" @if($required ?? false) required @endif
        @if(isset($parentField)) data-parent-field="{{ $parentField }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}_error" @enderror
        class="tx-field @error($name) field-invalid @enderror">
        <option value="">Select {{ strtolower($label) }}</option>
        @foreach($options as $option)
            <option value="{{ $option->id }}" @if(isset($parentField)) data-parent-id="{{ $option->{$parentField} }}" @endif @selected((string) old($name, $product->{$name}) === (string) $option->id)>{{ $option->name }} ({{ $option->code }})</option>
        @endforeach
    </select>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </div>
    @error($name)<p id="{{ $name }}_error" class="tx-error">{{ $message }}</p>@enderror
</div>
