<?php

use App\Models\BiddingDocument;
use App\Models\Company;
use App\Models\Lot;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleTrack;
use App\Models\ProjectLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

/** @return array{company: Company, project: Project, bidding: ProjectInformation, legacyLot: Lot, biddingLot: ProjectLot, owner: User} */
function lifecycleRecords(string $legacyLotName = '2', string $biddingLotNumber = 'Lot 2'): array
{
    $company = Company::query()->firstOrCreate(['code' => 'MMC'], ['name' => 'Metro-Mobilia', 'is_active' => true]);
    $owner = User::factory()->create(['role' => 'user', 'username' => fake()->unique()->userName()]);
    $owner->assignRole(Role::findOrCreate('user', 'web'));
    $owner->companies()->attach($company);
    $project = Project::query()->create([
        'project_name' => 'SME Lifecycle Package', 'project_code' => 'SME-2026', 'ref_no' => 'LC-'.fake()->unique()->numerify('####'),
        'agency' => 'Department of Education', 'contract_amount' => '500.00', 'ABC' => '600.00', 'status' => 'Implementation',
    ]);
    $bidding = ProjectInformation::query()->create([
        'project_id' => (string) $project->project_id, 'project_code' => 'SME-2026', 'project_name' => 'SME Lifecycle Package',
        'procuring_entity' => 'Department of Education', 'approved_budget_contract_abc' => '700.00', 'status' => 'Awarded',
        'contract_amount' => '500.00',
    ]);
    $project->forceFill(['project_information_id' => $bidding->getKey()])->save();
    $legacyLot = Lot::query()->create(['project_id' => $project->project_id, 'lot_name' => $legacyLotName, 'contract_no' => 'LC-LOT-2']);
    $biddingLot = ProjectLot::query()->create(['project_id' => $bidding->getKey(), 'lot_no' => $biddingLotNumber]);

    return compact('company', 'project', 'bidding', 'legacyLot', 'biddingLot', 'owner');
}

function startLifecycleTracking(array $records, array $overrides = []): TestResponse
{
    return test()->post(route('operations.lifecycle.store'), array_replace([
        'project_id' => $records['project']->project_id,
        'bidding_id' => $records['bidding']->getKey(),
        'legacy_lot_id' => $records['legacyLot']->getKey(),
        'bidding_lot_id' => $records['biddingLot']->getKey(),
        'owner_user_id' => $records['owner']->user_id,
        'next_action' => 'Confirm delivery schedule',
        'next_action_due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
    ], $overrides));
}

function lifecycleEvidenceDocument(ProjectInformation $bidding, string $name): BiddingDocument
{
    return BiddingDocument::query()->create([
        'project_information_id' => $bidding->getKey(),
        'display_name' => $name,
        'original_name' => $name.'.pdf',
        'stored_name' => fake()->uuid().'.pdf',
        'storage_disk' => 'local',
        'storage_path' => 'lifecycle/'.fake()->uuid().'.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'file_size' => 1024,
        'uploaded_at' => now(),
    ]);
}

it('renders existing operations totals without creating a duplicate project master', function () {
    $records = lifecycleRecords();
    $this->signInBiddingUser();

    $this->get(route('operations.lifecycle.index'))
        ->assertOk()
        ->assertSee('Operations Bidding & Project Lifecycle', false)
        ->assertSee('Bidding opportunities')
        ->assertSee('Data-backed signals')
        ->assertSee('Project List status stays authoritative')
        ->assertSee('SME Lifecycle Package');

    expect(Project::query()->count())->toBe(1)
        ->and(ProjectLifecycleTrack::query()->count())->toBe(0);
});

it('starts a lifecycle record from the verified existing project and lot records without changing operational statuses', function () {
    $records = lifecycleRecords();
    $this->signInBiddingUser();

    startLifecycleTracking($records)->assertRedirect(route('operations.lifecycle.show', ProjectLifecycleTrack::query()->first()))
        ->assertSessionHasNoErrors();

    $track = ProjectLifecycleTrack::query()->sole();
    expect($track->current_stage)->toBe('Implementation')
        ->and($track->project_id)->toBe($records['project']->project_id)
        ->and($track->project_information_id)->toBe($records['bidding']->getKey())
        ->and($track->legacy_lot_id)->toBe($records['legacyLot']->getKey())
        ->and($track->bidding_lot_id)->toBe($records['biddingLot']->getKey())
        ->and($track->owner_user_id)->toBe($records['owner']->user_id)
        ->and($records['project']->fresh()->status)->toBe('Implementation')
        ->and($records['bidding']->fresh()->status)->toBe('Awarded')
        ->and(Project::query()->count())->toBe(1);
    $this->assertDatabaseHas('project_lifecycle_events', [
        'project_lifecycle_track_id' => $track->getKey(), 'event_type' => 'tracking_started', 'to_stage' => 'Implementation',
    ]);

    startLifecycleTracking($records)->assertSessionHasErrors('project_id');
    expect(ProjectLifecycleTrack::query()->count())->toBe(1);
});

