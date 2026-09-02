<x-guest-layout>

    <div class="max-w-md mx-auto text-center">

        {{-- Icon --}}
        <div class="flex justify-center mb-6">

            <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">

                <svg
                    class="w-10 h-10"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m9.303 3.376L13.73 4.63a2 2 0 00-3.46 0L2.697 16.126A2 2 0 004.427 19h15.146a2 2 0 001.73-2.874z"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 16.5h.01"
                    />
                </svg>

            </div>

        </div>

        {{-- Title --}}
        <h1 class="text-2xl font-extrabold text-slate-900">
            Site Under Maintenance
        </h1>

        {{-- Message --}}
        <p class="mt-3 text-sm text-slate-500 leading-6">
            This site or module is currently unavailable for your account.
            Please try again later or contact the system administrator
            if you believe you should have access.
        </p>

        {{-- Information --}}
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4 text-left">

            <div class="flex gap-3">

                <svg
                    class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m9.303 3.376L13.73 4.63a2 2 0 00-3.46 0L2.697 16.126A2 2 0 004.427 19h15.146a2 2 0 001.73-2.874z"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 16.5h.01"
                    />
                </svg>

                <div>
                    <p class="text-sm font-bold text-amber-800">
                        Access Restricted
                    </p>

                    <p class="text-xs text-amber-700 mt-1">
                        Your account does not currently have access to
                        an available site or module.
                    </p>
                </div>

            </div>

        </div>

        {{-- Logout --}}
        <div class="mt-6">

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition"
                >
                    Sign Out
                </button>

            </form>

        </div>

    </div>

</x-guest-layout>