<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'jcasundo.sedge@gmail.com'],
            [
                'name' => 'Jonaer Casundo',
                'employee_id' => '26-0518',
                'password' => Hash::make('@Hanabi16'),
                'position' => 'IT',
                'department' => 'IT',
                'role' => 'admin',
            ]
        );

        // assign Spatie role
        $admin->assignRole('Administrator');
    }
}