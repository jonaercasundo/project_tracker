<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Administrator']);
        Role::firstOrCreate(['name' => 'IT']);

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

        $myAccount = User::updateOrCreate(
            ['email' => 'renzeljaredbautista@outlook.com'], 
            [
                'name' => 'Renzel Jared Y. Bautista',             
                'employee_id' => 'YOUR_ID',        
                'password' => Hash::make('password123'), 
                'position' => 'IT',
                'role' => 'admin',                    
                'department' => 'IT',
                'username' => 'renzeljaredbautista@outlook.com',
            ]
        );
        
        $myAccount->assignRole('IT');
    }
}