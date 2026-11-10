<?php

use App\Services\BillingOperationalSyncService;
use App\Services\Sme2InventoryReconciliationService;
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
        Schema::create('warehouse', function (Blueprint $table): void {
            $table->increments('warehouse_id');
            $table->string('warehouse_name');
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
            $table->unsignedInteger('changed_by_user_id')->nullable();
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
            $table->string('previous_status')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
}

pest()->extend(BillingOperationalSyncServiceTestCase::class)->in(__FILE__);

/** @return array{project_id: int, delivery_id: int, package_id: int} */
function completedSme2Fixture(string $region = 'Region I'): array
{
    $fixture = billingOperationalFixture(region: $region);
    DB::table('projects')->where('project_id', $fixture['project_id'])->update(['project_id' => 502698]);
    DB::table('deliveries')->where('delivery_id', $fixture['delivery_id'])->update(['project_id' => 502698, 'status' => 'delivered']);
    DB::table('item')->where('item_id', 91)->update(['project_id' => 502698]);
    DB::table('warehouse')->insert(['warehouse_id' => 2, 'warehouse_name' => 'Pampanga']);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 2, 'qty' => 20, 'inventory_status' => 'Approved']);
    $fixture['project_id'] = 502698;

    return $fixture;
}

it('deducts a completed delivery through region routing with exact remarks and no repeat deduction', function () {
    $fixture = completedSme2Fixture();
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([]);
    expect($preview['lines'][0]['quantity'])->toBe(6)
        ->and(DB::table('inventory')->value('qty'))->toBe(20);

    $service->reconcile([], true, $preview['fingerprint']);

    $this->assertDatabaseHas('inventory_history', ['delivery_id' => $fixture['delivery_id'], 'package_id' => $fixture['package_id'], 'quantity' => 6, 'old_qty' => 20, 'new_qty' => 14, 'remarks' => 'Bypass Encoding']);
    expect(DB::table('inventory')->value('qty'))->toBe(14)
        ->and($service->reconcile([])['lines'])->toBe([]);
    $rerun = $service->reconcile([]);
    $service->reconcile([], true, $rerun['fingerprint']);
    expect(DB::table('inventory_history')->count())->toBe(1);
});

it('credits recovered scanner deductions and applies only the remaining quantity', function () {
    $fixture = completedSme2Fixture();
    $statusId = (int) DB::table('package_status')->value('package_status_id');
    $historyId = (int) DB::table('inventory_history')->insertGetId(['inventory_id' => 1, 'item_id' => 91, 'warehouse_id' => 2, 'old_qty' => 23, 'new_qty' => 20, 'change_type' => 'stock_out']);
    $mapping = [['history_id' => $historyId, 'delivery_id' => $fixture['delivery_id'], 'package_id' => $fixture['package_id'], 'package_status_id' => $statusId, 'item_id' => 91, 'warehouse_id' => 2, 'deducted_quantity' => 3]];
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile($mapping);
    expect($preview['lines'][0]['quantity'])->toBe(3);

    $service->reconcile($mapping, true, $preview['fingerprint']);

    expect(DB::table('inventory')->value('qty'))->toBe(17)
        ->and($service->reconcile($mapping)['lines'])->toBe([]);
    expect(fn () => $service->reconcile([...$mapping, ...$mapping]))->toThrow(RuntimeException::class, 'repeated recovered scanner mapping');
});

it('does not treat a failed or pending stock-out as completed movement', function () {
    $fixture = completedSme2Fixture();
    DB::table('deliveries')->where('delivery_id', $fixture['delivery_id'])->update(['status' => 'pending']);

    $preview = app(Sme2InventoryReconciliationService::class)->reconcile([]);

    expect($preview['lines'])->toBe([])
        ->and(DB::table('inventory')->value('qty'))->toBe(20);
});

it('leaves missing mappings and quantities unresolved with specific reasons', function () {
    completedSme2Fixture(region: 'Unknown region');
    $service = app(Sme2InventoryReconciliationService::class);
    expect($service->reconcile([])['rows'][0]['reason'])->toContain('missing_warehouse_mapping');
    DB::table('package_content')->update(['qty' => null]);

    $preview = $service->reconcile([]);

    expect($preview['rows'][0]['reason'])->toBe('missing_item_or_package_quantity')
        ->and($preview['lines'])->toBe([]);
});

it('rejects stale previews and holds insufficient stock without changing inventory history', function () {
    completedSme2Fixture();
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([]);
    expect(fn () => $service->reconcile([], true, 'stale'))->toThrow(RuntimeException::class, 'Preview changed');
    DB::table('inventory')->update(['qty' => 1]);

    $blocked = $service->reconcile([]);
    expect($blocked['rows'][0]['reason'])->toBe('blocked_insufficient_stock')
        ->and($blocked['lines'])->toBe([]);
    $service->reconcile([], true, $blocked['fingerprint']);
    expect(DB::table('inventory')->value('qty'))->toBe(1)
        ->and(DB::table('inventory_history')->count())->toBe(0);
});

