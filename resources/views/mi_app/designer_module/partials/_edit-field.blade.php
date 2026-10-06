<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }} @if($required ?? false)<span class="text-red-600">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $inputType ?? 'text' }}" value="{{ old($name, $product->{$name}) }}"
        @if($required ?? false) required @endif
        @if(($inputType ?? 'text') === 'number') min="0" step="0.01" @else maxlength="255" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}_error" @enderror
        class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-blue-600 focus:ring-blue-600">
    @error($name)<p id="{{ $name }}_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
