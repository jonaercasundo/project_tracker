<?php

use App\Console\Commands\CreateMIApprover;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\MIBudgetFixtureFactory;
use Tests\MITravelFixtureFactory;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

beforeEach(function () {
    $this->app->make(Kernel::class)->registerCommand(app(CreateMIApprover::class));
});

it('renames the existing role without replacing credentials memberships or permissions', function () {
    $user = $this->miUser('MI Approver');
    $user->givePermissionTo(Permission::findOrCreate('mi.budget.approve', 'web'));
    $roleId = $user->roles()->sole()->getKey();
    $password = $user->password;
    $companyIds = $user->companies()->pluck('companies.company_id')->all();
    $migration = require database_path('migrations/2026_10_06_061026_rename_mi_approver_role_to_executive.php');
    $migration->up();
    $migration->up();

    expect($user->fresh()->role)->toBe('Executive');
    expect($user->fresh()->roles()->sole()->getKey())->toBe($roleId);
    expect($user->fresh()->hasRole('Executive'))->toBeTrue();
    expect($user->fresh()->password)->toBe($password);
    expect($user->fresh()->companies()->pluck('companies.company_id')->all())->toBe($companyIds);
    expect($user->fresh()->getAllPermissions()->pluck('name')->all())->toBe(['mi.budget.approve']);
    $this->assertDatabaseMissing('roles', ['name' => 'MI Approver', 'guard_name' => 'web']);
});

it('refuses to merge an existing Executive role and preserves both roles', function () {
    $user = $this->miUser('MI Approver');
    Role::findOrCreate('Executive', 'web');
    $migration = require database_path('migrations/2026_10_06_061026_rename_mi_approver_role_to_executive.php');
    expect(fn () => $migration->up())->toThrow(RuntimeException::class);
    expect($user->fresh()->role)->toBe('MI Approver');
    expect($user->fresh()->hasRole('MI Approver'))->toBeTrue();
});

it('creates an MI approver with only approval capabilities and a working login destination', function () {
    $this->miUser();
    expect(Artisan::call('mi:create-approver', ['email' => 'approver@example.test']))->toBe(0);
    preg_match('/Temporary password: ([^\r\n]+)/', Artisan::output(), $password);
    $user = User::where('email', 'approver@example.test')->sole();
    expect($user->hasRole('Executive'))->toBeTrue();
    expect($user->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe(['mi.budget.approve', 'mi.travel.approve']);
    expect($user->companies()->pluck('code')->all())->toBe(['MI']);
    $this->post(route('login'), ['email' => $user->email, 'password' => $password[1]])->assertRedirect(route('mi.approvals'));
    $this->get(route('mi.approvals'))->assertOk()->assertSee('No eligible requests');
    $this->get(route('accounting.mi.dashboard'))->assertForbidden();
    $this->post(route('company.switch'), ['company_id' => $user->companies()->first()->getKey()])->assertRedirect(route('mi.approvals'));
});

it('lets the approver review and approve eligible budgets and reviewed travel without release authority', function () {
    $owner = $this->miUser();
    Artisan::call('mi:create-approver', ['email' => 'approver@example.test']);
    $approver = User::where('email', 'approver@example.test')->sole();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $parent = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $parent->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted', 'noted_at' => now()]);
    $this->signInMI($approver);
    $this->get(route('mi.approvals'))->assertOk()->assertSee($budget->control_id)->assertSee($parent->control_id);
    $this->get(route('budget_requests.processing', $budget))->assertOk()->assertSee(route('budget_requests.approve', $budget));
    $this->post(route('budget_requests.approve', $budget))->assertRedirect();
    expect($budget->fresh()->status)->toBe('approved');
    expect($budget->fresh()->approved_by)->toBe($approver->getKey());
    $this->post(route('budget_requests.approve', $budget))->assertUnprocessable();
    $this->get(route('travel_liquidation.processing', $travel))->assertOk()->assertSee(route('travel_liquidation.approve', $travel));
    $this->post(route('travel_liquidation.approve', $travel))->assertRedirect();
    expect($travel->fresh()->status)->toBe('approved');
    $this->post(route('budget_requests.release', $budget))->assertForbidden();
});

it('excludes foreign unknown company and self owned records and blocks employee queue access', function () {
    $owner = $this->miUser();
    $foreignOwner = $this->miUser('user', 'MMC');
    Artisan::call('mi:create-approver', ['email' => 'approver@example.test']);
    $approver = User::where('email', 'approver@example.test')->sole();
    $own = MIBudgetFixtureFactory::new()->create(['employee_id' => $approver->getKey()]);
    $unknown = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'company_id' => null]);
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->first()->getKey()]);
    $this->signInMI($approver);
    $this->get(route('mi.approvals'))->assertOk()->assertViewHas('budgets', fn ($rows) => $rows->total() === 0)->assertDontSee($foreign->control_id)->assertDontSee($unknown->control_id)->assertDontSee($own->control_id);
    foreach ([$own, $unknown, $foreign] as $budget) {
        $this->post(route('budget_requests.approve', $budget))->assertForbidden();
    }
    $this->signInMI($owner);
    $this->get(route('mi.approvals'))->assertForbidden();
});

it('refuses existing accounts and missing company without changing privileges or passwords', function () {
    $existing = $this->miUser();
    $password = $existing->password;
    $this->artisan('mi:create-approver', ['email' => $existing->email])->assertFailed();
    expect($existing->fresh()->password)->toBe($password);
    expect($existing->fresh()->can('mi.budget.approve'))->toBeFalse();
    $this->assertDatabaseCount('users', 1);
});

it('requires an active MI company before creating an approver', function () {
    $this->artisan('mi:create-approver', ['email' => 'approver@example.test'])->assertFailed();
    $this->assertDatabaseCount('users', 0);
});
