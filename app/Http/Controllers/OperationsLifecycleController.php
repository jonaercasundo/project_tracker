<?php

namespace App\Http\Controllers;

use App\Models\BiddingDocument;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\DeliveryBillingRecord;
use App\Models\Lot;
use App\Models\MmcPplSourceLink;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleEvent;
use App\Models\ProjectLifecycleTask;
use App\Models\ProjectLifecycleTrack;
use App\Models\ProjectLot;
use App\Models\User;
use App\Services\ProjectLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperationsLifecycleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'agency' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', Rule::in(array_keys(config('project_lifecycle.stages')))],
            'status' => ['nullable', Rule::in(array_unique(array_merge(
                array_keys(config('project_lifecycle.project_status_mapping')),
                array_keys(config('project_lifecycle.bidding_status_mapping')),
                config('project_lifecycle.outcomes'),
                ['open'],
            )))],
            'owner' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'overdue' => ['nullable', Rule::in(['1'])],
            'lot' => ['nullable', 'string', 'max:50'],
        ]);

        $tracksQuery = ProjectLifecycleTrack::query()
            ->where('company_id', $company->company_id)
            ->with(['project', 'bidding', 'legacyLot', 'biddingLot', 'owner']);
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $tracksQuery->where(function ($query) use ($search): void {
                $query->whereHas('project', fn ($projects) => $projects->where('project_name', 'like', '%'.$search.'%')
                    ->orWhere('project_code', 'like', '%'.$search.'%')->orWhere('ref_no', 'like', '%'.$search.'%'))
                    ->orWhereHas('bidding', fn ($biddings) => $biddings->where('project_name', 'like', '%'.$search.'%')
                        ->orWhere('project_id', 'like', '%'.$search.'%')->orWhere('project_code', 'like', '%'.$search.'%'))
                    ->orWhereHas('legacyLot', fn ($lots) => $lots->where('lot_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('biddingLot', fn ($lots) => $lots->where('lot_no', 'like', '%'.$search.'%'));
            });
        }
        if (! empty($filters['agency'])) {
            $agency = $filters['agency'];
            $tracksQuery->where(fn ($query) => $query->whereHas('project', fn ($projects) => $projects->where('agency', $agency))
                ->orWhereHas('bidding', fn ($biddings) => $biddings->where('procuring_entity', $agency)));
        }
        if (! empty($filters['stage'])) {
            $tracksQuery->where('current_stage', $filters['stage']);
        }
        if (array_key_exists('status', $filters) && $filters['status'] !== '') {
            $isOutcome = in_array($filters['status'], config('project_lifecycle.outcomes'), true);
            $isLegacyStatus = array_key_exists($filters['status'], config('project_lifecycle.project_status_mapping'))
                || array_key_exists($filters['status'], config('project_lifecycle.bidding_status_mapping'));
            if ($isOutcome && $isLegacyStatus) {
                $tracksQuery->where(fn ($query) => $query->where('outcome', $filters['status'])
                    ->orWhereHas('project', fn ($projects) => $projects->where('status', $filters['status']))
                    ->orWhereHas('bidding', fn ($biddings) => $biddings->where('status', $filters['status'])));
            } elseif ($isOutcome) {
                $tracksQuery->where('outcome', $filters['status']);
            } elseif ($filters['status'] === 'open') {
                $tracksQuery->whereNull('outcome');
            } else {
                $tracksQuery->where(fn ($query) => $query->whereHas('project', fn ($projects) => $projects->where('status', $filters['status']))
                    ->orWhereHas('bidding', fn ($biddings) => $biddings->where('status', $filters['status'])));
            }
        }
        if (! empty($filters['owner'])) {
            $tracksQuery->where(fn ($query) => $query->where('owner_user_id', $filters['owner'])
                ->orWhereHas('tasks', fn ($tasks) => $tasks->where('owner_user_id', $filters['owner'])));
        }
        if (! empty($filters['from'])) {
            $tracksQuery->where(fn ($query) => $query->whereDate('next_action_due_at', '>=', $filters['from'])
                ->orWhereHas('tasks', fn ($tasks) => $tasks->whereDate('due_at', '>=', $filters['from'])));
        }
        if (! empty($filters['to'])) {
            $tracksQuery->where(fn ($query) => $query->whereDate('next_action_due_at', '<=', $filters['to'])
                ->orWhereHas('tasks', fn ($tasks) => $tasks->whereDate('due_at', '<=', $filters['to'])));
        }
        if (! empty($filters['overdue'])) {
            $tracksQuery->whereNull('outcome')->where(fn ($query) => $query
                ->where(fn ($actions) => $actions->whereNotNull('next_action_due_at')->where('next_action_due_at', '<', now()))
                ->orWhereHas('tasks', fn ($tasks) => $tasks->where('status', 'open')->whereNotNull('due_at')->where('due_at', '<', now())));
        }
        if (! empty($filters['lot'])) {
            $lot = $filters['lot'];
            $tracksQuery->where(fn ($query) => $query->whereHas('legacyLot', fn ($lots) => $lots->where('lot_name', $lot))
                ->orWhereHas('biddingLot', fn ($lots) => $lots->where('lot_no', $lot)));
        }

        $tracks = $tracksQuery->latest('updated_at')->paginate(20)->withQueryString();
        $owners = User::query()->whereHas('companies', fn ($users) => $users->where('companies.company_id', $company->company_id))
            ->orderBy('name')->get(['user_id', 'name']);
        $biddingAgencyQuery = $this->scopeCompanyOwned(ProjectInformation::query(), 'project_information', $company);
        $projectAgencyQuery = $this->scopeCompanyOwned(Project::query(), 'projects', $company);
        $agencies = $biddingAgencyQuery->whereNotNull('procuring_entity')->distinct()->orderBy('procuring_entity')->pluck('procuring_entity')
            ->merge($projectAgencyQuery->whereNotNull('agency')->distinct()->orderBy('agency')->pluck('agency'))->unique()->sort()->values();
        $biddingsQuery = $this->scopeCompanyOwned(ProjectInformation::query()->with([
            'lots',
            'implementationProject' => fn ($projects) => $projects->where(fn ($query) => $this->scopeCompanyOwned($query, 'projects', $company)),
        ]), 'project_information', $company);
        if ($search !== '') {
            $biddingsQuery->where(fn ($query) => $query->where('project_name', 'like', '%'.$search.'%')
                ->orWhere('project_id', 'like', '%'.$search.'%')->orWhere('project_code', 'like', '%'.$search.'%')
                ->orWhere('procuring_entity', 'like', '%'.$search.'%')
                ->orWhereHas('lots', fn ($lots) => $lots->where('lot_no', 'like', '%'.$search.'%')));
        }
        if (! empty($filters['agency'])) {
            $biddingsQuery->where('procuring_entity', $filters['agency']);
        }
        if (! empty($filters['stage'])) {
            $stageStatuses = collect(config('project_lifecycle.bidding_status_mapping'))
                ->filter(fn (array $mapping): bool => $mapping['stage'] === $filters['stage'])->keys();
            $biddingsQuery->whereIn('status', $stageStatuses);
        }
        if (! empty($filters['status'])) {
            if (array_key_exists($filters['status'], config('project_lifecycle.bidding_status_mapping'))) {
                $biddingsQuery->where('status', $filters['status']);
            } elseif ($filters['status'] === 'open') {
                $biddingsQuery->whereNotIn('status', ['Cancelled', 'Completed']);
            } elseif (! in_array($filters['status'], config('project_lifecycle.project_status_mapping'), true)) {
                $biddingsQuery->whereRaw('1 = 0');
            }
        }
        if (! empty($filters['lot'])) {
            $biddingsQuery->whereHas('lots', fn ($lots) => $lots->where('lot_no', $filters['lot']));
        }
        if (! empty($filters['from'])) {
            $biddingsQuery->whereDate('bid_submission_deadline', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $biddingsQuery->whereDate('bid_submission_deadline', '<=', $filters['to']);
        }
        if (! empty($filters['overdue'])) {
            $biddingsQuery->whereNotNull('bid_submission_deadline')->whereDate('bid_submission_deadline', '<', today())
                ->whereNotIn('status', ['Awarded', 'Cancelled', 'Completed']);
        }
        $existingBiddings = $biddingsQuery->latest('id')->paginate(12, ['*'], 'bids_page')->withQueryString();
        $availableBiddings = $this->scopeCompanyOwned(ProjectInformation::query()->with('lots'), 'project_information', $company)->latest('id')->limit(100)->get();
        $availableProjects = $this->scopeCompanyOwned(Project::query(), 'projects', $company)->orderByDesc('project_id')->limit(100)->get();
        $availableLegacyLots = Lot::query()->with('project')->whereHas('project', fn ($projects) => $this->scopeCompanyOwned($projects, 'projects', $company))
            ->orderBy('project_id')->orderBy('lot_name')->limit(500)->get();
        $availableBiddingLots = ProjectLot::query()->with('project')->whereHas('project', fn ($biddings) => $this->scopeCompanyOwned($biddings, 'project_information', $company))
            ->orderBy('project_id')->orderBy('lot_no')->limit(500)->get();

        return view('operation.lifecycle.index', [
            'tracks' => $tracks,
            'existingBiddings' => $existingBiddings,
            'filters' => $filters,
            'owners' => $owners,
            'agencies' => $agencies,
            'stages' => array_keys(config('project_lifecycle.stages')),
            'outcomes' => config('project_lifecycle.outcomes'),
            'statuses' => array_unique(array_merge(
                array_keys(config('project_lifecycle.project_status_mapping')),
                array_keys(config('project_lifecycle.bidding_status_mapping')),
                config('project_lifecycle.outcomes'),
            )),
            'availableBiddings' => $availableBiddings,
            'availableProjects' => $availableProjects,
            'availableLegacyLots' => $availableLegacyLots,
            'availableBiddingLots' => $availableBiddingLots,
            'metrics' => $this->dashboardMetrics($company),
            'readOnly' => request()->routeIs('finance.operations.lifecycle.*'),
        ]);
    }

    public function store(Request $request, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'bidding_id' => ['nullable', 'integer', 'exists:project_information,id'],
            'legacy_lot_id' => ['nullable', 'integer', 'exists:lot,lot_id'],
            'bidding_lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'owner_user_id' => ['nullable', 'integer'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'next_action_due_at' => ['nullable', 'date'],
        ]);

        if (empty($validated['project_id']) && empty($validated['bidding_id'])) {
            throw ValidationException::withMessages(['project_id' => 'Select an existing Project List record or Bidding record.']);
        }

        $project = isset($validated['project_id']) ? Project::query()->findOrFail($validated['project_id']) : null;
        $bidding = isset($validated['bidding_id']) ? ProjectInformation::query()->findOrFail($validated['bidding_id']) : null;
        $legacyLot = isset($validated['legacy_lot_id']) ? Lot::query()->findOrFail($validated['legacy_lot_id']) : null;
        $biddingLot = isset($validated['bidding_lot_id']) ? ProjectLot::query()->findOrFail($validated['bidding_lot_id']) : null;

        if (! $project && $bidding) {
            $project = $bidding->implementationProject()->first();
        }

        if ($bidding) {
            Gate::authorize($project ? 'update' : 'view', $bidding);
        }
        $this->assertVerifiedSources($project, $bidding, $legacyLot, $biddingLot, $lifecycle);
        $this->assertCompanyMember($validated['owner_user_id'] ?? null, $company);
        $initialState = $lifecycle->initialState($project, $bidding);
        $scopeKey = $lifecycle->scopeKey($project, $bidding, $legacyLot?->lot_name ?? $biddingLot?->lot_no);

        try {
            $track = DB::transaction(function () use ($request, $company, $project, $bidding, $legacyLot, $biddingLot, $validated, $initialState, $scopeKey): ProjectLifecycleTrack {
                $existing = ProjectLifecycleTrack::query()->where('company_id', $company->company_id)->where('scope_key', $scopeKey)->first();
                if ($existing) {
                    throw ValidationException::withMessages(['project_id' => 'Lifecycle tracking already exists for this project and lot.']);
                }

                $track = ProjectLifecycleTrack::query()->create([
                    'company_id' => $company->company_id,
                    'project_id' => $project?->project_id,
                    'project_information_id' => $bidding?->getKey(),
                    'legacy_lot_id' => $legacyLot?->lot_id,
                    'bidding_lot_id' => $biddingLot?->getKey(),
                    'scope_key' => $scopeKey,
                    'current_stage' => $initialState['stage'],
                    'outcome' => $initialState['outcome'],
                    'owner_user_id' => $validated['owner_user_id'] ?? null,
                    'next_action' => $validated['next_action'] ?? null,
                    'next_action_due_at' => $validated['next_action_due_at'] ?? null,
                    'created_by' => $request->user()->user_id,
                ]);
                ProjectLifecycleEvent::query()->create([
                    'project_lifecycle_track_id' => $track->getKey(),
                    'actor_user_id' => $request->user()->user_id,
                    'event_type' => 'tracking_started',
                    'to_stage' => $initialState['stage'],
                    'outcome' => $initialState['outcome'],
                    'note' => 'Lifecycle tracking started from existing source records.',
                    'metadata' => ['scope_key' => $scopeKey],
                    'created_at' => now(),
                ]);

                return $track;
            });
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw ValidationException::withMessages(['project_id' => 'Lifecycle tracking already exists for this project and lot.']);
            }
            throw $exception;
        }

        return redirect()->route('operations.lifecycle.show', $track)->with('success', 'Lifecycle tracking started for the existing record.');
    }

    public function show(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleService $lifecycle): View
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id, 404);

        $track->load(['project', 'bidding.documents', 'legacyLot', 'biddingLot', 'owner', 'creator', 'tasks.owner', 'events.actor']);
        if ($track->project && Schema::hasColumn('projects', 'company_id') && $track->project->company_id !== null
            && (int) $track->project->company_id !== (int) $company->company_id) {
            abort(404);
        }
        if ($track->bidding && Schema::hasColumn('project_information', 'company_id') && $track->bidding->company_id !== null
            && (int) $track->bidding->company_id !== (int) $company->company_id) {
            abort(404);
        }
        $documents = $track->bidding?->documents ?? collect();
        $team = User::query()->whereHas('companies', fn ($users) => $users->where('companies.company_id', $company->company_id))
            ->orderBy('name')->get(['user_id', 'name']);
        $deliveryQuery = Schema::hasTable('deliveries') ? Delivery::query()->where('project_id', $track->project_id ?? -1) : null;
        if ($deliveryQuery && $track->legacy_lot_id) {
            $deliveryQuery->where('lot_id', $track->legacy_lot_id);
        }
        $deliveryCount = $deliveryQuery ? (clone $deliveryQuery)->count() : 0;
        $deliveredCount = $deliveryQuery ? (clone $deliveryQuery)->whereIn('status', ['delivered', 'accepted'])->count() : 0;
        $billingCounts = $deliveryQuery && Schema::hasTable('delivery_billing_records')
            ? DeliveryBillingRecord::query()->whereIn('delivery_id', (clone $deliveryQuery)->select('delivery_id'))
                ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')
            : collect();
        $pplLink = $this->verifiedPplLink($track);
        $currentStatus = $track->project?->status ?? $track->bidding?->status;
        $currentValue = $track->project?->contract_amount ?? $track->bidding?->contract_amount;
        $currentAbc = $track->project?->ABC ?? $track->bidding?->approved_budget_contract_abc;

        return view('operation.lifecycle.show', [
            'track' => $track,
            'documents' => $documents,
            'team' => $team,
            'stages' => array_keys(config('project_lifecycle.stages')),
            'nextStages' => config('project_lifecycle.stages.'.$track->current_stage.'.next', []),
            'outcomes' => config('project_lifecycle.outcomes'),
            'riskSignals' => $lifecycle->riskSignals($track),
            'deliveryCount' => $deliveryCount,
            'deliveredCount' => $deliveredCount,
            'billingCounts' => $billingCounts,
            'pplLink' => $pplLink,
            'operationalStatus' => $currentStatus,
            'contractAmount' => $currentValue,
            'abc' => $currentAbc,
            'readOnly' => request()->routeIs('finance.operations.lifecycle.*'),
        ]);
    }

    public function update(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id, 404);
        $validated = $request->validate([
            'stage' => ['nullable', Rule::in(array_keys(config('project_lifecycle.stages')))],
            'outcome' => ['nullable', Rule::in(config('project_lifecycle.outcomes'))],
            'note' => ['nullable', 'string', 'max:2000'],
            'owner_user_id' => ['nullable', 'integer'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'next_action_due_at' => ['nullable', 'date'],
        ]);
        $this->assertCompanyMember($validated['owner_user_id'] ?? null, $company);

        if (array_key_exists('stage', $validated) || array_key_exists('outcome', $validated)) {
            $lifecycle->transition($track, $validated['stage'] ?? null, $validated['outcome'] ?? null, $validated['note'] ?? null, $request->user()->user_id);
        }
        if (array_key_exists('owner_user_id', $validated) || array_key_exists('next_action', $validated) || array_key_exists('next_action_due_at', $validated)) {
            DB::transaction(function () use ($track, $validated, $request, $lifecycle): void {
                $track->fill([
                    'owner_user_id' => $validated['owner_user_id'] ?? null,
                    'next_action' => $validated['next_action'] ?? null,
                    'next_action_due_at' => $validated['next_action_due_at'] ?? null,
                ])->save();
                $lifecycle->recordEvent($track, 'assignment_or_action_updated', $request->user()->user_id, 'Owner or next action updated.');
            });
        }

        return back()->with('success', 'Lifecycle tracking updated. The underlying operational status was not changed.');
    }

    public function verifyNoa(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('verifyLifecycleEvidence', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id, 404);
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
            'document_id' => ['required', 'integer', 'exists:bidding_documents,id'],
        ]);
        $this->assertEvidenceDocumentBelongsToBidding($track, (int) $validated['document_id']);

        $lifecycle->verifyNoticeOfAward($track, $validated['reference'], (int) $validated['document_id'], $request->user()->user_id);

        return back()->with('success', 'NOA verification recorded. Project status advances only when its award scope is complete.');
    }

    public function verifyNtp(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('verifyLifecycleEvidence', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id, 404);
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
            'notice_date' => ['required', 'date'],
            'effectivity_date' => ['required', 'date', 'after_or_equal:notice_date'],
            'document_id' => ['required', 'integer', 'exists:bidding_documents,id'],
        ]);
        $this->assertEvidenceDocumentBelongsToBidding($track, (int) $validated['document_id']);

        $lifecycle->verifyNoticeToProceed(
            $track,
            $validated['reference'],
            $validated['notice_date'],
            $validated['effectivity_date'],
            (int) $validated['document_id'],
            $request->user()->user_id,
        );

        return back()->with('success', 'NTP verification recorded. Implementation status and delivery planning are enabled.');
    }

    public function storeTask(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id, 404);
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['required_document', 'milestone'])],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'owner_user_id' => ['nullable', 'integer'],
        ]);
        $this->assertCompanyMember($validated['owner_user_id'] ?? null, $company);

        DB::transaction(function () use ($request, $track, $validated, $lifecycle): void {
            $task = $track->tasks()->create([
                ...$validated,
                'status' => 'open',
                'created_by' => $request->user()->user_id,
            ]);
            $lifecycle->recordEvent($track, 'task_created', $request->user()->user_id, 'Lifecycle task created.', [
                'task_id' => $task->getKey(), 'kind' => $task->kind, 'title' => $task->title,
            ]);
        });

        return back()->with('success', 'Task added to the lifecycle record.');
    }

    public function updateTask(Request $request, ProjectLifecycleTrack $track, ProjectLifecycleTask $task, ProjectLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $company = $this->requireMmcCompany($request->user());
        abort_unless((int) $track->company_id === (int) $company->company_id && (int) $task->project_lifecycle_track_id === (int) $track->getKey(), 404);
        $validated = $request->validate(['status' => ['required', Rule::in(['open', 'complete', 'skipped'])]]);

        DB::transaction(function () use ($request, $task, $validated, $track, $lifecycle): void {
            $previousStatus = $task->status;
            $task->update(['status' => $validated['status']]);
            $lifecycle->recordEvent($track, 'task_status_changed', $request->user()->user_id, 'Task status changed.', [
                'task_id' => $task->getKey(), 'from_status' => $previousStatus, 'to_status' => $task->status,
            ]);
        });

        return back()->with('success', 'Lifecycle task updated.');
    }

    private function requireMmcCompany(User $user): Company
    {
        $company = $user->currentCompany();
        abort_unless($company && $company->code === 'MMC' && $user->belongsToCompany($company->company_id), 403);

        return $company;
    }

    private function assertCompanyMember(mixed $userId, Company $company): void
    {
        if ($userId !== null && ! User::query()->whereKey($userId)->whereHas('companies', fn ($companies) => $companies->where('companies.company_id', $company->company_id))->exists()) {
            throw ValidationException::withMessages(['owner_user_id' => 'The assigned person must belong to the active MMC company.']);
        }
    }

    private function assertEvidenceDocumentBelongsToBidding(ProjectLifecycleTrack $track, int $documentId): void
    {
        abort_unless($track->project_information_id !== null, 422, 'Link the lifecycle record to its Bidding Docs record before verifying official documents.');
        $document = BiddingDocument::query()->findOrFail($documentId);
        abort_unless((int) $document->project_information_id === (int) $track->project_information_id, 422, 'The supporting document must belong to the linked Bidding record.');
    }

    private function assertVerifiedSources(?Project $project, ?ProjectInformation $bidding, ?Lot $legacyLot, ?ProjectLot $biddingLot, ProjectLifecycleService $lifecycle): void
    {
        if (($legacyLot || $biddingLot) && ! $project && ! $bidding) {
            throw ValidationException::withMessages(['legacy_lot_id' => 'Select the existing project or bidding record that owns this lot.']);
        }
        if ($legacyLot && ! $project) {
            throw ValidationException::withMessages(['legacy_lot_id' => 'A legacy lot can only be tracked after its existing Project List parent is verified.']);
        }
        if ($project && $legacyLot && (int) $legacyLot->project_id !== (int) $project->project_id) {
            throw ValidationException::withMessages(['legacy_lot_id' => 'The selected legacy lot does not belong to this Project List record.']);
        }
        if ($bidding && $biddingLot && (int) $biddingLot->project_id !== (int) $bidding->getKey()) {
            throw ValidationException::withMessages(['bidding_lot_id' => 'The selected bidding lot does not belong to this Bidding record.']);
        }
        if ($project && $bidding) {
            if (Schema::hasColumn('projects', 'project_information_id')) {
                $linked = (int) $project->project_information_id === (int) $bidding->getKey();
            } else {
                $linked = ctype_digit((string) $bidding->project_id)
                    && (int) $bidding->project_id === (int) $project->project_id
                    && ProjectInformation::query()->where('project_id', $bidding->project_id)->count() === 1;
            }
            if (! $linked) {
                throw ValidationException::withMessages(['bidding_id' => 'The Project List and Bidding records do not have a verified relationship.']);
            }
        }
        if ($project && Schema::hasColumn('projects', 'company_id') && $project->company_id !== null
            && (int) $project->company_id !== (int) auth()->user()->currentCompany()?->company_id) {
            throw ValidationException::withMessages(['project_id' => 'The selected Project List record is owned by a different company.']);
        }
        if ($bidding && Schema::hasColumn('project_information', 'company_id') && $bidding->company_id !== null
            && (int) $bidding->company_id !== (int) auth()->user()->currentCompany()?->company_id) {
            throw ValidationException::withMessages(['bidding_id' => 'The selected Bidding record is owned by a different company.']);
        }
        if ($project && $biddingLot && ! $bidding) {
            throw ValidationException::withMessages(['bidding_lot_id' => 'Select the Bidding record that owns this lot.']);
        }
        if ($legacyLot && $biddingLot) {
            $legacyNumber = $lifecycle->normalizeLotNumber($legacyLot->lot_name);
            $biddingNumber = $lifecycle->normalizeLotNumber($biddingLot->lot_no);
            if ($legacyNumber === '' || $legacyNumber !== $biddingNumber || ! $project || ! $bidding) {
                throw ValidationException::withMessages(['bidding_lot_id' => 'The selected legacy and bidding lots do not have a verified shared project and lot number.']);
            }
        }
    }

    private function verifiedPplLink(ProjectLifecycleTrack $track): ?MmcPplSourceLink
    {
        if (! Schema::hasTable('mmc_ppl_source_links')) {
            return null;
        }
        foreach (['company_id', 'match_status', 'ppl_form_id', 'project_information_id', 'project_id', 'legacy_lot_id', 'bidding_lot_id'] as $column) {
            if (! Schema::hasColumn('mmc_ppl_source_links', $column)) {
                return null;
            }
        }
        $query = MmcPplSourceLink::query()->where('company_id', $track->company_id)->where('match_status', 'verified');
        if ($track->legacy_lot_id) {
            $query->where('legacy_lot_id', $track->legacy_lot_id);
        }
        if ($track->bidding_lot_id) {
            $query->where('bidding_lot_id', $track->bidding_lot_id);
        }
        if ($track->project_id) {
            $query->where('project_id', $track->project_id);
        }
        if ($track->project_information_id) {
            $query->where('project_information_id', $track->project_information_id);
        }

        $link = $query->with('pplForm')->first();
        if ($link?->pplForm && Schema::hasColumn('ppl_forms', 'company_id') && $link->pplForm->company_id !== null
            && (int) $link->pplForm->company_id !== (int) $track->company_id) {
            return null;
        }

        return $link;
    }

    /** @return array<string, int|float> */
    private function dashboardMetrics(Company $company): array
    {
        $activeBidStatuses = ['Draft', 'For Review', 'Published'];
        $tracks = ProjectLifecycleTrack::query()->where('company_id', $company->company_id);
        $deliveryTotal = Schema::hasTable('deliveries') ? Delivery::query()->count() : 0;
        $delivered = Schema::hasTable('deliveries') ? Delivery::query()->whereIn('status', ['delivered', 'accepted'])->count() : 0;
        $billingBase = Schema::hasTable('delivery_billing_records')
            ? DeliveryBillingRecord::query()->whereHas('delivery.project', fn ($query) => $this->scopeCompanyOwned($query, 'projects', $company))
            : null;
        $openTasks = ProjectLifecycleTask::query()->whereHas('track', fn ($query) => $query->where('company_id', $company->company_id))->where('status', 'open');

        return [
            'opportunities' => $this->scopeCompanyOwned(ProjectInformation::query(), 'project_information', $company)->count(),
            'active_bids' => $this->scopeCompanyOwned(ProjectInformation::query(), 'project_information', $company)->whereIn('status', $activeBidStatuses)->count(),
            'submitted' => $this->scopeCompanyOwned(ProjectInformation::query(), 'project_information', $company)->where('status', 'Published')->count(),
            'evaluation' => $this->scopeCompanyOwned(ProjectInformation::query(), 'project_information', $company)->where('status', 'For Review')->count()
                + $this->scopeCompanyOwned(Project::query(), 'projects', $company)->whereIn('status', ['Bid Evaluation', 'Post Qualification'])->count(),
            'awarded_projects' => $this->scopeCompanyOwned(Project::query(), 'projects', $company)->whereIn('status', ['Awarded', 'For NOA'])->count(),
            'awarded_lots' => ProjectLot::query()->whereHas('project', fn ($query) => $this->scopeCompanyOwned($query->whereIn('status', ['Awarded', 'Completed']), 'project_information', $company))->count(),
            'implementation' => $this->scopeCompanyOwned(Project::query(), 'projects', $company)->whereIn('status', ['Implementation', 'Ongoing'])->count(),
            'deliveries' => Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'project_id') ? Delivery::query()->whereHas('project', fn ($query) => $this->scopeCompanyOwned($query, 'projects', $company))->count() : $deliveryTotal,
            'delivered' => Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'project_id') && Schema::hasColumn('deliveries', 'status') ? Delivery::query()->whereHas('project', fn ($query) => $this->scopeCompanyOwned($query, 'projects', $company))->whereIn('status', ['delivered', 'accepted'])->count() : $delivered,
            'for_billing' => (int) ($billingBase?->whereIn('status', ['for_billing', 'pending_billing'])->count() ?? 0),
            'billed' => (int) ($billingBase?->where('status', 'billed')->count() ?? 0),
            'overdue' => (clone $tracks)->whereNull('outcome')->whereNotNull('next_action_due_at')->where('next_action_due_at', '<', now())->count()
                + (clone $openTasks)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'upcoming' => (clone $tracks)->whereNull('outcome')->whereBetween('next_action_due_at', [now(), now()->addDays(7)])->count()
                + (clone $openTasks)->whereBetween('due_at', [now(), now()->addDays(7)])->count(),
            'project_abc' => (float) $this->scopeCompanyOwned(Project::query(), 'projects', $company)->sum('ABC'),
            'awarded_amount' => (float) $this->scopeCompanyOwned(Project::query(), 'projects', $company)->whereIn('status', ['Awarded', 'For NOA', 'For RTA', 'For Contract Signing', 'For NTP', 'Implementation', 'Ongoing', 'PDI', 'For Billing', 'For Collection', 'Collected', 'Delivered', 'Completed'])->sum('contract_amount'),
            'tracked_records' => (clone $tracks)->count(),
        ];
    }

    private function scopeCompanyOwned(Builder|Relation $query, string $table, Company $company): Builder|Relation
    {
        if (Schema::hasColumn($table, 'company_id')) {
            $query->where(fn ($owned) => $owned->where('company_id', $company->company_id)->orWhereNull('company_id'));
        }

        return $query;
    }
}
