<?php

use App\Models\Company;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

/** @return array{form: PplForm, project: ?Project, bidding: ?ProjectInformation} */
function makePplListingFixture(User $user, array $formAttributes = [], ?string $projectStatus = null): array
{
    $company = $user->currentCompany();
    $identifier = fake()->unique()->numerify('####');
    $project = null;
    $bidding = null;

    if ($projectStatus !== null) {
        $projectAttributes = [
            'project_name' => 'MMC Listing Project '.$identifier,
            'project_code' => 'MMC-LIST-'.$identifier,
            'ref_no' => 'MMC-LIST-REF-'.$identifier,
            'agency' => 'Listing Agency '.$identifier,
            'contract_amount' => '450.00',
            'ABC' => '500.00',
            'status' => $projectStatus,
        ];
        if (Schema::hasColumn('projects', 'company_id')) {
            $projectAttributes['company_id'] = $company->company_id;
        }
        $project = Project::query()->create($projectAttributes);

        $biddingAttributes = [
            'project_id' => 'BID-LIST-'.$identifier,
            'project_code' => 'MMC-LIST-'.$identifier,
            'project_name' => 'MMC Listing Project '.$identifier,
            'procuring_entity' => 'Listing Agency '.$identifier,
            'approved_budget_contract_abc' => '550.00',
            'status' => 'Awarded',
        ];
        if (Schema::hasColumn('project_information', 'company_id')) {
            $biddingAttributes['company_id'] = $company->company_id;
        }
        $bidding = ProjectInformation::query()->create($biddingAttributes);
        if (Schema::hasColumn('projects', 'project_information_id')) {
            $project->forceFill(['project_information_id' => $bidding->getKey()])->save();
        }
    }

    $form = PplForm::query()->create(array_replace([
        'company_id' => $company->company_id,
        'project_code' => 'PPL-'.$identifier,
        'lot_number' => 'Lot 2',
        'project_title' => 'MMC PPL Ledger '.$identifier,
        'project_id_no' => 'PPL-REF-'.$identifier,
        'region' => 'NCR',
        'abc' => '500.00',
        'year_of_revenue_recognition' => 2025,
    ], $formAttributes));
    if (isset($formAttributes['created_at'])) {
        $form->forceFill(['created_at' => $formAttributes['created_at']])->save();
    }

    if ($project !== null && $bidding !== null) {
        MmcPplSourceLink::query()->create([
            'company_id' => $company->company_id,
            'ppl_form_id' => $form->getKey(),
            'project_id' => $project->project_id,
            'project_information_id' => $bidding->getKey(),
            'match_status' => 'verified',
            'match_origin' => 'manual',
            'confirmed_by' => $user->user_id,
            'confirmed_at' => now(),
        ]);
    }

    return compact('form', 'project', 'bidding');
}

it('searches PPL text, identifiers, form ids, and agency from a verified source', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Searchable classroom tables'], 'Implementation');

    foreach (['Searchable classroom', $fixture['form']->project_code, $fixture['form']->project_id_no, 'Listing Agency', (string) $fixture['form']->id] as $search) {
        $this->get(route('ppl_forms.index', ['search' => $search]))
            ->assertOk()
            ->assertSee('Searchable classroom tables');
    }
});

it('combines verified operational status, project year, lot, bidding, plan and created date filters', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $matching = makePplListingFixture($user, [
        'project_title' => 'Combined filter match',
        'lot_number' => 'Lot 2',
        'year_of_revenue_recognition' => 2025,
        'abc' => '800.00',
        'created_at' => '2025-06-10 09:00:00',
    ], 'Implementation');
    $other = makePplListingFixture($user, [
        'project_title' => 'Combined filter excluded',
        'lot_number' => 'Lot 9',
        'year_of_revenue_recognition' => 2024,
        'created_at' => '2024-06-10 09:00:00',
    ], 'Bidding');
    ProjectFinancialPlan::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'ppl_form_id' => $matching['form']->getKey(),
        'name' => 'Linked PPL draft',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
    ]);

    $response = $this->get(route('ppl_forms.index', [
        'status' => 'Implementation',
        'year' => 2025,
        'lot' => 'Lot 2',
        'linkage' => 'linked',
        'bidding_id' => $matching['bidding']->getKey(),
        'financial_plan' => 'linked',
        'created_from' => '2025-06-01',
        'created_to' => '2025-06-30',
    ]));

    $response->assertOk()->assertSee('Combined filter match')->assertDontSee('Combined filter excluded');
    expect($response->viewData('data')->pluck('id')->all())->toBe([$matching['form']->getKey()])
        ->and($response->viewData('data')->total())->toBe(1)
        ->and((float) $other['form']->fresh()->abc)->toBe(500.0);
});

