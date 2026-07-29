<x-mi_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 text-white shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Create Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Fill in the specifications, dimensions, and details for the new product item.</p>
                </div>
            </div>

            <a href="{{ route('mi_app.index') }}" 
               class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-200 text-sm font-semibold py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-700/80 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back to Database
            </a>
        </div>

        {{-- Main Form Container --}}
        <form method="POST" action="{{ route('mi_app.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="space-y-8">

                {{-- SECTION 1: General Information --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-bold text-xs">
                            01
                        </span>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">General Information</h2>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Item Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="item_name" value="{{ old('item_name') }}" placeholder="e.g. Ergonomic Office Desk"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('item_name') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Furniture"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('category') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Collection</label>
                            <input type="text" name="collection" value="{{ old('collection') }}" placeholder="e.g. Summer 2026"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('collection') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Type of Sample <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="type_of_sample" value="{{ old('type_of_sample') }}" placeholder="e.g. Prototype"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('type_of_sample') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Classification <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="classification" class="w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 pl-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
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
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Designed By</label>
                            <input type="text" name="designed_by" value="{{ old('designed_by') }}" placeholder="Designer full name"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('designed_by') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Attributes & Product Dimensions --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                            02
                        </span>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Attributes & Dimensions</h2>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                    Materials <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="materials" value="{{ old('materials') }}" placeholder="e.g. Oak, Aluminum"
                                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                @error('materials') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Type</label>
                                <input type="text" name="type" value="{{ old('type') }}" placeholder="Product variant type"
                                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                @error('type') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Color</label>
                                <input type="text" name="color" value="{{ old('color') }}" placeholder="e.g. Matte Black"
                                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                @error('color') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Product Dimensions Panel --}}
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 p-5 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product Dimensions</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Height <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="product_height" value="{{ old('product_height') }}" placeholder="45"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_height') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Width</label>
                                    <div class="relative">
                                        <input type="text" name="product_width" value="{{ old('product_width') }}" placeholder="60"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_width') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Length</label>
                                    <div class="relative">
                                        <input type="text" name="product_length" value="{{ old('product_length') }}" placeholder="120"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_length') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Depth</label>
                                    <div class="relative">
                                        <input type="text" name="product_depth" value="{{ old('product_depth') }}" placeholder="30"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('product_depth') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Packaging & Attachments --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 font-bold text-xs">
                            03
                        </span>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Packaging & Media</h2>
                    </div>

                    <div class="p-6 sm:p-8 space-y-6">
                        {{-- Carton Dimensions Panel --}}
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 p-5 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Carton Dimensions</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Height</label>
                                    <div class="relative">
                                        <input type="text" name="carton_height" value="{{ old('carton_height') }}" placeholder="50"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_height') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Width</label>
                                    <div class="relative">
                                        <input type="text" name="carton_width" value="{{ old('carton_width') }}" placeholder="65"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_width') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Length</label>
                                    <div class="relative">
                                        <input type="text" name="carton_length" value="{{ old('carton_length') }}" placeholder="125"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_length') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Depth</label>
                                    <div class="relative">
                                        <input type="text" name="carton_depth" value="{{ old('carton_depth') }}" placeholder="35"
                                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-9 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">cm</span>
                                    </div>
                                    @error('carton_depth') <p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Purchase Cost</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <span class="text-gray-500 dark:text-gray-400 text-sm font-semibold">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost') }}" placeholder="0.00"
                                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 pl-8 pr-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                </div>
                                @error('purchase_cost') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Upload Asset File</label>
                                <label for="product_file" class="group relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700/80 hover:border-blue-500 dark:hover:border-blue-500 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-blue-50/30 dark:hover:bg-blue-950/20 p-6 cursor-pointer transition-all">
                                    <div class="h-10 w-10 rounded-xl bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700 mb-2 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">Click to upload</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"> or drag and drop</span>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5" id="product_file_name">Supports PNG, JPG, or 3D files (OBJ, GLTF)</p>
                                    </div>
                                    <input id="product_file" type="file" name="product_file" class="sr-only"
                                           onchange="document.getElementById('product_file_name').textContent = this.files.length > 0 ? this.files[0].name : 'Supports PNG, JPG, or 3D files (OBJ, GLTF)'">
                                </label>
                                @error('product_file') <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Form Footer Actions --}}
            <div class="mt-8 pt-6 flex items-center justify-end gap-3 border-t border-gray-200/80 dark:border-gray-800">
                <a href="{{ route('mi_app.index') }}" 
                   class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Save Product
                </button>
            </div>
        </form>

    </div>
</x-mi_app>