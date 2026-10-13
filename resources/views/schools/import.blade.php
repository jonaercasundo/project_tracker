<x-project-app-layout>
    <div class="min-w-0 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Import Schools</h1>
                <p class="mt-1 text-sm text-slate-500">Upload a file, review each row, then confirm the import.</p>
            </div>
            <a href="{{ route('schools.index', $listQuery) }}" class="shrink-0 text-sm font-semibold text-blue-600 hover:text-blue-800">Back to schools</a>
        </div>

        @include('schools._alerts')

        @if ($importResult ?? null)
            <section class="space-y-4" aria-labelledby="import-result-title">
                <h2 id="import-result-title" class="text-lg font-bold text-slate-900">Import results</h2>
                @include('schools._import-results', ['report' => $importResult, 'isPreview' => false])
            </section>
        @endif

        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="import-upload-title">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="import-upload-title" class="text-sm font-bold text-slate-900">1. Prepare and upload your file</h2>
                    <p class="mt-1 text-sm text-slate-500">CSV or Excel (.xlsx), up to 5 MB and 1,000 school rows per upload.</p>
                </div>
                <a href="{{ route('schools.template') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-xs font-bold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 16v4h14v-4" /></svg>
                    Download CSV template
                </a>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                <ul class="list-disc space-y-1.5 pl-4">
                    <li>Keep all 12 template column headers. Required values: <strong>school_id, school_name, address, division, region</strong>.</li>
                    <li>Leave optional values blank. Enter project IDs as whole numbers and contract prices with up to two decimal places, without thousands separators.</li>
                    <li>Format school IDs and phone numbers as text in Excel to preserve leading zeros.</li>
                    <li>Excel files must contain one worksheet. Each preview expires 15 minutes after upload.</li>
                    <li>Existing school IDs and repeated IDs in the file are skipped. Existing school records are never overwritten.</li>
                </ul>
            </div>
            <form method="POST" action="{{ route('schools.import.preview', $listQuery) }}" enctype="multipart/form-data" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="school-import-file" class="mb-2 block text-xs font-bold text-slate-600">School file <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="school-import-file" name="file" type="file" accept=".csv,.xlsx" required aria-describedby="school-import-file-help school-import-file-error" aria-invalid="{{ $errors->has('file') ? 'true' : 'false' }}" x-on:change="$el.setCustomValidity($el.files[0] && $el.files[0].size > 5242880 ? 'Choose a file no larger than 5 MB.' : ''); $el.reportValidity()" class="block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="school-import-file-help" class="mt-1 text-xs text-slate-500">The upload is checked before any school records are saved.</p>
                    <x-input-error :messages="$errors->get('file')" id="school-import-file-error" class="mt-1" />
                </div>
                <button type="submit" :disabled="submitting" class="shrink-0 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">Preview import</span>
                    <span x-show="submitting" x-cloak>Checking file...</span>
                </button>
            </form>
        </section>

        @if ($preview)
            <section class="space-y-4" aria-labelledby="import-preview-title">
                <div>
                    <h2 id="import-preview-title" class="text-lg font-bold text-slate-900">2. Review and confirm</h2>
                    <p class="mt-1 break-words text-sm text-slate-500">{{ $preview['filename'] }} — review row details and any validation issues below.</p>
                    <p class="mt-1 text-xs text-slate-500">This preview expires 15 minutes after upload. Upload the file again if it expires before confirmation.</p>
                </div>
                @include('schools._import-results', ['report' => $preview, 'isPreview' => true])
                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600">Only rows marked <strong class="text-emerald-700">Ready</strong> will be imported. Correct failed rows in your file and upload them again.</p>
                    <form method="POST" action="{{ route('schools.import.confirm', $listQuery) }}" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); } else if (window.confirm('Import all ready rows? Duplicate and invalid rows will be skipped.')) { submitting = true; } else { $event.preventDefault(); }" :aria-busy="submitting" class="shrink-0">
                        @csrf
                        <input type="hidden" name="token" value="{{ $preview['token'] }}">
                        <button type="submit" @disabled($preview['summary']['ready'] === 0) :disabled="submitting || {{ $preview['summary']['ready'] === 0 ? 'true' : 'false' }}" class="w-full rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                            <span x-show="!submitting">Confirm import ({{ number_format($preview['summary']['ready']) }})</span>
                            <span x-show="submitting" x-cloak>Importing...</span>
                        </button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</x-project-app-layout>
