<div>
    <!-- We must ship. - Taylor Otwell -->
</div>
<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Project item · ID {{ $item->item_id }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Edit item</h1>
                <p class="mt-1 break-words text-sm text-slate-500">{{ $project->project_name }}</p>
            </div>
            <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'items']) }}" class="shrink-0 text-sm font-semibold text-blue-600 hover:text-blue-800">Back to Items</a>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold">Please correct the item information and try again.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.items.update', [$project, $item->item_id]) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @csrf
            @method('PUT')

            @foreach ($fields as $field)
                <div>
                    <label for="{{ $field['name'] }}" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                        {{ $field['label'] }}
                        @if ($field['name'] === 'item_name')
                            <span class="text-red-600">*</span>
                        @endif
                    </label>

                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4" maxlength="10000" @required($field['name'] === 'item_name') class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">{{ old($field['name'], $field['value']) }}</textarea>
                    @else
                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}" value="{{ old($field['name'], $field['value']) }}" @if ($field['type'] === 'number') min="0" max="9999999999999.99" step="0.01" @endif @if ($field['name'] === 'item_name') maxlength="255" required @else maxlength="255" @endif class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    @endif

                    @error($field['name'])
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'items']) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700">Save changes</button>
            </div>
        </form>
    </div>
</x-project-app-layout>
