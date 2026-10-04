@forelse($keystages ?? [] as $keystage)

                                    @php

                                        $lotName =
                                            $keystage->lot_name
                                            ?? $keystage->lot_no
                                            ?? $keystage->lot_id
                                            ?? '—';

                                        $keystageNumber =
                                            $keystage->keystage_num
                                            ?? $keystage->keystage_no
                                            ?? $keystage->keystage_id
                                            ?? '—';

                                        $description =
                                            $keystage->description
                                            ?? $keystage->keystage_name
                                            ?? 'No description';

                                    @endphp


                                    <tr
                                        class="keystage-row hover:bg-slate-50 transition"

                                        data-search="
                                            {{ strtolower(
                                                ($lotName ?? '') . ' ' .
                                                ($keystageNumber ?? '') . ' ' .
                                                ($description ?? '')
                                            ) }}
                                        "

                                        data-lot="{{ strtolower($lotName) }}"
                                    >

                                        {{-- LOT --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-9 h-9 rounded-lg
                                                            bg-blue-50
                                                            flex items-center
                                                            justify-center">

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

                                                    <p class="font-bold text-slate-900">
                                                        {{ $lotName }}
                                                    </p>

                                                    @if(!empty($keystage->lot_id))

                                                        <p class="text-[11px] text-slate-400">
                                                            Lot ID: {{ $keystage->lot_id }}
                                                        </p>

                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- KEYSTAGE NUMBER --}}
                                        <td class="px-4 py-4">

                                            <span class="inline-flex items-center
                                                        px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        text-xs font-black">

                                                Keystage {{ $keystageNumber }}

                                            </span>

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-4 py-4">

                                            @if(
                                                !empty($keystage->description) ||
                                                !empty($keystage->keystage_name)
                                            )

                                                <p class="text-slate-700">
                                                    {{ $description }}
                                                </p>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    No description
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-4 py-4">

                                            <div class="flex items-center
                                                        justify-center gap-2">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    onclick="openEditKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }},
                                                        @js($keystageNumber),
                                                        @js($description),
                                                        @js($keystage->lot_id ?? '')
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-amber-50
                                                        text-amber-600
                                                        hover:bg-amber-100
                                                        transition"
                                                    title="Edit Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688
                                                                a2.25 2.25 0 113.182 3.182
                                                                l-1.688 1.687M16.862 4.487
                                                                L7.5 13.85V17h3.15l9.36-9.332
                                                                M16.862 4.487L19.5 7.125"/>

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    onclick="openDeleteKeystageModal(
                                                        {{ $keystage->keystage_id ?? $keystage->id ?? 0 }}
                                                    )"
                                                    class="p-2 rounded-lg
                                                        bg-red-50 text-red-600
                                                        hover:bg-red-100
                                                        transition"
                                                    title="Delete Keystage">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142
                                                                A2 2 0 0116.138 21H7.862
                                                                a2 2 0 01-1.995-1.858L5 7
                                                                m5 4v6m4-6v6M9 7V4
                                                                a1 1 0 011-1h4a1 1 0 011 1v3
                                                                m-9 0h14"/>

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
                                                            flex items-center
                                                            justify-center mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14l9-5-9-5-9 5 9 5z"/>

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M12 14v7m0-7L3 9m9 5l9-5"/>

                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No keystages found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No keystages are currently assigned
                                                    to this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse
