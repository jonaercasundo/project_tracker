@php
    $fieldKey = $field['key'];
    $fieldType = $field['type'] ?? 'text';
    $fieldId = 'bidding-'.$fieldKey;
    $fieldDefault = $fieldKey === 'date_of_pre_bid_conference' ? ($project->pre_bid_conf ?? '') : ($project->{$fieldKey} ?? '');
@endphp
<div class="flex flex-col gap-1.5 {{ $field['wide'] ?? false ? 'sm:col-span-2' : '' }}">
    <label for="{{ $fieldId }}" class="text-xs font-semibold text-slate-600">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    @if($fieldType === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $fieldKey }}" rows="{{ $field['rows'] ?? 2 }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false) class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">{{ $fieldValue($fieldKey, $fieldDefault) }}</textarea>
    @else
        <input id="{{ $fieldId }}" name="{{ $fieldKey }}" type="{{ $fieldType }}" value="{{ $fieldValue($fieldKey, $fieldDefault) }}" @required($field['required'] ?? false) @if($fieldType === 'number') min="0" max="{{ $field['max'] ?? '9999999999999.99' }}" step="{{ $field['step'] ?? '0.01' }}" @elseif($fieldType === 'text') maxlength="{{ $field['max'] ?? 255 }}" @endif class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
    @endif
    <x-input-error :messages="$errors->get($fieldKey)" class="text-xs" />
</div>
