<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AiCapabilityPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'ai.mmc.projects.view',
        'ai.mmc.deliveries.view',
        'ai.mmc.deliveries.analyze',
        'ai.mmc.inventory.view',
        'ai.mmc.reports.generate',
        'ai.mmc.charts.generate',
        'ai.mmc.financial.analyze',
        'ai.mi.products.search',
        'ai.mi.products.specifications',
        'ai.mi.prices.lookup',
        'ai.mi.quotation.create',
        'ai.mi.budget-liquidation.assist',
        'ai.mi.accounting.reports',
        'ai.records.view',
        'ai.records.create',
        'ai.records.edit',
        'ai.records.archive',
        'ai.records.delete',
        'ai.documents.pdf.generate',
        'ai.excel.export',
        'ai.excel.import',
        'ai.excel.analyze',
        'ai.attachments.upload',
        'ai.attachments.read',
        'ai.mi.liquidation.draft',
        'ai.mi.liquidation.form.fill',
        'ai.charts.generate',
        'ai.data.forecast',
        'ai.documents.process',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
