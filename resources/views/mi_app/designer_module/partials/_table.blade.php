<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3">Item No.</th>
                    <th class="px-5 py-3">SKU</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Sub Category</th>
                    <th class="px-5 py-3">Collection</th>
                    <th class="px-5 py-3">Item Name</th>
                    <th class="px-5 py-3 text-center w-[1%] whitespace-nowrap">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 font-medium text-slate-700">
                            {{ $loop->iteration }}
                        </td>

                        <td class="px-5 py-4 font-mono text-slate-700">
                            {{ $product->sku ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $product->category->name ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $product->subCategory->name ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $product->collection->name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 font-medium text-slate-900">
                            {{ $product->item_name }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2">

                                <a href="{{ route('mi_app.show', ['product' => $product->product_id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                                    View
                                </a>

                                <a href="{{ route('mi_app.edit', $product->product_id) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition">
                                    Edit
                                </a>

                                <form action="{{ route('mi_app.destroy', $product->product_id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">
                                        Archive
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>