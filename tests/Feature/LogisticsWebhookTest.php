<?php

use App\Jobs\ProcessLogisticsWebhookEvent;
use App\Services\LogisticsDrResolver;
use App\Services\LogisticsDrTrackingService;
use App\Services\LogisticsWebhookEventService;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LogisticsWebhookTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, LogisticsWebhookConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.logistics_webhooks.connection', 'sqlite');
        $app['config']->set('logistics-webhooks.keys', ['logistics-test' => 'test-secret']);
        $app['config']->set('logistics-webhooks.integration_scopes', ['logistics-test' => ['project_ids' => [10], 'school_ids' => []]]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--path' => [
                database_path('migrations/0001_01_01_000002_create_jobs_table.php'),
                database_path('migrations/2026_10_13_062831_create_logistics_webhook_events_table.php'),
                database_path('migrations/2026_10_13_064633_add_dr_scope_reconciliation_to_logistics_webhook_events_table.php'),
                database_path('migrations/2026_10_13_064633_create_logistics_webhook_dr_mappings_table.php'),
                database_path('migrations/2026_10_13_070536_create_logistics_dr_tracking_table.php'),
            ],
            '--realpath' => true,
            '--force' => true,
        ]);

        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_name')->nullable();
        });

        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->string('school_name')->nullable();
        });

        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->unsignedInteger('project_id');
            $table->string('school_id');
            $table->string('dr_no', 100);
            $table->string('status')->default('pending');
            $table->date('delivery_date')->nullable();
            $table->unsignedInteger('keystage_id')->nullable();
            $table->unsignedInteger('lot_id')->nullable();
            $table->unsignedInteger('package_qty')->default(1);
            $table->unsignedInteger('logistics_location_id')->nullable();
            $table->dateTime('delivered_date')->nullable();
        });

        Schema::create('package', function (Blueprint $table): void {
            $table->increments('package_id');
            $table->unsignedInteger('keystage_id')->nullable();
            $table->unsignedInteger('lot_id')->nullable();
        });

        Schema::create('logistics_location', function (Blueprint $table): void {
            $table->increments('logistics_location_id');
            $table->unsignedInteger('warehouse_id')->nullable();
        });

        Schema::create('package_content', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('package_id');
            $table->unsignedInteger('item_id');
            $table->integer('qty')->default(1);
        });

        Schema::create('inventory', function (Blueprint $table): void {
            $table->increments('inventory_id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('item_id');
            $table->integer('qty')->default(0);
            $table->string('inventory_status')->default('Approved');
        });

        Schema::create('inventory_history', function (Blueprint $table): void {
            $table->increments('history_id');
            $table->string('batch_no');
            $table->unsignedInteger('inventory_id')->nullable();
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('warehouse_id');
            $table->integer('old_qty')->nullable();
            $table->integer('new_qty')->nullable();
            $table->string('changed_by');
            $table->text('remarks')->nullable();
            $table->string('change_type');
            $table->dateTime('changed_at')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('delivery_id')->nullable();
            $table->unsignedInteger('package_id')->nullable();
            $table->string('dr_no')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('integration_id', 64)->nullable();
            $table->string('webhook_event_id', 191)->nullable();
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

        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('package_id');
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->unsignedInteger('delivered_by')->nullable();
            $table->string('receiver_name')->nullable();
        });

        Queue::fake();
    }
}

class LogisticsWebhookConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(LogisticsWebhookTestCase::class)->in(__DIR__.'/LogisticsWebhookTest.php');

function logisticsWebhookPayload(array $overrides = []): array
{
    return array_merge([
        'event_id' => 'logistics-event-001',
        'event_type' => 'delivery.logistics_received',
        'dr_reference' => '000123-DR',
        'occurred_at' => '2026-10-13T14:30:00+08:00',
        'sequence' => 42,
    ], $overrides);
}

