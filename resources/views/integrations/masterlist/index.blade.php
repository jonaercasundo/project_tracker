<x-project_app-layout>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operations / Integrations</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">MMC Masterlist</h1>
                <p class="mt-2 text-sm text-slate-500">Browse the masterlist records saved by the MMC Platform sync.</p>
            </div>
            <a href="{{ route('integrations.mmc-platform.index', ['tab' => 'api-webhooks']) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-700">API &amp; Webhooks</a>
        </div>

        @if ($snapshots->isEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">No synced masterlist yet</h2>
                <p class="mt-2 text-sm text-slate-600">Sync a program from API &amp; Webhooks to save its records here.</p>
                <a href="{{ route('integrations.mmc-platform.index', ['tab' => 'api-webhooks']) }}" class="mt-5 inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Open API &amp; Webhooks</a>
            </section>
        @else
            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <form method="GET" action="{{ route('integrations.mmc-platform.masterlist') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_auto] lg:items-end">
                    <div>
                        <label for="masterlist-program" class="mb-1 block text-sm font-semibold text-slate-700">Synced program</label>
                        <select id="masterlist-program" name="program_id" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($snapshots as $availableSnapshot)
                                <option value="{{ $availableSnapshot->program_id }}" @selected($snapshot->program_id === $availableSnapshot->program_id)>
                                    {{ $availableSnapshot->program_name ?: 'Program '.$availableSnapshot->program_id }} · Project ID: {{ $availableSnapshot->program_id }} · {{ number_format($availableSnapshot->record_count) }} records
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="masterlist-search" class="mb-1 block text-sm font-semibold text-slate-700">Search records</label>
                        <input id="masterlist-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="School, ID, location, or item" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="masterlist-per-page" class="mb-1 block text-sm font-semibold text-slate-700">Rows per page</label>
                        <select id="masterlist-per-page" name="per_page" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach ([25, 50, 100] as $pageSize)
                                <option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Apply</button>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-2 border-y border-slate-100 py-3 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-900">{{ $snapshot->program_name ?: 'Program '.$snapshot->program_id }}</span> · {{ number_format($rows->total()) }} records{{ filled($filters['search'] ?? null) ? ' matching search' : '' }}</p>
                    <p>Last synced {{ \Illuminate\Support\Carbon::parse($snapshot->synced_at)->format('M j, Y g:i A') }}</p>
                </div>

                @if ($rows->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 px-5 py-12 text-center text-sm text-slate-500">No masterlist records match this search.</div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-max divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    @foreach ($columns as $label)
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($rows as $row)
                                    <tr class="align-top hover:bg-slate-50/70">
                                        @foreach ($columns as $key => $label)
                                            <td class="whitespace-nowrap px-4 py-3 {{ in_array($key, ['delivery_id', 'lot_id', 'school_id'], true) ? 'font-mono text-xs' : '' }}">{{ filled($row[$key] ?? null) ? $row[$key] : '—' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $rows->links() }}</div>
                @endif
            </section>
        @endif
    </div>
</x-project_app-layout>
