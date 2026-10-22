<?php

use App\Models\MI_Product;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\ProjectFinancialPlanCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    $results = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', [
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

    $zeroSales = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', [
        'contract_amount' => '0', 'abc' => '0', 'factory_cost' => '0',
    ]);

    expect($zeroSales['gpm'])->toBeNull()->and($zeroSales['npm'])->toBeNull();
});

it('calculates the tax lawyer allowance from ABC when ABC differs from net sales', function () {
    $results = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', [
        'contract_amount' => '1120000.00',
        'abc' => '750000.00',
        'factory_cost' => '0.00',
        'tax_lawyer_rate' => '0.05',
    ]);

    expect($results['net_sales'])->toBe('1000000.00')
        ->and($results['costs']['tax_lawyer_allowance'])->toBe('37500.00');

    $miResults = app(ProjectFinancialPlanCalculator::class)->calculate('MI', [
        'contract_amount' => '1120000.00',
        'abc' => '750000.00',
        'factory_cost' => '0.00',
        'tax_lawyer_rate' => '0.05',
    ]);

    expect($miResults['net_sales'])->toBe('1000000.00')
        ->and($miResults['costs']['tax_lawyer_allowance'])->toBe('50000.00');

    expect(fn () => app(ProjectFinancialPlanCalculator::class)->calculate('UNKNOWN', []))
        ->toThrow(InvalidArgumentException::class);
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
    $results = app(ProjectFinancialPlanCalculator::class)->calculate('MMC', $inputs);

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

it('resumes a partial financial schema without dropping data and tolerates repeated execution', function (?string $jsonType) {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $migration = require base_path('database/migrations/2026_10_16_074412_create_project_financial_plan_tables.php');
    $migration->down();

    Schema::create('project_financial_plans', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('company_id');
        $table->unsignedInteger('project_id')->nullable();
        $table->foreignId('project_information_id')->nullable();
        $table->foreignId('lot_id')->nullable();
        $table->foreignId('ppl_form_id')->nullable();
        $table->string('name');
        $table->string('status', 24)->default('draft');
        $table->foreignId('created_by');
        $table->foreignId('updated_by');
        $table->timestamps();
    });
    $planId = DB::table('project_financial_plans')->insertGetId([
        'company_id' => $user->currentCompany()->company_id,
        'project_id' => null,
        'project_information_id' => null,
        'lot_id' => null,
        'ppl_form_id' => null,
        'name' => 'Preserved partial record',
        'status' => 'draft',
        'created_by' => $user->user_id,
        'updated_by' => $user->user_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $schema = Schema::getFacadeRoot();
    $schemaProxy = Mockery::mock($schema);
    $schemaProxy->shouldReceive('getColumns')->andReturnUsing(function (string $table) use ($schema, $jsonType): array {
        return array_map(function (array $column) use ($jsonType): array {
            if (in_array($column['name'], ['input_data', 'calculation_results', 'assumptions_snapshot', 'factory_cost_snapshot', 'source_snapshot'], true)) {
                $column['type'] = $jsonType ?? $column['type'];
            }

            return $column;
        }, $schema->getColumns($table));
    });
    Schema::swap($schemaProxy);

    $migration->up();
    $migration->up();

    Schema::table('project_financial_plans', function (Blueprint $table): void {
        $table->dropIndex('project_financial_plans_project_index');
    });
    $preview = DB::connection()->pretend(fn (): mixed => $migration->up());

    expect(DB::table('project_financial_plans')->where('id', $planId)->value('name'))->toBe('Preserved partial record')
        ->and(Schema::hasTable('project_financial_plan_versions'))->toBeTrue()
        ->and(Schema::hasTable('project_financial_plan_cost_items'))->toBeTrue()
        ->and(collect($preview)->pluck('query')->implode("\n"))->toContain('project_financial_plans_project_index')
        ->and(Schema::hasIndex('project_financial_plans', 'project_financial_plans_project_index'))->toBeFalse()
        ->and(Schema::hasIndex('project_financial_plans', ['company_id', 'status', 'updated_at']))->toBeTrue()
        ->and(count(Schema::getForeignKeys('project_financial_plans')))->toBe(7);

    $migration->up();
    expect(Schema::hasIndex('project_financial_plans', 'project_financial_plans_project_index'))->toBeTrue();
})->with([
    'native JSON column metadata' => [null],
    'MariaDB JSON columns reported as longtext' => ['longtext'],
]);

it('rejects incompatible financial JSON columns without losing stored versions', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $version = ProjectFinancialPlan::query()->sole()->versions()->sole();
    $schema = Schema::getFacadeRoot();
    $schemaProxy = Mockery::mock($schema);
    $schemaProxy->shouldReceive('getColumns')->andReturnUsing(function (string $table) use ($schema): array {
        return array_map(function (array $column): array {
            if ($column['name'] === 'input_data') {
                $column['type'] = 'integer';
            }

            return $column;
        }, $schema->getColumns($table));
    });
    Schema::swap($schemaProxy);
    $migration = require base_path('database/migrations/2026_10_16_074412_create_project_financial_plan_tables.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'input_data has type integer (expected json)');

    $this->assertDatabaseHas('project_financial_plan_versions', $version->getRawOriginal());
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

it('fills blank ABC and contract inputs from selected MMC sources and preserves entered values', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $bidding = ProjectInformation::query()->create([
        'project_id' => 'BID-'.fake()->unique()->numerify('####'),
        'project_name' => 'Awarded procurement record',
        'procuring_entity' => 'Test agency',
        'approved_budget_contract_abc' => '250000.00',
        'contract_amount' => '224000.00',
        'status' => 'Awarded',
    ]);
    $pplForm = PplForm::query()->create([
        'project_code' => 'HIST-ABC',
        'lot_number' => '1',
        'project_title' => 'Historical PPL source',
        'abc' => '200000.00',
    ]);
    $payload = financialPlanPayload([
        'project_information_id' => $bidding->id,
        'ppl_form_id' => $pplForm->id,
    ]);
    unset($payload['inputs']['abc'], $payload['inputs']['contract_amount']);

    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    $version = ProjectFinancialPlan::query()->sole()->latestVersion;
    expect($version->input_data['inputs']['abc'])->toBe('250000.00')
        ->and($version->input_data['inputs']['contract_amount'])->toBe('224000.00')
        ->and($version->input_data['source_mapping']['abc'])->toBe([
            'source' => 'bidding_document',
            'record_id' => (string) $bidding->id,
            'value' => '250000.00',
        ])
        ->and($version->input_data['source_mapping']['contract_amount']['source'])->toBe('bidding_document');

    $manualPayload = financialPlanPayload([
        'name' => 'Manually overridden estimate',
        'project_information_id' => $bidding->id,
        'inputs' => ['abc' => '175000.00', 'contract_amount' => '196000.00'],
    ]);
    $this->post(route('finance.plans.store'), $manualPayload)->assertRedirect();

    $manualVersion = ProjectFinancialPlan::query()->where('name', 'Manually overridden estimate')->sole()->latestVersion;
    expect($manualVersion->input_data['inputs']['abc'])->toBe('175000.00')
        ->and($manualVersion->input_data['inputs']['contract_amount'])->toBe('196000.00')
        ->and($manualVersion->input_data['source_mapping'])->toBe([]);

    $project = makeLegacyProject(['ABC' => '180000.00', 'contract_amount' => '168000.00']);
    $projectPayload = financialPlanPayload([
        'name' => 'Implementation source estimate',
        'project_id' => $project->project_id,
    ]);
    unset($projectPayload['inputs']['abc'], $projectPayload['inputs']['contract_amount']);
    $this->post(route('finance.plans.store'), $projectPayload)->assertRedirect();

    $projectVersion = ProjectFinancialPlan::query()->where('name', 'Implementation source estimate')->sole()->latestVersion;
    expect($projectVersion->input_data['inputs']['abc'])->toBe('180000')
        ->and($projectVersion->input_data['inputs']['contract_amount'])->toBe('168000.00')
        ->and($projectVersion->input_data['source_mapping']['abc']['record_id'])->toBe((string) $project->project_id);
});

it('makes all existing MMC PPL records available as financial plan sources without changing them', function () {
    $this->signInBiddingUser('finance', 'MMC');
    foreach (range(1, 174) as $index) {
        PplForm::query()->create([
            'project_code' => 'HIST-'.$index,
            'lot_number' => '1',
            'project_title' => 'Historical PPL '.$index,
            'abc' => 100000,
        ]);
    }

    $this->get(route('finance.plans.create'))
        ->assertOk()
        ->assertViewHas('pplForms', fn ($pplForms): bool => $pplForms->count() === 174);

    expect(PplForm::query()->count())->toBe(174);
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

it('restricts legacy PPL forms to the MMC company context while keeping MI standalone planning available', function () {
    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('ppl_forms.index'))->assertForbidden();
    $this->get(route('ppl_forms.create'))->assertForbidden();

    $this->get(route('finance.plans.create'))
        ->assertOk()
        ->assertViewHas('pplForms', fn ($pplForms): bool => $pplForms->isEmpty());

    $this->post(route('finance.plans.store'), financialPlanPayload(['name' => 'MI standalone draft']))
        ->assertRedirect();

    expect(ProjectFinancialPlan::query()->sole()->company_id)
        ->toBe(auth()->user()->currentCompany()->company_id);
});

it('uses the persisted company policy instead of a submitted company identifier', function (string $companyCode, string $expectedAllowance, string $expectedBase) {
    $this->signInBiddingUser('finance', $companyCode);
    $payload = financialPlanPayload([
        'name' => 'MI tax lawyer calculation policy',
        'inputs' => [
            'contract_amount' => '1120000.00',
            'abc' => '750000.00',
            'tax_lawyer_rate' => '0.05',
        ],
    ]);
    $payload['company_code'] = $companyCode === 'MMC' ? 'MI' : 'MMC';

    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    $plan = ProjectFinancialPlan::query()->sole();
    $version = $plan->latestVersion;
    expect($plan->company_id)->toBe(auth()->user()->currentCompany()->company_id)
        ->and($version->calculation_results['net_sales'])->toBe('1000000.00')
        ->and($version->calculation_results['costs']['tax_lawyer_allowance'])->toBe($expectedAllowance)
        ->and($version->assumptions_snapshot['formula_manifest']['tax_lawyer_allowance'])->toBe('tax_lawyer_rate * '.$expectedBase);
})->with([
    ['MMC', '37500.00', 'ABC'],
    ['MI', '50000.00', 'net_sales'],
]);

it('handles missing optional bidding and project source columns without database errors', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = makeLegacyProject();
    $bidding = ProjectInformation::query()->create([
        'project_id' => 'BID-'.fake()->unique()->numerify('####'),
        'project_name' => 'Optional fields unavailable',
        'procuring_entity' => 'Test agency',
        'approved_budget_contract_abc' => '250000.00',
        'contract_amount' => '224000.00',
        'status' => 'Awarded',
    ]);
    $pplForm = PplForm::query()->create([
        'project_code' => 'HIST-OPTIONAL',
        'lot_number' => '1',
        'project_title' => 'Historical PPL fallback',
        'abc' => '200000.00',
    ]);

    Schema::table('projects', function (Blueprint $table): void {
        $table->dropColumn(['ABC', 'contract_amount']);
    });
    Schema::table('project_information', function (Blueprint $table): void {
        $table->dropColumn('contract_amount');
    });

    $this->get(route('finance.plans.create'))->assertOk();
    $payload = financialPlanPayload([
        'project_id' => $project->project_id,
        'project_information_id' => $bidding->id,
        'ppl_form_id' => $pplForm->id,
    ]);
    unset($payload['inputs']['abc'], $payload['inputs']['contract_amount']);

    $this->from(route('finance.plans.create'))
        ->post(route('finance.plans.store'), $payload)
        ->assertRedirect(route('finance.plans.create'))
        ->assertSessionHasErrors('inputs.contract_amount');

    expect(ProjectFinancialPlan::query()->count())->toBe(0);
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

it('filters MMC PPL monitoring by historical revenue year and linked project status', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = makeLegacyProject(['status' => 'Ongoing']);
    $pplForm = PplForm::query()->create([
        'project_code' => 'PPL-2026',
        'lot_number' => '1',
        'project_title' => 'Monitoring source',
        'abc' => '100000.00',
        'year_of_revenue_recognition' => '2026',
    ]);
    $payload = financialPlanPayload([
        'project_id' => $project->project_id,
        'ppl_form_id' => $pplForm->id,
    ]);
    unset($payload['inputs']['abc']);
    $this->post(route('finance.plans.store'), $payload)->assertRedirect();
    $plan = ProjectFinancialPlan::query()->sole();

    $this->get(route('finance.dashboard', ['year' => '2026', 'status' => 'Ongoing']))
        ->assertOk()
        ->assertSee('ABC')
        ->assertSee('Factory cost')
        ->assertSee('Operating expenses')
        ->assertSee('Net profit after tax')
        ->assertSee('Version / sources')
        ->assertViewHas('monitoringPlans', fn ($plans): bool => $plans->count() === 1 && $plans->first()->is($plan))
        ->assertViewHas('isMmcPplWorkspace', true);
});

it('soft deletes eligible MMC financial plans while retaining source forms and versions', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $pplForm = PplForm::query()->create(['project_code' => 'PPL-KEEP', 'lot_number' => '2', 'project_title' => 'Retained source']);
    $payload = financialPlanPayload(['ppl_form_id' => $pplForm->id]);
    $this->post(route('finance.plans.store'), $payload)->assertRedirect();
    $plan = ProjectFinancialPlan::query()->with('versions')->sole();
    $versionCount = $plan->versions->count();

    $this->get(route('finance.plans.show', $plan))->assertOk();
    $this->get(route('finance.plans.edit', $plan))->assertOk();
    $this->delete(route('finance.plans.destroy', $plan))->assertRedirect(route('finance.dashboard'));

    expect(ProjectFinancialPlan::query()->find($plan->id))->toBeNull()
        ->and(ProjectFinancialPlan::withTrashed()->findOrFail($plan->id)->deleted_by)->toBe(auth()->id())
        ->and(ProjectFinancialPlan::withTrashed()->findOrFail($plan->id)->versions()->count())->toBe($versionCount)
        ->and(PplForm::query()->find($pplForm->id))->not->toBeNull();

    $this->get(route('finance.dashboard'))->assertOk()->assertDontSee('Pre-award profitability estimate');
});

it('rejects MMC plan deletion from unauthorized and MI company contexts', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $plan = ProjectFinancialPlan::query()->sole();

    $this->signInBiddingUser('user', 'MMC');
    $this->delete(route('finance.plans.destroy', $plan))->assertForbidden();

    $this->signInBiddingUser('finance', 'MI');
    $this->delete(route('finance.plans.destroy', $plan))->assertForbidden();
    expect(ProjectFinancialPlan::query()->find($plan->id))->not->toBeNull();
});

it('rejects cross-company and non-draft plan deletion', function () {
    $this->signInBiddingUser('finance', 'MI');
    $this->post(route('finance.plans.store'), financialPlanPayload())->assertRedirect();
    $miPlan = ProjectFinancialPlan::query()->sole();

    $this->signInBiddingUser('finance', 'MMC');
    $this->delete(route('finance.plans.destroy', $miPlan))->assertNotFound();
    $this->post(route('finance.plans.store'), financialPlanPayload(['name' => 'Not eligible']))->assertRedirect();
    $draft = ProjectFinancialPlan::query()->where('name', 'Not eligible')->firstOrFail();
    $draft->update(['status' => 'approved']);
    $this->delete(route('finance.plans.destroy', $draft))->assertStatus(409);
    expect(ProjectFinancialPlan::query()->find($miPlan->id))->not->toBeNull()
        ->and(ProjectFinancialPlan::query()->find($draft->id))->not->toBeNull();
});

it('searches monitoring by plan name and renders long project names with compact actions', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $projectName = str_repeat('A very long MMC project name ', 8);
    $project = makeLegacyProject(['project_name' => $projectName]);
    $this->post(route('finance.plans.store'), financialPlanPayload([
        'name' => 'Searchable monitoring plan',
        'project_id' => $project->project_id,
    ]))->assertRedirect();

    $this->get(route('finance.dashboard', ['search' => 'Searchable monitoring']))
        ->assertOk()
        ->assertSee('Searchable monitoring plan')
        ->assertSee('line-clamp-2')
        ->assertSee('title="'.$projectName.'"', false)
        ->assertSee('Actions')
        ->assertSee('Delete financial plan?');

    $this->get(route('finance.dashboard', ['search' => 'no matching plan']))
        ->assertOk()
        ->assertSee('No financial plans match these monitoring filters.');
});

it('paginates monitoring results while keeping search and filters in the query string', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $userId = auth()->id();
    $companyId = auth()->user()->currentCompany()->company_id;
    foreach (range(1, 26) as $number) {
        ProjectFinancialPlan::query()->create([
            'company_id' => $companyId,
            'name' => 'Pageable plan '.$number,
            'status' => 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    $this->get(route('finance.dashboard', ['search' => 'Pageable plan', 'status' => 'draft', 'page' => 2]))
        ->assertOk()
        ->assertViewHas('monitoringPlans', fn ($plans): bool => $plans->count() === 1
            && $plans->currentPage() === 2
            && $plans->total() === 26
            && str_contains($plans->url(1), 'search=Pageable%20plan')
            && str_contains($plans->url(1), 'status=draft'));
});

it('exports the MMC monitoring report with saved calculations version and source references', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $project = makeLegacyProject(['project_name' => 'Report source project']);
    $pplForm = PplForm::query()->create([
        'project_code' => 'PPL-REPORT',
        'lot_number' => '4',
        'project_title' => 'Report source project',
        'abc' => '100000.00',
        'year_of_revenue_recognition' => '2026',
    ]);
    $payload = financialPlanPayload([
        'project_id' => $project->project_id,
        'ppl_form_id' => $pplForm->id,
    ]);
    unset($payload['inputs']['abc']);
    $this->post(route('finance.plans.store'), $payload)->assertRedirect();

    $response = $this->get(route('finance.plans.monitoring.export', ['year' => '2026', 'status' => 'Ongoing']))
        ->assertOk()
        ->assertDownload('mmc-ppl-monitoring.csv');

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();
    $csvRows = array_map('str_getcsv', array_filter(explode("\n", str_replace("\r", '', $csv))));
    $headers = array_map(fn (string $header): string => ltrim($header, "\xEF\xBB\xBF"), $csvRows[0]);
    $row = array_combine($headers, $csvRows[1]);

    expect($row['Project'])->toBe('Report source project')
        ->and($row['Lot'])->toBe('4')
        ->and($row['Revenue year'])->toBe('2026')
        ->and($row['ABC'])->toBe('120000.00')
        ->and($row['Contract amount'])->toBe('112000.00')
        ->and($row['Net sales'])->toBe('100000.00')
        ->and($row['Factory cost'])->toBe('50000.00')
        ->and($row['Operating expenses'])->toBe('0.00')
        ->and($row['Version'])->toBe('1')
        ->and($row['Project ID'])->toBe((string) $project->project_id)
        ->and($row['PPL Form ID'])->toBe((string) $pplForm->id);

    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('finance.plans.monitoring.export'))->assertForbidden();
});
