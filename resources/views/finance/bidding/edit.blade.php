<x-finance_app-layout>

    <!-- Header Section -->
    <x-slot name="header">
        <div class="space-y-1">
            <h1 class="text-xl font-semibold tracking-tight text-slate-950">Edit Bidding Document</h1>
            <p class="text-xs text-slate-400 font-medium">
                Update project and item details
            </p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('bidding.show', $project->id) }}"
           class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
            Cancel
        </a>
    </x-slot>

    <!-- Form Container -->
    <form method="POST" action="{{ route('bidding.update', $project->id) }}" class="max-w-5xl mx-auto">
        @csrf
        @method('PUT')

        <div class="space-y-12">

            {{-- PROJECT INFO --}}
            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 tracking-wide">Project Information</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Core identifiers and constraints for the contract.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Project ID</label>
                        <input type="text" name="project_id" value="{{ $project->project_id }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Project ID">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Project Name</label>
                        <input type="text" name="project_name" value="{{ $project->project_name }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Project Name">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Procuring Entity</label>
                        <input type="text" name="procuring_entity" value="{{ $project->procuring_entity }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Procuring Entity">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">ABC</label>
                        <input type="number" name="approved_budget_contract_abc" value="{{ $project->approved_budget_contract_abc }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="ABC">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Lot No</label>
                        <input type="text" name="lot_no" value="{{ $project->lot_no }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Lot No">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Delivery Period</label>
                        <input type="text" name="delivery_period" value="{{ $project->delivery_period }}"
                            class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Delivery Period">
                    </div>
                </div>
            </div>

            <hr class="border-slate-100" />

            {{-- LOCATION --}}
            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 tracking-wide">Delivery Location</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Destinations details for full item distribution.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <input type="text" name="country" value="{{ $project->country }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Country">

                    <input type="text" name="region" value="{{ $project->region }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Region">

                    <input type="text" name="province" value="{{ $project->province }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Province">

                    <input type="text" name="city_municipality" value="{{ $project->city_municipality }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="City">

                    <input type="text" name="barangay" value="{{ $project->barangay }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Barangay">

                    <input type="text" name="address" value="{{ $project->address }}"
                        class="bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg px-3 py-2 text-sm text-slate-800 transition-all outline-none" placeholder="Address">
                </div>
            </div>

            <hr class="border-slate-100" />

            {{-- ITEMS --}}
            <div class="space-y-6">
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 tracking-wide">Project Items</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Itemized breakdown of components and values.</p>
                    </div>
                    <button type="button" onclick="addItem()"
                        class="inline-flex items-center text-xs font-semibold text-slate-900 hover:text-slate-600 transition-colors bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 shadow-sm">
                        + Add Item
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    @foreach($project->items as $i => $item)
                        @include('finance.bidding.partials._items', ['i' => $i, 'item' => $item])
                    @endforeach
                </div>

                <div class="flex justify-end pt-2">
                    <div class="flex items-baseline gap-4 px-2">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Grand Total</span>
                        <span id="grand-total" class="text-2xl font-light text-slate-900 tracking-tight">0.00</span>
                    </div>
                </div>
            </div>

            {{-- SUBMIT ACTIONS --}}
            <div class="flex justify-end pt-6 border-t border-slate-100">
                <button class="px-5 py-2.5 bg-slate-950 hover:bg-slate-800 text-white rounded-xl text-sm font-medium shadow-sm transition-all">
                    Update Bidding
                </button>
            </div>

        </div>
    </form>
<script>
let index = {{ count($project->items ?? []) }};

function addItem() {
    const container = document.getElementById('items-container');

    container.insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-12 gap-2 animate-fade-in">
            <div class="col-span-1">
                <input name="items[${index}][item_no]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="No">
            </div>
            <div class="col-span-4">
                <input name="items[${index}][item_description]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="Description">
            </div>
            <div class="col-span-2">
                <input name="items[${index}][unit]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="Unit">
            </div>
            <div class="col-span-1">
                <input name="items[${index}][quantity]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="Qty">
            </div>
            <div class="col-span-2">
                <input name="items[${index}][unit_cost]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="Unit Cost">
            </div>
            <div class="col-span-2">
                <input name="items[${index}][total_amount]" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-400 focus:bg-white rounded-lg p-2 text-xs text-slate-800 outline-none transition-all" placeholder="Total">
            </div>
        </div>
    `);

    index++;
}
</script>
</x-finance_app-layout>