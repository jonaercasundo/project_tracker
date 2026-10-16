<x-project_app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">Edit project</h1>
        <p class="mt-1 text-xs font-medium text-slate-500">Update contract details and implementation dates.</p>
    </x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('projects.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Back to projects</a>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please correct these fields before saving.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="project-ref-no" class="text-xs font-semibold text-slate-600">PhilGEPS Ref No</label>
                    <input id="project-ref-no" name="ref_no" value="{{ old('ref_no', $project->ref_no) }}" required maxlength="50" class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('ref_no')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-number" class="text-xs font-semibold text-slate-600">Project No.</label>
                    <input id="project-number" name="contract_number" value="{{ old('contract_number', $project->project_code) }}" maxlength="255" class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('contract_number')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="project-name" class="text-xs font-semibold text-slate-600">Project name</label>
                    <input id="project-name" name="project_name" value="{{ old('project_name', $project->project_name) }}" required maxlength="255" class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('project_name')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="project-agency" class="text-xs font-semibold text-slate-600">Agency</label>
                    <input id="project-agency" name="agency" value="{{ old('agency', $project->agency) }}" required maxlength="255" class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('agency')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-contract-amount" class="text-xs font-semibold text-slate-600">Contract amount (PHP)</label>
                    <input id="project-contract-amount" name="contract_amount" type="number" min="0.01" step="0.01" value="{{ old('contract_amount', $project->contract_amount) }}" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('contract_amount')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-abc" class="text-xs font-semibold text-slate-600">ABC (PHP)</label>
                    <input id="project-abc" name="ABC" type="number" min="0.01" step="0.01" value="{{ old('ABC', $project->ABC) }}" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('ABC')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-start-date" class="text-xs font-semibold text-slate-600">Notice to Proceed Start Date</label>
                    <input id="project-start-date" name="start_date" type="date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('start_date')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-end-date" class="text-xs font-semibold text-slate-600">Notice to Proceed End Date</label>
                    <input id="project-end-date" name="end_date" type="date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('end_date')" class="text-xs" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="project-status" class="text-xs font-semibold text-slate-600">Status</label>
                    <select id="project-status" name="status" required class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($projectStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="text-xs" />
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('projects.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2">Save changes</button>
            </div>
        </form>
    </div>
</x-project_app-layout>