it('rejects unrelated project bidding and lot combinations without saving lifecycle records', function () {
    $records = lifecycleRecords('2', 'Lot 9');
    $this->signInBiddingUser();

    startLifecycleTracking($records)->assertSessionHasErrors('bidding_lot_id');
    expect(ProjectLifecycleTrack::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(1)
        ->and($records['project']->fresh()->status)->toBe('Implementation');
});

it('tracks separate lots at separate lifecycle stages while sharing the same project masters', function () {
    $records = lifecycleRecords();
    $secondLegacyLot = Lot::query()->create([
        'project_id' => $records['project']->project_id, 'lot_name' => '9', 'contract_no' => 'LC-LOT-9',
    ]);
    $secondBiddingLot = ProjectLot::query()->create([
        'project_id' => $records['bidding']->getKey(), 'lot_no' => 'Lot 9',
    ]);
    $this->signInBiddingUser();

    startLifecycleTracking($records)->assertRedirect();
    startLifecycleTracking($records, [
        'legacy_lot_id' => $secondLegacyLot->getKey(), 'bidding_lot_id' => $secondBiddingLot->getKey(),
    ])->assertRedirect();
    $deliveryTrack = ProjectLifecycleTrack::query()->where('legacy_lot_id', $records['legacyLot']->getKey())->firstOrFail();
    $implementationTrack = ProjectLifecycleTrack::query()->where('legacy_lot_id', $secondLegacyLot->getKey())->firstOrFail();

    $this->patch(route('operations.lifecycle.update', $deliveryTrack), ['stage' => 'Delivery'])->assertRedirect();

    expect($deliveryTrack->fresh()->current_stage)->toBe('Delivery')
        ->and($implementationTrack->fresh()->current_stage)->toBe('Implementation')
        ->and(Project::query()->count())->toBe(1)
        ->and(ProjectInformation::query()->count())->toBe(1);
});

it('advances an eligible project only after an authorized user verifies an archived NOA', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Pending']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $document = lifecycleEvidenceDocument($records['bidding'], 'Official NOA');

    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-2026-001', 'document_id' => $document->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($records['project']->fresh()->status)->toBe('Awarded')
        ->and($track->fresh()->current_stage)->toBe('Awarded');
    $event = $track->fresh()->events()->where('event_type', 'noa_verified')->sole();
    expect($event->metadata)->toMatchArray([
        'reference' => 'NOA-2026-001', 'document_id' => $document->getKey(),
        'event_date' => '2026-10-15', 'verified_by' => auth()->id(),
    ]);

    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-2026-001', 'document_id' => $document->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($track->fresh()->events()->where('event_type', 'noa_verified')->count())->toBe(1);
    expect($track->fresh()->events()->where('event_type', 'noa_verified')->sole()->metadata)
        ->toMatchArray(['project_status_before' => 'Pending', 'project_status_after' => 'Awarded']);
});

it('does not advance a project when NOA evidence is missing or belongs to another bidding record', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Pending']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $otherBidding = ProjectInformation::query()->create([
        'project_id' => 'BID-'.fake()->unique()->numerify('####'), 'project_name' => 'Other bid',
        'procuring_entity' => 'Test Agency', 'approved_budget_contract_abc' => '900.00', 'status' => 'Awarded',
    ]);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $foreignDocument = lifecycleEvidenceDocument($otherBidding, 'Unrelated document');

    $this->post(route('operations.lifecycle.verify-noa', $track), ['reference' => 'NOA-001'])
        ->assertSessionHasErrors(['document_id']);
    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-001', 'document_id' => $foreignDocument->getKey(),
    ])->assertUnprocessable();

    expect($records['project']->fresh()->status)->toBe('Pending')
        ->and($track->fresh()->events()->where('event_type', 'noa_verified')->count())->toBe(0);
});

it('advances to implementation only after verified NOA and NTP evidence and creates no delivery receipt', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Pending']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $noa = lifecycleEvidenceDocument($records['bidding'], 'Official NOA');
    $ntp = lifecycleEvidenceDocument($records['bidding'], 'Official NTP');

    $this->post(route('operations.lifecycle.verify-ntp', $track), [
        'reference' => 'NTP-001', 'notice_date' => '2026-10-20', 'effectivity_date' => '2026-11-01', 'document_id' => $ntp->getKey(),
    ])->assertSessionHasErrors('reference');
    expect($records['project']->fresh()->status)->toBe('Pending');

    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-001', 'document_id' => $noa->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('operations.lifecycle.verify-ntp', $track), [
        'reference' => 'NTP-001', 'notice_date' => '2026-10-20', 'effectivity_date' => '2026-11-01', 'document_id' => $ntp->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($records['project']->fresh()->status)->toBe('Implementation')
        ->and($track->fresh()->current_stage)->toBe('Implementation')
        ->and(DB::table('deliveries')->count())->toBe(0);
    $event = $track->fresh()->events()->where('event_type', 'ntp_verified')->sole();
    expect($event->metadata)->toMatchArray([
        'reference' => 'NTP-001', 'document_id' => $ntp->getKey(),
        'event_date' => '2026-10-20', 'effectivity_date' => '2026-11-01', 'verified_by' => auth()->id(),
    ]);
});

