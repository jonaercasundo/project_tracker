<?php

use App\Models\MI_Product;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\ProjectFinancialPlanCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function financialPlanPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Pre-award profitability estimate',
        'project_id' => null,
        'project_information_id' => null,
        'lot_id' => null,
        'ppl_form_id' => null,
        'change_summary' => 'Initial draft',
        'inputs' => [
            'contract_amount' => '112000.00',
            'abc' => '100000.00',
            'factory_cost' => '50000.00',
            'pf1_rate' => '0.01',
            'pf2_rate' => '0',
            'pf3_rate' => '0',
            'll_commission_rate' => '0',
            'interest_dst_rate' => '0',
            'shipping_containers' => '0',
            'rate_per_container' => '0',
            'logistics_rate' => '0',
            'warehouse_area' => '0',
            'warehouse_rent_per_sqm' => '0',
            'warehouse_months' => '0',
            'other_expenses_rate' => '0',
            'manpower_rate' => '0',
            'assembly_rate' => '0',
            'rd_rate' => '0',
            'facilitation_rate' => '0',
            'tax_lawyer_rate' => '0',
            'opex_rate' => '0',
            'income_tax_rate' => '0',
            'incentive_rate' => '0',
            'importation_vat_rate' => '0.13',
            'sales_vat_rate' => '0.12',
        ],
        'assumptions' => [],
        'cost_items' => [],
    ], $overrides);
}

function makeLegacyProject(array $attributes = []): Project
{
    return Project::query()->create(array_replace([
        'ref_no' => fake()->unique()->bothify('REF-####'),
        'project_name' => 'Manually created project',
        'project_code' => 'PRJ-MANUAL',
        'agency' => 'Department of Education',
        'contract_amount' => 100000,
        'start_date' => null,
        'end_date' => null,
        'status' => 'Ongoing',
        'keystage' => false,
        'ABC' => 120000,
    ], $attributes));
}

it('calculates workbook PPL outputs with safe zero-sales margins and the corrected progressive municipal tax', function () {
    $results = app(ProjectFinancialPlanCalculator::class)->calculate([
        'contract_amount' => '112000.00',
        'abc' => '100000.00',
        'factory_cost' => '50000.00',
    ]);

    expect($results['net_sales'])->toBe('100000.00')
        ->and($results['costs']['municipal_tax'])->toBe('2310.00')
        ->and($results['costs']['performance_bond'])->toBe('2058.27')
        ->and($results['gross_profit'])->toBe('45430.89')
        ->and($results['gpm'])->toBe('0.45430888')
        ->and($results['costs']['goods_insurance'])->toBe('200.84')
        ->and($results['importation_vat'])->toBe('6500.00');

    $zeroSales = app(ProjectFinancialPlanCalculator::class)->calculate([
        'contract_amount' => '0', 'abc' => '0', 'factory_cost' => '0',
    ]);

    expect($zeroSales['gpm'])->toBeNull()->and($zeroSales['npm'])->toBeNull();
});

it('uses the workbook bases for every PPL expense and profitability measure', function () {
    $inputs = [
        'contract_amount' => '1120000', 'abc' => '1000000', 'factory_cost' => '500000',
        'pf1_rate' => '0.01', 'pf2_rate' => '0.02', 'pf3_rate' => '0.005',
        'll_commission_rate' => '0.10', 'interest_dst_rate' => '0.02',
        'shipping_containers' => '2', 'rate_per_container' => '5000', 'logistics_rate' => '0.01',
        'warehouse_area' => '10', 'warehouse_rent_per_sqm' => '100', 'warehouse_months' => '2',
        'other_expenses_rate' => '0.01', 'manpower_rate' => '0.01', 'assembly_rate' => '0.0025',
        'rd_rate' => '0.0025', 'facilitation_rate' => '0.0025', 'tax_lawyer_rate' => '0.005',
        'opex_rate' => '0.05', 'income_tax_rate' => '0.02', 'incentive_rate' => '0.15',
        'importation_vat_rate' => '0.13', 'sales_vat_rate' => '0.12',
    ];
    $results = app(ProjectFinancialPlanCalculator::class)->calculate($inputs);

    expect($results['costs']['pf1'])->toBe('11200.00')
        ->and($results['costs']['pf2'])->toBe('22400.00')
        ->and($results['costs']['pf3'])->toBe('5600.00')
        ->and($results['costs']['ll_commission'])->toBe('50000.00')
        ->and($results['costs']['interest_dst_working_capital'])->toBe('20000.00')
        ->and($results['costs']['shipping_brokerage'])->toBe('10000.00')
        ->and($results['costs']['logistics'])->toBe('10000.00')
        ->and($results['costs']['warehouse_rental'])->toBe('2000.00')
        ->and($results['costs']['other_expenses'])->toBe('11200.00')
        ->and($results['costs']['manpower'])->toBe('10000.00')
        ->and($results['costs']['assembly_service_center'])->toBe('2500.00')
        ->and($results['costs']['research_and_development'])->toBe('2500.00')
        ->and($results['costs']['facilitation'])->toBe('2500.00')
        ->and($results['costs']['tax_lawyer_allowance'])->toBe('5000.00')
        ->and($results['costs']['municipal_tax'])->toBe('12870.00')
        ->and($results['costs']['performance_bond'])->toBe('3932.70')
        ->and($results['costs']['goods_insurance'])->toBe('2008.43')
        ->and($results['opex'])->toBe('50000.00')
        ->and($results['income_tax_provision'])->toBe('20000.00')
        ->and($results['incentive'])->toBe('36943.33')
        ->and($results['ppl'])->toBe('209345.55');
});

