<x-finance_app-layout>
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('finance.ppl-import.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">← Import history</a>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $batch->original_file_name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Preview created {{ $batch->previewed_at?->format('M j, Y g:i A') }} · SHA-256 <span class="break-all font-mono text-xs">{{ $batch->file_sha256 }}</span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('finance.ppl-import.download', $batch) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download source</a>
                <span class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ ucfirst($batch->status) }}</span>
            </div>
        </div>

        @if (session('status'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if ($batch->error_message)<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $batch->error_message }}</div>@endif

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach ([['Workbook rows', $batch->summary['form_rows'] ?? 0], ['Ready', $batch->summary['ready'] ?? 0], ['Warnings', $batch->summary['warnings'] ?? 0], ['Blocked', $batch->summary['blocked'] ?? 0], ['Auto-matched', $batch->summary['auto_matched'] ?? 0], ['Needs Review', $batch->summary['needs_review'] ?? 0], ['Unmatched', $batch->summary['unmatched'] ?? 0]] as [$label, $value])
                <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $value }}</p></div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Row-by-row source and calculation review</h2><p class="mt-1 text-sm text-slate-600">Only ready and acknowledged warning rows can create new MMC financial plans. Existing PPL Forms stay unchanged.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1550px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Workbook row</th><th class="px-4 py-3">Workbook project / lot</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">ABC</th><th class="px-4 py-3 text-right">Contract</th><th class="px-4 py-3 text-right">Factory cost</th><th class="px-4 py-3">Data links and checks</th><th class="w-[390px] px-4 py-3">Explicit MMC source mapping</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch->rows as $row)
                            @php($normalized = $row->normalized_data ?? [])
                            @php($inputs = data_get($normalized, 'plan.inputs', []))
                            @php($planLinks = data_get($normalized, 'plan', []))
                            @php($sourceMapping = data_get($normalized, 'source_mapping', []))
                            @php($mappingUserId = data_get($sourceMapping, 'confirmed_by') ?? data_get($sourceMapping, 'import_confirmed_by'))
                            @php($mappingUser = $mappingUserId ? ($mappingUsers[$mappingUserId] ?? null) : null)
                            <tr class="align-top">
                                <td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $row->source_sheet }}!{{ $row->source_row }}</td>
                                <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $row->project_code }} · Lot {{ $row->lot_number }}</p><p class="mt-1 text-xs text-slate-500">{{ data_get($normalized, 'project_title') }}</p><p class="mt-1 text-xs text-slate-500">PPL Form #{{ data_get($normalized, 'plan.ppl_form_id') ?? 'not matched' }}</p></td>
                                <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->status === 'blocked' ? 'bg-rose-100 text-rose-800' : ($row->status === 'warning' ? 'bg-amber-100 text-amber-900' : ($row->status === 'imported' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700')) }}">{{ ucfirst($row->status) }}</span>@if($row->financial_plan_id)<p class="mt-2"><a class="font-semibold text-emerald-800 hover:underline" href="{{ route('finance.plans.show', $row->financial_plan_id) }}">Plan #{{ $row->financial_plan_id }}</a></p>@endif</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ is_numeric($inputs['abc'] ?? null) ? '₱'.number_format((float) $inputs['abc'], 2) : '—' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ is_numeric($inputs['contract_amount'] ?? null) ? '₱'.number_format((float) $inputs['contract_amount'], 2) : '—' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ is_numeric($inputs['factory_cost'] ?? null) ? '₱'.number_format((float) $inputs['factory_cost'], 2) : '—' }}</td>
                                <td class="px-4 py-4">
                                    @if (($sourceMapping['warnings'] ?? []) !== [])
                                        <p class="mb-1 text-xs font-semibold text-amber-900">Source mapping</p>
                                        <ul class="mb-3 list-disc space-y-1 pl-4">
                                            @foreach ($sourceMapping['warnings'] as $message)
                                                <li class="text-xs text-amber-900">{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (($row->errors ?? []) !== [])
                                        <p class="mb-1 text-xs font-semibold text-rose-900">Financial validation errors</p>
                                        <ul class="mb-3 list-disc space-y-1 pl-4">
                                            @foreach ($row->errors as $message)
                                                <li class="text-xs text-rose-800">{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (($row->warnings ?? []) !== [])
                                        <p class="mb-1 text-xs font-semibold text-amber-900">Financial review warnings</p>
                                        <ul class="list-disc space-y-1 pl-4">
                                            @foreach ($row->warnings as $message)
                                                <li class="text-xs text-amber-900">{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @foreach (data_get($normalized, 'reconciliation.differences', []) as $metric => $difference)
                                        <p class="mt-2 text-xs text-amber-900">{{ str($metric)->replace('_', ' ')->title() }} differs: workbook {{ number_format((float) $difference['workbook'], 2) }}, calculated {{ number_format((float) $difference['calculated'], 2) }} ({{ $difference['cell'] }})</p>
                                    @endforeach
                                    <p class="mt-2 text-xs text-slate-500">Linked PPL #{{ data_get($normalized, 'plan.ppl_form_id') ?? '-' }} / bidding #{{ data_get($normalized, 'plan.project_information_id') ?? '-' }} / lot #{{ data_get($normalized, 'plan.lot_id') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="mb-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ ($sourceMapping['status'] ?? 'unmatched') === 'auto_matched' ? 'bg-emerald-100 text-emerald-800' : (($sourceMapping['status'] ?? 'unmatched') === 'unmatched' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-900') }}">{{ ($sourceMapping['status'] ?? 'unmatched') === 'auto_matched' ? 'Auto-matched' : (($sourceMapping['status'] ?? 'unmatched') === 'unmatched' ? 'Unmatched' : 'Needs Review') }}</span>
                                    <p class="mb-2 text-xs text-slate-500">
                                        {{ ($sourceMapping['origin'] ?? 'automatic') === 'manual' ? 'Manually selected' : (($sourceMapping['origin'] ?? 'automatic') === 'previous_import' ? 'Reused from prior import' : 'Automatic match') }}
                                        @if ($mappingUser)
                                            by {{ $mappingUser->name }}
                                        @elseif (($sourceMapping['matched_by'] ?? null) === 'system')
                                            by system
                                        @endif
                                    </p>
                                    <p class="mb-2 break-words text-xs text-slate-500">Identifiers: {{ collect($sourceMapping['matched_identifiers'] ?? [])->filter()->values()->implode(' / ') }}</p>
                                    @if (collect(data_get($sourceMapping, 'candidates.projects', []))->count() > 1 || collect(data_get($sourceMapping, 'candidates.biddings', []))->count() > 1 || collect(data_get($sourceMapping, 'candidates.lots', []))->count() > 1)
                                        <details class="mb-2 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-950">
                                            <summary class="cursor-pointer font-semibold">Review matching candidates</summary>
                                            @foreach (['projects' => 'Projects', 'biddings' => 'Bidding records', 'lots' => 'Lots'] as $candidateKey => $candidateLabel)
                                                @if (count(data_get($sourceMapping, 'candidates.'.$candidateKey, [])) > 1)
                                                    <p class="mt-2 font-semibold">{{ $candidateLabel }}</p>
                                                    <ul class="list-disc pl-4">
                                                        @foreach (data_get($sourceMapping, 'candidates.'.$candidateKey, []) as $candidate)
                                                            <li>{{ $candidate['name'] ?? 'Lot '.$candidate['number'] ?? 'Record' }} / {{ $candidate['identifier'] ?? 'Lot '.$candidate['number'] ?? 'No identifier' }} / #{{ $candidate['id'] }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            @endforeach
                                        </details>
                                    @endif
                                    <p class="mb-2 text-xs text-slate-500">Linked IDs — Project {{ data_get($sourceMapping, 'linked_record_ids.project_id') ?? '—' }} / Bidding {{ data_get($sourceMapping, 'linked_record_ids.bidding_id') ?? '—' }} / Lot {{ data_get($sourceMapping, 'linked_record_ids.lot_id') ?? '—' }} / PPL {{ data_get($sourceMapping, 'linked_record_ids.ppl_form_id') ?? '—' }}</p>
                                    @if (in_array($batch->status, ['previewed', 'failed'], true) && $row->status !== 'imported')
                                        <form method="POST" action="{{ route('finance.ppl-import.rows.mapping', [$batch, $row]) }}" class="space-y-2">@csrf @method('PATCH')
                                            <div><label for="project-{{ $row->id }}" class="mb-1 block text-xs font-semibold text-slate-600">MMC project (name / identifier)</label><select id="project-{{ $row->id }}" name="project_id" class="w-full rounded-md border-slate-300 text-xs"><option value="">No project link</option>@foreach($mappingProjects as $project)<option value="{{ $project->project_id }}" @selected((string) old("project_id", $planLinks["project_id"] ?? "") === (string) $project->project_id)>{{ $project->project_name }} / {{ $project->project_code ?? $project->ref_no ?? "No identifier" }} / #{{ $project->project_id }}</option>@endforeach</select></div>
                                            <div><label for="bidding-{{ $row->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Bidding record (name / identifier)</label><select id="bidding-{{ $row->id }}" name="project_information_id" class="w-full rounded-md border-slate-300 text-xs"><option value="">No bidding link (PPL Form can remain the source)</option>@foreach($mappingBiddings as $bidding)<option value="{{ $bidding->id }}" @selected((string) old("project_information_id", $planLinks["project_information_id"] ?? "") === (string) $bidding->id)>{{ $bidding->project_name }} / {{ $bidding->project_id_no ?? $bidding->project_id ?? $bidding->project_code ?? $bidding->reference_no ?? "No identifier" }} / #{{ $bidding->id }}</option>@endforeach</select></div>
                                            <div><label for="lot-{{ $row->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Lot (must belong to selected bidding record)</label><select id="lot-{{ $row->id }}" name="lot_id" class="w-full rounded-md border-slate-300 text-xs"><option value="">No lot link</option>@foreach($mappingLots as $lot)<option value="{{ $lot->id }}" @selected((string) old("lot_id", $planLinks["lot_id"] ?? "") === (string) $lot->id)>{{ $lot->project?->project_name ?? "Bidding record #".$lot->project_id }} / Lot {{ $lot->lot_no }} / #{{ $lot->id }}</option>@endforeach</select></div>
                                            <button type="submit" class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Save source links</button>
                                        </form>
                                    @else
                                        <p class="text-xs text-slate-600">{{ data_get($sourceMapping, 'origin', 'No mapping') }} / Project {{ data_get($sourceMapping, 'linked_record_ids.project_id', '—') }} / Bidding {{ data_get($sourceMapping, 'linked_record_ids.bidding_id', '—') }} / Lot {{ data_get($sourceMapping, 'linked_record_ids.lot_id', '—') }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if (in_array($batch->status, ['previewed', 'failed'], true))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-sm text-slate-700">Confirmation creates new MMC financial plans and first version snapshots for importable rows. It does not update or bulk-convert existing PPL Forms. The import is transactional and duplicate project/lot sources are skipped.</p>
                <form method="POST" action="{{ route('finance.ppl-import.confirm', $batch) }}" class="mt-4 space-y-4">@csrf
                    @if (($batch->summary['warnings'] ?? 0) > 0 || $batch->rows->contains(fn ($row) => $row->status === 'warning'))
                        <label class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950"><input name="acknowledge_warnings" type="checkbox" value="1" class="mt-0.5 rounded border-amber-400 text-emerald-700 focus:ring-emerald-600"><span>I reviewed the workbook-to-database differences, unmatched source links, and row warnings shown above. Continue importing eligible warning rows.</span></label>
                    @endif
                    <div class="flex flex-wrap gap-3"><button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Confirm import</button></div>
                </form>
                <form method="POST" action="{{ route('finance.ppl-import.cancel', $batch) }}" class="mt-3">@csrf<button type="submit" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel preview</button></form>
            </section>
        @endif
    </main>
</x-finance_app-layout>
