<?php

use App\Models\User;
use App\Services\DeliveryBillingQrCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\BillingWorkflowTestCase;

pest()->extend(BillingWorkflowTestCase::class)->in(__FILE__);

function signInBillingUser(string $role = 'finance', string $companyCode = 'MMC'): array
{
    $companyId = DB::table('companies')->insertGetId([
        'name' => $companyCode === 'MMC' ? 'Metro Mobilia' : 'Other Company',
        'code' => $companyCode,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], 'company_id');

    $user = User::query()->create([
        'name' => 'Billing Tester',
        'username' => 'billing-tester-'.$companyId,
        'password' => 'test-password',
        'role' => $role,
        'employee_id' => 'EMP-'.$companyId,
        'department' => 'Finance',
        'position' => 'Billing Officer',
    ]);
    $user->assignRole($role);

    DB::table('company_user')->insert([
        'user_id' => $user->getKey(),
        'company_id' => $companyId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    test()->actingAs($user);
    test()->withSession(['company_id' => $companyId]);

    return [$user, $companyId];
}

function createBillingDelivery(string $drNumber = 'DR-1001', string $status = 'accepted'): int
{
    $projectId = DB::table('projects')->insertGetId([
        'project_name' => 'School Furniture Contract',
        'project_code' => 'PRJ-1001',
        'ref_no' => 'PROJ-1001',
        'status' => 'Ongoing',
    ], 'project_id');
    $lotId = DB::table('lot')->insertGetId([
        'lot_name' => 'Lot A',
        'project_id' => $projectId,
        'contract_no' => 'CONTRACT-'.$projectId,
    ], 'lot_id');
    $schoolId = 'SCH-'.str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
    DB::table('school')->insert(['school_id' => $schoolId, 'school_name' => 'North District School', 'division' => 'North Division']);

    return (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'lot_id' => $lotId,
        'dr_no' => $drNumber,
        'delivery_date' => '2026-09-15',
        'status' => $status,
    ], 'delivery_id');
}

function createDeliveryForBillingProject(int $projectId, string $drNumber, string $status = 'accepted', string $deliveryDate = '2026-09-15'): int
{
    $lotId = (int) DB::table('lot')->where('project_id', $projectId)->value('lot_id');
    $schoolId = 'SCH-'.$projectId.'-'.preg_replace('/[^A-Za-z0-9]/', '', $drNumber);
    DB::table('school')->insertOrIgnore([
        'school_id' => $schoolId,
        'school_name' => 'North District School '.$drNumber,
        'division' => 'North Division',
    ]);

    return (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'lot_id' => $lotId,
        'dr_no' => $drNumber,
        'delivery_date' => $deliveryDate,
        'status' => $status,
    ], 'delivery_id');
}

function confirmDeliveryForBilling(int $deliveryId): void
{
    test()->postJson(route('billing.for-billing', $deliveryId))->assertOk();
}

it('repairs the history index after a partial migration without losing billing data', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);
    Schema::table('delivery_billing_histories', function (Blueprint $table): void {
        $table->dropIndex('delivery_billing_history_record_created_idx');
    });

    $migration = require database_path('migrations/2026_10_13_030107_create_delivery_billing_tables.php');
    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('delivery_billing_histories', ['delivery_billing_record_id', 'created_at']))->toBeTrue();
    $this->assertDatabaseCount('delivery_billing_records', 1);
    $this->assertDatabaseCount('delivery_billing_histories', 1);
    $this->assertDatabaseHas('delivery_billing_records', ['delivery_id' => $deliveryId, 'status' => 'for_billing']);
});

it('previews a missing history index without changing the billing schema', function () {
    Schema::table('delivery_billing_histories', function (Blueprint $table): void {
        $table->dropIndex('delivery_billing_history_record_created_idx');
    });
    $migration = require database_path('migrations/2026_10_13_030107_create_delivery_billing_tables.php');

    $queries = DB::connection()->pretend(fn () => $migration->up());

    expect(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'delivery_billing_history_record_created_idx')))->toBeTrue();
    expect(Schema::hasIndex('delivery_billing_histories', ['delivery_billing_record_id', 'created_at']))->toBeFalse();
});

