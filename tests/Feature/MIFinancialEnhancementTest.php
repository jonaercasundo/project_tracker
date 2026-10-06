<?php

use App\Console\Commands\MigrateMIReceiptsPrivate;
use App\Models\BudgetRelease;
use App\Models\BudgetRequest;
use App\Models\FinancialActivity;
use App\Models\FinancialSettlement;
use App\Models\Liquidation;
use App\Models\MI_Liquidation;
use App\Services\MiFinancialWorkflowService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;
use Tests\MIBudgetFixtureFactory;
use Tests\MILiquidationFixtureFactory;
use Tests\MITravelFixtureFactory;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

it('filters travel reports while summaries remain restricted to the owner and company', function () {
    $owner = $this->miUser();
    $other = $this->miUser();
    $foreignCompany = $this->miUser('user', 'MMC')->companies()->first()->getKey();
    $matchingBudget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Field operations']);
    $otherBudget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Design']);
    $matching = MITravelFixtureFactory::new()->create(['budget_request_id' => $matchingBudget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted']);
    MITravelFixtureFactory::new()->create(['budget_request_id' => $otherBudget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'submitted']);
    foreach ([[$other->getKey(), $matchingBudget->company_id], [$owner->getKey(), $foreignCompany]] as [$employeeId, $companyId]) {
        $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $employeeId, 'company_id' => $companyId, 'department' => 'Field operations']);
        MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $employeeId, 'company_id' => $companyId, 'status' => 'noted']);
    }
    $this->signInMI($owner);

    $this->get(route('travel_liquidation.index', ['search' => 'Field', 'status' => 'noted']))
        ->assertOk()
        ->assertViewHas('liquidations', fn ($rows): bool => $rows->total() === 1 && $rows->first()->getKey() === $matching->getKey())
        ->assertViewHas('statusCounts', fn ($counts): bool => (int) $counts->sum() === 2 && (int) $counts->get('noted') === 1)
        ->assertSee('Awaiting approval')
        ->assertSee(route('travel_liquidation.show', $matching), false);
    $this->get(route('travel_liquidation.index', ['search' => $matchingBudget->control_id]))
        ->assertViewHas('liquidations', fn ($rows): bool => $rows->total() === 1);
});

it('shows travel guidance for empty and filtered lists and rejects invalid filters', function () {
    $this->signInMI($this->miUser());

    $this->get(route('travel_liquidation.index'))->assertOk()->assertSee('Your first trip starts with a budget')->assertSee(route('budget_requests.index'), false);
    $this->get(route('travel_liquidation.index', ['search' => 'missing']))->assertOk()->assertSee('No reports match your filters')->assertSee('Clear filters');
    $this->get(route('travel_liquidation.index', ['search' => '<script>alert(1)</script>']))->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    $this->getJson(route('travel_liquidation.index', ['status' => 'forged']))->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->getJson(route('travel_liquidation.index', ['search' => str_repeat('x', 101)]))->assertUnprocessable()->assertJsonValidationErrors('search');
});

it('preserves travel search and status in pagination links', function () {
    $owner = $this->miUser();
    for ($index = 0; $index < 16; $index++) {
        $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Field operations']);
        MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'submitted']);
    }
    $this->signInMI($owner);

    $this->get(route('travel_liquidation.index', ['search' => 'Field', 'status' => 'submitted']))
        ->assertOk()->assertViewHas('liquidations', fn ($rows): bool => $rows->total() === 16 && str_contains($rows->nextPageUrl(), 'search=Field') && str_contains($rows->nextPageUrl(), 'status=submitted'));
});

function miGrant($user, string $permission): void
{
    $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
}

it('stamps new budget and travel with validated record company and records real history', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $this->post(route('budget_requests.store'), $this->budgetPayload())->assertRedirect();
    $budget = BudgetRequest::sole();
    expect($budget->company_id)->toBe($owner->currentCompany()->getKey());
    expect($budget->activities()->pluck('event')->all())->toBe(['budget_created', 'budget_submitted']);
    $budget->update(['status' => 'in_progress']);
    $this->post(route('travel_liquidation.store'), $this->travelPayload($budget))->assertRedirect();
    $travel = Liquidation::sole();
    expect($travel->company_id)->toBe($budget->company_id);
    expect($travel->activities()->pluck('event')->all())->toBe(['liquidation_created', 'liquidation_submitted']);
});

