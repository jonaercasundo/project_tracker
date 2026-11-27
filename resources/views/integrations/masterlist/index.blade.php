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
                @if ($snapshot && $snapshot->tracker_project_id === null)
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">This saved snapshot has not been assigned to a Tracker project. Choose a project and sync this program again from <a class="font-semibold underline" href="{{ route('integrations.mmc-platform.index', ['tab' => 'api-webhooks']) }}">API &amp; Webhooks</a>.</p>
                @endif
                <form method="GET" action="{{ route('integrations.mmc-platform.masterlist') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_auto] lg:items-end">
                    <div>
                        <label for="masterlist-program" class="mb-1 block text-sm font-semibold text-slate-700">Synced program</label>
                        <select id="masterlist-program" name="program_id" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($snapshots as $availableSnapshot)
                                <option value="{{ $availableSnapshot->program_id }}" @selected($snapshot->program_id === $availableSnapshot->program_id)>
                                    {{ $availableSnapshot->program_name ?: 'Program '.$availableSnapshot->program_id }} · MMC ID: {{ $availableSnapshot->program_id }} · Tracker project: {{ $availableSnapshot->tracker_project_name ?: 'Not assigned' }} · {{ number_format($availableSnapshot->record_count) }} records
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

                <section x-data="{ open: @js($errors->has('school_ids') || $errors->has('returned_to_office_at') || session()->has('returnedToOfficeAtUpdate')) }" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Bulk set Returned to Office At</h2>
                            <p class="mt-1 text-sm text-slate-600">Set one return date for records matching multiple School IDs in this synced program.</p>
                        </div>
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="returned-to-office-at-form" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-500 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 2a.75.75 0 0 1 .75.75V4h5.5V2.75a.75.75 0 0 1 1.5 0V4h1A1.75 1.75 0 0 1 17 5.75v10.5A1.75 1.75 0 0 1 15.25 18h-10.5A1.75 1.75 0 0 1 3 16.25V5.75A1.75 1.75 0 0 1 4.75 4h1V2.75A.75.75 0 0 1 6.5 2ZM4.5 8v8.25c0 .138.112.25.25.25h10.5a.25.25 0 0 0 .25-.25V8h-11Z"/></svg>
                            <span x-text="open ? 'Close' : 'Set Returned to Office At'"></span>
                        </button>
                    </div>

                    <div x-cloak x-show="open" x-transition.opacity id="returned-to-office-at-form" class="mt-4 border-t border-slate-200 pt-4">
                        @if (session('returnedToOfficeAtUpdate'))
                            <p role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('returnedToOfficeAtUpdate') }}</p>
                        @endif
                        <form method="POST" action="{{ route('integrations.mmc-platform.masterlist.returned-to-office-at') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
                            @csrf
                            <input type="hidden" name="program_id" value="{{ $snapshot->program_id }}">
                            <div>
                                <label for="returned-to-office-at-school-ids" class="mb-1 block text-sm font-semibold text-slate-700">School IDs</label>
                                <textarea id="returned-to-office-at-school-ids" name="school_ids" rows="3" required maxlength="20000" placeholder="Enter IDs separated by commas or new lines" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('school_ids') }}</textarea>
                                <p class="mt-1 text-xs text-slate-500">Up to 500 IDs. Every matching delivery record for each ID will be updated.</p>
                                @error('school_ids')<p class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="returned-to-office-at-date" class="mb-1 block text-sm font-semibold text-slate-700">Returned to Office At date</label>
                                <input id="returned-to-office-at-date" name="returned_to_office_at" type="date" value="{{ old('returned_to_office_at') }}" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('returned_to_office_at')<p class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply date</button>
                        </form>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-2 border-y border-slate-100 py-3 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-900">{{ $snapshot->program_name ?: 'Program '.$snapshot->program_id }}</span> · Tracker project: {{ $trackerProjectName ?: 'Not assigned' }} · {{ number_format($rows->total()) }} records{{ filled($filters['search'] ?? null) ? ' matching search' : '' }}</p>
                    <p>Last synced {{ \Illuminate\Support\Carbon::parse($snapshot->synced_at)->format('M j, Y g:i A') }}</p>
                </div>

                @if ($rows->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 px-5 py-12 text-center text-sm text-slate-500">No masterlist records match this search.</div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-max divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    @foreach ($columns as $columnKey => $label)
                                        @php
                                            $isSorted = $sortBy === $columnKey;
                                            $nextDirection = $isSorted && $sortDirection === 'asc' ? 'desc' : 'asc';
                                            $sortUrl = request()->fullUrlWithQuery([
                                                'sort_by' => $columnKey,
                                                'sort_direction' => $nextDirection,
                                                'page' => null,
                                            ]);
                                        @endphp
                                        <th scope="col" aria-sort="{{ $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}" class="whitespace-nowrap px-4 py-3">
                                            <a href="{{ $sortUrl }}" aria-label="Sort by {{ $label }} {{ $nextDirection === 'asc' ? 'ascending' : 'descending' }}" class="inline-flex items-center gap-1.5 rounded text-left transition hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <span>{{ $label }}</span>
                                                <svg class="h-3.5 w-3.5 shrink-0 {{ $isSorted ? 'text-blue-600' : 'text-slate-400' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    @if ($isSorted)
                                                        @if ($sortDirection === 'asc')
                                                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l4.25 4.5a.75.75 0 0 1-.55 1.26h-8.5a.75.75 0 0 1-.55-1.26l4.25-4.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                                                        @else
                                                            <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.55-.24l-4.25-4.5A.75.75 0 0 1 5.75 11h8.5a.75.75 0 0 1 .55 1.26l-4.25 4.5A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
                                                        @endif
                                                    @else
                                                        <path d="M6.5 3a.75.75 0 0 1 .75.75v8.69l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 1.06-1.06l1.72 1.72V3.75A.75.75 0 0 1 6.5 3Zm7 14a.75.75 0 0 1-.75-.75V7.56l-1.72 1.72a.75.75 0 0 1-1.06-1.06l3-3a.75.75 0 0 1 1.06 0l3 3a.75.75 0 1 1-1.06 1.06l-1.72-1.72v8.69a.75.75 0 0 1-.75.75Z" />
                                                    @endif
                                                </svg>
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($rows as $row)
                                    <tr class="align-top hover:bg-slate-50/70">
                                        @foreach ($columns as $key => $label)
                                            <td class="whitespace-nowrap px-4 py-3 {{ in_array($key, ['project_id', 'school_id'], true) ? 'font-mono text-xs' : '' }}">@if (filled($row[$key] ?? null) && $key === 'returned_to_office_at'){{ \Illuminate\Support\Carbon::parse($row[$key])->format('d F, Y') }}@else{{ filled($row[$key] ?? null) ? $row[$key] : '—' }}@endif</td>
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