it('applies funded item lines while holding stock shortages and excluding other projects', function () {
    $fixture = completedSme2Fixture();
    DB::table('inventory')->update(['qty' => 1]);
    DB::table('item')->insert([['item_id' => 92, 'project_id' => 502698], ['item_id' => 99, 'project_id' => 123]]);
    DB::table('package_content')->insert(['package_id' => $fixture['package_id'], 'item_id' => 92, 'qty' => 1]);
    DB::table('inventory')->insert([['item_id' => 92, 'warehouse_id' => 2, 'qty' => 9], ['item_id' => 99, 'warehouse_id' => 2, 'qty' => 50]]);
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([]);

    $service->reconcile([], true, $preview['fingerprint']);

    expect(DB::table('inventory')->where('item_id', 91)->value('qty'))->toBe(1)
        ->and(DB::table('inventory')->where('item_id', 92)->value('qty'))->toBe(6)
        ->and(DB::table('inventory')->where('item_id', 99)->value('qty'))->toBe(50)
        ->and(DB::table('inventory_history')->count())->toBe(1)
        ->and($service->reconcile([])['lines'])->toBe([]);
});

it('adds only authorized shortfalls with exact stock-in remarks and does not repeat them', function () {
    $fixture = completedSme2Fixture();
    DB::table('inventory')->where('item_id', 91)->update(['qty' => 1]);
    DB::table('item')->insert(['item_id' => 92, 'project_id' => 502698]);
    DB::table('package_content')->insert(['package_id' => $fixture['package_id'], 'item_id' => 92, 'qty' => 1]);
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([], replenishShortfalls: true);

    $service->reconcile([], true, $preview['fingerprint'], true);

    $remark = 'bypass inventories due to insufficient inventory during bypass encoding of stock out and deliveries';
    $this->assertDatabaseHas('inventory_history', ['item_id' => 91, 'change_type' => 'stock_in', 'quantity' => 5, 'old_qty' => 1, 'new_qty' => 6, 'remarks' => $remark]);
    $this->assertDatabaseHas('inventory_history', ['item_id' => 92, 'change_type' => 'stock_in', 'quantity' => 3, 'remarks' => $remark]);
    expect(DB::table('inventory_history')->where('change_type', 'stock_in')->value('batch_no'))->toBe($preview['batch_no'].'-IN-W2');
    expect(DB::table('inventory')->sum('qty'))->toBe(0)
        ->and(DB::table('inventory_history')->count())->toBe(4);
    $again = $service->reconcile([], replenishShortfalls: true);
    $service->reconcile([], true, $again['fingerprint'], true);
    expect(DB::table('inventory_history')->count())->toBe(4);
});

it('stocks out only requested pending schools and ignores the school on a repeated run', function () {
    $fixture = completedSme2Fixture();
    DB::table('deliveries')->where('delivery_id', $fixture['delivery_id'])->update(['status' => 'pending']);
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([], replenishShortfalls: true, stockOutSchoolIds: ['SCH-1', 'NOT-FOUND']);
    expect($preview['schools'][1]['reason'])->toBe('school_not_found_in_sme2')
        ->and(DB::table('package_status')->value('status'))->toBe('pending');

    $service->reconcile([], true, $preview['fingerprint'], true, ['SCH-1', 'NOT-FOUND']);

    expect(DB::table('package_status')->value('status'))->toBe('released')
        ->and(DB::table('inventory')->value('qty'))->toBe(14)
        ->and(DB::table('deliveries')->value('status'))->toBe('pending');
    $rerun = $service->reconcile([], replenishShortfalls: true, stockOutSchoolIds: ['SCH-1']);
    expect($rerun['lines'])->toBe([])
        ->and($rerun['release_status_ids'])->toBe([])
        ->and($rerun['schools'][0]['reason'])->toBe('skip_already_stock_out');
});

it('ignores every package of a requested school that already has a delivered row', function () {
    completedSme2Fixture();
    DB::table('deliveries')->insert(['project_id' => 502698, 'school_id' => 'SCH-1', 'lot_id' => 7, 'dr_no' => 'SECOND', 'package_qty' => 1, 'status' => 'pending']);
    $service = app(Sme2InventoryReconciliationService::class);

    $preview = $service->reconcile([], replenishShortfalls: true, stockOutSchoolIds: ['SCH-1']);

    expect($preview['schools'][0]['reason'])->toBe('skip_already_delivered')
        ->and($preview['lines'])->toBe([])
        ->and($preview['release_status_ids'])->toBe([])
        ->and(DB::table('inventory')->value('qty'))->toBe(20);
});