it('denies historical unknown and foreign company records even to their owner or accountant', function (?string $companyCode, string $role) {
    $owner = $this->miUser();
    $companyId = $companyCode ? $this->miUser('user', $companyCode)->companies()->first()->getKey() : null;
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'company_id' => $companyId, 'status' => 'approved']);
    $actor = $role === 'user' ? $owner : $this->miUser('accounting');
    $this->signInMI($actor);
    $this->get(route($role === 'user' ? 'budget_requests.show' : 'budget_requests.processing', $budget))->assertForbidden();
    $this->post(route('budget_requests.note', $budget))->assertForbidden();
    expect($budget->fresh()->noted_at)->toBeNull();
    if ($role === 'user') {
        $this->get(route('budget_requests.index'))->assertOk()->assertViewHas('budgetRequests', fn ($rows) => $rows->total() === 0);
    } else {
        $this->get(route('budget_requests.index'))->assertForbidden();
    }
})->with([[null, 'user'], ['MMC', 'user'], [null, 'accounting'], ['MMC', 'accounting']]);

it('allows only explicitly permitted nonrequester budget approval and preserves stamps and one event', function () {
    $owner = $this->miUser();
    $approver = $this->miUser('Manager');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($approver);
    $this->post(route('budget_requests.approve', $budget))->assertForbidden();
    miGrant($approver, 'mi.budget.approve');
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $this->post(route('budget_requests.approve', $budget))->assertRedirect();
    $approvedAt = $budget->fresh()->approved_at;
    $this->post(route('budget_requests.approve', $budget))->assertUnprocessable();
    expect($budget->fresh()->approved_at->equalTo($approvedAt))->toBeTrue();
    expect($budget->fresh()->approved_by)->toBe($approver->getKey());
    expect($budget->activities()->where('event', 'budget_approved')->count())->toBe(1);
    miGrant($owner, 'mi.budget.approve');
    $ownPending = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($owner);
    $this->post(route('budget_requests.approve', $ownPending))->assertForbidden();
    expect($ownPending->fresh()->approved_at)->toBeNull();
});

it('allows explicit final travel permission without automatically closing or settling and denies self approval', function () {
    $owner = $this->miUser();
    $approver = $this->miUser('Manager');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted', 'noted_at' => now()]);
    miGrant($owner, 'mi.travel.approve');
    $this->signInMI($owner);
    $this->post(route('travel_liquidation.approve', $travel))->assertForbidden();
    miGrant($approver, 'mi.travel.approve');
    $this->signInMI($approver);
    $this->get(route('travel_liquidation.processing', $travel))->assertOk();
    $this->post(route('travel_liquidation.approve', $travel))->assertRedirect();
    expect($travel->fresh()->status)->toBe('approved');
    expect($budget->fresh()->status)->toBe('in_progress');
    expect($travel->fresh()->settlement)->toBeNull();
    $this->post(route('travel_liquidation.approve', $travel))->assertUnprocessable();
    expect($travel->activities()->where('event', 'liquidation_approved')->count())->toBe(1);
});

it('records actual releases independently of requested totals with exact actor and history', function () {
    config(['mi_financial.release_recording_enabled' => true, 'mi_financial.currencies' => ['VND']]);
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'budget_total' => '100.00', 'noted_by' => $actor->getKey(), 'noted_at' => now()]);
    $this->signInMI($actor);
    $payment = ['amount' => '75.25', 'currency' => 'VND', 'payment_method' => 'bank', 'reference_no' => 'PAY-1', 'released_by' => $owner->getKey()];
    $this->post(route('budget_requests.release', $budget), $payment)->assertRedirect();
    expect($budget->fresh()->budget_total)->toBe('100.00');
    $release = BudgetRelease::sole();
    expect($release->amount)->toBe('75.25');
    expect($release->released_by)->toBe($actor->getKey());
    expect($release->company_id)->toBe($budget->company_id);
    expect(FinancialActivity::sole()->amount)->toBe('75.25');
    expect(FinancialActivity::sole()->event)->toBe('funds_released');
    $this->post(route('budget_requests.release', $budget), $payment)->assertUnprocessable();
    $this->assertDatabaseCount('budget_releases', 1);
});

