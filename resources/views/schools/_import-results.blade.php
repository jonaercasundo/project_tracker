<div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-semibold text-slate-500">Total rows</p>
        <p class="mt-1 text-xl font-extrabold tabular-nums text-slate-900">{{ number_format($report['summary']['total']) }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-semibold text-emerald-700">{{ $isPreview ? 'Ready to import' : 'Imported' }}</p>
        <p class="mt-1 text-xl font-extrabold tabular-nums text-emerald-800">{{ number_format($report['summary'][$isPreview ? 'ready' : 'imported']) }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-semibold text-amber-700">Skipped duplicates</p>
        <p class="mt-1 text-xl font-extrabold tabular-nums text-amber-800">{{ number_format($report['summary']['duplicate']) }}</p>
    </div>
    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="text-xs font-semibold text-red-700">Failed rows</p>
        <p class="mt-1 text-xl font-extrabold tabular-nums text-red-800">{{ number_format($report['summary']['failed']) }}</p>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="overflow-x-auto focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" tabindex="0" role="region" aria-label="{{ $isPreview ? 'Import preview' : 'Import results' }} rows">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach (['File row', 'School ID', 'School name', 'Status', 'Details and validation'] as $heading)
                        <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report['rows'] as $row)
                    <tr class="align-top">
                        <th scope="row" class="px-4 py-4 font-mono text-xs text-slate-500">{{ $row['row'] }}</th>
                        <td class="px-4 py-4 font-mono text-xs text-slate-700">{{ $row['data']['school_id'] ?? '—' }}</td>
                        <td class="min-w-[180px] px-4 py-4 font-semibold text-slate-800">{{ $row['data']['school_name'] ?? '—' }}</td>
                        <td class="px-4 py-4">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ in_array($row['status'], ['ready', 'imported'], true) ? 'bg-emerald-50 text-emerald-700' : ($row['status'] === 'duplicate' ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700') }}">
                                {{ match ($row['status']) { 'ready' => 'Ready', 'imported' => 'Imported', 'duplicate' => 'Duplicate', default => 'Failed' } }}
                            </span>
                        </td>
                        <td class="min-w-[300px] px-4 py-4">
                            @if (!empty($row['errors']))
                                <ul class="mb-3 list-disc space-y-1 pl-4 text-xs {{ $row['status'] === 'duplicate' ? 'text-amber-800' : 'text-red-700' }}">
                                    @foreach ($row['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <details>
                                <summary class="cursor-pointer rounded text-xs font-bold text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Review all fields</summary>
                                <dl class="mt-3 space-y-2">
                                    @foreach (['school_id' => 'School ID', 'school_name' => 'School name', 'address' => 'Address', 'municipality' => 'Municipality', 'division' => 'Division', 'region' => 'Region', 'contact_person' => 'Contact person', 'contact' => 'Contact'] as $fieldName => $label)
                                        <div>
                                            <dt class="text-[11px] font-bold text-slate-500">{{ $label }}</dt>
                                            <dd class="break-words text-xs text-slate-700">{{ $row['data'][$fieldName] ?? '—' }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No school rows were found in this file.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