it('shows pending status until a user explicitly confirms For Billing', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    $qrPayload = app(DeliveryBillingQrCodeService::class)->makeDrNumberPayload('DR-1001');

    $this->get(route('billing.scanner'))
        ->assertOk()
        ->assertSee('Handheld scanner')
        ->assertSee('Select all scanned records')
        ->assertSee('Clear scanned list')
        ->assertSee('billing-bulk-billed-form')
        ->assertDontSee('Focus scanner');

    $this->getJson(route('billing.scanner.lookup', ['q' => $qrPayload, 'source' => 'qr']))
        ->assertOk()
        ->assertJsonPath('records.0.billing_status', 'pending_billing')
        ->assertJsonPath('records.0.next_action', 'mark_for_billing');

    $this->assertDatabaseCount('delivery_billing_records', 0);
    $this->postJson(route('billing.for-billing', $deliveryId))
        ->assertOk()
        ->assertJsonPath('record.billing_status', 'for_billing');

    $this->assertDatabaseHas('delivery_billing_records', [
        'delivery_id' => $deliveryId,
        'status' => 'for_billing',
        'submitted_for_billing_by' => auth()->id(),
    ]);
    $this->assertDatabaseHas('delivery_billing_histories', [
        'previous_status' => 'pending_billing',
        'new_status' => 'for_billing',
        'changed_by' => auth()->id(),
        'changed_by_name' => 'Billing Tester',
    ]);
    $this->assertDatabaseHas('deliveries', ['delivery_id' => $deliveryId, 'status' => 'accepted']);
});

it('resolves the signed DR number QR to every delivery row with that DR number', function () {
    signInBillingUser();
    $firstDeliveryId = createBillingDelivery('DR-SHARED');
    $secondDeliveryId = createBillingDelivery('DR-SHARED');
    $qrPayload = app(DeliveryBillingQrCodeService::class)->makeDrNumberPayload('DR-SHARED');

    $this->getJson(route('billing.scanner.lookup', ['q' => $qrPayload, 'source' => 'qr']))
        ->assertOk()
        ->assertJsonCount(2, 'records')
        ->assertJsonPath('records.0.dr_no', 'DR-SHARED')
        ->assertJsonPath('records.1.dr_no', 'DR-SHARED')
        ->assertJsonPath('records.0.delivery_id', $firstDeliveryId)
        ->assertJsonPath('records.1.delivery_id', $secondDeliveryId);
});

it('confirms Billed with a shared invoice header and keeps payment separate', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);

    $this->postJson(route('billing.billed', $deliveryId), [
        'invoice_no' => 'INV-2026-19',
        'invoice_date' => '2026-09-20',
        'amount' => '18500.00',
        'billing_amount' => '9250.00',
        'billing_reference' => 'Batch A / document 19',
        'billing_remarks' => 'Supporting documents checked.',
    ])->assertOk()
        ->assertJsonPath('record.billing_status', 'billed')
        ->assertJsonPath('record.invoice_no', 'INV-2026-19')
        ->assertJsonPath('record.invoice_status', 'Pending');

    $this->assertDatabaseHas('invoices', [
        'project_id' => 1,
        'invoice_no' => 'INV-2026-19',
        'amount' => '18500.00',
        'status' => 'Pending',
    ]);
    $this->assertDatabaseHas('delivery_billing_records', [
        'delivery_id' => $deliveryId,
        'status' => 'billed',
        'billing_amount' => '9250.00',
        'billing_reference' => 'Batch A / document 19',
        'confirmed_billed_by' => auth()->id(),
    ]);
    $this->assertDatabaseHas('delivery_billing_histories', [
        'previous_status' => 'for_billing',
        'new_status' => 'billed',
        'invoice_id' => 1,
        'changed_by' => auth()->id(),
    ]);
    $this->assertDatabaseCount('delivery_billing_histories', 2);
});

it('does not change status when a QR is scanned repeatedly without confirmation', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    $qrPayload = app(DeliveryBillingQrCodeService::class)->makePayload($deliveryId);

    foreach (range(1, 3) as $scan) {
        $this->getJson(route('billing.scanner.lookup', ['q' => $qrPayload, 'source' => 'qr']))
            ->assertOk()
            ->assertJsonPath('records.0.billing_status', 'pending_billing');
    }

    $this->assertDatabaseCount('delivery_billing_records', 0);
    $this->assertDatabaseCount('delivery_billing_histories', 0);
});

it('rejects repeated state changes and retains one audit event for the accepted transition', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();

    $this->postJson(route('billing.for-billing', $deliveryId))->assertOk();
    $this->postJson(route('billing.for-billing', $deliveryId))
        ->assertStatus(409)
        ->assertJsonPath('message', 'Only pending deliveries can be marked for billing.');

    $this->assertDatabaseCount('delivery_billing_records', 1);
    $this->assertDatabaseCount('delivery_billing_histories', 1);
});

it('requires invoice number, date and amount before confirming Billed', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);

    $this->postJson(route('billing.billed', $deliveryId), [])->assertUnprocessable();

    $this->assertDatabaseHas('delivery_billing_records', ['delivery_id' => $deliveryId, 'status' => 'for_billing']);
    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('delivery_billing_histories', 1);
});

