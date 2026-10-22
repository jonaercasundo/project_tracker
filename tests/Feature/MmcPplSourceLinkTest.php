<?php

use App\Models\Company;
use App\Models\Lot as LegacyLot;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Models\User;
use App\Services\MmcPplSourceLinkService;
use App\Services\ProjectAwardSynchronizationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function makeLinkedMmcPplFixture(): array
{
    $company = Company::query()->firstOrCreate(['code' => 'MMC'], ['name' => 'Metro-Mobilia', 'is_active' => true]);
    $project = Project::query()->create([
        'project_name' => 'SME Equipment Package', 'project_code' => 'SME', 'ref_no' => 'REF-1001',
        'agency' => 'Test Agency', 'contract_amount' => '500.00', 'ABC' => '600.00', 'status' => 'Implementation',
    ]);
    $bidding = ProjectInformation::query()->create([
        'project_id' => (string) $project->getKey(), 'project_code' => 'SME', 'project_name' => 'SME Equipment Package',
        'approved_budget_contract_abc' => '700.00', 'status' => 'Awarded',
    ]);
    $biddingLot = ProjectLot::query()->create(['project_id' => $bidding->getKey(), 'lot_no' => 'Lot 2']);
    $legacyLot = LegacyLot::query()->create([
        'project_id' => $project->getKey(), 'lot_name' => '2', 'contract_no' => 'LOT-2-CONTRACT',
    ]);
    $pplForm = PplForm::query()->create([
        'company_id' => $company->company_id, 'project_code' => 'SME', 'lot_number' => 'Lot 2',
        'project_title' => 'SME Equipment Package', 'project_id_no' => 'LOT-2-CONTRACT', 'abc' => '157751301.52',
    ]);

    return compact('company', 'project', 'bidding', 'biddingLot', 'legacyLot', 'pplForm');
}

function makeMmcFinanceUser(): User
{
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => 'finance']);
    $user->assignRole(Role::findOrCreate('finance', 'web'));
    $company = Company::query()->firstOrCreate(['code' => 'MMC'], ['name' => 'Metro-Mobilia', 'is_active' => true]);
    $user->companies()->attach($company);

    return $user;
}

it('dry runs and idempotently links only an exact project and lot match without changing PPL financial data or making duplicate lots', function () {
    $records = makeLinkedMmcPplFixture();
    $user = makeMmcFinanceUser();
    $service = app(MmcPplSourceLinkService::class);

    $dryRun = $service->dryRunHistoricalLinks();
    expect($dryRun['rows'][0]['status'])->toBe('auto_matched')
        ->and($dryRun['rows'][0]['legacy_lot_id'])->toBe($records['legacyLot']->getKey())
        ->and($dryRun['rows'][0]['bidding_lot_id'])->toBe($records['biddingLot']->getKey())
        ->and(MmcPplSourceLink::query()->count())->toBe(0);

    $firstApply = $service->linkVerifiedHistoricalSources($user);
    $secondApply = $service->linkVerifiedHistoricalSources($user);

    expect($firstApply['linked'])->toBe(1)
        ->and($secondApply['linked'])->toBe(0)
        ->and(MmcPplSourceLink::query()->count())->toBe(1)
        ->and(MmcPplSourceLink::query()->sole()->legacy_lot_id)->toBe($records['legacyLot']->getKey())
        ->and(MmcPplSourceLink::query()->sole()->bidding_lot_id)->toBe($records['biddingLot']->getKey())
        ->and(MmcPplSourceLink::query()->sole()->match_status)->toBe('verified')
        ->and((string) $records['pplForm']->fresh()->abc)->toBe('157751301.52')
        ->and((int) $records['project']->fresh()->project_information_id)->toBe((int) $records['bidding']->getKey())
        ->and($records['project']->fresh()->status)->toBe('Implementation')
        ->and(ProjectLot::query()->count())->toBe(1)
        ->and(LegacyLot::query()->count())->toBe(1);
});

it('requires review when lot identifiers conflict and leaves the PPL record unchanged', function () {
    $records = makeLinkedMmcPplFixture();
    $records['legacyLot']->forceFill(['contract_no' => 'DIFFERENT-CONTRACT'])->save();
    $user = makeMmcFinanceUser();
    $service = app(MmcPplSourceLinkService::class);

    $dryRun = $service->dryRunHistoricalLinks();
    $apply = $service->linkVerifiedHistoricalSources($user);

    expect($dryRun['rows'][0]['status'])->toBe('needs_review')
        ->and($apply['linked'])->toBe(0)
        ->and($apply['skipped'])->toBe(1)
        ->and(MmcPplSourceLink::query()->count())->toBe(0)
        ->and((string) $records['pplForm']->fresh()->abc)->toBe('157751301.52')
        ->and(LegacyLot::query()->count())->toBe(1);
});

it('marks duplicate bidding identities as ambiguous instead of selecting a record', function () {
    $records = makeLinkedMmcPplFixture();
    ProjectInformation::query()->create([
        'project_id' => 'OTHER-PROJECT', 'project_code' => 'SME', 'project_name' => 'SME Equipment Package',
        'approved_budget_contract_abc' => '700.00', 'status' => 'Draft',
    ]);

    $dryRun = app(MmcPplSourceLinkService::class)->dryRunHistoricalLinks();

    expect($dryRun['rows'][0]['status'])->toBe('ambiguous')
        ->and(MmcPplSourceLink::query()->count())->toBe(0)
        ->and((string) $records['pplForm']->fresh()->abc)->toBe('157751301.52');
});

it('rejects a manual source link when the Project List record belongs to another company', function () {
    $records = makeLinkedMmcPplFixture();
    $otherCompany = Company::query()->create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);
    $records['project']->forceFill(['company_id' => $otherCompany->company_id])->save();
    $user = makeMmcFinanceUser();

    expect(fn () => app(MmcPplSourceLinkService::class)->recordManualPplLink(
        $records['pplForm'],
        [
            'project_id' => $records['project']->getKey(),
            'project_information_id' => $records['bidding']->getKey(),
            'bidding_lot_id' => $records['biddingLot']->getKey(),
        ],
        $user,
        (int) $records['company']->company_id,
    ))->toThrow(ModelNotFoundException::class)
        ->and(MmcPplSourceLink::query()->count())->toBe(0);
});

it('does not expose a company-owned MI PPL Form through the MMC route context', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $mi = Company::query()->create(['name' => 'Mouser', 'code' => 'MI', 'is_active' => true]);
    $miPpl = PplForm::query()->create([
        'company_id' => $mi->company_id,
        'project_code' => 'MI-SOURCE',
        'lot_number' => '1',
        'project_title' => 'MI private source',
    ]);

    $this->actingAs($user)->get(route('ppl_forms.show', $miPpl->getKey()))->assertNotFound();
});

it('rejects operational synchronization between project and bidding records owned by different companies', function () {
    $records = makeLinkedMmcPplFixture();
    $otherCompany = Company::query()->create(['name' => 'Mouser', 'code' => 'MI', 'is_active' => true]);
    $records['project']->forceFill([
        'company_id' => $otherCompany->company_id,
        'project_information_id' => $records['bidding']->getKey(),
    ])->save();
    $records['bidding']->forceFill(['company_id' => $records['company']->company_id])->save();

    expect(fn () => app(ProjectAwardSynchronizationService::class)->syncFromBidding($records['bidding'], false))
        ->toThrow(ValidationException::class);
});
