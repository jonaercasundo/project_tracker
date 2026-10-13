<?php

use App\Models\User;
use App\Services\DeliveryBillingQrCodeService;
use Illuminate\Support\Facades\DB;
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
        'ref_no' => 'PROJ-1001',
        'status' => 'Ongoing',
    ], 'project_id');
    $schoolId = 'SCH-'.str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
    DB::table('school')->insert(['school_id' => $schoolId, 'school_name' => 'North District School']);

    return (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'dr_no' => $drNumber,
        'delivery_date' => '2026-09-15',
        'status' => $status,
    ], 'delivery_id');
}

function confirmDeliveryForBilling(int $deliveryId): void
{
    test()->postJson(route('billing.for-billing', $deliveryId))->assertOk();
}

it('shows pending status until a user explicitly confirms For Billing', function () {
    signInBillingUser();
    $deliveryId = createBillingDelivery();
    $qrPayload = app(DeliveryBillingQrCodeService::class)->makePayload($deliveryId);

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
    ]);
    $this->assertDatabaseHas('deliveries', ['delivery_id' => $deliveryId, 'status' => 'accepted']);
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

    $this->getJson(route('billing.scanner.lookup', ['q' => $invalidPayload, 'source' => 'qr']))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This billing QR code is not valid.');

    $expiresAt = now()->subMinute()->timestamp;
    $signed = "MMCBILLING:1:{$deliveryId}:{$expiresAt}";
    $expiredPayload = $signed.':'.hash_hmac('sha256', $signed, config('app.key'));
    $this->getJson(route('billing.scanner.lookup', ['q' => $expiredPayload, 'source' => 'qr']))
        ->assertStatus(410)
        ->assertJsonPath('message', 'This billing QR code has expired. Print a current delivery label.');

    $unknownPayload = app(DeliveryBillingQrCodeService::class)->makePayload(9999);
    $this->getJson(route('billing.scanner.lookup', ['q' => $unknownPayload, 'source' => 'qr']))->assertNotFound();
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
    $this->postJson(route('billing.billed', $billedDelivery), [
        'invoice_no' => 'INV-2003',
        'invoice_date' => '2026-09-20',
        'amount' => '300.00',
    ])->assertOk();
    $this->postJson(route('billing.hold', $onHoldDelivery), ['remarks' => 'Delivery documents missing.'])->assertOk();

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
            'pending_billing' => 1,
            'for_billing' => 1,
            'billed' => 1,
            'on_hold' => 1,
            'completion_percentage' => 25.0,
        ]);
});

it('blocks users outside the MMC company context from billing routes', function () {
    signInBillingUser('finance', 'MI');

    $this->get(route('billing.scanner'))->assertForbidden();
});
