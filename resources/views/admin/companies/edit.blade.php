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
                Edit Company
            </span>

        </nav>


        {{-- ================= HEADER ================= --}}
        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                Edit Company
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Update the information and status of this company.
            </p>

        </div>


        {{-- ================= SUCCESS ================= --}}
        @if(session('success'))

            <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">

                <svg class="w-5 h-5 shrink-0"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 13l4 4L19 7"
                    />
                </svg>

                {{ session('success') }}

            </div>

        @endif


        {{-- ================= ERRORS ================= --}}
        @if($errors->any())

            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4">

                <p class="text-sm font-semibold text-rose-700">
                    Please correct the following errors:
                </p>

                <ul class="mt-2 list-disc list-inside text-xs text-rose-600 space-y-1">

                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ================= COMPANY FORM ================= --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

            <div class="p-6 border-b border-slate-100">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">

                        <svg class="w-5 h-5"
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

                    </div>

                    <div>

                        <h2 class="text-base font-bold text-slate-900">
                            {{ $company->name }}
                        </h2>

                        <p class="text-xs text-slate-500">
                            Company ID: {{ $company->company_id }}
                        </p>

                    </div>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('companies.update', $company) }}"
            >

                @csrf
                @method('PUT')

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
                            value="{{ old('name', $company->name) }}"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition"
                        >

                        @error('name')
                            <p class="mt-1.5 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- COMPANY CODE --}}
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
                            value="{{ old('code', $company->code) }}"
                            required
                            maxlength="50"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono uppercase text-slate-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition"
                        >

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            This code must remain unique.
                        </p>

                        @error('code')
                            <p class="mt-1.5 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- STATUS --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <label class="flex items-center gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $company->is_active) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <div>

                                <span class="block text-sm font-semibold text-slate-800">
                                    Active company
                                </span>

                                <span class="block text-xs text-slate-500 mt-0.5">
                                    Users can access this company when it is active.
                                </span>

                            </div>

                        </label>

                    </div>


                    {{-- COMPANY USERS --}}
                    <div class="rounded-xl border border-slate-200 overflow-hidden">

                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">

                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Company Users
                            </h3>

                        </div>

                        <div class="p-4">

                            @if($company->users->count())

                                <div class="space-y-2">

                                    @foreach($company->users as $user)

                                        <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-slate-100">

                                            <div class="flex items-center gap-3">

                                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-600">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>

                                                <div>

                                                    <div class="text-xs font-semibold text-slate-800">
                                                        {{ $user->name }}
                                                    </div>

                                                    <div class="text-[10px] text-slate-400">
                                                        {{ $user->email }}
                                                    </div>

                                                </div>

                                            </div>

                                            <span class="text-[10px] text-slate-400">
                                                {{ $user->employee_id }}
                                            </span>

                                        </div>

                                    @endforeach

                                </div>

                            @else

                                <div class="text-center py-5">

                                    <p class="text-xs text-slate-400">
                                        No users assigned to this company yet.
                                    </p>

                                </div>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- ================= ACTIONS ================= --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">

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
                             stroke-width="2"
                             viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>