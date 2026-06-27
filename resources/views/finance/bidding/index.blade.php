<x-finance_app-layout>

    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-base font-black text-slate-900 tracking-tight sm:text-lg">
                Bidding Documents
            </h1>

            <p class="text-[11px] font-semibold text-slate-400 tracking-wide uppercase mt-0.5">
                Procurement & Bidding Project Management
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">

        <a href="{{ route('bidding.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold tracking-wide uppercase bg-blue-600 text-white rounded-xl hover:bg-blue-500 active:scale-95 transition shadow-md shadow-blue-600/10 h-[36px]">

            <svg class="w-3.5 h-3.5 mr-1.5 stroke-[3]"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"/>

            </svg>

            Create New

        </a>

    </x-slot>

    <div class="space-y-5">

        {{-- Search --}}
        @include('finance.bidding.partials._search')

        {{-- Table --}}
        @include('finance.bidding.partials._table')

    </div>

</x-finance_app-layout>