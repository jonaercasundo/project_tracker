<?php

use App\Models\User;
use App\Services\AiCapabilityRegistry;
use App\Services\AiPermissionCatalog;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiCapabilityPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\MIWorkflowTestCase;

class AdminAiPermissionCatalogTestCase extends MIWorkflowTestCase
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

pest()->extend(AdminAiPermissionCatalogTestCase::class)->in('AdminAiPermissionCatalogTest.php');

function aiPermissionCatalogAdmin(): User
{
    $admin = test()->miUser('Administrator');
    test()->signInMI($admin);

    return $admin;
}

it('discovers capability and seeder permissions with effective user and role grant sources', function () {
    $admin = aiPermissionCatalogAdmin();
    $member = $this->miUser('user', 'MMC');
    $member->givePermissionTo(Permission::findByName('ai.mmc.projects.view', 'web'));
    $role = Role::findByName('user', 'web');
    $entries = app(AiPermissionCatalog::class)->entries(app(AiCapabilityRegistry::class), $member, null, $member->companies()->sole());
    $byName = collect($entries)->keyBy('name');

    expect($byName)->toHaveKey('ai.mmc.projects.view')
        ->and($byName['ai.mmc.projects.view']['source'])->toBe('Direct user permission')
        ->and($byName['ai.assistant.use']['inherited'])->toBeTrue()
        ->and($byName['ai.assistant.use']['inherited_roles'])->toContain('user')
        ->and($byName['ai.documents.process']['group'])->toBe('Documents & Analytics');

    $roleEntries = collect(app(AiPermissionCatalog::class)->entries(app(AiCapabilityRegistry::class), null, $role))->keyBy('name');
    expect($roleEntries['ai.assistant.use']['source'])->toBe('Assigned to selected role')
        ->and($roleEntries['ai.mmc.projects.view']['source'])->toBe('Missing from selected role');
    expect($admin->hasRole('Administrator'))->toBeTrue();
});

it('shows unregistered AI permissions and registers them without granting them', function () {
    $admin = aiPermissionCatalogAdmin();
    $target = $this->miUser('user', 'MMC');
    Permission::query()->where('name', 'ai.documents.process')->where('guard_name', 'web')->delete();

    $response = $this->get(route('admin.access.permissions.ai', [
        'catalog_subject_type' => 'user',
        'catalog_user_id' => $target->getKey(),
        'company_id' => $target->companies()->sole()->getKey(),
    ]))->assertOk()->assertSee('ai.documents.process')->assertSee('Not Registered')->assertSee('Register Permission');

    $this->post(route('admin.access.permissions.ai.register'), [
        'permission_name' => 'ai.documents.process',
        'catalog_subject_type' => 'user',
        'catalog_user_id' => $target->getKey(),
        'company_id' => $target->companies()->sole()->getKey(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Permission::findByName('ai.documents.process', 'web'))->not->toBeNull()
        ->and($target->fresh()->can('ai.documents.process'))->toBeFalse()
        ->and(DB::table('admin_activity_logs')->where('event', 'ai.permission_registered')->exists())->toBeTrue();
});

it('requires review for bulk grants and audits the resulting permission changes', function () {
    aiPermissionCatalogAdmin();
    $target = $this->miUser('user', 'MMC');
    $permissions = Permission::query()->where('guard_name', 'web')->whereIn('name', ['ai.mmc.projects.view', 'ai.mmc.deliveries.view'])->get();

    $payload = [
        'subject_type' => 'user',
        'user_subject_id' => $target->getKey(),
        'permission_ids' => $permissions->modelKeys(),
        'action' => 'grant',
    ];
    $this->patch(route('admin.access.permissions.ai.configure'), $payload)->assertStatus(422);

    $this->patch(route('admin.access.permissions.ai.configure'), [...$payload, 'confirm_changes' => '1'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($target->fresh()->can('ai.mmc.projects.view'))->toBeTrue()
        ->and($target->fresh()->can('ai.mmc.deliveries.view'))->toBeTrue()
        ->and(DB::table('admin_activity_logs')->where('event', 'ai.permission_granted')->count())->toBe(2);

    $this->patch(route('admin.access.permissions.ai.configure'), [...$payload, 'action' => 'revoke', 'confirm_changes' => '1'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($target->fresh()->can('ai.mmc.projects.view'))->toBeFalse()
        ->and($target->fresh()->can('ai.mmc.deliveries.view'))->toBeFalse()
        ->and(DB::table('admin_activity_logs')->where('event', 'ai.permission_revoked')->count())->toBe(2);
});

it('requires explicit confirmation before granting a sensitive AI permission', function () {
    aiPermissionCatalogAdmin();
    $target = $this->miUser('user', 'MMC');
    $permission = Permission::findByName('ai.records.delete', 'web');
    $payload = [
        'subject_type' => 'user',
        'user_subject_id' => $target->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'grant',
    ];

    $this->patch(route('admin.access.permissions.ai.configure'), $payload)->assertStatus(422);
    $this->patch(route('admin.access.permissions.ai.configure'), [...$payload, 'confirm_changes' => '1'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($target->fresh()->can('ai.records.delete'))->toBeTrue();
});

it('refuses to revoke a role-inherited permission from a user', function () {
    aiPermissionCatalogAdmin();
    $member = $this->miUser('user', 'MMC');
    $permission = Permission::findByName('ai.mmc.projects.view', 'web');
    $role = Role::findByName('user', 'web');
    $role->givePermissionTo($permission);

    $this->patch(route('admin.access.permissions.ai.configure'), [
        'subject_type' => 'user',
        'user_subject_id' => $member->getKey(),
        'permission_id' => $permission->getKey(),
        'action' => 'revoke',
    ])->assertStatus(422)->assertSee('Remove inherited permissions from the relevant role instead.');

    expect($member->fresh()->can('ai.mmc.projects.view'))->toBeTrue();
});

it('filters catalog permission scope while keeping permission registration under administrator authorization', function () {
    aiPermissionCatalogAdmin();
    $registry = app(AiCapabilityRegistry::class);
    $catalog = collect(app(AiPermissionCatalog::class)->entries($registry, null, null, $this->miUser('user', 'MMC')->companies()->sole()))->keyBy('name');

    expect($catalog['ai.mmc.projects.view']['scope_matches'])->toBeTrue()
        ->and($catalog['ai.mi.products.search']['scope_matches'])->toBeFalse();

    $member = $this->miUser('user', 'MMC');
    $this->actingAs($member)->post(route('admin.access.permissions.ai.register'), ['permission_name' => 'ai.documents.process'])->assertForbidden();
});
