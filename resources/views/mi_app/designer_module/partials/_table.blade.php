<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 text-small font-medium text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3">Item No.</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Sub-Category</th>
                    <th class="px-5 py-3">Collection</th>
                    <th class="px-5 py-3">Item Name</th>    
                    <th class="px-5 py-3 text-center w-[1%] whitespace-nowrap">Actions</th>                
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-slate-50 transition-colors">
                        
                        {{-- Data Columns --}}
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $asset->asset_code }}</td>
                        <td class="px-5 py-4">{{ $asset->asset_name }}</td>
                        <td class="px-5 py-4">{{ $asset->category }}</td>
                        
                        {{-- Status Column --}} 
                        <td class="px-5 py-4">
                            @if($asset->status == 'Available')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-small font-medium bg-green-100 text-green-800">
                                    Available
                                </span>
                            @elseif($asset->status == 'Assigned')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-small font-medium bg-blue-100 text-blue-800">
                                    Assigned
                                </span>
                            @elseif($asset->status == 'Repair')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-small font-medium bg-yellow-100 text-yellow-800">
                                    Repair
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-small font-medium bg-red-100 text-slate-700">
                                    Disposed
                                </span>
                            @endif
                        </td>
                        
                        {{-- Department Column (Simplified) --}}
                        <td class="px-5 py-4 text-slate-900">
                            {{ str_replace('_', ' ', $asset->department) ?: 'Unassigned' }}
                        </td>
                        
                        <td class="px-5 py-4 text-sm w-[1%] whitespace-nowrap">
                            <div class="flex justify-center items-center gap-4">
                                
                                <a href="{{ route('it.asset.show', $asset->id) }}" class="text-indigo-600 hover:text-indigo-400 font-bold transition-colors">
                                    View
                                </a>
                                
                                <button onclick="downloadSvgAsPng('{{ asset('storage/qrcodes/' . rawurlencode($asset->asset_code) . '.svg') }}', '{{ $asset->asset_code }}')" class="text-slate-600 hover:text-slate-400 font-bold transition-colors cursor-pointer">
                                    Generate QR
                                </button>

                                <a href="{{ route('it.asset.edit', $asset->id) }}" class="text-green-600 hover:text-blue-900 font-bold transition-colors">
                                    Edit
                                </a>
                                
                                <form action="{{ route('it.asset.destroy', $asset->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-bold transition-colors">
                                        Delete
                                    </button>
                                </form>
                                
                            </div>
                        </td>
                    </tr>
                        @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center text-sm text-slate-500">No IT Assets found. Click "New Asset" to add one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>