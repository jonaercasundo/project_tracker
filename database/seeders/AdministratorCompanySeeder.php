<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class AdministratorCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $administrator = User::where('email', 'jcasundo.sedge@gmail.com')->lockForUpdate()->firstOrFail();
            if (! $administrator->hasRole('Administrator')) {
                throw new LogicException('The requested account must already have the Administrator role.');
            }

            $company = Company::firstOrCreate(['code' => 'MI'], ['name' => 'MI', 'is_active' => true]);
            if (! $company->is_active) {
                throw new LogicException('The MI company is inactive; no membership was assigned.');
            }

            $administrator->companies()->syncWithoutDetaching([$company->getKey()]);
        });
    }
}
