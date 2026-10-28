<div>
    <!-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi -->
</div>
<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-800 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">Project lot · ID {{ $lot->lot_id }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Edit lot</h1>
                <p class="mt-1 break-words text-sm text-slate-500">{{ $project->project_name }}</p>
            </div>
            <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'lots']) }}" class="shrink-0 text-sm font-semibold text-blue-600 hover:text-blue-800">Back to Lots</a>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold">Please correct the lot information and try again.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.lots.update', [$project, $lot->lot_id]) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="lot_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Lot number <span class="text-red-600">*</span></label>
                <input id="lot_name" name="lot_name" type="text" value="{{ old('lot_name', $lot->lot_name) }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                @error('lot_name')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="contract_no" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Contract number</label>
                <input id="contract_no" name="contract_no" type="text" value="{{ old('contract_no', $lot->contract_no) }}" maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                @error('contract_no')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="contract_price" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Contract price <span class="text-red-600">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">&#8369;</span>
                    <input id="contract_price" name="contract_price" type="number" min="0" max="9999999999999.99" step="0.01" required value="{{ old('contract_price', $lot->contract_price !== null ? number_format((float) $lot->contract_price, 2, '.', '') : '') }}" class="w-full rounded-xl border border-slate-200 py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                </div>
                @error('contract_price')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <fieldset>
                <legend class="text-xs font-bold uppercase tracking-wide text-slate-600">Assigned keystages</legend>
                <p class="mt-1 text-xs text-slate-400">Select the keystages that belong to this lot. Keystages assigned to another lot will move here when selected.</p>
                @error('keystage_ids')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror

                @if ($keystages->isNotEmpty())
                    @php
                        $selectedKeystageIds = old('keystage_ids', $assignedKeystageIds);
                        $selectedKeystageIds = is_array($selectedKeystageIds)
                            ? array_map('intval', $selectedKeystageIds)
                            : $assignedKeystageIds;
                    @endphp
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($keystages as $keystage)
                            <label for="keystage-{{ $keystage->keystage_id }}" class="flex min-w-0 items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-blue-300 hover:bg-blue-50/40">
                                <input id="keystage-{{ $keystage->keystage_id }}" type="checkbox" name="keystage_ids[]" value="{{ $keystage->keystage_id }}" @checked(in_array((int) $keystage->keystage_id, $selectedKeystageIds, true)) class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="min-w-0 text-sm">
                                    <span class="block font-semibold text-slate-800">Keystage {{ $keystage->keystage_num ?? $keystage->keystage_id }}</span>
                                    <span class="mt-0.5 block break-words text-xs text-slate-500">{{ $keystage->description ?: 'No description' }} · Currently assigned to {{ $keystage->lot?->lot_name ?? 'No lot' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No keystages are available for this project.</p>
                @endif
            </fieldset>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('projects.show', ['project' => $project->project_id, 'tab' => 'lots']) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700">Save changes</button>
            </div>
        </form>
    </div>
</x-project-app-layout>
