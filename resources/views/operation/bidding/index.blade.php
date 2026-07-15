<x-project_app-layout>

    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-lg font-semibold text-slate-900">
                Bidding Documents
            </h1>

            <p class="text-xs text-slate-500 mt-0.5">
                Procurement & Bidding Project Management
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">

        <a href="{{ route('bidding.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors">

            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>

            New Document

        </a>

    </x-slot>

    <div class="space-y-5">

        {{-- Search --}}
        @include('finance.bidding.partials._search')

        {{-- Table --}}
        @include('finance.bidding.partials._table')

    </div>

</x-project_app-layout>