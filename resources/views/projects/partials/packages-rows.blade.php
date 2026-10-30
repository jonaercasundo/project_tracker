@forelse(($packages ?? []) as $index => $package)

                                    @php

                                        $packageId =
                                            $package->package_id
                                            ?? $package->id
                                            ?? null;

                                        $packageName = 'Package '.($package->package_num ?? $packageId ?? '');

                                        $keystageIsInProject = filled($package->keystage_num ?? null)
                                            && (int) ($package->keystage_project_id ?? 0) === (int) $project->project_id
                                            && ($package->lot_id === null || (int) $package->lot_id === (int) ($package->keystage_lot_id ?? 0));

                                        $directLotIsInProject = filled($package->lot_name ?? null)
                                            && (int) ($package->lot_project_id ?? 0) === (int) $project->project_id;

                                        $packageLot = $directLotIsInProject
                                            ? $package->lot_name
                                            : ($keystageIsInProject ? $package->keystage_lot_name : null);

                                        $packageDescription = filled($package->description ?? null)
                                            ? $package->description
                                            : (filled($package->package_description ?? null)
                                                ? $package->package_description
                                                : ($keystageIsInProject ? $package->keystage_description : null));

                                        $packageCode =
                                            $package->package_code
                                            ?? $package->code
                                            ?? $package->package_num
                                            ?? $package->package_id
                                            ?? '—';

                                        $packageType =
                                            $package->package_type
                                            ?? $package->type
                                            ?? '—';

                                    @endphp


                                    <tr
                                        class="package-row
                                            hover:bg-slate-50
                                            transition"

                                        data-search="{{ strtolower(
                                            $packageName . ' ' .
                                            $packageCode . ' ' .
                                            $packageType
                                        ) }}"

                                        data-type="{{ strtolower($packageType) }}">


                                        {{-- NUMBER --}}
                                        <td class="px-5 py-4
                                                text-xs text-slate-400
                                                font-semibold package-number">

                                            {{ ($rowOffset ?? 0) + $index + 1 }}

                                        </td>


                                        {{-- PACKAGE --}}
                                        <td class="px-5 py-4">

                                            <div class="font-bold text-slate-900">
                                                {{ $packageName }}
                                            </div>

                                            @if($packageId)
                                                <div class="text-[11px] text-slate-400 mt-1">ID: {{ $packageId }}</div>
                                            @endif

                                        </td>

                                        <td class="px-5 py-4">{{ $packageLot ?: 'No Lot assigned' }}</td>
                                        <td class="px-5 py-4">{{ $keystageIsInProject ? 'Key Stage '.$package->keystage_num : 'No Key Stage assigned' }}</td>
                                        <td class="max-w-sm px-5 py-4 text-slate-600">{{ filled($packageDescription) ? $packageDescription : '—' }}</td>


                                        {{-- CODE --}}
                                        <td class="px-5 py-4">

                                            <span class="text-xs
                                                        font-semibold
                                                        text-slate-600">

                                                {{ $packageCode }}

                                            </span>

                                        </td>


                                        {{-- TYPE --}}
                                        <td class="px-5 py-4">

                                            <span class="inline-flex
                                                        px-2.5 py-1
                                                        rounded-lg
                                                        bg-cyan-50
                                                        text-cyan-700
                                                        text-xs
                                                        font-semibold">

                                                {{ $packageType }}

                                            </span>

                                        </td>


                                        {{-- ACTIONS --}}
                                        <td class="px-5 py-4 text-right">

                                            <div class="inline-flex
                                                        items-center gap-2">

                                                <a href="{{ route('projects.packages.show', [$project, $packageId]) }}"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-blue-50
                                                        text-blue-700
                                                        hover:bg-blue-100
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    View

                                                </a>

                                                <a href="{{ route('projects.packages.edit', [$project, $packageId]) }}"
                                                class="px-3 py-1.5
                                                        rounded-lg
                                                        bg-slate-100
                                                        text-slate-700
                                                        hover:bg-slate-200
                                                        text-xs
                                                        font-bold
                                                        transition">

                                                    Edit

                                                </a>

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="8"
                                            class="px-6 py-12 text-center">

                                            <div class="flex flex-col
                                                        items-center">

                                                <div class="w-12 h-12
                                                            rounded-full
                                                            bg-slate-100
                                                            flex items-center
                                                            justify-center
                                                            mb-3">

                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-6 h-6
                                                                text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M20 7H4m16 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7m16 0-2-3H6L4 7"/>

                                                    </svg>

                                                </div>


                                                <p class="font-bold
                                                        text-slate-600">

                                                    No packages found

                                                </p>


                                                <p class="text-xs
                                                        text-slate-400 mt-1">

                                                    No packages are currently
                                                    associated with this project.

                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse
