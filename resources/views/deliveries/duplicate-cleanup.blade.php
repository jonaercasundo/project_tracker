<x-project_app-layout>
    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">Delivery review</p>
                <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Duplicate DR Cleanup</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Repeated DR numbers are grouped by company, project, and normalized DR number. Review the linked delivery records and their differing school, lot, keystage, and date fields before deciding whether any exact-scope rows are duplicates.</p>
            </div>
            <a href="{{ route('deliveries.duplicate-cleanup.export', request()->query()) }}" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Export review preview</a>
        </div>

        @if(session('status'))
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="status">
            Found <strong>{{ number_format($summary['groups']) }}</strong> repeated DR-number groups containing <strong>{{ number_format($summary['records']) }}</strong> delivery records with the current filters.
        </div>

        <form method="GET" action="{{ route('deliveries.duplicate-cleanup') }}" class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-xs font-semibold text-slate-600">Search DR, project, or school
                <input name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Search&#8230;">
            </label>
            <x-searchable-project-filter :projects="$projects" :selected="$filters['project_id'] ?? ''" name="project_id" id="duplicate-dr-project-filter" all-label="All projects" :truncate-selected="true" />
            <label class="text-xs font-semibold text-slate-600">Delivery status
                <select name="status" class="mt-1 h-[42px] w-full rounded-lg border-slate-300 text-sm"><option value="">Any status</option>@foreach(['Pending', 'Delivered', 'Cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
            </label>
            <div class="flex items-end gap-2"><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Apply filters</button><a href="{{ route('deliveries.duplicate-cleanup') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Reset</a></div>
        </form>

        <div class="mt-5 space-y-4">
            @forelse($groups as $group)
                <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="break-words font-bold text-slate-900">{{ $group->project_name }} <span class="font-normal text-slate-600">&#183; DR {{ $group->normalized_dr }}</span></h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $group->record_count }} delivery records share this normalized DR number.</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">Possible duplicate</span>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[64rem] divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-white text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Delivery ID</th><th class="px-4 py-3">DR as stored</th><th class="px-4 py-3">School</th><th class="px-4 py-3">Lot</th><th class="px-4 py-3">Keystage</th><th class="px-4 py-3">Delivery date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Linked packages</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($group->records as $record)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-slate-800">#{{ $record->delivery_id }}</td>
                                        <td class="break-words px-4 py-3">{{ $record->dr_no }}</td>
                                        <td class="max-w-xs break-words px-4 py-3">{{ $record->school_name ?? 'Unknown' }} <span class="text-xs text-slate-500">({{ $record->school_id }})</span></td>
                                        <td class="break-words px-4 py-3">{{ $record->lot_name ?? '—' }} <span class="text-xs text-slate-500">{{ $record->lot_id ? '(#'.$record->lot_id.')' : '' }}</span></td>
                                        <td class="break-words px-4 py-3">{{ $record->keystage_id ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $record->delivery_date ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $record->status ?? 'Unknown' }}</td>
                                        <td class="max-w-sm break-words px-4 py-3 text-slate-600">
                                            @php($linked = $group->packages->where('delivery_id', $record->delivery_id))
                                            @if($linked->isEmpty()) No linked package rows @else {{ $linked->map(fn ($item) => 'Package '.$item->package_num.' · '.$item->status)->implode('; ') }} @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($group->exactDuplicateSets->isNotEmpty())
                        <div class="space-y-3 border-t border-slate-100 p-4 sm:p-5">
                            <h3 class="text-sm font-bold text-slate-800">Exact-scope duplicate candidates</h3>
                            <p class="text-xs text-slate-600">Only records with matching school, lot, keystage, and delivery date are offered for resolution. Other records with the same DR remain untouched.</p>
                            @foreach($group->exactDuplicateSets as $duplicateSet)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-800">Matching rows: {{ $duplicateSet->records->pluck('delivery_id')->map(fn ($id) => '#'.$id)->implode(', ') }}</p>
                                    @if(!$duplicateSet->eligible)<p class="mt-2 text-sm text-rose-800"><strong>Cannot resolve:</strong> {{ $duplicateSet->exclusion }}</p>@endif
                                    @if($duplicateSet->eligible)
                                        <form method="POST" action="{{ route('deliveries.duplicate-cleanup.resolve') }}" class="resolve-form mt-3" onsubmit="return confirm('Confirm duplicate cleanup? The chosen record will be kept. Every other record listed in this exact-scope set will be marked Cancelled. Linked records will be preserved.');">
                                            @csrf
                                            <div class="flex flex-wrap items-end gap-3">
                                                <label class="min-w-0 text-xs font-semibold text-slate-600">Keep
                                                    <select name="keep_delivery_id" class="mt-1 block h-[42px] max-w-full rounded-lg border-slate-300 text-sm">@foreach($duplicateSet->records as $record)<option value="{{ $record->delivery_id }}">Delivery #{{ $record->delivery_id }} · {{ $record->status }}</option>@endforeach</select>
                                                </label>
                                                @foreach($duplicateSet->records as $record)<input type="hidden" name="affected_delivery_ids[]" value="{{ $record->delivery_id }}" class="affected-choice">@endforeach
                                                <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">Review and resolve this exact-scope set</button>
                                            </div>
                                            <p class="resolution-plan mt-3 break-words text-sm font-semibold text-slate-800" aria-live="polite"></p>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="border-t border-slate-100 px-5 py-4 text-sm text-slate-600">These records share a DR number but have no repeated school, lot, keystage, and date combination. Review the differences above; no records are marked for cleanup.</p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">No possible duplicate DR-number groups match these filters.</div>
            @endforelse
        </div>
        <div class="mt-6">{{ $groups->links() }}</div>
    </div>

    <script>
        document.querySelectorAll('.resolve-form').forEach((form) => {
            const keep = form.querySelector('[name="keep_delivery_id"]');
            const affected = [...form.querySelectorAll('.affected-choice')];
            const plan = form.querySelector('.resolution-plan');
            const update = () => {
                affected.forEach((input) => { input.disabled = input.value === keep.value; });
                const ids = affected.filter((input) => !input.disabled).map((input) => `#${input.value}`);
                plan.textContent = `Will keep delivery #${keep.value}; will mark Cancelled: ${ids.join(', ')}.`;
            };
            keep.addEventListener('change', update);
            update();
        });
    </script>
</x-project_app-layout>
