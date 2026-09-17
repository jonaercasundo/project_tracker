<x-project_app-layout>
@php
    $schools = collect($schools ?? ($project->schools ?? []));
    $lots = collect($project->lots ?? []);
    $items = collect($project->items ?? []);
    $keystages = collect($project->keystages ?? []);
    $packages = collect($packages ?? []);

    $schoolCount = $schools->count();
    $lotCount = $lots->count();
    $itemCount = $items->count();
    $keystageCount = $keystages->count();
    $packageCount = $packages->count();
@endphp
<div class="max-w-7xl mx-auto p-6 space-y-6 text-slate-800">

    {{-- HEADER --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">

            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900">
                        {{ $project->project_name }}
                    </h1>

                    @if($project->status)
                        <span class="px-3 py-1 text-xs font-bold rounded-full
                            bg-blue-50 text-blue-700 border border-blue-100">
                            {{ $project->status }}
                        </span>
                    @endif
                </div>

                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">
                    Project CRM Dashboard
                </p>
            </div>

            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 text-xs font-bold bg-slate-100 text-slate-700
                      hover:bg-slate-200 rounded-xl border border-slate-200">
                Back to Projects
            </a>

        </div>
    </div>


    {{-- QUICK STATISTICS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        {{-- SCHOOLS --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">
                Schools
            </p>

            <p class="text-3xl font-black text-slate-900 mt-2">
                {{ collect($schools ?? [])->count() ?? 0 }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Project schools
            </p>
        </div>

        {{-- LOTS --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">
                Lots
            </p>

            <p class="text-3xl font-black text-slate-900 mt-2">
                {{ $project->lots->count() ?? 0 }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Project lots
            </p>
        </div>

        {{-- ITEMS --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">
                Items
            </p>

            <p class="text-3xl font-black text-slate-900 mt-2">
                {{ $project->items->count() ?? 0 }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Project items
            </p>
        </div>

        {{-- PACKAGES --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">
                Packages
            </p>

            <p class="text-3xl font-black text-slate-900 mt-2">
                {{ collect($packages ?? [])->count() ?? 0 }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Project packages
            </p>
        </div>

    </div>


    {{-- TABS --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- TAB NAV --}}
        <div role="tablist"
             class="flex overflow-x-auto border-b bg-slate-50/50
                    text-xs font-bold text-slate-500">

            @php
                $tabs = [
                    'overview' => 'Overview',
                    'schools'  => 'Schools',
                    'lots'     => 'Lots',
                    'keystage' => 'Keystages',
                    'items'    => 'Items',
                    'packages' => 'Packages',
                    'setting'  => 'Project Setting',
                ];
            @endphp

            @foreach($tabs as $key => $label)

                <button
                    type="button"
                    role="tab"
                    data-tab="{{ $key }}"
                    onclick="openTab(event, '{{ $key }}')"
                    class="tab-btn whitespace-nowrap px-5 py-3.5
                           border-b-2 transition-all duration-200
                           {{ $loop->first
                                ? 'border-blue-600 text-blue-600 bg-white'
                                : 'border-transparent hover:bg-slate-50' }}">

                    {{ $label }}

                </button>

            @endforeach

        </div>


        {{-- CONTENT --}}
        <div class="p-6">


            {{-- ========================================================= --}}
            {{-- OVERVIEW --}}
            {{-- ========================================================= --}}

            <div id="overview" class="tab-content space-y-6">

                <div class="grid lg:grid-cols-2 gap-6">

                    {{-- PROJECT INFORMATION --}}
                    <div class="border border-slate-200 rounded-2xl p-5">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Project Information
                        </h2>

                        <div class="grid sm:grid-cols-2 gap-5">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Reference No.
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->ref_no ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Project Code
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->project_code ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Agency
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->agency ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Key Stage
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->keystage ?: 'Not Set' }}
                                </p>
                            </div>

                        </div>

                    </div>


                    {{-- FINANCIAL INFORMATION --}}
                    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Financial Information
                        </h2>

                        <div class="space-y-4">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Contract Amount
                                </p>

                                <p class="text-2xl font-black text-slate-900">
                                    ₱{{ number_format($project->contract_amount ?? 0, 2) }}
                                </p>
                            </div>

                            <div class="border-t border-slate-200 pt-3">

                                <p class="text-xs text-slate-400 uppercase">
                                    ABC
                                </p>

                                <p class="font-bold text-lg">
                                    ₱{{ number_format($project->ABC ?? 0, 2) }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- PROJECT STRUCTURE --}}
                <div>

                    <h2 class="text-sm font-black text-slate-900 mb-4">
                        Project Structure
                    </h2>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Schools
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ collect($schools ?? [])->count() ?? 0 }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Lots
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $project->lots->count() ?? 0 }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Items
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ $project->items->count() ?? 0 }}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Packages
                            </p>

                            <p class="text-xl font-black mt-1">
                                {{ collect($packages ?? [])->count() ?? 0 }}
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- SCHOOLS --}}
            {{-- ========================================================= --}}

            <div id="schools" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Schools
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Schools associated with this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        <span class="px-3 py-1.5 rounded-full bg-blue-50
                                    text-blue-700 text-xs font-bold">
                            {{ collect($schools ?? [])->count() }} Schools
                        </span>

                        <a href="#"
                        class="inline-flex items-center gap-2 px-4 py-2
                                text-xs font-bold rounded-xl
                                bg-blue-600 text-white
                                hover:bg-blue-700 transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>
                            </svg>

                            Upload Schools
                        </a>

                    </div>
                </div>


                {{-- SEARCH + FILTERS --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

                        {{-- SEARCH --}}
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-600 mb-1">
                                Search
                            </label>

                            <div class="relative">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-3 top-1/2 -translate-y-1/2
                                            w-4 h-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>

                                <input
                                    type="text"
                                    id="schoolSearch"
                                    placeholder="Search school name, ID, municipality..."
                                    class="w-full pl-9 pr-3 py-2.5 text-sm
                                        border border-slate-200 rounded-xl
                                        focus:ring-2 focus:ring-blue-500
                                        focus:border-blue-500 outline-none">
                            </div>
                        </div>


                        {{-- REGION --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">
                                Region
                            </label>

                            <select
                                id="schoolRegion"
                                class="w-full px-3 py-2.5 text-sm
                                    border border-slate-200 rounded-xl
                                    bg-white focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500 outline-none">

                                <option value="">All Regions</option>

                                @foreach(
                                    collect($schools ?? [])
                                        ->pluck('region')
                                        ->filter()
                                        ->unique()
                                        ->sort()
                                    as $region
                                )
                                    <option value="{{ $region }}">
                                        {{ $region }}
                                    </option>
                                @endforeach

                            </select>
                        </div>


                        {{-- DIVISION --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">
                                Division
                            </label>

                            <select
                                id="schoolDivision"
                                class="w-full px-3 py-2.5 text-sm
                                    border border-slate-200 rounded-xl
                                    bg-white focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500 outline-none">

                                <option value="">All Divisions</option>

                                @foreach(
                                    collect($schools ?? [])
                                        ->pluck('division')
                                        ->filter()
                                        ->unique()
                                        ->sort()
                                    as $division
                                )
                                    <option value="{{ $division }}">
                                        {{ $division }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                    </div>


                    {{-- MUNICIPALITY + FILTER BUTTONS --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">

                        {{-- MUNICIPALITY --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">
                                Municipality
                            </label>

                            <select
                                id="schoolMunicipality"
                                class="w-full px-3 py-2.5 text-sm
                                    border border-slate-200 rounded-xl
                                    bg-white focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500 outline-none">

                                <option value="">All Municipalities</option>

                                @foreach(
                                    collect($schools ?? [])
                                        ->pluck('municipality')
                                        ->filter()
                                        ->unique()
                                        ->sort()
                                    as $municipality
                                )
                                    <option value="{{ $municipality }}">
                                        {{ $municipality }}
                                    </option>
                                @endforeach

                            </select>
                        </div>


                        {{-- BUTTONS --}}
                        <div class="flex items-end gap-2 lg:col-span-3">

                            <button
                                type="button"
                                id="applySchoolFilters"
                                class="px-4 py-2.5 rounded-xl
                                    bg-blue-600 text-white
                                    text-xs font-bold
                                    hover:bg-blue-700 transition">

                                Apply Filters
                            </button>

                            <button
                                type="button"
                                id="clearSchoolFilters"
                                class="px-4 py-2.5 rounded-xl
                                    bg-slate-100 text-slate-700
                                    text-xs font-bold
                                    hover:bg-slate-200 transition">

                                Clear
                            </button>

                        </div>

                    </div>

                </div>


                {{-- SCHOOL TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-slate-900 text-white">

                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        School ID
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        School Name
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Address
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Municipality
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Division
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Region
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Contact Person
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-bold whitespace-nowrap">
                                        Telephone
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-bold whitespace-nowrap">
                                        Action
                                    </th>
                                </tr>

                            </thead>


                            <tbody id="schoolsTableBody"
                                class="divide-y divide-slate-100">

                                @forelse($schools ?? [] as $school)

                                    <tr
                                        class="school-row hover:bg-slate-50 transition"
                                        data-school-id="{{ strtolower($school->school_id ?? '') }}"
                                        data-school-name="{{ strtolower($school->school_name ?? $school->name ?? '') }}"
                                        data-region="{{ strtolower($school->region ?? '') }}"
                                        data-division="{{ strtolower($school->division ?? '') }}"
                                        data-municipality="{{ strtolower($school->municipality ?? '') }}">

                                        {{-- SCHOOL ID --}}
                                        <td class="px-4 py-3 whitespace-nowrap">

                                            <span class="font-bold text-blue-700">
                                                {{ $school->school_id ?? '—' }}
                                            </span>

                                        </td>


                                        {{-- SCHOOL NAME --}}
                                        <td class="px-4 py-3">

                                            <div class="font-bold text-slate-900">
                                                {{ $school->school_name ?? $school->name ?? 'School' }}
                                            </div>

                                        </td>


                                        {{-- ADDRESS --}}
                                        <td class="px-4 py-3 text-slate-600 max-w-xs">

                                            {{ $school->address ?? '—' }}

                                        </td>


                                        {{-- MUNICIPALITY --}}
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">

                                            {{ $school->municipality ?? '—' }}

                                        </td>


                                        {{-- DIVISION --}}
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">

                                            {{ $school->division ?? '—' }}

                                        </td>


                                        {{-- REGION --}}
                                        <td class="px-4 py-3">

                                            @if(!empty($school->region))

                                                <span class="inline-flex px-2 py-1
                                                            rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            text-xs font-bold">

                                                    {{ $school->region }}

                                                </span>

                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif

                                        </td>


                                        {{-- CONTACT PERSON --}}
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">

                                            {{ $school->contact_person ?? '—' }}

                                        </td>


                                        {{-- TELEPHONE --}}
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">

                                            {{ $school->contact ?? '—' }}

                                        </td>


                                        {{-- ACTION --}}
                                        <td class="px-4 py-3">

                                            <div class="flex items-center justify-center gap-2">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50 text-amber-600
                                                        hover:bg-amber-100 transition"
                                                    title="Edit School">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688a2.25 2.25 0 113.182 3.182l-1.688 1.687M16.862 4.487L7.5 13.85V17h3.15l9.36-9.332M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100 transition"
                                                    title="Delete School">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="noSchoolsRow">

                                        <td colspan="9"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14l9-5-9-5-9 5 9 5z"/>

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14v7m0-7L3 9m9 5l9-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No schools found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No schools are currently associated with this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- TABLE FOOTER --}}
                    <div class="px-4 py-3 border-t border-slate-200
                                flex flex-col sm:flex-row
                                justify-between items-center gap-2">

                        <p id="schoolResultCount"
                        class="text-xs text-slate-400">

                            Showing
                            <span class="font-bold text-slate-600">
                                {{ collect($schools ?? [])->count() }}
                            </span>
                            schools

                        </p>

                    </div>

                </div>

            </div>
            {{-- ========================================================= --}}
            {{-- SCHOOL FILTER SCRIPT --}}
            {{-- ========================================================= --}}
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const searchInput = document.getElementById('schoolSearch');
                    const regionSelect = document.getElementById('schoolRegion');
                    const divisionSelect = document.getElementById('schoolDivision');
                    const municipalitySelect = document.getElementById('schoolMunicipality');

                    const applyButton = document.getElementById('applySchoolFilters');
                    const clearButton = document.getElementById('clearSchoolFilters');

                    const rows = document.querySelectorAll('.school-row');
                    const resultCount = document.getElementById('schoolResultCount');

                    function filterSchools() {

                        const search = searchInput.value.toLowerCase().trim();
                        const region = regionSelect.value.toLowerCase();
                        const division = divisionSelect.value.toLowerCase();
                        const municipality = municipalitySelect.value.toLowerCase();

                        let visible = 0;

                        rows.forEach(row => {

                            const schoolId = row.dataset.schoolId || '';
                            const schoolName = row.dataset.schoolName || '';
                            const rowRegion = row.dataset.region || '';
                            const rowDivision = row.dataset.division || '';
                            const rowMunicipality = row.dataset.municipality || '';

                            const matchesSearch =
                                !search ||
                                schoolId.includes(search) ||
                                schoolName.includes(search) ||
                                rowMunicipality.includes(search);

                            const matchesRegion =
                                !region || rowRegion === region;

                            const matchesDivision =
                                !division || rowDivision === division;

                            const matchesMunicipality =
                                !municipality || rowMunicipality === municipality;

                            const show =
                                matchesSearch &&
                                matchesRegion &&
                                matchesDivision &&
                                matchesMunicipality;

                            row.classList.toggle('hidden', !show);

                            if (show) {
                                visible++;
                            }
                        });

                        resultCount.innerHTML = `
                            Showing
                            <span class="font-bold text-slate-600">
                                ${visible}
                            </span>
                            schools
                        `;
                    }


                    applyButton?.addEventListener('click', filterSchools);

                    searchInput?.addEventListener('input', filterSchools);

                    searchInput?.addEventListener('keydown', function (event) {

                        if (event.key === 'Enter') {
                            filterSchools();
                        }

                    });


                    regionSelect?.addEventListener('change', function () {

                        const selectedRegion = this.value.toLowerCase();

                        divisionSelect.value = '';
                        municipalitySelect.value = '';

                        Array.from(divisionSelect.options).forEach(option => {

                            if (!option.value) {
                                return;
                            }

                            const optionRegion =
                                option.dataset.region?.toLowerCase() || '';

                            option.hidden =
                                selectedRegion &&
                                optionRegion !== selectedRegion;

                        });

                        filterSchools();

                    });


                    divisionSelect?.addEventListener('change', function () {

                        municipalitySelect.value = '';

                        filterSchools();

                    });


                    municipalitySelect?.addEventListener('change', function () {

                        filterSchools();

                    });


                    clearButton?.addEventListener('click', function () {

                        searchInput.value = '';
                        regionSelect.value = '';
                        divisionSelect.value = '';
                        municipalitySelect.value = '';

                        Array.from(divisionSelect.options).forEach(option => {
                            option.hidden = false;
                        });

                        filterSchools();

                    });

                });
            </script>

            {{-- ========================================================= --}}
            {{-- LOTS --}}
            {{-- ========================================================= --}}

            <div id="lots" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Lots
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Lots assigned to this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- LOT COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">
                            {{ $lotCount ?? collect($lots ?? [])->count() }} Lots
                        </span>

                        {{-- ADD LOT --}}
                        <button
                            type="button"
                            class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl bg-emerald-600
                                text-white hover:bg-emerald-700
                                transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>

                            </svg>

                            Add New Lot
                        </button>

                    </div>
                </div>


                {{-- LOT TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-slate-900 text-white">

                                <tr>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot Number
                                    </th>

                                    @if(($keystageProj->keystage ?? $keystage ?? 0) == 1)

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Keystage
                                        </th>

                                    @else

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Cartons
                                        </th>

                                    @endif

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Contract No.
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse($lots ?? [] as $lot)

                                    <tr class="hover:bg-slate-50 transition">

                                        {{-- LOT NUMBER --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center justify-center">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-blue-600"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                    </svg>

                                                </div>

                                                <div>

                                                    <p class="font-black text-slate-900">
                                                        {{ $lot->lot_name ?? 'Lot' }}
                                                    </p>

                                                    <p class="text-[11px] text-slate-400">
                                                        Lot ID: {{ $lot->lot_id }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE / CARTONS --}}
                                        <td class="px-4 py-4">

                                            @if(($keystageProj->keystage ?? $keystage ?? 0) == 1)

                                                {{-- KEYSTAGE PROJECT --}}
                                                @if(!empty($lot->keystages) && $lot->keystages->count())

                                                    <div class="space-y-1">

                                                        @foreach($lot->keystages as $ks)

                                                            <div>
                                                                <span class="font-bold text-slate-700">
                                                                    Keystage {{ $ks->keystage_num }}
                                                                </span>

                                                                @if(!empty($ks->description))
                                                                    <span class="text-slate-500">
                                                                        — {{ $ks->description }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @else

                                                {{-- CARTON / PACKAGE PROJECT --}}
                                                @php
                                                    $cartonCount =
                                                        $lot->packages_count
                                                        ?? $lot->carton_count
                                                        ?? ($lot->packages ? $lot->packages->count() : 0);
                                                @endphp

                                                @if($cartonCount > 0)

                                                    <span class="inline-flex items-center
                                                                px-2.5 py-1 rounded-lg
                                                                bg-blue-50 text-blue-700
                                                                text-xs font-bold">

                                                        {{ number_format($cartonCount) }}
                                                        {{ $cartonCount == 1 ? 'Carton' : 'Cartons' }}

                                                    </span>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @endif

                                        </td>


                                        {{-- CONTRACT NUMBER --}}
                                        <td class="px-4 py-4">

                                            @if(!empty($lot->contract_no))

                                                <span class="font-medium text-slate-700">
                                                    {{ $lot->contract_no }}
                                                </span>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    Not assigned
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center justify-center
                                                        gap-2">

                                                {{-- KEYSTAGE / PACKAGES --}}
                                                @if(($keystageProj->keystage ?? $keystage ?? 0) == 1)

                                                    <a
                                                        href="{{ route('projects.keystage', [
                                                            'id' => $project->project_id,
                                                            'lot_id' => $lot->lot_id
                                                        ]) }}"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>

                                                            <circle cx="12"
                                                                    cy="12"
                                                                    r="3"/>

                                                        </svg>

                                                        Keystage

                                                    </a>

                                                @else

                                                    <a
                                                        href="{{ route('projects.packages', [
                                                            'id' => $project->project_id,
                                                            'lot_id' => $lot->lot_id
                                                        ]) }}"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                        </svg>

                                                        Packages

                                                    </a>

                                                @endif


                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditLotModal(
                                                        {{ $lot->lot_id }},
                                                        @js($lot->lot_name),
                                                        @js($lot->project_id),
                                                        @js($lot->contract_no)
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50 text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688a2.25 2.25 0 113.182 3.182l-1.688 1.687M16.862 4.487L7.5 13.85V17h3.15l9.36-9.332M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteLotModal({{ $lot->lot_id }})"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No lots found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No lots are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>
            {{-- ========================================================= --}}
            {{-- LOT MODAL HELPERS --}}
            {{-- ========================================================= --}}

            <script>
                function openEditLotModal(lotId, lotName, projectId, contractNo)
                {
                    /*
                    * These IDs should match your Laravel edit-lot modal.
                    */

                    const lotIdInput =
                        document.getElementById('editlotid');

                    const lotNameInput =
                        document.getElementById('editlotname');

                    const projectIdInput =
                        document.getElementById('editprojectid');

                    const contractNoInput =
                        document.getElementById('editcontractno');


                    if (lotIdInput) {
                        lotIdInput.value = lotId;
                    }

                    if (lotNameInput) {
                        lotNameInput.value = lotName ?? '';
                    }

                    if (projectIdInput) {
                        projectIdInput.value = projectId ?? '';
                    }

                    if (contractNoInput) {
                        contractNoInput.value = contractNo ?? '';
                    }


                    /*
                    * If your modal has a specific ID,
                    * open it here.
                    */

                    const modal =
                        document.getElementById('editLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
                function openDeleteLotModal(lotId)
                {
                    const deleteInput =
                        document.getElementById('delete_lot');

                    if (deleteInput) {
                        deleteInput.value = lotId;
                    }

                    const modal =
                        document.getElementById('deleteLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
            </script>

            {{-- ========================================================= --}}
            {{-- KEYSTAGES --}}
            {{-- ========================================================= --}}

            <div id="keystage" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Keystages
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Keystages assigned to the lots in this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">

                            {{ $keystageCount ?? collect($keystages ?? [])->count() }}
                            Keystages

                        </span>


                        {{-- ADD / UPLOAD --}}
                        <button
                            type="button"
                            class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl bg-emerald-600
                                text-white hover:bg-emerald-700
                                transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>

                            </svg>

                            Add Keystage
                        </button>

                    </div>

                </div>


                {{-- SEARCH --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl p-4 shadow-sm">

                    <div class="flex flex-col md:flex-row gap-3">

                        <div class="flex-1">

                            <div class="relative">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-3 top-1/2
                                            -translate-y-1/2
                                            w-4 h-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.35-5.65
                                            a7 7 0 11-14 0
                                            7 7 0 0114 0z"/>

                                </svg>

                                <input
                                    type="text"
                                    id="keystageSearch"
                                    placeholder="Search keystage, description, lot..."
                                    class="w-full pl-9 pr-3 py-2.5
                                        text-sm border border-slate-200
                                        rounded-xl
                                        focus:ring-2 focus:ring-blue-500
                                        focus:border-blue-500
                                        outline-none">

                            </div>

                        </div>


                        {{-- LOT FILTER --}}
                        <div class="w-full md:w-64">

                            <select
                                id="keystageLotFilter"
                                class="w-full px-3 py-2.5
                                    text-sm border border-slate-200
                                    rounded-xl bg-white
                                    focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500
                                    outline-none">

                                <option value="">
                                    All Lots
                                </option>

                                @foreach(collect($keystages ?? [])
                                    ->map(fn($item) => $item->lot_name ?? $item->lot_id ?? null)
                                    ->filter()
                                    ->unique()
                                    ->sort()
                                    as $lot)

                                    <option value="{{ strtolower($lot) }}">
                                        {{ $lot }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>


                {{-- TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-slate-900 text-white">

                                <tr>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Keystage
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold">
                                        Description
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="keystageTableBody"
                                class="divide-y divide-slate-100">

                                @forelse($keystages ?? [] as $keystage)

                                    @php

                                        $lotName =
                                            $keystage->lot_name
                                            ?? $keystage->lot_no
                                            ?? $keystage->lot_id
                                            ?? '—';

                                        $keystageNumber =
                                            $keystage->keystage_num
                                            ?? $keystage->keystage_no
                                            ?? $keystage->keystage_id
                                            ?? '—';

                                        $description =
                                            $keystage->description
                                            ?? $keystage->keystage_name
                                            ?? 'No description';

                                    @endphp


                                    <tr
                                        class="keystage-row hover:bg-slate-50 transition"

                                        data-search="
                                            {{ strtolower(
                                                ($lotName ?? '') . ' ' .
                                                ($keystageNumber ?? '') . ' ' .
                                                ($description ?? '')
                                            ) }}
                                        "

                                        data-lot="{{ strtolower($lotName) }}"
                                    >

                                        {{-- LOT --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center
                                                            justify-center">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-blue-600"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                    </svg>

                                                </div>

                                                <div>

                                                    <p class="font-bold text-slate-900">
                                                        {{ $lotName }}
                                                    </p>

                                                    @if(!empty($keystage->lot_id))

                                                        <p class="text-[11px] text-slate-400">
                                                            Lot ID: {{ $keystage->lot_id }}
                                                        </p>

                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE NUMBER --}}
                                        <td class="px-4 py-4">

                                            <span class="inline-flex items-center
                                                        px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        text-xs font-black">

                                                Keystage {{ $keystageNumber }}

                                            </span>

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-4 py-4">

                                            @if(
                                                !empty($keystage->description) ||
                                                !empty($keystage->keystage_name)
                                            )

                                                <p class="text-slate-700">
                                                    {{ $description }}
                                                </p>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    No description
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center
                                                        justify-center gap-2">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }},
                                                        @js($keystageNumber),
                                                        @js($description),
                                                        @js($keystage->lot_id ?? '')
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50
                                                        text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688
                                                                a2.25 2.25 0 113.182 3.182
                                                                l-1.688 1.687M16.862 4.487
                                                                L7.5 13.85V17h3.15l9.36-9.332
                                                                M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }}
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142
                                                                A2 2 0 0116.138 21H7.862
                                                                a2 2 0 01-1.995-1.858L5 7
                                                                m5 4v6m4-6v6M9 7V4
                                                                a1 1 0 011-1h4a1 1 0 011 1v3
                                                                m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="4"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center
                                                            justify-center mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14l9-5-9-5-9 5 9 5z"/>

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14v7m0-7L3 9m9 5l9-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No keystages found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No keystages are currently assigned
                                                    to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- FOOTER --}}
                    <div class="px-4 py-3 border-t border-slate-200">

                        <p id="keystageResultCount"
                        class="text-xs text-slate-400">

                            Showing
                            <span class="font-bold text-slate-600">
                                {{ collect($keystages ?? [])->count() }}
                            </span>
                            keystages

                        </p>

                    </div>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- KEYSTAGE FILTER SCRIPT --}}
            {{-- ========================================================= --}}

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const searchInput =
                        document.getElementById('keystageSearch');

                    const lotFilter =
                        document.getElementById('keystageLotFilter');

                    const rows =
                        document.querySelectorAll('.keystage-row');

                    const resultCount =
                        document.getElementById('keystageResultCount');


                    function filterKeystages()
                    {
                        const search =
                            searchInput?.value
                                .toLowerCase()
                                .trim() || '';

                        const selectedLot =
                            lotFilter?.value
                                .toLowerCase()
                                .trim() || '';

                        let visible = 0;


                        rows.forEach(row => {

                            const rowSearch =
                                row.dataset.search || '';

                            const rowLot =
                                row.dataset.lot || '';


                            const matchesSearch =
                                !search ||
                                rowSearch.includes(search);


                            const matchesLot =
                                !selectedLot ||
                                rowLot === selectedLot;


                            const show =
                                matchesSearch &&
                                matchesLot;


                            row.classList.toggle(
                                'hidden',
                                !show
                            );


                            if (show) {
                                visible++;
                            }

                        });


                        if (resultCount) {

                            resultCount.innerHTML = `
                                Showing
                                <span class="font-bold text-slate-600">
                                    ${visible}
                                </span>
                                keystages
                            `;

                        }

                    }


                    searchInput?.addEventListener(
                        'input',
                        filterKeystages
                    );


                    lotFilter?.addEventListener(
                        'change',
                        filterKeystages
                    );

                });


                /*
                |--------------------------------------------------------------------------
                | Edit Keystage
                |--------------------------------------------------------------------------
                */

                function openEditKeystageModal(
                    keystageId,
                    keystageNumber,
                    description,
                    lotId
                ) {

                    const id =
                        document.getElementById(
                            'editkeystageid'
                        );

                    const number =
                        document.getElementById(
                            'editkeystagenum'
                        );

                    const desc =
                        document.getElementById(
                            'editkeystagedescription'
                        );

                    const lot =
                        document.getElementById(
                            'editkeystagelotid'
                        );


                    if (id) {
                        id.value = keystageId;
                    }

                    if (number) {
                        number.value = keystageNumber ?? '';
                    }

                    if (desc) {
                        desc.value = description ?? '';
                    }

                    if (lot) {
                        lot.value = lotId ?? '';
                    }


                    const modal =
                        document.getElementById(
                            'editKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }
                /*
                |--------------------------------------------------------------------------
                | Delete Keystage
                |--------------------------------------------------------------------------
                */
                function openDeleteKeystageModal(
                    keystageId
                ) {

                    const input =
                        document.getElementById(
                            'delete_keystage'
                        );

                    if (input) {
                        input.value = keystageId;
                    }


                    const modal =
                        document.getElementById(
                            'deleteKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }

            </script>

            {{-- ========================================================= --}}
            {{-- ITEMS --}}
            {{-- ========================================================= --}}

            <div id="items" class="tab-content hidden space-y-4">

                {{-- HEADER --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Items
                        </h2>

                        <p class="text-xs text-slate-400">
                            Items assigned to this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                            {{ $itemCount ?? collect($items ?? [])->count() }} Items
                        </span>

                        <a href="#"
                        class="inline-flex items-center gap-2 px-4 py-2
                                text-xs font-bold rounded-xl
                                bg-orange-600 text-white
                                hover:bg-orange-700
                                shadow-sm transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>
                            </svg>

                            Upload Items
                        </a>

                    </div>

                </div>


                {{-- SEARCH / FILTERS --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        {{-- Search --}}
                        <div class="md:col-span-2 relative">

                            <input
                                type="text"
                                id="itemSearch"
                                placeholder="Search item name, description..."
                                class="w-full pl-10 pr-4 py-2.5
                                    text-sm rounded-xl
                                    border border-slate-200
                                    focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="absolute left-3 top-3 w-4 h-4 text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>

                        </div>


                        {{-- Item Type / Category --}}
                        <select
                            id="itemTypeFilter"
                            class="w-full py-2.5 px-3
                                text-sm rounded-xl
                                border border-slate-200
                                focus:ring-2 focus:ring-blue-500
                                focus:border-blue-500">

                            <option value="">All Items</option>

                            @foreach(($items ?? collect())->pluck('item_type')->filter()->unique()->sort() as $type)
                                <option value="{{ strtolower($type) }}">
                                    {{ $type }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- ITEMS TABLE --}}
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-slate-50 border-b border-slate-200">

                                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wide">

                                    <th class="px-5 py-3">
                                        #
                                    </th>

                                    <th class="px-5 py-3">
                                        Item
                                    </th>

                                    <th class="px-5 py-3">
                                        Description
                                    </th>

                                    <th class="px-5 py-3">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="itemsTableBody"
                                class="divide-y divide-slate-100">

                                @forelse(($items ?? []) as $index => $item)

                                    @php
                                        $itemName =
                                            $item->item_name
                                            ?? $item->name
                                            ?? $item->item
                                            ?? 'Unnamed Item';

                                        $description =
                                            $item->description
                                            ?? $item->item_description
                                            ?? '—';

                                        $itemType =
                                            $item->item_type
                                            ?? $item->type
                                            ?? '—';

                                        $itemId =
                                            $item->item_id
                                            ?? $item->id
                                            ?? null;
                                    @endphp

                                    <tr
                                        class="item-row hover:bg-slate-50 transition"
                                        data-search="{{ strtolower($itemName . ' ' . $description . ' ' . $itemType) }}"
                                        data-type="{{ strtolower($itemType) }}">

                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4 text-xs text-slate-400 font-semibold item-number">
                                            {{ $index + 1 }}
                                        </td>


                                        {{-- ITEM --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $itemName }}
                                            </div>

                                            @if($itemId)
                                                <div class="text-[11px] text-slate-400 mt-1">
                                                    ID: {{ $itemId }}
                                                </div>
                                            @endif

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-5 py-4 text-slate-600 max-w-md">

                                            <div class="truncate"
                                                title="{{ $description }}">
                                                {{ $description }}
                                            </div>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex px-2.5 py-1
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-600
                                                        text-xs font-semibold">
                                                {{ $itemType }}
                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex items-center gap-2">

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-blue-50 text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs font-bold transition">
                                                    View
                                                </a>

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-slate-100 text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs font-bold transition">
                                                    Edit
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="itemsEmptyRow">
                                        <td colspan="5"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>
                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No items found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No items are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- PAGINATION --}}
                    {{-- ========================================================= --}}

                    <div id="itemsPagination"
                        class="flex flex-col sm:flex-row
                                items-center justify-between
                                gap-3 px-5 py-4
                                border-t border-slate-200
                                bg-slate-50">

                        {{-- Result information --}}
                        <div class="text-xs text-slate-500">
                            Showing
                            <span id="itemsShowingStart"
                                class="font-bold text-slate-700">0</span>
                            -
                            <span id="itemsShowingEnd"
                                class="font-bold text-slate-700">0</span>
                            of
                            <span id="itemsTotal"
                                class="font-bold text-slate-700">0</span>
                            items
                        </div>


                        {{-- Pagination buttons --}}
                        <div id="itemsPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- ITEMS SEARCH / FILTER / PAGINATION --}}
            {{-- ========================================================= --}}

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const searchInput = document.getElementById('itemSearch');
                    const typeFilter = document.getElementById('itemTypeFilter');

                    const rows = Array.from(
                        document.querySelectorAll('.item-row')
                    );

                    const paginationButtons =
                        document.getElementById('itemsPaginationButtons');

                    const showingStart =
                        document.getElementById('itemsShowingStart');

                    const showingEnd =
                        document.getElementById('itemsShowingEnd');

                    const totalItems =
                        document.getElementById('itemsTotal');


                    // =========================================================
                    // SETTINGS
                    // =========================================================

                    const itemsPerPage = 10;

                    let currentPage = 1;

                    let filteredRows = [...rows];


                    // =========================================================
                    // FILTER
                    // =========================================================

                    function filterItems() {

                        const search =
                            (searchInput?.value || '')
                                .toLowerCase()
                                .trim();

                        const type =
                            (typeFilter?.value || '')
                                .toLowerCase();

                        filteredRows = rows.filter(row => {

                            const rowSearch =
                                row.dataset.search || '';

                            const rowType =
                                row.dataset.type || '';

                            const matchesSearch =
                                !search ||
                                rowSearch.includes(search);

                            const matchesType =
                                !type ||
                                rowType === type;

                            return matchesSearch && matchesType;

                        });


                        currentPage = 1;

                        renderPagination();

                    }


                    // =========================================================
                    // PAGINATION
                    // =========================================================

                    function renderPagination() {

                        const total =
                            filteredRows.length;

                        const totalPages =
                            Math.max(
                                1,
                                Math.ceil(total / itemsPerPage)
                            );


                        // Prevent invalid page
                        if (currentPage > totalPages) {
                            currentPage = totalPages;
                        }


                        // Hide all rows first
                        rows.forEach(row => {
                            row.classList.add('hidden');
                        });


                        // Calculate current page
                        const start =
                            (currentPage - 1) * itemsPerPage;

                        const end =
                            Math.min(
                                start + itemsPerPage,
                                total
                            );


                        // Show current page rows
                        for (let i = start; i < end; i++) {

                            filteredRows[i].classList.remove('hidden');

                            const number =
                                filteredRows[i].querySelector('.item-number');

                            if (number) {
                                number.textContent = i + 1;
                            }

                        }


                        // Result information
                        showingStart.textContent =
                            total > 0 ? start + 1 : 0;

                        showingEnd.textContent =
                            end;

                        totalItems.textContent =
                            total;


                        // Render buttons
                        renderPaginationButtons(totalPages);

                    }


                    // =========================================================
                    // PAGINATION BUTTONS
                    // =========================================================

                    function renderPaginationButtons(totalPages) {

                        paginationButtons.innerHTML = '';


                        // No results
                        if (filteredRows.length === 0) {
                            return;
                        }


                        // Previous
                        const previous =
                            createPaginationButton(
                                '‹',
                                currentPage - 1,
                                currentPage === 1
                            );

                        paginationButtons.appendChild(previous);


                        // Page numbers
                        const maxVisiblePages = 5;

                        let startPage =
                            Math.max(
                                1,
                                currentPage - 2
                            );

                        let endPage =
                            Math.min(
                                totalPages,
                                startPage + maxVisiblePages - 1
                            );


                        if (
                            endPage - startPage + 1 <
                            maxVisiblePages
                        ) {
                            startPage =
                                Math.max(
                                    1,
                                    endPage - maxVisiblePages + 1
                                );
                        }


                        // First page + ellipsis
                        if (startPage > 1) {

                            paginationButtons.appendChild(
                                createPaginationButton('1', 1)
                            );

                            if (startPage > 2) {

                                const dots =
                                    document.createElement('span');

                                dots.className =
                                    'px-2 text-slate-400 text-sm';

                                dots.textContent = '...';

                                paginationButtons.appendChild(dots);
                            }

                        }


                        // Pages
                        for (
                            let page = startPage;
                            page <= endPage;
                            page++
                        ) {

                            paginationButtons.appendChild(
                                createPaginationButton(
                                    page,
                                    page,
                                    false,
                                    page === currentPage
                                )
                            );

                        }


                        // Last page + ellipsis
                        if (endPage < totalPages) {

                            if (endPage < totalPages - 1) {

                                const dots =
                                    document.createElement('span');

                                dots.className =
                                    'px-2 text-slate-400 text-sm';

                                dots.textContent = '...';

                                paginationButtons.appendChild(dots);
                            }


                            paginationButtons.appendChild(
                                createPaginationButton(
                                    totalPages,
                                    totalPages
                                )
                            );

                        }


                        // Next
                        const next =
                            createPaginationButton(
                                '›',
                                currentPage + 1,
                                currentPage === totalPages
                            );

                        paginationButtons.appendChild(next);

                    }


                    // =========================================================
                    // CREATE PAGINATION BUTTON
                    // =========================================================

                    function createPaginationButton(
                        label,
                        page,
                        disabled = false,
                        active = false
                    ) {

                        const button =
                            document.createElement('button');

                        button.type = 'button';

                        button.textContent = label;

                        button.className =
                            'min-w-[32px] h-8 px-2 rounded-lg ' +
                            'text-xs font-bold transition ' +
                            (
                                active
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-slate-600 ' +
                                    'border border-slate-200 ' +
                                    'hover:bg-slate-100'
                            );


                        if (disabled) {

                            button.disabled = true;

                            button.classList.add(
                                'opacity-40',
                                'cursor-not-allowed'
                            );

                        } else {

                            button.addEventListener(
                                'click',
                                function () {

                                    currentPage = page;

                                    renderPagination();

                                }
                            );

                        }

                        return button;

                    }


                    // =========================================================
                    // EVENTS
                    // =========================================================

                    searchInput?.addEventListener(
                        'input',
                        filterItems
                    );

                    typeFilter?.addEventListener(
                        'change',
                        filterItems
                    );


                    // =========================================================
                    // INITIAL LOAD
                    // =========================================================

                    renderPagination();

                });
            </script>
            {{-- ========================================================= --}}
            {{-- PACKAGES --}}
            {{-- ========================================================= --}}

            <div id="packages" class="tab-content hidden space-y-4">

                {{-- HEADER --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Packages
                        </h2>

                        <p class="text-xs text-slate-400">
                            Packages associated with this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        <span class="px-3 py-1 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">
                            {{ $packageCount ?? collect($packages ?? [])->count() }} Packages
                        </span>

                        <a href="#"
                        class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl
                                bg-cyan-600 text-white
                                hover:bg-cyan-700
                                shadow-sm transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>
                            </svg>

                            Upload Packages
                        </a>

                    </div>

                </div>


                {{-- SEARCH / FILTERS --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl p-4 shadow-sm">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        {{-- SEARCH --}}
                        <div class="md:col-span-2 relative">

                            <input
                                type="text"
                                id="packageSearch"
                                placeholder="Search package name, code..."
                                class="w-full pl-10 pr-4 py-2.5
                                    text-sm rounded-xl
                                    border border-slate-200
                                    focus:ring-2 focus:ring-cyan-500
                                    focus:border-cyan-500">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="absolute left-3 top-3 w-4 h-4
                                        text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>

                        </div>


                        {{-- PACKAGE TYPE --}}
                        <select
                            id="packageTypeFilter"
                            class="w-full py-2.5 px-3
                                text-sm rounded-xl
                                border border-slate-200
                                focus:ring-2 focus:ring-cyan-500
                                focus:border-cyan-500">

                            <option value="">
                                All Packages
                            </option>

                            @foreach(($packages ?? collect())->map(function ($package) {
                                return $package->package_type
                                    ?? $package->type
                                    ?? null;
                            })->filter()->unique()->sort() as $type)

                                <option value="{{ strtolower($type) }}">
                                    {{ $type }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- PACKAGES TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-slate-50
                                        border-b border-slate-200">

                                <tr class="text-left text-xs font-bold
                                        text-slate-500 uppercase tracking-wide">

                                    <th class="px-5 py-3">
                                        #
                                    </th>

                                    <th class="px-5 py-3">
                                        Package
                                    </th>

                                    <th class="px-5 py-3">
                                        Code
                                    </th>

                                    <th class="px-5 py-3">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="packagesTableBody"
                                class="divide-y divide-slate-100">

                                @forelse(($packages ?? []) as $index => $package)

                                    @php

                                        $packageName =
                                            $package->package_name
                                            ?? $package->name
                                            ?? $package->package
                                            ?? 'Unnamed Package';

                                        $packageCode =
                                            $package->package_code
                                            ?? $package->code
                                            ?? $package->package_id
                                            ?? '—';

                                        $packageType =
                                            $package->package_type
                                            ?? $package->type
                                            ?? '—';

                                        $packageId =
                                            $package->package_id
                                            ?? $package->id
                                            ?? null;

                                    @endphp


                                    <tr
                                        class="package-row
                                            hover:bg-slate-50
                                            transition"

                                        data-search="{{ strtolower(
                                            $packageName . ' ' .
                                            $packageCode . ' ' .
                                            $packageType
                                        ) }}"

                                        data-type="{{ strtolower($packageType) }}">


                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4
                                                text-xs text-slate-400
                                                font-semibold package-number">

                                            {{ $index + 1 }}

                                        </td>


                                        {{-- PACKAGE --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $packageName }}
                                            </div>

                                            @if($packageId)

                                                <div class="text-[11px]
                                                            text-slate-400 mt-1">

                                                    ID: {{ $packageId }}

                                                </div>

                                            @endif

                                        </td>


                                        {{-- CODE --}}
                                        <td class="px-5 py-4">

                                            <span class="text-xs
                                                        font-semibold
                                                        text-slate-600">

                                                {{ $packageCode }}

                                            </span>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex
                                                        px-2.5 py-1
                                                        rounded-lg
                                                        bg-cyan-50
                                                        text-cyan-700
                                                        text-xs
                                                        font-semibold">

                                                {{ $packageType }}

                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex
                                                        items-center gap-2">

                                                <a href="#"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    View

                                                </a>

                                                <a href="#"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    Edit

                                                </a>

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="5"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col
                                                        items-center">

                                                <div class="w-12 h-12
                                                            rounded-full
                                                            bg-slate-100
                                                            flex items-center
                                                            justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6
                                                                text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>

                                                    </svg>

                                                </div>


                                                <p class="font-bold
                                                        text-slate-600">

                                                    No packages found

                                                </p>


                                                <p class="text-xs
                                                        text-slate-400 mt-1">

                                                    No packages are currently
                                                    associated with this project.

                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- PAGINATION --}}
                    {{-- ===================================================== --}}

                    <div id="packagesPagination"
                        class="flex flex-col sm:flex-row
                                items-center justify-between
                                gap-3 px-5 py-4
                                border-t border-slate-200
                                bg-slate-50">


                        {{-- RESULT INFORMATION --}}
                        <div class="text-xs text-slate-500">

                            Showing

                            <span id="packagesShowingStart"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            -

                            <span id="packagesShowingEnd"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            of

                            <span id="packagesTotal"
                                class="font-bold text-slate-700">
                                0
                            </span>

                            packages

                        </div>


                        {{-- PAGINATION BUTTONS --}}
                        <div id="packagesPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- PACKAGES SEARCH / FILTER / PAGINATION --}}
            {{-- ========================================================= --}}

            <script>
            document.addEventListener('DOMContentLoaded', function () {

                const searchInput =
                    document.getElementById('packageSearch');

                const typeFilter =
                    document.getElementById('packageTypeFilter');

                const rows =
                    Array.from(
                        document.querySelectorAll('.package-row')
                    );

                const paginationButtons =
                    document.getElementById(
                        'packagesPaginationButtons'
                    );

                const showingStart =
                    document.getElementById(
                        'packagesShowingStart'
                    );

                const showingEnd =
                    document.getElementById(
                        'packagesShowingEnd'
                    );

                const totalPackages =
                    document.getElementById(
                        'packagesTotal'
                    );


                // =========================================================
                // SETTINGS
                // =========================================================

                const packagesPerPage = 10;

                let currentPage = 1;

                let filteredRows = [...rows];


                // =========================================================
                // FILTER
                // =========================================================

                function filterPackages() {

                    const search =
                        (searchInput?.value || '')
                            .toLowerCase()
                            .trim();

                    const type =
                        (typeFilter?.value || '')
                            .toLowerCase();


                    filteredRows = rows.filter(row => {

                        const rowSearch =
                            row.dataset.search || '';

                        const rowType =
                            row.dataset.type || '';


                        const matchesSearch =
                            !search ||
                            rowSearch.includes(search);


                        const matchesType =
                            !type ||
                            rowType === type;


                        return matchesSearch && matchesType;

                    });


                    currentPage = 1;

                    renderPagination();

                }


                // =========================================================
                // PAGINATION
                // =========================================================

                function renderPagination() {

                    const total =
                        filteredRows.length;


                    const totalPages =
                        Math.max(
                            1,
                            Math.ceil(
                                total / packagesPerPage
                            )
                        );


                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }


                    // Hide every row
                    rows.forEach(row => {
                        row.classList.add('hidden');
                    });


                    const start =
                        (currentPage - 1) *
                        packagesPerPage;


                    const end =
                        Math.min(
                            start + packagesPerPage,
                            total
                        );


                    // Show current page
                    for (
                        let i = start;
                        i < end;
                        i++
                    ) {

                        filteredRows[i]
                            .classList.remove('hidden');


                        const number =
                            filteredRows[i]
                                .querySelector(
                                    '.package-number'
                                );


                        if (number) {
                            number.textContent =
                                i + 1;
                        }

                    }


                    // Result information
                    showingStart.textContent =
                        total > 0
                            ? start + 1
                            : 0;

                    showingEnd.textContent =
                        end;

                    totalPackages.textContent =
                        total;


                    renderPaginationButtons(
                        totalPages
                    );

                }


                // =========================================================
                // PAGINATION BUTTONS
                // =========================================================

                function renderPaginationButtons(
                    totalPages
                ) {

                    paginationButtons.innerHTML = '';


                    if (filteredRows.length === 0) {
                        return;
                    }


                    // PREVIOUS
                    paginationButtons.appendChild(
                        createPaginationButton(
                            '‹',
                            currentPage - 1,
                            currentPage === 1
                        )
                    );


                    const maxVisiblePages = 5;


                    let startPage =
                        Math.max(
                            1,
                            currentPage - 2
                        );


                    let endPage =
                        Math.min(
                            totalPages,
                            startPage +
                            maxVisiblePages -
                            1
                        );


                    if (
                        endPage -
                        startPage +
                        1 <
                        maxVisiblePages
                    ) {

                        startPage =
                            Math.max(
                                1,
                                endPage -
                                maxVisiblePages +
                                1
                            );

                    }


                    // FIRST PAGE
                    if (startPage > 1) {

                        paginationButtons.appendChild(
                            createPaginationButton(
                                '1',
                                1
                            )
                        );


                        if (startPage > 2) {

                            const dots =
                                document.createElement(
                                    'span'
                                );

                            dots.className =
                                'px-2 text-slate-400 text-sm';

                            dots.textContent =
                                '...';

                            paginationButtons.appendChild(
                                dots
                            );

                        }

                    }


                    // PAGE NUMBERS
                    for (
                        let page = startPage;
                        page <= endPage;
                        page++
                    ) {

                        paginationButtons.appendChild(
                            createPaginationButton(
                                page,
                                page,
                                false,
                                page === currentPage
                            )
                        );

                    }


                    // LAST PAGE
                    if (endPage < totalPages) {

                        if (endPage < totalPages - 1) {

                            const dots =
                                document.createElement(
                                    'span'
                                );

                            dots.className =
                                'px-2 text-slate-400 text-sm';

                            dots.textContent =
                                '...';

                            paginationButtons.appendChild(
                                dots
                            );

                        }


                        paginationButtons.appendChild(
                            createPaginationButton(
                                totalPages,
                                totalPages
                            )
                        );

                    }


                    // NEXT
                    paginationButtons.appendChild(
                        createPaginationButton(
                            '›',
                            currentPage + 1,
                            currentPage === totalPages
                        )
                    );

                }


                // =========================================================
                // CREATE BUTTON
                // =========================================================

                function createPaginationButton(
                    label,
                    page,
                    disabled = false,
                    active = false
                ) {

                    const button =
                        document.createElement(
                            'button'
                        );


                    button.type = 'button';

                    button.textContent = label;


                    button.className =
                        'min-w-[32px] h-8 px-2 ' +
                        'rounded-lg text-xs font-bold ' +
                        'transition ' +
                        (
                            active
                                ? 'bg-cyan-600 text-white'
                                : 'bg-white text-slate-600 ' +
                                'border border-slate-200 ' +
                                'hover:bg-slate-100'
                        );


                    if (disabled) {

                        button.disabled = true;

                        button.classList.add(
                            'opacity-40',
                            'cursor-not-allowed'
                        );

                    } else {

                        button.addEventListener(
                            'click',
                            function () {

                                currentPage = page;

                                renderPagination();

                            }
                        );

                    }


                    return button;

                }


                // =========================================================
                // EVENTS
                // =========================================================

                searchInput?.addEventListener(
                    'input',
                    filterPackages
                );

                typeFilter?.addEventListener(
                    'change',
                    filterPackages
                );


                // =========================================================
                // INITIAL LOAD
                // =========================================================

                renderPagination();

            });
            </script>
            {{-- ========================================================= --}}
            {{-- PROJECT SETTINGS --}}
            {{-- ========================================================= --}}

            <div id="setting" class="tab-content hidden space-y-6">

                {{-- HEADER --}}
                <div>
                    <h2 class="text-lg font-black text-slate-900">
                        Project Settings
                    </h2>

                    <p class="text-xs text-slate-400">
                        Configure AR and label settings for this project.
                    </p>
                </div>


                {{-- ========================================================= --}}
                {{-- SETTINGS FORM --}}
                {{-- ========================================================= --}}

                <form method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6">

                    @csrf
                    @method('PUT')

                    <input type="hidden"
                        name="project_id"
                        value="{{ $project->project_id }}">


                    {{-- ===================================================== --}}
                    {{-- AR SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                AR Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-5">


                            {{-- PROJECT NAME --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Project Name

                                </label>

                                <input
                                    type="text"
                                    name="project_name"
                                    value="{{ old(
                                        'project_name',
                                        $arSettings->project_name
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('project_name')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Company

                                </label>

                                <input
                                    type="text"
                                    name="company"
                                    value="{{ old(
                                        'company',
                                        $arSettings->company
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('company')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CLIENT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Client

                                </label>

                                <input
                                    type="text"
                                    name="client"
                                    value="{{ old(
                                        'client',
                                        $arSettings->client
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('client')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- DISPLAY LABEL --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_label"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_label"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_label',
                                            $arSettings->display_label
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Label

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Enable label information on AR output.
                                    </p>

                                </div>

                            </div>


                            {{-- DISPLAY SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_school_id',
                                            $arSettings->display_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Include the school ID in the AR.
                                    </p>

                                </div>

                            </div>


                            {{-- FOOTER COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Company Name

                                </label>

                                <input
                                    type="text"
                                    name="ar_company_footer"
                                    value="{{ old(
                                        'ar_company_footer',
                                        $arSettings->ar_company_footer
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    This will appear under the signature
                                    in the AR PDF.
                                </p>

                                @error('ar_company_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER ADDRESS --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Address

                                </label>

                                <textarea
                                    name="ar_address_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_address_footer',
                                                $arSettings->ar_address_footer
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Full address shown at the bottom of the AR.
                                </p>

                                @error('ar_address_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER CONTACT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Contact

                                </label>

                                <textarea
                                    name="ar_contact_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_contact_footer',
                                                $arSettings->ar_contact_footer ?? ''
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Contact details shown at the bottom of the AR.
                                </p>

                                @error('ar_contact_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- SELECT LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Select Logo

                                </label>

                                <select
                                    name="ar_logo"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                    @forelse(($logoFiles ?? []) as $logo)

                                        <option
                                            value="{{ $logo }}"
                                            @selected(
                                                ($arSettings->ar_logo ?? 'logo.webp')
                                                === $logo
                                            )>

                                            {{ $logo }}

                                        </option>

                                    @empty

                                        <option value="logo.webp">
                                            logo.webp
                                        </option>

                                    @endforelse

                                </select>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Choose from existing logos.
                                </p>

                                @error('ar_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- UPLOAD NEW LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Or Upload New Logo

                                </label>

                                <input
                                    type="file"
                                    name="new_logo"
                                    accept=".png,.jpg,.jpeg,.webp"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        file:mr-4
                                        file:py-1.5
                                        file:px-3
                                        file:rounded-lg
                                        file:border-0
                                        file:text-xs
                                        file:font-bold
                                        file:bg-blue-50
                                        file:text-blue-700">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Uploading a file will override the selected logo.
                                </p>

                                @error('new_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CURRENT LOGO --}}
                            @if (!empty($arSettings->ar_logo))

                                <div class="pt-4 border-t border-slate-200">

                                    <label class="block text-xs
                                                font-bold uppercase
                                                text-slate-500 mb-3">

                                        Current Logo Preview

                                    </label>

                                    <div class="inline-flex
                                                items-center justify-center
                                                p-4
                                                bg-slate-50
                                                border border-slate-200
                                                rounded-xl">

                                        <img
                                            src="{{ asset(
                                                'assets/uploads/logo/' .
                                                $arSettings->ar_logo
                                            ) }}"
                                            alt="Current Logo"
                                            class="max-h-24 max-w-xs
                                                object-contain">

                                    </div>

                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- LABEL SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                Label Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-3">


                            {{-- SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_school_id',
                                            $arSettings->label_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the school ID on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- MUNICIPALITY --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_municipality"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_municipality"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_municipality',
                                            $arSettings->label_municipality
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Municipality

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the municipality on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- DIVISION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_division"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_division"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_division',
                                            $arSettings->label_division
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Division

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the division on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- REGION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_region"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_region"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_region',
                                            $arSettings->label_region
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Region

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the region on generated labels.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- SAVE --}}
                    {{-- ===================================================== --}}

                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2
                                px-6 py-3
                                text-sm font-bold
                                rounded-xl
                                bg-emerald-600
                                text-white
                                hover:bg-emerald-700
                                shadow-sm
                                transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"/>

                            </svg>

                            Save Settings

                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
</div>


{{-- ============================================================= --}}
{{-- TAB SCRIPT --}}
{{-- ============================================================= --}}

<script>
function openTab(event, tabName) {

    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove(
            'border-blue-600',
            'text-blue-600',
            'bg-white'
        );

        btn.classList.add(
            'border-transparent',
            'text-slate-500'
        );
    });

    const content = document.getElementById(tabName);

    if (content) {
        content.classList.remove('hidden');
    }

    const btn = event.currentTarget;

    btn.classList.remove(
        'border-transparent',
        'text-slate-500'
    );

    btn.classList.add(
        'border-blue-600',
        'text-blue-600',
        'bg-white'
    );

    localStorage.setItem(
        'activeProjectTab',
        tabName
    );
}


document.addEventListener('DOMContentLoaded', () => {

    const saved = localStorage.getItem('activeProjectTab');

    if (saved) {

        const btn = document.querySelector(
            `[data-tab="${saved}"]`
        );

        if (btn) {
            btn.click();
        }

    }

});
</script>

</x-project_app-layout>