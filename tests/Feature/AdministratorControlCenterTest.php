<?php

use App\Models\User;
use Database\Seeders\AiAssistantPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\MIWorkflowTestCase;

class AdministratorControlCenterTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrationPaths = parent::migrateFreshUsing();
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_115437_add_description_to_roles_and_create_admin_activity_logs_table.php';

        return $migrationPaths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
    }
}

pest()->extend(AdministratorControlCenterTestCase::class)->in('AdministratorControlCenterTest.php');

function controlCenterAdmin(): User
{
    $admin = test()->miUser('Administrator');
    test()->signInMI($admin);

    return $admin;
}

it('shows the control center and protects management routes from non-administrators', function () {
    $admin = controlCenterAdmin();

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('A clear view of access and operations.');
    $this->get(route('admin.access.permissions.index'))->assertOk()->assertSee('Role permission matrix');
    $this->get(route('admin.access.review'))->assertOk()->assertSee('User access review');
    $this->get(route('admin.audit.index'))->assertOk()->assertSee('Activity and access log');

    $member = $this->miUser('user');
    $this->actingAs($member)->withSession(['company_id' => $member->companies()->sole()->getKey()]);
    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->patch(route('admin.access.permissions.update'), [])->assertForbidden();
});

it('updates a permission matrix and records an audit without affecting other permission grants', function () {
    $admin = controlCenterAdmin();
    $role = Role::findByName('user', 'web');
    $existingPermission = Permission::findOrCreate('mi.budget.view', 'web');
    $permission = Permission::findOrCreate('reports.view', 'web');
    $role->givePermissionTo($existingPermission);
    expect($role->fresh()->hasPermissionTo($existingPermission))->toBeTrue();

    $this->patch(route('admin.access.permissions.update'), [
        'company' => 'all',
        'role_ids' => [$role->getKey()],
        'permissions' => [$role->getKey() => [$permission->getKey() => '1', $existingPermission->getKey() => '1']],
        'confirm_critical' => '1',
        'scope' => 'all',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($role->fresh()->permissions()->whereKey($permission->getKey())->exists())->toBeTrue()
        ->and($role->fresh()->permissions()->whereKey($existingPermission->getKey())->exists())->toBeTrue()
        ->and(DB::table('admin_activity_logs')->where('event', 'role.permissions_updated')->exists())->toBeTrue();
});

it('keeps the final active Administrator enabled and blocks inactive accounts from authenticated pages', function () {
    $admin = controlCenterAdmin();
    $companyId = $admin->companies()->sole()->getKey();

    $this->patch(route('roleaccess.update'), [
        'user_id' => $admin->getKey(),
        'company_ids' => [$companyId],
        'roles' => ['user'],
        'is_active' => 0,
    ])->assertSessionHasErrors('roles');
    expect($admin->fresh()->is_active)->toBeTrue();

    $member = $this->miUser('user');
    $this->patch(route('roleaccess.update'), [
        'user_id' => $member->getKey(),
        'company_ids' => [$member->companies()->sole()->getKey()],
        'roles' => ['user'],
        'is_active' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($member->fresh()->is_active)->toBeFalse();
    $this->actingAs($member->fresh())->get(route('dashboard.launch'))->assertRedirect();
    $this->assertGuest();
});
