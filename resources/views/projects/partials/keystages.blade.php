<div id="keystage" data-project-detail="keystage" data-endpoint="{{ route('projects.keystages-data', $project) }}" data-options-endpoint="{{ route('projects.detail-options', $project) }}" data-lazy="{{ $structureIsSmall ? 'false' : 'true' }}" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Keystages
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Keystages assigned to the lots in this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">

                            <span data-project-count="keystage">{{ $keystageCount }}</span>
                            Keystages

                        </span>


                        {{-- ADD / UPLOAD --}}
                        <button
                            type="button"
                            class="inline-flex items-center gap-2
                                px-4 py-2 text-xs font-bold
                                rounded-xl bg-emerald-600
                                text-white hover:bg-emerald-700
                                transition shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"/>

                            </svg>

                            Add Keystage
                        </button>

                    </div>

                </div>


                {{-- SEARCH --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl p-4 shadow-sm">

                    <div class="flex flex-col md:flex-row gap-3">

                        <div class="flex-1">

                            <div class="relative">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-3 top-1/2
                                            -translate-y-1/2
                                            w-4 h-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.35-5.65
                                            a7 7 0 11-14 0
                                            7 7 0 0114 0z"/>

                                </svg>

                                <input
                                    type="text"
                                    id="keystageSearch"
                                    placeholder="Search keystage, description, lot..."
                                    class="w-full pl-9 pr-3 py-2.5
                                        text-sm border border-slate-200
                                        rounded-xl
                                        focus:ring-2 focus:ring-blue-500
                                        focus:border-blue-500
                                        outline-none">

                            </div>

                        </div>


                        {{-- LOT FILTER --}}
                        <div class="w-full md:w-64">

                            <select
                                id="keystageLotFilter"
                                class="w-full px-3 py-2.5
                                    text-sm border border-slate-200
                                    rounded-xl bg-white
                                    focus:ring-2 focus:ring-blue-500
                                    focus:border-blue-500
                                    outline-none">

                                <option value="">
                                    All Lots
                                </option>

                                @if($structureIsSmall)
                                    @foreach($lots as $lot)
                                        <option value="{{ strtolower($lot->lot_name) }}">{{ $lot->lot_name }}</option>
                                    @endforeach
                                @endif
                            </select>

                        </div>

                    </div>

                </div>


                {{-- TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Keystage
                                    </th>

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold">
                                        Description
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="keystageTableBody"
                                class="divide-y divide-slate-100">

                                @if($structureIsSmall)
@include('projects.partials.keystage-rows')
@else
<tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Open this tab to load records.</td></tr>
@endif

                            </tbody>

                        </table>

                    </div>


                    {{-- FOOTER --}}
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row">

                        <p id="keystageResultCount"
                        class="text-xs text-slate-400">

                            Showing
                            <span class="font-bold text-slate-600">
                                {{ collect($keystages ?? [])->count() }}
                            </span>
                            keystages

                        </p>

                        <div id="keystagesPaginationButtons"
                            class="flex items-center gap-1">
                        </div>

                    </div>

                </div>

            </div>

            <div id="editKeystageModal" class="{{ $errors->any() ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="editKeystageTitle">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                    <h2 id="editKeystageTitle" class="text-lg font-bold text-slate-900">Edit Key Stage</h2>
                    @if ($errors->any())
                        <div role="alert" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif
                    <form id="editKeystageForm" method="POST" action="{{ route('projects.keystages.update', [$project, '__KEYSTAGE__']) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="keystage_id" id="editkeystageid" value="{{ old('keystage_id') }}">
                        <label class="block text-sm font-medium text-slate-700">Lot
                            <select id="editkeystagelotid" name="lot_id" required class="mt-1 w-full rounded-lg border-slate-300">
                                @foreach ($keystageLots as $lotOption)
                                    <option value="{{ $lotOption->lot_id }}" @selected((string) old('lot_id') === (string) $lotOption->lot_id)>{{ $lotOption->lot_name }}</option>
                                @endforeach
                            </select>
                            @error('lot_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block text-sm font-medium text-slate-700">Key Stage
                            <input id="editkeystagenum" name="keystage_num" type="number" min="1" step="1" required value="{{ old('keystage_num') }}" class="mt-1 w-full rounded-lg border-slate-300">
                            @error('keystage_num')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block text-sm font-medium text-slate-700">Description
                            <input id="editkeystagedescription" name="description" maxlength="255" value="{{ old('description') }}" class="mt-1 w-full rounded-lg border-slate-300">
                            @error('description')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeKeystageModal('editKeystageModal')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="deleteKeystageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="deleteKeystageTitle">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h2 id="deleteKeystageTitle" class="text-lg font-bold text-slate-900">Delete Key Stage?</h2>
                    <p class="mt-2 text-sm text-slate-600">This permanently deletes the selected Key Stage. Stages with related records cannot be deleted.</p>
                    <form id="deleteKeystageForm" method="POST" action="{{ route('projects.keystages.destroy', [$project, '__KEYSTAGE__']) }}" class="mt-5 flex justify-end gap-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="closeKeystageModal('deleteKeystageModal')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Delete</button>
                    </form>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- KEYSTAGE FILTER SCRIPT --}}
            {{-- ========================================================= --}}

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    if (! @json($structureIsSmall)) { return; }
                    const searchInput =
                        document.getElementById('keystageSearch');

                    const lotFilter =
                        document.getElementById('keystageLotFilter');

                    const rows =
                        document.querySelectorAll('.keystage-row');

                    const resultCount =
                        document.getElementById('keystageResultCount');


                    function filterKeystages()
                    {
                        const search =
                            searchInput?.value
                                .toLowerCase()
                                .trim() || '';

                        const selectedLot =
                            lotFilter?.value
                                .toLowerCase()
                                .trim() || '';

                        let visible = 0;


                        rows.forEach(row => {

                            const rowSearch =
                                row.dataset.search || '';

                            const rowLot =
                                row.dataset.lot || '';


                            const matchesSearch =
                                !search ||
                                rowSearch.includes(search);


                            const matchesLot =
                                !selectedLot ||
                                rowLot === selectedLot;


                            const show =
                                matchesSearch &&
                                matchesLot;


                            row.classList.toggle(
                                'hidden',
                                !show
                            );


                            if (show) {
                                visible++;
                            }

                        });


                        if (resultCount) {

                            resultCount.innerHTML = `
                                Showing
                                <span class="font-bold text-slate-600">
                                    ${visible}
                                </span>
                                keystages
                            `;

                        }

                    }


                    searchInput?.addEventListener(
                        'input',
                        filterKeystages
                    );


                    lotFilter?.addEventListener(
                        'change',
                        filterKeystages
                    );

                });


                /*
                |--------------------------------------------------------------------------
                | Edit Keystage
                |--------------------------------------------------------------------------
                */

                function openEditKeystageModal(
                    keystageId,
                    keystageNumber,
                    description,
                    lotId
                ) {

                    const id =
                        document.getElementById(
                            'editkeystageid'
                        );

                    const number =
                        document.getElementById(
                            'editkeystagenum'
                        );

                    const desc =
                        document.getElementById(
                            'editkeystagedescription'
                        );

                    const lot =
                        document.getElementById(
                            'editkeystagelotid'
                        );


                    const form = document.getElementById('editKeystageForm');
                    if (id) { id.value = keystageId; }
                    if (form) { form.action = form.action.replace('__KEYSTAGE__', encodeURIComponent(keystageId)); }

                    if (number) {
                        number.value = keystageNumber ?? '';
                    }

                    if (desc) {
                        desc.value = description ?? '';
                    }

                    if (lot) {
                        lot.value = lotId ?? '';
                    }


                    const modal =
                        document.getElementById(
                            'editKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }
                /*
                |--------------------------------------------------------------------------
                | Delete Keystage
                |--------------------------------------------------------------------------
                */
                function openDeleteKeystageModal(
                    keystageId
                ) {

                    const form = document.getElementById('deleteKeystageForm');
                    if (form) { form.action = form.action.replace('__KEYSTAGE__', encodeURIComponent(keystageId)); }


                    const modal =
                        document.getElementById(
                            'deleteKeystageModal'
                        );

                    if (modal) {
                        modal.classList.remove('hidden');
                    }

                }

                function closeKeystageModal(modalId) {
                    document.getElementById(modalId)?.classList.add('hidden');
                }

                document.addEventListener('DOMContentLoaded', function () {
                    const invalidKeystageId = document.getElementById('editkeystageid')?.value;
                    const editForm = document.getElementById('editKeystageForm');
                    if (invalidKeystageId && editForm) {
                        editForm.action = editForm.action.replace('__KEYSTAGE__', encodeURIComponent(invalidKeystageId));
                    }
                });

            </script>

            {{-- ========================================================= --}}
            {{-- ITEMS --}}
            {{-- ========================================================= --}}


