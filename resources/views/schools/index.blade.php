<x-project-app-layout>
    <div class="min-w-0 space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">School Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage school records, contacts, and project details.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('schools.import', $listQuery) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Import Schools</a>
                <a href="{{ route('schools.create', $listQuery) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    Add New School
                </a>
            </div>
        </div>

        @include('schools._alerts')

        <form action="{{ route('schools.index') }}" method="GET" x-data="{ submitting: false }" x-on:submit="submitting = true" :aria-busy="submitting" class="grid grid-cols-1 items-end gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
            <div class="sm:col-span-2 xl:col-span-4">
                <label for="school-search" class="mb-1.5 block text-xs font-bold text-slate-600">Search schools</label>
                <input id="school-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="School ID, name, project, address, or contact..." class="w-full rounded-xl border-slate-200 py-2 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
            </div>
            @foreach (['municipality' => $municipalities, 'division' => $divisions, 'region' => $regions] as $filterName => $options)
                <div>
                    <label for="school-{{ $filterName }}" class="mb-1.5 block text-xs font-bold text-slate-600">{{ ucfirst($filterName) }}</label>
                    <select id="school-{{ $filterName }}" name="{{ $filterName }}" class="w-full rounded-xl border-slate-200 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All {{ $filterName === 'municipality' ? 'municipalities' : $filterName . 's' }}</option>
                        @foreach ($options as $option)
                            <option value="{{ $option }}" @selected(($filters[$filterName] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="submitting" class="flex-1 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">Apply filters</span>
                    <span x-show="submitting" x-cloak>Searching...</span>
                </button>
                <a href="{{ route('schools.index') }}" class="rounded-lg px-2 py-2 text-xs font-bold text-slate-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Reset</a>
            </div>
        </form>

        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="school-directory-title">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <h2 id="school-directory-title" class="text-sm font-bold text-slate-900">School directory <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ number_format($schools->total()) }}</span></h2>
                <p class="text-xs text-slate-500">Scroll horizontally to view all school details.</p>
            </div>
            <div class="overflow-x-auto focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" tabindex="0" role="region" aria-label="School directory table">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            @foreach (['School ID', 'School Name', 'Project No.', 'Project', 'Address', 'Contact Person', 'Contact', 'Municipality', 'Division', 'Region', 'Contract Price', 'Actions'] as $heading)
                                <th scope="col" class="whitespace-nowrap px-4 py-3 {{ $heading === 'Contract Price' ? 'text-right' : '' }}">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @forelse ($schools as $school)
                            <tr class="hover:bg-slate-50/70">
                                <th scope="row" class="whitespace-nowrap px-4 py-4 text-left font-mono text-xs font-semibold text-slate-700">{{ $school->school_id }}</th>
                                <td class="min-w-[220px] px-4 py-4 font-semibold text-slate-900">{{ $school->school_name }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $school->project_no ?? '—' }}</td>
                                <td class="min-w-[200px] px-4 py-4">{{ $school->project ?? '—' }}</td>
                                <td class="min-w-[220px] px-4 py-4">{{ $school->address }}</td>
                                <td class="min-w-[160px] px-4 py-4">{{ $school->contact_person ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $school->contact ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $school->municipality ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $school->division }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $school->region }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-slate-900">{{ $school->total_contract_price !== null ? number_format((float) $school->total_contract_price, 2) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('schools.show', ['school' => $school->school_id] + $listQuery) }}" aria-label="View {{ $school->school_name }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">View</a>
                                        <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" aria-label="Edit {{ $school->school_name }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-5 py-14 text-center">
                                    <p class="font-semibold text-slate-700">No schools found.</p>
                                    <p class="mt-1 text-sm text-slate-500">Adjust the search and filters, or add your first school.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($schools->hasPages())
                <div class="border-t border-slate-100 px-4 py-4">{{ $schools->links() }}</div>
            @endif
        </section>
    </div>
</x-project-app-layout>
