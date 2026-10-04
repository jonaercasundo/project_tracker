@forelse(($items ?? []) as $index => $item)

                                    @php
                                        $itemName =
                                            $item->item_name
                                            ?? $item->name
                                            ?? $item->item
                                            ?? 'Unnamed Item';

                                        $description =
                                            $item->description
                                            ?? $item->item_description
                                            ?? '—';

                                        $itemType =
                                            $item->item_type
                                            ?? $item->type
                                            ?? '—';

                                        $itemId =
                                            $item->item_id
                                            ?? $item->id
                                            ?? null;
                                    @endphp

                                    <tr
                                        class="item-row hover:bg-slate-50 transition"
                                        data-search="{{ strtolower($itemName . ' ' . $description . ' ' . $itemType) }}"
                                        data-type="{{ strtolower($itemType) }}">

                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4 text-xs text-slate-400 font-semibold item-number">
                                            {{ ($rowOffset ?? 0) + $index + 1 }}
                                        </td>


                                        {{-- ITEM --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $itemName }}
                                            </div>

                                            @if($itemId)
                                                <div class="text-[11px] text-slate-400 mt-1">
                                                    ID: {{ $itemId }}
                                                </div>
                                            @endif

                                        </td>


                                        {{-- DESCRIPTION --}}
                                        <td class="px-5 py-4 text-slate-600 max-w-md">

                                            <div class="truncate"
                                                title="{{ $description }}">
                                                {{ $description }}
                                            </div>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex px-2.5 py-1
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-600
                                                        text-xs font-semibold">
                                                {{ $itemType }}
                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex items-center gap-2">

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-blue-50 text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs font-bold transition">
                                                    View
                                                </a>

                                                <a href="#"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-slate-100 text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs font-bold transition">
                                                    Edit
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="itemsEmptyRow">
                                        <td colspan="5"
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
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>
                                                    </svg>

                                                </div>

                                                <p class="font-bold text-slate-600">
                                                    No items found
                                                </p>

                                                <p class="text-xs text-slate-400 mt-1">
                                                    No items are currently assigned to this project.
                                                </p>

                                            </div>

                                        </td>
                                    </tr>

                                @endforelse
