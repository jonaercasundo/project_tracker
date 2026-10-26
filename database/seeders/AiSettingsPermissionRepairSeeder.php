<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AiSettingsPermissionRepairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = Role::query()
            ->where('name', 'Administrator')
            ->where('guard_name', 'web')
            ->first();

        if ($administrator === null) {
            throw new RuntimeException('The existing web Administrator role was not found. No permission changes were made.');
        }

        $permission = Permission::findOrCreate('ai.settings.manage', 'web');
        $administrator->givePermissionTo($permission);
    }
}
