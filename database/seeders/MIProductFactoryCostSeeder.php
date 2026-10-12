<?php

namespace Database\Seeders;

use App\Models\MI_Product;
use App\Models\MI_Product_Factory_Cost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MIProductFactoryCostSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (MI_Product::where('item_code', 'DEMO-FACTORY-COSTS')->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $product = MI_Product::factory()->create([
                'item_name' => 'Factory cost demonstration product',
                'item_code' => 'DEMO-FACTORY-COSTS',
            ]);

            MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create([
                'factory_name' => 'Demo Factory A',
                'factory_cost' => '1250.00',
            ]);

            MI_Product_Factory_Cost::factory()->for($product, 'product')->create([
                'factory_name' => 'Demo Factory B',
                'factory_cost' => '1180.00',
            ]);

            MI_Product_Factory_Cost::factory()->for($product, 'product')->create([
                'factory_name' => 'Demo Factory C',
                'factory_cost' => '1350.00',
            ]);
        });
    }
}