function logisticsWebhookRequest(
    LogisticsWebhookTestCase $testCase,
    string $body,
    ?string $timestamp = null,
    ?string $signature = null,
    bool $withAuthentication = true
): TestResponse {
    $timestamp ??= (string) time();
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ];

    if ($withAuthentication) {
        $headers['HTTP_X_INTEGRATION_KEY_ID'] = 'logistics-test';
        $headers['HTTP_X_WEBHOOK_TIMESTAMP'] = $timestamp;
        $headers['HTTP_X_WEBHOOK_SIGNATURE'] = 'v1='.($signature ?? hash_hmac(
            'sha256',
            "logistics-test\n".$timestamp."\n".$body,
            'test-secret'
        ));
    }

    return $testCase->call(
        'POST',
        '/api/v1/logistics/webhooks/delivery-status',
        [],
        [],
        [],
        $headers,
        $body
    );
}

function processLogisticsWebhookEvent(string $eventId): object
{
    $event = DB::table('logistics_webhook_events')->where('external_event_id', $eventId)->first();
    (new ProcessLogisticsWebhookEvent((int) $event->id))->handle(
        app(LogisticsDrResolver::class),
        app(LogisticsDrTrackingService::class)
    );

    return DB::table('logistics_webhook_events')->where('id', $event->id)->first();
}

function insertDeliveryFixture(int $projectId, string $schoolId, string $drNumber, string $status = 'pending', ?int $lotId = null): int
{
    return (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'dr_no' => $drNumber,
        'status' => $status,
        'delivery_date' => '2026-10-13',
        'lot_id' => $lotId,
    ], 'delivery_id');
}

function prepareLogisticsStockOut(int $deliveryId, int $stock = 20, int $packageId = 900): void
{
    DB::table('logistics_location')->insert(['logistics_location_id' => 1, 'warehouse_id' => 3]);
    DB::table('deliveries')->where('delivery_id', $deliveryId)->update([
        'lot_id' => 5, 'logistics_location_id' => 1, 'package_qty' => 2,
    ]);
    DB::table('package')->insert(['package_id' => $packageId, 'lot_id' => 5]);
    DB::table('package_status')->insert(['delivery_id' => $deliveryId, 'package_id' => $packageId, 'status' => 'pending']);
    DB::table('package_content')->insert(['package_id' => $packageId, 'item_id' => 77, 'qty' => 3]);
    DB::table('inventory')->insert(['warehouse_id' => 3, 'item_id' => 77, 'qty' => $stock]);
}

it('counts distinct pending DR scopes while Logistics tracking is empty', function () {
    insertDeliveryFixture(10, 'school-A', 'DR-100');
    insertDeliveryFixture(10, 'school-A', 'DR-100');
    insertDeliveryFixture(10, 'school-B', 'DR-100');
    insertDeliveryFixture(11, 'school-A', 'DR-100');

    expect(app(LogisticsDrTrackingService::class)->dashboardSummary())->toMatchArray([
        'logistics_received' => 0,
        'logistics_delivered' => 0,
        'pending_updates' => 3,
        'reconciliation_required' => 0,
        'total_scoped_drs' => 3,
        'delivery_performance_percentage' => 0,
        'partner_performance' => [],
    ]);
});

it('removes a verified DR scope from pending counts after Logistics Received', function () {
    insertDeliveryFixture(10, 'school-A', 'DR-150', 'warehouse');
    insertDeliveryFixture(10, 'school-A', 'DR-150', 'warehouse');
    $payload = logisticsWebhookPayload(['dr_reference' => 'DR-150']);
    logisticsWebhookRequest($this, json_encode($payload, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($payload['event_id']);

    expect(app(LogisticsDrTrackingService::class)->dashboardSummary())->toMatchArray([
        'logistics_received' => 1,
        'logistics_delivered' => 0,
        'pending_updates' => 0,
    ]);
});

it('filters pending scope summaries and keeps exact scope counts', function () {
    DB::table('school')->insert([
        ['school_id' => 'school-A', 'school_name' => 'Central School'],
        ['school_id' => 'school-B', 'school_name' => 'West School'],
    ]);
    insertDeliveryFixture(10, 'school-A', 'DR-200');
    insertDeliveryFixture(10, 'school-A', 'DR-200');
    insertDeliveryFixture(10, 'school-B', 'DR-200');
    insertDeliveryFixture(11, 'school-A', 'DR-201');

    $summary = app(LogisticsDrTrackingService::class)->dashboardSummary([
        'project_id' => 10,
        'dr_reference' => 'DR-200',
        'school' => 'Central',
        'date_from' => '2026-10-10',
        'date_to' => '2026-10-15',
    ]);

    expect($summary['pending_updates'])->toBe(1);
});

it('uses exact collation-safe school joins and skips the tracking anti-join when empty', function () {
    DB::table('school')->insert(['school_id' => 'school-A', 'school_name' => 'Central School']);
    insertDeliveryFixture(10, 'school-A', 'DR-300');
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    app(LogisticsDrTrackingService::class)->dashboardSummary(['school' => 'Central']);

    $pendingQueries = array_values(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'pending_scopes')));
    expect($pendingQueries)->toHaveCount(1)
        ->and(str_contains($pendingQueries[0], 'COLLATE BINARY'))->toBeTrue()
        ->and(str_contains(strtolower($pendingQueries[0]), 'not exists'))->toBeFalse()
        ->and(str_contains(strtolower($pendingQueries[0]), 'join `projects`'))->toBeFalse();
});

