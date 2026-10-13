<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BillingPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'billing.records.view',
        'billing.qr.scan',
        'billing.for-billing.mark',
        'billing.billed.confirm',
        'billing.exceptions.manage',
        'billing.reports.export',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $operator = Role::findOrCreate('user', 'web');
        $finance = Role::findOrCreate('finance', 'web');
        $administrator = Role::findOrCreate('Administrator', 'web');

        $operator->givePermissionTo([
            'billing.records.view',
            'billing.qr.scan',
            'billing.for-billing.mark',
        ]);
        $finance->givePermissionTo(self::PERMISSIONS);
        $administrator->givePermissionTo(self::PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
