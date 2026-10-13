@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;

    $filterLabels = [
        'search' => 'Search',
        'municipality' => 'Municipality',
        'division' => 'Division',
        'region' => 'Region',
    ];
    $activeFilters = array_filter(Arr::only($filters ?? [], array_keys($filterLabels)), fn ($v) => filled($v));
    $price = fn ($school) => $school->total_contract_price !== null ? '₱' . number_format((float) $school->total_contract_price, 2) : '—';
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');
@endphp

<x-project-app-layout>
    <div class="min-w-0 space-y-6">

        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">School Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage school records, contacts, and project details.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('schools.import', $listQuery) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-900/10 transition hover:bg-slate-50 hover:ring-slate-900/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" /></svg>
                    Import Schools
                </a>
                <a href="{{ route('schools.create', $listQuery) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    Add New School
                </a>
            </div>
        </div>

        @include('schools._alerts')

        {{-- Search & filters --}}
        <form action="{{ route('schools.index') }}" method="GET" x-data="{ submitting: false }" x-on:submit="submitting = true" :aria-busy="submitting" class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-5">
            <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="sm:col-span-2 xl:col-span-4">
                    <label for="school-search" class="sr-only">Search schools</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                        <input id="school-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="Search by school ID, name, project, address, or contact" class="w-full rounded-2xl border-0 bg-slate-100/80 py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 ring-1 ring-transparent transition focus:bg-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                @foreach (['municipality' => $municipalities, 'division' => $divisions, 'region' => $regions] as $filterName => $options)
                    <div>
                        <label for="school-{{ $filterName }}" class="mb-1.5 block text-xs font-semibold text-slate-500">{{ ucfirst($filterName) }}</label>
                        <select id="school-{{ $filterName }}" name="{{ $filterName }}" class="w-full rounded-xl border-0 bg-white py-2.5 text-sm text-slate-800 shadow-sm ring-1 ring-slate-900/10 transition hover:ring-slate-900/20 focus:ring-2 focus:ring-blue-500">
                            <option value="">All {{ $filterName === 'municipality' ? 'municipalities' : $filterName . 's' }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option }}" @selected(($filters[$filterName] ?? '') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="submitting" class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!submitting">Apply filters</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                    <a href="{{ route('schools.index') }}" class="rounded-xl px-3 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Reset</a>
                </div>
            </div>

            @if (count($activeFilters))
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @foreach ($activeFilters as $key => $value)
                        <a href="{{ route('schools.index', array_filter(Arr::except($activeFilters, [$key]))) }}" class="group inline-flex items-center gap-1.5 rounded-full bg-blue-50 py-1.5 pl-3 pr-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-600/10 transition hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label="Remove {{ $filterLabels[$key] }} filter: {{ $value }}">
                            <span class="font-medium text-blue-500">{{ $filterLabels[$key] }}</span>
                            {{ Str::limit($value, 28) }}
                            <svg class="h-3.5 w-3.5 text-blue-400 transition group-hover:text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Directory --}}
        <section class="min-w-0 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5" aria-labelledby="school-directory-title">
            <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-5">
                <h2 id="school-directory-title" class="flex items-center gap-2 text-base font-bold text-slate-900">
                    School directory
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">{{ number_format($schools->total()) }}</span>
                </h2>
                @if ($schools->count())
                    <p class="text-xs text-slate-500">Showing {{ number_format($schools->firstItem()) }}–{{ number_format($schools->lastItem()) }} of {{ number_format($schools->total()) }}</p>
                @endif
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto border-t border-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 md:block" tabindex="0" role="region" aria-label="School directory table">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-semibold text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-6 py-3.5">School</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5">Address</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5">Contact</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5">Area</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right">Contract Price</th>
                            <th scope="col" class="sticky right-0 whitespace-nowrap bg-slate-50 px-6 py-3.5 text-right shadow-[-10px_0_10px_-10px_rgba(15,23,42,0.1)]"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @forelse ($schools as $school)
                            <tr class="group transition-colors hover:bg-blue-50/40">
                                <th scope="row" class="min-w-[280px] px-6 py-4 text-left font-normal">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-extrabold text-blue-700 ring-1 ring-blue-600/10" aria-hidden="true">{{ $initials($school->school_name) }}</span>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900">{{ $school->school_name }}</div>
                                            <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $school->school_id }}</div>
                                        </div>
                                    </div>
                                </th>
                                <td class="min-w-[220px] max-w-[300px] px-4 py-4">
                                    <p class="line-clamp-2 leading-snug" title="{{ $school->address }}">{{ $school->address ?: '—' }}</p>
                                </td>
                                <td class="min-w-[170px] px-4 py-4">
                                    <div class="font-medium text-slate-800">{{ $school->contact_person ?? '—' }}</div>
                                    @if ($school->contact)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->contact) }}" class="mt-0.5 inline-block whitespace-nowrap text-xs text-slate-500 transition hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">{{ $school->contact }}</a>
                                    @endif
                                </td>
                                <td class="min-w-[170px] px-4 py-4">
                                    <div class="whitespace-nowrap font-medium text-slate-800">{{ $school->municipality ?? '—' }}</div>
                                    <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">{{ $school->division }} · {{ $school->region }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-slate-900">{{ $price($school) }}</td>
                                <td class="sticky right-0 whitespace-nowrap bg-white px-6 py-4 shadow-[-10px_0_10px_-10px_rgba(15,23,42,0.1)] group-hover:bg-blue-50/60">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('schools.show', ['school' => $school->school_id] + $listQuery) }}" aria-label="View {{ $school->school_name }}" title="View" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 ring-1 ring-slate-900/10 transition hover:bg-white hover:text-slate-900 hover:ring-slate-900/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                        </a>
                                        <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" aria-label="Edit {{ $school->school_name }}" title="Edit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9a2.8 2.8 0 00-4-4L4 16v4z" /></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    @include('schools._empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 border-t border-slate-100 bg-slate-50/60 p-3 md:hidden">
                @forelse ($schools as $school)
                    <article class="space-y-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-extrabold text-blue-700 ring-1 ring-blue-600/10" aria-hidden="true">{{ $initials($school->school_name) }}</span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold leading-snug text-slate-900">{{ $school->school_name }}</h3>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $school->school_id }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold tabular-nums text-slate-900">{{ $price($school) }}</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                            <div class="col-span-2"><dt class="font-semibold text-slate-500">Address</dt><dd class="mt-0.5 text-slate-700">{{ $school->address ?: '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Contact person</dt><dd class="mt-0.5 text-slate-700">{{ $school->contact_person ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Contact</dt><dd class="mt-0.5 text-slate-700">{{ $school->contact ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Municipality</dt><dd class="mt-0.5 text-slate-700">{{ $school->municipality ?? '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Division · Region</dt><dd class="mt-0.5 text-slate-700">{{ $school->division }} · {{ $school->region }}</dd></div>
                        </dl>
                        <div class="flex gap-2">
                            <a href="{{ route('schools.show', ['school' => $school->school_id] + $listQuery) }}" aria-label="View {{ $school->school_name }}" class="flex-1 rounded-xl py-2.5 text-center text-xs font-bold text-slate-700 ring-1 ring-slate-900/10 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">View</a>
                            <a href="{{ route('schools.edit', ['school' => $school->school_id] + $listQuery) }}" aria-label="Edit {{ $school->school_name }}" class="flex-1 rounded-xl bg-blue-600 py-2.5 text-center text-xs font-bold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Edit</a>
                        </div>
                    </article>
                @empty
                    <div class="px-3 py-12 text-center">@include('schools._empty')</div>
                @endforelse
            </div>

            @if ($schools->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $schools->links() }}</div>
            @endif
        </section>
    </div>
</x-project-app-layout>