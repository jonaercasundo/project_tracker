<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lot as LegacyLot;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MmcPplSourceLinkService
{
    /** @return array{rows: list<array<string, mixed>>, summary: array<string, int>} */
    public function dryRunHistoricalLinks(): array
    {
        $company = Company::query()->where('code', 'MMC')->firstOrFail();
        $pplFormsQuery = PplForm::query()->orderBy('id');
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $pplFormsQuery->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $company->company_id));
        }
        $pplForms = $pplFormsQuery->get();
        $projects = Project::query()->get();
        $biddings = ProjectInformation::query()->get();
        $biddingLots = Schema::hasTable('lots') ? ProjectLot::query()->get() : collect();
        $legacyLots = Schema::hasTable('lot') ? LegacyLot::query()->get() : collect();
        $sourceLinks = Schema::hasTable('mmc_ppl_source_links')
            ? MmcPplSourceLink::query()->get()->keyBy('ppl_form_id')
            : collect();

        $rows = $pplForms->map(function (PplForm $pplForm) use ($company, $projects, $biddings, $biddingLots, $legacyLots, $sourceLinks): array {
            $resolved = $this->resolveHistoricalMatch($pplForm, $projects, $biddings, $biddingLots, $legacyLots);
            $ownerConflict = Schema::hasColumn('ppl_forms', 'company_id')
                && $pplForm->company_id !== null && (int) $pplForm->company_id !== (int) $company->company_id;
            foreach ([$resolved['project'], $resolved['bidding']] as $source) {
                if ($source !== null && Schema::hasColumn($source->getTable(), 'company_id')
                    && $source->company_id !== null && (int) $source->company_id !== (int) $company->company_id) {
                    $ownerConflict = true;
                }
            }

            $status = $ownerConflict ? 'ownership_conflict' : $resolved['status'];
            $existingLink = $sourceLinks->get($pplForm->getKey());
            if (! $ownerConflict && $existingLink?->match_origin === 'manual' && $existingLink->match_status === 'verified') {
                $status = 'already_manually_verified';
            } elseif (! $ownerConflict && $existingLink?->match_status === 'verified') {
                $status = 'already_linked';
            }

            return [
                'ppl_form_id' => $pplForm->getKey(),
                'project_code' => $pplForm->project_code,
                'lot_number' => $pplForm->lot_number,
                'project_identifier' => $pplForm->project_id_no,
                'status' => $status,
                'project_id' => $resolved['project']?->getKey(),
                'bidding_id' => $resolved['bidding']?->getKey(),
                'candidate_bidding_ids' => $resolved['candidate_bidding_ids'] ?? [],
                'candidate_biddings' => $resolved['candidate_biddings'] ?? [],
                'bidding_lot_id' => $resolved['bidding_lot']?->getKey(),
                'legacy_lot_id' => $resolved['legacy_lot']?->getKey(),
                'reasons' => $resolved['reasons'],
            ];
        })->values()->all();

        return ['rows' => $rows, 'summary' => collect($rows)->countBy('status')->all()];
    }

    /** @return array{linked: int, skipped: int, rows: list<array<string, mixed>>} */
    public function linkVerifiedHistoricalSources(User $user): array
    {
        if (! Schema::hasTable('mmc_ppl_source_links') || ! Schema::hasColumn('ppl_forms', 'company_id')
            || ! Schema::hasColumn('project_information', 'company_id') || ! Schema::hasColumn('projects', 'company_id')) {
            throw ValidationException::withMessages(['schema' => 'Apply the reviewed MMC PPL source-link migration before applying historical links.']);
        }

        $company = Company::query()->where('code', 'MMC')->firstOrFail();
        $this->assertMmcFinanceUser($user, (int) $company->company_id);

        $dryRun = $this->dryRunHistoricalLinks();
        $linked = 0;
        $skipped = 0;
        $rows = [];

        foreach ($dryRun['rows'] as $match) {
            if ($match['status'] !== 'auto_matched') {
                $skipped++;
                $rows[] = [...$match, 'action' => 'left_unchanged'];

                continue;
            }

            DB::transaction(function () use ($match, $user, &$linked, &$rows): void {
                $pplForm = PplForm::query()->whereKey($match['ppl_form_id'])->lockForUpdate()->firstOrFail();
                $existingLink = MmcPplSourceLink::query()->where('ppl_form_id', $pplForm->getKey())->lockForUpdate()->first();
                if ($existingLink?->match_origin === 'manual') {
                    $rows[] = [...$match, 'action' => 'preserved_manual_link'];

                    return;
                }

                $company = Company::query()->where('code', 'MMC')->firstOrFail();
                $companyId = (int) $company->company_id;
                $project = $match['project_id'] === null ? null : Project::query()->whereKey($match['project_id'])->lockForUpdate()->firstOrFail();
                $bidding = $match['bidding_id'] === null ? null : ProjectInformation::query()->whereKey($match['bidding_id'])->lockForUpdate()->firstOrFail();
                foreach ([$pplForm, $project, $bidding] as $source) {
                    if ($source !== null && $source->company_id !== null && (int) $source->company_id !== $companyId) {
                        $rows[] = [...$match, 'action' => 'ownership_conflict'];

                        return;
                    }
                }

                if ($project !== null && $bidding !== null) {
                    $this->persistExplicitProjectBiddingLink($project, $bidding);
                }

                $pplForm->forceFill(['company_id' => $companyId])->save();
                $project?->forceFill(['company_id' => $companyId])->save();
                $bidding?->forceFill(['company_id' => $companyId])->save();
                $link = MmcPplSourceLink::query()->updateOrCreate(
                    ['ppl_form_id' => $pplForm->getKey()],
                    [
                        'company_id' => $companyId,
                        'project_id' => $match['project_id'],
                        'project_information_id' => $match['bidding_id'],
                        'bidding_lot_id' => $match['bidding_lot_id'],
                        'legacy_lot_id' => $match['legacy_lot_id'],
                        'match_status' => 'verified',
                        'match_origin' => 'automatic',
                        'matching_identifiers' => [
                            'project_code' => $pplForm->project_code,
                            'project_title' => $pplForm->project_title,
                            'project_identifier' => $pplForm->project_id_no,
                            'lot_number' => $pplForm->lot_number,
                            'dry_run_reasons' => $match['reasons'],
                        ],
                        'confirmed_by' => $user->getAuthIdentifier(),
                        'confirmed_at' => now(),
                    ],
                );
                ProjectFinancialPlan::query()->where('company_id', $companyId)
                    ->where('ppl_form_id', $pplForm->getKey())
                    ->update(['mmc_ppl_source_link_id' => $link->getKey()]);
                $linked++;
                $rows[] = [...$match, 'action' => 'linked'];
            }, 3);
        }

        return ['linked' => $linked, 'skipped' => $skipped, 'rows' => $rows];
    }

    /** @param array<string, mixed> $sourceIds */
    public function recordManualPplLink(PplForm $pplForm, array $sourceIds, User $user, int $companyId): ?MmcPplSourceLink
    {
        if (! Schema::hasTable('mmc_ppl_source_links') || ! Schema::hasColumn('ppl_forms', 'company_id')) {
            return null;
        }
        $companyCode = Company::query()->whereKey($companyId)->value('code');
        if ($companyCode !== 'MMC') {
            throw ValidationException::withMessages(['source' => 'Only MMC Finance can link MMC PPL sources.']);
        }
        $this->assertMmcFinanceUser($user, $companyId);

        $biddingId = $sourceIds['project_information_id'] ?? null;
        $projectId = $sourceIds['project_id'] ?? null;
        $biddingLotId = $sourceIds['bidding_lot_id'] ?? null;
        $legacyLotId = $sourceIds['legacy_lot_id'] ?? null;
        if ($biddingId === null && $projectId === null && $biddingLotId === null && $legacyLotId === null) {
            return null;
        }

        return DB::transaction(function () use ($pplForm, $user, $companyId, $biddingId, $projectId, $biddingLotId, $legacyLotId): MmcPplSourceLink {
            $biddingQuery = ProjectInformation::query()->whereKey($biddingId);
            if ($biddingId !== null && Schema::hasColumn('project_information', 'company_id')) {
                $biddingQuery->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId));
            }
            $bidding = $biddingId === null ? null : $biddingQuery->firstOrFail();
            $projectQuery = Project::query()->whereKey($projectId);
            if ($projectId !== null && Schema::hasColumn('projects', 'company_id')) {
                $projectQuery->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId));
            }
            $project = $projectId === null ? null : $projectQuery->firstOrFail();
            $biddingLot = $biddingLotId === null ? null : ProjectLot::query()->whereKey($biddingLotId)->firstOrFail();
            $legacyLot = $legacyLotId === null ? null : LegacyLot::query()->whereKey($legacyLotId)->firstOrFail();

            if ($biddingLot !== null && ($bidding === null || (int) $biddingLot->project_id !== (int) $bidding->getKey())) {
                throw ValidationException::withMessages(['bidding_lot_id' => 'The selected bidding lot does not belong to the selected bidding record.']);
            }
            if ($legacyLot !== null && ($project === null || (int) $legacyLot->project_id !== (int) $project->getKey())) {
                throw ValidationException::withMessages(['legacy_lot_id' => 'The selected Project List lot does not belong to the selected project.']);
            }
            if ($project !== null && $bidding !== null && ! $this->projectsAreRelated($project, $bidding)) {
                throw ValidationException::withMessages(['project_information_id' => 'The selected project and bidding record do not have a verified relationship.']);
            }
            if ($project === null && $bidding !== null) {
                $relatedProjects = Project::query()->get()->filter(fn (Project $candidate): bool => $this->projectsAreRelated($candidate, $bidding));
                if ($relatedProjects->count() === 1) {
                    $project = $relatedProjects->first();
                }
            }
            if ($pplForm->company_id !== null && (int) $pplForm->company_id !== $companyId) {
                throw ValidationException::withMessages(['ppl_form' => 'This PPL Form belongs to a different company.']);
            }
            if ($biddingLot !== null && $legacyLot !== null && $this->normalizeLot($biddingLot->lot_no) !== $this->normalizeLot($legacyLot->lot_name)) {
                throw ValidationException::withMessages(['legacy_lot_id' => 'The selected bidding and Project List lots have different lot numbers.']);
            }
            $selectedLot = $biddingLot?->lot_no ?? $legacyLot?->lot_name;
            if ($selectedLot !== null && $this->normalizeLot((string) $selectedLot) !== $this->normalizeLot((string) $pplForm->lot_number)) {
                throw ValidationException::withMessages(['lot_number' => 'The selected source lot does not match the PPL Form lot number.']);
            }

            foreach ([[$pplForm, 'ppl_forms'], [$project, 'projects'], [$bidding, 'project_information']] as [$record, $table]) {
                if ($record !== null && Schema::hasColumn($table, 'company_id') && $record->company_id !== null && (int) $record->company_id !== $companyId) {
                    throw ValidationException::withMessages(['source' => 'A selected source record belongs to a different company.']);
                }
            }
            if ($project !== null && $bidding !== null) {
                $this->persistExplicitProjectBiddingLink($project, $bidding);
            }
            foreach ([[$pplForm, 'ppl_forms'], [$project, 'projects'], [$bidding, 'project_information']] as [$record, $table]) {
                if ($record !== null && Schema::hasColumn($table, 'company_id') && $record->company_id === null) {
                    $record->forceFill(['company_id' => $companyId])->save();
                }
            }

            return MmcPplSourceLink::query()->updateOrCreate(
                ['ppl_form_id' => $pplForm->getKey()],
                [
                    'company_id' => $companyId,
                    'project_information_id' => $bidding?->getKey(),
                    'project_id' => $project?->getKey(),
                    'bidding_lot_id' => $biddingLot?->getKey(),
                    'legacy_lot_id' => $legacyLot?->getKey(),
                    'match_status' => 'verified',
                    'match_origin' => 'manual',
                    'matching_identifiers' => [
                        'project_code' => $pplForm->project_code,
                        'project_title' => $pplForm->project_title,
                        'project_identifier' => $pplForm->project_id_no,
                        'lot_number' => $pplForm->lot_number,
                    ],
                    'confirmed_by' => $user->getAuthIdentifier(),
                    'confirmed_at' => now(),
                ],
            );
        }, 3);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, ProjectInformation>  $biddings
     * @param  Collection<int, ProjectLot>  $biddingLots
     * @param  Collection<int, LegacyLot>  $legacyLots
     * @return array{status: string, project: ?Project, bidding: ?ProjectInformation, bidding_lot: ?ProjectLot, legacy_lot: ?LegacyLot, reasons: list<string>}
     */
    private function resolveHistoricalMatch(PplForm $pplForm, $projects, $biddings, $biddingLots, $legacyLots): array
    {
        $identifier = trim((string) $pplForm->project_id_no);
        $biddingMatches = collect();
        if ($identifier !== '') {
            $biddingMatches = $biddings->filter(function (ProjectInformation $bidding) use ($identifier): bool {
                foreach (['project_id_no', 'reference_no', 'project_id'] as $field) {
                    $candidate = $bidding->getAttribute($field);
                    if (is_string($candidate) && trim($candidate) !== '' && $this->normalize($candidate) === $this->normalize($identifier)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }
        if ($biddingMatches->isEmpty()) {
            $biddingMatches = $biddings->filter(fn (ProjectInformation $bidding): bool => $this->normalizeProjectName((string) $pplForm->project_title) !== ''
                && $this->normalizeProjectName((string) $pplForm->project_title) === $this->normalizedBiddingName($bidding))->values();
        }
        if ($biddingMatches->count() !== 1) {
            return [
                'status' => $biddingMatches->count() > 1 ? 'ambiguous' : 'unmatched',
                'project' => null, 'bidding' => null, 'bidding_lot' => null, 'legacy_lot' => null,
                'candidate_bidding_ids' => $biddingMatches->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                'candidate_biddings' => $biddingMatches->map(fn (ProjectInformation $candidate): array => [
                    'id' => $candidate->getKey(),
                    'name' => $candidate->project_name,
                    'identifier' => $candidate->project_id_no ?: ($candidate->reference_no ?: $candidate->project_id),
                ])->all(),
                'reasons' => [$biddingMatches->count() > 1 ? 'Multiple bidding records match the PPL identity.' : 'No bidding record matches the PPL identity.'],
            ];
        }

        $bidding = $biddingMatches->first();
        $projectMatches = $projects->filter(fn (Project $project): bool => $this->projectsAreRelated($project, $bidding))->values();
        if ($projectMatches->count() !== 1) {
            return [
                'status' => $projectMatches->count() > 1 ? 'ambiguous' : 'needs_review',
                'project' => null, 'bidding' => $bidding, 'bidding_lot' => null, 'legacy_lot' => null,
                'candidate_bidding_ids' => [$bidding->getKey()],
                'candidate_biddings' => [[
                    'id' => $bidding->getKey(),
                    'name' => $bidding->project_name,
                    'identifier' => $bidding->project_id_no ?: ($bidding->reference_no ?: $bidding->project_id),
                ]],
                'reasons' => ['No unique verified Project List relationship exists.'],
            ];
        }

        $project = $projectMatches->first();
        $number = $this->normalizeLot((string) $pplForm->lot_number);
        $biddingLotMatches = $biddingLots->filter(fn (ProjectLot $lot): bool => (int) $lot->project_id === (int) $bidding->getKey()
            && $this->normalizeLot((string) $lot->lot_no) === $number)->values();
        $legacyLotMatches = $legacyLots->filter(fn (LegacyLot $lot): bool => (int) $lot->project_id === (int) $project->getKey()
            && $this->normalizeLot((string) $lot->lot_name) === $number)->values();
        if ($biddingLotMatches->count() > 1 || $legacyLotMatches->count() > 1) {
            return [
                'status' => 'ambiguous', 'project' => $project, 'bidding' => $bidding, 'bidding_lot' => null,
                'legacy_lot' => null, 'candidate_bidding_ids' => [$bidding->getKey()],
                'candidate_biddings' => [['id' => $bidding->getKey(), 'name' => $bidding->project_name, 'identifier' => $bidding->project_id_no ?: ($bidding->reference_no ?: $bidding->project_id)]],
                'reasons' => ['Multiple lot records share the PPL lot number.'],
            ];
        }

        $biddingLot = $biddingLotMatches->first();
        $legacyLot = $legacyLotMatches->first();
        $reasons = [];
        if ($biddingLot === null && $legacyLot === null) {
            $reasons[] = 'No existing lot record matches the PPL lot number.';
        }
        if ($legacyLot !== null && $identifier !== '' && trim((string) $legacyLot->contract_no) !== ''
            && $this->normalize($identifier) !== $this->normalize((string) $legacyLot->contract_no)) {
            $reasons[] = 'PPL Project ID No. and the legacy lot contract number differ; manual review is required.';
        }
        if ($biddingLot !== null && $legacyLot !== null
            && $this->normalizeLot((string) $biddingLot->lot_no) !== $this->normalizeLot((string) $legacyLot->lot_name)) {
            $reasons[] = 'Bidding and legacy lot numbers conflict.';
        }

        return [
            'status' => $reasons === [] && ($biddingLot !== null || $legacyLot !== null) ? 'auto_matched' : 'needs_review',
            'project' => $project,
            'bidding' => $bidding,
            'candidate_bidding_ids' => [$bidding->getKey()],
            'candidate_biddings' => [['id' => $bidding->getKey(), 'name' => $bidding->project_name, 'identifier' => $bidding->project_id_no ?: ($bidding->reference_no ?: $bidding->project_id)]],
            'bidding_lot' => $biddingLot,
            'legacy_lot' => $legacyLot,
            'reasons' => $reasons,
        ];
    }

    private function projectsAreRelated(Project $project, ProjectInformation $bidding): bool
    {
        if (Schema::hasColumn('projects', 'project_information_id') && $project->project_information_id !== null) {
            return (int) $project->project_information_id === (int) $bidding->getKey();
        }
        $legacy = is_numeric($bidding->project_id) && (string) $project->getKey() === trim((string) $bidding->project_id);

        return $legacy;
    }

    private function persistExplicitProjectBiddingLink(Project $project, ProjectInformation $bidding): void
    {
        if (! Schema::hasColumn('projects', 'project_information_id')) {
            return;
        }
        if ($project->project_information_id !== null && (int) $project->project_information_id !== (int) $bidding->getKey()) {
            throw ValidationException::withMessages(['project_information_id' => 'The Project List record is already linked to a different bidding record.']);
        }
        $otherProjectExists = Project::query()->where('project_information_id', $bidding->getKey())
            ->where('project_id', '<>', $project->getKey())->exists();
        if ($otherProjectExists) {
            throw ValidationException::withMessages(['project_information_id' => 'The bidding record is already linked to another Project List record.']);
        }
        if ($project->project_information_id === null) {
            $project->forceFill(['project_information_id' => $bidding->getKey()])->save();
        }
    }

    private function normalizedBiddingName(ProjectInformation $bidding): string
    {
        $name = (string) $bidding->project_name;
        if (preg_match('/^\s*\(([^)]+)\)\s*/u', $name, $match) === 1
            && $bidding->project_code !== null
            && $this->normalize($match[1]) === $this->normalize((string) $bidding->project_code)) {
            $name = substr($name, strlen($match[0]));
        }

        return $this->normalizeProjectName($name);
    }

    private function normalizeProjectName(string $value): string
    {
        return $this->normalize($value);
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^\pL0-9]/u', '', mb_strtolower(trim($value))) ?? '';
    }

    private function normalizeLot(string $value): string
    {
        return preg_replace('/^lot/', '', $this->normalize($value)) ?? '';
    }

    private function assertMmcFinanceUser(User $user, int $companyId): void
    {
        if (! $user->hasRole('finance') || ! $user->belongsToCompany($companyId)) {
            throw ValidationException::withMessages(['source' => 'An MMC Finance user is required to confirm source relationships.']);
        }
    }
}
