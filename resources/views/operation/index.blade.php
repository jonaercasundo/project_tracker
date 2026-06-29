<x-project_app-layout>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Item Master
            </h1>

            <p class="text-sm text-slate-500">
                Manage all inventory items used in projects.
            </p>
        </div>

        <a href="{{ route('items.create') }}"
           class="inline-flex items-center px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 transition shadow-sm">

            + New Item

        </a>

    </div>


    {{-- Search --}}
    <form method="GET"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

            <div class="lg:col-span-2">

                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Item ID, Item Name..."
                    class="w-full rounded-xl border-slate-300">

            </div>

            <div>

                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Project
                </label>

                <select
                    name="project"
                    class="w-full rounded-xl border-slate-300">

                    <option value="">All Projects</option>

                    @foreach($projects as $project)

                        <option
                            value="{{ $project->project_id }}"
                            @selected(request('project') == $project->project_id)>

                            {{ $project->project_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div>

                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Status
                </label>

                <select
                    name="active"
                    class="w-full rounded-xl border-slate-300">

                    <option value="">All</option>

                    <option value="1" @selected(request('active')=='1')>
                        Active
                    </option>

                    <option value="0" @selected(request('active')=='0')>
                        Inactive
                    </option>

                </select>

            </div>

        </div>

        <div class="mt-5 flex justify-end gap-3">

            <a href="{{ route('items.index') }}"
               class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300">

                Reset

            </a>

            <button
                class="px-5 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">

                Apply Filters

            </button>

        </div>

    </form>


    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="bg-slate-100">

                <tr>

                    <th class="px-5 py-3 text-left">Item ID</th>

                    <th class="px-5 py-3 text-left">
                        Classification
                    </th>

                    <th class="px-5 py-3 text-left">
                        Item Name
                    </th>

                    <th class="px-5 py-3 text-left">
                        Project
                    </th>

                    <th class="px-5 py-3 text-center">
                        Unit
                    </th>

                    <th class="px-5 py-3 text-right">
                        Price
                    </th>

                    <th class="px-5 py-3 text-center">
                        Status
                    </th>

                    <th class="px-5 py-3 text-center">
                        Actions
                    </th>

                </tr>

                </thead>

                <tbody>

                @forelse($items as $item)

                    <tr class="border-t hover:bg-slate-50">

                        <td class="px-5 py-4 font-semibold">
                            {{ $item->item_id }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $item->code_prefix }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $item->item_name }}
                        </td>

                        <td class="px-5 py-4">
                            {{ $item->project_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-center">
                            {{ $item->unit }}
                        </td>

                        <td class="px-5 py-4 text-right">
                            {{ number_format($item->price,2) }}
                        </td>

                        <td class="px-5 py-4 text-center">

                            @if($item->active)

                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                    Active
                                </span>

                            @else

                                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                    Inactive
                                </span>

                            @endif

                        </td>

                        <td class="px-5 py-4">

                            <div class="flex justify-center gap-2">

                                <a href="{{ route('items.edit',$item->id) }}"
                                   class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 text-xs font-semibold hover:bg-amber-200">

                                    Edit

                                </a>

                                <form
                                    action="{{ route('items.destroy',$item->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this item?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">

                                        Delete

                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="8"
                            class="text-center py-12 text-slate-500">

                            No items found.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- Pagination --}}
    <div>

        {{ $items->links() }}

    </div>

</div>

</x-project_app-layout>