it('rejects unconfirmed release rules invalid currency bad decimals and configured limits', function (array $config, string $amount, string $currency) {
    config(['mi_financial.release_recording_enabled' => true, 'mi_financial.currencies' => ['VND']] + $config);
    config($config);
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'noted_by' => $actor->getKey(), 'noted_at' => now()]);
    $this->signInMI($actor);
    $this->postJson(route('budget_requests.release', $budget), ['amount' => $amount, 'currency' => $currency, 'payment_method' => 'bank', 'reference_no' => 'PAY'])->assertUnprocessable();
    expect($budget->fresh()->released_at)->toBeNull();
    $this->assertDatabaseCount('budget_releases', 0);
    $this->assertDatabaseCount('financial_activities', 0);
})->with([
    [[], '1e2', 'VND'], [[], '1.001', 'VND'], [[], '-1', 'VND'], [[], '10000000000', 'VND'],
    [[], '1.00', 'USD'], [['mi_financial.release_maximum' => '0.99'], '1.00', 'VND'],
    [['mi_financial.release_recording_enabled' => false], '1.00', 'VND'],
]);

it('keeps legacy release confirmation explicitly unquantified', function () {
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'noted_by' => $actor->getKey(), 'noted_at' => now(), 'budget_total' => '100.00']);
    $this->signInMI($actor);
    $this->post(route('budget_requests.release', $budget))->assertRedirect();
    $this->assertDatabaseCount('budget_releases', 0);
    expect(FinancialActivity::sole()->event)->toBe('release_confirmed_unquantified');
    expect(FinancialActivity::sole()->amount)->toBeNull();
});

it('records exact settlement payments and requires zero outstanding and explicit closure authority', function (string $expenses, string $returned, string $reimbursed, string $balance) {
    config(['mi_financial.settlement_recording_enabled' => true, 'mi_financial.closure_enabled' => true, 'mi_financial.currencies' => ['VND']]);
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    miGrant($actor, 'mi.travel.settle');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $budget->releases()->create(['company_id' => $budget->company_id, 'amount' => '100.00', 'currency' => 'VND', 'released_by' => $actor->getKey(), 'released_at' => now()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'approved', 'actual_total' => $expenses, 'approved_at' => now()]);
    $this->signInMI($actor);
    $payment = ['employee_return_amount' => $returned, 'company_reimbursement_amount' => $reimbursed, 'currency' => 'VND', 'settlement_method' => 'bank', 'reference_no' => 'SET-1', 'expense_amount' => '999'];
    $this->post(route('travel_liquidation.settlement', $travel), $payment)->assertRedirect();
    $settlement = FinancialSettlement::sole();
    expect($settlement->outstanding_balance)->toBe($balance);
    expect($settlement->expense_amount)->toBe($expenses);
    expect($settlement->settled_by)->toBe($actor->getKey());
    expect($settlement->reference_no)->toBe('SET-1');
    $this->post(route('travel_liquidation.settlement', $travel), $payment)->assertUnprocessable();
    $this->post(route('travel_liquidation.close', $travel))->assertForbidden();
    miGrant($actor, 'mi.travel.close');
    $response = $this->post(route('travel_liquidation.close', $travel));
    if ($balance === '0.00') {
        $response->assertRedirect();
        expect($travel->fresh()->status)->toBe('closed');
        expect($budget->fresh()->status)->toBe('liquidated');
        expect($travel->activities()->pluck('event')->all())->toBe(['settlement_recorded', 'closed']);
    } else {
        $response->assertUnprocessable();
        expect($travel->fresh()->status)->toBe('approved');
    }
})->with([
    ['75.25', '24.75', '0.00', '0.00'], ['100.00', '0.00', '0.00', '0.00'],
    ['125.25', '0.00', '25.25', '0.00'], ['75.25', '0.00', '0.00', '24.75'],
    ['125.25', '0.00', '0.00', '-25.25'], ['99.99', '0.00', '0.00', '0.01'],
]);

it('denies settlement while policy is unconfirmed and rejects missing release evidence when enabled', function () {
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    miGrant($actor, 'mi.travel.settle');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'approved']);
    $this->signInMI($actor);
    $payment = ['employee_return_amount' => '0.00', 'company_reimbursement_amount' => '0.00', 'currency' => 'VND', 'settlement_method' => 'bank', 'reference_no' => 'SET'];
    $this->post(route('travel_liquidation.settlement', $travel), $payment)->assertForbidden();
    config(['mi_financial.settlement_recording_enabled' => true, 'mi_financial.currencies' => ['VND']]);
    $this->post(route('travel_liquidation.settlement', $travel), $payment)->assertUnprocessable();
    $this->assertDatabaseCount('financial_settlements', 0);
});

