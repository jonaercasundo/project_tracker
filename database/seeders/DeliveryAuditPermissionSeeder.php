<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DeliveryAuditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('delivery.audit.view', 'web');
        Permission::findOrCreate('delivery.audit.correct', 'web');

        foreach (['user', 'finance', 'Administrator'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo('delivery.audit.view');
        }

        Role::findOrCreate('Administrator', 'web')->givePermissionTo('delivery.audit.correct');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
