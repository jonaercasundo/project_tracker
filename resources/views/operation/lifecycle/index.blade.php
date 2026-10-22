<x-dynamic-component :component="$readOnly ? 'finance-app-layout' : 'project-app-layout'">
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Operations Bidding & Project Lifecycle</h1>
            <p class="mt-1 text-xs text-slate-500">Monitor existing opportunities, project delivery, and billing without changing Project List status.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <p class="font-semibold">Please correct the lifecycle form.</p>
                <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section aria-label="Lifecycle overview" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @php
                $cards = [
                ['Bidding opportunities', $metrics['opportunities'], 'text-slate-900'],
                ['Active bids', $metrics['active_bids'], 'text-blue-700'],
                ['Submitted', $metrics['submitted'], 'text-indigo-700'],
                ['Under evaluation', $metrics['evaluation'], 'text-amber-700'],
                ['Awarded projects', $metrics['awarded_projects'], 'text-emerald-700'],
                ['Awarded bidding lots', $metrics['awarded_lots'], 'text-emerald-700'],
                ['Implementation', $metrics['implementation'], 'text-cyan-700'],
                ['Deliveries accepted', $metrics['delivered'].' / '.$metrics['deliveries'], 'text-cyan-700'],
                ['For billing / billed', $metrics['for_billing'].' / '.$metrics['billed'], 'text-violet-700'],
                ['Overdue actions', $metrics['overdue'], $metrics['overdue'] > 0 ? 'text-rose-700' : 'text-emerald-700'],
                ];
            @endphp
            @foreach ($cards as [$label, $value, $color])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Project values</h2>
                        <p class="mt-1 text-xs text-slate-500">Totals are read from existing Project List records.</p>
                    </div>
                    @unless($readOnly)<a href="{{ route('projects.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Open Project List</a>@endunless
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Project ABC total</p>
                        <p class="mt-1 text-lg font-bold tabular-nums text-slate-900">₱{{ number_format($metrics['project_abc'], 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4">
                        <p class="text-xs text-emerald-800">Awarded contract total</p>
                        <p class="mt-1 text-lg font-bold tabular-nums text-emerald-900">₱{{ number_format($metrics['awarded_amount'], 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-sm font-bold text-amber-950">Data-backed signals</h2>
                <p class="mt-2 text-xs leading-5 text-amber-900">These are rule-based indicators from recorded due dates and open tasks. They are not AI-generated risk findings.</p>
                <p class="mt-3 text-sm font-semibold text-amber-950">{{ $metrics['upcoming'] }} upcoming actions in the next 7 days</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Lifecycle monitoring</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $metrics['tracked_records'] }} explicitly tracked project or lot records.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-800">Project List status stays authoritative</span>
                </div>
                <form method="GET" action="{{ route($readOnly ? 'finance.operations.lifecycle.index' : 'operations.lifecycle.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search project, code, or lot" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="agency" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All agencies</option>@foreach ($agencies as $agency)<option value="{{ $agency }}" @selected(($filters['agency'] ?? '') === $agency)>{{ $agency }}</option>@endforeach</select>
                    <select name="stage" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All lifecycle stages</option>@foreach ($stages as $stage)<option value="{{ $stage }}" @selected(($filters['stage'] ?? '') === $stage)>{{ $stage }}</option>@endforeach</select>
                    <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All statuses</option><option value="open" @selected(($filters['status'] ?? '') === 'open')>Open lifecycle</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
                    <select name="owner" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All owners</option>@foreach ($owners as $owner)<option value="{{ $owner->user_id }}" @selected((string) ($filters['owner'] ?? '') === (string) $owner->user_id)>{{ $owner->name }}</option>@endforeach</select>
                    <input name="lot" value="{{ $filters['lot'] ?? '' }}" placeholder="Lot number" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="Due date from" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <div class="flex gap-2"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="Due date to" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"><label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 text-xs text-slate-700"><input type="checkbox" name="overdue" value="1" @checked(isset($filters['overdue'])) class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">Overdue</label></div>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-4"><button class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Apply filters</button><a href="{{ route($readOnly ? 'finance.operations.lifecycle.index' : 'operations.lifecycle.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Clear</a></div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Project / Lot</th><th class="px-4 py-3">Agency</th><th class="px-4 py-3">Lifecycle</th><th class="px-4 py-3">Project List status</th><th class="px-4 py-3">Next action / deadline</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Values</th><th class="px-4 py-3">Open</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tracks as $track)
                            @php
                                $title = $track->project?->project_name ?? $track->bidding?->project_name ?? 'Existing record';
                                $lotName = $track->legacyLot?->lot_name ?? $track->biddingLot?->lot_no;
                                $current = $track->project?->status ?? $track->bidding?->status;
                                $stageIndex = array_search($track->current_stage, $stages, true);
                                $stagePercent = count($stages) > 1 ? round(($stageIndex / (count($stages) - 1)) * 100) : 0;
                                $isOverdue = $track->outcome === null && $track->next_action_due_at?->isPast();
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="max-w-[280px] px-4 py-4"><a href="{{ route($readOnly ? 'finance.operations.lifecycle.show' : 'operations.lifecycle.show', $track) }}" class="line-clamp-2 font-semibold text-slate-900 hover:text-blue-700" title="{{ $title }}">{{ $title }}</a><p class="mt-1 text-[10px] text-slate-500">{{ $lotName ? 'Lot '.$lotName : 'Project level' }} · {{ $track->bidding?->project_code ?? $track->project?->project_code ?? $track->project?->ref_no }}</p></td>
                                <td class="px-4 py-4 text-slate-600">{{ $track->project?->agency ?? $track->bidding?->procuring_entity ?? '—' }}</td>
                                <td class="min-w-40 px-4 py-4"><div class="flex items-center gap-2"><span class="rounded-full bg-blue-50 px-2 py-1 font-semibold text-blue-800">{{ $track->current_stage }}</span>@if($track->outcome)<span class="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-900">{{ $track->outcome }}</span>@endif</div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $stagePercent }}%"></div></div><span class="sr-only">{{ $stagePercent }} percent through the configured lifecycle</span></td>
                                <td class="px-4 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">{{ $current ?? '—' }}</span></td>
                                <td class="max-w-48 px-4 py-4"><p class="line-clamp-2 text-slate-800">{{ $track->next_action ?: 'No action recorded' }}</p><p class="mt-1 font-medium {{ $isOverdue ? 'text-rose-700' : 'text-slate-500' }}">{{ $track->next_action_due_at?->format('M j, Y') ?? 'No deadline' }}{{ $isOverdue ? ' · Overdue' : '' }}</p></td>
                                <td class="px-4 py-4 text-slate-600">{{ $track->owner?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums text-slate-700">{{ $track->project?->contract_amount ? '₱'.number_format((float) $track->project->contract_amount, 2) : '—' }}</td>
                                <td class="px-4 py-4"><a href="{{ route($readOnly ? 'finance.operations.lifecycle.show' : 'operations.lifecycle.show', $track) }}" class="font-semibold text-blue-700 hover:underline">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center"><p class="font-semibold text-slate-800">No lifecycle records match these filters.</p><p class="mt-1 text-xs text-slate-500">Start tracking an existing Bidding or Project List record below. This will not create or change a project master.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $tracks->links() }}</div>
        </section>

        @unless($readOnly)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-sm font-bold text-slate-900">Start lifecycle tracking</h2>
                <p class="mt-1 text-xs text-slate-500">Select existing records only. Project and bidding links, and any paired lot numbers, are validated before a lifecycle record is added.</p>
            </div>
            <form method="POST" action="{{ route('operations.lifecycle.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                <label class="text-xs font-semibold text-slate-700">Project List record <select name="project_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No Project List record</option>@foreach ($availableProjects as $project)<option value="{{ $project->project_id }}">#{{ $project->project_id }} · {{ \Illuminate\Support\Str::limit($project->project_name, 60) }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-slate-700">Bidding record <select name="bidding_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No Bidding record</option>@foreach ($availableBiddings as $bidding)<option value="{{ $bidding->id }}">#{{ $bidding->id }} · {{ \Illuminate\Support\Str::limit($bidding->project_name, 60) }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-slate-700">Legacy Project List lot <select name="legacy_lot_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No legacy lot</option>@foreach ($availableLegacyLots as $lot)<option value="{{ $lot->lot_id }}">Project #{{ $lot->project_id }} · Lot {{ $lot->lot_name }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-slate-700">Bidding lot <select name="bidding_lot_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No bidding lot</option>@foreach ($availableBiddingLots as $lot)<option value="{{ $lot->id }}">Bidding #{{ $lot->project_id }} · Lot {{ $lot->lot_no }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-slate-700">Assigned owner <select name="owner_user_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Unassigned</option>@foreach ($owners as $owner)<option value="{{ $owner->user_id }}">{{ $owner->name }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold text-slate-700 md:col-span-2">Next action <input name="next_action" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="What needs to happen next?"></label>
                <label class="text-xs font-semibold text-slate-700">Next action deadline <input type="datetime-local" name="next_action_due_at" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <div class="md:col-span-2 xl:col-span-4"><button class="rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">Start tracking existing records</button></div>
            </form>
        </section>
        @else
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">Finance view is read only. Operations owns bidding records and operational lifecycle updates.</p>
        @endunless

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-sm font-bold text-slate-900">Existing bidding opportunities</h2>
                <p class="mt-1 text-xs text-slate-500">Legacy status is mapped for display only. The Operations Form and Project List remain unchanged.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[1000px] w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Opportunity</th><th class="px-4 py-3">Agency</th><th class="px-4 py-3">Lots</th><th class="px-4 py-3">Bidding status</th><th class="px-4 py-3">Mapped stage</th><th class="px-4 py-3">Project List status</th><th class="px-4 py-3 text-right">ABC / contract</th><th class="px-4 py-3">Submission deadline</th><th class="px-4 py-3">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($existingBiddings as $bidding)
                            @php
                                $mapping = config('project_lifecycle.bidding_status_mapping.'.$bidding->status, ['stage' => 'Entry']);
                                $deadlineOverdue = $bidding->bid_submission_deadline && \Illuminate\Support\Carbon::parse($bidding->bid_submission_deadline)->isPast() && !in_array($bidding->status, ['Awarded', 'Cancelled', 'Completed'], true);
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="max-w-[300px] px-4 py-4">@unless($readOnly)<a href="{{ route('project.bidding.show', $bidding) }}" class="line-clamp-2 font-semibold text-slate-900 hover:text-blue-700" title="{{ $bidding->project_name }}">{{ $bidding->project_name }}</a>@else<p class="line-clamp-2 font-semibold text-slate-900" title="{{ $bidding->project_name }}">{{ $bidding->project_name }}</p>@endunless<p class="mt-1 text-[10px] text-slate-500">Bidding #{{ $bidding->id }} · {{ $bidding->project_code ?: $bidding->project_id }}</p></td>
                                <td class="px-4 py-4 text-slate-600">{{ $bidding->procuring_entity ?: '—' }}</td>
                                <td class="px-4 py-4 text-slate-700">@if($bidding->lots->isNotEmpty()){{ $bidding->lots->pluck('lot_no')->join(', ') }}@else{{ $bidding->lot_no ?: '—' }}@endif</td>
                                <td class="px-4 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 font-medium text-slate-700">{{ $bidding->status }}</span></td>
                                <td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-2 py-1 font-semibold text-blue-800">{{ $mapping['stage'] }}</span></td>
                                <td class="px-4 py-4 text-slate-700">{{ $bidding->implementationProject?->status ?? 'Not linked' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums text-slate-700"><div>₱{{ number_format((float) $bidding->approved_budget_contract_abc, 2) }}</div><div class="mt-1 text-[10px] text-slate-500">Contract ₱{{ number_format((float) $bidding->contract_amount, 2) }}</div></td>
                                <td class="px-4 py-4 font-medium {{ $deadlineOverdue ? 'text-rose-700' : 'text-slate-600' }}">{{ $bidding->bid_submission_deadline ? \Illuminate\Support\Carbon::parse($bidding->bid_submission_deadline)->format('M j, Y') : 'No deadline' }}{{ $deadlineOverdue ? ' · Overdue' : '' }}</td>
                                <td class="px-4 py-4">@unless($readOnly)<div class="flex items-center gap-2"><a href="{{ route('project.bidding.show', $bidding) }}" class="font-semibold text-blue-700 hover:underline">View</a><form method="POST" action="{{ route('operations.lifecycle.store') }}">@csrf<input type="hidden" name="bidding_id" value="{{ $bidding->id }}"><button class="rounded-md border border-slate-200 px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">Track</button></form></div>@else<span class="text-slate-500">Read only</span>@endunless</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-8 text-center text-xs text-slate-500">No existing Bidding records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $existingBiddings->links() }}</div>
        </section>
    </div>
</x-dynamic-component>