it('preserves actor snapshots and prevents application activity edits and deletion', function () {
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved']);
    $this->signInMI($actor);
    $this->post(route('budget_requests.note', $budget))->assertRedirect();
    $event = FinancialActivity::sole();
    $name = $actor->name;
    $actor->update(['name' => 'New name']);
    $actor->delete();
    expect($event->fresh()->actor_name_snapshot)->toBe($name);
    expect($event->fresh()->actor_role_snapshot)->toBe('accounting');
    expect($event->fresh()->actor_user_id)->toBeNull();
    expect(fn () => $event->update(['event' => 'changed']))->toThrow(LogicException::class);
    expect(fn () => $event->delete())->toThrow(LogicException::class);
    $this->signInMI($owner);
    $this->put('/financial-activities/'.$event->getKey(), ['event' => 'changed'])->assertNotFound();
    $this->delete('/financial-activities/'.$event->getKey())->assertNotFound();
    expect(FinancialActivity::sole()->event)->toBe('accounting_noted');
});

it('rolls back a transition if its activity cannot be persisted', function () {
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved']);
    $this->signInMI($actor);
    FinancialActivity::creating(fn () => throw new RuntimeException('Forced audit failure'));
    try {
        expect(fn () => app(MiFinancialWorkflowService::class)->noteBudget($budget, $actor))->toThrow(RuntimeException::class);
        expect($budget->fresh()->noted_at)->toBeNull();
    } finally {
        FinancialActivity::flushEventListeners();
        FinancialActivity::clearBootedModels();
    }
});

it('migrates legacy receipts with dry run integrity idempotency and authorized retrieval', function () {
    Storage::fake('public');
    Storage::fake('local');
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['company_id' => $owner->currentCompany()->getKey(), 'prepared_by' => $owner->getKey()]);
    $path = 'liquidations/receipts/legacy.png';
    $item = $report->items()->create(['ref_no' => 'LF-fixture-001', 'line_no' => 1, 'item_date' => now(), 'requested_by' => 'name', 'payee' => 'payee', 'expense_type' => 'Travel', 'account_buyer' => 'buyer', 'amount_vnd' => '1.00', 'receipt_image' => $path]);
    Storage::disk('public')->put($path, 'receipt fixture bytes');
    $this->app->make(Kernel::class)->registerCommand(app(MigrateMIReceiptsPrivate::class));
    $options = ['--company' => $report->company_id, '--dry-run' => true];
    $this->artisan('mi:migrate-receipts-private', $options)->assertSuccessful();
    expect($item->fresh()->receipt_image)->toBe($path);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    $options = ['--company' => $report->company_id, '--execute' => true];
    $this->artisan('mi:migrate-receipts-private', $options)->assertSuccessful();
    $private = $item->fresh()->receipt_image;
    expect($private)->toStartWith('private/liquidations/receipts/');
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('local')->get($private))->toBe('receipt fixture bytes');
    $this->get(route('liquidation.receipt', $item))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->artisan('mi:migrate-receipts-private', $options)->assertSuccessful();
    $this->assertDatabaseCount('financial_activities', 1);
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
    $this->signInMI($this->miUser());
    $this->get(route('liquidation.receipt', $item))->assertForbidden();
});