it('requires an eligible logistics status at each billing transition', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);
    DB::table('deliveries')->where('delivery_id', $deliveryId)->update(['status' => 'pending']);

    $this->getJson(route('billing.scanner.lookup', ['q' => 'DR-1001', 'source' => 'manual']))
        ->assertOk()
        ->assertJsonPath('records.0.next_action', 'ineligible');

    $this->postJson(route('billing.billed', $deliveryId), [
        'invoice_no' => 'INV-INELIGIBLE',
        'invoice_date' => '2026-09-21',
        'amount' => '100.00',
    ])->assertUnprocessable();

    $this->assertDatabaseHas('delivery_billing_records', ['delivery_id' => $deliveryId, 'status' => 'for_billing']);
    $this->assertDatabaseCount('invoices', 0);
});

it('links multiple deliveries to the same project invoice without duplicating it', function () {
    signInBillingUser();
    $firstDeliveryId = createBillingDelivery('DR-1001');
    $secondDeliveryId = (int) DB::table('deliveries')->insertGetId([
        'project_id' => 1,
        'school_id' => 'SCH-0001',
        'dr_no' => 'DR-1002',
        'delivery_date' => '2026-09-16',
        'status' => 'accepted',
    ], 'delivery_id');

    foreach ([$firstDeliveryId, $secondDeliveryId] as $deliveryId) {
        $this->postJson(route('billing.for-billing', $deliveryId))->assertOk();
        $this->postJson(route('billing.billed', $deliveryId), [
            'invoice_no' => 'INV-SHARED',
            'invoice_date' => '2026-09-20',
            'amount' => '300.00',
        ])->assertOk();
    }

    $this->assertDatabaseCount('invoices', 1);
    $this->assertDatabaseCount('delivery_billing_records', 2);
    $this->assertDatabaseCount('delivery_billing_histories', 4);
});

it('denies billing confirmation to an operator without the permission', function () {
    signInBillingUser('user');
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);

    $this->postJson(route('billing.billed', $deliveryId), [
        'invoice_no' => 'INV-2026-20',
        'invoice_date' => '2026-09-21',
        'amount' => '100.00',
    ])->assertForbidden();

    $this->assertDatabaseHas('delivery_billing_records', ['delivery_id' => $deliveryId, 'status' => 'for_billing']);
    $this->assertDatabaseCount('invoices', 0);
});

it('rejects invalid, expired, unknown and lot-only QR payloads', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    $qrPayload = app(DeliveryBillingQrCodeService::class)->makePayload($deliveryId);
    $invalidPayload = substr($qrPayload, 0, -64).str_repeat('0', 64);
    $drNumber = base64_encode('DR-1001');
    $drNumber = rtrim(strtr($drNumber, '+/', '-_'), '=');
    $invalidDrPayload = app(DeliveryBillingQrCodeService::class)->makeDrNumberPayload('DR-1001');
    $invalidDrPayload = substr($invalidDrPayload, 0, -64).str_repeat('0', 64);

    $this->getJson(route('billing.scanner.lookup', ['q' => $invalidPayload, 'source' => 'qr']))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This billing QR code is not valid.');
    $this->getJson(route('billing.scanner.lookup', ['q' => $invalidDrPayload, 'source' => 'qr']))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This billing QR code is not valid.');

    $expiresAt = now()->subMinute()->timestamp;
    $signed = "MMCBILLING:1:{$deliveryId}:{$expiresAt}";
    $expiredPayload = $signed.':'.hash_hmac('sha256', $signed, config('app.key'));
    $this->getJson(route('billing.scanner.lookup', ['q' => $expiredPayload, 'source' => 'qr']))
        ->assertGone()
        ->assertJsonPath('message', 'This billing QR code has expired. Print a current delivery label.');
    $expiredDrSigned = "MMCBILLING:2:{$drNumber}:{$expiresAt}";
    $expiredDrPayload = $expiredDrSigned.':'.hash_hmac('sha256', $expiredDrSigned, config('app.key'));
    $this->getJson(route('billing.scanner.lookup', ['q' => $expiredDrPayload, 'source' => 'qr']))
        ->assertGone()
        ->assertJsonPath('message', 'This billing QR code has expired. Print a current delivery label.');

    $unknownPayload = app(DeliveryBillingQrCodeService::class)->makePayload(9999);
    $this->getJson(route('billing.scanner.lookup', ['q' => $unknownPayload, 'source' => 'qr']))->assertNotFound();
    $unknownDrPayload = app(DeliveryBillingQrCodeService::class)->makeDrNumberPayload('DR-UNKNOWN');
    $this->getJson(route('billing.scanner.lookup', ['q' => $unknownDrPayload, 'source' => 'qr']))->assertNotFound();
    $this->getJson(route('billing.scanner.lookup', ['q' => '42', 'source' => 'qr']))->assertUnprocessable();
});

