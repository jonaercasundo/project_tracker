<?php

use App\Console\Commands\CreateAdministrator;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\AdministratorCompanySeeder;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

beforeEach(function () {
    $this->app->make(Kernel::class)->registerCommand(app(CreateAdministrator::class));
});

it('creates an administrator who can log in with active company membership', function () {
    $company = Company::create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);

    $this->artisan('app:create-administrator')
        ->expectsChoice('Company (comma-separated choices for multiple companies)', ['MI'], ['MI'])
        ->expectsQuestion('Administrator name', 'Test Administrator')
        ->expectsQuestion('Login email', 'admin@example.test')
        ->expectsQuestion('Password (at least 12 characters)', 'Admin-test-password!')
        ->expectsQuestion('Confirm password', 'Admin-test-password!')
        ->expectsOutput('Administrator created. Sign in with the email and password you entered.')
        ->assertSuccessful();

    $user = User::sole();
    expect(Hash::check('Admin-test-password!', $user->password))->toBeTrue();
    expect($user->hasRole('Administrator'))->toBeTrue();
    expect($user->companies()->pluck('companies.company_id')->all())->toBe([$company->getKey()]);
    expect($user->getAllPermissions())->toHaveCount(0);
    $this->post(route('login'), ['email' => 'admin@example.test', 'password' => 'Admin-test-password!'])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('company_id', $company->getKey());
    $this->assertAuthenticatedAs($user);
});

it('preserves an existing account instead of resetting its password or granting administrator access', function () {
    $existing = $this->miUser();
    $originalPassword = $existing->password;

    $this->artisan('app:create-administrator', ['--name' => 'Admin', '--email' => $existing->email, '--company' => ['MI']])
        ->expectsQuestion('Password (at least 12 characters)', 'Admin-test-password!')
        ->expectsQuestion('Confirm password', 'Admin-test-password!')
        ->expectsOutput('The email has already been taken.')
        ->assertFailed();

    expect($existing->fresh()->password)->toBe($originalPassword);
    expect($existing->fresh()->hasRole('Administrator'))->toBeFalse();
    $this->assertDatabaseCount('users', 1);
});

it('rejects invalid company selections without creating an account', function (array $selected, bool $active) {
    Company::create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);
    Company::create(['name' => 'MMC', 'code' => 'MMC', 'is_active' => $active]);

    $this->artisan('app:create-administrator', ['--name' => 'Admin', '--email' => 'admin@example.test', '--company' => $selected])
        ->expectsQuestion('Password (at least 12 characters)', 'Admin-test-password!')
        ->expectsQuestion('Confirm password', 'Admin-test-password!')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('company_user', 0);
})->with([[['UNKNOWN'], true], [['MMC'], false], [['MI', 'MI'], true]]);

it('rejects short or unconfirmed passwords without creating an account', function (string $password, string $confirmation) {
    Company::create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);

    $this->artisan('app:create-administrator', ['--name' => 'Admin', '--email' => 'admin@example.test', '--company' => ['MI']])
        ->expectsQuestion('Password (at least 12 characters)', $password)
        ->expectsQuestion('Confirm password', $confirmation)
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
})->with([['short', 'short'], ['Admin-test-password!', 'Different-password!']]);

it('supports explicitly selected membership in both companies', function () {
    Company::create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);
    Company::create(['name' => 'MMC', 'code' => 'MMC', 'is_active' => true]);

    $this->artisan('app:create-administrator', ['--name' => 'Admin', '--email' => 'admin@example.test', '--company' => ['MI', 'MMC']])
        ->expectsQuestion('Password (at least 12 characters)', 'Admin-test-password!')
        ->expectsQuestion('Confirm password', 'Admin-test-password!')
        ->assertSuccessful();

    expect(User::sole()->companies()->orderBy('code')->pluck('code')->all())->toBe(['MI', 'MMC']);
});

it('fails clearly when the login company schema has not been installed', function () {
    Schema::drop('company_user');

    $this->artisan('app:create-administrator')
        ->expectsOutput('The companies and company_user tables must be installed before creating a login account.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});

it('requires private interactive password entry', function () {
    $this->artisan('app:create-administrator', ['--no-interaction' => true])
        ->expectsOutput('Run this command interactively to enter your password privately.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});

it('fails clearly when there is no active supported company', function () {
    $this->artisan('app:create-administrator')
        ->expectsOutput('An active MI or MMC company must exist before creating a login account.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});

it('seeds only the requested administrator and does not duplicate it on repeat', function () {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('employee_id')->nullable();
        $table->string('department')->nullable();
        $table->string('position')->nullable();
    });

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $this->assertDatabaseCount('users', 1);
    $user = User::sole();
    expect($user->email)->toBe('jcasundo.sedge@gmail.com');
    expect($user->hasRole('Administrator'))->toBeTrue();
    expect($user->role)->toBe('admin');
    expect($user->getAllPermissions())->toHaveCount(0);
});

it('assigns MI only to the requested administrator and supports repeat provisioning', function () {
    $other = $this->miUser('user', 'MMC');
    $administrator = User::factory()->create(['email' => 'jcasundo.sedge@gmail.com', 'username' => 'jcasundo.sedge@gmail.com', 'role' => 'admin']);
    $administrator->assignRole(Role::findOrCreate('Administrator', 'web'));

    $this->seed(AdministratorCompanySeeder::class);
    $this->seed(AdministratorCompanySeeder::class);

    expect($administrator->companies()->pluck('code')->all())->toBe(['MI']);
    expect($other->companies()->pluck('code')->all())->toBe(['MMC']);
    $this->assertDatabaseCount('company_user', 2);
    $this->assertDatabaseCount('companies', 2);
    $this->post(route('login'), ['email' => $administrator->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($administrator);
});

it('does not reactivate an inactive MI company during administrator provisioning', function () {
    $administrator = $this->miUser('Administrator');
    $administrator->update(['email' => 'jcasundo.sedge@gmail.com']);
    $company = Company::where('code', 'MI')->sole();
    $company->update(['is_active' => false]);
    $administrator->companies()->detach();

    expect(fn () => $this->seed(AdministratorCompanySeeder::class))->toThrow(LogicException::class);

    expect($company->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseCount('company_user', 0);
});

it('refuses to provision administrator membership for an ordinary user', function () {
    $user = $this->miUser('user', 'MMC');
    $user->update(['email' => 'jcasundo.sedge@gmail.com']);

    expect(fn () => $this->seed(AdministratorCompanySeeder::class))->toThrow(LogicException::class);

    expect($user->companies()->pluck('code')->all())->toBe(['MMC']);
    $this->assertDatabaseMissing('companies', ['code' => 'MI']);
});
