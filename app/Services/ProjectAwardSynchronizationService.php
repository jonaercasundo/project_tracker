<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectInformation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectAwardSynchronizationService
{
    /**
     * Synchronize approved procurement fields to the linked implementation project.
     * The caller owns the transaction and locks the bidding row before calling this method.
     */
    public function syncFromBidding(
        ProjectInformation $bidding,
        bool $createForAward,
        bool $recordAwardEvent = false,
        ?string $statusBefore = null,
        ?int $actorUserId = null,
        bool $preserveExistingData = false,
    ): ?Project {
        if ($createForAward) {
            $this->assertAwardReady($bidding);
        }

        $project = Project::query()
            ->where('project_information_id', $bidding->getKey())
            ->lockForUpdate()
            ->first();

        if ($project === null && $createForAward) {
            $project = $this->findExistingProject($bidding);
        }

        if ($project === null && ! $createForAward) {
            return null;
        }

        if ($project !== null && $project->project_information_id !== null && (int) $project->project_information_id !== (int) $bidding->getKey()) {
            throw ValidationException::withMessages([
                'reference_no' => 'This project is already linked to another bidding document.',
            ]);
        }

        if ($preserveExistingData && $project !== null && ! $project->project_information_id) {
            $this->assertExistingProjectMatchesAward($project, $bidding);
            $project->project_information_id = $bidding->getKey();
            $project->saveQuietly();
            if ($recordAwardEvent) {
                $this->recordAwardEvent($bidding, $project, $statusBefore, $actorUserId);
            }

            return $project;
        }

        $project ??= new Project;
        $project->fill([
            'project_information_id' => $bidding->getKey(),
            'ref_no' => $bidding->reference_no,
            'project_code' => $bidding->project_id,
            'project_name' => $bidding->project_name,
            'agency' => $bidding->procuring_entity,
            'contract_amount' => $bidding->contract_amount,
            'ABC' => $bidding->approved_budget_contract_abc,
            'start_date' => $bidding->start_date,
            'end_date' => $bidding->end_date,
        ]);

        if (! $project->exists) {
            $project->status = 'Pending';
        }

        $project->saveQuietly();

        if ($recordAwardEvent) {
            $this->recordAwardEvent($bidding, $project, $statusBefore, $actorUserId);
        }

        return $project;
    }

    /**
     * Copy project-owned shared fields back into its bidding record without firing model events.
     * Callers use a transaction and already hold the project row lock.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function syncFromProject(Project $project, array $attributes): void
    {
        $bidding = ProjectInformation::query()
            ->whereKey($project->project_information_id)
            ->lockForUpdate()
            ->first();

        if ($bidding === null) {
            return;
        }

        $bidding->fill([
            'reference_no' => $attributes['ref_no'] ?? $project->ref_no,
            'project_id' => $attributes['project_code'] ?? $project->project_code,
            'project_name' => $attributes['project_name'] ?? $project->project_name,
            'procuring_entity' => $attributes['agency'] ?? $project->agency,
            'contract_amount' => $attributes['contract_amount'] ?? $project->contract_amount,
            'approved_budget_contract_abc' => $attributes['ABC'] ?? $project->ABC,
            'start_date' => $attributes['start_date'] ?? $project->start_date,
            'end_date' => $attributes['end_date'] ?? $project->end_date,
        ])->saveQuietly();
    }

    public function assertAwardReady(ProjectInformation $bidding): void
    {
        $errors = [];
        if (! is_string($bidding->reference_no) || trim($bidding->reference_no) === '' || mb_strlen($bidding->reference_no) > 50) {
            $errors['reference_no'] = 'A PhilGEPS Reference Number of 50 characters or fewer is required before award.';
        }
        if (! is_string($bidding->project_id) || trim($bidding->project_id) === '') {
            $errors['project_id'] = 'A Project Number / Project ID is required before award.';
        }
        if (! is_string($bidding->project_name) || trim($bidding->project_name) === '') {
            $errors['project_name'] = 'A project name is required before award.';
        }
        if (! is_string($bidding->procuring_entity) || trim($bidding->procuring_entity) === '') {
            $errors['procuring_entity'] = 'An agency is required before award.';
        }
        if (! is_numeric($bidding->contract_amount) || (float) $bidding->contract_amount <= 0) {
            $errors['contract_amount'] = 'The awarded contract amount must be greater than zero.';
        }
        if (! is_numeric($bidding->approved_budget_contract_abc) || (float) $bidding->approved_budget_contract_abc <= 0) {
            $errors['approved_budget_contract_abc'] = 'The ABC must be greater than zero before award.';
        }
        if (! $bidding->start_date || ! $bidding->end_date || $bidding->end_date->lt($bidding->start_date)) {
            $errors['end_date'] = 'Valid project start and end dates are required before award.';
        }
        if (! $bidding->notice_of_award_date) {
            $errors['notice_of_award_date'] = 'An award date is required before award.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function findExistingProject(ProjectInformation $bidding): ?Project
    {
        $matches = Project::query()
            ->where('ref_no', $bidding->reference_no)
            ->orWhere('project_code', $bidding->project_id)
            ->orderBy('project_id')
            ->lockForUpdate()
            ->get();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'reference_no' => 'The reference number and project number match different existing projects. Link them manually before awarding.',
            ]);
        }

        return $matches->first();
    }

    private function assertExistingProjectMatchesAward(Project $project, ProjectInformation $bidding): void
    {
        $expected = [
            'ref_no' => $bidding->reference_no,
            'project_code' => $bidding->project_id,
            'project_name' => $bidding->project_name,
            'agency' => $bidding->procuring_entity,
            'contract_amount' => $bidding->contract_amount,
            'ABC' => $bidding->approved_budget_contract_abc,
            'start_date' => $bidding->start_date?->toDateString(),
            'end_date' => $bidding->end_date?->toDateString(),
        ];

        foreach ($expected as $field => $value) {
            $existing = $project->{$field};
            if (in_array($field, ['contract_amount', 'ABC'], true)) {
                $matches = is_numeric($existing) && is_numeric($value) && number_format((float) $existing, 2, '.', '') === number_format((float) $value, 2, '.', '');
            } elseif (in_array($field, ['start_date', 'end_date'], true)) {
                $matches = $existing?->format('Y-m-d') === $value;
            } else {
                $matches = $existing === $value;
            }

            if (! $matches) {
                throw ValidationException::withMessages([
                    'reference_no' => 'An existing project matches this award but has different shared information. Review and link it manually; reconciliation will not overwrite it.',
                ]);
            }
        }
    }

    private function recordAwardEvent(ProjectInformation $bidding, Project $project, ?string $statusBefore, ?int $actorUserId): void
    {
        DB::table('project_award_events')->insert([
            'project_information_id' => $bidding->getKey(),
            'project_id' => $project->getKey(),
            'event_type' => 'awarded',
            'actor_user_id' => $actorUserId,
            'event_date' => $bidding->notice_of_award_date?->toDateString(),
            'status_before' => $statusBefore,
            'project_snapshot' => json_encode(Arr::only($project->getAttributes(), [
                'project_id', 'project_information_id', 'ref_no', 'project_code', 'project_name',
                'agency', 'contract_amount', 'ABC', 'start_date', 'end_date', 'status',
            ]), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
