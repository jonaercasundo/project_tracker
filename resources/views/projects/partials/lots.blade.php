<div id="lots" data-project-detail="lots" data-endpoint="{{ route('projects.lots-data', $project) }}" data-options-endpoint="{{ route('projects.detail-options', $project) }}" data-lazy="{{ $structureIsSmall ? 'false' : 'true' }}" class="tab-content hidden space-y-5">

                {{-- HEADER --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Lots
                        </h2>

                        <p class="text-xs text-slate-400 mt-1">
                            Lots assigned to this project.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">

                        {{-- LOT COUNT --}}
                        <span class="px-3 py-1.5 rounded-full
                                    bg-blue-50 text-blue-700
                                    text-xs font-bold">
                            <span data-project-count="lots">{{ $lotCount }}</span> Lots
                        </span>

                        {{-- ADD LOT --}}
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

                            Add New Lot
                        </button>

                    </div>
                </div>


                {{-- LOT TABLE --}}
                <div class="bg-white border border-slate-200
                            rounded-2xl shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Lot Number
                                    </th>

                                    @if(($project->keystage ?? 0) == 1)

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Keystage
                                        </th>

                                    @else

                                        <th class="px-4 py-3 text-left
                                                text-xs font-bold whitespace-nowrap">
                                            Cartons
                                        </th>

                                    @endif

                                    <th class="px-4 py-3 text-left
                                            text-xs font-bold whitespace-nowrap">
                                        Contract No.
                                    </th>

                                    <th class="px-4 py-3 text-center
                                            text-xs font-bold whitespace-nowrap">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="lotsTableBody" class="divide-y divide-slate-100">

                                @if($structureIsSmall)
@include('projects.partials.lots-rows')
@else
<tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Open this tab to load records.</td></tr>
@endif

                            </tbody>

                        </table>

                    </div>

                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row">
                        <p id="lotResultCount" class="text-xs text-slate-400"></p>

                        <div id="lotsPaginationButtons" class="flex items-center gap-1"></div>
                    </div>

                </div>

            </div>
            {{-- ========================================================= --}}
            {{-- LOT MODAL HELPERS --}}
            {{-- ========================================================= --}}

            <script>
                function openEditLotModal(lotId, lotName, projectId, contractNo)
                {
                    /*
                    * These IDs should match your Laravel edit-lot modal.
                    */

                    const lotIdInput =
                        document.getElementById('editlotid');

                    const lotNameInput =
                        document.getElementById('editlotname');

                    const projectIdInput =
                        document.getElementById('editprojectid');

                    const contractNoInput =
                        document.getElementById('editcontractno');


                    if (lotIdInput) {
                        lotIdInput.value = lotId;
                    }

                    if (lotNameInput) {
                        lotNameInput.value = lotName ?? '';
                    }

                    if (projectIdInput) {
                        projectIdInput.value = projectId ?? '';
                    }

                    if (contractNoInput) {
                        contractNoInput.value = contractNo ?? '';
                    }


                    /*
                    * If your modal has a specific ID,
                    * open it here.
                    */

                    const modal =
                        document.getElementById('editLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
                function openDeleteLotModal(lotId)
                {
                    const deleteInput =
                        document.getElementById('delete_lot');

                    if (deleteInput) {
                        deleteInput.value = lotId;
                    }

                    const modal =
                        document.getElementById('deleteLotModal');

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
            </script>

            {{-- ========================================================= --}}
            {{-- KEYSTAGES --}}
            {{-- ========================================================= --}}

            