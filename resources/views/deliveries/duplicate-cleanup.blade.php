<x-project_app-layout>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">Delivery review</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Duplicate DR Cleanup</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Possible duplicates are grouped by company, project, DR, school, lot, keystage, and delivery date. Multiple schools, lots, and package rows remain separate. Review each record before marking any row Cancelled.</p>
        </div>
        <a href="{{ route('deliveries.duplicate-cleanup.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Export review preview</a>
    </div>

    @if(session('status'))
        <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('deliveries.duplicate-cleanup') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-4">
        <label class="text-xs font-semibold text-slate-600">Search DR, project, or school
            <input name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Search…">
        </label>
        <label class="text-xs font-semibold text-slate-600">Project
            <select name="project_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name }}</option>@endforeach</select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Delivery status
            <select name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Any status</option>@foreach(['Pending', 'Delivered', 'Cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
        </label>
        <div class="flex items-end gap-2"><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Apply filters</button><a href="{{ route('deliveries.duplicate-cleanup') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Reset</a></div>
    </form>

    <div class="mt-5 space-y-4">
        @forelse($groups as $group)
            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900">DR {{ $group->dr_no ?: '(blank)' }} <span class="font-normal text-slate-500">· {{ $group->project_name }}</span></h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $group->school_name ?? 'Unknown school' }} · Lot {{ $group->lot_name ?? '—' }} · {{ $group->record_count }} delivery records · {{ $group->packages->count() }} linked package status rows</p>
                    </div>
                    @if($group->eligible)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Review eligible</span>@else<span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Blocked</span>@endif
                </header>
                @if(!$group->eligible)<div class="border-b border-rose-100 bg-rose-50 px-5 py-3 text-sm text-rose-800"><strong>Cannot resolve:</strong> {{ $group->exclusion }}</div>@endif
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-white text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Delivery record</th><th class="px-5 py-3">Delivery status</th><th class="px-5 py-3">Package rows</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($group->records as $record)
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-slate-800">#{{ $record->delivery_id }}<div class="text-xs font-normal text-slate-500">{{ $record->delivery_date ?? 'Date unavailable' }}</div></td>
                                    <td class="px-5 py-3">{{ $record->status ?? 'Unknown' }}</td>
                                    <td class="px-5 py-3 text-slate-600">@php($linked = $group->packages->where('delivery_id', $record->delivery_id))@if($linked->isEmpty()) No linked package rows @else {{ $linked->map(fn($item) => 'Package '.$item->package_num.' · '.$item->status)->implode('; ') }} @endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($group->eligible)
                    <form method="POST" action="{{ route('deliveries.duplicate-cleanup.resolve') }}" class="resolve-form border-t border-slate-100 bg-slate-50 px-5 py-4" onsubmit="return confirm('Confirm duplicate cleanup? The chosen record will be kept. Every other delivery record shown in this group will be marked Cancelled. Linked package, proof, billing, inventory, and logistics records will not be deleted.');">
                        @csrf
                        <p class="mb-3 text-xs text-slate-600">Choose the record to keep. All other delivery rows in this group will be marked Cancelled after your confirmation.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="text-xs font-semibold text-slate-600">Keep
                                <select name="keep_delivery_id" class="mt-1 block rounded-lg border-slate-300 text-sm">@foreach($group->records as $record)<option value="{{ $record->delivery_id }}">Delivery #{{ $record->delivery_id }} · {{ $record->status }}</option>@endforeach</select>
                            </label>
                            @foreach($group->records as $record)<input type="hidden" name="affected_delivery_ids[]" value="{{ $record->delivery_id }}" class="affected-choice">@endforeach
                            <button class="mt-4 rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">Review and resolve duplicate group</button>
                        </div>
                        <p class="resolution-plan mt-3 text-sm font-semibold text-slate-800" aria-live="polite"></p>
                        <p class="mt-3 text-xs text-slate-500">Affected records are every row in this group except the selected keep record. Existing billing or linked logistics dependencies block the action.</p>
                    </form>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">No possible duplicate delivery groups match these filters.</div>
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
