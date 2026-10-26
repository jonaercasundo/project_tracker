<?php

use App\Models\Company;
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
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_095438_create_ai_quotation_workflow_tables.php';

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
        ->assertSee('Effective access diagnostics')->assertSee('MMC Intelligence')
        ->assertSee('Required AI permissions')->assertSee('Business-policy requirements')
        ->assertSee('Implementation status / risk')->assertSee('Company scope')
        ->assertSee('Diagnose Access')->assertSee('Configure access for')->assertSee('Save &amp; Verify Access', false);
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

it('allows administrators to grant and revoke AI permissions for one user without granting business permissions', function () {
    controlCenterAdmin();
    $target = $this->miUser('user', 'MMC');
    $permission = Permission::findByName('ai.mmc.projects.view', 'web');

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'user',
        'user_subject_id' => $target->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'grant',
        'diagnostic_user_id' => $target->getKey(),
        'company_id' => $target->companies()->sole()->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($target->fresh()->can('ai.mmc.projects.view'))->toBeTrue()
        ->and($target->fresh()->can('mmc.projects.view'))->toBeFalse()
        ->and(DB::table('admin_activity_logs')->where('event', 'ai.permission_granted')->exists())->toBeTrue();

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'user',
        'user_subject_id' => $target->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'revoke',
        'diagnostic_user_id' => $target->getKey(),
        'company_id' => $target->companies()->sole()->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($target->fresh()->can('ai.mmc.projects.view'))->toBeFalse()
        ->and(DB::table('admin_activity_logs')->where('event', 'ai.permission_revoked')->exists())->toBeTrue();
});

it('requires confirmation for shared role AI permission changes and protects self grants', function () {
    $admin = controlCenterAdmin();
    $role = Role::findByName('user', 'web');
    $permission = Permission::findByName('ai.mmc.projects.view', 'web');

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'role',
        'role_subject_id' => $role->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'grant',
    ])->assertSessionHasErrors('confirm_shared_role');

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'role',
        'role_subject_id' => $role->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'grant',
        'confirm_shared_role' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'user',
        'user_subject_id' => $admin->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'grant',
    ])->assertStatus(422);

    $ordinaryUser = $this->miUser('user', 'MMC');
    $this->actingAs($ordinaryUser)->withSession(['company_id' => $ordinaryUser->companies()->sole()->getKey()])
        ->patch(route('admin.access.permissions.ai.configure'), [
            'subject_type' => 'user',
            'user_subject_id' => $ordinaryUser->getKey(),
            'permission_id' => $permission->getKey(),
            'action' => 'grant',
        ])->assertForbidden();
});

it('authorizes global AI configuration by permission without an AI company', function () {
    $admin = controlCenterAdmin();
    $registry = app(AiCapabilityRegistry::class);
    $authorized = $registry->diagnostic('ai.configuration', $admin, null, false, false);

    expect($authorized['status'])->toBe('Implemented')
        ->and($authorized['allowed'])->toBeTrue()
        ->and($authorized['company_scope'])->toBe('*')
        ->and($authorized['required_ai_permissions'])->toBe(['ai.settings.manage'])
        ->and($authorized['missing_ai_permissions'])->toBeEmpty();

    $member = $this->miUser('user');
    $denied = $registry->diagnostic('ai.configuration', $member, null, true, true);
    expect($denied['status'])->toBe('Permission Restricted')
        ->and($denied['missing_ai_permissions'])->toContain('ai.settings.manage');
});

it('reports workflow guidance and quotation suggestions as partially implemented', function () {
    $workflowUser = $this->miUser('user', 'MMC');
    $workflowCompany = $workflowUser->companies()->sole();
    $registry = app(AiCapabilityRegistry::class);

    $workflow = $registry->diagnostic('business.workflow_guidance', $workflowUser, $workflowCompany, true, true);
    expect($workflow['status'])->toBe('Partially Implemented')
        ->and($workflow['allowed'])->toBeTrue()
        ->and($workflow['business_policy_requirements'])->toContain('AiModuleRegistry pageContext action; no workflow execution authority');

    $unauthorizedSuggestionsUser = $this->miUser('user', 'MI');
    $unauthorizedSuggestions = $registry->diagnostic('business.proactive_suggestions', $unauthorizedSuggestionsUser, $unauthorizedSuggestionsUser->companies()->sole(), true, true);
    expect($unauthorizedSuggestions['status'])->toBe('Permission Restricted')
        ->and($unauthorizedSuggestions['missing_ai_permissions'])->toContain('ai.mi.quotation.create');

    $miRole = Role::findByName('user', 'web');
    $miRole->givePermissionTo([
        Permission::findOrCreate('ai.mi.quotation.create', 'web'),
        Permission::findOrCreate('mi.quotation.create', 'web'),
        Permission::findOrCreate('mi.product-prices.view', 'web'),
    ]);
    $quotationUser = $this->miUser('user', 'MI');
    $quotationCompany = $quotationUser->companies()->sole();
    $suggestions = $registry->diagnostic('business.proactive_suggestions', $quotationUser, $quotationCompany, true, true);

    expect($suggestions['status'])->toBe('Partially Implemented')
        ->and($suggestions['allowed'])->toBeTrue()
        ->and($suggestions['business_policy_requirements'])->toContain('MI quotation permissions and executable quote workflow');
});

