<x-mi_app>
    <!-- Header Section -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-600/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Add New Product Module</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Fill in the specifications, dimensions, and details for the new product.</p>
                </div>
            </div>
        </div>

        <a href="{{ route('mi_app.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-medium py-2.5 px-4 rounded-xl border border-slate-200 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back
        </a>
    </div>

    <!-- Main Form Card -->
    <div class="max-w-7xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm shadow-slate-200/50 overflow-hidden">

        <form method="POST" action="{{ route('mi_app.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="p-6 sm:p-8 space-y-10">

                <!-- SECTION 1: General Information -->
                <div>
                    <div class="flex items-center gap-2.5 pb-4 mb-6 border-b border-slate-100">
                        <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25zM5 6.75A.75.75 0 015.75 6h8.5a.75.75 0 010 1.5h-8.5A.75.75 0 015 6.75zM5.75 9.5a.75.75 0 000 1.5h8.5a.75.75 0 000-1.5h-8.5zM5 13.25a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold leading-7 text-slate-900">General Information</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Item Name <span class="text-red-500">*</span></label>
                            <input type="text" name="item_name" value="{{ old('item_name') }}" placeholder="Enter full product name"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('item_name') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Category <span class="text-red-500">*</span></label>
                            <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g., Furniture"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('category') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Collection</label>
                            <input type="text" name="collection" value="{{ old('collection') }}" placeholder="e.g., Summer 2026"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('collection') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Type of Sample <span class="text-red-500">*</span></label>
                            <input type="text" name="type_of_sample" value="{{ old('type_of_sample') }}" placeholder="e.g., Prototype"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('type_of_sample') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Classification <span class="text-red-500">*</span></label>
                            <select name="classification" class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 bg-white transition-all hover:ring-slate-300">
                                <option value="Available" {{ old('classification', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                                <option value="Assigned" {{ old('classification') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="Repair" {{ old('classification') == 'Repair' ? 'selected' : '' }}>Repair</option>
                                <option value="Disposed" {{ old('classification') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                            </select>
                            @error('classification') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Designed By</label>
                            <input type="text" name="designed_by" value="{{ old('designed_by') }}" placeholder="Designer Name"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('designed_by') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Attributes & Dimensions -->
                <div>
                    <div class="flex items-center gap-2.5 pb-4 mb-6 border-b border-slate-100">
                        <div class="h-8 w-8 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-600" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold leading-7 text-slate-900">Attributes & Dimensions</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Materials <span class="text-red-500">*</span></label>
                            <input type="text" name="materials" value="{{ old('materials') }}" placeholder="e.g., Wood, Metal"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('materials') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Type</label>
                            <input type="text" name="type" value="{{ old('type') }}" placeholder="Product Type"
                                   class="block w-full rounded-xl border-0 py-2.5 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            @error('type') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Color</label>
                            <div class="relative">
                                <input type="text" name="color" value="{{ old('color') }}" placeholder="e.g., Matte Black"
                                       class="block w-full rounded-xl border-0 py-2.5 pl-4 pr-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 rounded-full border border-slate-200 bg-slate-800"></span>
                            </div>
                            @error('color') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div class="hidden lg:block"></div>

                        <!-- Dimensions sub-group -->
                        <div class="lg:col-span-4 mt-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Product Dimensions</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 rounded-xl p-4 border border-slate-100">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Height <span class="text-red-500">*</span></label>
                                    <input type="text" name="product_height" value="{{ old('product_height') }}" placeholder="45cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('product_height') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Width</label>
                                    <input type="text" name="product_width" value="{{ old('product_width') }}" placeholder="60cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('product_width') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Length</label>
                                    <input type="text" name="product_length" value="{{ old('product_length') }}" placeholder="120cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('product_length') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Depth</label>
                                    <input type="text" name="product_depth" value="{{ old('product_depth') }}" placeholder="30cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('product_depth') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Packaging Dimensions & Others -->
                <div>
                    <div class="flex items-center gap-2.5 pb-4 mb-6 border-b border-slate-100">
                        <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.99 1.5a1.5 1.5 0 00-1.373.9L6.94 6.06 2.484 6.7a1.5 1.5 0 00-.832 2.558l3.226 3.146-.762 4.443a1.5 1.5 0 002.176 1.58L10 16.347" />
                                <path fill-rule="evenodd" d="M2 5.75A2.75 2.75 0 014.75 3h10.5A2.75 2.75 0 0118 5.75v8.5A2.75 2.75 0 0115.25 17H4.75A2.75 2.75 0 012 14.25v-8.5zM5 8.5A.75.75 0 015.75 7.75h8.5a.75.75 0 010 1.5h-8.5A.75.75 0 015 8.5zm.75 2.25a.75.75 0 000 1.5h5.5a.75.75 0 000-1.5h-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold leading-7 text-slate-900">Packaging Dimensions & Others</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Carton Dimensions</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 rounded-xl p-4 border border-slate-100">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Height</label>
                                    <input type="text" name="carton_height" value="{{ old('carton_height') }}" placeholder="50cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('carton_height') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Width</label>
                                    <input type="text" name="carton_width" value="{{ old('carton_width') }}" placeholder="65cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('carton_width') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Length</label>
                                    <input type="text" name="carton_length" value="{{ old('carton_length') }}" placeholder="125cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('carton_length') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Depth</label>
                                    <input type="text" name="carton_depth" value="{{ old('carton_depth') }}" placeholder="35cm"
                                           class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-sm transition-all">
                                    @error('carton_depth') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Purchase Cost</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <span class="text-slate-500 sm:text-sm font-medium">₱</span>
                                </div>
                                <input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost') }}" placeholder="0.00"
                                       class="block w-full rounded-xl border-0 py-2.5 pl-8 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all hover:ring-slate-300">
                            </div>
                            @error('purchase_cost') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-3">
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Upload Image or 3D File</label>
                            <label for="product_file" class="group relative flex items-center justify-center gap-3 w-full rounded-xl border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-blue-50/40 py-5 px-4 cursor-pointer transition-all">
                                <div class="h-9 w-9 rounded-lg bg-white group-hover:bg-blue-100 flex items-center justify-center shadow-sm transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-slate-500 group-hover:text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v6.19l2.72-2.72a.75.75 0 111.06 1.06l-4 4a.75.75 0 01-1.06 0l-4-4a.75.75 0 111.06-1.06l2.72 2.72V3.75A.75.75 0 0110 3zM3.5 12.75a.75.75 0 01.75.75v2.5c0 .414.336.75.75.75h10a.75.75 0 00.75-.75v-2.5a.75.75 0 011.5 0v2.5A2.25 2.25 0 0115 18.5H5a2.25 2.25 0 01-2.25-2.25v-2.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium text-blue-600">Click to upload</span>
                                    <span class="text-slate-500"> or drag and drop</span>
                                    <p class="text-xs text-slate-400 mt-0.5" id="product_file_name">PNG, JPG, or 3D model files</p>
                                </div>
                                <input id="product_file" type="file" name="product_file" class="sr-only"
                                       onchange="document.getElementById('product_file_name').textContent = this.files.length > 0 ? this.files[0].name : 'PNG, JPG, or 3D model files'">
                            </label>
                            @error('product_file') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50/70 px-6 sm:px-8 py-5 border-t border-slate-200 flex items-center justify-end gap-x-3 rounded-b-2xl">
                <a href="{{ route('mi_app.index') }}" class="text-sm font-semibold leading-6 text-slate-600 hover:text-slate-900 px-4 py-2.5 rounded-xl hover:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-slate-300">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    Save
                </button>
            </div>
        </form>
    </div>
</x-mi_app>