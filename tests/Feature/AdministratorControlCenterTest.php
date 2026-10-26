<?php

use App\Models\User;
use App\Services\AiCapabilityRegistry;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiCapabilityPermissionSeeder;
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
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_083437_create_ai_assistant_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_085724_create_ai_provider_settings_tables.php';

        return $migrationPaths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
        $this->seed(AiCapabilityPermissionSeeder::class);
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

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('A clear view of access and operations.')
        ->assertSee('Administrator navigation')->assertSee('AI Assistant')->assertSee('AI Configuration')
        ->assertSee('md:w-[72px]')->assertSee('md:w-[260px]')->assertSee('md:sticky')
        ->assertSee(route('roles.index'))->assertSee(route('admin.ai-management.index'))->assertSee(route('roleaccess.index'));
    $this->get(route('admin.access.permissions.index'))->assertOk()->assertSee('Role permission matrix')->assertSee('Administrator navigation');
    $this->get(route('admin.access.permissions.ai'))->assertOk()->assertSee('AI capability permissions')
        ->assertSee('ai.page-help.use')->assertSee('ai.business-search.use')->assertSee('Capability details')
        ->assertSee('ai.mmc.projects.view')->assertSee('Filter by implementation status')->assertSee('Select visible')->assertSee('Reset changes')
        ->assertSee('Effective access diagnostics')->assertSee('MMC Intelligence');
    $this->get(route('admin.access.review'))->assertOk()->assertSee('User access review')->assertSee('All users &amp; access', false);
    $this->get(route('admin.audit.index'))->assertOk()->assertSee('Activity and access log')->assertSee('Roles &amp; permissions', false);
    $this->get(route('admin.ai-management.index'))->assertOk()->assertSee('Assistant capabilities and usage')->assertSee('AI Assistant');
    $this->get(route('roles.index'))->assertOk()->assertSee('Role management')->assertSee('Roles &amp; permissions', false);
    $this->get(route('roleaccess.index'))->assertOk()->assertSee('Role Access Management');

    $member = $this->miUser('user');
    $this->actingAs($member)->withSession(['company_id' => $member->companies()->sole()->getKey()]);
    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->patch(route('admin.access.permissions.update'), [])->assertForbidden();
});

it('shows the AI capability registry with implemented and unavailable functions clearly identified', function () {
    controlCenterAdmin();

    $this->get(route('admin.ai-management.index'))
        ->assertOk()
        ->assertSee('AI capability registry')
        ->assertSee('MMC Intelligence')->assertSee('MI Intelligence')
        ->assertSee('Create other business records')
        ->assertSee('Generate business PDFs')
        ->assertSee('Documents &amp; Analytics', false)
        ->assertSee('Forecast Data')
        ->assertSee('Process Uploaded Documents')
        ->assertSee('Workflow guidance')
        ->assertSee('Context-aware next steps')
        ->assertSee('MI Product Catalog Search')
        ->assertSee('Not Implemented')
        ->assertSee('Manage role permissions');
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

it('lets administrators grant and revoke MMC AI capability permissions without role allowlists', function () {
    controlCenterAdmin();
    $role = Role::findByName('user', 'web');
    $projectPermission = Permission::findByName('ai.mmc.projects.view', 'web');

    expect($role->hasPermissionTo($projectPermission))->toBeFalse();

    $this->patch(route('admin.access.permissions.update'), [
        'company' => 'all',
        'module' => 'MMC',
        'role_ids' => [$role->getKey()],
        'permissions' => [$role->getKey() => [$projectPermission->getKey() => '1']],
        'scope' => 'ai',
        'confirm_critical' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($role->fresh()->hasPermissionTo($projectPermission))->toBeTrue();
    $mmcUser = $this->miUser('user', 'MMC');
    $mmcCompany = $mmcUser->companies()->sole();
    $this->withSession(['company_id' => $mmcCompany->getKey()]);
    expect(app(AiCapabilityRegistry::class)->allowsTool('search_mmc_projects', $mmcUser, $mmcCompany))->toBeTrue();

    $this->patch(route('admin.access.permissions.update'), [
        'company' => 'all',
        'module' => 'MMC',
        'role_ids' => [$role->getKey()],
        'permissions' => [$role->getKey() => []],
        'scope' => 'ai',
        'confirm_critical' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($role->fresh()->hasPermissionTo($projectPermission))->toBeFalse()
        ->and(app(AiCapabilityRegistry::class)->allowsTool('search_mmc_projects', $mmcUser, $mmcCompany))->toBeFalse()
        ->and($role->fresh()->hasPermissionTo('ai.assistant.use'))->toBeTrue();
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