it('shows unlinked forms without assigning an operational status', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Unlinked legacy form']);

    $this->get(route('ppl_forms.index', ['status' => 'Unlinked', 'linkage' => 'unlinked']))
        ->assertOk()
        ->assertSee('Unlinked legacy form')
        ->assertSee('Unlinked');

    expect((float) $fixture['form']->fresh()->abc)->toBe(500.0);
});

it('keeps foreign company forms and source statuses out of the MMC listing', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'MMC owned form'], 'Implementation');
    $miCompany = Company::query()->create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);
    $miForm = PplForm::query()->create([
        'company_id' => $miCompany->company_id,
        'project_code' => 'MI-PRIVATE',
        'lot_number' => 'Lot 1',
        'project_title' => 'MI private form',
        'project_id_no' => 'MI-PRIVATE-REF',
        'abc' => '999.00',
    ]);
    $miProject = Project::query()->create([
        'project_name' => 'MI confidential project',
        'project_code' => 'MI-PROJECT',
        'ref_no' => 'MI-REF-'.fake()->unique()->numerify('####'),
        'agency' => 'MI Agency',
        'contract_amount' => '999.00',
        'ABC' => '999.00',
        'status' => 'Completed',
    ]);
    $foreignSource = MmcPplSourceLink::query()->where('ppl_form_id', $fixture['form']->getKey())->firstOrFail();
    $foreignSource->forceFill(['company_id' => $miCompany->company_id, 'project_id' => $miProject->project_id])->save();

    $response = $this->get(route('ppl_forms.index'))
        ->assertOk()
        ->assertSee('MMC owned form')
        ->assertSee('Unlinked')
        ->assertDontSee('MI private form')
        ->assertDontSee('MI confidential project');

    expect($response->viewData('data')->pluck('id')->all())->toContain($fixture['form']->getKey())
        ->not->toContain($miForm->getKey());
});

it('sorts by permitted columns and preserves filters in pagination and form links', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $low = makePplListingFixture($user, ['project_title' => 'Low ABC form', 'abc' => '100.00']);
    $high = makePplListingFixture($user, ['project_title' => 'High ABC form', 'abc' => '900.00']);
    for ($index = 0; $index < 11; $index++) {
        makePplListingFixture($user, ['project_title' => 'Page fixture '.$index]);
    }

    $sorted = $this->get(route('ppl_forms.index', ['sort_by' => 'abc', 'sort_order' => 'asc', 'per_page' => 50]))->assertOk();
    expect($sorted->viewData('data')->first()->id)->toBe($low['form']->getKey())
        ->and($sorted->viewData('data')->last()->id)->toBe($high['form']->getKey());

    $paginated = $this->get(route('ppl_forms.index', ['search' => 'Page fixture', 'per_page' => 10]))->assertOk();
    $paginated->assertSee('page=2')->assertSee('search=Page%20fixture');

    $filters = ['search' => 'Low ABC', 'sort_by' => 'abc', 'sort_order' => 'asc'];
    $this->get(route('ppl_forms.index', $filters))
        ->assertSee(route('ppl_forms.show', ['id' => $low['form']->getKey()] + $filters));
    $this->get(route('ppl_forms.show', ['id' => $low['form']->getKey()] + $filters))
        ->assertSee(route('ppl_forms.index', $filters));
});

it('validates filter parameters and restricts the listing to authorized MMC Finance users', function () {
    $financeUser = $this->signInBiddingUser('finance', 'MMC');
    makePplListingFixture($financeUser, ['project_title' => 'MMC private form']);
    $this->get(route('ppl_forms.index', ['sort_by' => 'untrusted_column']))->assertSessionHasErrors('sort_by');

    $this->signInBiddingUser('user', 'MMC');
    $this->get(route('ppl_forms.index'))->assertForbidden();

    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('ppl_forms.index'))->assertForbidden()->assertDontSee('MMC private form');
});