it('keeps unsafe missing and foreign legacy receipt references unchanged', function (string $path, bool $foreign) {
    Storage::fake('public');
    Storage::fake('local');
    $owner = $this->miUser();
    $other = $this->miUser('user', 'MMC');
    $report = MILiquidationFixtureFactory::new()->create(['company_id' => ($foreign ? $other : $owner)->companies()->first()->getKey(), 'prepared_by' => $owner->getKey()]);
    $item = $report->items()->create(['ref_no' => 'LF-fixture-001', 'line_no' => 1, 'item_date' => now(), 'requested_by' => 'name', 'payee' => 'payee', 'expense_type' => 'Travel', 'account_buyer' => 'buyer', 'amount_vnd' => '1.00', 'receipt_image' => $path]);
    $this->app->make(Kernel::class)->registerCommand(app(MigrateMIReceiptsPrivate::class));
    $this->artisan('mi:migrate-receipts-private', ['--company' => $owner->companies()->first()->getKey(), '--execute' => true])->assertExitCode($foreign ? 0 : 1);
    expect($item->fresh()->receipt_image)->toBe($path);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([['../secret.png', false], ['liquidations/receipts/missing.png', false], ['liquidations/receipts/foreign.png', true]]);

it('scopes accounting queues and processing routes to persisted company with filters', function () {
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $foreignOwner = $this->miUser('user', 'MMC');
    $own = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Design']);
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey()]);
    $unknown = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'company_id' => null]);
    $this->signInMI($actor);
    $this->get(route('accounting.mi.dashboard', ['department' => 'Design']))->assertOk()
        ->assertViewHas('workflowQueues', fn ($queues) => $queues['Budget requests awaiting approval'] === 1)
        ->assertViewHas('queueEmployees', fn ($employees) => $employees->pluck('user_id')->all() === [$owner->getKey()])
        ->assertViewHas('budgetQueue', fn ($rows) => $rows->total() === 1)->assertDontSee($foreign->control_id)->assertDontSee($unknown->control_id);
    $this->get(route('budget_requests.processing', $own))->assertOk()->assertSee('No recorded activity history');
    $this->get(route('budget_requests.processing', $foreign))->assertForbidden();
    $this->get(route('budget_requests.processing', $unknown))->assertForbidden();
    $this->get(route('accounting.mi.dashboard', ['department' => 'Other']))->assertViewHas('budgetQueue', fn ($rows) => $rows->total() === 0);
    $this->get(route('accounting.mi.dashboard', ['employee' => $foreignOwner->getKey()]))->assertViewHas('budgetQueue', fn ($rows) => $rows->total() === 0);
});

it('archives pending budgets without deleting items or activity history', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $this->post(route('budget_requests.store'), $this->budgetPayload())->assertRedirect();
    $budget = BudgetRequest::sole();
    $item = $budget->items()->sole();
    $this->delete(route('budget_requests.destroy', $budget))->assertRedirect();
    $this->assertSoftDeleted($budget);
    $this->assertModelExists($item);
    expect($budget->activities()->count())->toBe(3);
    $this->post(route('budget_requests.store'), $this->budgetPayload())->assertRedirect();
    expect(BudgetRequest::sole()->control_id)->toBe('BR-'.now()->year.'-0002');
});

it('serializes simultaneous mysql transitions with one winner and one audit event', function (string $action, string $status, string $event) {
    if (config('database.default') !== 'mi_test_mysql') {
        $this->markTestSkipped('Requires the token-scoped isolated MySQL database.');
    }
    $owner = $this->miUser();
    $actor = match ($action) {
        'approve' => $this->miUser('Manager'),
        'release' => $this->miUser('accounting'),
        default => $owner,
    };
    if ($action === 'approve') {
        miGrant($actor, 'mi.budget.approve');
    }
    $budget = MIBudgetFixtureFactory::new()->create([
        'employee_id' => $owner->getKey(), 'status' => $status,
        'noted_at' => $action === 'release' ? now() : null, 'noted_by' => $action === 'release' ? $actor->getKey() : null,
    ]);
    DB::commit();
    try {
        $start = (string) (microtime(true) + 1.5);
        $command = [PHP_BINARY, base_path('tests/MIFinancialRaceWorker.php'), $action, (string) $actor->getKey(), (string) $budget->company_id, (string) $budget->getKey(), $start];
        $first = new Process($command, base_path());
        $second = new Process($command, base_path());
        $first->start();
        $second->start();
        $first->wait();
        $second->wait();
        expect($first->isSuccessful())->toBeTrue($first->getErrorOutput());
        expect($second->isSuccessful())->toBeTrue($second->getErrorOutput());
        $results = [trim($first->getOutput()), trim($second->getOutput())];
        sort($results);
        expect($results)->toBe(['200', '422']);
        if ($action === 'travel') {
            $this->assertDatabaseCount('liquidations', 1);
            expect(Liquidation::sole()->activities()->where('event', $event)->count())->toBe(1);
        } else {
            expect($budget->activities()->where('event', $event)->count())->toBe(1);
        }
    } finally {
        $token = getenv('MI_TEST_MYSQL_TOKEN');
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{24}$/D', $token) || DB::connection()->getDatabaseName() !== 'mi_workflow_test_'.$token) {
            throw new RuntimeException('Test cleanup refused outside token-scoped database.');
        }
        Schema::disableForeignKeyConstraints();
        try {
            foreach (['financial_activities', 'financial_settlements', 'budget_releases', 'liquidation_items', 'liquidations',
                'budget_request_items', 'budget_requests', 'mi_liquidation_items', 'mi_liquidations',
                'model_has_permissions', 'model_has_roles', 'role_has_permissions', 'roles', 'permissions', 'company_user', 'companies', 'users'] as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
            DB::beginTransaction();
        }
    }
})->with([
    ['approve', 'budget_requested', 'budget_approved'],
    ['release', 'approved', 'release_confirmed_unquantified'],
    ['receive', 'released', 'funds_received'],
    ['travel', 'in_progress', 'liquidation_submitted'],
]);

