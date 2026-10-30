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

                                        $sellingPrice = $item->price ?? null;
                                        $supplierPrice = $item->supplier_price ?? null;
                                        $packageQuantities = $itemPackageQuantities[$itemId] ?? collect();
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

                                            @if($packageQuantities->isNotEmpty())
                                                <div class="mt-1 space-y-0.5 text-[11px] text-slate-500">
                                                    @foreach ($packageQuantities as $packageQuantity)
                                                        @php
                                                            $quantityKeyStage = filled($packageQuantity->keystage_num)
                                                                && (int) $packageQuantity->keystage_project_id === (int) $project->project_id
                                                                && ($packageQuantity->lot_id === null || (int) $packageQuantity->lot_id === (int) $packageQuantity->keystage_lot_id);
                                                        @endphp
                                                        <p>{{ $quantityKeyStage ? 'Key Stage '.$packageQuantity->keystage_num : 'No Key Stage assigned' }} · Package {{ $packageQuantity->package_num ?? $packageQuantity->package_id }}: {{ $packageQuantity->qty ?? 0 }}</p>
                                                    @endforeach
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


                                        {{-- SELLING PRICE --}}
                                        <td class="whitespace-nowrap px-5 py-4 text-right tabular-nums">
                                            @if($sellingPrice !== null)
                                                <span class="font-semibold text-slate-700">&#8369;{{ number_format((float) $sellingPrice, 2) }}</span>
                                            @else
                                                <span class="text-xs text-slate-400">Not set</span>
                                            @endif
                                        </td>


                                        {{-- SUPPLIER PRICE --}}
                                        <td class="whitespace-nowrap px-5 py-4 text-right tabular-nums">
                                            @if($supplierPrice !== null)
                                                <span class="font-semibold text-slate-700">&#8369;{{ number_format((float) $supplierPrice, 2) }}</span>
                                            @else
                                                <span class="text-xs text-slate-400">Not set</span>
                                            @endif
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex items-center gap-2">

                                                @if($itemId !== null)
                                                <a href="{{ route('projects.items.show', [$project, $itemId]) }}"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-blue-50 text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs font-bold transition">
                                                    View
                                                </a>

                                                <a href="{{ route('projects.items.edit', [$project, $itemId]) }}"
                                                class="px-3 py-1.5 rounded-lg
                                                        bg-slate-100 text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs font-bold transition">
                                                    Edit
                                                </a>
                                                <a href="{{ route('projects.items.edit', [$project, $itemId]) }}#package-quantities"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold transition">
                                                    Edit quantity
                                                </a>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="itemsEmptyRow">
                                        <td colspan="7"
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
