<div>
    <label for="{{ $name }}" class="{{ isset($unit) ? 'tx-dim-label' : 'tx-label' }}">{{ $label }} @if($required ?? false)<span class="tx-required">*</span>@endif</label>
    <div @if(isset($unit)) class="tx-dim-input-wrap" @endif>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $inputType ?? 'text' }}" value="{{ old($name, $product->{$name}) }}"
        @if($required ?? false) required @endif
        @if(($inputType ?? 'text') === 'number') min="0" step="0.01" @else maxlength="255" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}_error" @enderror
        class="tx-field @error($name) field-invalid @enderror">
        @if(isset($unit))<span class="tx-dim-unit">{{ $unit }}</span>@endif
    </div>
    @error($name)<p id="{{ $name }}_error" class="tx-error">{{ $message }}</p>@enderror
</div>
