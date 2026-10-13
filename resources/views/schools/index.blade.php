@php
    use Illuminate\Support\Arr;

    $filterLabels = [
        'search' => 'Search',
        'municipality' => 'Municipality',
        'division' => 'Division',
        'region' => 'Region',
    ];
    $activeFilters = array_filter(Arr::only($filters ?? [], array_keys($filterLabels)), fn ($v) => filled($v));
    $price = fn ($school) => $school->total_contract_price !== null ? '₱' . number_format((float) $school->total_contract_price, 2) : '—';
@endphp

<x-project-app-layout>
    <div class="min-w-0 space-y-6">

        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">School Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage school records, contacts, and project details.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('schools.import', $listQuery) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" /></svg>
                    Import Schools
                </a>
                <a href="{{ route('schools.create', $listQuery) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    Add New School
                </a>
            </div>
        </div>

        @include('schools._alerts')

        {{-- Search & filters --}}
        <form action="{{ route('schools.index') }}" method="GET" x-data="{ submitting: false }" x-on:submit="submitting = true" :aria-busy="submitting" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="sm:col-span-2 xl:col-span-4">
                    <label for="school-search" class="mb-1.5 block text-xs font-bold text-slate-600">Search schools</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                        <input id="school-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="School ID, name, project, address, or contact..." class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500">
                    </div>
                </div>

                @foreach (['municipality' => $municipalities, 'division' => $divisions, 'region' => $regions] as $filterName => $options)
                    <div>
                        <label for="school-{{ $filterName }}" class="mb-1.5 block text-xs font-bold text-slate-600">{{ ucfirst($filterName) }}</label>
                        <select id="school-{{ $filterName }}" name="{{ $filterName }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 text-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500">
                            <option value="">All {{ $filterName === 'municipality' ? 'municipalities' : $filterName . 's' }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option }}" @selected(($filters[$filterName] ?? '') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="submitting" class="flex-1 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!submitting">Apply filters</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                    <a href="{{ route('schools.index') }}" class="rounded-lg px-2 py-2 text-xs font-bold text-slate-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Reset</a>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if (count($activeFilters))
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    <span class="text-xs font-semibold text-slate-500">Active filters:</span>
                    @foreach ($activeFilters as $key => $value)
                        <a href="{{ route('schools.index', array_filter(Arr::except($activeFilters, [$key]))) }}" class="group inline-flex items-center gap-1.5 rounded-full bg-blue-50 py-1 pl-3 pr-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Remove {{ $filterLabels[$key] }} filter: {{ $value }}">
                            <span class="text-blue-500">{{ $filterLabels[$key] }}:</span> {{ \Illuminate\Support\Str::limit($value, 28) }}
                            <svg class="h-3.5 w-3.5 text-blue-400 group-hover:text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Directory --}}
        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="school-directory-title">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <h2 id="school-directory-title" class="text-sm font-bold text-slate-900">
                    School directory
                    <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ number_format($schools->total()) }}</span>
                </h2>
                @if ($schools->count())
                    <p class="text-xs text-slate-500">Showing {{ number_format($schools->firstItem()) }}–{{ number_format($schools->lastItem()) }} of {{ number_format($schools->total()) }}</p>
                @endif
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:block" tabindex="0" role="region" aria-label="School directory table">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-5 py-3">School</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Address</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Contact</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Area</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Contract Price</th>
                            <th scope="col" class="sticky right-0 whitespace-nowrap bg-slate-50 px-5 py-3 text-right shadow-[-8px_0_8px_-8px_rgba(15,23,42,0.08)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @forelse ($schools as $school)
                            <tr class="group transition hover:bg-slate-50/70">
                                <th scope="row" class="min-w-[240px] px-5 py-4 text-left font-normal">
                                    <div class="font-semibold text-slate-900">{{ $school->school_name }}</div>
                                    <span class="mt-1 inline-block rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600">{{ $school->school_id }}</span>
                                </th>
                                <td class="min-w-[220px] max-w-[300px] px-4 py-4">
                                    <p class="line-clamp-2" title="{{ $school->address }}">{{ $school->address ?: '—' }}</p>
                                </td>
                                <td class="min-w-[170px] px-4 py-4">
                                    <div class="font-medium text-slate-800">{{ $school->contact_person ?? '—' }}</div>
                                    @if ($school->contact)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->contact) }}" class="mt-0.5 inline-block whitespace-nowrap text-xs text-slate-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $school->contact }}</a>
                                    @endif
                                </td>
                                <td class="min-w-[170px] px-4 py-4">
                                    <div class="whitespace-nowrap font-medium text-slate-800">{{ $school->municipality ?? '—' }}</div>
                                    <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">{{ $school->division }} · {{ $school->region }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-slate-900">{{ $price($school) }}</td>
                                <td class="sticky right-0 whitespace-nowrap bg-white px-5 py-4 shadow-[-8px_0_8px_-8px_rgba(15,23,42,0.08)] group-hover:bg-slate-50">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('schools.show', ['school' => $school->school_id] + $listQuery) }}" aria-label="View {{ $school->school_name }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">View</a>
                                        <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" aria-label="Edit {{ $school->school_name }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    @include('schools._empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="divide-y divide-slate-100 md:hidden">
                @forelse ($schools as $school)
                    <article class="space-y-3 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-slate-900">{{ $school->school_name }}</h3>
                                <span class="mt-1 inline-block rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600">{{ $school->school_id }}</span>
                            </div>
                            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900">{{ $price($school) }}</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div class="col-span-2"><dt class="font-semibold text-slate-500">Address</dt><dd class="mt-0.5 text-slate-700">{{ $school->address ?: '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Contact person</dt><dd class="mt-0.5 text-slate-700">{{ $school->contact_person ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Contact</dt><dd class="mt-0.5 text-slate-700">{{ $school->contact ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Municipality</dt><dd class="mt-0.5 text-slate-700">{{ $school->municipality ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Division · Region</dt><dd class="mt-0.5 text-slate-700">{{ $school->division }} · {{ $school->region }}</dd></div>
                        </dl>
                        <div class="flex gap-2 pt-1">
                            <a href="{{ route('schools.show', ['school' => $school->school_id] + $listQuery) }}" aria-label="View {{ $school->school_name }}" class="flex-1 rounded-lg bg-slate-100 px-3 py-2 text-center text-xs font-bold text-slate-700 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">View</a>
                            <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" aria-label="Edit {{ $school->school_name }}" class="flex-1 rounded-lg bg-blue-50 px-3 py-2 text-center text-xs font-bold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500">Edit</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-14 text-center">@include('schools._empty')</div>
                @endforelse
            </div>

            @if ($schools->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-4">{{ $schools->links() }}</div>
            @endif
        </section>
    </div>
</x-project-app-layout>