it('verifies financial mysql column types foreign keys enum strictness and unique parent linkage', function () {
    if (config('database.default') !== 'mi_test_mysql') {
        $this->markTestSkipped('Requires isolated MySQL.');
    }
    $schema = DB::connection()->getDatabaseName();
    $columns = collect(DB::select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?', [$schema]))
        ->keyBy(fn ($column) => $column->TABLE_NAME.'.'.$column->COLUMN_NAME);
    expect($columns['users.user_id']->COLUMN_TYPE)->toMatch('/^bigint(?:\(20\))? unsigned$/');
    expect($columns['company_user.user_id']->COLUMN_TYPE)->toMatch('/^bigint(?:\(20\))? unsigned$/');
    expect($columns['budget_requests.company_id']->COLUMN_TYPE)->toMatch('/^bigint(?:\(20\))? unsigned$/');
    expect($columns['budget_releases.amount']->COLUMN_TYPE)->toBe('decimal(12,2)');
    expect($columns['financial_settlements.outstanding_balance']->COLUMN_TYPE)->toBe('decimal(12,2)');
    expect($columns['financial_activities.actor_user_id']->COLUMN_TYPE)->toMatch('/^bigint(?:\(20\))? unsigned$/');
    expect($columns['liquidation_items.receipt_attached']->COLUMN_TYPE)->toContain("'yes'", "'no'", "'n_a'");
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    expect(fn () => MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]))->toThrow(QueryException::class);
    expect(fn () => DB::table('liquidation_items')->insert([
        'liquidation_id' => $travel->getKey(), 'expense_category' => 'Travel', 'particular' => 'Invalid enum',
        'actual_cash' => '0', 'actual_credit_card' => '0', 'actual_travel_agent' => '0', 'actual_total' => '0', 'receipt_attached' => 'invalid',
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('budget_releases')->insert(['budget_request_id' => $budget->getKey(), 'company_id' => 999999, 'amount' => '1.00', 'currency' => 'VND', 'released_at' => now()]))->toThrow(QueryException::class);
});

it('renders an enhanced multipage travel pdf with actual company payment and history data', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'budget_total' => '150.00',
        'travel_date_from' => '2026-10-01', 'travel_date_to' => '2026-10-05']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    for ($index = 1; $index <= 45; $index++) {
        $travel->items()->create(['expense_category' => 'Travel', 'particular' => 'Transport and hotel expense '.$index,
            'actual_cash' => '1.00', 'actual_credit_card' => '0.50', 'actual_travel_agent' => '0.25', 'receipt_attached' => 'yes']);
    }
    $budget->releases()->create(['company_id' => $budget->company_id, 'amount' => '100.00', 'currency' => 'VND',
        'payment_method' => 'bank transfer', 'reference_no' => 'PAY-QA-001', 'released_by' => $owner->getKey(), 'released_at' => now()]);
    app(MiFinancialWorkflowService::class)->activity($budget, $owner, 'budget_submitted', null, ['amount' => '150.00']);
    app(MiFinancialWorkflowService::class)->activity($travel, $owner, 'liquidation_submitted');
    $response = $this->get(route('travel_liquidation.pdf', $travel))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    $html = view('mi_app.liquidations.pdf', ['liquidation' => $travel->fresh()->load('company', 'items.budgetRequestItem', 'budgetRequest.releases', 'budgetRequest.activities', 'activities', 'settlement', 'liquidatedBy')])->render();
    expect($html)->toContain('PAY-QA-001', '100.00', 'Travel sign-off history', 'Transport and hotel expense 45');
    if (getenv('MI_PDF_QA') === '1') {
        file_put_contents(storage_path('app/mi-travel-qa.pdf'), $response->getContent());
    }
});