it('authenticates, stores, queues and resolves a DR event without changing delivery data', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', '000123-DR', 'warehouse');
    DB::table('package_status')->insert(['delivery_id' => $deliveryId, 'package_id' => 900, 'status' => 'released']);
    $payload = logisticsWebhookPayload();
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $response = logisticsWebhookRequest($this, $body);

    $response->assertAccepted()
        ->assertJsonPath('code', 'event_accepted')
        ->assertJsonPath('event_id', $payload['event_id']);

    $event = DB::table('logistics_webhook_events')->first();
    expect($event->processing_status)->toBe('received')
        ->and($event->queued_at)->not->toBeNull()
        ->and($event->payload_hash)->toBe(hash('sha256', $body))
        ->and(DB::getSchemaBuilder()->hasTable('deliveries'))->toBeTrue()
        ->and(DB::getSchemaBuilder()->hasTable('package_status'))->toBeTrue();

    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, fn (ProcessLogisticsWebhookEvent $job): bool => $job->eventRowId === $event->id);

    $processed = processLogisticsWebhookEvent($payload['event_id']);
    expect($processed->processing_status)->toBe('processed')
        ->and($processed->resolution_status)->toBe('resolved')
        ->and($processed->reconciliation_status)->toBe('accepted')
        ->and((int) $processed->resolved_project_id)->toBe(10)
        ->and($processed->resolved_school_id)->toBe('school-A')
        ->and($processed->logistics_status)->toBe('logistics_received')
        ->and((int) $processed->event_sequence)->toBe(42)
        ->and(DB::table('deliveries')->where('delivery_id', $deliveryId)->value('status'))->toBe('warehouse')
        ->and(DB::table('package_status')->where('delivery_id', $deliveryId)->value('status'))->toBe('released')
        ->and(DB::table('logistics_dr_tracking')->count())->toBe(1)
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('logistics_received');

    logisticsWebhookRequest($this, $body)
        ->assertOk()
        ->assertJsonPath('code', 'duplicate_processed');

    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, 1);
    expect(DB::table('logistics_dr_tracking')->count())->toBe(1);
});

it('rejects a request without integration authentication', function () {
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR),
        withAuthentication: false
    )->assertUnauthorized()->assertJsonPath('code', 'invalid_signature');

    expect(DB::table('logistics_webhook_events')->count())->toBe(0);
});

it('rejects an invalid HMAC signature', function () {
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR),
        signature: str_repeat('0', 64)
    )->assertUnauthorized()->assertJsonPath('code', 'invalid_signature');
});

it('rejects an expired request timestamp', function () {
    $timestamp = (string) (time() - 301);
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR),
        timestamp: $timestamp
    )->assertUnauthorized()->assertJsonPath('code', 'stale_signature');
});

it('rejects malformed JSON after authenticating the exact request body', function () {
    logisticsWebhookRequest($this, '{invalid-json')->assertBadRequest()->assertJsonPath('code', 'malformed_json');
});

