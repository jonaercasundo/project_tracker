<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectAwardEvent;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleTrack;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProjectLifecycleSourceMatcher
{
    public function autoLink(ProjectInformation $bidding, Company $company, int $actorId, ProjectLifecycleService $lifecycle): bool
    {
        if (! $this->supportsDurableLinks()
            || (int) $bidding->company_id !== (int) $company->company_id) {
            return false;
        }

        $candidates = $this->candidates($bidding, $company);
        if ($candidates->count() !== 1) {
            return false;
        }

        $project = $candidates->first()['project'];
        if ($project->project_information_id !== null || Project::query()->where('project_information_id', $bidding->getKey())->exists()) {
            return false;
        }
        if ((int) $this->biddingsForProject($project, $company)->count() !== 1) {
            return false;
        }

        return $this->link($bidding, $project, $company, $actorId, $lifecycle, 'source_link_auto');
    }

    /** @return Collection<int, ProjectInformation> */
    public function biddingsForProject(Project $project, Company $company): Collection
    {
        if (! Schema::hasColumn('project_information', 'company_id')) {
            return collect();
        }

        $tokens = array_values(array_filter(array_unique([
            (string) $project->project_id, trim((string) $project->project_code), trim((string) $project->ref_no),
        ]), fn (string $value): bool => $value !== ''));

        return ProjectInformation::query()->where('company_id', $company->company_id)
            ->where(function ($biddings) use ($tokens): void {
                foreach ($tokens as $token) {
                    $biddings->orWhere('project_id', $token)->orWhere('project_code', $token)->orWhere('reference_no', $token);
                    if (Schema::hasColumn('project_information', 'project_id_no')) {
                        $biddings->orWhere('project_id_no', $token);
                    }
                }
            })->get()->filter(fn (ProjectInformation $bidding): bool => $this->candidates($bidding, $company)
            ->contains(fn (array $candidate): bool => (int) $candidate['project']->project_id === (int) $project->project_id))->values();
    }

    public function supportsDurableLinks(): bool
    {
        return Schema::hasColumn('projects', 'project_information_id')
            && Schema::hasColumn('projects', 'company_id')
            && Schema::hasColumn('project_information', 'company_id')
            && Schema::hasTable('project_award_events')
            && collect(Schema::getIndexes('projects'))->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['project_information_id'] && ($index['unique'] ?? false))
            && collect(Schema::getForeignKeys('projects'))->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['project_information_id']
                && ($key['foreign_table'] ?? null) === 'project_information');
    }

    public function link(ProjectInformation $bidding, Project $project, Company $company, int $actorId, ProjectLifecycleService $lifecycle, string $eventType): bool
    {
        abort_unless($this->supportsDurableLinks(), 409);

        return DB::transaction(function () use ($bidding, $project, $company, $actorId, $lifecycle, $eventType): bool {
            $bidding = ProjectInformation::query()->lockForUpdate()->findOrFail($bidding->getKey());
            $project = Project::query()->lockForUpdate()->findOrFail($project->getKey());
            abort_unless((int) $bidding->company_id === (int) $company->company_id
                && (int) $project->company_id === (int) $company->company_id, 404);
            $matchedCandidate = $this->candidates($bidding, $company)
                ->first(fn (array $candidate): bool => (int) $candidate['project']->project_id === (int) $project->project_id);
            if ($matchedCandidate === null) {
                throw ValidationException::withMessages(['project_id' => 'The selected project has no exact verified identifier in this Bidding record.']);
            }
            if ($project->project_information_id !== null && (int) $project->project_information_id !== (int) $bidding->getKey()) {
                throw ValidationException::withMessages(['project_id' => 'This project is already linked to another Bidding record.']);
            }
            if (Project::query()->where('project_information_id', $bidding->getKey())->where('project_id', '!=', $project->getKey())->exists()) {
                throw ValidationException::withMessages(['bidding_id' => 'This Bidding record is already linked to another project.']);
            }
            if ((int) $project->project_information_id === (int) $bidding->getKey()) {
                return false;
            }

            $tracks = Schema::hasTable('project_lifecycle_tracks')
                ? ProjectLifecycleTrack::query()->where('company_id', $company->company_id)
                    ->where('project_information_id', $bidding->getKey())->whereNull('project_id')->with('biddingLot')->lockForUpdate()->get()
                : collect();
            foreach ($tracks as $track) {
                $newScopeKey = $lifecycle->scopeKey($project, $bidding, $track->biddingLot?->lot_no);
                if (ProjectLifecycleTrack::query()->where('scope_key', $newScopeKey)->where('id', '!=', $track->getKey())->exists()) {
                    throw ValidationException::withMessages(['project_id' => 'A Project List lifecycle record already exists for this scope. Review duplicate tracking before confirming.']);
                }
                $track->project_id = $project->project_id;
                $track->scope_key = $newScopeKey;
                $track->save();
                $lifecycle->recordEvent($track, $eventType, $actorId, 'Project List source link established from exact identifiers.', [
                    'project_id' => $project->project_id, 'bidding_id' => $bidding->getKey(),
                    'matching_identifiers' => $matchedCandidate['identifiers'],
                ]);
            }

            $project->project_information_id = $bidding->getKey();
            $project->saveQuietly();
            ProjectAwardEvent::query()->create([
                'project_information_id' => $bidding->getKey(), 'project_id' => $project->project_id,
                'event_type' => $eventType, 'actor_user_id' => $actorId, 'event_date' => today(),
                'status_before' => $project->status,
                'reason' => 'Exact source identifiers linked; no operational status changed.',
                'project_snapshot' => ['project_id' => $project->project_id, 'bidding_id' => $bidding->getKey(),
                    'project_status_before' => $project->status, 'project_status_after' => $project->status,
                    'bidding_status' => $bidding->status, 'matching_identifiers' => $matchedCandidate['identifiers']],
            ]);

            return true;
        }, 3);
    }

    /** @return Collection<int, array{project: Project, identifiers: list<string>}> */
    public function candidates(ProjectInformation $bidding, Company $company): Collection
    {
        $identifiers = array_filter([
            'project_id' => trim((string) $bidding->project_id),
            'project_code' => trim((string) $bidding->project_code),
            'reference_no' => trim((string) $bidding->reference_no),
            'project_id_no' => trim((string) $bidding->project_id_no),
        ], fn (string $value): bool => $value !== '');

        if ($identifiers === []) {
            return collect();
        }

        $query = Project::query()->where(function ($projects) use ($identifiers): void {
            foreach ($identifiers as $identifier) {
                $projects->orWhere('project_code', $identifier)->orWhere('ref_no', $identifier);
                if (ctype_digit($identifier)) {
                    $projects->orWhere('project_id', (int) $identifier);
                }
            }
        });
        if (Schema::hasColumn('projects', 'company_id')) {
            $query->where('company_id', $company->company_id);
        }

        return $query->get()->map(function (Project $project) use ($identifiers): array {
            $matches = [];
            foreach ($identifiers as $source => $identifier) {
                if ($source === 'project_id' && ctype_digit($identifier) && (int) $identifier === (int) $project->project_id) {
                    $matches[] = 'project_id';
                }
                if (in_array($source, ['project_id', 'project_code'], true) && $identifier === (string) $project->project_code) {
                    $matches[] = 'project_code';
                }
                if (in_array($source, ['reference_no', 'project_id_no'], true) && $identifier === (string) $project->ref_no) {
                    $matches[] = 'reference_no';
                }
            }

            return ['project' => $project, 'identifiers' => array_values(array_unique($matches))];
        })->filter(fn (array $candidate): bool => $candidate['identifiers'] !== [])->values();
    }
}
