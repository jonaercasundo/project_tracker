<?php

namespace App\Services;

use App\Models\BiddingDocument;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleEvent;
use App\Models\ProjectLifecycleTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProjectLifecycleService
{
    /** @return array{stage: string, outcome: ?string} */
    public function initialState(?Project $project, ?ProjectInformation $bidding): array
    {
        $mapping = $project
            ? config('project_lifecycle.project_status_mapping.'.(string) $project->status)
            : config('project_lifecycle.bidding_status_mapping.'.(string) $bidding?->status);

        return [
            'stage' => $mapping['stage'] ?? 'Entry',
            'outcome' => $mapping['outcome'] ?? null,
        ];
    }

    /** @return array{bidding_status: ?string, project_status: ?string, effective_stage: string, linked: bool, discrepancy: bool} */
    public function displayState(?Project $project, ?ProjectInformation $bidding): array
    {
        $projectStatus = $project?->status;
        $biddingStatus = $bidding?->status;
        $projectStage = $projectStatus === null ? null : config('project_lifecycle.project_status_mapping.'.$projectStatus.'.stage');
        $biddingStage = $biddingStatus === null ? null : config('project_lifecycle.bidding_status_mapping.'.$biddingStatus.'.stage');

        return [
            'bidding_status' => $biddingStatus,
            'project_status' => $projectStatus,
            'effective_stage' => $projectStage ?? $projectStatus ?? $biddingStage ?? 'Entry',
            'linked' => $project !== null,
            'discrepancy' => $projectStatus !== null && $biddingStatus !== null && $projectStage !== $biddingStage,
        ];
    }

    public function verifiedProjectForBidding(ProjectInformation $bidding): ?Project
    {
        if (Schema::hasColumn('projects', 'project_information_id')) {
            $projects = Project::query()->where('project_information_id', $bidding->getKey())->limit(2)->get();

            return $projects->count() === 1 ? $projects->first() : null;
        }

        if (! ctype_digit((string) $bidding->project_id)
            || ProjectInformation::query()->where('project_id', $bidding->project_id)->limit(2)->count() !== 1) {
            return null;
        }

        return Project::query()->find((int) $bidding->project_id);
    }

    public function scopeKey(?Project $project, ?ProjectInformation $bidding, ?string $lotNumber): string
    {
        $root = $project ? 'project:'.$project->project_id : 'bidding:'.$bidding->getKey();
        $normalizedLot = $lotNumber === null ? '' : $this->normalizeLotNumber($lotNumber);
        $lot = $normalizedLot === '' ? '' : ':lot:'.$normalizedLot;

        return $root.$lot;
    }

    public function normalizeLotNumber(string $value): string
    {
        $value = preg_replace('/^\s*lot\s*/i', '', $value) ?? $value;

        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $value) ?? '');
    }

    public function transition(ProjectLifecycleTrack $track, ?string $targetStage, ?string $targetOutcome, ?string $note, int $actorId): void
    {
        if ($targetStage === null && $targetOutcome === null) {
            throw ValidationException::withMessages(['stage' => 'Select a lifecycle stage or outcome.']);
        }
        if ($targetStage !== null && $targetOutcome !== null) {
            throw ValidationException::withMessages(['outcome' => 'Choose either a lifecycle stage or an outcome.']);
        }

        DB::transaction(function () use ($track, $targetStage, $targetOutcome, $note, $actorId): void {
            $track = ProjectLifecycleTrack::query()->lockForUpdate()->findOrFail($track->getKey());
            $fromStage = $track->current_stage;
            $fromOutcome = $track->outcome;

            if ($targetOutcome !== null) {
                if (! in_array($targetOutcome, config('project_lifecycle.outcomes', []), true)) {
                    throw ValidationException::withMessages(['outcome' => 'Select a supported lifecycle outcome.']);
                }
                $track->outcome = $targetOutcome;
                if ($targetOutcome === 'Rebid') {
                    $track->current_stage = 'Entry';
                }
            } else {
                $allowedStages = config('project_lifecycle.stages.'.$fromStage.'.next', []);
                if (! in_array($targetStage, $allowedStages, true)) {
                    throw ValidationException::withMessages(['stage' => 'The requested lifecycle transition is not allowed from the current stage.']);
                }
                if (in_array($fromOutcome, ['Lost', 'Disqualified', 'Cancelled'], true)) {
                    throw ValidationException::withMessages(['stage' => 'This outcome is closed. Start a new rebid lifecycle before progressing.']);
                }
                $track->current_stage = $targetStage;
                $track->outcome = null;
            }

            $track->save();
            ProjectLifecycleEvent::query()->create([
                'project_lifecycle_track_id' => $track->getKey(),
                'actor_user_id' => $actorId,
                'event_type' => $targetOutcome !== null ? 'outcome_changed' : 'stage_changed',
                'from_stage' => $fromStage,
                'to_stage' => $track->current_stage,
                'outcome' => $track->outcome,
                'note' => $note,
                'metadata' => ['previous_outcome' => $fromOutcome],
                'created_at' => now(),
            ]);
        });
    }

    public function recordEvent(ProjectLifecycleTrack $track, string $eventType, int $actorId, string $note, array $metadata = []): void
    {
        ProjectLifecycleEvent::query()->create([
            'project_lifecycle_track_id' => $track->getKey(),
            'actor_user_id' => $actorId,
            'event_type' => $eventType,
            'from_stage' => $track->current_stage,
            'to_stage' => $track->current_stage,
            'outcome' => $track->outcome,
            'note' => $note,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function verifyNoticeOfAward(
        ProjectLifecycleTrack $track,
        string $reference,
        int $documentId,
        int $actorId,
    ): void {
        DB::transaction(function () use ($track, $reference, $documentId, $actorId): void {
            $track = ProjectLifecycleTrack::query()->lockForUpdate()->with(['project', 'bidding'])->findOrFail($track->getKey());
            $bidding = ProjectInformation::query()->lockForUpdate()->findOrFail($track->project_information_id);
            $project = Project::query()->lockForUpdate()->findOrFail($track->project_id);
            $track->setRelation('bidding', $bidding);
            $track->setRelation('project', $project);
            if (! $bidding || $bidding->status !== 'Awarded' || ! $bidding->notice_of_award_date) {
                throw ValidationException::withMessages(['reference' => 'The Bidding record must be awarded and have an NOA date before verifying its Notice of Award.']);
            }
            $this->assertVerifiedProjectBiddingLink($track);
            if (in_array($track->outcome, ['Lost', 'Disqualified', 'Cancelled'], true)) {
                throw ValidationException::withMessages(['reference' => 'A closed lifecycle outcome cannot receive a Notice of Award.']);
            }

            $stageBefore = $track->current_stage;
            $projectStatusBefore = $track->project?->status;
            $existingEventId = $track->events()->where('event_type', 'noa_verified')->value('id');
            $event = $this->recordVerifiedEvidence($track, 'noa_verified', $reference, $documentId, $actorId, [
                'event_date' => $bidding->notice_of_award_date->toDateString(),
            ], 'Notice of Award verified.');
            if ($track->current_stage !== 'Awarded' && $this->isBeforeStage($track->current_stage, 'Awarded')) {
                $track->current_stage = 'Awarded';
                $track->save();
            }

            if ($track->project && $this->isProjectTriggerComplete($track, 'noa_verified')) {
                $this->advanceProjectStatus($track->project, 'Awarded');
            }
            if ($existingEventId === null) {
                $event->from_stage = $stageBefore;
                $event->to_stage = $track->current_stage;
                $event->metadata = [...($event->metadata ?? []), 'project_status_before' => $projectStatusBefore, 'project_status_after' => $track->project?->fresh()->status];
                $event->save();
            }
        }, 3);
    }

    public function verifyNoticeToProceed(
        ProjectLifecycleTrack $track,
        string $reference,
        string $noticeDate,
        string $effectivityDate,
        int $documentId,
        int $actorId,
    ): void {
        DB::transaction(function () use ($track, $reference, $noticeDate, $effectivityDate, $documentId, $actorId): void {
            $track = ProjectLifecycleTrack::query()->lockForUpdate()->with(['project', 'bidding'])->findOrFail($track->getKey());
            $bidding = ProjectInformation::query()->lockForUpdate()->findOrFail($track->project_information_id);
            $project = Project::query()->lockForUpdate()->findOrFail($track->project_id);
            $track->setRelation('bidding', $bidding);
            $track->setRelation('project', $project);
            if ($bidding->status !== 'Awarded') {
                throw ValidationException::withMessages(['reference' => 'A verified NOA and linked Bidding and Project List records are required before verifying an NTP.']);
            }
            $this->assertVerifiedProjectBiddingLink($track);
            if (in_array($track->outcome, ['Lost', 'Disqualified', 'Cancelled'], true)) {
                throw ValidationException::withMessages(['reference' => 'A closed lifecycle outcome cannot receive a Notice to Proceed.']);
            }
            if (! in_array($track->current_stage, ['Awarded', 'Contract Processing', 'Implementation'], true)) {
                throw ValidationException::withMessages(['reference' => 'NTP can only be verified after the Awarded stage and before delivery.']);
            }
            $hasVerifiedNoa = $track->events()->where('event_type', 'noa_verified')->exists();
            if (! $hasVerifiedNoa) {
                throw ValidationException::withMessages(['reference' => 'Verify the Notice of Award before verifying the Notice to Proceed.']);
            }

            $stageBefore = $track->current_stage;
            $projectStatusBefore = $project->status;
            $existingEventId = $track->events()->where('event_type', 'ntp_verified')->value('id');
            $event = $this->recordVerifiedEvidence($track, 'ntp_verified', $reference, $documentId, $actorId, [
                'event_date' => $noticeDate,
                'effectivity_date' => $effectivityDate,
            ], 'Notice to Proceed verified. Delivery planning is enabled. No delivery receipt was created.');

            if ($this->isBeforeStage($track->current_stage, 'Implementation')) {
                $track->current_stage = 'Implementation';
                $track->save();
            }
            if ($this->isProjectTriggerComplete($track, 'ntp_verified')) {
                $this->advanceProjectStatus($project, 'Implementation');
            }
            if ($existingEventId === null) {
                $event->from_stage = $stageBefore;
                $event->to_stage = $track->current_stage;
                $event->metadata = [...($event->metadata ?? []), 'project_status_before' => $projectStatusBefore, 'project_status_after' => $project->fresh()->status];
                $event->save();
            }
        }, 3);
    }

    /** @param array<string, string> $details */
    private function recordVerifiedEvidence(
        ProjectLifecycleTrack $track,
        string $eventType,
        string $reference,
        int $documentId,
        int $actorId,
        array $details,
        string $note,
    ): ProjectLifecycleEvent {
        $document = BiddingDocument::query()->lockForUpdate()->findOrFail($documentId);
        $existing = $track->events()->where('event_type', $eventType)->latest('id')->first();
        if ($existing) {
            if (($existing->metadata['reference'] ?? null) === $reference
                && (int) ($existing->metadata['document_id'] ?? 0) === $documentId
                && (int) ($existing->metadata['document_version'] ?? 0) === (int) $document->version
                && collect($details)->every(fn (string $value, string $key): bool => ($existing->metadata[$key] ?? null) === $value)) {
                return $existing;
            }

            throw ValidationException::withMessages(['reference' => 'Verified evidence is already recorded for this lifecycle record. Contact an administrator to correct it.']);
        }

        return $track->events()->create([
            'actor_user_id' => $actorId,
            'event_type' => $eventType,
            'from_stage' => $track->current_stage,
            'to_stage' => $track->current_stage,
            'outcome' => $track->outcome,
            'note' => $note,
            'metadata' => [
                'reference' => $reference,
                'document_id' => $documentId,
                'document_name' => $document->display_name,
                'document_version' => $document->version,
                'verified_by' => $actorId,
                'verified_at' => now()->toISOString(),
                ...$details,
            ],
            'created_at' => now(),
        ]);
    }

    private function isProjectTriggerComplete(ProjectLifecycleTrack $track, string $eventType): bool
    {
        if ($track->bidding_lot_id === null) {
            return $track->legacy_lot_id === null;
        }

        $bidding = $track->bidding;
        if (! $bidding) {
            return false;
        }
        $lots = $bidding->lots()->get(['id']);
        if ($lots->isEmpty()) {
            return false;
        }

        foreach ($lots as $lot) {
            $lotTrack = ProjectLifecycleTrack::query()
                ->where('company_id', $track->company_id)
                ->where('project_information_id', $bidding->getKey())
                ->where('bidding_lot_id', $lot->id)
                ->first();
            if (! $lotTrack || ! $lotTrack->events()->where('event_type', $eventType)->exists()) {
                return false;
            }
        }

        return true;
    }

    private function assertVerifiedProjectBiddingLink(ProjectLifecycleTrack $track): void
    {
        if (! $track->project || ! $track->bidding) {
            throw ValidationException::withMessages(['reference' => 'Verified Project List and Bidding relationships are required for official triggers.']);
        }
        if (! Schema::hasColumn('projects', 'project_information_id')) {
            throw ValidationException::withMessages(['reference' => 'The reviewed Project List to Bidding relationship schema is required before official triggers can run.']);
        }
        if ((int) $track->project->project_information_id !== (int) $track->bidding->getKey()) {
            throw ValidationException::withMessages(['reference' => 'The Project List and Bidding records no longer have a verified relationship.']);
        }
        if (Schema::hasColumn('projects', 'company_id') && Schema::hasColumn('project_information', 'company_id')
            && $track->project->company_id !== null && $track->bidding->company_id !== null
            && (int) $track->project->company_id !== (int) $track->bidding->company_id) {
            throw ValidationException::withMessages(['reference' => 'The Project List and Bidding records belong to different companies.']);
        }
    }

    private function advanceProjectStatus(Project $project, string $targetStage): void
    {
        $mapping = config('project_lifecycle.project_status_mapping.'.(string) $project->status);
        if (! $mapping || ! $this->isBeforeStage((string) ($mapping['stage'] ?? ''), $targetStage)) {
            return;
        }

        $project->status = $targetStage;
        $project->saveQuietly();
    }

    private function isBeforeStage(string $currentStage, string $targetStage): bool
    {
        $stages = array_keys(config('project_lifecycle.stages', []));
        $currentIndex = array_search($currentStage, $stages, true);
        $targetIndex = array_search($targetStage, $stages, true);

        return $currentIndex !== false && $targetIndex !== false && $currentIndex < $targetIndex;
    }

    /** @return list<string> */
    public function riskSignals(ProjectLifecycleTrack $track): array
    {
        $signals = [];
        if ($track->next_action === null || trim($track->next_action) === '') {
            $signals[] = 'No next action is recorded.';
        }
        if ($track->next_action_due_at?->isPast()) {
            $signals[] = 'The next action deadline has passed.';
        }
        if ($track->tasks()->where('status', 'open')->whereNotNull('due_at')->where('due_at', '<', now())->exists()) {
            $signals[] = 'One or more open tasks are overdue.';
        }
        if ($track->outcome === 'On Hold') {
            $signals[] = 'The lifecycle is marked On Hold.';
        }

        return $signals;
    }
}