it('returns an idempotent acknowledgement for a repeated event', function () {
    $body = json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR);

    logisticsWebhookRequest($this, $body)->assertAccepted();
    logisticsWebhookRequest($this, $body)
        ->assertAccepted()
        ->assertJsonPath('code', 'duplicate_received');

    expect(DB::table('logistics_webhook_events')->count())->toBe(1);
    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, 1);
});

it('rejects an event id reused with a different payload', function () {
    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $conflictingBody = json_encode(logisticsWebhookPayload(['sequence' => 43]), JSON_THROW_ON_ERROR);

    logisticsWebhookRequest($this, $conflictingBody)
        ->assertStatus(409)
        ->assertJsonPath('code', 'event_id_conflict');

    expect(DB::table('logistics_webhook_events')->count())->toBe(1);
    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, 1);
});

it('rejects an unsupported event type', function () {
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(['event_type' => 'delivery.in_transit']), JSON_THROW_ON_ERROR)
    )->assertUnprocessable()->assertJsonPath('code', 'validation_failed');

    expect(DB::table('logistics_webhook_events')->count())->toBe(0);
});

it('requires sequence to be a JSON integer', function () {
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(['sequence' => '42']), JSON_THROW_ON_ERROR)
    )->assertUnprocessable()->assertJsonPath('code', 'validation_failed');
});

it('resolves a unique DR within its authenticated project scope', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('resolved')
        ->and($event->reconciliation_status)->toBe('accepted')
        ->and((int) $event->resolved_project_id)->toBe(10)
        ->and($event->resolved_school_id)->toBe('school-A');
});

it('quarantines the same DR used by different projects in integration scope', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    insertDeliveryFixture(20, 'school-B', '000123-DR');
    config(['logistics-webhooks.integration_scopes.logistics-test.project_ids' => [10, 20]]);

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('ambiguous')
        ->and($event->reconciliation_status)->toBe('ambiguous')
        ->and($event->resolved_project_id)->toBeNull()
        ->and(DB::table('logistics_dr_tracking')->count())->toBe(0);
});

it('quarantines the same DR reused across schools within one project', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    insertDeliveryFixture(10, 'school-B', '000123-DR');

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('ambiguous')
        ->and($event->resolved_school_id)->toBeNull()
        ->and(DB::table('logistics_dr_tracking')->count())->toBe(0);
});

it('resolves multiple delivery rows belonging to one DR project and school scope', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    insertDeliveryFixture(10, 'school-A', '000123-DR');

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('resolved')
        ->and($event->reconciliation_status)->toBe('accepted')
        ->and((int) $event->resolved_project_id)->toBe(10)
        ->and($event->resolved_school_id)->toBe('school-A')
        ->and(DB::table('logistics_dr_tracking')->count())->toBe(1);
});

it('quarantines an unknown DR reference', function () {
    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('unknown')
        ->and($event->reconciliation_status)->toBe('unknown')
        ->and($event->resolved_project_id)->toBeNull();
});

