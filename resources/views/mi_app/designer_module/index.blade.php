<x-mi_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-200/80 dark:border-gray-800">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        Metroinc Centralized Database
                    </h1>
                </div>
                
                {{-- Live Status Pill --}}
                <div class="flex items-center gap-2 pt-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Connected
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                        Synced: <time>Just now</time>
                    </span>
                </div>
            </div>

            {{-- Action Button --}}
            <a href="{{ route('mi_app.create') }}" 
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add Product</span>
            </a>
        </div>

        {{-- Main Content Section --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-800 overflow-hidden">
            
            {{-- Search & Filters Wrapper --}}
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                @include('mi_app.designer_module.partials._search')
            </div>

            {{-- Data Table Wrapper --}}
            <div class="relative overflow-x-auto">
                @include('mi_app.designer_module.partials._table')
            </div>
            
        </div>

    </div>
</x-mi_app>