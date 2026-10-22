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

it('opens the existing PPL Form edit page with saved values', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Editable historical form', 'abc' => '74316257.20']);

    $this->get(route('ppl_forms.edit', ['id' => $fixture['form']->id, 'search' => 'Editable']))
        ->assertOk()
        ->assertSee('Edit PPL Form #'.$fixture['form']->id)
        ->assertSee('Editable historical form')
        ->assertSee('74316257.20')
        ->assertSee(route('ppl_forms.update', ['id' => $fixture['form']->id, 'search' => 'Editable']))
        ->assertDontSee('Use existing Operations Bidding Form');

    $this->get(route('ppl_forms.edit', ['id' => 999999]))->assertNotFound();
});

it('renders workbook input columns without filling blank historical assumptions', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, [
        'project_code' => 'SME 2026',
        'lot_number' => '4',
        'project_id_no' => '2026-BLR4-001-B2-CB-008',
        'abc' => '327523242.63',
        'lcb_abc' => null,
        'pf3_contract_amt' => null,
        'bidder' => '15.00%',
    ]);

    $this->get(route('ppl_forms.edit', $fixture['form']->id))
        ->assertOk()
        ->assertSee('1. FORM source')
        ->assertSee('SME 2026')
        ->assertSee('2026-BLR4-001-B2-CB-008')
        ->assertSee('name="lcb_abc"', false)
        ->assertSee('name="pf3_contract_amt"', false)
        ->assertSee('name="allowance_tax_lawyer"', false)
        ->assertSee('name="year_of_revenue_recognition"', false)
        ->assertSee('15.00%')
        ->assertSee('Blank saved fields remain blank')
        ->assertSee('value="327523242.63"', false);
});

it('rejects workbook rate precision that the historical PPL columns cannot retain', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['abc' => '327523242.63', 'lcb_abc' => null]);

    $this->put(route('ppl_forms.update', $fixture['form']->id), [
        'project_code' => $fixture['form']->project_code,
        'lot_number' => $fixture['form']->lot_number,
        'project_title' => $fixture['form']->project_title,
        'abc' => '327523242.63',
        'lcb_abc' => '0.2202',
    ])->assertSessionHasErrors('lcb_abc');

    expect($fixture['form']->fresh()->lcb_abc)->toBeNull();
});

it('updates only validated historical form fields and retains source links and saved plan versions', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Original PPL title', 'abc' => '500.00'], 'Implementation');
    $plan = ProjectFinancialPlan::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'ppl_form_id' => $fixture['form']->id,
        'name' => 'Saved historical plan',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
    ]);
    $version = $plan->versions()->create([
        'version_number' => 1,
        'status' => 'draft',
        'input_data' => ['inputs' => ['abc' => '500.00']],
        'calculation_results' => ['abc' => '500.00'],
        'assumptions_snapshot' => [],
        'factory_cost_snapshot' => [],
        'created_by' => $user->user_id,
    ]);

    $this->put(route('ppl_forms.update', ['id' => $fixture['form']->id]), [
        'project_code' => $fixture['form']->project_code,
        'lot_number' => $fixture['form']->lot_number,
        'project_title' => 'Corrected PPL title',
        'abc' => '500.00',
        'source_project_id' => 999999,
        'company_id' => 999999,
    ])->assertRedirect(route('ppl_forms.show', $fixture['form']->id));

    expect($fixture['form']->fresh()->project_title)->toBe('Corrected PPL title')
        ->and($fixture['form']->fresh()->company_id)->toBe($user->currentCompany()->company_id)
        ->and($fixture['form']->fresh()->sourceLink->project_id)->toBe($fixture['project']->project_id)
        ->and($version->fresh()->calculation_results['abc'])->toBe('500.00');
    $this->assertDatabaseCount('ppl_forms', 1);
});

