<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create roles FIRST
        Role::firstOrCreate(['name' => 'Administrator']);
        Role::firstOrCreate(['name' => 'Manager']);
        Role::firstOrCreate(['name' => 'user']);

        // 2. CLEAR CACHE BEFORE assigning roles
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->forgetCachedPermissions();

        // 3. THEN seed user
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}