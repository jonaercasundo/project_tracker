function initializeBiddingDocuments(panel) {
    if (panel.dataset.documentsInitialized) return;
    panel.dataset.documentsInitialized = 'true';
    const find = selector => panel.querySelector(selector);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    const state = { folders: [], documents: [], permissions: {}, limits: {}, folder: '', search: '', page: 1, meta: {}, selected: new Set(), queue: [], uploading: false };
    const dialog = find('[data-document-dialog]');
    const modalForm = find('[data-document-modal-form]');
    let loadController;
    let searchTimer;
    let modal;
    let historyController;
    let replacementDocument;
    const textNode = (tag, text, classes = '') => {
        const element = document.createElement(tag);
        element.textContent = text;
        element.className = classes;
        return element;
    };
    const status = text => { find('[data-document-status]').textContent = text; };
    function errorText(error) {
        const details = error.errors ? Object.values(error.errors).flat().join(' ') : '';
        return details || error.message || 'The request failed. Please try again.';
    }
    function showError(error, target = find('[data-document-error]')) {
        target.textContent = error ? errorText(error) : '';
        target.hidden = !error;
        if (target === find('[data-document-error]')) find('[data-document-action="retry"]').hidden = !error;
        if (error && target.hasAttribute('tabindex')) target.focus();
    }
    async function request(url, options = {}) {
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', ...options.headers };
        if (options.body && !(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
        const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Your session has expired. Sign in and reload this page.');
        const data = await response.json();
        if (!response.ok) throw Object.assign(new Error(data.message || 'The request could not be completed.'), { errors: data.errors });
        return data;
    }
    function folderUrl(type, id) { return panel.dataset[type].replace('__FOLDER__', String(id)); }
    function folderName(id) { return state.folders.find(folder => String(folder.id) === String(id))?.name || 'Unfiled'; }
    function folderPath(folder) {
        const names = [folder.name];
        const visited = new Set([String(folder.id)]);
        let parent = folder.parent_id;
        while (parent) {
            const found = state.folders.find(entry => String(entry.id) === String(parent));
            if (!found || visited.has(String(found.id))) break;
            visited.add(String(found.id));
            names.unshift(found.name);
            parent = found.parent_id;
        }
        return names.join(' / ');
    }
    function folderOptions(select, value = '', excluded = null) {
        select.replaceChildren(new Option('Unfiled / no parent', ''));
        for (const folder of state.folders) {
            let candidate = folder;
            const visited = new Set();
            let blocked = false;
            while (candidate && !visited.has(String(candidate.id))) {
                if (String(candidate.id) === String(excluded)) { blocked = true; break; }
                visited.add(String(candidate.id));
                candidate = state.folders.find(entry => String(entry.id) === String(candidate.parent_id));
            }
            if (!blocked) select.add(new Option(folderPath(folder), folder.id));
        }
        select.value = value == null || value === 'root' ? '' : String(value);
    }
    function fileSize(bytes) {
        const value = Number(bytes) || 0;
        if (value < 1024) return `${value} B`;
        if (value < 1048576) return `${(value / 1024).toFixed(1)} KB`;
        return `${(value / 1048576).toFixed(1)} MB`;
    }
    function displayDate(value) {
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? 'Date unavailable' : date.toLocaleString();
    }
    function actionButton(label, action, id, color = 'slate') {
        const button = textNode('button', label, `rounded-md px-2 py-1.5 text-xs font-semibold ${color === 'red' ? 'text-red-600 hover:bg-red-50' : 'text-slate-600 hover:bg-slate-100'}`);
        button.type = 'button';
        button.dataset.documentAction = action;
        if (id != null) button.dataset.documentId = id;
        return button;
    }
    function downloadLink(label, url, preview = false) {
        const link = textNode('a', label, 'rounded-md px-2 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50');
        link.href = url;
        if (preview) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
        return link;
    }
    function renderSelection() {
        find('[data-document-selection]').hidden = !state.selected.size;
        find('[data-document-selected-count]').textContent = `${state.selected.size} file${state.selected.size === 1 ? '' : 's'} selected`;
    }
    function renderFolders() {
        const navigation = find('[data-document-folders]');
        navigation.replaceChildren();
        const entries = [{ id: '', name: 'All files' }, { id: 'root', name: 'Unfiled' }, ...state.folders.map(folder => ({ ...folder, name: folderPath(folder) }))];
        for (const folder of entries) {
            const active = String(folder.id) === state.folder;
            const button = textNode('button', folder.name, `block w-full truncate rounded-lg px-3 py-2 text-left text-xs ${active ? 'bg-white font-semibold text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-white'}`);
            button.type = 'button';
            button.dataset.documentAction = 'folder';
            button.dataset.folderId = folder.id;
            button.title = folder.name;
            if (active) button.setAttribute('aria-current', 'page');
            navigation.append(button);
        }
        const current = state.folders.find(folder => String(folder.id) === state.folder);
        find('[data-document-folder-title]').textContent = current ? folderPath(current) : state.folder === 'root' ? 'Unfiled' : 'All files';
        panel.querySelectorAll('[data-document-folder-mutable]').forEach(button => { button.hidden = !(current && state.permissions.update); });
        folderOptions(find('[data-document-upload-folder]'), state.folder);
    }
    function renderDocuments() {
        const grid = find('[data-document-grid]');
        grid.replaceChildren();
        for (const item of state.documents) {
            const card = document.createElement('article');
            card.className = 'min-w-0 space-y-3 rounded-xl border border-slate-200 p-4';
            card.dataset.documentCard = item.id;
            const header = document.createElement('div');
            header.className = 'flex items-start gap-3';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500';
            checkbox.dataset.documentSelect = item.id;
            checkbox.checked = state.selected.has(String(item.id));
            checkbox.setAttribute('aria-label', `Select ${item.display_name}`);
            const titleBox = document.createElement('div');
            titleBox.className = 'min-w-0 flex-1';
            titleBox.append(textNode('h4', item.display_name, 'break-words text-sm font-semibold text-slate-800'));
            titleBox.append(textNode('p', `${String(item.extension || 'FILE').toUpperCase()} | ${fileSize(item.file_size)} | Version ${item.version}`, 'mt-1 text-[11px] text-slate-500'));
            header.append(checkbox, titleBox);
            card.append(header);
            if (item.description) card.append(textNode('p', item.description, 'break-words text-xs text-slate-600'));
            card.append(textNode('p', `Folder: ${folderName(item.folder_id)}`, 'break-words text-[11px] text-slate-500'));
            card.append(textNode('p', `${displayDate(item.uploaded_at || item.created_at)} | ${item.uploaded_by?.name || 'Uploader unavailable'}`, 'break-words text-[11px] text-slate-400'));
            const actions = document.createElement('div');
            actions.className = 'flex flex-wrap items-center gap-1 border-t border-slate-100 pt-2';
            actions.append(downloadLink('Download', item.download_url));
            if (item.preview_url) actions.append(downloadLink('Preview', item.preview_url, true));
            actions.append(actionButton('Versions', 'history', item.id));
            if (state.permissions.update) actions.append(actionButton('Edit / move', 'edit-document', item.id));
            if (state.permissions.upload) actions.append(actionButton('Replace file', 'replace', item.id));
            if (state.permissions.delete) actions.append(actionButton('Delete', 'delete-document', item.id, 'red'));
            card.append(actions);
            grid.append(card);
        }
        find('[data-document-empty]').hidden = state.documents.length !== 0;
        find('[data-document-count]').textContent = `${state.meta.total || 0} file${state.meta.total === 1 ? '' : 's'}`;
        find('[data-document-pagination]').hidden = !(state.meta.last_page > 1);
        find('[data-document-page]').textContent = `Page ${state.meta.current_page || 1} of ${state.meta.last_page || 1}`;
        find('[data-document-action="previous"]').disabled = state.page <= 1;
        find('[data-document-action="next"]').disabled = state.page >= (state.meta.last_page || 1);
        renderSelection();
    }
    async function load() {
        loadController?.abort();
        const controller = new AbortController();
        loadController = controller;
        status('Loading documents...');
        showError(null);
        try {
            const url = new URL(panel.dataset.indexUrl, window.location.href);
            if (state.folder) url.searchParams.set('folder_id', state.folder);
            if (state.search) url.searchParams.set('search', state.search);
            url.searchParams.set('page', state.page);
            const data = await request(url, { signal: controller.signal });
            if (controller.signal.aborted || loadController !== controller) return;
            Object.assign(state, { folders: data.folders, documents: data.documents, permissions: data.permissions, limits: data.limits, meta: data.meta });
            if (state.page > data.meta.last_page && state.page > 1) { state.page = Math.max(1, data.meta.last_page); await load(); return; }
            panel.querySelectorAll('[data-document-upload]').forEach(element => { element.hidden = !state.permissions.upload; });
            panel.querySelectorAll('[data-document-mutable]').forEach(element => { element.hidden = !state.permissions.update; });
            const extensions = state.limits.extensions || [];
            find('[data-document-files]').accept = extensions.map(extension => `.${extension}`).join(',');
            find('[data-document-replacement]').accept = find('[data-document-files]').accept;
            find('[data-document-limits]').textContent = `${extensions.join(', ').toUpperCase()} | Up to ${fileSize((state.limits.max_file_size_kb || 0) * 1024)} per file | ${state.limits.max_files || 1} files per selection`;
            renderFolders();
            renderDocuments();
            status('');
        } catch (error) { if (error.name !== 'AbortError') { status('Documents could not be loaded.'); showError(error); } }
    }
    function renderQueue() {
        find('[data-document-upload-panel]').hidden = !state.queue.length;
        const list = find('[data-document-upload-list]');
        list.replaceChildren();
        for (const entry of state.queue) {
            const row = document.createElement('li');
            row.className = 'space-y-1 rounded-lg border border-slate-200 bg-white p-3 text-xs';
            row.dataset.uploadEntry = entry.id;
            row.append(textNode('p', entry.file.name, 'break-words font-semibold text-slate-700'));
            const progress = document.createElement('progress');
            progress.className = 'h-2 w-full accent-blue-600';
            progress.max = 100;
            progress.value = entry.progress || 0;
            progress.setAttribute('aria-label', `Upload progress for ${entry.file.name}`);
            row.append(progress);
            row.append(textNode('p', entry.error || (entry.status === 'complete' ? 'Uploaded successfully.' : entry.status === 'uploading' ? `Uploading: ${entry.progress || 0}%` : entry.status === 'cancelled' ? 'Upload cancelled.' : 'Ready to upload.'), entry.error ? 'text-red-700' : 'text-slate-500'));
            if ((entry.status === 'failed' || entry.status === 'cancelled') && !entry.invalid) {
                const retry = actionButton('Retry', 'retry-upload', entry.id);
                retry.disabled = state.uploading;
                row.append(retry);
            }
            if (entry.status === 'uploading') row.append(actionButton('Cancel', 'cancel-upload', entry.id));
            list.append(row);
        }
        find('[data-document-action="start-upload"]').disabled = state.uploading || !state.queue.some(entry => entry.status === 'queued');
    }
    function queueFiles(files) {
        if (!state.permissions.upload || state.uploading) return;
        const extensions = (state.limits.extensions || []).map(extension => extension.toLowerCase());
        const maximum = state.limits.max_files || 1;
        state.queue = [...files].map((file, index) => {
            const extension = file.name.split('.').pop().toLowerCase();
            const error = index >= maximum ? `Select no more than ${maximum} files at a time.` : file.size > state.limits.max_file_size_kb * 1024 ? 'This file exceeds the upload size limit.' : !extensions.includes(extension) ? 'This file type is not allowed.' : '';
            return { id: String(index), file, status: error ? 'failed' : 'queued', error, invalid: Boolean(error), progress: 0 };
        });
        folderOptions(find('[data-document-upload-folder]'), state.folder);
        renderQueue();
    }
    function upload(entry) {
        return new Promise(resolve => {
            const xhr = new XMLHttpRequest();
            entry.xhr = xhr;
            entry.status = 'uploading';
            entry.error = '';
            entry.progress = 0;
            const body = new FormData();
            const replacing = Boolean(entry.document);
            body.append(replacing ? 'file' : 'files[]', entry.file);
            if (!replacing) {
                if (entry.folder) body.append('folder_id', entry.folder);
                if (entry.description) body.append('description', entry.description);
            }
            xhr.open('POST', replacing ? entry.document.replace_url : panel.dataset.storeUrl);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = event => { if (event.lengthComputable) entry.progress = Math.round(event.loaded / event.total * 100); renderQueue(); };
            const finish = () => { entry.xhr = null; renderQueue(); resolve(); };
            xhr.onload = () => {
                try {
                    if (xhr.status === 413) throw new Error('The server upload limit is smaller than this file. Choose a smaller file or ask your administrator to adjust the upload limit.');
                    if (xhr.status === 401 || xhr.status === 419) throw new Error('Your session has expired. Sign in and reload this page.');
                    if (xhr.status >= 500 && !xhr.getResponseHeader('Content-Type')?.includes('application/json')) throw new Error('The server could not complete this upload. Retry when the server is available.');
                    if (!xhr.getResponseHeader('Content-Type')?.includes('application/json')) throw new Error('Your session has expired. Sign in and reload this page.');
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status < 200 || xhr.status >= 300) throw Object.assign(new Error(data.message || 'Upload failed.'), { errors: data.errors });
                    entry.status = 'complete';
                    entry.progress = 100;
                } catch (error) { entry.status = 'failed'; entry.error = errorText(error); }
                finish();
            };
            xhr.onerror = () => { entry.status = 'failed'; entry.error = 'Upload failed. Check your connection and retry.'; finish(); };
            xhr.onabort = () => { entry.status = 'cancelled'; finish(); };
            renderQueue();
            xhr.send(body);
        });
    }
    async function uploadQueue(entries) {
        if (state.uploading) return;
        state.uploading = true;
        renderQueue();
        for (const entry of entries) await upload(entry);
        state.uploading = false;
        renderQueue();
        await load();
        status('Upload processing finished. Review each file result above.');
    }
    function openModal(mode, record = null) {
        historyController?.abort();
        modal = { mode, record, page: 1, meta: {} };
        modalForm.reset();
        showError(null, find('[data-document-dialog-error]'));
        find('[data-document-dialog-title]').textContent = mode === 'document' ? 'Edit document' : mode === 'history' ? `Versions: ${record.display_name}` : record ? 'Edit folder' : 'New folder';
        const isHistory = mode === 'history';
        find('[data-document-name-field]').hidden = isHistory;
        modalForm.elements.name.disabled = isHistory;
        find('[data-document-description-field]').hidden = mode !== 'document';
        find('[data-document-folder-field]').hidden = isHistory;
        find('[data-document-history]').hidden = !isHistory;
        find('[data-document-history-pages]').hidden = true;
        find('[data-document-dialog-save]').hidden = isHistory;
        find('[data-document-dialog-save]').disabled = false;
        modalForm.elements.name.maxLength = mode === 'folder' ? 120 : 255;
        modalForm.elements.name.value = mode === 'document' ? record.display_name : record?.name || '';
        modalForm.elements.description.value = record?.description || '';
        folderOptions(modalForm.elements.folder_id, mode === 'document' ? record.folder_id : record?.parent_id ?? state.folder, mode === 'folder' ? record?.id : null);
        if (!dialog.open) dialog.showModal();
        if (isHistory) loadHistory();
    }
    async function loadHistory() {
        historyController?.abort();
        const controller = new AbortController();
        historyController = controller;
        const target = find('[data-document-history]');
        target.replaceChildren(textNode('p', 'Loading versions...', 'text-xs text-slate-500'));
        try {
            const url = new URL(modal.record.history_url, window.location.href);
            url.searchParams.set('page', modal.page);
            const data = await request(url, { signal: controller.signal });
            if (controller.signal.aborted || modal.mode !== 'history') return;
            modal.meta = data.meta;
            target.replaceChildren();
            for (const version of data.versions) {
                const card = document.createElement('div');
                card.className = 'rounded-lg border border-slate-200 p-3';
                card.append(textNode('h4', `Version ${version.version}: ${version.original_name}`, 'break-words text-xs font-semibold text-slate-700'));
                card.append(textNode('p', `${fileSize(version.file_size)} | ${displayDate(version.created_at)} | ${version.uploaded_by?.name || 'Uploader unavailable'}`, 'mt-1 text-[11px] text-slate-500'));
                card.append(downloadLink('Download this version', version.download_url));
                target.append(card);
            }
            if (!data.versions.length) target.append(textNode('p', 'No versions found.', 'text-xs text-slate-500'));
            find('[data-document-history-pages]').hidden = !(data.meta.last_page > 1);
            find('[data-document-history-page]').textContent = `Page ${data.meta.current_page} of ${data.meta.last_page}`;
            find('[data-document-action="history-previous"]').disabled = modal.page <= 1;
            find('[data-document-action="history-next"]').disabled = modal.page >= data.meta.last_page;
        } catch (error) { if (error.name !== 'AbortError') showError(error, find('[data-document-dialog-error]')); }
    }
    modalForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!modal || modal.mode === 'history') return;
        const button = find('[data-document-dialog-save]');
        button.disabled = true;
        showError(null, find('[data-document-dialog-error]'));
        try {
            const folder = modalForm.elements.folder_id.value || null;
            const payload = modal.mode === 'document' ? { display_name: modalForm.elements.name.value, description: modalForm.elements.description.value, folder_id: folder } : { name: modalForm.elements.name.value, parent_id: folder };
            const url = modal.mode === 'document' ? modal.record.update_url : modal.record ? folderUrl('folderUpdateUrl', modal.record.id) : panel.dataset.folderStoreUrl;
            const result = await request(url, { method: modal.record ? 'PUT' : 'POST', body: JSON.stringify(payload) });
            dialog.close();
            await load();
            status(result.message);
        } catch (error) { showError(error, find('[data-document-dialog-error]')); }
        finally { button.disabled = false; }
    });
    async function deleteRecord(url, label) {
        if (!window.confirm(`Delete ${label}? This action removes its stored files and versions.`)) return;
        const result = await request(url, { method: 'DELETE' });
        await load();
        status(result.message);
    }
    async function downloadZip(button) {
        if (!state.selected.size) return;
        button.disabled = true;
        status('Preparing selected files...');
        try {
            const response = await fetch(panel.dataset.zipUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/zip, application/json' }, body: JSON.stringify({ document_ids: [...state.selected] }) });
            if (!response.ok || response.headers.get('content-type')?.includes('application/json')) {
                const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
                throw Object.assign(new Error(data.message || 'The ZIP could not be created. Sign in and retry.'), { errors: data.errors });
            }
            if (!response.headers.get('content-type')?.includes('zip') && !response.headers.get('content-type')?.includes('octet-stream')) throw new Error('The ZIP could not be created. Sign in and retry.');
            const url = URL.createObjectURL(await response.blob());
            const link = document.createElement('a');
            link.href = url;
            link.download = 'bidding-documents.zip';
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            status('ZIP download started.');
        } catch (error) { showError(error); }
        finally { button.disabled = false; }
    }
    panel.addEventListener('click', async event => {
        const button = event.target.closest('[data-document-action]');
        if (!button || !panel.contains(button)) return;
        const action = button.dataset.documentAction;
        const item = state.documents.find(entry => String(entry.id) === button.dataset.documentId);
        try {
            if (state.uploading && ['choose-files', 'replace'].includes(action)) throw new Error('Wait for the current uploads to finish before selecting more files.');
            if (action === 'retry') await load();
            else if (action === 'folder') { state.folder = button.dataset.folderId; state.page = 1; await load(); }
            else if (action === 'new-folder') openModal('folder');
            else if (action === 'rename-folder') openModal('folder', state.folders.find(folder => String(folder.id) === state.folder));
            else if (action === 'delete-folder') {
                const folder = state.folders.find(entry => String(entry.id) === state.folder);
                if (!window.confirm(`Delete the empty folder "${folder.name}"?`)) return;
                const result = await request(folderUrl('folderDeleteUrl', folder.id), { method: 'DELETE' });
                state.folder = ''; state.page = 1;
                await load(); status(result.message);
            }
            else if (action === 'choose-files') find('[data-document-files]').click();
            else if (action === 'start-upload') {
                const folder = find('[data-document-upload-folder]').value;
                const description = find('[data-document-upload-description]').value;
                const queued = state.queue.filter(entry => entry.status === 'queued');
                queued.forEach(entry => { entry.folder = folder; entry.description = description; });
                await uploadQueue(queued);
            }
            else if (action === 'retry-upload') { const entry = state.queue.find(entry => entry.id === button.dataset.documentId); await uploadQueue([entry]); }
            else if (action === 'cancel-upload') state.queue.find(entry => entry.id === button.dataset.documentId)?.xhr?.abort();
            else if (action === 'edit-document') openModal('document', item);
            else if (action === 'history') openModal('history', item);
            else if (action === 'replace') { replacementDocument = item; find('[data-document-replacement]').click(); }
            else if (action === 'delete-document') { await deleteRecord(item.delete_url, `"${item.display_name}"`); state.selected.delete(String(item.id)); renderSelection(); }
            else if (action === 'previous' || action === 'next') { state.page += action === 'next' ? 1 : -1; await load(); }
            else if (action === 'history-previous' || action === 'history-next') { modal.page += action === 'history-next' ? 1 : -1; await loadHistory(); }
            else if (action === 'close-dialog') { historyController?.abort(); dialog.close(); }
            else if (action === 'zip') await downloadZip(button);
        } catch (error) { showError(error); }
    });
    panel.addEventListener('change', event => {
        if (event.target.matches('[data-document-select]')) {
            const id = event.target.dataset.documentSelect;
            if (event.target.checked && state.selected.size >= (state.limits.max_zip_files || 100)) { event.target.checked = false; showError(new Error(`Select no more than ${state.limits.max_zip_files || 100} files for one ZIP.`)); return; }
            if (event.target.checked) state.selected.add(id); else state.selected.delete(id);
            renderSelection();
        }
        if (event.target.matches('[data-document-files]')) { queueFiles(event.target.files); event.target.value = ''; }
        if (event.target.matches('[data-document-replacement]') && event.target.files.length && !state.uploading) {
            queueFiles(event.target.files);
            const entry = state.queue[0];
            if (entry && !entry.invalid) { entry.document = replacementDocument; uploadQueue([entry]); }
            event.target.value = '';
        }
    });
    find('[data-document-search]').addEventListener('input', event => {
        clearTimeout(searchTimer);
        loadController?.abort();
        state.search = event.target.value.trim();
        state.page = 1;
        searchTimer = setTimeout(load, 250);
    });
    const dropzone = find('[data-document-dropzone]');
    dropzone.addEventListener('click', () => find('[data-document-files]').click());
    dropzone.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); find('[data-document-files]').click(); } });
    dropzone.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('border-blue-400'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-blue-400'));
    dropzone.addEventListener('drop', event => { event.preventDefault(); dropzone.classList.remove('border-blue-400'); queueFiles(event.dataTransfer.files); });
    dialog.addEventListener('cancel', () => historyController?.abort());
    load();
}
function initializeDocumentPanels() { document.querySelectorAll('[data-bidding-documents]').forEach(initializeBiddingDocuments); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeDocumentPanels);
else initializeDocumentPanels();
