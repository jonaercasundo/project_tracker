<x-project_app-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Import Schools
                </h1>

                <p class="text-sm text-slate-500">
                    Import school information directly from a DepEd Allocation List PDF URL, or upload the PDF directly.
                </p>
            </div>

            <a href="{{ route('school.index') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-300 transition">

                ← Back to Schools

            </a>

        </div>

        {{-- Import Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <form id="importForm">

                @csrf

                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        DepEd Allocation List PDF URL
                    </label>

                    <input
                        type="url"
                        id="pdf_url"
                        name="url"
                        class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="https://www.deped.gov.ph/wp-content/uploads/....pdf">

                </div>

                <div class="mt-4">

                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        ...or upload a PDF file directly
                    </label>

                    <input
                        type="file"
                        id="pdf_file"
                        name="pdf_file"
                        accept="application/pdf"
                        class="w-full rounded-xl border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">

                </div>

                <div class="mt-6 flex gap-3">

                <button
                    type="button"
                    id="analyzeBtn"
                    class="inline-flex items-center px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
                    Analyze PDF
                </button>

                    <button
                        type="button"
                        id="saveBtn"
                        class="hidden inline-flex items-center px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">

                        Import to Database

                    </button>

                </div>

            </form>

        </div>

        {{-- Preview Card --}}
        <div id="previewCard"
             class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="px-6 py-4 border-b">

                <h2 class="text-lg font-semibold text-slate-800">

                    Preview Extracted Schools

                </h2>

            </div>

            <div class="overflow-x-auto">

                <table id="schoolTable"
                       class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                School ID
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                School Name
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                Municipality
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                Division
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                Region
                            </th>

                        </tr>

                    </thead>

                    <tbody id="schoolBody"
                           class="divide-y divide-slate-100 bg-white">

                    </tbody>

                </table>

            </div>

        </div>

    </div>
@push('scripts')
    <script>
        let extractedSchools = [];
        console.log('School Import Script Loaded');

        const analyzeBtn = document.getElementById('analyzeBtn');

        console.log(analyzeBtn);

        if (analyzeBtn) {

            analyzeBtn.addEventListener('click', async function () {

                console.log('Analyze button clicked');

                const url = document.getElementById('pdf_url').value.trim();
                const fileInput = document.getElementById('pdf_file');
                const file = fileInput.files[0];

                if (!url && !file) {
                    alert('Please enter a PDF URL or choose a file to upload.');
                    return;
                }

                analyzeBtn.disabled = true;
                analyzeBtn.textContent = 'Analyzing...';

                try {

                    let response;

                    if (file) {
                        // File upload — send as multipart form data
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
                        // URL — send as JSON, same as before
                        response = await fetch("{{ route('school.preview') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                url: url
                            })
                        });
                    }

                    if (!response.ok) {
                        throw new Error(await response.text());
                    }

                    extractedSchools = await response.json();
                    let rows = '';
                    extractedSchools.forEach(function (school) {

                        rows += `
                            <tr>
                                <td class="px-4 py-2">${school["School ID"] ?? ''}</td>
                                <td class="px-4 py-2">${school["School Name"] ?? ''}</td>
                                <td class="px-4 py-2">${school["Municipality"] ?? ''}</td>
                                <td class="px-4 py-2">${school["Division"] ?? ''}</td>
                                <td class="px-4 py-2">${school["Region"] ?? ''}</td>
                            </tr>
                        `;
                    });

                    document.getElementById('schoolBody').innerHTML = rows;

                    document.getElementById('previewCard').classList.remove('hidden');

                    document.getElementById('saveBtn').classList.remove('hidden');

                } catch (e) {

                    console.error(e);

                    alert(e.message);

                } finally {

                    analyzeBtn.disabled = false;
                    analyzeBtn.textContent = 'Analyze PDF';

                }

            });

        } else {

            console.error('Analyze button not found.');

        }
saveBtn.addEventListener('click', async function () {

    if (!extractedSchools.length) {
        alert('No data to import');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Importing...';

    try {

        const response = await fetch("{{ route('school.import') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
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
        saveBtn.textContent = 'Import to Database';
    }
});
    </script>
@endpush
</x-project_app-layout>