it('creates the missing status row for an allocated requested school package', function () {
    $fixture = completedSme2Fixture();
    DB::table('deliveries')->where('delivery_id', $fixture['delivery_id'])->update(['status' => 'pending']);
    DB::table('package_status')->delete();
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([], replenishShortfalls: true, stockOutSchoolIds: ['SCH-1']);

    $service->reconcile([], true, $preview['fingerprint'], true, ['SCH-1']);

    $this->assertDatabaseHas('package_status', ['delivery_id' => $fixture['delivery_id'], 'package_id' => $fixture['package_id'], 'status' => 'released']);
    expect(DB::table('inventory')->value('qty'))->toBe(14)
        ->and($service->reconcile([], replenishShortfalls: true, stockOutSchoolIds: ['SCH-1'])['lines'])->toBe([]);
});

it('records the source school and exact history metadata in bulk reconciliation audits', function () {
    completedSme2Fixture();
    Schema::table('school', function (Blueprint $table): void {
        $table->string('school_name')->nullable();
    });
    DB::table('school')->update(['school_name' => 'Test School']);
    Schema::create('delivery_audit_events', function (Blueprint $table): void {
        $table->id();
        $table->string('source_key')->unique();
        foreach (['event_uuid', 'event_at', 'project_id', 'lot_id', 'school_id', 'school_name', 'package_code', 'dr_no', 'event_type', 'quantity', 'warehouse_id', 'warehouse_name', 'performed_by', 'actor_name', 'source_module', 'related_type', 'related_id', 'notes', 'metadata'] as $column) {
            $table->text($column)->nullable();
        }
        $table->timestamps();
    });
    $service = app(Sme2InventoryReconciliationService::class);
    $preview = $service->reconcile([]);

    $service->reconcile([], true, $preview['fingerprint']);

    $audit = DB::table('delivery_audit_events')->first();
    $history = DB::table('inventory_history')->first();
    expect($audit->school_name)->toBe('Test School')
        ->and($audit->source_key)->toBe('legacy:inventory_history:'.$history->history_id)
        ->and($audit->notes)->toBe('Bypass Encoding')
        ->and(json_decode($audit->metadata, true)['quantity'])->toBe(6)
        ->and(DB::table('delivery_audit_events')->count())->toBe(1);
});

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
    $fixture = billingOperationalFixture(region: 'Region I');
    DB::table('warehouse')->insert(['warehouse_id' => 2, 'warehouse_name' => 'Pampanga']);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 2, 'qty' => 20, 'inventory_status' => 'Approved']);

    $result = app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User');

    expect($result['synced_dr_count'])->toBe(1)
        ->and(DB::table('inventory')->where('warehouse_id', 2)->value('qty'))->toBe(14)
        ->and(DB::table('inventory_history')->value('warehouse_id'))->toBe(2)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeTrue();
});

it('prefers the warehouse assigned directly to the DR logistics location', function () {
    $fixture = billingOperationalFixture(logisticsLocationId: 2);
    DB::table('logistics_location')->insert([
        ['logistics_location_id' => 1, 'region' => 'Region IV-A', 'warehouse_id' => 4],
        ['logistics_location_id' => 2, 'region' => 'Other Region', 'warehouse_id' => 9],
    ]);
    DB::table('warehouse')->insert([
        ['warehouse_id' => 4, 'warehouse_name' => 'Pampanga'],
        ['warehouse_id' => 9, 'warehouse_name' => 'Other Region'],
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

it('records an already released package as stock out without deducting inventory again', function () {
    $fixture = billingOperationalFixture(region: 'Region I');
    DB::table('warehouse')->insert(['warehouse_id' => 2, 'warehouse_name' => 'Pampanga']);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 2, 'qty' => 20, 'inventory_status' => 'Approved']);
    DB::table('package_status')->where('delivery_id', $fixture['delivery_id'])->update(['status' => 'released']);

    app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User');

    $history = DB::table('inventory_history')->first();
    expect(DB::table('inventory')->where('warehouse_id', 2)->value('qty'))->toBe(20)
        ->and($history->change_type)->toBe('stock_out')
        ->and($history->old_qty)->toBeNull()
        ->and($history->new_qty)->toBeNull()
        ->and($history->quantity)->toBe(0)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeTrue();
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

it('stops with a clear error when a school region has no configured warehouse hub', function () {
    $fixture = billingOperationalFixture(region: 'Unknown Region');
    DB::table('logistics_location')->insert(['region' => 'Region VI', 'warehouse_id' => 4]);
    DB::table('inventory')->insert(['item_id' => 91, 'warehouse_id' => 4, 'qty' => 20, 'inventory_status' => 'Approved']);

    expect(fn () => app(BillingOperationalSyncService::class)->syncProject($fixture['project_id'], 12, 'Billing User'))
        ->toThrow(RuntimeException::class, 'school region Unknown Region for DR DR-7001 has no configured warehouse hub')
        ->and(DB::table('inventory')->value('qty'))->toBe(20)
        ->and(DB::table('inventory_history')->count())->toBe(0);
});
