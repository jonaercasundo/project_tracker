<x-app-layout>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

        {{-- ================= BREADCRUMB ================= --}}
        <nav class="flex items-center gap-2 text-sm mb-6" aria-label="Breadcrumb">

            <a
                href="{{ route('admin.dashboard') }}"
                class="text-slate-500 hover:text-blue-600 transition-colors"
            >
                Dashboard
            </a>

            <svg class="w-4 h-4 text-slate-300"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 5l7 7-7 7"
                />
            </svg>

            <a
                href="{{ route('companies.index') }}"
                class="text-slate-500 hover:text-blue-600 transition-colors"
            >
                Company Management
            </a>

            <svg class="w-4 h-4 text-slate-300"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 5l7 7-7 7"
                />
            </svg>

            <span class="font-medium text-slate-900">
                Add Company
            </span>

        </nav>


        {{-- ================= HEADER ================= --}}
        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                Add Company
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Create a new company that can be assigned to users.
            </p>

        </div>


        {{-- ================= VALIDATION ERRORS ================= --}}
        @if($errors->any())

            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4">

                <div class="flex gap-3">

                    <svg class="w-5 h-5 text-rose-500 shrink-0"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                        />
                    </svg>

                    <div>

                        <p class="text-sm font-semibold text-rose-700">
                            Please correct the following errors:
                        </p>

                        <ul class="mt-2 list-disc list-inside text-xs text-rose-600 space-y-1">

                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach

                        </ul>

                    </div>

                </div>

            </div>

        @endif


        {{-- ================= FORM ================= --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

            <div class="p-6 border-b border-slate-100">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">

                        <svg class="w-5 h-5"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-bold text-slate-900">
                            Company Information
                        </h2>

                        <p class="text-xs text-slate-500">
                            Enter the basic details of the company.
                        </p>

                    </div>

                </div>

            </div>


            <form method="POST" action="{{ route('companies.store') }}">

                @csrf

                <div class="p-6 space-y-6">

                    {{-- COMPANY NAME --}}
                    <div>

                        <label
                            for="name"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"
                        >
                            Company Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            placeholder="e.g. Metro Mobilia Corporation"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition"
                        >

                        @error('name')
                            <p class="mt-1.5 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- CODE --}}
                    <div>

                        <label
                            for="code"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"
                        >
                            Company Code
                        </label>

                        <input
                            id="code"
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            required
                            maxlength="50"
                            placeholder="e.g. MMC"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono uppercase text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition"
                        >

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            A short unique identifier for the company.
                        </p>

                        @error('code')
                            <p class="mt-1.5 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- ACTIVE --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <label class="flex items-center gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <div>

                                <span class="block text-sm font-semibold text-slate-800">
                                    Active company
                                </span>

                                <span class="block text-xs text-slate-500 mt-0.5">
                                    Allow this company to be selected and assigned to users.
                                </span>

                            </div>

                        </label>

                    </div>

                </div>


                {{-- ACTIONS --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">

                    <a
                        href="{{ route('companies.index') }}"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold text-slate-600 transition-colors"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-sm"
                    >

                        <svg class="w-4 h-4"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.5"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>

                        Create Company

                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>