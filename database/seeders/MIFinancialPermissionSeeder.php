<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MIFinancialPermissionSeeder extends Seeder
{
    /** Opt-in permission catalog only; never assigns a role or user. */
    public function run(): void
    {
        foreach (['mi.budget.approve', 'mi.travel.approve', 'mi.travel.settle', 'mi.travel.close',
            'mi.accounting.dashboard.view', 'mi.budget.view', 'mi.budget.review', 'mi.budget.release',
            'mi.liquidation.view', 'mi.liquidation.review', 'mi.settlement.view', 'mi.financial-reports.view', 'mi.audit.view',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
