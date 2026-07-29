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
                        <td class="px-5 py-4 font-medium text-slate-900">{{  }}</td>
                        <td class="px-5 py-4">{{}}</td>
                        <td class="px-5 py-4">{{}}</td>

            <tbody class="divide-y divide-slate-100">
            </tbody>
        </table>
    </div>
</div>