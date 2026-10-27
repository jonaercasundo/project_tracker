<?php

use App\Exceptions\GoogleSheetsApiException;
use App\Jobs\SyncBillingSheetDate;
use App\Models\BillingSheetSync;
use App\Models\GoogleSheetsSetting;
use App\Models\User;
use App\Services\BillingSheetSyncService;
use App\Services\DeliveryBillingService;
use App\Services\GoogleSheetsApiClient;
use App\Services\GoogleSheetsBillingConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\BillingWorkflowTestCase;

pest()->extend(BillingWorkflowTestCase::class)->in(__FILE__);

function googleSheetsSettingsUser(string $role): User
{
    $company = DB::table('companies')->where('code', 'MMC')->first();
    $companyId = $company?->company_id ?? DB::table('companies')->insertGetId([
        'name' => 'Metro Mobilia',
        'code' => 'MMC',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], 'company_id');
    $user = User::query()->create([
        'name' => 'Settings '.$role,
        'username' => 'sheets-settings-'.$role,
        'password' => 'test-password',
        'role' => $role,
        'employee_id' => 'SHEETS-'.$role,
        'department' => 'IT',
        'position' => 'Administrator',
    ]);
    $user->assignRole($role);
    DB::table('company_user')->insert([
        'user_id' => $user->getKey(),
        'company_id' => $companyId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    test()->actingAs($user)->withSession(['company_id' => $companyId]);

    return $user;
}

/** @return array<string, mixed> */
function googleSheetsSettingsPayload(array $overrides = []): array
{
    return array_replace([
        'spreadsheet_id' => 'sheet-ID_1234567890',
        'sheet_tab' => 'Copy of SME LOT 2,9,11',
        'service_account_json' => '',
        'enabled' => '1',
    ], $overrides);
}

function createGoogleSheetsSyncDelivery(string $birDr = 'BIR-DR-SETTINGS'): int
{
    $projectId = DB::table('projects')->insertGetId([
        'project_name' => 'Sheets Sync Project',
        'project_code' => 'SHEETS-1',
        'ref_no' => 'SHEETS-1',
        'status' => 'Ongoing',
    ], 'project_id');
    $lotId = DB::table('lot')->insertGetId([
        'lot_name' => 'Lot 2',
        'project_id' => $projectId,
        'contract_no' => 'SHEETS-CONTRACT',
    ], 'lot_id');
    $schoolId = 'SCH-SHEETS';
    DB::table('school')->insert(['school_id' => $schoolId, 'school_name' => 'Sheets Test School', 'division' => 'North Division']);
    $deliveryId = (int) DB::table('deliveries')->insertGetId([
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'lot_id' => $lotId,
        'dr_no' => $birDr,
        'delivery_date' => '2026-10-27',
        'status' => 'accepted',
    ], 'delivery_id');
    $packageId = DB::table('package')->insertGetId(['lot_id' => $lotId, 'package_code' => 'CORE-SAVED-SETTING'], 'package_id');
    DB::table('package_status')->insert(['delivery_id' => $deliveryId, 'package_id' => $packageId, 'status' => 'pending']);

    return $deliveryId;
}

it('restricts settings access to Administrators and exposes the settings link in Billing', function (): void {
    googleSheetsSettingsUser('user');
    $this->get(route('billing.google-sheets-settings'))->assertForbidden();
    $this->post(route('billing.google-sheets-settings.store'), googleSheetsSettingsPayload())->assertForbidden();
    $this->post(route('billing.google-sheets-settings.test-connection'))->assertForbidden();
    $this->assertDatabaseCount('google_sheets_settings', 0);

    googleSheetsSettingsUser('Administrator');
    $this->get(route('billing.google-sheets-settings'))
        ->assertOk()
        ->assertSee('Google Sheets Sync')
        ->assertSee('Copy of SME LOT 2,9,11')
        ->assertSee(route('billing.google-sheets-settings'));
});

it('stores service account credentials encrypted and never returns them to the browser', function (): void {
    googleSheetsSettingsUser('Administrator');
    Schema::create('admin_activity_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('actor_user_id')->nullable();
        $table->unsignedInteger('company_id')->nullable();
        $table->string('event');
        $table->string('subject_type');
        $table->string('subject_id')->nullable();
        $table->text('summary');
        $table->json('details')->nullable();
        $table->timestamp('created_at')->nullable();
    });
    $secret = json_encode([
        'type' => 'service_account',
        'client_email' => 'billing-sheets@example.test',
        'private_key' => '-----BEGIN PRIVATE KEY-----private-test-material-----END PRIVATE KEY-----',
    ], JSON_THROW_ON_ERROR);

    $this->post(route('billing.google-sheets-settings.store'), googleSheetsSettingsPayload(['service_account_json' => $secret]))
        ->assertRedirect(route('billing.google-sheets-settings'))
        ->assertSessionHas('success')
        ->assertDontSee($secret);

    $stored = DB::table('google_sheets_settings')->where('id', 1)->value('service_account_json');
    $setting = GoogleSheetsSetting::query()->findOrFail(1);
    expect($stored)->not->toBe($secret)
        ->and($stored)->not->toContain('private-test-material')
        ->and($setting->service_account_json)->toBe($secret)
        ->and($setting->toArray())->not->toHaveKey('service_account_json');
    expect(DB::table('admin_activity_logs')->value('details'))->not->toContain($secret)->not->toContain('private-test-material');
    $this->get(route('billing.google-sheets-settings'))->assertOk()->assertDontSee($secret)->assertDontSee('private-test-material');
});

it('updates spreadsheet settings while preserving a saved credential when the input is blank', function (): void {
    googleSheetsSettingsUser('Administrator');
    $secret = json_encode(['client_email' => 'billing-sheets@example.test', 'private_key' => 'preserved-private-key'], JSON_THROW_ON_ERROR);
    GoogleSheetsSetting::query()->create([
        'id' => 1,
        'spreadsheet_id' => 'sheet-ID_1234567890',
        'sheet_tab' => 'Old worksheet',
        'service_account_json' => $secret,
        'enabled' => true,
    ]);

    $this->post(route('billing.google-sheets-settings.store'), googleSheetsSettingsPayload([
        'spreadsheet_id' => 'sheet-ID_0987654321',
        'sheet_tab' => 'New worksheet',
        'service_account_json' => '',
        'enabled' => '0',
    ]))->assertRedirect(route('billing.google-sheets-settings'));

    $setting = GoogleSheetsSetting::query()->findOrFail(1);
    expect($setting->spreadsheet_id)->toBe('sheet-ID_0987654321')
        ->and($setting->sheet_tab)->toBe('New worksheet')
        ->and($setting->enabled)->toBeFalse()
        ->and($setting->service_account_json)->toBe($secret);
});

it('rejects invalid identifiers, worksheet names, and credential JSON without persisting settings', function (): void {
    googleSheetsSettingsUser('Administrator');
    $secret = '{not-valid-json';

    $this->postJson(route('billing.google-sheets-settings.store'), googleSheetsSettingsPayload([
        'spreadsheet_id' => 'https://docs.google.com/spreadsheets/d/123',
        'sheet_tab' => "Invalid\nWorksheet",
        'service_account_json' => $secret,
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['spreadsheet_id', 'sheet_tab', 'service_account_json'])
        ->assertDontSee($secret);

    $this->assertDatabaseCount('google_sheets_settings', 0);
});

it('shows an application-key diagnostic when saved credentials cannot be decrypted', function (): void {
    googleSheetsSettingsUser('Administrator');
    GoogleSheetsSetting::query()->create([
        'id' => 1,
        'spreadsheet_id' => 'sheet-ID_1234567890',
        'sheet_tab' => 'Copy of SME LOT 2,9,11',
        'service_account_json' => json_encode(['client_email' => 'billing-sheets@example.test', 'private_key' => 'test-key'], JSON_THROW_ON_ERROR),
        'enabled' => true,
    ]);
    DB::table('google_sheets_settings')->where('id', 1)->update(['service_account_json' => 'unreadable-encrypted-content']);

    $this->get(route('billing.google-sheets-settings'))
        ->assertOk()
        ->assertSee('APP_KEY')
        ->assertDontSee('unreadable-encrypted-content');
});

it('shows sanitized connection-test success and failure messages', function (): void {
    googleSheetsSettingsUser('Administrator');
    $this->post(route('billing.google-sheets-settings.store'), googleSheetsSettingsPayload([
        'service_account_json' => json_encode(['client_email' => 'billing-sheets@example.test', 'private_key' => 'not-rendered'], JSON_THROW_ON_ERROR),
    ]))->assertRedirect(route('billing.google-sheets-settings'));

    $client = Mockery::mock(GoogleSheetsApiClient::class, [app(GoogleSheetsBillingConfiguration::class)])->makePartial();
    $client->shouldReceive('configurationStatus')->once()->andReturn(['configured' => true, 'reason' => null]);
    $client->shouldReceive('values')->once()->with("'Copy of SME LOT 2,9,11'!A1", 'sheet-ID_1234567890')->andReturn([]);
    app()->instance(GoogleSheetsApiClient::class, $client);
    $this->post(route('billing.google-sheets-settings.test-connection'))->assertRedirect()->assertSessionHas('success');

    $client = Mockery::mock(GoogleSheetsApiClient::class, [app(GoogleSheetsBillingConfiguration::class)])->makePartial();
    $client->shouldReceive('configurationStatus')->once()->andReturn(['configured' => true, 'reason' => null]);
    $client->shouldReceive('values')->once()->with("'Copy of SME LOT 2,9,11'!A1", 'sheet-ID_1234567890')
        ->andThrow(new GoogleSheetsApiException('Provider detail must not be shown.', 403));
    app()->instance(GoogleSheetsApiClient::class, $client);
    $this->post(route('billing.google-sheets-settings.test-connection'))
        ->assertRedirect()
        ->assertSessionHas('error', 'Google denied spreadsheet access. Share the spreadsheet with the service account as an Editor.');
});

it('uses administrator-saved spreadsheet settings for billing sync audit and job requests', function (): void {
    $actor = googleSheetsSettingsUser('finance');
    Queue::fake();
    config(['queue.default' => 'database', 'queue.connections.database.driver' => 'database']);
    config([
        'google_sheets.billing.spreadsheet_id' => 'environment-sheet-ID_123',
        'google_sheets.billing.sheet_tab' => 'Environment tab',
    ]);
    GoogleSheetsSetting::query()->create([
        'id' => 1,
        'spreadsheet_id' => 'saved-sheet-ID_987654321',
        'sheet_tab' => 'Copy of SME LOT 2,9,11',
        'service_account_json' => json_encode(['client_email' => 'saved@example.test', 'private_key' => 'saved-private-key'], JSON_THROW_ON_ERROR),
        'enabled' => true,
    ]);
    $deliveryId = createGoogleSheetsSyncDelivery();

    app(DeliveryBillingService::class)->markForBilling($deliveryId, $actor);
    $sync = BillingSheetSync::query()->sole();
    expect($sync->spreadsheet_id)->toBe('saved-sheet-ID_987654321')
        ->and($sync->sheet_tab)->toBe('Copy of SME LOT 2,9,11')
        ->and(app(GoogleSheetsBillingConfiguration::class)->current()['credential_source'])->toBe('database');
    Queue::assertPushed(SyncBillingSheetDate::class);

    $rows = array_fill(0, 2, array_fill(0, 87, ''));
    $rows[0][1] = 'BIR DR Lot 2';
    $rows[0][2] = 'Core Package Code Lot2';
    $rows[1][1] = $sync->bir_dr;
    $rows[1][2] = $sync->package_code;
    $sheets = Mockery::mock(GoogleSheetsApiClient::class);
    $sheets->shouldReceive('configurationStatus')->once()->andReturn(['configured' => true, 'reason' => null]);
    $sheets->shouldReceive('values')->once()->with("'Copy of SME LOT 2,9,11'!A:CI", 'saved-sheet-ID_987654321')->andReturn($rows);
    $sheets->shouldReceive('updateCell')->once()->with("'Copy of SME LOT 2,9,11'!BF2", $sync->transition_date->toDateString(), 'saved-sheet-ID_987654321');

    (new SyncBillingSheetDate($sync->getKey()))->handle(app(BillingSheetSyncService::class), $sheets);

    expect($sync->fresh()->outcome)->toBe('synced');
});