it('rejects invalid or cross-company PPL Form edits', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Protected MMC form']);
    $foreignCompany = Company::query()->create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);
    $foreignForm = PplForm::query()->create([
        'company_id' => $foreignCompany->company_id,
        'project_code' => 'FOREIGN', 'lot_number' => '1', 'project_title' => 'Foreign form', 'abc' => '100.00',
    ]);

    $this->put(route('ppl_forms.update', $fixture['form']->id), [
        'project_code' => $fixture['form']->project_code,
        'lot_number' => $fixture['form']->lot_number,
        'project_title' => '',
        'abc' => '500.00',
    ])->assertSessionHasErrors('project_title');
    $this->get(route('ppl_forms.edit', $foreignForm->id))->assertNotFound();
    $this->put(route('ppl_forms.update', $foreignForm->id), [
        'project_code' => 'FOREIGN', 'lot_number' => '1', 'project_title' => 'Tampered', 'abc' => '100.00',
    ])->assertNotFound();
    expect($fixture['form']->fresh()->project_title)->toBe('Protected MMC form')
        ->and($foreignForm->fresh()->project_title)->toBe('Foreign form');

    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('ppl_forms.edit', $fixture['form']->id))->assertForbidden();
    $this->put(route('ppl_forms.update', $fixture['form']->id), [
        'project_code' => 'PPL-EDIT', 'lot_number' => '2', 'project_title' => 'Tampered', 'abc' => '500.00',
    ])->assertForbidden();
});

it('requires MMC Finance access to edit a PPL Form', function () {
    $financeUser = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($financeUser, ['project_title' => 'Finance-only PPL form']);

    $this->signInBiddingUser('user', 'MMC');
    $this->get(route('ppl_forms.edit', $fixture['form']->id))->assertForbidden();
    $this->put(route('ppl_forms.update', $fixture['form']->id), [
        'project_code' => 'TAMPER', 'lot_number' => '2', 'project_title' => 'Tampered', 'abc' => '500.00',
    ])->assertForbidden();
    expect($fixture['form']->fresh()->project_title)->toBe('Finance-only PPL form');
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

it('shows workbook financial columns from the latest saved version and totals only filtered MMC plans', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $included = makePplListingFixture($user, [
        'project_title' => str_repeat('Long verified project title ', 8),
        'lot_number' => '2',
        'abc' => '200.00',
        'lcb_abc' => '0.10',
    ], 'Implementation');
    $excluded = makePplListingFixture($user, ['lot_number' => '9', 'abc' => '900.00']);

    $plan = ProjectFinancialPlan::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'ppl_form_id' => $included['form']->id,
        'name' => 'Lot 2 plan',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
    ]);
    foreach ([1 => '10.00', 2 => '30.00'] as $number => $profit) {
        $plan->versions()->create([
            'version_number' => $number,
            'status' => 'draft',
            'input_data' => [],
            'calculation_results' => [
                'abc' => '200.00', 'contract_amount' => '180.00', 'net_sales' => '100.00',
                'factory_cost' => '40.00', 'costs' => ['pf1' => '2.00', 'pf2' => '3.00', 'pf3' => '0.00', 'interest_dst_working_capital' => '4.00'],
                'total_cost_of_sales' => '70.00', 'gross_profit' => $profit,
                'gpm' => $number === 1 ? '0.10' : '0.30', 'opex' => '5.00',
                'net_profit_before_tax' => '25.00', 'income_tax_provision' => '2.00',
                'net_profit_after_tax' => '23.00', 'ppl' => '20.00', 'npm' => '0.20',
                'working_capital_needed' => '60.00',
            ],
            'assumptions_snapshot' => [],
            'factory_cost_snapshot' => [],
            'created_by' => $user->user_id,
        ]);
    }
    $second = makePplListingFixture($user, ['project_title' => 'Another Lot 2 plan', 'lot_number' => '2', 'abc' => '300.00']);
    $secondPlan = ProjectFinancialPlan::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'ppl_form_id' => $second['form']->id,
        'name' => 'Second Lot 2 plan',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
    ]);
    $secondPlan->versions()->create([
        'version_number' => 1,
        'status' => 'draft',
        'input_data' => [],
        'calculation_results' => [
            'abc' => '300.00', 'net_sales' => '300.00', 'gross_profit' => '30.00',
            'ppl' => '15.00', 'gpm' => '0.10', 'npm' => '0.05',
        ],
        'assumptions_snapshot' => [],
        'factory_cost_snapshot' => [],
        'created_by' => $user->user_id,
    ]);
    $foreignCompany = Company::query()->create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);
    ProjectFinancialPlan::query()->create([
        'company_id' => $foreignCompany->company_id,
        'ppl_form_id' => $included['form']->id,
        'name' => 'Foreign plan',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
    ]);

    $response = $this->get(route('ppl_forms.index', ['lot' => '2', 'per_page' => 10]))->assertOk();

    $response->assertSee('PPL Forms project and lot financial table')
        ->assertSee('line-clamp-2')
        ->assertSee('Interest / DST')
        ->assertSee('Working capital')
        ->assertSee('30.00%')
        ->assertSee('10.00%')
        ->assertSee('15.00%')
        ->assertSee('8.75%')
        ->assertDontSee('Lot 9 plan');
    expect($response->viewData('financialSummary')['amounts']['gross_profit'])->toBe('60.00')
        ->and($response->viewData('financialSummary')['amounts']['abc'])->toBe('500.00')
        ->and($response->viewData('financialSummary')['form_abc'])->toBe('500.00')
        ->and($response->viewData('financialSummary')['gpm'])->toBe('0.150000')
        ->and($response->viewData('financialSummary')['npm'])->toBe('0.087500')
        ->and($response->viewData('financialSummary')['plan_count'])->toBe(2)
        ->and((float) $included['form']->fresh()->abc)->toBe(200.0)
        ->and((float) $excluded['form']->fresh()->abc)->toBe(900.0);
});

