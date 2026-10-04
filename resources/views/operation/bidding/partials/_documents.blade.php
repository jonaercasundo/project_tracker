@if(\Illuminate\Support\Facades\Route::has($biddingRoutePrefix.'.documents.index'))
<section data-bidding-documents
    data-index-url="{{ route($biddingRoutePrefix.'.documents.index', $project) }}"
    data-store-url="{{ route($biddingRoutePrefix.'.documents.store', $project) }}"
    data-zip-url="{{ route($biddingRoutePrefix.'.documents.zip', $project) }}"
    data-folder-store-url="{{ route($biddingRoutePrefix.'.folders.store', $project) }}"
    data-folder-update-url="{{ route($biddingRoutePrefix.'.folders.update', [$project, '__FOLDER__']) }}"
    data-folder-delete-url="{{ route($biddingRoutePrefix.'.folders.destroy', [$project, '__FOLDER__']) }}"
    class="[&_[hidden]]:!hidden mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div><h2 class="text-sm font-semibold text-slate-900">Documents</h2><p class="mt-1 text-xs text-slate-500">Organize bidding files, keep earlier versions and download what you need.</p></div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-document-action="new-folder" data-document-mutable hidden class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">New folder</button>
            <button type="button" data-document-action="choose-files" data-document-upload hidden class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Upload files</button>
        </div>
    </header>
    <div class="grid min-w-0 grid-cols-1 md:grid-cols-[210px_minmax(0,1fr)]">
        <aside class="border-b border-slate-100 bg-slate-50 p-4 md:border-b-0 md:border-r"><h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Folders</h3><nav data-document-folders aria-label="Document folders" class="space-y-1"></nav></aside>
        <div class="min-w-0 space-y-4 p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0"><h3 data-document-folder-title class="truncate text-sm font-semibold text-slate-800">All files</h3><p data-document-count class="mt-1 text-xs text-slate-500"></p></div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-document-action="rename-folder" data-document-folder-mutable hidden class="rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Edit folder</button>
                    <button type="button" data-document-action="delete-folder" data-document-folder-mutable hidden class="rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete folder</button>
                    <label for="bidding-document-search" class="sr-only">Search documents</label><input id="bidding-document-search" data-document-search type="search" placeholder="Search files..." class="w-48 max-w-full rounded-lg border-slate-200 py-2 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div data-document-error role="alert" tabindex="-1" hidden class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700"></div>
            <button type="button" data-document-action="retry" hidden class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Retry loading documents</button>
            <p data-document-status role="status" aria-live="polite" class="text-xs text-slate-500">Loading documents...</p>
            <div data-document-dropzone data-document-upload hidden tabindex="0" role="button" aria-label="Choose files or drop files here" class="cursor-pointer rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-5 text-center text-xs text-slate-500 focus:border-blue-500 focus:outline-none"><span class="font-semibold text-blue-700">Choose files</span> or drop files here.<p data-document-limits class="mt-2 text-[11px] text-slate-400"></p></div>
            <input data-document-files type="file" multiple hidden><input data-document-replacement type="file" hidden>
            <div data-document-upload-panel hidden class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div><label for="bidding-upload-folder" class="text-xs font-semibold text-slate-600">Upload folder</label><select id="bidding-upload-folder" data-document-upload-folder class="mt-1 w-full rounded-lg border-slate-200 text-xs"></select></div><div><label for="bidding-upload-description" class="text-xs font-semibold text-slate-600">Description (optional)</label><input id="bidding-upload-description" data-document-upload-description maxlength="5000" class="mt-1 w-full rounded-lg border-slate-200 text-xs"></div></div>
                <ul data-document-upload-list class="space-y-2"></ul>
                <button type="button" data-document-action="start-upload" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Upload selected files</button>
            </div>
            <div data-document-selection hidden class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-blue-50 p-3 text-xs text-blue-800"><span data-document-selected-count></span><button type="button" data-document-action="zip" class="rounded-lg bg-white px-3 py-2 font-semibold text-blue-700 shadow-sm disabled:opacity-50">Download selected as ZIP</button></div>
            <div data-document-grid class="grid grid-cols-1 gap-3 xl:grid-cols-2"></div>
            <div data-document-empty hidden class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">No files found in this view.</div>
            <div data-document-pagination hidden class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-xs text-slate-500"><button type="button" data-document-action="previous" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">Previous</button><span data-document-page></span><button type="button" data-document-action="next" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">Next</button></div>
        </div>
    </div>
    <dialog data-document-dialog class="w-[calc(100%-2rem)] max-w-lg rounded-xl border-0 p-0 shadow-xl backdrop:bg-slate-900/40">
        <form data-document-modal-form class="space-y-4 p-5">
            <div class="flex items-center justify-between gap-3"><h3 data-document-dialog-title class="text-base font-semibold text-slate-900"></h3><button type="button" data-document-action="close-dialog" aria-label="Close dialog" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100">Close</button></div>
            <div data-document-dialog-error role="alert" hidden class="rounded-lg bg-red-50 p-3 text-xs text-red-700"></div>
            <div data-document-name-field><label for="bidding-document-name" class="text-xs font-semibold text-slate-600">Name</label><input id="bidding-document-name" name="name" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></div>
            <div data-document-description-field><label for="bidding-document-description" class="text-xs font-semibold text-slate-600">Description</label><textarea id="bidding-document-description" name="description" rows="3" maxlength="5000" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></textarea></div>
            <div data-document-folder-field><label for="bidding-document-folder" class="text-xs font-semibold text-slate-600">Folder</label><select id="bidding-document-folder" name="folder_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></select></div>
            <div data-document-history hidden class="space-y-3"></div>
            <div data-document-history-pages hidden class="flex items-center justify-between text-xs"><button type="button" data-document-action="history-previous" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">Previous</button><span data-document-history-page></span><button type="button" data-document-action="history-next" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">Next</button></div>
            <div class="flex justify-end gap-2"><button type="button" data-document-action="close-dialog" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Close</button><button type="submit" data-document-dialog-save class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">Save changes</button></div>
        </form>
    </dialog>
</section>
@endif
