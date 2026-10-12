<section data-school-workspace data-revision="{{ $schoolRevision ?? '' }}" data-endpoint="{{ route($biddingRoutePrefix.'.schools.workspace') }}" data-search-url="{{ route($biddingRoutePrefix.'.schools.search') }}" data-catalog-url="{{ route($biddingRoutePrefix.'.catalog') }}" data-project-id="{{ $project->id ?? '' }}" data-readonly="{{ ($schoolReadOnly ?? false) ? '1' : '0' }}" class="space-y-4 rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
    <script type="application/json" data-school-initial>{!! json_encode($schoolPlan ?? ['packages' => []], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @unless($schoolReadOnly ?? false)
        <input type="hidden" name="school_plan" data-school-plan value="{{ old('school_plan', '') }}" disabled>
        <input type="hidden" name="school_token" data-school-token value="{{ old('school_token', '') }}" disabled>
    @endunless
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h3 class="text-sm font-semibold text-slate-900">School assignments &amp; reusable packages</h3><p class="mt-1 text-xs text-slate-500">Configure each package once. Assign existing schools by School ID.</p></div>
        <span data-school-count class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"></span>
    </div>
    <div class="flex flex-wrap gap-2">
        @unless($schoolReadOnly ?? false)
            <button type="button" data-school-action="package" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">+ Configure package</button>
            <button type="button" data-school-action="import" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700">Import masterlist</button>
            <button type="button" data-school-action="bulk" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold">Bulk add School IDs</button>
            <button type="button" data-school-action="single" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold">+ Add school</button>
        @endunless
        <button type="button" data-school-action="list" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold">View assigned schools</button>
    </div>
    <div data-school-packages class="grid gap-3 lg:grid-cols-2"></div>
    <p data-school-message role="status" aria-live="polite" class="text-sm text-red-700"></p>
    @unless($schoolReadOnly ?? false)<p class="text-xs text-slate-500">Confirmed assignments are staged until you save this bidding document. Drafts expire after 8 hours.</p>@endunless
    <dialog data-school-dialog class="max-h-[90vh] w-[95vw] max-w-4xl overflow-y-auto rounded-xl border border-slate-200 p-5 shadow-xl backdrop:bg-slate-900/40">
        <div class="mb-4 flex items-center justify-between gap-3"><h3 data-school-dialog-title class="font-semibold text-slate-900"></h3><button type="button" data-school-action="close" class="rounded-lg border px-3 py-2 text-sm">Close</button></div>
        <div data-school-dialog-content class="space-y-4"></div>
        <p data-school-dialog-error role="alert" class="mt-3 text-sm text-red-700"></p>
    </dialog>
    @if(isset($schoolImportHistory) && $schoolImportHistory->isNotEmpty())
        <details class="border-t border-slate-100 pt-3"><summary class="cursor-pointer text-xs font-semibold text-slate-600">Recent confirmed imports</summary><ul class="mt-3 space-y-2 text-xs text-slate-600">@foreach($schoolImportHistory as $import)<li>{{ $import->filename }} · {{ $import->valid_assignments }} valid assignments · {{ $import->rejected_rows }} rejected · {{ $import->created_at }} · User {{ $import->user_id ?? 'unavailable' }}</li>@endforeach</ul></details>
    @endif
</section>