it('excludes ambiguous plan links from filtered totals without hiding the historical form', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $fixture = makePplListingFixture($user, ['project_title' => 'Review two plans']);
    foreach (['First', 'Second'] as $name) {
        ProjectFinancialPlan::query()->create([
            'company_id' => $user->currentCompany()->company_id,
            'ppl_form_id' => $fixture['form']->id,
            'name' => $name,
            'status' => 'draft',
            'created_by' => $user->user_id,
            'updated_by' => $user->user_id,
        ]);
    }

    $response = $this->get(route('ppl_forms.index', ['search' => 'Review two plans']))->assertSee('Review two plans');

    expect($response->viewData('financialSummary')['ambiguous_count'])->toBe(1)
        ->and($response->viewData('financialSummary')['plan_count'])->toBe(0)
        ->and($response->viewData('financialSummary')['gpm'])->toBeNull();
});

it('aggregates the three SME lot snapshots like PPL VERTICAL without adding project subtotals', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $rows = [
        ['2', '157751301.52', '126626272.8125', '118844587.84908006', '7781684.963419944', '-1082154.1334550562'],
        ['9', '188467107.19', '166636749.99107143', '159249168.11950898', '7387581.8715624511', '-944255.62799112126'],
        ['11', '249563999.56', '204822761.625', '183726829.27875394', '21095932.346246064', '5744588.1776216533'],
    ];
    foreach ($rows as [$lot, $abc, $netSales, $costOfSales, $grossProfit, $ppl]) {
        $fixture = makePplListingFixture($user, [
            'project_code' => 'SME 2025 2, 9, 11',
            'project_title' => 'SME 2025 2, 9, 11',
            'lot_number' => $lot,
            'abc' => $abc,
        ]);
        $plan = ProjectFinancialPlan::query()->create([
            'company_id' => $user->currentCompany()->company_id,
            'ppl_form_id' => $fixture['form']->id,
            'name' => 'SME Lot '.$lot,
            'status' => 'draft',
            'created_by' => $user->user_id,
            'updated_by' => $user->user_id,
        ]);
        $plan->versions()->create([
            'version_number' => 1,
            'status' => 'draft',
            'input_data' => [],
            'calculation_results' => [
                'abc' => $abc,
                'net_sales' => $netSales,
                'total_cost_of_sales' => $costOfSales,
                'gross_profit' => $grossProfit,
                'ppl' => $ppl,
            ],
            'assumptions_snapshot' => [],
            'factory_cost_snapshot' => [],
            'created_by' => $user->user_id,
        ]);
    }

    $response = $this->get(route('ppl_forms.index', ['search' => 'SME 2025 2, 9, 11', 'per_page' => 10]))->assertOk();
    $summary = $response->viewData('financialSummary');

    expect($summary['plan_count'])->toBe(3)
        ->and($summary['form_abc'])->toBe('595782408.27')
        ->and($summary['amounts']['abc'])->toBe('595782408.27')
        ->and($summary['amounts']['net_sales'])->toBe('498085784.43')
        ->and($summary['amounts']['total_cost_of_sales'])->toBe('461820585.25')
        ->and($summary['amounts']['gross_profit'])->toBe('36265199.18')
        ->and($summary['amounts']['ppl'])->toBe('3718178.42')
        ->and($summary['gpm'])->toBe('0.072809')
        ->and($summary['npm'])->toBe('0.007465');
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
