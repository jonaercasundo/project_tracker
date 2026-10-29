<?php

use App\Services\BillingOperationalSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingOperationalSyncServiceTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
        });
        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->string('region')->nullable();
        });
        Schema::create('logistics_location', function (Blueprint $table): void {
            $table->increments('logistics_location_id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('region')->nullable();
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->unsignedInteger('project_id');
            $table->string('school_id');
            $table->unsignedInteger('lot_id')->nullable();
            $table->unsignedInteger('keystage_id')->nullable();
            $table->unsignedInteger('logistics_location_id')->nullable();
            $table->string('dr_no');
            $table->unsignedInteger('package_qty')->default(1);
            $table->string('status')->default('accepted');
        });
        Schema::create('delivery_billing_records', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('delivery_id');
            $table->string('status');
        });
        Schema::create('package', function (Blueprint $table): void {
            $table->increments('package_id');
            $table->unsignedInteger('lot_id')->nullable();
            $table->unsignedInteger('keystage_id')->nullable();
        });
        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('package_id');
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->unsignedInteger('delivered_by')->nullable();
            $table->string('receiver_name')->nullable();
        });
        Schema::create('package_content', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('package_id');
            $table->unsignedInteger('item_id');
            $table->integer('qty')->nullable();
        });
        Schema::create('item', function (Blueprint $table): void {
            $table->increments('item_id');
            $table->unsignedInteger('project_id');
        });
        Schema::create('inventory', function (Blueprint $table): void {
            $table->increments('inventory_id');
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('warehouse_id');
            $table->integer('qty');
            $table->string('inventory_status')->nullable();
        });
        Schema::create('inventory_history', function (Blueprint $table): void {
            $table->increments('history_id');
            $table->unsignedInteger('inventory_id')->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->integer('old_qty')->nullable();
            $table->integer('new_qty')->nullable();
            $table->string('change_type')->nullable();
            $table->string('batch_no')->nullable();
            $table->dateTime('changed_at')->nullable();
            $table->string('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('delivery_id')->nullable();
            $table->unsignedInteger('package_id')->nullable();
            $table->string('dr_no')->nullable();
            $table->integer('quantity')->nullable();
        });
        Schema::create('delivery_history', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('package_status_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
}

pest()->extend(BillingOperationalSyncServiceTestCase::class)->in(__FILE__);

/** @return array{project_id: int, delivery_id: int, package_id: int} */
function billingOperationalFixture(?int $logisticsLocationId = null, string $region = 'Region IV-A'): array
{
    $projectId = (int) DB::table('projects')->insertGetId([]);
    DB::table('school')->insert(['school_id' => 'SCH-1', 'region' => $region]);
    $deliveryId = (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => 'SCH-1',
        'lot_id' => 7,
        'logistics_location_id' => $logisticsLocationId,
        'dr_no' => 'DR-7001',
        'package_qty' => 3,
        'status' => 'accepted',
    ]);
    DB::table('delivery_billing_records')->insert(['delivery_id' => $deliveryId, 'status' => 'for_billing']);
    $packageId = (int) DB::table('package')->insertGetId(['lot_id' => 7]);
    DB::table('package_status')->insert(['delivery_id' => $deliveryId, 'package_id' => $packageId, 'status' => 'pending']);
    DB::table('package_content')->insert(['package_id' => $packageId, 'item_id' => 91, 'qty' => 2]);
    DB::table('item')->insert(['item_id' => 91, 'project_id' => $projectId]);

    return ['project_id' => $projectId, 'delivery_id' => $deliveryId, 'package_id' => $packageId];
}

it('uses the warehouse mapped to the school region when the DR has no direct location assignment', function () {
    $fixture = billingOperationalFixture();
    DB::table('logistics_location')->insert(['region' => 'Region IV-A', 'warehouse_id' => 4]);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 4, 'qty' => 20, 'inventory_status' => 'Approved']);

    $result = app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User');

    expect($result['synced_dr_count'])->toBe(1)
        ->and(DB::table('inventory')->where('warehouse_id', 4)->value('qty'))->toBe(14)
        ->and(DB::table('inventory_history')->value('warehouse_id'))->toBe(4)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeTrue();
});

it('prefers the warehouse assigned directly to the DR logistics location', function () {
    $fixture = billingOperationalFixture(logisticsLocationId: 2);
    DB::table('logistics_location')->insert([
        ['logistics_location_id' => 1, 'region' => 'Region IV-A', 'warehouse_id' => 4],
        ['logistics_location_id' => 2, 'region' => 'Other Region', 'warehouse_id' => 9],
    ]);
    DB::table('inventory')->insert([
        ['item_id' => 91, 'warehouse_id' => 4, 'qty' => 30, 'inventory_status' => 'Approved'],
        ['item_id' => 91, 'warehouse_id' => 9, 'qty' => 20, 'inventory_status' => 'Approved'],
    ]);

    app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User');

    expect(DB::table('inventory')->where('warehouse_id', 4)->value('qty'))->toBe(30)
        ->and(DB::table('inventory')->where('warehouse_id', 9)->value('qty'))->toBe(14)
        ->and(DB::table('inventory_history')->value('warehouse_id'))->toBe(9);
});

it('stops without changing inventory when a school region maps to multiple warehouses', function () {
    $fixture = billingOperationalFixture();
    DB::table('logistics_location')->insert([
        ['region' => 'Region IV-A', 'warehouse_id' => 4],
        ['region' => 'Region IV-A', 'warehouse_id' => 9],
    ]);
    DB::table('inventory')->insert([
        ['item_id' => 91, 'warehouse_id' => 4, 'qty' => 20, 'inventory_status' => 'Approved'],
        ['item_id' => 91, 'warehouse_id' => 9, 'qty' => 20, 'inventory_status' => 'Approved'],
    ]);

    expect(fn () => app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User'))
        ->toThrow(RuntimeException::class, 'maps to multiple warehouses')
        ->and(DB::table('inventory')->sum('qty'))->toBe(40)
        ->and(DB::table('inventory_history')->count())->toBe(0)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeFalse();
});

it('stops with a clear error when no warehouse matches the school region', function () {
    $fixture = billingOperationalFixture();
    DB::table('logistics_location')->insert(['region' => 'Region VI', 'warehouse_id' => 4]);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 4, 'qty' => 20, 'inventory_status' => 'Approved']);

    expect(fn () => app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User'))
        ->toThrow(RuntimeException::class, 'school region Region IV-A for DR DR-7001 has no warehouse mapping')
        ->and(DB::table('inventory')->value('qty'))->toBe(20)
        ->and(DB::table('inventory_history')->count())->toBe(0);
});
