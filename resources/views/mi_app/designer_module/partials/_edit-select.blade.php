<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }} @if($required ?? false)<span class="text-red-600">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" @if($required ?? false) required @endif
        @if(isset($parentField)) data-parent-field="{{ $parentField }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}_error" @enderror
        class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-blue-600 focus:ring-blue-600">
        <option value="">Select {{ strtolower($label) }}</option>
        @foreach($options as $option)
            <option value="{{ $option->id }}" @if(isset($parentField)) data-parent-id="{{ $option->{$parentField} }}" @endif @selected((string) old($name, $product->{$name}) === (string) $option->id)>{{ $option->name }} ({{ $option->code }})</option>
        @endforeach
    </select>
    @error($name)<p id="{{ $name }}_error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