it('creates a company-scoped standalone draft with saved inputs formulas and source categories', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $payload = financialPlanPayload(['cost_items' => [[
        'label' => 'Courier and document notarization',
        'amount' => '1250.00',
        'source_reference' => 'Supplier quotation Q-24',
    ]]]);

    $this->post(route('finance.plans.store'), $payload)
        ->assertRedirect();

    $plan = ProjectFinancialPlan::query()->sole();
    $version = $plan->versions()->sole();
    expect($plan->company_id)->toBe(auth()->user()->currentCompany()->company_id)
        ->and($plan->status)->toBe('draft')
        ->and($version->version_number)->toBe(1)
        ->and($version->input_data['additional_cost_items'][0]['source_reference'])->toBe('Supplier quotation Q-24')
        ->and($version->calculation_results['net_sales'])->toBe('100000.00')
        ->and($version->costItems()->where('category', 'additional_cost')->value('source_reference'))->toBe('Supplier quotation Q-24');
});

it('requires explicit project-specific financial rates instead of assuming missing values are zero', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $payload = financialPlanPayload();
    unset($payload['inputs']['pf1_rate']);

    $this->post(route('finance.plans.store'), $payload)
        ->assertSessionHasErrors(['inputs.pf1_rate']);

    expect(ProjectFinancialPlan::query()->count())->toBe(0);
});

it('creates later immutable versions instead of overwriting the prior financial estimate', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $plan = ProjectFinancialPlan::query()->sole();
    $originalResult = $plan->versions()->firstOrFail()->calculation_results['ppl'];

    $payload = financialPlanPayload([
        'name' => $plan->name,
        'inputs' => ['contract_amount' => '224000.00'],
        'change_summary' => 'Updated contract estimate',
    ]);
    $this->put(route('finance.plans.update', $plan), $payload)->assertRedirect(route('finance.plans.show', $plan));

    expect($plan->fresh()->versions()->count())->toBe(2)
        ->and($plan->fresh()->versions()->first()->version_number)->toBe(2)
        ->and($plan->fresh()->versions()->reorder('version_number')->first()->calculation_results['ppl'])->toBe($originalResult)
        ->and($plan->fresh()->latestVersion->calculation_results['net_sales'])->toBe('200000.00');
});

it('supports existing project links and optional bidding lots without changing project implementation status', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = makeLegacyProject();
    $bidding = ProjectInformation::query()->create([
        'project_id' => 'BID-'.fake()->unique()->numerify('####'),
        'project_name' => 'Procurement lot group',
        'procuring_entity' => 'Test agency',
        'approved_budget_contract_abc' => 100000,
        'status' => 'Awarded',
    ]);
    $lot = ProjectLot::query()->create(['project_id' => $bidding->id, 'lot_no' => '1']);
    $payload = financialPlanPayload([
        'project_id' => $project->project_id,
        'project_information_id' => $bidding->id,
        'lot_id' => $lot->id,
    ]);

    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    $plan = ProjectFinancialPlan::query()->sole();
    expect($plan->project_id)->toBe($project->project_id)
        ->and($plan->project_information_id)->toBe($bidding->id)
        ->and($plan->lot_id)->toBe($lot->id)
        ->and($plan->project->status)->toBe('Ongoing')
        ->and($plan->bidding->status)->toBe('Awarded');
});

it('keeps historical PPL records and manually created projects independent of financial drafts', function () {
    $historical = PplForm::query()->create([
        'project_code' => 'HIST-1', 'lot_number' => '1', 'project_title' => 'Historical PPL', 'abc' => 250000,
    ]);
    $project = makeLegacyProject();

    expect($historical->fresh()->project_code)->toBe('HIST-1')
        ->and($project->fresh()->status)->toBe('Ongoing')
        ->and(ProjectFinancialPlan::query()->count())->toBe(0);
});

