<x-mi_app>
    @php
        $countries = ['Philippines', 'United States', 'Canada', 'United Kingdom', 'Japan', 'China', 'South Korea', 'Singapore', 'Australia', 'Vietnam'];
        $storedCountry = (string) $budgetRequest->country;
        $hasOldCountry = session()->hasOldInput('country');
        $requestedCountry = (string) old('country', $storedCountry);
        $countrySelection = in_array($requestedCountry, [...$countries, 'Other'], true)
            ? $requestedCountry
            : ($requestedCountry !== '' ? 'Other' : '');
        $customCountry = $countrySelection === 'Other'
            ? old('custom_country', $hasOldCountry
                ? ($requestedCountry !== 'Other' ? $requestedCountry : '')
                : $storedCountry)
            : '';
    @endphp
    <div class="max-w-4xl mx-auto py-6">
        <h1 class="text-xl font-semibold mb-4">Edit {{ $budgetRequest->control_id }}</h1>

        @if ($errors->any())
            <ul class="text-red-700 mb-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('budget_requests.update', $budgetRequest) }}">
            @csrf
            @method('PUT')

            @foreach (['department' => 'Department', 'place' => 'Place'] as $field => $label)
                <div class="mb-4">
                    <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $budgetRequest->{$field}) }}"
                        class="w-full border rounded p-2" @required($field === 'department')>
                </div>
            @endforeach

            <div class="mb-4">
                <label for="country" class="block text-sm font-medium">Country</label>
                <select id="country" name="country" class="w-full rounded border p-2">
                    <option value="">Select country</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @selected($countrySelection === $country)>{{ $country }}</option>
                    @endforeach
                    <option value="Other" @selected($countrySelection === 'Other')>Others — Specify Country</option>
                </select>
                @error('country')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror

                <div id="custom-country-wrap" class="mt-2" @if($countrySelection !== 'Other') hidden @endif>
                    <label for="custom_country" class="block text-sm font-medium">Specify Country <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="custom_country" name="custom_country" type="text" maxlength="100" value="{{ $customCountry }}"
                        @if($countrySelection === 'Other') required @else disabled @endif
                        aria-describedby="custom-country-error" class="w-full rounded border p-2">
                    <p id="custom-country-error" class="mt-1 text-sm text-red-700" @if(!$errors->has('custom_country')) hidden @endif>
                        {{ $errors->first('custom_country') ?: 'Please specify a country.' }}
                    </p>
                </div>
            </div>

            @foreach (['travel_date_from' => 'Travel date from', 'travel_date_to' => 'Travel date to'] as $field => $label)
                <div class="mb-4">
                    <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="date"
                        value="{{ old($field, $budgetRequest->{$field}?->format('Y-m-d')) }}" class="w-full border rounded p-2">
                </div>
            @endforeach

            @foreach (['objectives' => 'Objectives', 'remarks' => 'Remarks'] as $field => $label)
                <div class="mb-4">
                    <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                    <textarea id="{{ $field }}" name="{{ $field }}" class="w-full border rounded p-2">{{ old($field, $budgetRequest->{$field}) }}</textarea>
                </div>
            @endforeach

            <button class="bg-blue-600 text-white px-4 py-2 rounded">Save changes</button>
            <a href="{{ route('budget_requests.show', $budgetRequest) }}" class="text-blue-600 underline">Cancel</a>
        </form>
    </div>
    <script>
        const countrySelect = document.getElementById('country');
        const customCountryWrap = document.getElementById('custom-country-wrap');
        const customCountryInput = document.getElementById('custom_country');
        const customCountryError = document.getElementById('custom-country-error');

        countrySelect.addEventListener('change', () => {
            const isOther = countrySelect.value === 'Other';
            customCountryWrap.hidden = !isOther;
            customCountryInput.required = isOther;
            customCountryInput.disabled = !isOther;
            if (!isOther) {
                customCountryInput.value = '';
                customCountryInput.setCustomValidity('');
                customCountryError.hidden = true;
            }
        });
        customCountryInput.addEventListener('input', () => {
            customCountryInput.setCustomValidity('');
            if (customCountryInput.value.trim() !== '') customCountryError.hidden = true;
        });
        customCountryInput.addEventListener('invalid', () => {
            customCountryError.hidden = false;
        });
        customCountryInput.form.addEventListener('submit', (event) => {
            if (countrySelect.value !== 'Other') return;
            customCountryInput.value = customCountryInput.value.trim();
            if (customCountryInput.value === '') {
                customCountryInput.setCustomValidity('Please specify a country.');
                customCountryError.hidden = false;
                event.preventDefault();
                customCountryInput.focus();
            }
        });
    </script>
</x-mi_app>
