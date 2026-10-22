<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleEvent;
use App\Models\ProjectLifecycleTrack;
use Illuminate\Support\Facades\DB;
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
