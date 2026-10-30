<?php

use App\Models\DeliveryAuditEvent;
use App\Models\DeliveryHistory;
use App\Models\InventoryHistory;
use App\Services\DeliveryAuditTrailService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryAuditTrailTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, DeliveryAuditTrailConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--path' => [database_path('migrations/2026_10_29_095151_create_delivery_audit_events_table.php')],
            '--realpath' => true,
            '--force' => true,
        ]);
        Schema::create('inventory_history', function (Blueprint $table): void {
            $table->increments('history_id');
            $table->string('change_type');
            $table->integer('project_id')->nullable();
            $table->integer('delivery_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('old_qty')->nullable();
            $table->integer('new_qty')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('changed_by_user_id')->nullable();
            $table->string('changed_by')->nullable();
            $table->string('dr_no')->nullable();
            $table->string('remarks')->nullable();
            $table->dateTime('changed_at')->nullable();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_name');
        });
        Schema::create('lot', function (Blueprint $table): void {
            $table->increments('lot_id');
            $table->string('lot_name');
        });
        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->string('school_name');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->string('name');
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->integer('project_id');
            $table->integer('lot_id')->nullable();
            $table->string('school_id');
            $table->string('dr_no');
        });
        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->integer('delivery_id');
            $table->integer('package_id')->nullable();
        });
        Schema::create('delivery_history', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('package_status_id');
            $table->integer('user_id')->nullable();
            $table->string('status');
            $table->string('previous_status')->nullable();
            $table->string('remarks')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('delivery_billing_records', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('delivery_id');
            $table->integer('billing_batch_id')->nullable();
        });
        Schema::create('delivery_billing_histories', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('delivery_billing_record_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->integer('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->string('remarks')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('logistics_webhook_events', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('integration_id');
            $table->string('external_event_id');
            $table->string('dr_reference');
            $table->integer('resolved_project_id')->nullable();
            $table->string('resolved_school_id')->nullable();
            $table->string('processing_status');
            $table->unsignedInteger('processing_attempts')->default(0);
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('event_occurred_at')->nullable();
            $table->string('event_type');
            $table->string('reconciliation_status')->nullable();
            $table->string('reconciliation_details')->nullable();
            $table->string('error_details')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
}

uses(DeliveryAuditTrailTestCase::class);

it('prevents duplicate source events and retains corrections as separate rows', function (): void {
    $service = app(DeliveryAuditTrailService::class);
    $event = $service->record('inventory:history:88', [
        'event_type' => 'stock_out', 'source_module' => 'Inventory', 'performed_by' => 14,
        'actor_name' => 'Warehouse Operator', 'previous_status' => 'available', 'new_status' => 'released',
    ]);
    $retry = $service->record('inventory:history:88', ['event_type' => 'stock_out', 'source_module' => 'Inventory']);
    $service->record('correction:'.$event->event_uuid.':14:reason', [
        'event_type' => 'correction', 'source_module' => 'Delivery Audit Trail', 'related_id' => $event->event_uuid,
        'performed_by' => 14, 'notes' => 'Corrected quantity entry',
    ]);

    expect($retry->id)->toBe($event->id)
        ->and(DeliveryAuditEvent::query()->count())->toBe(2)
        ->and(DeliveryAuditEvent::query()->findOrFail($event->id)->new_status)->toBe('released');
});

it('records user attributed inventory events as their source row is created', function (): void {
    InventoryHistory::query()->create([
        'change_type' => 'stock_in',
        'old_qty' => 2,
        'new_qty' => 9,
        'quantity' => 7,
        'changed_by_user_id' => 7,
        'changed_by' => 'Warehouse Clerk',
        'changed_at' => '2026-10-24 11:00:00',
    ]);

    $event = DeliveryAuditEvent::query()->firstOrFail();

    expect($event->event_type)->toBe('stock_in')
        ->and($event->performed_by)->toBe(7)
        ->and($event->event_at->toDateTimeString())->toBe('2026-10-24 11:00:00');
});

it('records an explicit previous and new package status on a delivery transition', function (): void {
    DB::table('projects')->insert(['project_id' => 8, 'project_name' => 'Transition project']);
    DB::table('school')->insert(['school_id' => 'SCH-08', 'school_name' => 'East School']);
    DB::table('deliveries')->insert(['delivery_id' => 18, 'project_id' => 8, 'lot_id' => 3, 'school_id' => 'SCH-08', 'dr_no' => 'DR-818']);
    DB::table('package_status')->insert(['package_status_id' => 28, 'delivery_id' => 18]);

    $history = new DeliveryHistory([
        'package_status_id' => 28,
        'user_id' => 7,
        'previous_status' => 'released',
        'status' => 'delivered',
        'remarks' => 'Received by school representative.',
    ]);
    $history->created_at = now();
    $history->save();

    $event = DeliveryAuditEvent::query()->where('source_key', 'legacy:delivery_history:'.$history->getKey())->firstOrFail();

    expect($event->previous_status)->toBe('released')
        ->and($event->new_status)->toBe('delivered')
        ->and($event->school_id)->toBe('SCH-08')
        ->and($event->performed_by)->toBe(7);
});

it('imports recorded inventory and delivery transitions with known actors and statuses', function (): void {
    DB::table('users')->insert(['user_id' => 7, 'name' => 'Warehouse Clerk']);
    DB::table('projects')->insert(['project_id' => 2, 'project_name' => 'Pilot']);
    DB::table('school')->insert(['school_id' => 'SCH-02', 'school_name' => 'Central School']);
    DB::table('deliveries')->insert(['delivery_id' => 5, 'project_id' => 2, 'lot_id' => 3, 'school_id' => 'SCH-02', 'dr_no' => 'DR-105']);
    DB::table('package_status')->insert(['package_status_id' => 9, 'delivery_id' => 5]);
    DB::table('inventory_history')->insert(['history_id' => 31, 'change_type' => 'stock_in', 'old_qty' => 4, 'new_qty' => 12, 'changed_by_user_id' => 7, 'changed_by' => 'Warehouse Clerk', 'changed_at' => '2026-10-20 10:00:00']);
    DB::table('delivery_history')->insert(['id' => 22, 'package_status_id' => 9, 'user_id' => 7, 'status' => 'delivered', 'previous_status' => 'released', 'created_at' => '2026-10-21 10:00:00']);

    app(DeliveryAuditTrailService::class)->syncRecordedHistory();
    $stockIn = DeliveryAuditEvent::query()->where('event_type', 'stock_in')->firstOrFail();
    $delivered = DeliveryAuditEvent::query()->where('event_type', 'delivery_status')->firstOrFail();

    expect($stockIn->performed_by)->toBe(7)
        ->and($delivered->previous_status)->toBe('released')
        ->and($delivered->new_status)->toBe('delivered')
        ->and($delivered->school_id)->toBe('SCH-02');
});

it('keeps billing confirmation separate from delivery and stock movement events', function (): void {
    app(DeliveryAuditTrailService::class)->record('billing:confirm:6', [
        'event_type' => 'billing_billed', 'source_module' => 'Billing', 'previous_status' => 'for_billing',
        'new_status' => 'billed', 'related_type' => 'billing_batch', 'related_id' => '6',
    ]);

    expect(DeliveryAuditEvent::query()->where('event_type', 'billing_billed')->exists())->toBeTrue()
        ->and(DeliveryAuditEvent::query()->whereIn('event_type', ['stock_out', 'delivered'])->exists())->toBeFalse();
});

it('imports billing confirmations and webhook receipt and processing timestamps as distinct events', function (): void {
    DB::table('projects')->insert(['project_id' => 4, 'project_name' => 'Audit project']);
    DB::table('school')->insert(['school_id' => 'SCH-04', 'school_name' => 'North School']);
    DB::table('deliveries')->insert(['delivery_id' => 12, 'project_id' => 4, 'lot_id' => 3, 'school_id' => 'SCH-04', 'dr_no' => 'DR-412']);
    DB::table('delivery_billing_records')->insert(['id' => 3, 'delivery_id' => 12, 'billing_batch_id' => 18]);
    DB::table('delivery_billing_histories')->insert(['id' => 42, 'delivery_billing_record_id' => 3, 'previous_status' => 'for_billing', 'new_status' => 'billed', 'changed_by' => 7, 'changed_by_name' => 'Finance User', 'created_at' => '2026-10-22 09:30:00']);
    DB::table('logistics_webhook_events')->insert([
        ['id' => 5, 'integration_id' => 'provider-a', 'external_event_id' => 'event-77', 'dr_reference' => 'DR-412', 'resolved_project_id' => 4, 'resolved_school_id' => 'SCH-04', 'processing_status' => 'processed', 'received_at' => '2026-10-22 10:00:00', 'processed_at' => '2026-10-22 10:00:03', 'event_occurred_at' => '2026-10-22 10:00:00', 'event_type' => 'delivery.logistics_received', 'reconciliation_status' => 'accepted'],
        ['id' => 7, 'integration_id' => 'provider-a', 'external_event_id' => 'event-79', 'dr_reference' => 'DR-412', 'resolved_project_id' => 4, 'resolved_school_id' => 'SCH-04', 'processing_status' => 'processed', 'received_at' => '2026-10-22 11:00:00', 'processed_at' => '2026-10-22 11:00:03', 'event_occurred_at' => '2026-10-22 11:00:00', 'event_type' => 'delivery.delivered', 'reconciliation_status' => 'accepted'],
    ]);

    app(DeliveryAuditTrailService::class)->syncRecordedHistory();

    expect(DeliveryAuditEvent::query()->where('event_type', 'billing_billed')->value('new_status'))->toBe('billed')
        ->and(DeliveryAuditEvent::query()->where('event_type', 'webhook_received')->count())->toBe(2)
        ->and(DeliveryAuditEvent::query()->where('event_type', 'webhook_processed')->count())->toBe(2)
        ->and(DeliveryAuditEvent::query()->where('event_type', 'logistics_received')->count())->toBe(1)
        ->and(DeliveryAuditEvent::query()->where('event_type', 'logistics_delivered')->count())->toBe(1)
        ->and(DeliveryAuditEvent::query()->where('event_type', 'delivered')->exists())->toBeFalse();
});

it('records each webhook processing attempt with its integration as actor', function (): void {
    DB::table('logistics_webhook_events')->insert([
        'id' => 6,
        'integration_id' => 'provider-b',
        'external_event_id' => 'event-78',
        'dr_reference' => 'DR-500',
        'processing_status' => 'processing',
        'processing_attempts' => 2,
        'received_at' => '2026-10-23 12:00:00',
        'event_type' => 'delivery.logistics_received',
        'updated_at' => '2026-10-23 12:02:00',
    ]);

    app(DeliveryAuditTrailService::class)->recordWebhookProcessingAttempt(6);

    $event = DeliveryAuditEvent::query()->firstOrFail();
    expect($event->event_type)->toBe('webhook_processing')
        ->and($event->actor_name)->toBe('Logistics Integration')
        ->and($event->event_at->toDateTimeString())->toBe('2026-10-23 12:02:00');
});

it('flags ambiguous exact DR matches and leaves the school unidentified', function (): void {
    DB::table('projects')->insert(['project_id' => 5, 'project_name' => 'Conflicting project']);
    DB::table('school')->insert([
        ['school_id' => 'SCH-A', 'school_name' => 'School A'],
        ['school_id' => 'SCH-B', 'school_name' => 'School B'],
    ]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 21, 'project_id' => 5, 'lot_id' => 3, 'school_id' => 'SCH-A', 'dr_no' => 'DR-SHARED'],
        ['delivery_id' => 22, 'project_id' => 5, 'lot_id' => 3, 'school_id' => 'SCH-B', 'dr_no' => 'DR-SHARED'],
    ]);
    DB::table('inventory_history')->insert([
        'history_id' => 55, 'project_id' => 5, 'dr_no' => 'DR-SHARED', 'change_type' => 'stock_out',
        'old_qty' => 10, 'new_qty' => 6, 'changed_at' => '2026-10-23 10:00:00',
    ]);

    app(DeliveryAuditTrailService::class)->syncRecordedHistory();
    $event = DeliveryAuditEvent::query()->where('source_key', 'legacy:inventory_history:55')->firstOrFail();

    expect($event->school_id)->toBeNull()
        ->and($event->metadata['mapping_review'])->toContain('Multiple exact delivery records');
});

it('protects the trail and correction routes with separate permissions', function (): void {
    $routes = app('router')->getRoutes();

    expect($routes->getByName('delivery-audit-trail.index')->gatherMiddleware())->toContain('permission:delivery.audit.view')
        ->and($routes->getByName('delivery-audit-trail.show')->gatherMiddleware())->toContain('permission:delivery.audit.view')
        ->and($routes->getByName('delivery-audit-trail.corrections.store')->gatherMiddleware())->toContain('permission:delivery.audit.correct');
});

class DeliveryAuditTrailConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function schedule(Schedule $schedule): void {}

    protected function commands(): void {}
}