it('supports the existing delivery package QR while rejecting unrelated package records', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    DB::table('package_status')->insert([
        'package_status_id' => 7,
        'delivery_id' => $deliveryId,
        'package_id' => 3,
        'status' => 'accepted',
    ]);

    $legacyQr = "https://philgeps.metro-mobilia.com/entry.php?id=7&delivery_id={$deliveryId}";
    $this->getJson(route('billing.scanner.lookup', ['q' => $legacyQr, 'source' => 'qr']))
        ->assertOk()
        ->assertJsonPath('records.0.delivery_id', $deliveryId);

    $wrongDeliveryQr = 'https://philgeps.metro-mobilia.com/entry.php?id=7&delivery_id=9999';
    $this->getJson(route('billing.scanner.lookup', ['q' => $wrongDeliveryQr, 'source' => 'qr']))->assertUnprocessable();
});

it('does not advance an already billed record and includes accurate project dashboard counts', function () {
    signInBillingUser();
    $pendingDelivery = createBillingDelivery('DR-2001');
    $forBillingDelivery = createBillingDelivery('DR-2002', 'delivered');
    $billedDelivery = createBillingDelivery('DR-2003');
    $onHoldDelivery = createBillingDelivery('DR-2004');

    $this->postJson(route('billing.for-billing', $forBillingDelivery))->assertOk();
    $this->postJson(route('billing.for-billing', $billedDelivery))->assertOk();
    $batchId = DB::table('billing_batches')->insertGetId([
        'batch_ref' => 'BILL-DASHBOARD-TEST',
        'status' => 'for_billing',
        'selection_method' => 'manual',
        'selection_hash' => str_repeat('a', 64),
        'idempotency_key' => (string) Str::uuid(),
        'created_by' => auth()->id(),
        'created_by_name' => 'Billing Tester',
        'confirmed_by' => auth()->id(),
        'confirmed_by_name' => 'Billing Tester',
        'confirmed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('delivery_billing_records')->where('delivery_id', $forBillingDelivery)->update(['billing_batch_id' => $batchId]);
    $this->postJson(route('billing.billed', $billedDelivery), [
        'invoice_no' => 'INV-2003',
        'invoice_date' => '2026-09-20',
        'amount' => '300.00',
    ])->assertOk();
    $this->postJson(route('billing.hold', $onHoldDelivery), ['remarks' => 'Delivery documents missing.'])->assertOk();
    $returnedBatchId = DB::table('billing_batches')->insertGetId([
        'batch_ref' => 'BILL-RETURNED-TEST',
        'status' => 'returned_for_correction',
        'selection_method' => 'manual',
        'selection_hash' => str_repeat('b', 64),
        'idempotency_key' => (string) Str::uuid(),
        'created_by' => auth()->id(),
        'created_by_name' => 'Billing Tester',
        'confirmed_by' => auth()->id(),
        'confirmed_by_name' => 'Billing Tester',
        'confirmed_at' => now(),
        'created_at' => now()->subDay(),
        'updated_at' => now(),
    ]);
    DB::table('delivery_billing_records')->where('delivery_id', $onHoldDelivery)->update(['billing_batch_id' => $returnedBatchId]);

    $this->postJson(route('billing.for-billing', $billedDelivery))->assertStatus(409);
    $this->postJson(route('billing.billed', $billedDelivery), [
        'invoice_no' => 'INV-2003',
        'invoice_date' => '2026-09-20',
        'amount' => '300.00',
    ])->assertStatus(409);
    $this->assertDatabaseHas('deliveries', ['delivery_id' => $pendingDelivery, 'status' => 'accepted']);
    $this->assertDatabaseCount('delivery_billing_histories', 4);

    $this->get(route('billing.dashboard'))
        ->assertOk()
        ->assertViewHas('totals', fn (array $totals): bool => $totals === [
            'total' => 4,
            'ready' => 1,
            'for_billing' => 1,
            'billed' => 1,
            'blocked' => 1,
            'active_batches' => 2,
            'completion_percentage' => 25.0,
        ])
        ->assertViewHas('recentBatches', fn ($batches): bool => $batches->count() === 2
            && $batches->contains('batch_ref', 'BILL-DASHBOARD-TEST'))
        ->assertViewHas('returnedBatchCount', 1)
        ->assertViewHas('returnedBatches', fn ($batches): bool => $batches->count() === 1
            && $batches->first()->batch_ref === 'BILL-RETURNED-TEST')
        ->assertViewHas('activityTrend', fn (array $trend): bool => count($trend) === 1
            && $trend[0]['submitted'] === 2
            && $trend[0]['billed'] === 1);
});

it('applies project and delivery date filters consistently to billing dashboard metrics', function (): void {
    signInBillingUser();
    $matchingDelivery = createBillingDelivery('DR-MATCHING');
    $matchingProjectId = (int) DB::table('deliveries')->where('delivery_id', $matchingDelivery)->value('project_id');
    DB::table('projects')->where('project_id', $matchingProjectId)->update([
        'project_name' => '2026 Northern District Furniture and Learning Materials Distribution Program',
        'project_code' => 'MMC-2026-001',
    ]);
    createDeliveryForBillingProject($matchingProjectId, 'DR-OTHER-DATE', 'accepted', '2026-08-10');
    $otherProjectDelivery = createBillingDelivery('DR-OTHER-PROJECT');
    DB::table('delivery_billing_records')->insert([
        'delivery_id' => $otherProjectDelivery,
        'status' => 'billed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('billing.dashboard', [
        'project_id' => $matchingProjectId,
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-30',
    ]))
        ->assertOk()
        ->assertSee('Operations menu')
        ->assertSee('2026 Northern District Furniture and Learning Materials Distribution Program')
        ->assertViewHas('filters', [
            'project_id' => (string) $matchingProjectId,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
        ])
        ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 1
            && $totals['ready'] === 1
            && $totals['billed'] === 0)
        ->assertViewHas('projectPerformance', fn ($projects): bool => $projects->count() === 1
            && (int) $projects->first()->project_id === $matchingProjectId);
});

