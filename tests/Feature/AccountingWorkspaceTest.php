<?php

use App\Console\Commands\GrantMIAccountingAccess;
use App\Models\BudgetRelease;
use App\Models\FinancialActivity;
use App\Services\AccountingWorkspace;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;
use Tests\MIBudgetFixtureFactory;
use Tests\MILiquidationFixtureFactory;
use Tests\MITravelFixtureFactory;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

it('provisions only explicitly requested read-only Accounting access idempotently', function () {
    $accountant = $this->miUser('accounting');
    $accountant->syncPermissions([]);
    $this->app->make(Kernel::class)->registerCommand(app(GrantMIAccountingAccess::class));
    $this->artisan('mi:accounting-access', ['email' => $accountant->email, '--read-only' => true])->assertSuccessful();
    $this->artisan('mi:accounting-access', ['email' => $accountant->email, '--read-only' => true])->assertSuccessful();
    expect($accountant->fresh()->getAllPermissions())->toHaveCount(6);
    expect($accountant->fresh()->can('mi.budget.release'))->toBeFalse();
    expect($accountant->fresh()->can('mi.budget.approve'))->toBeFalse();
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.dashboard'))->assertOk();
});

it('refuses implicit access employee escalation and unsupported authority grants', function () {
    $employee = $this->miUser();
    $accountant = $this->miUser('accounting');
    $accountant->syncPermissions([]);
    $this->app->make(Kernel::class)->registerCommand(app(GrantMIAccountingAccess::class));
    $this->artisan('mi:accounting-access', ['email' => $accountant->email])->assertFailed();
    $this->artisan('mi:accounting-access', ['email' => $employee->email, '--read-only' => true])->assertFailed();
    $this->artisan('mi:accounting-access', ['email' => $accountant->email, '--permission' => ['mi.budget.approve']])->assertFailed();
    expect($accountant->fresh()->getAllPermissions())->toHaveCount(0);
    expect($employee->fresh()->hasRole('accounting'))->toBeFalse();
});

it('indexes private receipts and itemized expenses without exposing foreign evidence', function () {
    Storage::fake('local');
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    $foreignOwner = $this->miUser('user', 'MMC');
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey(), 'title' => 'Authorized evidence']);
    $foreign = MILiquidationFixtureFactory::new()->create(['prepared_by' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey(), 'title' => 'Foreign evidence']);
    $path = 'private/liquidations/receipts/accounting-fixture.png';
    Storage::disk('local')->put($path, 'Private receipt fixture');
    $item = $report->items()->create($this->ordinaryPayload()['items'][0] + ['line_no' => 1, 'ref_no' => 'AUTHORIZED-RECEIPT', 'receipt_image' => $path]);
    $foreignItem = $foreign->items()->create($this->ordinaryPayload()['items'][0] + ['line_no' => 1, 'ref_no' => 'FOREIGN-RECEIPT', 'receipt_image' => $path]);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.workspace', 'documents'))->assertOk()->assertSee('AUTHORIZED-RECEIPT')->assertDontSee('FOREIGN-RECEIPT')->assertSee(route('liquidation.receipt', $item));
    $this->get(route('liquidation.receipt', $item))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('liquidation.receipt', $foreignItem))->assertForbidden();
    $this->get(route('accounting.mi.workspace', 'expenses'))->assertOk()->assertViewHas('expenses', fn ($items) => $items->total() === 1)->assertDontSee('Foreign evidence');
    $this->get(route('accounting.mi.workspace', ['section' => 'documents', 'reference' => 'No match']))->assertViewHas('documents', fn ($items) => $items->total() === 0);
    expect(Storage::disk('local')->exists($path))->toBeTrue();
});

it('requires authentication accounting role and dashboard permission independently', function () {
    $this->get(route('accounting.mi.home'))->assertRedirect(route('login'));
    $employee = $this->miUser();
    $this->signInMI($employee);
    $this->get(route('accounting.mi.dashboard'))->assertForbidden();

    $accountant = $this->miUser('accounting');
    $accountant->syncPermissions([]);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.dashboard'))->assertForbidden();
    $this->get(route('accounting.mi.workspace', 'budgets'))->assertForbidden();
});

it('shows authorized operational counters and only actionable records in My Tasks', function () {
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    $approved = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved']);
    $ready = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'noted_at' => now(), 'noted_by' => $accountant->getKey()]);
    $awaiting = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'released', 'released_at' => now()]);
    $pending = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $parent = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $parent->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'submitted']);
    $foreignOwner = $this->miUser('user', 'MMC');
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey(), 'status' => 'approved']);
    $unknown = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'company_id' => null, 'status' => 'approved']);
    $this->signInMI($accountant);

    $this->get(route('accounting.mi.home'))->assertOk()->assertSee('Accounting Control Center')
        ->assertViewHas('counters', fn ($counts) => $counts['budget-review'] === 1 && $counts['releases'] === 1 && $counts['acknowledgment'] === 1 && $counts['liquidation-review'] === 1 && $counts['tasks'] === 3)
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 3)
        ->assertDontSee($foreign->control_id)->assertDontSee($unknown->control_id)->assertDontSee($pending->control_id);
    $this->get(route('accounting.mi.workspace', 'releases'))->assertOk()->assertSee($ready->control_id)->assertDontSee($approved->control_id);
    $this->get(route('accounting.mi.workspace', 'acknowledgment'))->assertOk()->assertSee($awaiting->control_id);
    $this->get(route('accounting.mi.budgets.show', $foreign))->assertForbidden();
    $this->get(route('accounting.mi.budgets.show', $unknown))->assertForbidden();
    $this->get(route('accounting.mi.travel.show', $travel))->assertOk()->assertSee('Actual expenses');
});

