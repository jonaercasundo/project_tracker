<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

beforeEach(function () {
    Schema::table('users', function (Blueprint $table): void {
        foreach (['employee_id', 'department', 'position'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                $table->string($column)->nullable();
            }
        }
    });
});

it('preserves legacy roles and creates an accounting account after the role migration', function () {
    $migration = require database_path('migrations/2026_10_06_050731_change_users_role_to_string.php');
    $isMysql = DB::connection()->getDriverName() === 'mysql';
    if (! $isMysql) {
        $migration->up();
    }

    $administrator = $this->miUser('Administrator');
    $existing = $this->miUser();
    if ($isMysql) {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'Administrator', 'user') NULL DEFAULT NULL");
    }

    $existing->update(['role' => null]);

    if ($isMysql) {
        $migration->up();
    }

    expect($administrator->fresh()->role)->toBe('Administrator');
    expect($existing->fresh()->role)->toBeNull();
    Role::findOrCreate('accounting', 'web');
    $companyId = $administrator->companies()->first()->getKey();

    $this->actingAs($administrator)->post(route('users.store'), [
        'name' => 'Accounting Staff', 'email' => 'accounting@example.test',
        'employee_id' => 'emp-accounting', 'department' => 'Accounting',
        'company_ids' => [$companyId], 'roles' => ['accounting'],
        'password' => 'Accounting-test-password!', 'password_confirmation' => 'Accounting-test-password!',
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

    $accountant = User::where('email', 'accounting@example.test')->sole();
    expect($accountant->role)->toBe('accounting');
    expect($accountant->hasRole('accounting'))->toBeTrue();
    expect(Hash::check('Accounting-test-password!', $accountant->password))->toBeTrue();
    expect($accountant->companies()->pluck('companies.company_id')->all())->toBe([$companyId]);
    expect($accountant->getAllPermissions())->toHaveCount(0);
});

it('rolls back account creation if role assignment fails without exposing passwords or SQL', function () {
    $administrator = $this->miUser('Administrator');
    Role::findOrCreate('accounting', 'api');

    $this->actingAs($administrator)->post(route('users.store'), [
        'name' => 'Accounting Staff', 'email' => 'failed-accounting@example.test',
        'employee_id' => 'emp-accounting', 'department' => 'Accounting',
        'company_ids' => [$administrator->companies()->first()->getKey()], 'roles' => ['accounting'],
        'password' => 'Accounting-test-password!', 'password_confirmation' => 'Accounting-test-password!',
    ])->assertRedirect()->assertSessionHasErrors(['error' => 'Unable to create the user. Please try again.'])
        ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation')
        ->assertSessionHas('_old_input.email', 'failed-accounting@example.test');

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('company_user', 1);
    $this->assertDatabaseCount('model_has_roles', 1);
});

it('denies employee account provisioning', function () {
    $employee = $this->miUser();
    $this->actingAs($employee)->post(route('users.store'), [])->assertForbidden();
    $this->assertDatabaseCount('users', 1);
});