it('shows clear empty states when dashboard filters have no matching deliveries', function (): void {
    signInBillingUser();

    $this->get(route('billing.dashboard', ['date_from' => '2099-01-01', 'date_to' => '2099-01-31']))
        ->assertOk()
        ->assertSee('No delivered records match these filters')
        ->assertSee('No billing activity in this date range')
        ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 0
            && $totals['completion_percentage'] === 0.0);
});

it('filters billing records, shows their audit history and exports CSV', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    confirmDeliveryForBilling($deliveryId);
    $this->postJson(route('billing.billed', $deliveryId), [
        'invoice_no' => 'INV-RECORDS',
        'invoice_date' => '2026-09-20',
        'amount' => '300.00',
    ])->assertOk();

    $this->get(route('billing.records.index', [
        'dr_no' => 'DR-1001',
        'billing_status' => 'billed',
    ]))->assertOk()->assertSee('INV-RECORDS');

    $this->get(route('billing.records.show', $deliveryId))
        ->assertOk()
        ->assertSee('Billing history')
        ->assertSee('Billing Tester')
        ->assertSee('INV-RECORDS');

    $this->get(route('billing.records.export', ['dr_no' => 'DR-1001']))
        ->assertOk()
        ->assertDownload('billing-records.csv');
});

it('filters billing records by project while preserving the remaining ledger filters', function (): void {
    signInBillingUser();
    $firstDeliveryId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $firstDeliveryId)->value('project_id');
    $projectName = '2026 Northern Division School Furniture, Learning Materials, and Nutrition Distribution Program';
    DB::table('projects')->where('project_id', $projectId)->update([
        'project_name' => $projectName,
        'project_code' => 'MMC-PRJ-2026-001',
    ]);
    $sameProjectDeliveryId = createDeliveryForBillingProject($projectId, '1003');
    $secondLotId = DB::table('lot')->insertGetId([
        'lot_name' => 'Lot B',
        'project_id' => $projectId,
        'contract_no' => 'CONTRACT-'.$projectId.'-B',
    ], 'lot_id');
    DB::table('deliveries')->where('delivery_id', $sameProjectDeliveryId)->update(['lot_id' => $secondLotId]);
    $otherProjectDeliveryId = createBillingDelivery('1002');
    $otherProjectId = (int) DB::table('deliveries')->where('delivery_id', $otherProjectDeliveryId)->value('project_id');
    DB::table('projects')->where('project_id', $otherProjectId)->update([
        'project_name' => $projectName,
        'project_code' => 'MMC-PRJ-2026-002',
    ]);

    foreach ([$firstDeliveryId, $sameProjectDeliveryId, $otherProjectDeliveryId] as $deliveryId) {
        confirmDeliveryForBilling($deliveryId);
    }
    $this->postJson(route('billing.billed', $firstDeliveryId), [
        'invoice_no' => 'INV-PROJECT-FILTER',
        'invoice_date' => '2026-09-20',
        'amount' => '300.00',
    ])->assertOk();

    $this->get(route('billing.records.index', [
        'project_id' => $projectId,
        'dr_no' => '1001',
        'school_id' => 'SCH-0001',
        'billing_status' => 'billed',
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-30',
        'invoice_no' => 'INV-PROJECT-FILTER',
    ]))
        ->assertOk()
        ->assertSee($projectName)
        ->assertSee('MMC-PRJ-2026-001')
        ->assertSee('Search projects by name or code...', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('whitespace-normal break-words', false)
        ->assertSee('name="project_id" value="'.$projectId.'"', false)
        ->assertSee('name="dr_no" value="1001"', false)
        ->assertSee('name="school_id"', false)
        ->assertSee('INV-PROJECT-FILTER')
        ->assertDontSee('1002');

    $this->get(route('billing.records.index', ['project_id' => $projectId]))
        ->assertOk()
        ->assertSee('Lot A')
        ->assertSee('Lot B')
        ->assertSee('MMC-PRJ-2026-001')
        ->assertSee('MMC-PRJ-2026-002');
});

