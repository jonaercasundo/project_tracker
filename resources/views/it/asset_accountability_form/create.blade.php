<x-it_app>

    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add IT Asset</h1>
            <p class="text-gray-500 mt-1">Enter the hardware details and other information</p>
        </div>

        <a href="{{ route('it.asset.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back
        </a>
    </div>

    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">

        <form method="POST" action="{{ route('it.asset.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 mb-8">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Asset Name <span class="text-red-500">*</span></label>
                    <input type="text" name="asset_name" value="{{ old('asset_name') }}" placeholder="e.g., Dell XPS 15 Developer Edition"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('asset_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Asset Code <span class="text-red-500">*</span></label>
                    <input type="text" name="asset_code" value="{{ old('asset_code') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('asset_code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g., Laptop, Monitor"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('category') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('brand') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ old('model') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('model') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Serial Number</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('serial_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('warranty_expiry') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Specifications</label>
                    <input type="text" name="specification" value="{{ old('specification') }}" placeholder="e.g., 16GB RAM, 512GB SSD, i7 Processor"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('specification') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Assigned" {{ old('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="Repair" {{ old('status') == 'Repair' ? 'selected' : '' }}>Repair</option>
                        <option value="Disposed" {{ old('status') == 'Disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>
                    @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Department <span class="text-red-500">*</span></label>
                    <select name="department" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="Information_Technology" {{ old('department', 'Information_Technology') == 'Information_Technology' ? 'selected' : '' }}>Information Technology</option>
                        <option value="Human_Resource" {{ old('department') == 'Human_Resource' ? 'selected' : '' }}>Human Resource</option>
                        <option value="Accounting" {{ old('department') == 'Accounting' ? 'selected' : '' }}>Accounting</option>
                        <option value="Operations" {{ old('department') == 'Operations' ? 'selected' : '' }}>Operations</option>
                        <option value="Administrative" {{ old('department') == 'Administrative' ? 'selected' : '' }}>Administrative</option>
                    </select>
                    @error('department') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Assigned To</label>
                    <input type="text" name="assigned_to" value="{{ old('assigned_to') }}" placeholder="Employee Name"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('assigned_to') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g., 3rd Floor, Room 302"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('location') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('purchase_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Purchase Cost</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">₱</span>
                        <input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost') }}" 
                               class="w-full border border-slate-300 rounded-lg pl-8 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    @error('purchase_cost') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Remarks</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" 
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('remarks') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end pt-5 border-t border-slate-100">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('it.asset.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 hover:shadow transition-all">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-it_app>