<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-bold text-slate-900">Project Source Mapping</h1></x-slot>
    <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Historical source-link dry run</h2>
            <p class="mt-1 text-sm text-slate-600">Proposals use existing identifiers, verified project and bidding relationships, and matching lot numbers. Project List status appears only after a source link is verified. Review and confirmation never change financial values or project status.</p>
            @unless ($canConfirm)
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-900" role="status">Source-link schema is not available in this database. Review is read-only; confirmation is disabled until the reviewed migration is applied.</p>
            @endunless
            @if (session('success')) <p class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ session('success') }}</p> @endif
            @if ($errors->any()) <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div> @endif
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                @foreach ($summary as $status => $count)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ str_replace('_', ' ', ucfirst($status)) }}: {{ $count }}</span>
                @endforeach
            </div>
        </div>

        <form action="{{ route('finance.ppl.sources.index') }}" method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <label class="min-w-[14rem] flex-1 text-xs font-semibold text-slate-700">Search PPL ID, code, reference, or lot
                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" maxlength="120" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
            </label>
            <label class="text-xs font-semibold text-slate-700">Link status
                <select name="status" class="mt-1 block rounded-lg border-slate-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach (['auto_matched' => 'Verified proposal', 'ambiguous' => 'Needs Review', 'needs_review' => 'Needs Review', 'unmatched' => 'Unlinked', 'already_linked' => 'Linked', 'already_manually_verified' => 'Manually linked', 'ownership_conflict' => 'Ownership conflict'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-slate-700">Rows
                <select name="per_page" class="mt-1 block rounded-lg border-slate-300 text-sm">@foreach ([10, 25, 50] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>@endforeach</select>
            </label>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Review</button>
            <a href="{{ route('finance.ppl.sources.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Clear</a>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="PPL source-link review">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">PPL Form</th><th class="px-4 py-3">Project / lot</th><th class="px-4 py-3">Project List</th><th class="px-4 py-3">Bidding</th><th class="px-4 py-3">Link status and evidence</th><th class="px-4 py-3">Review</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php($link = $row['existing_link'])
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold"><a class="text-emerald-800 hover:underline" href="{{ route('ppl_forms.show', $row['ppl_form_id']) }}">#{{ $row['ppl_form_id'] }}</a></td>
                                <td class="max-w-[18rem] px-4 py-3"><p class="line-clamp-2 font-semibold" title="{{ $row['project_title'] }}">{{ $row['project_title'] }}</p><p class="mt-1 text-xs text-slate-600">{{ $row['project_code'] ?: 'No project code' }} · Lot {{ $row['lot_number'] ?: 'N/A' }}</p><p class="text-xs text-slate-500">{{ $row['project_identifier'] ?: 'No reference' }}</p></td>
                                <td class="px-4 py-3">{{ ($link?->project_id ?? $row['project_id']) !== null ? '#'.($link?->project_id ?? $row['project_id']) : 'Unlinked' }}
                                    @foreach ($row['candidate_projects'] as $candidate)<p class="mt-1 text-xs text-slate-600">#{{ $candidate['id'] }} {{ $candidate['name'] }} ({{ $candidate['identifier'] }})</p>@endforeach
                                </td>
                                <td class="px-4 py-3">{{ ($link?->project_information_id ?? $row['bidding_id']) !== null ? '#'.($link?->project_information_id ?? $row['bidding_id']) : 'Unlinked' }}
                                    @foreach ($row['candidate_biddings'] as $candidate)<p class="mt-1 max-w-[15rem] text-xs text-slate-600">#{{ $candidate['id'] }} {{ $candidate['name'] }} ({{ $candidate['identifier'] }})</p>@endforeach
                                    <p class="mt-1 text-xs text-slate-600">Bidding lot #{{ $link?->bidding_lot_id ?? $row['bidding_lot_id'] ?? '—' }} · Project lot #{{ $link?->legacy_lot_id ?? $row['legacy_lot_id'] ?? '—' }}</p>
                                </td>
                                <td class="max-w-[15rem] px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ in_array($row['status'], ['already_linked', 'already_manually_verified'], true) ? 'bg-emerald-50 text-emerald-800' : (in_array($row['status'], ['ambiguous', 'needs_review'], true) ? 'bg-amber-50 text-amber-900' : 'bg-slate-100 text-slate-700') }}">{{ match ($row['status']) { 'auto_matched' => 'Proposed unique match', 'ambiguous', 'needs_review' => 'Needs Review', 'unmatched' => 'Unlinked', 'already_linked', 'already_manually_verified' => 'Verified link', default => 'Ownership conflict' } }}</span>
                                    @foreach ($row['reasons'] as $reason)<p class="mt-2 text-xs text-slate-600">{{ $reason }}</p>@endforeach
                                    @if ($link)<p class="mt-2 text-xs text-slate-600">{{ ucfirst($link->match_origin) }} · {{ $link->confirmer?->name ?? 'Unknown reviewer' }} · {{ $link->confirmed_at?->format('Y-m-d H:i') ?? 'Date unavailable' }}</p>@if ($link->match_status === 'verified' && $link->project)<p class="mt-1 text-xs font-semibold text-emerald-800">Project List: {{ $link->project->status }}</p>@endif @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($canConfirm && $row['status'] === 'auto_matched')
                                        <form method="POST" action="{{ route('finance.ppl.sources.confirm', $row['ppl_form_id']) }}">@csrf <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Confirm Link</button></form>
                                    @elseif ($canConfirm && in_array($row['status'], ['ambiguous', 'needs_review', 'unmatched'], true))
                                        <details><summary class="cursor-pointer text-xs font-semibold text-emerald-800">Link Project</summary>
                                            <form method="POST" action="{{ route('finance.ppl.sources.confirm', $row['ppl_form_id']) }}" class="mt-2 grid gap-2">@csrf
                                                <p class="text-xs text-slate-600">Confirm existing IDs only after checking project, bidding, and lot ownership.</p>
                                                @foreach (['project_id' => 'Project List ID', 'project_information_id' => 'Bidding ID', 'bidding_lot_id' => 'Bidding lot ID', 'legacy_lot_id' => 'Project lot ID'] as $field => $label)
                                                    <label class="text-xs font-semibold text-slate-700">{{ $label }}<input type="number" min="1" name="{{ $field }}" class="mt-1 w-full rounded border-slate-300 text-xs" @if (in_array($field, ['project_id', 'project_information_id'], true)) required @endif></label>
                                                @endforeach
                                                <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Confirm verified records</button>
                                            </form>
                                        </details>
                                    @else <span class="text-xs text-slate-500">{{ $canConfirm ? 'No change available' : 'Read-only' }}</span> @endif
                                </td>
                            </tr>
                        @empty <tr><td colspan="6" class="px-4 py-10 text-center text-slate-600">No PPL Forms match the review filters.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>
        </section>
    </div>
</x-finance_app-layout>