it('blocks users outside the MMC company context from billing routes', function () {
    signInBillingUser('finance', 'MI');

    $this->get(route('billing.scanner'))->assertForbidden();
});

it('renders the Operations preparation page with the dependent project filter and four selection methods', function (): void {
    signInBillingUser();
    createBillingDelivery('1001');

    $this->get(route('billing.prepare', ['year' => 2026]))
        ->assertOk()
        ->assertSee('Prepare for Billing')
        ->assertSee('All Years')
        ->assertSee('Select Year First')
        ->assertSee('Scan QR')
        ->assertSee('Search DR')
        ->assertSee('DR From')
        ->assertSee('All Delivered')
        ->assertSee('Search projects...', false)
        ->assertSee('PRJ-1001');
});

it('shows the billing sidebar links to MMC users with billing permissions', function (): void {
    signInBillingUser('finance');

    $sidebar = view('layouts.project-sidebar')->render();

    expect($sidebar)
        ->toContain('Billing')
        ->toContain('href="'.route('billing.dashboard').'"')
        ->toContain('href="'.route('billing.prepare').'"')
        ->toContain('href="'.route('billing.batches.index').'"')
        ->toContain('href="'.route('billing.records.index').'"')
        ->toContain('Billing Dashboard')
        ->toContain('Prepare for Billing')
        ->toContain('Billing Batches')
        ->toContain('Billing History');
});

it('shows the billing sidebar to the user role with its seeded billing permissions', function (): void {
    signInBillingUser('user');

    $sidebar = view('layouts.project-sidebar')->render();

    expect($sidebar)
        ->toContain('Billing')
        ->toContain('href="'.route('billing.prepare').'"')
        ->toContain('href="'.route('billing.batches.index').'"')
        ->toContain('href="'.route('billing.records.index').'"')
        ->toContain('href="'.route('billing.scanner').'"')
        ->toContain('Billing Dashboard');
});

it('hides the billing sidebar from MMC users without billing permissions', function (): void {
    Role::findOrCreate('billing-viewer-without-access', 'web');
    [$user] = signInBillingUser('billing-viewer-without-access');
    $user->refresh();

    $sidebar = view('layouts.project-sidebar')->render();

    expect($sidebar)
        ->not->toContain('Billing Dashboard')
        ->not->toContain('Prepare for Billing')
        ->not->toContain('Billing Batches')
        ->not->toContain('Billing History')
        ->not->toContain('Billing QR Scanner');
});

it('shows only billing history links to MMC users with read-only billing access', function (): void {
    Role::findOrCreate('billing-records-viewer', 'web')->givePermissionTo('billing.records.view');
    [$user] = signInBillingUser('billing-records-viewer');
    $user->refresh();

    $sidebar = view('layouts.project-sidebar')->render();

    expect($sidebar)
        ->toContain('Billing Dashboard')
        ->toContain('Billing Batches')
        ->toContain('Billing History')
        ->not->toContain('Prepare for Billing')
        ->not->toContain('Billing QR Scanner');
});

it('hides the billing sidebar when the selected company is not MMC', function (): void {
    signInBillingUser('finance', 'MI');

    $sidebar = view('layouts.project-sidebar')->render();

    expect($sidebar)
        ->not->toContain('Prepare for Billing')
        ->not->toContain('Billing Batches')
        ->not->toContain('Billing History');
});

it('expands the Billing sidebar and highlights the current page link', function (): void {
    signInBillingUser('finance');

    foreach ([
        'billing.dashboard' => route('billing.dashboard'),
        'billing.prepare' => route('billing.prepare'),
        'billing.batches.index' => route('billing.batches.index'),
        'billing.records.index' => route('billing.records.index'),
        'billing.scanner' => route('billing.scanner'),
    ] as $routeName => $url) {
        $response = $this->get($url)
            ->assertOk()
            ->assertSee('x-data="{ open: true }"', false);

        expect(preg_match('/<a href="'.preg_quote($url, '/').'"[^>]*aria-current="page"[^>]*>/s', $response->getContent()))
            ->toBe(1, "The {$routeName} sidebar link should be marked active.");
    }
});

