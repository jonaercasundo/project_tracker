<x-mi_app>
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

            @foreach (['department' => 'Department', 'place' => 'Place', 'country' => 'Country'] as $field => $label)
                <div class="mb-4">
                    <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $budgetRequest->{$field}) }}"
                        class="w-full border rounded p-2" @required($field === 'department')>
                </div>
            @endforeach

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
</x-mi_app>
