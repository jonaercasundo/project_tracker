<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('Administrator', 'web');

        $admin = User::updateOrCreate(
            ['email' => 'jcasundo.sedge@gmail.com'],
            [
                'name' => 'Jonaer Casundo',
                'employee_id' => '26-0518',
                'password' => Hash::make('@Hanabi16'),
                'position' => 'IT',
                'role' => 'admin',
                'department' => 'IT',
                'username' => 'jcasundo.sedge@gmail.com',
            ]
        );

        $admin->assignRole('Administrator');

    }
}
