<x-app-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

        {{-- ================= BREADCRUMB ================= --}}
        <nav class="flex items-center gap-2 text-sm mb-6" aria-label="Breadcrumb">

            <a
                href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-slate-500 hover:text-blue-600 transition-colors"
            >
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 10.5L12 3l9 7.5M5.25 9v10.5a1.5 1.5 0 001.5 1.5h10.5a1.5 1.5 0 001.5-1.5V9"
                    />
                </svg>

                Dashboard
            </a>

            <svg class="w-4 h-4 text-slate-300"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24"
                 aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 5l7 7-7 7"
                />
            </svg>

            <span class="font-medium text-slate-900">
                Company Management
            </span>

        </nav>


        {{-- ================= SUCCESS MESSAGE ================= --}}
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">

                <svg class="w-5 h-5 shrink-0"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 13l4 4L19 7"
                    />
                </svg>

                <span>{{ session('success') }}</span>

            </div>
        @endif


        {{-- ================= HEADER ================= --}}
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm mb-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                <div>
                    <div class="flex items-center gap-3">

                        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"
                                />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-xl font-bold text-slate-900">
                                Company Management
                            </h1>

                            <p class="text-sm text-slate-500 mt-0.5">
                                Manage companies and their access.
                            </p>
                        </div>

                    </div>
                </div>


                {{-- ADD COMPANY --}}
                <a
                    href="{{ route('companies.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm"
                >

                    <svg class="w-4 h-4"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.5"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>

                    Add Company
                </a>

            </div>

        </div>


        {{-- ================= STATISTICS ================= --}}
        @php
            $totalCompanies = $companies->count();
            $activeCompanies = $companies->where('is_active', true)->count();
            $inactiveCompanies = $companies->where('is_active', false)->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">

            {{-- TOTAL --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <span class="text-xs uppercase tracking-wider font-bold text-slate-400">
                            Total Companies
                        </span>

                        <div class="text-2xl font-bold text-slate-900 mt-2">
                            {{ $totalCompanies }}
                        </div>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">

                        <svg class="w-5 h-5"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- ACTIVE --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <span class="text-xs uppercase tracking-wider font-bold text-slate-400">
                            Active
                        </span>

                        <div class="text-2xl font-bold text-emerald-600 mt-2">
                            {{ $activeCompanies }}
                        </div>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">

                        <svg class="w-5 h-5"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 12l4 4L19 6"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- INACTIVE --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <span class="text-xs uppercase tracking-wider font-bold text-slate-400">
                            Inactive
                        </span>

                        <div class="text-2xl font-bold text-slate-500 mt-2">
                            {{ $inactiveCompanies }}
                        </div>
                    </div>

                    <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">

                        <svg class="w-5 h-5"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- ================= COMPANY TABLE ================= --}}
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">

            <div class="p-6 border-b border-slate-100">

                <h2 class="text-base font-bold text-slate-900">
                    Companies
                </h2>

                <p class="text-xs text-slate-500 mt-0.5">
                    All companies registered in the system.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-slate-50 border-b border-slate-200">

                        <tr>

                            <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                Company
                            </th>

                            <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                Code
                            </th>

                            <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                Users
                            </th>

                            <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                Status
                            </th>

                            <th class="text-right px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($companies as $company)

                            <tr class="hover:bg-slate-50 transition-colors">

                                {{-- COMPANY --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                            {{ strtoupper(substr($company->name, 0, 2)) }}
                                        </div>

                                        <div>
                                            <div class="font-semibold text-slate-900">
                                                {{ $company->name }}
                                            </div>

                                            <div class="text-[11px] text-slate-400">
                                                ID: {{ $company->company_id }}
                                            </div>
                                        </div>

                                    </div>

                                </td>


                                {{-- CODE --}}
                                <td class="px-6 py-4">

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-mono font-semibold text-slate-700">
                                        {{ $company->code }}
                                    </span>

                                </td>


                                {{-- USERS --}}
                                <td class="px-6 py-4">

                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ $company->users_count ?? $company->users()->count() }}
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        users
                                    </span>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-4">

                                    @if($company->is_active)

                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-100 text-xs font-bold text-emerald-700">

                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                            Active

                                        </span>

                                    @else

                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-bold text-slate-500">

                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>

                                            Inactive

                                        </span>

                                    @endif

                                </td>


                                {{-- ACTIONS --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center justify-end gap-2">

                                        <a
                                            href="{{ route('companies.edit', $company) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs font-semibold text-slate-600 hover:text-blue-600 transition-colors"
                                        >

                                            <svg class="w-3.5 h-3.5"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                                                />
                                            </svg>

                                            Edit

                                        </a>


                                        <form
                                            method="POST"
                                            action="{{ route('companies.destroy', $company) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this company?');"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-xs font-semibold text-slate-600 hover:text-rose-600 transition-colors"
                                            >

                                                <svg class="w-3.5 h-3.5"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     stroke-width="2"
                                                     viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M6 7h12M9 7V4h6v3m-7 4v6m4-6v6m4-6v6M5 7l1 14h12l1-14"
                                                    />
                                                </svg>

                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="px-6 py-14 text-center">

                                    <div class="flex flex-col items-center">

                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">

                                            <svg class="w-7 h-7"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="1.5"
                                                 viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1"
                                                />
                                            </svg>

                                        </div>

                                        <p class="text-sm font-semibold text-slate-700">
                                            No companies found.
                                        </p>

                                        <p class="text-xs text-slate-400 mt-1">
                                            Create your first company to get started.
                                        </p>

                                        <a
                                            href="{{ route('companies.create') }}"
                                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold"
                                        >
                                            Add Company
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>