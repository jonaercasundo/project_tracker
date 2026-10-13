@php
    $isEditing = isset($school) && $school->exists;
    $fieldGroups = [
        'School details' => [
            'school_id' => ['label' => 'School ID', 'required' => true, 'maxlength' => 255],
            'school_name' => ['label' => 'School name', 'required' => true, 'maxlength' => 255],
            'address' => ['label' => 'Address', 'required' => true, 'maxlength' => 255],
            'municipality' => ['label' => 'Municipality', 'maxlength' => 255],
            'division' => ['label' => 'Division', 'required' => true, 'maxlength' => 255],
            'region' => ['label' => 'Region', 'required' => true, 'maxlength' => 255],
        ],
        'Contact details' => [
            'contact_person' => ['label' => 'Contact person', 'maxlength' => 255],
            'contact' => ['label' => 'Contact', 'type' => 'tel', 'maxlength' => 255],
        ],
    ];
@endphp

<p class="text-xs text-slate-500">Fields marked <span class="font-bold text-red-600">*</span> are required.</p>

@foreach ($fieldGroups as $groupLabel => $fields)
    <fieldset class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <legend class="px-2 text-sm font-bold text-slate-900">{{ $groupLabel }}</legend>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            @foreach ($fields as $fieldName => $field)
                <div class="min-w-0">
                    <label for="{{ $fieldName }}" class="mb-1.5 block text-xs font-bold text-slate-600">
                        {{ $field['label'] }}
                        @if ($field['required'] ?? false)
                            <span class="text-red-600" aria-hidden="true">*</span>
                        @endif
                    </label>
                    <input
                        id="{{ $fieldName }}"
                        name="{{ $fieldName }}"
                        type="{{ $field['type'] ?? 'text' }}"
                        value="{{ $isEditing && $fieldName === 'school_id' ? $school->school_id : old($fieldName, $school?->{$fieldName} ?? '') }}"
                        @required($field['required'] ?? false)
                        @readonly($isEditing && $fieldName === 'school_id')
                        @if (isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                        @if (isset($field['inputmode'])) inputmode="{{ $field['inputmode'] }}" @endif
                        @if (isset($field['pattern'])) pattern="{{ $field['pattern'] }}" @endif
                        aria-invalid="{{ $errors->has($fieldName) ? 'true' : 'false' }}"
                        aria-describedby="{{ $fieldName }}-help {{ $fieldName }}-error"
                        class="w-full rounded-xl border-slate-200 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500 read-only:bg-slate-50 read-only:text-slate-500 {{ $errors->has($fieldName) ? 'border-red-400' : '' }}"
                    >
                    <p id="{{ $fieldName }}-help" class="mt-1 text-xs text-slate-500">{{ $isEditing && $fieldName === 'school_id' ? 'The School ID is permanent and cannot be changed.' : ($field['help'] ?? '') }}</p>
                    <x-input-error :messages="$errors->get($fieldName)" id="{{ $fieldName }}-error" class="mt-1" />
                </div>
            @endforeach
        </div>
    </fieldset>
@endforeach
