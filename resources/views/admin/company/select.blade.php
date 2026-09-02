<x-guest-layout>

    <div class="max-w-md mx-auto">

        {{-- Header --}}
        <div class="text-center mb-8">

            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl text-white mb-4">
                <svg
                    class="w-6 h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"
                    />
                </svg>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900">
                Select Company
            </h2>

            <p class="text-sm text-slate-500 mt-2">
                Choose the company you want to access.
            </p>

        </div>

        {{-- Company List --}}
        <form
            method="POST"
            action="{{ route('company.select.store') }}"
            class="space-y-3"
        >
            @csrf

            @foreach($companies as $company)

                <button
                    type="submit"
                    name="company_id"
                    value="{{ $company->company_id }}"
                    class="w-full text-left bg-white border border-slate-200 rounded-xl p-4 hover:border-blue-500 hover:bg-blue-50 transition-all duration-150"
                >

                    <div class="flex items-center gap-4">

                        {{-- Company Icon --}}
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"
                                />
                            </svg>

                        </div>

                        {{-- Company Information --}}
                        <div class="flex-1 min-w-0">

                            <div class="font-bold text-slate-900 truncate">
                                {{ $company->name }}
                            </div>

                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $company->code }}
                            </div>

                        </div>

                        {{-- Arrow --}}
                        <div class="text-slate-400 text-lg">
                            →
                        </div>

                    </div>

                </button>

            @endforeach

        </form>

        {{-- Logout --}}
        <div class="text-center mt-6">

            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="text-sm text-slate-500 hover:text-red-600 transition"
                >
                    Sign out
                </button>

            </form>

        </div>

    </div>

</x-guest-layout>