it('keeps generic Billing navigation links out of Billing page headers', function (): void {
    signInBillingUser('finance');

    foreach ([
        ['billing.dashboard', 'Billing Dashboard'],
        ['billing.prepare', 'Prepare for Billing'],
        ['billing.batches.index', 'Billing batches'],
        ['billing.records.index', 'Delivery records'],
        ['billing.scanner', 'Delivery QR scanner'],
    ] as [$routeName, $title]) {
        $response = $this->get(route($routeName))->assertOk()->assertSee($title);
        preg_match('/<header\\b[^>]*>(.*?)<\\/header>/s', $response->getContent(), $matches);
        $header = $matches[1] ?? '';

        expect($header)->not->toContain('aria-label="Billing navigation"');

        foreach ([
            'billing.dashboard',
            'billing.prepare',
            'billing.batches.index',
            'billing.records.index',
            'billing.scanner',
        ] as $navigationRoute) {
            expect($header)->not->toContain('href="'.route($navigationRoute).'"');
        }
    }
});

it('resolves signed QR values through the Operations preparation lookup', function (): void {
    signInBillingUser();
    $deliveryId = createBillingDelivery('1001');
    $payload = app(DeliveryBillingQrCodeService::class)->makePayload($deliveryId);

    $this->getJson(route('billing.prepare.scan', ['q' => $payload]))
        ->assertOk()
        ->assertJsonPath('records.0.delivery_id', $deliveryId)
        ->assertJsonPath('records.0.eligible', true);
    $this->getJson(route('billing.prepare.scan', ['q' => 'unsigned-delivery-id']))
        ->assertUnprocessable();
});

