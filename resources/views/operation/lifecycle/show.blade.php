<x-dynamic-component :component="$readOnly ? 'finance-app-layout' : 'project-app-layout'">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Operations lifecycle</p><h1 class="mt-1 max-w-3xl text-lg font-bold text-slate-900">{{ $track->project?->project_name ?? $track->bidding?->project_name ?? 'Tracked record' }}</h1><p class="mt-1 text-xs text-slate-500">{{ $track->legacyLot?->lot_name ? 'Legacy Lot '.$track->legacyLot->lot_name : ($track->biddingLot?->lot_no ? 'Bidding Lot '.$track->biddingLot->lot_no : 'Project level') }} · Tracking #{{ $track->getKey() }}</p></div>
            <a href="{{ route($readOnly ? 'finance.operations.lifecycle.index' : 'operations.lifecycle.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Back to monitoring</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        @php
            $stageIndex = array_search($displayState['effective_stage'], $stages, true);
            $progress = count($stages) > 1 ? round(($stageIndex / (count($stages) - 1)) * 100) : 0;
        @endphp
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-800">{{ $displayState['effective_stage'] }}</span>@if($track->outcome)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-950">{{ $track->outcome }}</span>@endif</div><p class="mt-2 text-xs text-slate-600">{{ $displayState['linked'] ? 'Effective stage follows Project List status.' : 'Unlinked: bidding stage is provisional.' }} Bidding status: {{ $displayState['bidding_status'] ?? 'Not linked' }}.</p>@if($displayState['discrepancy'])<p class="mt-2 rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-950">Status discrepancy: Bidding and Project List are at different stages.</p>@endif</div>
                <div class="text-right"><p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Operational status</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $operationalStatus ?? 'Not linked' }}</p></div>
            </div>
            <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $progress }}%"></div></div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-[10px] font-medium text-slate-500 sm:grid-cols-5 lg:grid-cols-10">@foreach($stages as $stage)<span class="{{ $stage === $displayState['effective_stage'] ? 'font-bold text-blue-800' : '' }}">{{ $stage }}</span>@endforeach</div>
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <h2 class="text-sm font-bold text-emerald-950">Verify Notice of Award</h2>
                <p class="mt-1 text-xs text-emerald-900">A Bidding status alone does not advance Project List. Verify the official NOA and select its archived supporting document.</p>
                @if(!$readOnly && $track->bidding?->status === 'Awarded' && $documents->isNotEmpty())
                    <form method="POST" action="{{ route('operations.lifecycle.verify-noa', $track) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-xs font-semibold text-slate-700">NOA number / reference <input name="reference" required maxlength="120" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">NOA date <input value="{{ $track->bidding->notice_of_award_date?->format('Y-m-d') }}" readonly class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-100 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Supporting archived document <select name="document_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Select NOA document</option>@foreach($documents as $document)<option value="{{ $document->id }}">{{ $document->display_name }} · v{{ $document->version }}</option>@endforeach</select></label>
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 sm:col-span-2">Verify NOA</button>
                    </form>
                @elseif($readOnly)
                    <p class="mt-3 text-xs font-medium text-emerald-900">Finance view is read only. Operations verifies NOA evidence.</p>
                @else
                    <p class="mt-3 text-xs font-medium text-amber-800">Set the Bidding record to Awarded, link it here, and archive its NOA document before verification.</p>
                @endif
            </div>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                <h2 class="text-sm font-bold text-blue-950">Verify Notice to Proceed</h2>
                <p class="mt-1 text-xs text-blue-900">A verified NTP advances the eligible record to Implementation and permits delivery planning. It does not create a delivery receipt.</p>
                @if(!$readOnly && $track->bidding?->status === 'Awarded' && $documents->isNotEmpty() && $track->events->contains(fn ($event) => $event->event_type === 'noa_verified'))
                    <form method="POST" action="{{ route('operations.lifecycle.verify-ntp', $track) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-xs font-semibold text-slate-700">NTP number / reference <input name="reference" required maxlength="120" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">NTP date <input type="date" name="notice_date" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">Effectivity date <input type="date" name="effectivity_date" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">Supporting archived document <select name="document_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Select NTP document</option>@foreach($documents as $document)<option value="{{ $document->id }}">{{ $document->display_name }} · v{{ $document->version }}</option>@endforeach</select></label>
                        <button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800 sm:col-span-2">Verify NTP</button>
                    </form>
                @elseif($readOnly)
                    <p class="mt-3 text-xs font-medium text-blue-900">Finance view is read only. Operations verifies NTP evidence.</p>
                @else
                    <p class="mt-3 text-xs font-medium text-amber-800">Verify the NOA first, then archive the NTP document before verification.</p>
                @endif
            </div>
        </section>

        @php
            $hasVerifiedNoa = $track->events->contains(fn ($event) => $event->event_type === 'noa_verified');
            $hasVerifiedNtp = $track->events->contains(fn ($event) => $event->event_type === 'ntp_verified');
        @endphp
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-sm font-bold text-amber-950">Pending requirements</h2>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-amber-900">
                @unless($hasVerifiedNoa)<li>Official NOA verification is pending.</li>@endunless
                @if($hasVerifiedNoa && !$hasVerifiedNtp)<li>Official NTP verification is pending.</li>@endif
                @if($hasVerifiedNtp)<li>Implementation is enabled. Customer acceptance for all required deliveries still needs verification before the Project List can show Delivered.</li>@endif
                @if($track->owner)<li>Responsible user: {{ $track->owner->name }}.</li>@else<li>Assign a responsible user and next action.</li>@endif
            </ul>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="space-y-4 xl:col-span-2">
                @unless($readOnly)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Update lifecycle</h2>
                    <form method="POST" action="{{ route('operations.lifecycle.update', $track) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf @method('PATCH')
                        <label class="text-xs font-semibold text-slate-700">Advance to next stage <select name="stage" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Keep current stage</option>@foreach($nextStages as $stage)<option value="{{ $stage }}">{{ $stage }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Alternative outcome <select name="outcome" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No outcome change</option>@foreach($outcomes as $outcome)<option value="{{ $outcome }}">{{ $outcome }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Reason or stage note <textarea name="note" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="Record why this stage or outcome changed."></textarea></label>
                        <div class="sm:col-span-2"><button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800">Save lifecycle change</button></div>
                    </form>
                </div>
                @else
                    <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">Finance view is read only. Operations owns bidding records and operational lifecycle updates.</p>
                @endunless

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Next action & assigned personnel</h2>
                    @unless($readOnly)<form method="POST" action="{{ route('operations.lifecycle.update', $track) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf @method('PATCH')
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Next action <input name="next_action" value="{{ $track->next_action }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">Deadline <input type="datetime-local" name="next_action_due_at" value="{{ $track->next_action_due_at?->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">Owner <select name="owner_user_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Unassigned</option>@foreach($team as $member)<option value="{{ $member->user_id }}" @selected($track->owner_user_id === $member->user_id)>{{ $member->name }}</option>@endforeach</select></label>
                        <div class="sm:col-span-2"><button class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Save action & owner</button></div>
                    </form>@else<p class="mt-3 text-sm text-slate-800">{{ $track->next_action ?: 'No action recorded' }}</p><p class="mt-1 text-xs text-slate-500">Due {{ $track->next_action_due_at?->format('M j, Y g:i A') ?? 'No deadline' }} · {{ $track->owner?->name ?? 'Unassigned' }}</p>@endunless
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-sm font-bold text-slate-900">Milestones & required documents</h2><p class="mt-1 text-xs text-slate-500">Tasks are separate from the existing Bidding document archive.</p></div></div>
                    @unless($readOnly)<form method="POST" action="{{ route('operations.lifecycle.tasks.store', $track) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                        @csrf
                        <label class="text-xs font-semibold text-slate-700">Type <select name="kind" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="milestone">Milestone</option><option value="required_document">Required document</option></select></label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Title <input name="title" required maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="Describe the item"></label>
                        <label class="text-xs font-semibold text-slate-700">Due date <input type="date" name="due_at" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-700">Assigned to <select name="owner_user_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Unassigned</option>@foreach($team as $member)<option value="{{ $member->user_id }}">{{ $member->name }}</option>@endforeach</select></label>
                        <div class="flex items-end sm:col-span-3"><button class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Add task</button></div>
                    </form>@endunless
                    <div class="mt-5 space-y-2">
                        @forelse($track->tasks as $task)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 p-3">
                                <div><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">{{ str_replace('_', ' ', $task->kind) }}</span><p class="mt-2 text-sm font-medium text-slate-900">{{ $task->title }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $task->owner?->name ?? 'Unassigned' }} · {{ $task->due_at?->format('M j, Y') ?? 'No due date' }} · {{ ucfirst($task->status) }}</p></div>
                                @unless($readOnly)<form method="POST" action="{{ route('operations.lifecycle.tasks.update', [$track, $task]) }}" class="flex items-center gap-2">@csrf @method('PATCH')<select name="status" aria-label="Task status" class="rounded-lg border-slate-300 text-xs"><option value="open" @selected($task->status === 'open')>Open</option><option value="complete" @selected($task->status === 'complete')>Complete</option><option value="skipped" @selected($task->status === 'skipped')>Skipped</option></select><button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Update</button></form>@endunless
                            </div>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-xs text-slate-500">No milestones or document tasks recorded.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Stage history</h2>
                    <ol class="mt-4 space-y-4 border-l-2 border-slate-100 pl-4">
                        @forelse($track->events as $event)
                            <li class="relative"><span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-blue-600 ring-4 ring-white"></span><p class="text-xs font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($event->event_type)) }}@if($event->to_stage) · {{ $event->to_stage }}@endif @if($event->outcome) · {{ $event->outcome }}@endif</p><p class="mt-1 text-[11px] text-slate-500">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at?->format('M j, Y g:i A') }}</p>@if($event->note)<p class="mt-1 text-xs text-slate-700">{{ $event->note }}</p>@endif @if(in_array($event->event_type, ['noa_verified', 'ntp_verified'], true))<p class="mt-1 text-[11px] text-slate-600">Reference {{ $event->metadata['reference'] ?? '—' }} · Document #{{ $event->metadata['document_id'] ?? '—' }} v{{ $event->metadata['document_version'] ?? '—' }} · {{ $event->metadata['event_date'] ?? $event->metadata['notice_date'] ?? '—' }}@if($event->metadata['effectivity_date'] ?? null) · Effective {{ $event->metadata['effectivity_date'] }}@endif</p>@endif</li>
                        @empty
                            <li class="text-xs text-slate-500">No history has been recorded.</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            <aside class="space-y-4">
                <section class="rounded-2xl border {{ $riskSignals ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
                    <h2 class="text-sm font-bold {{ $riskSignals ? 'text-amber-950' : 'text-emerald-950' }}">Data-backed risk signals</h2>
                    <p class="mt-1 text-[11px] {{ $riskSignals ? 'text-amber-900' : 'text-emerald-900' }}">Rule-based from recorded actions and deadlines; not represented as verified AI analysis.</p>
                    @if($riskSignals)<ul class="mt-3 list-inside list-disc space-y-1 text-xs text-amber-950">@foreach($riskSignals as $signal)<li>{{ $signal }}</li>@endforeach</ul>@else<p class="mt-3 text-xs text-emerald-900">No exception was detected from the dates and open tasks currently recorded.</p>@endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Linked source records</h2>
                    <dl class="mt-3 space-y-3 text-xs">
                        <div><dt class="text-slate-500">Project List</dt><dd class="mt-1 font-semibold text-slate-900">@if($track->project)@if($readOnly)#{{ $track->project->project_id }} · {{ $track->project->project_name }}@else<a class="text-blue-700 hover:underline" href="{{ route('projects.show', $track->project) }}">#{{ $track->project->project_id }} · {{ $track->project->project_name }}</a>@endif @else Not linked @endif</dd></div>
                        <div><dt class="text-slate-500">Bidding</dt><dd class="mt-1 font-semibold text-slate-900">@if($track->bidding)@if($readOnly)#{{ $track->bidding->id }} · {{ $track->bidding->project_code }}@else<a class="text-blue-700 hover:underline" href="{{ route('project.bidding.show', $track->bidding) }}">#{{ $track->bidding->id }} · {{ $track->bidding->project_code }}</a>@endif @else Not linked @endif</dd></div>
                        <div><dt class="text-slate-500">Project List lot / Bidding lot</dt><dd class="mt-1 font-semibold text-slate-900">{{ $track->legacyLot?->lot_name ? 'Legacy #'.$track->legacyLot->lot_id.' · Lot '.$track->legacyLot->lot_name : '—' }} / {{ $track->biddingLot?->lot_no ? 'Bidding #'.$track->biddingLot->id.' · Lot '.$track->biddingLot->lot_no : '—' }}</dd></div>
                        <div><dt class="text-slate-500">Project List operational status</dt><dd class="mt-1 font-semibold text-slate-900">{{ $track->project?->status ?? 'No Project List status' }}</dd></div>
                        @if($track->bidding)<div><dt class="text-slate-500">Bidding status</dt><dd class="mt-1 font-semibold text-slate-900">{{ $track->bidding->status }}</dd></div>@endif
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Financial summary</h2>
                    <dl class="mt-3 space-y-3 text-xs">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Project ABC</dt><dd class="text-right font-semibold tabular-nums text-slate-900">{{ $track->project?->ABC !== null ? '₱'.number_format((float) $track->project->ABC, 2) : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Bidding ABC</dt><dd class="text-right font-semibold tabular-nums text-slate-900">{{ $track->bidding?->approved_budget_contract_abc !== null ? '₱'.number_format((float) $track->bidding->approved_budget_contract_abc, 2) : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Contract amount</dt><dd class="text-right font-semibold tabular-nums text-slate-900">{{ $contractAmount !== null ? '₱'.number_format((float) $contractAmount, 2) : '—' }}</dd></div>
                        @if($pplLink?->pplForm)<div><dt class="text-slate-500">Verified MMC PPL source link</dt><dd class="mt-1 font-semibold text-slate-900">PPL Form #{{ $pplLink->ppl_form_id }} · Lot {{ $pplLink->pplForm->lot_number }}</dd><dd class="mt-1 text-[11px] text-slate-500">Financial values and plans remain in the Finance workspace.</dd></div>@else<div><dt class="text-slate-500">MMC PPL link</dt><dd class="mt-1 text-slate-700">No verified project-and-lot source link is available.</dd></div>@endif
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Delivery & billing progress</h2>
                    <p class="mt-3 text-xs text-slate-600">DR rows reported delivered or accepted: <strong class="text-slate-900">{{ $deliveredCount }} / {{ $deliveryCount }}</strong></p>
                    @if($billingCounts->isNotEmpty())<dl class="mt-3 space-y-2 text-xs">@foreach($billingCounts as $status => $count)<div class="flex justify-between gap-2"><dt class="text-slate-500">{{ str_replace('_', ' ', ucfirst($status)) }}</dt><dd class="font-semibold text-slate-900">{{ $count }}</dd></div>@endforeach</dl>@else<p class="mt-2 text-xs text-slate-500">No delivery billing records are linked yet.</p>@endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Existing bidding documents</h2>
                    @if($documents->isNotEmpty())<ul class="mt-3 space-y-2">@foreach($documents as $document)<li class="text-xs"><a class="font-medium text-blue-700 hover:underline" href="{{ route('project.bidding.documents.download', [$track->bidding, $document]) }}">{{ $document->display_name }}</a><span class="ml-1 text-slate-500">v{{ $document->version }}</span></li>@endforeach</ul>@else<p class="mt-2 text-xs text-slate-500">No existing Bidding documents are linked to this record.</p>@endif
                </section>
            </aside>
        </section>
    </div>
</x-dynamic-component>
