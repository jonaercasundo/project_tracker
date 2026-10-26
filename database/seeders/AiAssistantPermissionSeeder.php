<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AiAssistantPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('ai.assistant.use', 'web');
        Permission::findOrCreate('ai.settings.manage', 'web');

        $administrator = Role::findOrCreate('Administrator', 'web');
        $administrator->givePermissionTo(['ai.assistant.use', 'ai.settings.manage']);

        foreach (['user', 'finance', 'accounting', 'Accounting', 'Executive', 'executive', 'IT', 'Warehouse_officer'] as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo('ai.assistant.use');
        }

        foreach (Role::query()->where('name', '!=', 'Administrator')->get() as $role) {
            if ($role->hasPermissionTo('ai.settings.manage')) {
                $role->revokePermissionTo('ai.settings.manage');
            }
        }

        foreach (User::permission('ai.settings.manage')->with('roles')->get() as $user) {
            if (! $user->hasRole('Administrator')) {
                $user->revokePermissionTo('ai.settings.manage');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
