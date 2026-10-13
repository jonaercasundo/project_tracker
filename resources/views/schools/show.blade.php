<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600">School details</p>
                <h1 class="break-words text-2xl font-extrabold tracking-tight text-slate-900">{{ $school->school_name }}</h1>
                <p class="mt-2 break-words font-mono text-sm text-slate-500">{{ $school->school_id }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <a href="{{ route('schools.index', $listQuery) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Back to schools</a>
                <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Edit school</a>
            </div>
        </div>

        @include('schools._alerts')

        <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm sm:grid-cols-2">
            @foreach (['school_id' => 'School ID', 'school_name' => 'School name', 'address' => 'Address', 'municipality' => 'Municipality', 'division' => 'Division', 'region' => 'Region', 'project_id' => 'Project ID', 'project_no' => 'Project number', 'project' => 'Project', 'total_contract_price' => 'Total contract price', 'contact_person' => 'Contact person', 'contact' => 'Contact'] as $fieldName => $label)
                <div class="min-w-0 bg-white p-5">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                    <dd class="mt-2 break-words text-sm font-medium text-slate-900 {{ $fieldName === 'total_contract_price' ? 'tabular-nums' : '' }}">
                        {{ $fieldName === 'total_contract_price' && $school->{$fieldName} !== null ? number_format((float) $school->{$fieldName}, 2) : ($school->{$fieldName} ?? '—') }}
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
</x-project-app-layout>
