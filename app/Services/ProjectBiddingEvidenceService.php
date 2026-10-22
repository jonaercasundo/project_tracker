<?php

namespace App\Services;

use App\Models\BiddingDocument;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleTrack;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProjectBiddingEvidenceService
{
    public function __construct(private BiddingDocumentService $biddingDocuments) {}

    /**
     * @return array{
     *     bidding: ?ProjectInformation,
     *     documents: Collection<int, BiddingDocument>,
     *     scopes: Collection<int, array<string, mixed>>,
     *     deliveryVerified: bool
     * }
     */
    public function forProject(Project $project, ?Company $company): array
    {
        $bidding = null;
        if (Schema::hasTable('project_information')) {
            if (Schema::hasColumn('projects', 'project_information_id')) {
                $bidding = $project->bidding;
            } else {
                $matchingBiddingRecords = ProjectInformation::query()
                    ->where('project_id', (string) $project->project_id)
                    ->limit(2)
                    ->get();
                if ($matchingBiddingRecords->count() === 1) {
                    $bidding = $matchingBiddingRecords->first();
                }
            }
        }
        $documents = collect();
        $scopes = collect();
        $tracksAvailable = Schema::hasTable('project_lifecycle_tracks')
            && Schema::hasTable('project_lifecycle_events')
            && Schema::hasTable('project_information')
            && Schema::hasTable('bidding_documents');

        if (! $company || $company->code !== 'MMC' || ! $company->is_active) {
            $bidding = null;
        }

        if ($bidding && Schema::hasColumn('projects', 'company_id') && $project->company_id !== null
            && (int) $project->company_id !== (int) $company->company_id) {
            $bidding = null;
        }

        if ($bidding && Schema::hasColumn('project_information', 'company_id')
            && (int) $bidding->company_id !== (int) $company->company_id) {
            $bidding = null;
        }

        if ($bidding && Schema::hasTable('bidding_documents')) {
            $documentRelations = ['uploader:user_id,name'];
            if (Schema::hasTable('bidding_document_versions')) {
                $documentRelations[] = 'versions';
            }
            $documents = $bidding->documents()->with($documentRelations)->orderByDesc('id')->get();
            $documents->each(fn (BiddingDocument $document) => $document->setAttribute(
                'can_preview',
                $this->biddingDocuments->canPreview($document)
            ));
        }

        if ($bidding && $tracksAvailable && Schema::hasColumn('project_lifecycle_tracks', 'company_id')
            && Schema::hasColumn('project_lifecycle_tracks', 'project_id')
            && Schema::hasColumn('project_lifecycle_tracks', 'project_information_id')
            && Schema::hasColumn('project_lifecycle_events', 'event_type')
            && Schema::hasColumn('project_lifecycle_events', 'metadata')) {
            $trackRelations = ['events.actor'];
            if (Schema::hasTable('lot')) {
                $trackRelations[] = 'legacyLot';
            }
            if (Schema::hasTable('lots')) {
                $trackRelations[] = 'biddingLot';
            }

            $tracks = ProjectLifecycleTrack::query()
                ->where('project_id', $project->project_id)
                ->where('project_information_id', $bidding->getKey())
                ->where('company_id', $company->company_id)
                ->with($trackRelations)
                ->get();

            foreach ($tracks as $track) {
                $lotLabel = ($track->relationLoaded('legacyLot') ? $track->legacyLot?->lot_name : null)
                    ?? ($track->relationLoaded('biddingLot') ? $track->biddingLot?->lot_no : null)
                    ?? ($track->legacy_lot_id || $track->bidding_lot_id ? 'Lot reference unavailable' : 'Project-level');
                $scopeEvents = collect();

                foreach ($track->events as $event) {
                    if (! in_array($event->event_type, ['noa_verified', 'ntp_verified', 'delivery_acceptance_verified'], true)) {
                        continue;
                    }

                    $documentId = (int) ($event->metadata['document_id'] ?? 0);
                    $document = $documentId > 0
                        ? $documents->firstWhere('id', $documentId)
                        : null;
                    $documentVersionNumber = (int) ($event->metadata['document_version'] ?? 0);
                    $documentVersion = $document && $documentVersionNumber > 0
                        && $documentVersionNumber !== (int) $document->version
                        ? $document->versions->firstWhere('version', $documentVersionNumber)
                        : null;
                    if ($documentVersionNumber > 0 && $document && $documentVersionNumber !== (int) $document->version && ! $documentVersion) {
                        $document = null;
                    }

                    $scopeEvents->push([
                        'event' => $event,
                        'document' => $document,
                        'documentVersion' => $documentVersion,
                        'amount' => $event->event_type === 'noa_verified'
                            ? ($event->metadata['award_amount'] ?? (! $track->legacy_lot_id && ! $track->bidding_lot_id ? $bidding->contract_amount : null))
                            : null,
                        'amountSource' => isset($event->metadata['award_amount'])
                            ? 'NOA verification record'
                            : (! $track->legacy_lot_id && ! $track->bidding_lot_id && $bidding->contract_amount !== null ? 'Bidding contract amount' : null),
                    ]);
                }

                $scopes->push([
                    'lot' => $lotLabel,
                    'bidding_lot_id' => $track->bidding_lot_id,
                    'events' => $scopeEvents->sortByDesc(fn (array $entry): string => (string) $entry['event']->created_at)->values(),
                ]);
            }
        }

        if ($bidding && Schema::hasTable('lots') && Schema::hasColumn('lots', 'project_id') && Schema::hasColumn('lots', 'lot_no')) {
            $knownBiddingLotIds = $scopes->pluck('bidding_lot_id')->filter()->all();
            foreach ($bidding->lots()->orderBy('id')->get() as $lot) {
                if (! in_array((int) $lot->getKey(), array_map('intval', $knownBiddingLotIds), true)) {
                    $scopes->push(['lot' => $lot->lot_no ?: 'Lot '.$lot->getKey(), 'bidding_lot_id' => $lot->getKey(), 'events' => collect()]);
                }
            }
        }

        if ($scopes->isEmpty()) {
            $scopes->push(['lot' => 'Project-level', 'bidding_lot_id' => null, 'events' => collect()]);
        }

        $deliveryVerified = $scopes->contains(fn (array $scope): bool => $scope['events']->contains(
            fn (array $entry): bool => $entry['event']->event_type === 'delivery_acceptance_verified'
        ));

        return [
            'bidding' => $bidding,
            'documents' => $documents,
            'scopes' => $scopes,
            'deliveryVerified' => $deliveryVerified,
        ];
    }
}