it('separates viewing from review release and manager approval capabilities', function () {
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    $accountant->revokePermissionTo(['mi.budget.review', 'mi.budget.release', 'mi.liquidation.review']);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'noted_at' => now(), 'noted_by' => $owner->getKey()]);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.budgets.show', $budget))->assertOk()->assertDontSee('Confirm unquantified release');
    $this->post(route('budget_requests.release', $budget))->assertForbidden();
    $this->post(route('budget_requests.approve', $budget))->assertForbidden();
    $this->get(route('accounting.mi.workspace', 'tasks'))->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
    expect($budget->fresh()->status)->toBe('approved');
});

it('does not expose mismatched travel parents through lists counts or detail', function () {
    $accountant = $this->miUser('accounting');
    $foreignOwner = $this->miUser('user', 'MMC');
    $parent = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $parent->getKey(), 'liquidated_by' => $foreignOwner->getKey()]);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.workspace', 'liquidations'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 0)->assertDontSee($parent->control_id);
    $this->get(route('accounting.mi.travel.show', $travel))->assertForbidden();
});

it('groups authoritative release amounts by currency with exact cents and excludes foreign payments', function () {
    $this->freezeTime();
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    $parent = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    foreach ([['USD', '0.10'], ['USD', '0.20'], ['VND', '200.00']] as [$currency, $amount]) {
        BudgetRelease::create(['budget_request_id' => $parent->getKey(), 'company_id' => $parent->company_id, 'amount' => $amount, 'currency' => $currency, 'released_at' => now(), 'reference_no' => 'Payment fixture']);
    }
    $foreignOwner = $this->miUser('user', 'MMC');
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey()]);
    BudgetRelease::create(['budget_request_id' => $foreign->getKey(), 'company_id' => $foreign->company_id, 'amount' => '1000.00', 'currency' => 'USD', 'released_at' => now(), 'reference_no' => 'Foreign payment']);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.dashboard'))->assertOk()->assertViewHas('summary', fn ($summary) => (string) $summary->firstWhere('currency', 'USD')->amount === '0.30' && (string) $summary->firstWhere('currency', 'VND')->amount === '200.00')
        ->assertSee('0.30')->assertSee('200.00')->assertDontSee('1000.00');
    $this->get(route('accounting.mi.workspace', 'transactions'))->assertOk()->assertSee('Payment fixture')->assertDontSee('Foreign payment');
});

it('filters queues before pagination and rejects invalid amounts and dates', function () {
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Operations', 'budget_total' => '25.50']);
    MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Design', 'budget_total' => '5.00']);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.workspace', ['section' => 'budgets', 'department' => 'Operations', 'amount_min' => '25.00']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
    $this->getJson(route('accounting.mi.workspace', ['section' => 'budgets', 'amount_min' => '1e3']))->assertUnprocessable()->assertJsonValidationErrors('amount_min');
    $this->getJson(route('accounting.mi.workspace', ['section' => 'budgets', 'date_from' => '2026-10-06', 'date_to' => '2026-10-01']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
    $this->get(route('accounting.mi.workspace', 'invalid'))->assertNotFound();
});

it('renders every permitted sidebar destination without creating financial records', function () {
    $accountant = $this->miUser('accounting');
    $this->signInMI($accountant);
    foreach (array_keys(AccountingWorkspace::SECTIONS) as $section) {
        $this->get(route('accounting.mi.workspace', $section))->assertOk();
    }
    $this->assertDatabaseCount('budget_releases', 0);
    $this->assertDatabaseCount('financial_settlements', 0);
    $this->assertDatabaseCount('financial_activities', 0);
});

it('retains authorized archived activity and denies unrelated company journal entries', function () {
    $owner = $this->miUser();
    $accountant = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    FinancialActivity::create(['company_id' => $budget->company_id, 'record_type' => 'budget_requests', 'record_id' => $budget->getKey(), 'event' => 'budget_submitted', 'actor_name_snapshot' => 'Archived actor fixture', 'created_at' => now()]);
    $budget->delete();
    $foreignOwner = $this->miUser('user', 'MMC');
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey()]);
    FinancialActivity::create(['company_id' => $foreign->company_id, 'record_type' => 'budget_requests', 'record_id' => $foreign->getKey(), 'event' => 'budget_submitted', 'actor_name_snapshot' => 'Foreign journal actor', 'created_at' => now()]);
    $this->signInMI($accountant);
    $this->get(route('accounting.mi.workspace', 'audit'))->assertOk()->assertSee('Archived actor fixture')->assertDontSee('Foreign journal actor');
});
