<x-mi_app>

<div class="mb-8 flex justify-between items-center">
    <div class="flex flex-col">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Metroinc Centralized Database</h1>
        <p class="text-xs text-gray-500 mt-1 font-mono">Status: Connected • Last synced: Just now</p>
    </div>

    <a href="{{ route('mi_app.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition duration-150">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Add Products
    </a>
</div>

    <div class="space-y-6">
        {{-- Search & Filters --}}
        @include('mi_app.designer_module.partials._search')

        {{-- Data Table --}}
        @include('mi_app.designer_module.partials._table')
    </div>

</x-mi_app>

