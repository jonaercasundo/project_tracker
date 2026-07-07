<x-project_app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
                        Import Schools
                    </h1>
                    <p class="text-sm text-slate-500 mt-1 max-w-xl">
                        Import school information directly from a DepEd Allocation List PDF URL or upload the file from your local storage.
                    </p>
                </div>

                <div>
                    <a href="{{ route('school.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold text-sm shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-all active:scale-[0.98]">
                        <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Schools
                    </a>
                </div>
            </div>

            {{-- Import Card --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8">
                    <form id="importForm" class="space-y-6">
                        @csrf

                        {{-- URL Input --}}
                        <div class="space-y-2">
                            <label for="pdf_url" class="block text-sm font-semibold text-slate-800">
                                DepEd Allocation List PDF URL
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </div>
                                <input
                                    type="url"
                                    id="pdf_url"
                                    name="url"
                                    class="block w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm transition-all bg-slate-50/50"
                                    placeholder="https://www.deped.gov.ph/wp-content/uploads/....pdf">
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="relative flex py-2 items-center">
                            <div class="flex-grow border-t border-slate-200"></div>
                            <span class="flex-shrink mx-4 text-xs font-semibold uppercase tracking-wider text-slate-400 bg-white px-2">or</span>
                            <div class="flex-grow border-t border-slate-200"></div>
                        </div>

                        {{-- File Input Container --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-800">
                                Upload PDF file directly
                            </label>
                            <div class="relative border-2 border-dashed border-slate-200 hover:border-indigo-400 rounded-2xl p-6 transition-all group bg-slate-50/30 hover:bg-slate-50/80">
                                <input
                                    type="file"
                                    id="pdf_file"
                                    name="pdf_file"
                                    accept="application/pdf"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="text-center space-y-2">
                                    <div class="inline-flex p-3 rounded-xl bg-white shadow-sm border border-slate-100 text-slate-500 group-hover:text-indigo-600 group-hover:scale-110 transition-all">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        <span class="font-semibold text-indigo-600 hover:text-indigo-500">Click to upload</span> or drag and drop
                                    </div>
                                    <p id="file-chosen-text" class="text-xs text-slate-400">PDF documents only up to 10MB</p>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-2 flex items-center gap-3">
                            <button
                                type="button"
                                id="analyzeBtn"
                                class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 text-white font-semibold text-sm shadow-md shadow-indigo-600/10 hover:bg-indigo-700 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:scale-[0.98]">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Analyze PDF
                            </button>

                            <button
                                type="button"
                                id="saveBtn"
                                class="hidden inline-flex items-center justify-center px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold text-sm shadow-md shadow-emerald-600/10 hover:bg-emerald-700 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-[0.98]">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                                Import to Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Preview Card --}}
            <div id="previewCard"
                 class="hidden bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden transition-all duration-300">
                {{-- Project Information --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    {{-- Project No --}}
                    <div class="space-y-2">
                        <label for="project_no" class="block text-sm font-semibold text-slate-800">
                            Project No.
                        </label>
                        <input
                            type="text"
                            id="project_no"
                            name="project_no"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm bg-slate-50/50"
                            placeholder="e.g. PRJ-001">
                    </div>

                    {{-- Project --}}
                    <div class="space-y-2">
                        <label for="project" class="block text-sm font-semibold text-slate-800">
                            Project
                        </label>
                        <input
                            type="text"
                            id="project"
                            name="project"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm bg-slate-50/50"
                            placeholder="Project Name">
                    </div>

                    {{-- Total Contract Price --}}
                    <div class="space-y-2">
                        <label for="total_contract_price" class="block text-sm font-semibold text-slate-800">
                            ABC
                        </label>
                        <input
                            type="number"
                            id="total_contract_price"
                            name="total_contract_price"
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm bg-slate-50/50"
                            placeholder="₱ 0.00">
                    </div>

                </div>
                 <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-md font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        Preview Extracted Schools
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table id="schoolTable" class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
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
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        let extractedSchools = [];
        const analyzeBtn = document.getElementById('analyzeBtn');
        const saveBtn = document.getElementById('saveBtn');
        const fileInput = document.getElementById('pdf_file');
        const fileChosenText = document.getElementById('file-chosen-text');

        // Dynamic file-label update for better UX
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if(this.files && this.files.length > 0) {
                    fileChosenText.textContent = `Selected: ${this.files[0].name}`;
                    fileChosenText.classList.remove('text-slate-400');
                    fileChosenText.classList.add('text-indigo-600', 'font-medium');
                } else {
                    fileChosenText.textContent = 'PDF documents only up to 10MB';
                    fileChosenText.classList.remove('text-indigo-600', 'font-medium');
                    fileChosenText.classList.add('text-slate-400');
                }
            });
        }

        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', async function () {
                const url = document.getElementById('pdf_url').value.trim();
                const file = fileInput.files[0];

                if (!url && !file) {
                    alert('Please enter a PDF URL or choose a file to upload.');
                    return;
                }

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
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                    } else {
                        response = await fetch("{{ route('school.preview') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ url: url })
                        });
                    }

                    if (!response.ok) {
                        throw new Error(await response.text());
                    }

                    extractedSchools = await response.json();
                    let rows = '';
                    extractedSchools.forEach(function (school) {
                        rows += `
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-900">${school["School ID"] ?? ''}</td>
                                <td class="px-6 py-4 font-medium text-slate-700">${school["School Name"] ?? ''}</td>
                                <td class="px-6 py-4">${school["Municipality"] ?? ''}</td>
                                <td class="px-6 py-4">${school["Division"] ?? ''}</td>
                                <td class="px-6 py-4 text-slate-500">${school["Region"] ?? ''}</td>
                            </tr>
                        `;
                    });

                    document.getElementById('schoolBody').innerHTML = rows;
                    document.getElementById('previewCard').classList.remove('hidden');
                    saveBtn.classList.remove('hidden');

                } catch (e) {
                    console.error(e);
                    alert(e.message);
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
                    alert('No data to import');
                    return;
                }

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
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            project_no: document.getElementById('project_no').value,
                            project: document.getElementById('project').value,
                            total_contract_price: document.getElementById('total_contract_price').value,
                            schools: extractedSchools
                        })
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || 'Import failed');
                    }

                    alert(`Success! Imported ${result.count} schools`);

                } catch (err) {
                    console.error(err);
                    alert(err.message);
                } finally {
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