it('validates and persists PPL form fields using the actual database column names', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('ppl_forms.store'), [
        'project_code' => 'PPL-NEW', 'lot_number' => '1', 'project_title' => 'PPL validation case',
        'project_id_no' => 'ID-8', 'region' => 'Region VII', 'bid_opening' => '2026-10-01',
        'abc' => '200000.00', 'lcb_abc' => '190000.00', 'delivery_days_after_production' => '20',
        'pf1_contract_amt' => '1.50', 'year_of_revenue_recognition' => '2026',
    ])->assertRedirect(route('ppl_forms.index'));

    $pplForm = PplForm::query()->sole();
    expect((float) $pplForm->lcb_abc)->toBe(190000.0)
        ->and($pplForm->delivery_days_after_production)->toBe(20)
        ->and((float) $pplForm->pf1_contract_amt)->toBe(1.5);

    $this->post(route('ppl_forms.store'), [
        'project_code' => 'PPL-INVALID', 'lot_number' => '2', 'project_title' => 'Invalid',
        'abc' => '-1', 'delivery_days_after_production' => '-2',
    ])->assertSessionHasErrors(['abc', 'delivery_days_after_production']);
    expect(PplForm::query()->count())->toBe(1);
});

it('denies non-finance users and isolates plans between company workspaces', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $plan = ProjectFinancialPlan::query()->sole();
    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('finance.plans.show', $plan))->assertNotFound();
    $this->put(route('finance.plans.update', $plan), financialPlanPayload())->assertNotFound();

    $this->signInBiddingUser('user', 'MMC');
    $this->get(route('finance.plans.index'))->assertForbidden();
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertForbidden();

});

it('ignores client supplied status changes so a financial draft cannot alter project lifecycle status', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = makeLegacyProject(['status' => 'Ongoing']);
    $payload = financialPlanPayload(['project_id' => $project->project_id]);
    $payload['status'] = 'approved';

    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    expect(ProjectFinancialPlan::query()->sole()->status)->toBe('draft')
        ->and($project->fresh()->status)->toBe('Ongoing');
});

it('snapshots a selected MI factory cost without replacing the manual project-level factory total', function () {
    $this->signInBiddingUser('finance', 'MI');
    $productId = DB::table('mi_products')->insertGetId([
        'main_category' => 'Testing',
        'item_name' => 'Education kit component',
        'created_at' => now(),
        'updated_at' => now(),
    ], 'product_id');
    $product = MI_Product::query()->findOrFail($productId);
    $factoryCost = $product->factoryCosts()->create([
        'factory_name' => 'Factory A',
        'factory_cost' => '1250.00',
        'currency' => 'PHP',
        'effective_date' => '2026-09-01',
        'remarks' => 'Quoted cost',
        'is_primary' => true,
    ]);
    $payload = financialPlanPayload(['inputs' => ['factory_cost_id' => $factoryCost->id]]);

    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    $version = ProjectFinancialPlan::query()->sole()->versions()->sole();
    expect($version->factory_cost_snapshot['id'])->toBe($factoryCost->id)
        ->and($version->factory_cost_snapshot['product_name'])->toBe('Education kit component')
        ->and($version->factory_cost_snapshot['factory_cost'])->toBe('1250.00')
        ->and($version->input_data['inputs']['factory_cost'])->toBe('50000.00');
});

it('imports PPL rows by header name and preserves numeric zero values', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $csv = implode("\n", [
        'PROJECT CODE,LOT #,PROJECT TITLE,PROJECT ID NO.,REGION,BID OPENING,ABC,BIDDER,AUTHORIZED SIGNATORY',
        'PPL-CSV-1,1,CSV PPL Record,ID-CSV,Region IV,2026-10-01,0,Vendor,Authorized Person',
    ]);

    $this->post(route('ppl_forms.import'), ['file' => UploadedFile::fake()->createWithContent('ppl.csv', $csv)])
        ->assertRedirect()
        ->assertSessionHas('success', 'Import completed. Inserted: 1 | Failed: 0');

    $record = PplForm::query()->sole();
    expect($record->project_code)->toBe('PPL-CSV-1')
        ->and((float) $record->abc)->toBe(0.0)
        ->and($record->authorized_signatory)->toBe('Authorized Person');
});

it('shows a company-scoped finance dashboard using the latest version for each plan', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $plan = ProjectFinancialPlan::query()->sole();
    $this->signInBiddingUser('finance', 'MI');
    $this->post(route('finance.plans.store'), financialPlanPayload(['name' => 'MI standalone estimate']))->assertRedirect();

    $this->get(route('finance.dashboard'))
        ->assertOk()
        ->assertViewHas('planCount', 1)
        ->assertViewHas('draftCount', 1)
        ->assertViewHas('recentPlans', fn ($plans): bool => $plans->count() === 1 && $plans->first()->name === 'MI standalone estimate');
});
