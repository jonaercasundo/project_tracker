<x-mi_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 text-white shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        class="h-6 w-6" 
                        fill="currentColor" 
                        viewBox="0 0 24 24">
                        <path d="M12 5c.55 0 1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-gray-400 dark:text-gray-500 mb-0.5">
                        <a href="{{ route('mi_app.index') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Product Database</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-500 dark:text-gray-400">New Product</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Create Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Fill in the specifications, dimensions, and details for the new product item.</p>
                </div>
            </div>

            <a href="{{ route('mi_app.index') }}"
               class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-200 text-sm font-semibold py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-700/80 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back to Database
            </a>
        </div>

        {{-- Required fields progress --}}
        <div class="flex items-center gap-3 -mt-2">
            <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div id="progress_bar" class="h-full bg-blue-500 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
            </div>
            <span id="progress_label" class="text-xs font-medium text-gray-400 dark:text-gray-500 shrink-0 tabular-nums">0 / 6 required fields</span>
        </div>

        {{-- Main Form Container --}}
        <form method="POST" action="{{ route('mi_app.store') }}" enctype="multipart/form-data" id="product_form" novalidate>
            @csrf

            <div class="space-y-8">

                {{-- SECTION 1: General Information --}}
                <div class="reveal bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">General Information</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Basic identity of the product</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-2">
                            <label for="item_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Item Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="item_name" name="item_name" value="{{ old('item_name') }}" placeholder="e.g. Ergonomic Office Desk" required
                                   data-required
                                   class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('item_name')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="category" name="category" value="{{ old('category') }}" placeholder="e.g. Furniture" required
                                   data-required
                                   class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('category')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="collection" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Collection</label>
                            <input type="text" id="collection" name="collection" value="{{ old('collection') }}" placeholder="e.g. Summer 2026"
                                   class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('collection') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="type_of_sample" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Type of Sample <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="type_of_sample" name="type_of_sample" value="{{ old('type_of_sample') }}" placeholder="e.g. Prototype" required
                                   data-required
                                   class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('type_of_sample')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="classification" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Classification <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select id="classification" name="classification" required data-required
                                        class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 pl-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="Available" {{ old('classification', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                                    <option value="Assigned" {{ old('classification') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                    <option value="Repair" {{ old('classification') == 'Repair' ? 'selected' : '' }}>Repair</option>
                                    <option value="Disposed" {{ old('classification') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            @error('classification') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label for="designed_by" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Designed By</label>
                            <input type="text" id="designed_by" name="designed_by" value="{{ old('designed_by') }}" placeholder="Designer full name"
                                   class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('designed_by') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Attributes & Product Dimensions --}}
                <div class="reveal bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25M21 7.5v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Attributes & Dimensions</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Physical properties and measurements</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="materials" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                    Materials <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="materials" name="materials" value="{{ old('materials') }}" placeholder="e.g. Oak, Aluminum" required
                                       data-required
                                       class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                @error('materials')
                                    <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Type</label>
                                <input type="text" id="type" name="type" value="{{ old('type') }}" placeholder="Product variant type"
                                       class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                @error('type') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="color" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Color</label>
                                <div class="relative">
                                    <input type="text" id="color" name="color" value="{{ old('color') }}" placeholder="e.g. Matte Black"
                                           class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 pl-4 pr-10 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <span id="color_swatch" class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-200 dark:bg-gray-700 transition-colors" aria-hidden="true"></span>
                                </div>
                                @error('color') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Product Dimensions Panel --}}
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 p-5">
                            <div class="flex items-center justify-between gap-4 mb-4">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product Dimensions</h3>
                                <span class="hidden sm:flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300 dark:text-gray-600" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3">
                                        <path d="M6 24 6 10 14 6 26 10 26 24 18 28 6 24Z" stroke-linejoin="round"/>
                                        <path d="M6 10 18 14 26 10" stroke-linejoin="round"/>
                                        <path d="M18 14 18 28" stroke-linejoin="round"/>
                                    </svg>
                                    H × W × L × D
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label for="product_height" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Height <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="product_height" name="product_height" value="{{ old('product_height') }}" placeholder="45" required data-required
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_height')
                                        <p class="flex items-center gap-1 text-rose-500 text-xs mt-1 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.75 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="product_width" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Width</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="product_width" name="product_width" value="{{ old('product_width') }}" placeholder="60"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_width') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="product_length" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Length</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="product_length" name="product_length" value="{{ old('product_length') }}" placeholder="120"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_length') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="product_depth" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Depth</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="product_depth" name="product_depth" value="{{ old('product_depth') }}" placeholder="30"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_depth') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Packaging & Attachments --}}
                <div class="reveal bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Packaging & Media</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Shipping footprint, cost, and reference file</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        {{-- Carton Dimensions Panel --}}
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 p-5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Carton Dimensions</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label for="carton_height" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Height</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_height" name="carton_height" value="{{ old('carton_height') }}" placeholder="50"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_height') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="carton_width" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Width</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_width" name="carton_width" value="{{ old('carton_width') }}" placeholder="65"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_width') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="carton_length" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Length</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_length" name="carton_length" value="{{ old('carton_length') }}" placeholder="125"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_length') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="carton_depth" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Depth</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" min="0" inputmode="decimal" id="carton_depth" name="carton_depth" value="{{ old('carton_depth') }}" placeholder="35"
                                               class="field w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_depth') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-900/70 p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Purchase Cost</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Estimated acquisition value for the item.</p>
                                        </div>
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 2.5a.75.75 0 01.75.75v.79a3.75 3.75 0 013.75 3.75V8.5h.25A1.75 1.75 0 0117.5 10.25v1.5a1.75 1.75 0 01-1.75 1.75h-.25v1.25a1.75 1.75 0 01-1.75 1.75H6.25A1.75 1.75 0 014.5 14.75V13.5H4.25A1.75 1.75 0 012.5 11.75v-1.5A1.75 1.75 0 014.25 8.5h.25v-.5a3.75 3.75 0 013.75-3.75V3.25A.75.75 0 0110 2.5zm-2 6v5.5h4V8.5H8zm-1.5 5.5h1.5V8.5H6.5v5.5zm6.5 0h1.5V8.5H13v5.5z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                            <span class="text-gray-500 dark:text-gray-400 text-sm font-semibold">₱</span>
                                        </div>
                                        <input type="number" step="0.01" min="0" inputmode="decimal" id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost') }}" placeholder="0.00"
                                               class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 pl-8 pr-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    </div>
                                    @error('purchase_cost') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                                </div>

                                <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-slate-50/70 dark:bg-gray-800/40 p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Reference Link</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Optional URL to a product page, spec sheet, or inspiration board.</p>
                                        </div>
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-5.25-4.5l5.25 5.25m0 0L13.5 10.5m5.25 1.5V3" />
                                            </svg>
                                        </div>
                                    </div>
                                    <input type="url" name="reference_link" value="{{ old('reference_link') }}" placeholder="https://example.com/product"
                                           class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    @error('reference_link') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-gradient-to-br from-slate-50 to-white dark:from-gray-800/60 dark:to-gray-900/80 p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Asset Upload</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Drop in a product image or 3D file.</p>
                                    </div>
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                        </svg>
                                    </div>
                                </div>

                                <div id="dropzone"
                                     class="group relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700/80 hover:border-blue-500 dark:hover:border-blue-500 bg-white/80 dark:bg-gray-900/60 hover:bg-blue-50/40 dark:hover:bg-blue-950/20 p-6 cursor-pointer transition-all min-h-[220px]">
                                    <label for="product_file" class="absolute inset-0 cursor-pointer">
                                        <span class="sr-only">Upload asset file</span>
                                    </label>

                                    <div id="dropzone_empty" class="flex flex-col items-center text-center pointer-events-none">
                                        <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700 mb-3 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">Click to upload</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">or drag and drop</span>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">PNG, JPG, OBJ, GLTF, or GLB up to 20MB</p>
                                    </div>

                                    <div id="dropzone_filled" class="hidden w-full items-center gap-3 pointer-events-none">
                                        <div id="file_thumb" class="h-14 w-14 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden flex items-center justify-center shrink-0 text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        </div>
                                        <div class="min-w-0 text-left">
                                            <p id="file_name" class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate"></p>
                                            <p id="file_size" class="text-[11px] text-gray-400 dark:text-gray-500"></p>
                                        </div>
                                        <button type="button" id="file_remove" aria-label="Remove file" class="pointer-events-auto ml-auto shrink-0 h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>

                                    <input id="product_file" type="file" name="product_file" accept="image/png,image/jpeg,.obj,.gltf,.glb" class="sr-only">
                                </div>
                                @error('product_file') <p class="text-rose-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Form Footer Actions --}}
            <div class="sticky bottom-4 mt-8 z-10">
                <div class="flex items-center justify-end gap-3 rounded-2xl border border-gray-200/80 dark:border-gray-800 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md shadow-lg shadow-gray-900/5 px-5 py-4">
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-gray-500 mr-auto">
                        <span class="text-rose-500">*</span> Required fields
                    </p>
                    <a href="{{ route('mi_app.index') }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                        Cancel
                    </a>
                    <button type="submit" id="submit_btn"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg id="submit_icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <svg id="submit_spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="submit_label">Save Product</span>
                    </button>
                </div>
            </div>
        </form>

    </div>

    <style>
        @media (prefers-reduced-motion: no-preference) {
            .reveal {
                animation: reveal-in 0.5s ease-out both;
            }
            .reveal:nth-of-type(1) { animation-delay: 0ms; }
            .reveal:nth-of-type(2) { animation-delay: 70ms; }
            .reveal:nth-of-type(3) { animation-delay: 140ms; }
            @keyframes reveal-in {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
        .field {
            box-shadow: inset 0 1px 2px rgba(255,255,255,0.6);
        }
        .field:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }
        .field.field-invalid {
            border-color: rgb(244 63 94) !important;
        }
    </style>

    <script>
        (function () {
            // ---- Required-field progress indicator ----
            var requiredFields = Array.prototype.slice.call(document.querySelectorAll('[data-required]'));
            var progressBar = document.getElementById('progress_bar');
            var progressLabel = document.getElementById('progress_label');

            function updateProgress() {
                var filled = requiredFields.filter(function (el) { return el.value && el.value.trim() !== ''; }).length;
                var total = requiredFields.length;
                var pct = total ? Math.round((filled / total) * 100) : 0;
                progressBar.style.width = pct + '%';
                progressLabel.textContent = filled + ' / ' + total + ' required fields';
            }
            requiredFields.forEach(function (el) {
                el.addEventListener('input', updateProgress);
                el.addEventListener('change', updateProgress);
            });
            updateProgress();

            // ---- Color swatch preview ----
            var colorInput = document.getElementById('color');
            var colorSwatch = document.getElementById('color_swatch');
            var namedColors = { black: '#111111', white: '#ffffff', 'matte black': '#1c1c1c', oak: '#c9a066', walnut: '#5c4433', gray: '#9ca3af', grey: '#9ca3af', blue: '#3b82f6', red: '#ef4444', green: '#22c55e', beige: '#e8dfc8' };
            function updateSwatch() {
                var val = (colorInput.value || '').trim().toLowerCase();
                if (!val) { colorSwatch.style.backgroundColor = ''; colorSwatch.classList.add('bg-gray-200', 'dark:bg-gray-700'); return; }
                var css = namedColors[val] || val;
                var probe = new Option().style;
                probe.color = '';
                probe.color = css;
                if (probe.color !== '') {
                    colorSwatch.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                    colorSwatch.style.backgroundColor = css;
                } else {
                    colorSwatch.style.backgroundColor = '';
                    colorSwatch.classList.add('bg-gray-200', 'dark:bg-gray-700');
                }
            }
            if (colorInput) { colorInput.addEventListener('input', updateSwatch); updateSwatch(); }

            // ---- File upload: drag & drop, preview, remove ----
            var dropzone = document.getElementById('dropzone');
            var fileInput = document.getElementById('product_file');
            var emptyState = document.getElementById('dropzone_empty');
            var filledState = document.getElementById('dropzone_filled');
            var fileName = document.getElementById('file_name');
            var fileSize = document.getElementById('file_size');
            var fileThumb = document.getElementById('file_thumb');
            var removeBtn = document.getElementById('file_remove');

            function formatBytes(bytes) {
                if (!bytes) return '0 KB';
                var kb = bytes / 1024;
                if (kb < 1024) return kb.toFixed(0) + ' KB';
                return (kb / 1024).toFixed(1) + ' MB';
            }

            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                emptyState.classList.add('hidden');
                filledState.classList.remove('hidden');
                filledState.classList.add('flex');

                fileThumb.innerHTML = '';
                if (file.type && file.type.indexOf('image/') === 0) {
                    var img = document.createElement('img');
                    img.className = 'h-full w-full object-cover';
                    img.alt = 'Selected file preview';
                    var reader = new FileReader();
                    reader.onload = function (e) { img.src = e.target.result; };
                    reader.readAsDataURL(file);
                    fileThumb.appendChild(img);
                } else {
                    fileThumb.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>';
                }
            }

            function clearFile() {
                fileInput.value = '';
                emptyState.classList.remove('hidden');
                filledState.classList.add('hidden');
                filledState.classList.remove('flex');
            }

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) showFile(fileInput.files[0]);
            });

            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearFile();
            });

            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-blue-500', 'bg-blue-50/30', 'dark:bg-blue-950/20');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-blue-500', 'bg-blue-50/30', 'dark:bg-blue-950/20');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                if (dt && dt.files && dt.files[0]) {
                    fileInput.files = dt.files;
                    showFile(dt.files[0]);
                }
            });

            // ---- Inline validation styling + submit loading state ----
            var form = document.getElementById('product_form');
            var submitBtn = document.getElementById('submit_btn');
            var submitIcon = document.getElementById('submit_icon');
            var submitSpinner = document.getElementById('submit_spinner');
            var submitLabel = document.getElementById('submit_label');

            requiredFields.forEach(function (el) {
                el.addEventListener('blur', function () {
                    el.classList.toggle('field-invalid', el.value.trim() === '');
                });
            });

            form.addEventListener('submit', function (e) {
                var firstInvalid = null;
                requiredFields.forEach(function (el) {
                    var invalid = el.value.trim() === '';
                    el.classList.toggle('field-invalid', invalid);
                    if (invalid && !firstInvalid) firstInvalid = el;
                });
                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                    return;
                }
                submitBtn.disabled = true;
                submitIcon.classList.add('hidden');
                submitSpinner.classList.remove('hidden');
                submitLabel.textContent = 'Saving…';
            });
        })();
    </script>
</x-mi_app>