it('keeps a partial NOA award at lot level until every existing bidding lot has verified evidence', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Pending']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $secondBiddingLot = ProjectLot::query()->create(['project_id' => $records['bidding']->getKey(), 'lot_no' => 'Lot 9']);
    $secondLegacyLot = Lot::query()->create([
        'project_id' => $records['project']->project_id, 'lot_name' => '9', 'contract_no' => 'LC-LOT-9',
    ]);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    startLifecycleTracking($records, [
        'legacy_lot_id' => $secondLegacyLot->getKey(), 'bidding_lot_id' => $secondBiddingLot->getKey(),
    ])->assertRedirect();
    $tracks = ProjectLifecycleTrack::query()->orderBy('id')->get();
    $documents = [
        lifecycleEvidenceDocument($records['bidding'], 'Lot 2 NOA'),
        lifecycleEvidenceDocument($records['bidding'], 'Lot 9 NOA'),
    ];

    $this->post(route('operations.lifecycle.verify-noa', $tracks[0]), [
        'reference' => 'NOA-LOT-2', 'document_id' => $documents[0]->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($records['project']->fresh()->status)->toBe('Pending');

    $this->post(route('operations.lifecycle.verify-noa', $tracks[1]), [
        'reference' => 'NOA-LOT-9', 'document_id' => $documents[1]->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($records['project']->fresh()->status)->toBe('Awarded');
});

it('preserves an advanced Project List status when a late NOA is verified', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Delivered']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $document = lifecycleEvidenceDocument($records['bidding'], 'Late NOA');

    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-LATE', 'document_id' => $document->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($records['project']->fresh()->status)->toBe('Delivered')
        ->and($track->fresh()->current_stage)->toBe('Delivery');
});

it('restricts official trigger verification to the authenticated MMC operations role', function () {
    $records = lifecycleRecords();
    $records['project']->update(['status' => 'Pending']);
    $records['bidding']->update(['notice_of_award_date' => '2026-10-15']);
    $operationsUser = $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $document = lifecycleEvidenceDocument($records['bidding'], 'Official NOA');
    auth()->logout();
    $this->signInBiddingUser('finance');

    $this->post(route('operations.lifecycle.verify-noa', $track), [
        'reference' => 'NOA-001', 'document_id' => $document->getKey(),
    ])->assertForbidden();

    expect($operationsUser->exists)->toBeTrue()
        ->and($records['project']->fresh()->status)->toBe('Pending');
});

it('maps an existing published bidding record to Submitted without advancing its stored status', function () {
    $company = Company::query()->firstOrCreate(['code' => 'MMC'], ['name' => 'Metro-Mobilia', 'is_active' => true]);
    $bidding = ProjectInformation::query()->create([
        'project_id' => 'BID-'.fake()->unique()->numerify('####'), 'project_name' => 'Published Opportunity',
        'procuring_entity' => 'Test Agency', 'approved_budget_contract_abc' => '900.00', 'status' => 'Published',
    ]);
    $this->signInBiddingUser();

    $this->post(route('operations.lifecycle.store'), ['bidding_id' => $bidding->getKey()])->assertRedirect()->assertSessionHasNoErrors();

    expect(ProjectLifecycleTrack::query()->sole()->current_stage)->toBe('Submitted')
        ->and(ProjectLifecycleTrack::query()->sole()->project_id)->toBeNull()
        ->and($bidding->fresh()->status)->toBe('Published')
        ->and($company->is_active)->toBeTrue();
});

it('enforces sequential lifecycle transitions and keeps the Project List status unchanged', function () {
    $records = lifecycleRecords();
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();

    $this->patch(route('operations.lifecycle.update', $track), ['stage' => 'Billing', 'note' => 'Skip ahead'])
        ->assertSessionHasErrors('stage');
    expect($track->fresh()->current_stage)->toBe('Implementation')
        ->and($records['project']->fresh()->status)->toBe('Implementation');

    $this->patch(route('operations.lifecycle.update', $track), ['stage' => 'Delivery', 'note' => 'Delivery phase began'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($track->fresh()->current_stage)->toBe('Delivery')
        ->and($records['project']->fresh()->status)->toBe('Implementation');
    $this->assertDatabaseHas('project_lifecycle_events', [
        'project_lifecycle_track_id' => $track->getKey(), 'event_type' => 'stage_changed', 'from_stage' => 'Implementation', 'to_stage' => 'Delivery',
    ]);
});

it('audits alternative outcomes and refuses to advance a closed lost or disqualified record', function (string $outcome) {
    $records = lifecycleRecords();
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();

    $this->patch(route('operations.lifecycle.update', $track), ['outcome' => $outcome, 'note' => 'Recorded result'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->patch(route('operations.lifecycle.update', $track), ['stage' => 'Delivery'])
        ->assertSessionHasErrors('stage');

    expect($track->fresh()->outcome)->toBe($outcome)
        ->and($records['project']->fresh()->status)->toBe('Implementation');
    $this->assertDatabaseHas('project_lifecycle_events', [
        'project_lifecycle_track_id' => $track->getKey(), 'event_type' => 'outcome_changed', 'outcome' => $outcome,
    ]);
})->with(['Lost', 'Disqualified', 'Cancelled']);

it('records milestones and required document tasks and filters overdue work', function () {
    $records = lifecycleRecords();
    $this->travelTo(now()->startOfDay()->addHours(12));
    $this->signInBiddingUser();
    startLifecycleTracking($records, ['next_action_due_at' => now()->subDay()->format('Y-m-d H:i:s')])->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();

    $this->post(route('operations.lifecycle.tasks.store', $track), [
        'kind' => 'required_document', 'title' => 'Notice to proceed', 'due_at' => now()->subDay()->format('Y-m-d'),
        'owner_user_id' => $records['owner']->user_id,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $task = $track->tasks()->sole();
    $this->patch(route('operations.lifecycle.tasks.update', [$track, $task]), ['status' => 'complete'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('operations.lifecycle.index', ['overdue' => '1']))
        ->assertOk()->assertSee('SME Lifecycle Package')->assertSee('Overdue');
    $this->get(route('operations.lifecycle.show', $track))
        ->assertOk()->assertSee('Notice to proceed')->assertSee('Data-backed risk signals')
        ->assertSee('Project List operational status')->assertSee('₱600.00');
    expect($task->fresh()->status)->toBe('complete');
    $this->assertDatabaseHas('project_lifecycle_events', ['event_type' => 'task_created']);
    $this->assertDatabaseHas('project_lifecycle_events', ['event_type' => 'task_status_changed']);
});

it('limits lifecycle records and assignments to the active MMC company', function () {
    $records = lifecycleRecords();
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();
    $otherCompany = Company::query()->create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);
    $otherUser = User::factory()->create(['role' => 'user', 'username' => fake()->unique()->userName()]);
    $otherUser->assignRole(Role::findOrCreate('user', 'web'));
    $otherUser->companies()->attach($otherCompany);

    $this->patch(route('operations.lifecycle.update', $track), [
        'owner_user_id' => $otherUser->user_id, 'next_action' => 'Untrusted assignment',
    ])->assertSessionHasErrors('owner_user_id');
    ProjectLifecycleTrack::query()->whereKey($track->getKey())->update(['company_id' => $otherCompany->company_id]);
    $this->get(route('operations.lifecycle.show', $track))->assertNotFound();
    $this->withSession(['company_id' => $otherCompany->company_id])->get(route('operations.lifecycle.index'))->assertForbidden();
});

it('shows MMC PPL financial data only through a verified project and lot source link', function () {
    $records = lifecycleRecords();
    $company = $records['company'];
    $ppl = PplForm::query()->create([
        'company_id' => $company->company_id, 'project_code' => 'SME-2026', 'lot_number' => 'Lot 2',
        'project_title' => 'SME Lifecycle Package', 'project_id_no' => 'LC-LOT-2', 'abc' => '157751301.52',
    ]);
    MmcPplSourceLink::query()->create([
        'company_id' => $company->company_id, 'ppl_form_id' => $ppl->getKey(),
        'project_information_id' => $records['bidding']->getKey(), 'project_id' => $records['project']->project_id,
        'legacy_lot_id' => $records['legacyLot']->getKey(), 'bidding_lot_id' => $records['biddingLot']->getKey(),
        'match_status' => 'verified', 'match_origin' => 'manual', 'confirmed_by' => $records['owner']->user_id, 'confirmed_at' => now(),
    ]);
    $this->signInBiddingUser();
    startLifecycleTracking($records)->assertRedirect();
    $track = ProjectLifecycleTrack::query()->sole();

    $this->get(route('operations.lifecycle.show', $track))
        ->assertOk()->assertSee('Verified MMC PPL source link')->assertSee('PPL Form #'.$ppl->getKey())
        ->assertDontSee('157751301.52')
        ->assertSee('₱600.00')->assertSee('Bidding ABC');
});
