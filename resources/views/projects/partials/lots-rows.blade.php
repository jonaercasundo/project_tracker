@forelse($lots ?? [] as $lot)

                                    <tr class="lot-row hover:bg-slate-50 transition">

                                        {{-- LOT NUMBER --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center justify-center">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-blue-600"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                    </svg>

                                                </div>

                                                <div>

                                                    <p class="font-black text-slate-900">
                                                        {{ $lot->lot_name ?? 'Lot' }}
                                                    </p>

                                                    <p class="text-[11px] text-slate-400">
                                                        Lot ID: {{ $lot->lot_id }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE / CARTONS --}}
                                        <td class="px-4 py-4">

                                            @if(($project->keystage ?? 0) == 1)

                                                {{-- KEYSTAGE PROJECT --}}
                                                @if(!empty($lot->keystages) && $lot->keystages->count())

                                                    <div class="space-y-1">

                                                        @foreach($lot->keystages as $ks)

                                                            <div>
                                                                <span class="font-bold text-slate-700">
                                                                    Keystage {{ $ks->keystage_num }}
                                                                </span>

                                                                @if(!empty($ks->description))
                                                                    <span class="text-slate-500">
                                                                        — {{ $ks->description }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @elseif(($lot->keystages_count ?? 0) > 0)
                                                    <button type="button" class="text-xs font-bold text-blue-600" onclick="openTab(event, 'keystage')">{{ $lot->keystages_count }} keystages ? open Keystages</button>
                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @else

                                                {{-- CARTON / PACKAGE PROJECT --}}
                                                @php
                                                    $cartonCount =
                                                        $lot->packages_count
                                                        ?? $lot->carton_count
                                                        ?? 0;
                                                @endphp

                                                @if($cartonCount > 0)

                                                    <span class="inline-flex items-center
                                                                px-2.5 py-1 rounded-lg
                                                                bg-blue-50 text-blue-700
                                                                text-xs font-bold">

                                                        {{ number_format($cartonCount) }}
                                                        {{ $cartonCount == 1 ? 'Carton' : 'Cartons' }}

                                                    </span>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        None
                                                    </span>

                                                @endif

                                            @endif

                                        </td>


                                        {{-- CONTRACT NUMBER --}}
                                        <td class="px-4 py-4">

                                            @if(!empty($lot->contract_no))

                                                <span class="font-medium text-slate-700">
                                                    {{ $lot->contract_no }}
                                                </span>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    Not assigned
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center justify-center
                                                        gap-2">

                                                {{-- KEYSTAGE / PACKAGES --}}
                                                @if(($project->keystage ?? 0) == 1)

                                                    <button
                                                        type="button"
                                                        onclick="openTab(event, 'keystage')"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>

                                                            <circle cx="12"
                                                                    cy="12"
                                                                    r="3"/>

                                                        </svg>

                                                        Keystage

                                                    </button>

                                                @else

                                                    <button
                                                        type="button"
                                                        onclick="openTab(event, 'packages')"
                                                        class="inline-flex items-center gap-1.5
                                                            px-3 py-2 rounded-lg
                                                            bg-blue-50 text-blue-700
                                                            hover:bg-blue-100
                                                            text-xs font-bold transition">

                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>

                                                        </svg>

                                                        Packages

                                                    </button>

                                                @endif


                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditLotModal(
                                                        {{ $lot->lot_id }},
                                                        @js($lot->lot_name),
                                                        @js($lot->project_id),
                                                        @js($lot->contract_no)
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50 text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688a2.25 2.25 0 113.182 3.182l-1.688 1.687M16.862 4.487L7.5 13.85V17h3.15l9.36-9.332M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteLotModal({{ $lot->lot_id }})"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Lot">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-full
                                                            bg-slate-100
                                                            flex items-center justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No lots found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No lots are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse
