<?php

use App\Models\Company;
use App\Models\Lot as LegacyLot;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectFinancialPlanVersion;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\MmcPplSourceLinkService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function pplManagementFixture(): array
{
    $company = Company::query()->where('code', 'MMC')->firstOrFail();
    $project = Project::query()->create([
        'company_id' => $company->company_id,
        'project_name' => 'Unique MMC Source', 'project_code' => 'PPL-MANAGE',
        'ref_no' => 'PPL-MANAGE-REF', 'agency' => 'Test Agency',
        'contract_amount' => '800.00', 'status' => 'Implementation', 'ABC' => '900.00',
    ]);
    $bidding = ProjectInformation::query()->create([
        'company_id' => $company->company_id, 'project_id' => (string) $project->getKey(),
        'project_name' => 'Unique MMC Source', 'project_code' => 'PPL-MANAGE',
        'approved_budget_contract_abc' => '950.00', 'status' => 'Draft',
    ]);
    $lot = ProjectLot::query()->create(['project_id' => $bidding->getKey(), 'lot_no' => 'Lot 2']);
    $legacyLot = LegacyLot::query()->create(['project_id' => $project->getKey(), 'lot_name' => '2', 'contract_no' => '']);
    $form = PplForm::query()->create([
        'company_id' => $company->company_id, 'project_code' => 'PPL-MANAGE',
        'project_title' => 'Unique MMC Source', 'lot_number' => '2', 'abc' => '777.00',
    ]);

    return compact('company', 'project', 'bidding', 'lot', 'legacyLot', 'form');
}

it('shows the dedicated PPL sidebar and preserves existing form routes', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $this->get(route('finance.dashboard'))->assertOk()->assertSee('PPL Management')->assertDontSee('Project Source Mapping');
    $this->get(route('finance.ppl.dashboard'))->assertOk()
        ->assertSee('Overview')->assertSee('Financial Planning')->assertSee('Monitoring & Intelligence')
        ->assertSee(route('finance.ppl-import.index'), false)
        ->assertSee(route('finance.ppl.sources.index'), false)
        ->assertSee(route('finance.plans.index'), false)
        ->assertSee(route('finance.ppl.analytics', ['view' => 'history']).'#history', false)
        ->assertSee(route('finance.ppl.dashboard', ['view' => 'monitoring']).'#monitoring', false)
        ->assertSee(route('finance.ppl.analytics', ['view' => 'variance']).'#variance', false)
        ->assertSee(route('finance.ppl.analytics', ['view' => 'reports']).'#reports', false)
        ->assertSee(route('finance.ppl.analytics'), false)
        ->assertSee('AI Analytics & Chart Studio')
        ->assertSee('Back to Finance Dashboard')
        ->assertSee(route('company.switch'), false)
        ->assertSee('name="company_id"', false)
        ->assertSee('overflow-y-auto')
        ->assertSee('lg:hidden');
    $this->get(route('ppl_forms.index'))->assertOk()
        ->assertSee('PPL Forms')->assertSee('PPL Management')
        ->assertSee('aria-current="page"', false)
        ->assertSee('bg-emerald-50/80 text-emerald-600');
    $this->get(route('finance.ppl-import.index'))->assertOk();
});

it('keeps the source review read only when the source link schema is absent', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $form = PplForm::query()->create(['project_code' => 'HIST', 'project_title' => 'Historical', 'lot_number' => '1', 'abc' => '500.00']);
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('mmc_ppl_source_links');
    Schema::enableForeignKeyConstraints();

    $this->get(route('finance.ppl.sources.index'))->assertOk()->assertSee('Review is read-only')->assertSee('Read-only');
    $this->post(route('finance.ppl.sources.confirm', $form->id))->assertSessionHasErrors('schema');
    expect((float) $form->fresh()->abc)->toBe(500.0);
});

it('shows a disabled workbook import when the staging tables are absent', function () {
    $this->signInBiddingUser('finance', 'MMC');
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('mmc_ppl_import_rows');
    Schema::enableForeignKeyConstraints();

    $this->get(route('finance.ppl-import.index'))->assertOk()
        ->assertSee('Workbook import is unavailable')
        ->assertSee('disabled', false);
});

it('keeps PPL forms readable while financial-plan schema is incomplete', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $form = PplForm::query()->create(['project_code' => 'LEGACY', 'project_title' => 'Legacy source', 'lot_number' => '1', 'abc' => '500.00']);
    Schema::table('project_financial_plans', fn (Blueprint $table) => $table->dropIndex('pfinancial_company_deleted_index'));
    Schema::table('project_financial_plans', fn (Blueprint $table) => $table->dropColumn('deleted_at'));

    $this->get(route('finance.ppl.dashboard'))->assertOk()->assertSee('Financial-plan schema review required');
    $this->get(route('finance.ppl.analytics'))->assertOk()->assertSee('Financial-plan schema is unavailable');
    $this->get(route('ppl_forms.index'))->assertOk()->assertSee('Legacy source')->assertSee('Financial-plan schema is incomplete');
    $this->get(route('ppl_forms.show', $form->id))->assertOk()->assertSee('Saved plan values and versions cannot be shown');
});

it('requires explicit confirmation of a unique project and lot without changing historical amounts or status', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $records = pplManagementFixture();
    $this->get(route('finance.ppl.sources.index', ['search' => $records['form']->id]))
        ->assertOk()->assertSee('Proposed unique match')->assertSee('Confirm Link');
    $this->post(route('finance.ppl.sources.confirm', $records['form']->id))->assertRedirect();

    $link = MmcPplSourceLink::query()->sole();
    expect($link->project_id)->toBe($records['project']->getKey())
        ->and($link->project_information_id)->toBe($records['bidding']->getKey())
        ->and($link->bidding_lot_id)->toBe($records['lot']->getKey())
        ->and((float) $records['form']->fresh()->abc)->toBe(777.0)
        ->and($records['project']->fresh()->status)->toBe('Implementation')
        ->and(Project::query()->count())->toBe(1)
        ->and(ProjectLot::query()->count())->toBe(1);

    $this->get(route('ppl_forms.index'))->assertOk()->assertSee('Implementation');
    $this->post(route('finance.ppl.sources.confirm', $records['form']->id))->assertSessionHasErrors('source');
});

it('marks ambiguous source records for review and never links them automatically', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $records = pplManagementFixture();
    ProjectInformation::query()->create([
        'company_id' => $records['company']->company_id, 'project_id' => 'OTHER',
        'project_name' => 'Unique MMC Source', 'project_code' => 'PPL-MANAGE',
        'approved_budget_contract_abc' => '950.00', 'status' => 'Draft',
    ]);

    $this->get(route('finance.ppl.sources.index'))->assertOk()->assertSee('Needs Review')->assertSee('Link Project');
    $this->post(route('finance.ppl.sources.confirm', $records['form']->id))->assertSessionHasErrors('source');
    expect(MmcPplSourceLink::query()->count())->toBe(0);
});

it('excludes MI owned bidding and project candidates from MMC source proposals', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $records = pplManagementFixture();
    $mi = Company::query()->create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);
    $records['project']->forceFill(['company_id' => $mi->company_id])->save();
    $records['bidding']->forceFill(['company_id' => $mi->company_id])->save();

    $row = collect(app(MmcPplSourceLinkService::class)->dryRunHistoricalLinks()['rows'])->firstWhere('ppl_form_id', $records['form']->id);
    expect($row['status'])->toBe('unmatched')
        ->and($row['candidate_projects'])->toBe([])
        ->and($row['candidate_biddings'])->toBe([]);
    $this->post(route('finance.ppl.sources.confirm', $records['form']->id))->assertSessionHasErrors('source');
    expect(MmcPplSourceLink::query()->count())->toBe(0);
});

it('uses only saved MMC versions for analytics and blocks MI access', function () {
    $this->signInBiddingUser('finance', 'MMC');
    $records = pplManagementFixture();
    $this->get(route('finance.ppl.analytics'))->assertOk()
        ->assertSee('Without one usable saved plan')->assertSee('No saved plan data');
    $this->get(route('ppl_forms.index'))->assertOk()->assertSee('777.00')->assertSee('Unlinked');

    $this->signInBiddingUser('finance', 'MI');
    $this->get(route('finance.ppl.dashboard'))->assertForbidden();
    $this->get(route('finance.ppl.sources.index'))->assertForbidden();
    $this->get(route('finance.ppl.analytics'))->assertForbidden();
    $this->post(route('finance.ppl.sources.confirm', $records['form']->id))->assertForbidden();
    expect(ProjectFinancialPlan::query()->count())->toBe(0);
});

it('distinguishes a saved zero result from a missing plan and calculates version variance from snapshots', function () {
    $user = $this->signInBiddingUser('finance', 'MMC');
    $form = PplForm::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'project_code' => 'ZERO', 'project_title' => 'Saved zero result', 'lot_number' => '1',
        'abc' => '100.00', 'year_of_revenue_recognition' => '2026',
    ]);
    PplForm::query()->create([
        'company_id' => $user->currentCompany()->company_id,
        'project_code' => 'MISSING', 'project_title' => 'Missing plan', 'lot_number' => '2',
        'abc' => '200.00', 'year_of_revenue_recognition' => '2027',
    ]);
    $plan = ProjectFinancialPlan::query()->create([
        'company_id' => $user->currentCompany()->company_id, 'ppl_form_id' => $form->id,
        'name' => 'Zero plan', 'status' => 'draft', 'created_by' => $user->getKey(), 'updated_by' => $user->getKey(),
    ]);
    foreach ([1 => '10.00', 2 => '0.00'] as $versionNumber => $ppl) {
        ProjectFinancialPlanVersion::query()->create([
            'project_financial_plan_id' => $plan->id, 'version_number' => $versionNumber,
            'status' => 'draft', 'input_data' => [],
            'calculation_results' => ['abc' => '100.00', 'net_sales' => '90.00', 'ppl' => $ppl],
            'created_by' => $user->getKey(),
        ]);
    }

    $this->get(route('finance.ppl.analytics'))->assertOk()
        ->assertViewHas('summary', fn (array $summary): bool => $summary['plan_count'] === 1 && $summary['amounts']['ppl'] === '0.00')
        ->assertSee('Without one usable saved plan')->assertSee('₱-10.00')->assertSee('2026')
        ->assertDontSee('2027');
    expect((float) $form->fresh()->abc)->toBe(100.0);
});
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