it('reports exact missing AI permissions and MMC business-policy failures', function () {
    $registry = app(AiCapabilityRegistry::class);
    $user = $this->miUser('user', 'MMC');
    $company = $user->companies()->sole();
    $missingPermissions = $registry->diagnostic('mmc.deliveries.analyze', $user, $company, true, true);

    expect($missingPermissions['status'])->toBe('Permission Restricted')
        ->and($missingPermissions['required_ai_permissions'])->toContain('ai.assistant.use', 'ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze')
        ->and($missingPermissions['granted_ai_permissions'])->toContain('ai.assistant.use', 'ai.business-search.use')
        ->and($missingPermissions['missing_ai_permissions'])->toContain('ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze')
        ->and($missingPermissions['business_policy_requirements'])->toContain('ProjectInformation viewAny policy', 'viewMmcDeliveryMonitoring Gate')
        ->and($missingPermissions['reason'])->toContain('ai.mmc.deliveries.analyze')
        ->and(collect($missingPermissions['checks'])->firstWhere('name', 'ai.assistant.use')['granted'])->toBeTrue()
        ->and(collect($missingPermissions['checks'])->firstWhere('name', 'ai.mmc.deliveries.analyze')['granted'])->toBeFalse()
        ->and(collect($missingPermissions['checks'])->firstWhere('name', 'viewMmcDeliveryMonitoring Gate'))->not->toBeNull();

    $itRole = Role::findByName('IT', 'web');
    $itRole->givePermissionTo(Permission::findOrCreate('ai.mmc.projects.view', 'web'));
    $itUser = $this->miUser('IT', 'MMC');
    $itCompany = $itUser->companies()->sole();
    $projectPolicy = $registry->diagnostic('mmc.projects.view', $itUser, $itCompany, true, true);

    expect($projectPolicy['status'])->toBe('Business Access Restricted')
        ->and($projectPolicy['failed_business_checks'])->toContain('ProjectInformation viewAny policy check failed');

    $userRole = Role::findByName('user', 'web');
    $userRole->givePermissionTo(Permission::findOrCreate('ai.mmc.projects.view', 'web'));
    $miMember = $this->miUser('user', 'MI');
    $otherCompany = Company::firstOrCreate(['code' => 'MMC'], ['name' => 'MMC', 'is_active' => true]);
    $membership = $registry->diagnostic('mmc.projects.view', $miMember, $otherCompany, true, true);
    expect($membership['status'])->toBe('Company Restricted')
        ->and($membership['reason'])->toContain('membership check failed');

    $financeRole = Role::findByName('finance', 'web');
    $financeRole->givePermissionTo([
        Permission::findOrCreate('ai.mmc.projects.view', 'web'),
        Permission::findOrCreate('ai.mmc.deliveries.view', 'web'),
        Permission::findOrCreate('ai.mmc.deliveries.analyze', 'web'),
    ]);
    $financeUser = $this->miUser('finance', 'MMC');
    $financeCompany = $financeUser->companies()->sole();
    $deliveryPolicy = $registry->diagnostic('mmc.deliveries.analyze', $financeUser, $financeCompany, true, true);

    expect($deliveryPolicy['status'])->toBe('Business Access Restricted')
        ->and($deliveryPolicy['failed_business_checks'])->toContain('viewMmcDeliveryMonitoring Gate check failed');
});

it('evaluates selected-user company diagnostics without using the administrator session company', function () {
    controlCenterAdmin();
    $inspectedUser = $this->miUser('user', 'MMC');
    $inspectedCompany = $inspectedUser->companies()->sole();

    $this->get(route('admin.access.permissions.ai', [
        'diagnostic_user_id' => $inspectedUser->getKey(),
        'company_id' => $inspectedCompany->getKey(),
    ]))->assertOk()
        ->assertSee($inspectedUser->name)
        ->assertSee($inspectedCompany->name)
        ->assertSee('ai.mmc.projects.view')
        ->assertSee('Permission Restricted');
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