it('allows read-only preparation access without allowing billing confirmation', function (): void {
    [$user] = signInBillingUser('user');
    $user->removeRole('user');
    $readOnlyRole = Role::findOrCreate('billing-viewer', 'web');
    $readOnlyRole->givePermissionTo('billing.qr.scan');
    $user->assignRole($readOnlyRole);
    $deliveryId = createBillingDelivery('1001');

    $this->get(route('billing.prepare'))
        ->assertOk()
        ->assertDontSee('Billing Dashboard')
        ->assertSee('data-can-confirm="false"', false);
    $this->postJson(route('billing.prepare.confirm'), [
        'mode' => 'scan',
        'delivery_ids' => [$deliveryId],
        'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();

    $this->assertDatabaseCount('billing_batches', 0);
});

it('searches duplicate DR numbers by exact delivery records and shows eligibility and POD state', function (): void {
    signInBillingUser();
    $firstId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $firstId)->value('project_id');
    $secondId = createDeliveryForBillingProject($projectId, '1001');
    $packageStatusId = (int) DB::table('package_status')->insertGetId([
        'delivery_id' => $secondId,
        'package_id' => 1,
        'status' => 'accepted',
    ], 'package_status_id');
    DB::table('delivery_proofs')->insert(['package_status_id' => $packageStatusId, 'photo' => 'proof.jpg']);

    $response = $this->getJson(route('billing.prepare.search', ['q' => '1001']))
        ->assertOk()
        ->assertJsonCount(2, 'records')
        ->assertJsonPath('records.0.eligible', true);

    expect(collect($response->json('records'))->contains(fn (array $record): bool => $record['pod_available']))->toBeTrue();

    expect($firstId)->not->toBe($secondId);
});

it('previews a numeric DR range without casting nonnumeric receipt identifiers', function (): void {
    signInBillingUser();
    $firstId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $firstId)->value('project_id');
    createDeliveryForBillingProject($projectId, '1003');
    createDeliveryForBillingProject($projectId, 'DR-1002');

    $this->getJson(route('billing.prepare.range', [
        'project_id' => $projectId,
        'year' => 2026,
        'dr_from' => '1001',
        'dr_to' => '1002',
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.dr_no', '1001');
});

it('paginates server-side DR search results while retaining filters in page links', function (): void {
    signInBillingUser();
    $firstId = createBillingDelivery('1000');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $firstId)->value('project_id');
    foreach (range(1001, 1011) as $number) {
        createDeliveryForBillingProject($projectId, (string) $number);
    }

    $this->getJson(route('billing.prepare.search', [
        'q' => 'North District School',
        'year' => 2026,
        'project_id' => $projectId,
        'per_page' => 10,
        'page' => 2,
    ]))
        ->assertOk()
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.last_page', 2)
        ->assertJsonPath('pagination.total', 12);
});

it('previews only scoped deliveries and explains excluded statuses for Select All', function (): void {
    signInBillingUser();
    $eligibleId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $eligibleId)->value('project_id');
    $forBillingId = createDeliveryForBillingProject($projectId, '1002');
    $pendingId = createDeliveryForBillingProject($projectId, '1003', 'pending');
    confirmDeliveryForBilling($forBillingId);

    $this->getJson(route('billing.prepare.all', ['year' => 2026, 'project_id' => $projectId]))
        ->assertOk()
        ->assertJsonPath('summary.total', 3)
        ->assertJsonPath('summary.eligible', 1)
        ->assertJsonPath('summary.excluded', 2);

    $this->getJson(route('billing.prepare.all'))->assertUnprocessable();
    $this->assertDatabaseCount('delivery_billing_records', 1);
    $this->assertDatabaseMissing('delivery_billing_records', ['delivery_id' => $pendingId]);
});

it('confirms a batch into the existing Billing queue idempotently without creating invoices', function (): void {
    signInBillingUser();
    $deliveryId = createBillingDelivery('1001');
    $key = (string) Str::uuid();
    $payload = ['mode' => 'scan', 'delivery_ids' => [$deliveryId], 'idempotency_key' => $key];

    $created = $this->postJson(route('billing.prepare.confirm'), $payload)
        ->assertCreated()
        ->assertJsonPath('batch.delivery_count', 1)
        ->assertJsonPath('batch.status', 'for_billing');
    $batchId = $created->json('batch.id');
    $batchRef = $created->json('batch.batch_ref');

    $this->postJson(route('billing.prepare.confirm'), $payload)
        ->assertOk()
        ->assertJsonPath('batch.id', $batchId);

    $this->assertDatabaseHas('delivery_billing_records', [
        'delivery_id' => $deliveryId,
        'billing_batch_id' => $batchId,
        'status' => 'for_billing',
    ]);
    $this->assertDatabaseHas('billing_batches', [
        'id' => $batchId,
        'batch_ref' => $batchRef,
        'created_by' => auth()->id(),
        'confirmed_by' => auth()->id(),
    ]);
    $this->assertDatabaseCount('delivery_billing_histories', 1);
    $this->assertDatabaseCount('invoices', 0);

    $this->get(route('billing.records.index', ['billing_status' => 'for_billing']))
        ->assertOk()
        ->assertSee($batchRef);
    $this->get(route('billing.batches.show', $batchId))
        ->assertOk()
        ->assertSee($batchRef)
        ->assertSee('1001');

    $this->postJson(route('billing.billed', $deliveryId), [
        'invoice_no' => 'INV-BATCH-1001',
        'invoice_date' => '2026-09-20',
        'amount' => '100.00',
    ])->assertOk()->assertJsonPath('record.billing_status', 'billed');
    $this->assertDatabaseHas('billing_batches', ['id' => $batchId, 'status' => 'billed']);
    $this->assertDatabaseCount('invoices', 1);
});

it('rejects a mixed eligible and ineligible confirmation atomically', function (): void {
    signInBillingUser();
    $eligibleId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $eligibleId)->value('project_id');
    $pendingId = createDeliveryForBillingProject($projectId, '1002', 'pending');

    $this->postJson(route('billing.prepare.confirm'), [
        'mode' => 'mixed',
        'delivery_ids' => [$eligibleId, $pendingId],
        'idempotency_key' => (string) Str::uuid(),
    ])->assertStatus(409)
        ->assertJsonPath('rejected.0.delivery_id', $pendingId)
        ->assertJsonPath('rejected.0.reason', 'Not Yet Delivered');

    $this->assertDatabaseCount('billing_batches', 0);
    $this->assertDatabaseCount('delivery_billing_records', 0);
    $this->assertDatabaseCount('delivery_billing_histories', 0);
});

it('confirms Select All from server-side criteria and rejects stale previews', function (): void {
    signInBillingUser();
    $firstId = createBillingDelivery('1001');
    $projectId = (int) DB::table('deliveries')->where('delivery_id', $firstId)->value('project_id');
    $secondId = createDeliveryForBillingProject($projectId, '1002');
    $criteria = ['year' => 2026, 'project_id' => $projectId];
    $preview = $this->getJson(route('billing.prepare.all', $criteria))->assertOk();
    $expected = collect($preview->json('records'))->where('eligible', true)->pluck('delivery_id')->all();

    $this->postJson(route('billing.prepare.confirm'), $criteria + [
        'mode' => 'all',
        'expected_delivery_ids' => $expected,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated()->assertJsonPath('batch.delivery_count', 2);

    $staleKey = (string) Str::uuid();
    $stalePreview = $this->getJson(route('billing.prepare.all', ['year' => 2026, 'project_id' => (int) DB::table('deliveries')->where('delivery_id', $secondId)->value('project_id')]))->assertOk();
    $this->postJson(route('billing.prepare.confirm'), [
        'mode' => 'all',
        'year' => 2026,
        'project_id' => $projectId,
        'expected_delivery_ids' => collect($stalePreview->json('records'))->pluck('delivery_id')->all(),
        'idempotency_key' => $staleKey,
    ])->assertStatus(409);
});
