<x-mi_app>
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('it.asset.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1   gap-x-6 gap-y-3 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </form>
    </div>

    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('it.asset.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-x-6 gap-y-3 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-category</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </form>
    </div>

    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('it.asset.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-x-6 gap-y-3 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-category</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                     @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </form>
    </div>

        <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form method="POST" action="{{ route('it.asset.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-x-6 gap-y-3 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-sub-category</label>
                    <select name="category" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sub-category</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror

                </div>
            </div>
        </form>
    </div>
        
        <div class="flex items-center justify-end pt-5 border-t border-slate-100 gap-3">
            <a href="{{ route('mi_app.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
            Cancel
            </a>
        <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 hover:shadow transition-all">
            Save
        </button>
    </div>
</x-mi_app>
