@forelse(($packages ?? []) as $index => $package)

                                    @php

                                        $packageName =
                                            $package->package_name
                                            ?? $package->name
                                            ?? $package->package
                                            ?? 'Unnamed Package';

                                        $packageCode =
                                            $package->package_code
                                            ?? $package->code
                                            ?? $package->package_id
                                            ?? '—';

                                        $packageType =
                                            $package->package_type
                                            ?? $package->type
                                            ?? '—';

                                        $packageId =
                                            $package->package_id
                                            ?? $package->id
                                            ?? null;

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

                                                <div class="text-[11px]
                                                            text-slate-400 mt-1">

                                                    ID: {{ $packageId }}

                                                </div>

                                            @endif

                                        </td>


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

                                                <a href="#"
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

                                                <a href="#"
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

                                        <td colspan="5"
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