it('denies mismatched and unknown travel company even when parent and actor are authorized', function (bool $unknown, string $role) {
    $owner = $this->miUser();
    $foreign = $this->miUser('user', 'MMC');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(),
        'company_id' => $unknown ? null : $foreign->companies()->first()->getKey()]);
    $this->signInMI($role === 'user' ? $owner : $this->miUser('accounting'));
    $this->get(route($role === 'user' ? 'travel_liquidation.show' : 'travel_liquidation.processing', $travel))->assertForbidden();
    $this->post(route('travel_liquidation.note', $travel))->assertForbidden();
    expect($travel->fresh()->noted_at)->toBeNull();
})->with([[true, 'user'], [false, 'user'], [true, 'accounting'], [false, 'accounting']]);

it('requires release payment data after payment recording is explicitly enabled', function () {
    config(['mi_financial.release_recording_enabled' => true, 'mi_financial.currencies' => ['VND']]);
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved', 'noted_by' => $actor->getKey(), 'noted_at' => now()]);
    $this->signInMI($actor);
    $this->postJson(route('budget_requests.release', $budget))->assertUnprocessable()->assertJsonValidationErrors(['amount', 'currency', 'payment_method', 'reference_no']);
    expect($budget->fresh()->released_at)->toBeNull();
});

it('refuses settlement evidence from a different company and retains one-cent classification', function () {
    config(['mi_financial.settlement_recording_enabled' => true, 'mi_financial.currencies' => ['VND']]);
    $owner = $this->miUser();
    $actor = $this->miUser('accounting');
    $foreign = $this->miUser('user', 'MMC');
    miGrant($actor, 'mi.travel.settle');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $budget->releases()->create(['company_id' => $foreign->companies()->first()->getKey(), 'amount' => '100.00', 'currency' => 'VND', 'released_at' => now()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'approved', 'variance' => '0.01']);
    expect($travel->isBalanced())->toBeTrue();
    $this->signInMI($actor);
    $this->postJson(route('travel_liquidation.settlement', $travel), ['employee_return_amount' => '0.00', 'company_reimbursement_amount' => '0.00', 'currency' => 'VND', 'settlement_method' => 'bank', 'reference_no' => 'SET'])->assertUnprocessable();
    $this->assertDatabaseCount('financial_settlements', 0);
});

it('renders ordinary pdf history while rejecting invalid receipt file references', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $this->post(route('liquidation.store'), $this->ordinaryPayload())->assertRedirect();
    $report = MI_Liquidation::sole();
    $report->items()->sole()->update(['receipt_image' => '../outside.png']);
    $response = $this->get(route('liquidation.pdf', $report))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    expect($report->activities()->sole()->event)->toBe('ordinary_liquidation_created');
    if (getenv('MI_PDF_QA') === '1') {
        file_put_contents(storage_path('app/mi-ordinary-qa.pdf'), $response->getContent());
    }
});

it('denies amendments when signed records have their status reset', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'approved_at' => now(), 'status' => 'budget_requested']);
    $this->put(route('budget_requests.update', $budget), ['department' => 'Changed'])->assertForbidden();
    $this->delete(route('budget_requests.destroy', $budget))->assertForbidden();
    expect($budget->fresh()->department)->toBe('Design');
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'noted_at' => now(), 'status' => 'submitted']);
    $this->putJson(route('travel_liquidation.update', $travel), $this->travelPayload($budget))->assertForbidden();
    expect($travel->fresh()->noted_at)->not->toBeNull();
});

it('continues budget numbering after a five-digit sequence without changing existing references', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $existing = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'control_id' => 'BR-'.now()->year.'-10000']);
    $this->post(route('budget_requests.store'), $this->budgetPayload())->assertRedirect();
    expect(BudgetRequest::latest('id')->first()->control_id)->toBe('BR-'.now()->year.'-10001');
    expect($existing->fresh()->control_id)->toBe('BR-'.now()->year.'-10000');
});
