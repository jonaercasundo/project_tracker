<div id="setting" class="tab-content hidden space-y-6">

                {{-- HEADER --}}
                <div>
                    <h2 class="text-lg font-black text-slate-900">
                        Project Settings
                    </h2>

                    <p class="text-xs text-slate-400">
                        Configure AR and label settings for this project.
                    </p>
                </div>


                {{-- ========================================================= --}}
                {{-- SETTINGS FORM --}}
                {{-- ========================================================= --}}

                <form method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6">

                    @csrf
                    @method('PUT')

                    <input type="hidden"
                        name="project_id"
                        value="{{ $project->project_id }}">


                    {{-- ===================================================== --}}
                    {{-- AR SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                AR Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-5">


                            {{-- PROJECT NAME --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Project Name

                                </label>

                                <input
                                    type="text"
                                    name="project_name"
                                    value="{{ old(
                                        'project_name',
                                        $arSettings->project_name
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('project_name')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Company

                                </label>

                                <input
                                    type="text"
                                    name="company"
                                    value="{{ old(
                                        'company',
                                        $arSettings->company
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('company')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CLIENT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Client

                                </label>

                                <input
                                    type="text"
                                    name="client"
                                    value="{{ old(
                                        'client',
                                        $arSettings->client
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                @error('client')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- DISPLAY LABEL --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_label"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_label"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_label',
                                            $arSettings->display_label
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Label

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Enable label information on AR output.
                                    </p>

                                </div>

                            </div>


                            {{-- DISPLAY SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        bg-slate-50">

                                <input type="hidden"
                                    name="display_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="display_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'display_school_id',
                                            $arSettings->display_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Include the school ID in the AR.
                                    </p>

                                </div>

                            </div>


                            {{-- FOOTER COMPANY --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Company Name

                                </label>

                                <input
                                    type="text"
                                    name="ar_company_footer"
                                    value="{{ old(
                                        'ar_company_footer',
                                        $arSettings->ar_company_footer
                                    ) }}"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    This will appear under the signature
                                    in the AR PDF.
                                </p>

                                @error('ar_company_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER ADDRESS --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Address

                                </label>

                                <textarea
                                    name="ar_address_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_address_footer',
                                                $arSettings->ar_address_footer
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Full address shown at the bottom of the AR.
                                </p>

                                @error('ar_address_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- FOOTER CONTACT --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Footer Contact

                                </label>

                                <textarea
                                    name="ar_contact_footer"
                                    rows="4"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-3
                                        text-sm
                                        resize-y
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">{{ old(
                                                'ar_contact_footer',
                                                $arSettings->ar_contact_footer ?? ''
                                            ) }}</textarea>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Contact details shown at the bottom of the AR.
                                </p>

                                @error('ar_contact_footer')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- SELECT LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Select Logo

                                </label>

                                <select
                                    name="ar_logo"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        focus:ring-2
                                        focus:ring-blue-500
                                        focus:border-blue-500">

                                    @forelse(($logoFiles ?? []) as $logo)

                                        <option
                                            value="{{ $logo }}"
                                            @selected(
                                                ($arSettings->ar_logo ?? 'logo.webp')
                                                === $logo
                                            )>

                                            {{ $logo }}

                                        </option>

                                    @empty

                                        <option value="logo.webp">
                                            logo.webp
                                        </option>

                                    @endforelse

                                </select>

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Choose from existing logos.
                                </p>

                                @error('ar_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- UPLOAD NEW LOGO --}}
                            <div>

                                <label class="block text-xs
                                            font-bold uppercase
                                            text-slate-500 mb-1">

                                    Or Upload New Logo

                                </label>

                                <input
                                    type="file"
                                    name="new_logo"
                                    accept=".png,.jpg,.jpeg,.webp"
                                    class="w-full border border-slate-200
                                        rounded-xl px-4 py-2.5
                                        text-sm bg-white
                                        file:mr-4
                                        file:py-1.5
                                        file:px-3
                                        file:rounded-lg
                                        file:border-0
                                        file:text-xs
                                        file:font-bold
                                        file:bg-blue-50
                                        file:text-blue-700">

                                <p class="text-[11px] text-slate-400 mt-1">
                                    Uploading a file will override the selected logo.
                                </p>

                                @error('new_logo')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CURRENT LOGO --}}
                            @if (!empty($arSettings->ar_logo))

                                <div class="pt-4 border-t border-slate-200">

                                    <label class="block text-xs
                                                font-bold uppercase
                                                text-slate-500 mb-3">

                                        Current Logo Preview

                                    </label>

                                    <div class="inline-flex
                                                items-center justify-center
                                                p-4
                                                bg-slate-50
                                                border border-slate-200
                                                rounded-xl">

                                        <img
                                            src="{{ asset(
                                                'assets/uploads/logo/' .
                                                $arSettings->ar_logo
                                            ) }}"
                                            alt="Current Logo"
                                            class="max-h-24 max-w-xs
                                                object-contain">

                                    </div>

                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- LABEL SETTINGS --}}
                    {{-- ===================================================== --}}

                    <div class="bg-white border border-slate-200
                                rounded-2xl shadow-sm overflow-hidden">

                        {{-- HEADER --}}
                        <div class="px-6 py-4
                                    bg-blue-600
                                    border-b border-blue-700">

                            <h3 class="text-sm font-black text-white">
                                Label Settings
                            </h3>

                        </div>


                        <div class="p-6 space-y-3">


                            {{-- SCHOOL ID --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_school_id"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_school_id"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_school_id',
                                            $arSettings->label_school_id
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display School ID

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the school ID on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- MUNICIPALITY --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_municipality"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_municipality"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_municipality',
                                            $arSettings->label_municipality
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Municipality

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the municipality on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- DIVISION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_division"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_division"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_division',
                                            $arSettings->label_division
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Division

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the division on generated labels.
                                    </p>

                                </div>

                            </div>


                            {{-- REGION --}}
                            <div class="flex items-center gap-3
                                        p-4 rounded-xl
                                        border border-slate-200
                                        hover:bg-slate-50
                                        transition">

                                <input type="hidden"
                                    name="label_region"
                                    value="0">

                                <input
                                    type="checkbox"
                                    name="label_region"
                                    value="1"
                                    @checked(
                                        old(
                                            'label_region',
                                            $arSettings->label_region
                                        ) == 1
                                    )
                                    class="w-4 h-4 rounded
                                        border-slate-300
                                        text-blue-600
                                        focus:ring-blue-500">

                                <div>

                                    <label class="text-sm
                                                font-bold
                                                text-slate-700">

                                        Display Region

                                    </label>

                                    <p class="text-xs text-slate-400">
                                        Show the region on generated labels.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- SAVE --}}
                    {{-- ===================================================== --}}

                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2
                                px-6 py-3
                                text-sm font-bold
                                rounded-xl
                                bg-emerald-600
                                text-white
                                hover:bg-emerald-700
                                shadow-sm
                                transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"/>

                            </svg>

                            Save Settings

                        </button>

                    </div>

                </form>

            </div>
