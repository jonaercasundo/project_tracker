<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AiBusinessDataPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $catalogPermission = Permission::findOrCreate('mi.product-catalog.view', 'web');
        $pricePermission = Permission::findOrCreate('mi.product-prices.view', 'web');

        foreach (['user', 'Accounting', 'accounting', 'Executive', 'executive'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo([$catalogPermission, $pricePermission]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
