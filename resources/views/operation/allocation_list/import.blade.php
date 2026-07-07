<x-project_app-layout>
    <div class="min-h-screen bg-[#F6F7FA] py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#C9992A] mb-1.5">School Data Import</p>
                    <h1 class="text-3xl font-bold text-[#14213D] tracking-tight">
                        Import Schools
                    </h1>
                    <p class="text-sm text-slate-500 mt-1.5 max-w-xl">
                        Pull school records straight from a DepEd Allocation List — paste a link or drop the PDF in.
                    </p>
                </div>

                <a href="{{ route('school.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-sm shadow-sm hover:bg-slate-50 hover:text-[#14213D] hover:border-slate-300 transition-all active:scale-[0.98] shrink-0">
                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Schools
                </a>
            </div>
            {{-- Inline notification banner --}}
            <div id="banner" class="hidden rounded-xl px-4 py-3.5 text-sm font-medium flex items-start gap-2.5 border"></div>

            {{-- Import Card --}}
            <div id="sourceCard" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8">
                    <form id="importForm" class="space-y-6" onsubmit="event.preventDefault();">
                        @csrf

                        {{-- File Input Container --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-[#14213D]">
                                Upload PDF file directly
                            </label>
                            <div id="dropZone" class="relative border-2 border-dashed border-slate-200 rounded-2xl p-6 transition-all group bg-slate-50/30">
                                <input
                                    type="file"
                                    id="pdf_file"
                                    name="pdf_file"
                                    accept="application/pdf"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="text-center space-y-2 pointer-events-none">
                                    <div id="dropIcon" class="inline-flex p-3 rounded-xl bg-white shadow-sm border border-slate-100 text-slate-500 transition-all">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        <span class="font-semibold text-[#14213D]">Click to upload</span> or drag and drop
                                    </div>
                                    <p id="file-chosen-text" class="text-xs text-slate-400">PDF documents only, up to 10MB</p>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-2 flex items-center gap-3">
                            <button
                                type="button"
                                id="analyzeBtn"
                                style="background-color: #14213D;"
                                class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#14213D] text-white font-semibold text-sm shadow-md shadow-[#14213D]/15 hover:bg-[#1c2d52] transition-all focus:outline-none focus:ring-2 focus:ring-[#14213D] focus:ring-offset-2 active:scale-[0.98]">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Analyze PDF
                            </button>
                            <button 
                                type="button" 
                                id="resetBtn" 
                                class="hidden inline-flex items-center justify-center px-4 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-all active:scale-[0.98]">
                                Clear Source
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Preview Card --}}
            <div id="previewCard"
                 class="hidden bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

                <div class="px-6 sm:px-8 py-6 border-b border-slate-100 bg-slate-50/60">
                    <h2 class="text-md font-bold text-[#14213D] tracking-tight flex items-center gap-2 mb-5">
                        <span class="w-2 h-2 rounded-full bg-[#C9992A]"></span>
                        Project details
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label for="project_no" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                Project No.
                            </label>
                            <input
                                type="text"
                                id="project_no"
                                name="project_no"
                                class="block w-full px-4 py-2.5 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#14213D]/15 focus:border-[#14213D] text-sm bg-white"
                                placeholder="e.g. PRJ-001">
                        </div>

                        <div class="space-y-1.5">
                            <label for="project" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                Project
                            </label>
                            <input
                                type="text"
                                id="project"
                                name="project"
                                class="block w-full px-4 py-2.5 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#14213D]/15 focus:border-[#14213D] text-sm bg-white"
                                placeholder="Project name">
                        </div>

                        <div class="space-y-1.5">
                            <label for="total_contract_price" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                ABC
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm text-slate-400 pointer-events-none">₱</span>
                                <input
                                    type="number"
                                    id="total_contract_price"
                                    name="total_contract_price"
                                    class="block w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#14213D]/15 focus:border-[#14213D] text-sm bg-white"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100">
                    <h2 class="text-md font-bold text-[#14213D] tracking-tight flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#C9992A]"></span>
                        Extracted schools
                        <span id="schoolCountBadge" class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">0</span>
                    </h2>

                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="tableFilter"
                            class="block w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#14213D]/15 focus:border-[#14213D] text-sm bg-white"
                            placeholder="Filter schools...">
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table id="schoolTable" class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">School ID</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">School Name</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Municipality</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Division</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Region</th>
                            </tr>
                        </thead>
                        <tbody id="schoolBody" class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            {{-- Dynamically Injected Rows --}}
                        </tbody>
                    </table>
                </div>

                <div class="px-6 sm:px-8 py-5 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        id="saveBtn"
                        class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#157F4D] text-white font-semibold text-sm shadow-md shadow-[#157F4D]/15 hover:bg-[#116b40] transition-all focus:outline-none focus:ring-2 focus:ring-[#157F4D] focus:ring-offset-2 active:scale-[0.98]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Import to Database
                    </button>
                </div>
            </div>

        </div>
    </div>

    @push('styles')
    <style>
        .step-dot { border-color: #E2E5EC; color: #94A3B8; background: #fff; }
        .step-label { color: #94A3B8; }
        .step-line { background-color: #E2E5EC; }

        .step-item.is-complete .step-dot,
        .step-item.is-current .step-dot,
        .li-step-complete .step-dot,
        .li-step-current .step-dot {
            border-color: #14213D;
            background: #14213D;
            color: #fff;
        }
        li[data-step].is-current .step-dot {
            box-shadow: 0 0 0 4px rgba(20,33,61,0.12);
        }
        li[data-step].is-complete .step-dot { background: #C9992A; border-color: #C9992A; }
        li[data-step].is-complete .step-label,
        li[data-step].is-current .step-label { color: #14213D; }
        li[data-step].is-complete .step-line { background-color: #C9992A; }

        #dropZone.is-dragover {
            border-color: #14213D;
            background-color: rgba(20,33,61,0.04);
        }
        #dropZone.is-dragover #dropIcon {
            color: #14213D;
            transform: scale(1.08);
        }
        #dropZone:hover {
            border-color: #94A3B8;
        }

        .banner-error { background: #FEF2F2; border-color: #FECACA; color: #B91C1C; }
        .banner-success { background: #F0FDF4; border-color: #BBF7D0; color: #15803D; }

        @keyframes row-in {
            from { opacity: 0; transform: translateY(2px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .row-in { animation: row-in 0.25s ease-out both; }
    </style>
    @endpush

    @push('scripts')
    <script>
        let extractedSchools = [];
        const analyzeBtn = document.getElementById('analyzeBtn');
        const resetBtn = document.getElementById('resetBtn');
        const saveBtn = document.getElementById('saveBtn');
        const urlInput = document.getElementById('pdf_url');
        const fileInput = document.getElementById('pdf_file');
        const fileChosenText = document.getElementById('file-chosen-text');
        const dropZone = document.getElementById('dropZone');
        const banner = document.getElementById('banner');
        const tableFilter = document.getElementById('tableFilter');
        const schoolCountBadge = document.getElementById('schoolCountBadge');
        const previewCard = document.getElementById('previewCard');

        function setStep(n) {
            document.querySelectorAll('#stepper li[data-step]').forEach(function (li) {
                const step = parseInt(li.dataset.step, 10);
                li.classList.remove('is-complete', 'is-current');
                if (step < n) li.classList.add('is-complete');
                if (step === n) li.classList.add('is-current');
            });
        }
        setStep(1);

        function showBanner(type, message) {
            banner.className = 'rounded-xl px-4 py-3.5 text-sm font-medium flex items-start gap-2.5 border ' +
                (type === 'error' ? 'banner-error' : 'banner-success');
            const icon = type === 'error'
                ? '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.007M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            banner.innerHTML = icon + '<span>' + message + '</span>';
            banner.classList.remove('hidden');
        }

        function hideBanner() {
            banner.classList.add('hidden');
        }

        // Mutual Input Handlers for enhanced UX
        if (urlInput) {
            urlInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    fileInput.value = '';
                    fileChosenText.textContent = 'PDF documents only, up to 10MB';
                    fileChosenText.className = 'text-xs text-slate-400';
                    resetBtn.classList.remove('hidden');
                } else if (!fileInput.files.length) {
                    resetBtn.classList.add('hidden');
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    urlInput.value = ''; // clear url input if file picked
                    fileChosenText.textContent = 'Selected: ' + this.files[0].name;
                    fileChosenText.classList.remove('text-slate-400');
                    fileChosenText.classList.add('text-[#14213D]', 'font-semibold');
                    resetBtn.classList.remove('hidden');
                } else {
                    fileChosenText.textContent = 'PDF documents only, up to 10MB';
                    fileChosenText.classList.remove('text-[#14213D]', 'font-semibold');
                    fileChosenText.classList.add('text-slate-400');
                    if (!urlInput.value.trim()) resetBtn.classList.add('hidden');
                }
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                urlInput.value = '';
                fileInput.value = '';
                fileChosenText.textContent = 'PDF documents only, up to 10MB';
                fileChosenText.className = 'text-xs text-slate-400';
                previewCard.classList.add('hidden');
                extractedSchools = [];
                hideBanner();
                setStep(1);
                resetBtn.classList.add('hidden');
            });
        }

        // Drag & drop configuration
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(function (evt) {
                dropZone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropZone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropZone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropZone.classList.remove('is-dragover');
                });
            });
            dropZone.addEventListener('drop', function (e) {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        function renderRows(schools) {
            let rows = '';
            schools.forEach(function (school, i) {
                rows += '<tr class="hover:bg-slate-50/80 transition-colors row-in" style="animation-delay:' + Math.min(i * 20, 400) + 'ms">' +
                    '<td class="px-6 py-4 font-semibold text-[#14213D]">' + (school['School ID'] ?? '') + '</td>' +
                    '<td class="px-6 py-4 font-medium text-slate-700">' + (school['School Name'] ?? '') + '</td>' +
                    '<td class="px-6 py-4">' + (school['Municipality'] ?? '') + '</td>' +
                    '<td class="px-6 py-4">' + (school['Division'] ?? '') + '</td>' +
                    '<td class="px-6 py-4 text-slate-500">' + (school['Region'] ?? '') + '</td>' +
                    '</tr>';
            });
            document.getElementById('schoolBody').innerHTML = rows ||
                '<tr><td colspan="5" class="px-6 py-10 text-center text-slate-400 text-sm">No matching schools</td></tr>';
        }

        if (tableFilter) {
            tableFilter.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                if (!q) return renderRows(extractedSchools);
                const filtered = extractedSchools.filter(function (s) {
                    return Object.values(s).some(function (v) {
                        return String(v ?? '').toLowerCase().includes(q);
                    });
                });
                renderRows(filtered);
            });
        }

        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', async function () {
                const url = urlInput.value.trim();
                const file = fileInput.files[0];

                hideBanner();

                if (!url && !file) {
                    showBanner('error', 'Please enter a PDF URL or choose a file to upload.');
                    return;
                }

                setStep(2);
                analyzeBtn.disabled = true;
                analyzeBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Analyzing PDF...
                `;

                try {
                    let response;
                    if (file) {
                        const formData = new FormData();
                        formData.append('pdf_file', file);

                        response = await fetch("{{ route('school.preview') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                    } else {
                        response = await fetch("{{ route('school.preview') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ url: url })
                        });
                    }

                    // Soft-check if Response is JSON or HTML Error Page
                    const contentType = response.headers.get("content-type");
                    if (!response.ok) {
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            const errJson = await response.json();
                            throw new Error(errJson.error || 'Server processing error.');
                        } else {
                            throw new Error('Server returned an unexpected system error status: ' + response.status);
                        }
                    }

                    extractedSchools = await response.json();
                    renderRows(extractedSchools);
                    schoolCountBadge.textContent = extractedSchools.length;

                    previewCard.classList.remove('hidden');
                    previewCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setStep(3);
                    showBanner('success', 'Found ' + extractedSchools.length + ' school records. Review the details below before importing.');

                } catch (e) {
                    console.error(e);
                    setStep(1);
                    showBanner('error', e.message || 'Something went wrong while analyzing the PDF.');
                } finally {
                    analyzeBtn.disabled = false;
                    analyzeBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Analyze PDF
                    `;
                }
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', async function () {
                if (!extractedSchools.length) {
                    showBanner('error', 'No data to import.');
                    return;
                }

                setStep(4);
                saveBtn.disabled = true;
                saveBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Importing...
                `;

                try {
                    const response = await fetch("{{ route('school.import') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            project_no: document.getElementById('project_no').value,
                            project: document.getElementById('project').value,
                            total_contract_price: document.getElementById('total_contract_price').value,
                            schools: extractedSchools
                        })
                    });

                    const contentType = response.headers.get("content-type");
                    if (!response.ok) {
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            const result = await response.json();
                            throw new Error(result.error || 'Import failed');
                        } else {
                            throw new Error('Database exception occurred on the server.');
                        }
                    }

                    const result = await response.json();
                    showBanner('success', 'Imported ' + result.count + ' schools successfully.');
                    saveBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Imported
                    `;
                    if(resetBtn) resetBtn.classList.add('hidden');

                } catch (err) {
                    console.error(err);
                    setStep(3);
                    showBanner('error', err.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Import to Database
                    `;
                }
            });
        }
    </script>
    @endpush
</x-project_app-layout>