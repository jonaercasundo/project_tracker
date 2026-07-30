<x-mi_app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <div class="rounded-[32px] border border-slate-200/80 bg-gradient-to-br from-slate-50/90 via-white to-slate-100/80 p-6 shadow-[0_30px_80px_-35px_rgba(15,23,42,0.35)] dark:border-slate-800/70 dark:from-slate-900/80 dark:via-slate-900 dark:to-slate-950/80 sm:p-8">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 rounded-3xl border border-slate-200/70 bg-white/70 p-5 shadow-sm backdrop-blur-sm dark:border-slate-800/70 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200 text-slate-700 shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-slate-700 dark:text-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                    </svg>
                </div>
                <div>
                    <div class="mb-0.5 flex items-center gap-2 text-xs font-medium text-gray-400 dark:text-gray-500">
                        <a href="{{ route('mi_app.index') }}" class="transition-colors hover:text-gray-600 dark:hover:text-gray-300">Product Database</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-500 dark:text-gray-400">Settings</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Settings</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Fill in the Category, Sub Category, Sub Sub Category, Collection, Type of Sample, And Materials</p>
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

        <div class="space-y-8 mt-8">

            {{-- SECTION 1: Category --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="category">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Category</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a new top-level Category</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="category_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Category Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="category_name" name="category_name" value="{{ old('category_name') }}" placeholder="e.g. Furniture" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('category_name')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-4 hover:opacity-90 transition-all">
                                Add Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 2: Sub Category --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="sub_category">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sub Category</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a Sub Category under a Category</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="subcat_category_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="category_id" id="subcat_category_id" required
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @error('category_id')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sub_category_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Sub Category Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="sub_category_name" name="sub_category_name" value="{{ old('sub_category_name') }}" placeholder="e.g. Chairs" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('sub_category_name')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-4 hover:opacity-90 transition-all">
                                Add Sub Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 3: Sub Sub Category --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="sub_sub_category">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sub Sub Category</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a Sub Sub Category under a Sub Category</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="ssc_category_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="category_id" id="ssc_category_id" required data-cascade-target="ssc_subcategory_id"
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="ssc_subcategory_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Sub Category <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="sub_category_id" id="ssc_subcategory_id" required
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Category First --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->category_id }}" class="hidden">
                                            {{ $subCategory->sub_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="sub_sub_category_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Sub Sub Category Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="sub_sub_category_name" name="sub_sub_category_name" value="{{ old('sub_sub_category_name') }}" placeholder="e.g. Dining Chairs" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-4 hover:opacity-90 transition-all">
                                Add Sub Sub Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 4: Collection --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="collection">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Collection</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a Collection under a Sub Sub Category</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="col_category_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Category <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select name="category_id" id="col_category_id" required data-cascade-target="col_subcategory_id"
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="col_subcategory_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Sub Category <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select name="sub_category_id" id="col_subcategory_id" required data-cascade-target="col_subsubcategory_id"
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Category First --</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->category_id }}" class="hidden">
                                            {{ $subCategory->sub_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="col_subsubcategory_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Sub Sub Category <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select name="sub_sub_category_id" id="col_subsubcategory_id" required
                                    class="field w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 pr-10 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                                    <option value="">-- Select Sub Category First --</option>
                                    @foreach($subSubCategories as $subSubCategory)
                                        <option value="{{ $subSubCategory->id }}" data-parent="{{ $subSubCategory->sub_category_id }}" class="hidden">
                                            {{ $subSubCategory->sub_sub_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <div>
                            <label for="collection_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Collection Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="collection_name" name="collection_name" value="{{ old('collection_name') }}" placeholder="e.g. Spring 2026" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                        </div>
                    </div>

                    <div class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <button type="submit" class="rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-6 hover:opacity-90 transition-all">
                            Add Collection
                        </button>
                    </div>
                </form>
            </div>

            {{-- SECTION 5: Type of Sample --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="type_of_sample">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Type of Sample</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a Sample Type (e.g. Swatch, Cutting, Full Piece)</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="type_of_sample_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Type of Sample <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="type_of_sample_name" name="type_of_sample_name" value="{{ old('type_of_sample_name') }}" placeholder="e.g. Swatch" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('type_of_sample_name')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-4 hover:opacity-90 transition-all">
                                Add Type of Sample
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECTION 6: Materials --}}
            <div class="reveal overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_20px_60px_-28px_rgba(15,23,42,0.28)] dark:border-slate-800/80 dark:bg-gray-900">
                <form method="POST" action="{{ route('mi_app.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="entity_type" value="material">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5M3.75 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Materials</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add a Material (e.g. Cotton, Oak, Steel)</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="material_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                Material Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="material_name" name="material_name" value="{{ old('material_name') }}" placeholder="e.g. Cotton" required
                                class="field w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 py-2.5 px-4 text-gray-900 dark:text-white text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('material_name')
                                <p class="flex items-center gap-1 text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold py-2.5 px-4 hover:opacity-90 transition-all">
                                Add Material
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
        </div>
    </div>

    {{-- Cascading dropdown logic --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-cascade-target]').forEach(function (parentSelect) {
                parentSelect.addEventListener('change', function () {
                    var targetId = parentSelect.getAttribute('data-cascade-target');
                    var target = document.getElementById(targetId);
                    if (!target) return;

                    var selectedParent = parentSelect.value;
                    var firstOption = target.querySelector('option[value=""]');
                    target.value = '';

                    Array.from(target.options).forEach(function (opt) {
                        if (!opt.value) return; // keep placeholder
                        var belongs = opt.getAttribute('data-parent') === selectedParent;
                        opt.classList.toggle('hidden', !belongs);
                        opt.disabled = !belongs;
                    });

                    // If this select also drives a further cascade (e.g. subcategory -> subsubcategory), reset it
                    var nextTargetId = target.getAttribute('data-cascade-target');
                    if (nextTargetId) {
                        var nextTarget = document.getElementById(nextTargetId);
                        if (nextTarget) {
                            nextTarget.value = '';
                            Array.from(nextTarget.options).forEach(function (opt) {
                                if (!opt.value) return;
                                opt.classList.add('hidden');
                                opt.disabled = true;
                            });
                        }
                    }
                });
            });
        });
    </script>
</x-mi_app>