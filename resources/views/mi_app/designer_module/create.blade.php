<x-mi_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <div class="rounded-[32px] border border-slate-200/80 bg-gradient-to-br from-slate-50/90 via-white to-slate-100/80 p-6 shadow-[0_30px_80px_-35px_rgba(15,23,42,0.35)] dark:border-slate-800/70 dark:from-slate-900/80 dark:via-slate-900 dark:to-slate-950/80 sm:p-8">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 rounded-3xl border border-slate-200/70 bg-white/70 p-5 shadow-sm backdrop-blur-sm dark:border-slate-800/70 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200 text-slate-700 shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-slate-700 dark:text-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                    </svg>
                </div>
                <div>
                    <div class="mb-0.5 flex items-center gap-2 text-xs font-medium text-gray-400 dark:text-gray-500">
                        <a href="{{ route('mi_app.index') }}" class="transition-colors hover:text-gray-600 dark:hover:text-gray-300">Product Database</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-500 dark:text-gray-400">New Product</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Create Product</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Fill in the specifications, dimensions, and details for the new product item.</p>
                </div>
            </div>

            <a href="{{ route('mi_app.index') }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:border-gray-700/80 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/60 dark:hover:text-white dark:focus:ring-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back to Database
            </a>
        </div>

        {{-- Required fields progress --}}
        <div class="-mt-2 flex items-center gap-3">
            <div class="flex-1 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-800/80">
                <div id="progress_bar" class="h-2 rounded-full bg-gradient-to-r from-blue-500 via-cyan-500 to-indigo-500 transition-all duration-300 ease-out" style="width: 0%"></div>
            </div>
            <span id="progress_label" class="shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500 tabular-nums">0 / 6 required fields</span>
        </div>

        {{-- Main Form Container --}}
        <form method="POST" action="{{ route('mi_app.store') }}" enctype="multipart/form-data" id="product_form" novalidate>
            @csrf

            <div class="space-y-8">

                {{-- SECTION 1: General Information --}}
                <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
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
                <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
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

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-900/70 p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-4 mb-4">
                                    <div>
                                        <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Product dimensions</h3>
                                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Core measurements for the physical item.</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3">
                                            <path d="M6 24 6 10 14 6 26 10 26 24 18 28 6 24Z" stroke-linejoin="round"/>
                                            <path d="M6 10 18 14 26 10" stroke-linejoin="round"/>
                                            <path d="M18 14 18 28" stroke-linejoin="round"/>
                                        </svg>
                                        H × W × L × D
                                    </div>
                                </div>
                                <div class="grid grid-cols-4 gap-4">
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

                            <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-slate-50/80 dark:bg-gray-800/40 p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-4 mb-4">
                                    <div>
                                        <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Carton dimensions</h3>
                                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Packaging footprint for shipping and storage.</p>
                                    </div>
                                    <div class="hidden sm:flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-medium text-slate-600 shadow-sm dark:bg-gray-900/70 dark:text-slate-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M3 7.5v9l9 4.5 9-4.5v-9M3 7.5l9 4.5 9-4.5" />
                                        </svg>
                                        Box size
                                    </div>
                                </div>
                                <div class="grid grid-cols-4 gap-4">
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
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Packaging & Media --}}
                <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                    <div class="border-b border-gray-100 bg-gray-50/50 p-6 dark:border-gray-800/80 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Media & Images</h2>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Product photos and linked imagery</p>
                        </div>
                    </div>

                    <div class="space-y-6 p-6 sm:p-8">
                        {{-- Image Link Input --}}
                        <div>
                            <label for="image_link" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Product Image Link</label>
                            <p class="mb-3 text-[11px] text-gray-400 dark:text-gray-500">Provide a direct URL to the product image</p>
                            <input type="url" id="image_link" name="image_link" value="{{ old('image_link') }}" placeholder="https://example.com/image.jpg"
                                   class="field w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-all focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                            @error('image_link') <p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- File Upload Dropzone --}}
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Upload Product Image</label>
                            <p class="mb-3 text-[11px] text-gray-400 dark:text-gray-500">Drag and drop your image or click to browse</p>
                            <div id="dropzone" class="relative cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-gray-200 bg-white/50 px-6 py-8 transition-all hover:border-blue-400 hover:bg-blue-50/30 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:border-blue-500 dark:hover:bg-blue-950/20">
                                {{-- Empty State --}}
                                <div id="dropzone_empty" class="flex flex-col items-center justify-center gap-3 py-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-[1.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z" />
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Click to upload or drag and drop</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">PNG, JPG, WebP (max 5MB)</p>
                                    </div>
                                </div>

                                {{-- Filled State --}}
                                <div id="dropzone_filled" class="hidden flex-col gap-4">
                                    <div class="flex items-center gap-3">
                                        <div id="file_thumb" class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800 flex items-center justify-center"></div>
                                        <div class="flex-1 min-w-0">
                                            <p id="file_name" class="text-sm font-semibold text-gray-900 truncate dark:text-white"></p>
                                            <p id="file_size" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"></p>
                                        </div>
                                        <button id="file_remove" type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors dark:hover:bg-red-950/30 dark:hover:text-red-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <input type="file" id="product_file" name="product_file" accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                            </div>
                            @error('product_file') <p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- Form Footer Actions --}}
            <div class="sticky bottom-4 z-10 mt-8">
                <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200/80 bg-white/90 px-5 py-4 shadow-[0_20px_60px_-28px_rgba(15,23,42,0.25)] backdrop-blur-md dark:border-slate-800/80 dark:bg-slate-900/90">
                    <p class="mr-auto hidden text-xs text-gray-400 dark:text-gray-500 sm:block">
                        <span class="text-rose-500">*</span> Required fields
                    </p>
                    <a href="{{ route('mi_app.index') }}"
                       class="rounded-xl px-5 py-2.5 text-sm font-semibold text-gray-600 transition-all hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                        Cancel
                    </a>
                    <button type="submit" id="submit_btn"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:from-blue-500 hover:to-indigo-500 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
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