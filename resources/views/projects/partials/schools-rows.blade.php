@forelse($schools ?? [] as $school)

                                    <tr
                                        class="school-row group hover:bg-blue-50/40 transition-colors duration-150"

                                        data-school-id="{{ $school->school_id }}"
                                        data-school-name="{{ $school->school_name }}"
                                        data-region="{{ $school->region }}"
                                        data-division="{{ $school->division }}"
                                        data-municipality="{{ $school->municipality }}"
                                    >

                                        {{-- SCHOOL ID --}}
                                        <td class="px-4 py-3 align-middle whitespace-nowrap">

                                            <span class="inline-flex items-center
                                                        px-2 py-1 rounded-lg
                                                        bg-slate-100
                                                        border border-slate-200
                                                        text-[11px] font-bold
                                                        text-slate-700">

                                                {{ $school->school_id }}

                                            </span>

                                        </td>


                                        {{-- SCHOOL NAME --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="font-bold text-xs text-slate-900
                                                        group-hover:text-blue-700
                                                        transition-colors">

                                                {{ $school->school_name }}

                                            </div>

                                        </td>


                                        {{-- ADDRESS --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="text-xs text-slate-600 leading-5 max-w-[280px]">

                                                {{ $school->address ?: '—' }}

                                            </div>

                                        </td>


                                        {{-- MUNICIPALITY --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span class="text-xs font-semibold text-slate-700">

                                                {{ $school->municipality ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- DIVISION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span
                                                class="inline-flex items-center
                                                    px-2.5 py-1 rounded-lg
                                                    bg-indigo-50
                                                    border border-indigo-100
                                                    text-[10px] font-bold
                                                    text-indigo-700">

                                                {{ $school->division ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- REGION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <span
                                                class="inline-flex items-center
                                                    px-2.5 py-1 rounded-lg
                                                    bg-blue-50
                                                    border border-blue-100
                                                    text-[10px] font-bold
                                                    text-blue-700">

                                                {{ $school->region ?: '—' }}

                                            </span>

                                        </td>


                                        {{-- CONTACT PERSON --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="flex items-center gap-2">

                                                <div class="w-7 h-7 rounded-lg
                                                            bg-slate-100
                                                            border border-slate-200
                                                            flex items-center justify-center
                                                            flex-shrink-0">

                                                    <svg class="w-3.5 h-3.5 text-slate-500"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0z
                                                            M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />

                                                    </svg>

                                                </div>

                                                <span class="text-xs font-semibold text-slate-700">
                                                    {{ $school->contact_person ?: '—' }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- ACTION --}}
                                        <td class="px-4 py-3 align-middle">

                                            <div class="flex items-center justify-end gap-1.5">

                                                {{-- EDIT --}}
                                                <button
                                                    type="button"
                                                    class="edit-school-btn
                                                        inline-flex items-center justify-center
                                                        w-8 h-8 rounded-lg
                                                        bg-white
                                                        border border-slate-200
                                                        text-slate-500
                                                        hover:bg-blue-50
                                                        hover:border-blue-200
                                                        hover:text-blue-600
                                                        active:scale-95
                                                        transition-all duration-150"
                                                    data-school-id="{{ $school->school_id }}"
                                                    title="Edit School">

                                                    <svg class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M16.862 3.487a2.1 2.1 0 013 3L7.5
                                                            18.85 3 20l1.15-4.5L16.862 3.487z" />

                                                    </svg>

                                                </button>


                                                {{-- DELETE --}}
                                                <button
                                                    type="button"
                                                    class="delete-school-btn
                                                        inline-flex items-center justify-center
                                                        w-8 h-8 rounded-lg
                                                        bg-white
                                                        border border-slate-200
                                                        text-slate-500
                                                        hover:bg-red-50
                                                        hover:border-red-200
                                                        hover:text-red-600
                                                        active:scale-95
                                                        transition-all duration-150"
                                                    data-school-id="{{ $school->school_id }}"
                                                    data-school-name="{{ $school->school_name }}"
                                                    title="Delete School">

                                                    <svg class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M6 7h12M9 7V5a1 1 0 011-1h4
                                                            a1 1 0 011 1v2m2 0v12a1 1 0
                                                            01-1 1H8a1 1 0 01-1-1V7m3
                                                            4v6m4-6v6" />

                                                    </svg>

                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr id="noSchoolsRow">

                                        <td colspan="9" class="px-6 py-14 text-center">

                                            <div class="flex flex-col items-center">

                                                <div class="w-12 h-12 rounded-2xl
                                                            bg-slate-100
                                                            border border-slate-200
                                                            flex items-center justify-center">

                                                    <svg class="w-6 h-6 text-slate-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.5">

                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2
                                                            0 00-2 2v16m14 0H5m14 0h2
                                                            M9 7h1m4 0h1m-6 4h1m4 0h1
                                                            m-6 4h1m4 0h1" />

                                                    </svg>

                                                </div>

                                                <h3 class="mt-3 text-sm font-bold text-slate-800">
                                                    No schools found
                                                </h3>

                                                <p class="mt-1 text-xs text-slate-500">
                                                    Add or upload schools associated with this project.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse
