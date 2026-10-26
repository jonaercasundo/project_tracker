<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AiQuotationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('mi.quotation.create', 'web');
        $quotationRoles = ['user', 'Accounting', 'accounting', 'Executive', 'executive'];

        foreach ($quotationRoles as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo('mi.quotation.create');
        }

        foreach (Role::query()->whereNotIn('name', $quotationRoles)->get() as $role) {
            if ($role->hasPermissionTo('mi.quotation.create')) {
                $role->revokePermissionTo('mi.quotation.create');
            }
        }

        foreach (User::permission('mi.quotation.create')->with('roles')->get() as $user) {
            if (! $user->hasAnyRole($quotationRoles)) {
                $user->revokePermissionTo('mi.quotation.create');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