it('uses an approved integration DR mapping to resolve one exact receipt scope', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    insertDeliveryFixture(20, 'school-B', '000123-DR');
    DB::table('logistics_webhook_dr_mappings')->insert([
        'integration_id' => 'logistics-test',
        'dr_reference' => '000123-DR',
        'project_id' => 20,
        'school_id' => 'school-B',
        'approved_by' => 'test-reviewer',
        'approved_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    $event = processLogisticsWebhookEvent('logistics-event-001');

    expect($event->resolution_status)->toBe('resolved')
        ->and($event->resolved_school_id)->toBe('school-B')
        ->and((int) $event->resolved_project_id)->toBe(20);
});

it('records lower-sequence events without replacing the latest accepted milestone', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');

    $firstPayload = logisticsWebhookPayload(['sequence' => 10]);
    logisticsWebhookRequest($this, json_encode($firstPayload, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($firstPayload['event_id']);

    $olderPayload = logisticsWebhookPayload([
        'event_id' => 'logistics-event-002',
        'event_type' => 'delivery.delivered',
        'sequence' => 9,
        'occurred_at' => '2026-10-13T14:00:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($olderPayload, JSON_THROW_ON_ERROR))->assertAccepted();
    $olderEvent = processLogisticsWebhookEvent($olderPayload['event_id']);

    expect($olderEvent->reconciliation_status)->toBe('out_of_order')
        ->and(DB::table('logistics_webhook_events')->where('external_event_id', $firstPayload['event_id'])->value('logistics_status'))->toBe('logistics_received')
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('logistics_received')
        ->and(DB::table('deliveries')->value('status'))->toBe('pending');
});

it('records stale and duplicate sequence values without accepting them', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(['sequence' => 20]), JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent('logistics-event-001');

    $duplicateSequence = logisticsWebhookPayload([
        'event_id' => 'logistics-event-002',
        'sequence' => 20,
        'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($duplicateSequence, JSON_THROW_ON_ERROR))->assertAccepted();
    expect(processLogisticsWebhookEvent($duplicateSequence['event_id'])->reconciliation_status)->toBe('duplicate_sequence');

    $stale = logisticsWebhookPayload([
        'event_id' => 'logistics-event-003',
        'sequence' => 21,
        'occurred_at' => '2026-10-13T14:29:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($stale, JSON_THROW_ON_ERROR))->assertAccepted();
    expect(processLogisticsWebhookEvent($stale['event_id'])->reconciliation_status)->toBe('stale');
});

it('progresses a verified DR from Logistics Received to Delivered and updates MMC delivery stages', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', '000123-DR', 'warehouse');
    prepareLogisticsStockOut($deliveryId);

    $received = logisticsWebhookPayload(['sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    expect(processLogisticsWebhookEvent($received['event_id'])->reconciliation_status)->toBe('accepted');

    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-event-002',
        'event_type' => 'delivery.delivered',
        'sequence' => 2,
        'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();
    expect(processLogisticsWebhookEvent($delivered['event_id'])->reconciliation_status)->toBe('accepted')
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('delivered')
        ->and(DB::table('logistics_dr_tracking')->value('latest_event_id'))->toBe($delivered['event_id'])
        ->and(DB::table('deliveries')->where('delivery_id', $deliveryId)->value('status'))->toBe('delivered')
        ->and(DB::table('package_status')->where('delivery_id', $deliveryId)->where('status', 'delivered')->exists())->toBeTrue();

    expect(app(LogisticsDrTrackingService::class)->dashboardSummary())->toMatchArray([
        'logistics_received' => 0,
        'logistics_delivered' => 1,
        'pending_updates' => 0,
        'total_scoped_drs' => 1,
        'delivery_performance_percentage' => 100,
        'partner_performance' => [[
            'integration_id' => 'logistics-test',
            'total_scopes' => 1,
            'delivered_scopes' => 1,
            'performance_percentage' => 100,
        ]],
    ]);
});

it('quarantines a delivered event that arrives before Logistics Received', function () {
    insertDeliveryFixture(10, 'school-A', '000123-DR');
    $payload = logisticsWebhookPayload([
        'event_type' => 'delivery.delivered',
        'sequence' => 1,
    ]);
    logisticsWebhookRequest($this, json_encode($payload, JSON_THROW_ON_ERROR))->assertAccepted();

    $event = processLogisticsWebhookEvent($payload['event_id']);
    expect($event->resolution_status)->toBe('resolved')
        ->and($event->reconciliation_status)->toBe('out_of_order')
        ->and(DB::table('logistics_dr_tracking')->count())->toBe(0);
});

it('does not allow Delivered to be reversed by a later Logistics Received event', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', '000123-DR');
    prepareLogisticsStockOut($deliveryId);
    $received = logisticsWebhookPayload(['sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);

    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-event-002',
        'event_type' => 'delivery.delivered',
        'sequence' => 2,
        'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($delivered['event_id']);

    $regression = logisticsWebhookPayload([
        'event_id' => 'logistics-event-003',
        'event_type' => 'delivery.logistics_received',
        'sequence' => 3,
        'occurred_at' => '2026-10-13T14:32:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($regression, JSON_THROW_ON_ERROR))->assertAccepted();
    expect(processLogisticsWebhookEvent($regression['event_id'])->reconciliation_status)->toBe('out_of_order')
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('delivered');
});

it('exposes Logistics tracking beside the read-only MMC receiving projection', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', '000123-DR', 'warehouse', 5);
    DB::table('package')->insert([
        ['package_id' => 901, 'lot_id' => 5],
        ['package_id' => 902, 'lot_id' => 5],
    ]);
    DB::table('package_status')->insert(['delivery_id' => $deliveryId, 'package_id' => 901, 'status' => 'delivered']);
    $payload = logisticsWebhookPayload();
    logisticsWebhookRequest($this, json_encode($payload, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($payload['event_id']);

    $row = app(LogisticsDrTrackingService::class)->dashboardRows(10, 'school-A')->sole();
    expect($row['dr_reference'])->toBe('000123-DR')
        ->and($row['logistics_status'])->toBe('logistics_received')
        ->and($row['mmc_receiving_status'])->toBe('partially_received')
        ->and($row['mmc_expected_package_count'])->toBe(2)
        ->and($row['mmc_received_package_count'])->toBe(1)
        ->and(DB::table('deliveries')->where('delivery_id', $deliveryId)->value('status'))->toBe('warehouse');
});

it('rejects request bodies over the configured maximum size', function () {
    logisticsWebhookRequest($this, str_repeat('x', 65537))
        ->assertStatus(413)
        ->assertJsonPath('code', 'payload_too_large');
});

it('stores an event when queue dispatch fails and recovers it later', function () {
    $dispatcher = Mockery::mock(DispatcherContract::class);
    $dispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable.'));
    app()->instance(DispatcherContract::class, $dispatcher);

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))
        ->assertServiceUnavailable()
        ->assertJsonPath('code', 'event_stored_retry_later');

    expect(DB::table('logistics_webhook_events')->value('queued_at'))->toBeNull();

    Queue::fake();
    $realDispatcher = new Dispatcher(app(), fn (?string $connection = null) => Queue::connection($connection));
    app()->instance(DispatcherContract::class, $realDispatcher);

    $result = app(LogisticsWebhookEventService::class)->recoverUnqueued();

    expect($result)->toBe(['queued' => 1, 'failed' => 0])
        ->and(DB::table('logistics_webhook_events')->value('queued_at'))->not->toBeNull();
    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, 1);
});

it('rate limits authenticated integrations', function () {
    config(['logistics-webhooks.rate_limit_per_minute' => 1]);
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

    logisticsWebhookRequest($this, json_encode(logisticsWebhookPayload(), JSON_THROW_ON_ERROR))->assertAccepted();
    logisticsWebhookRequest(
        $this,
        json_encode(logisticsWebhookPayload(['event_id' => 'logistics-event-002']), JSON_THROW_ON_ERROR)
    )->assertTooManyRequests()->assertJsonPath('code', 'rate_limited');
});

it('completes a matched delivery and records the integration stock out atomically', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', 'DR-LOG-1', 'warehouse');
    prepareLogisticsStockOut($deliveryId);
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-1', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-1', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-1', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();

    $event = processLogisticsWebhookEvent($delivered['event_id']);
    expect($event->reconciliation_status)->toBe('accepted')
        ->and(DB::table('inventory')->value('qty'))->toBe(14)
        ->and(DB::table('inventory_history')->where('change_type', 'stock_out')->value('quantity'))->toBe(6)
        ->and(DB::table('inventory_history')->value('dr_no'))->toBe('DR-LOG-1')
        ->and(DB::table('inventory_history')->value('package_id'))->toBe(900)
        ->and(DB::table('inventory_history')->value('integration_id'))->toBe('logistics-test')
        ->and(DB::table('inventory_history')->value('webhook_event_id'))->toBe($delivered['event_id'])
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeTrue()
        ->and(DB::table('deliveries')->where('delivery_id', $deliveryId)->value('status'))->toBe('delivered')
        ->and(DB::table('delivery_history')->where('status', 'delivered')->count())->toBe(1);
});

it('records a previously released logistics package without deducting inventory again', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', 'DR-LOG-RELEASED', 'warehouse');
    prepareLogisticsStockOut($deliveryId);
    DB::table('package_status')->where('delivery_id', $deliveryId)->update(['status' => 'released']);
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-RELEASED', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-released', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-RELEASED', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();

    $event = processLogisticsWebhookEvent($delivered['event_id']);
    $history = DB::table('inventory_history')->first();
    expect($event->reconciliation_status)->toBe('accepted')
        ->and(DB::table('inventory')->value('qty'))->toBe(20)
        ->and($history->change_type)->toBe('stock_out')
        ->and($history->quantity)->toBe(0)
        ->and($history->old_qty)->toBeNull()
        ->and($history->new_qty)->toBeNull()
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeTrue();
});

it('does not deduct inventory again when a processed delivery event is replayed', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', 'DR-LOG-2', 'warehouse');
    prepareLogisticsStockOut($deliveryId);
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-2', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-2', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-2', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    $body = json_encode($delivered, JSON_THROW_ON_ERROR);
    logisticsWebhookRequest($this, $body)->assertAccepted();
    processLogisticsWebhookEvent($delivered['event_id']);
    logisticsWebhookRequest($this, $body)->assertOk()->assertJsonPath('code', 'duplicate_processed');

    expect(DB::table('inventory')->value('qty'))->toBe(14)
        ->and(DB::table('inventory_history')->where('change_type', 'stock_out')->count())->toBe(1)
        ->and(DB::table('delivery_history')->where('status', 'delivered')->count())->toBe(1);
});

it('leaves the DR pending reconciliation when warehouse stock is insufficient', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', 'DR-LOG-3', 'warehouse');
    prepareLogisticsStockOut($deliveryId, stock: 5);
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-3', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-3', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-3', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();

    $event = processLogisticsWebhookEvent($delivered['event_id']);
    expect($event->reconciliation_status)->toBe('insufficient_stock')
        ->and($event->reconciliation_details)->toContain('Required: 6; available: 5')
        ->and(DB::table('inventory')->value('qty'))->toBe(5)
        ->and(DB::table('inventory_history')->count())->toBe(0)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeFalse()
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('logistics_received');
});

it('keeps a delivery event unresolved when its DR has no unambiguous package allocation', function () {
    insertDeliveryFixture(10, 'school-A', 'DR-LOG-4', 'warehouse');
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-4', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-4', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-4', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();

    $event = processLogisticsWebhookEvent($delivered['event_id']);
    expect($event->reconciliation_status)->toBe('ambiguous')
        ->and($event->reconciliation_details)->toContain('does not identify any package allocations')
        ->and(DB::table('inventory_history')->count())->toBe(0)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeFalse();
});

it('rolls back inventory and delivery changes if history recording fails', function () {
    $deliveryId = insertDeliveryFixture(10, 'school-A', 'DR-LOG-5', 'warehouse');
    prepareLogisticsStockOut($deliveryId);
    $received = logisticsWebhookPayload(['dr_reference' => 'DR-LOG-5', 'sequence' => 1]);
    logisticsWebhookRequest($this, json_encode($received, JSON_THROW_ON_ERROR))->assertAccepted();
    processLogisticsWebhookEvent($received['event_id']);
    DB::unprepared("CREATE TRIGGER reject_logistics_history BEFORE INSERT ON inventory_history BEGIN SELECT RAISE(ABORT, 'history unavailable'); END");
    $delivered = logisticsWebhookPayload([
        'event_id' => 'logistics-delivered-5', 'event_type' => 'delivery.delivered',
        'dr_reference' => 'DR-LOG-5', 'sequence' => 2, 'occurred_at' => '2026-10-13T14:31:00+08:00',
    ]);
    logisticsWebhookRequest($this, json_encode($delivered, JSON_THROW_ON_ERROR))->assertAccepted();

    expect(fn () => processLogisticsWebhookEvent($delivered['event_id']))->toThrow(QueryException::class)
        ->and(DB::table('inventory')->value('qty'))->toBe(20)
        ->and(DB::table('inventory_history')->count())->toBe(0)
        ->and(DB::table('package_status')->where('status', 'delivered')->exists())->toBeFalse()
        ->and(DB::table('logistics_dr_tracking')->value('logistics_status'))->toBe('logistics_received')
        ->and(DB::table('logistics_webhook_events')->where('external_event_id', $delivered['event_id'])->value('processing_status'))->toBe('received');
});
