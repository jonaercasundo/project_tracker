<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectDetailsTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, ProjectDetailsConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];

        return $app;
    }

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_06_15_081615_create_permission_tables.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__.'/../Fixtures/jarvis_schema.php';
        (require database_path('migrations/2026_10_16_093347_add_notice_of_award_date_to_projects_table.php'))->up();
        Schema::create('AR_settings', function (Blueprint $table): void {
            $table->integer('project_id');
            foreach (['project_name', 'company', 'client', 'ar_company_footer', 'ar_address_footer', 'ar_contact_footer', 'ar_logo'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['display_label', 'display_school_id', 'label_school_id', 'label_municipality', 'label_division', 'label_region'] as $column) {
                $table->integer($column)->nullable();
            }
        });
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }
}

/** Avoid unrelated legacy console discovery, following the existing Jarvis test harness. */
class ProjectDetailsConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(ProjectDetailsTestCase::class);

function projectDetailsUser(string $companyCode = 'MMC', string $role = 'user'): User
{
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $role]);
    $user->assignRole(Role::findOrCreate($role, 'web'));
    $company = Company::firstOrCreate(['code' => $companyCode], ['name' => $companyCode, 'is_active' => true]);
    $user->companies()->attach($company);
    test()->actingAs($user)->withSession(['company_id' => $company->company_id]);

    return $user;
}

function projectDetailsFixtures(): void
{
    DB::table('projects')->insert([
        ['project_id' => 1, 'project_name' => 'Science', 'keystage' => 1],
        ['project_id' => 2, 'project_name' => 'Other', 'keystage' => 0],
    ]);
    DB::table('school')->insert([
        ['school_id' => '001', 'school_name' => 'Direct', 'project_id' => 1, 'address' => 'Special Street', 'region' => 'North', 'division' => 'A', 'municipality' => 'Town A'],
        ['school_id' => '002', 'school_name' => 'Delivery', 'project_id' => 2, 'address' => 'Road', 'region' => 'South', 'division' => 'B', 'municipality' => 'Town B'],
        ['school_id' => '003', 'school_name' => 'Outside', 'project_id' => 2, 'address' => 'Road', 'region' => 'North', 'division' => 'A', 'municipality' => 'Town C'],
    ]);
    DB::table('deliveries')->insert([
        ['project_id' => 1, 'school_id' => '001'],
        ['project_id' => 1, 'school_id' => '002'],
        ['project_id' => 1, 'school_id' => '002'],
    ]);
    DB::table('lot')->insert([
        ['lot_id' => 1, 'project_id' => 1, 'lot_name' => 'Lot A'],
        ['lot_id' => 2, 'project_id' => 1, 'lot_name' => 'Lot B'],
        ['lot_id' => 3, 'project_id' => 2, 'lot_name' => 'Other lot'],
    ]);
    DB::table('keystage')->insert(['keystage_id' => 1, 'lot_id' => 2, 'keystage_num' => 1, 'description' => 'Intermediate']);
    DB::table('package')->insert([
        ['package_id' => 1, 'package_num' => 1, 'lot_id' => 1, 'keystage_id' => null],
        ['package_id' => 2, 'package_num' => 2, 'lot_id' => null, 'keystage_id' => 1],
        ['package_id' => 3, 'package_num' => 3, 'lot_id' => 3, 'keystage_id' => null],
    ]);
    DB::table('item')->insert([
        ['item_id' => 1, 'project_id' => 1, 'item_name' => 'Microscope'],
        ['item_id' => 2, 'project_id' => 2, 'item_name' => 'Other item'],
    ]);
}

function projectBiddingEvidenceFixtures(bool $withEvidence = true): void
{
    Schema::create('project_information', function (Blueprint $table): void {
        $table->id();
        $table->string('project_id')->nullable();
        $table->string('project_code')->nullable();
        $table->text('project_name')->nullable();
        $table->string('status')->nullable();
        $table->date('notice_of_award_date')->nullable();
    });
    $projectInformationId = DB::table('project_information')->insertGetId([
        'project_id' => '1',
        'project_code' => 'SME-2025',
        'project_name' => 'Science procurement',
        'status' => 'Awarded',
        'notice_of_award_date' => '2026-09-01',
    ]);
    Schema::create('lots', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->string('lot_no')->nullable();
        $table->timestamps();
    });
    $lotTwoId = DB::table('lots')->insertGetId(['project_id' => $projectInformationId, 'lot_no' => 'Lot 2']);
    $lotThreeId = DB::table('lots')->insertGetId(['project_id' => $projectInformationId, 'lot_no' => 'Lot 3']);
    Schema::create('bidding_documents', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_information_id');
        $table->unsignedBigInteger('folder_id')->nullable();
        $table->string('display_name');
        $table->string('original_name');
        $table->string('stored_name');
        $table->string('storage_disk');
        $table->string('storage_path');
        $table->string('mime_type');
        $table->string('extension');
        $table->unsignedBigInteger('file_size');
        $table->text('description')->nullable();
        $table->integer('uploaded_by')->nullable();
        $table->unsignedInteger('version')->default(1);
        $table->timestamp('uploaded_at')->nullable();
        $table->timestamps();
    });
    $noaDocumentId = DB::table('bidding_documents')->insertGetId([
        'project_information_id' => $projectInformationId,
        'display_name' => 'Signed NOA',
        'original_name' => 'noa.pdf',
        'stored_name' => 'private-noa.pdf',
        'storage_disk' => 'local',
        'storage_path' => 'private/noa.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'file_size' => 100,
        'version' => 1,
        'uploaded_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $ntpDocumentId = DB::table('bidding_documents')->insertGetId([
        'project_information_id' => $projectInformationId,
        'display_name' => 'Signed NTP',
        'original_name' => 'ntp.pdf',
        'stored_name' => 'private-ntp.pdf',
        'storage_disk' => 'local',
        'storage_path' => 'private/ntp.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'file_size' => 100,
        'version' => 1,
        'uploaded_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('bidding_documents')->insert([
        'project_information_id' => $projectInformationId,
        'display_name' => 'Contract attachment',
        'original_name' => 'contract.pdf',
        'stored_name' => 'private-contract.pdf',
        'storage_disk' => 'local',
        'storage_path' => 'private/contract.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'file_size' => 100,
        'description' => 'Contract processing document',
        'version' => 1,
        'uploaded_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::create('project_lifecycle_tracks', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('company_id');
        $table->integer('project_id');
        $table->unsignedBigInteger('project_information_id');
        $table->integer('legacy_lot_id')->nullable();
        $table->unsignedBigInteger('bidding_lot_id')->nullable();
        $table->timestamps();
    });
    Schema::create('project_lifecycle_events', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_lifecycle_track_id');
        $table->integer('actor_user_id')->nullable();
        $table->string('event_type');
        $table->string('from_stage')->nullable();
        $table->string('to_stage')->nullable();
        $table->string('outcome')->nullable();
        $table->text('note')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    if (! $withEvidence) {
        return;
    }

    $companyId = DB::table('companies')->where('code', 'MMC')->value('company_id');
    $actorId = auth()->id();
    $lotTwoTrackId = DB::table('project_lifecycle_tracks')->insertGetId([
        'company_id' => $companyId,
        'project_id' => 1,
        'project_information_id' => $projectInformationId,
        'bidding_lot_id' => $lotTwoId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('project_lifecycle_tracks')->insert([
        'company_id' => $companyId,
        'project_id' => 1,
        'project_information_id' => $projectInformationId,
        'bidding_lot_id' => $lotThreeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('project_lifecycle_events')->insert([
        [
            'project_lifecycle_track_id' => $lotTwoTrackId,
            'actor_user_id' => $actorId,
            'event_type' => 'noa_verified',
            'from_stage' => 'Evaluation',
            'to_stage' => 'Awarded',
            'note' => 'NOA reviewed and verified',
            'metadata' => json_encode(['reference' => 'NOA-778', 'document_id' => $noaDocumentId, 'verified_by' => $actorId, 'event_date' => '2026-09-01', 'award_amount' => '100.12']),
            'created_at' => now()->subDay(),
        ],
        [
            'project_lifecycle_track_id' => $lotTwoTrackId,
            'actor_user_id' => $actorId,
            'event_type' => 'ntp_verified',
            'from_stage' => 'Awarded',
            'to_stage' => 'Implementation',
            'note' => 'NTP reviewed and verified',
            'metadata' => json_encode(['reference' => 'NTP-445', 'document_id' => $ntpDocumentId, 'verified_by' => $actorId, 'event_date' => '2026-09-05', 'effectivity_date' => '2026-09-10']),
            'created_at' => now(),
        ],
    ]);
}

it('renders SQL summaries without querying or rendering large detail collections', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    DB::enableQueryLog();

    $response = $this->get('/projects/1');

    $response->assertOk()->assertViewHas('schoolCount', 2)->assertViewHas('itemCount', 1)
        ->assertViewHas('packageCount', 2)->assertViewHas('lotCount', 2)->assertViewHas('keystageCount', 1)
        ->assertSee('Lot A')->assertSee('Intermediate')->assertDontSee('Microscope')->assertDontSee('data-school-id=', false);
    $queries = collect(DB::getQueryLog())->pluck('query');
    expect($queries->filter(fn (string $sql): bool => preg_match('/select \* from "(school|item|package|deliveries)"/i', $sql))->all())->toBe([]);
});

it('shows verified NOA and NTP references with per-lot evidence and protected document actions', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    projectBiddingEvidenceFixtures();

    $response = $this->get('/projects/1');

    $response->assertOk()
        ->assertSee('Bidding Documents &amp; Award References', false)
        ->assertSee('Bidding record #1')
        ->assertSee('Lot 2')
        ->assertSee('Lot 3')
        ->assertSee('NOA-778')
        ->assertSee('NTP-445')
        ->assertSee('2026-09-10')
        ->assertSee('Signed NOA')
        ->assertSee('Contract attachment')
        ->assertSee(route('project.bidding.documents.preview', [1, 1]), false)
        ->assertSee(route('project.bidding.documents.download', [1, 1]), false)
        ->assertSee('No verified delivery acceptance linked')
        ->assertDontSee('private/noa.pdf');

    projectDetailsUser('MI');
    $this->get('/project/bidding/1/documents/1/download')->assertForbidden();
});

it('explains when no bidding document or verified lifecycle evidence is linked', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    projectBiddingEvidenceFixtures(false);
    DB::table('bidding_documents')->delete();

    $this->get('/projects/1')
        ->assertOk()
        ->assertSee('No verified NOA recorded')
        ->assertSee('No verified NTP recorded')
        ->assertSee('Not yet uploaded.')
        ->assertSee('No verified NOA, NTP, or delivery acceptance evidence recorded for this scope.');
});

it('allows authorized MMC Finance users to read Project Details and gives them read-only document links', function () {
    projectDetailsUser('MMC', 'finance');
    projectDetailsFixtures();
    projectBiddingEvidenceFixtures();

    $this->get('/projects/1')
        ->assertOk()
        ->assertSee(route('bidding.documents.download', [1, 1]), false)
        ->assertDontSee(route('project.bidding.documents.download', [1, 1]), false)
        ->assertDontSee('Open Bidding record')
        ->assertDontSee('Project Setting');
});

it('keeps Project Details unavailable to users whose active company is not MMC', function () {
    projectDetailsFixtures();
    $this->get('/projects/1')->assertRedirect(route('login'));

    projectDetailsUser('MI');

    $this->get('/projects/1')->assertForbidden();
});

it('does not expose bidding evidence when the legacy project identifier matches multiple records', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    projectBiddingEvidenceFixtures();
    DB::table('project_information')->insert([
        'project_id' => '1',
        'project_code' => 'SME-2025-DUPLICATE',
        'project_name' => 'Ambiguous second bidding record',
        'status' => 'Awarded',
    ]);

    $this->get('/projects/1')
        ->assertOk()
        ->assertSee('No verified Bidding record is linked to this project.')
        ->assertDontSee('NOA-778')
        ->assertDontSee('Signed NOA')
        ->assertDontSee('private/noa.pdf');
});

it('includes direct and delivered schools once and applies SQL search and location filters', function () {
    projectDetailsUser();
    projectDetailsFixtures();

    $this->getJson('/projects/1/schools-data')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('total_count', 2)->assertSee('Direct')->assertSee('Delivery')->assertDontSee('Outside');
    $this->getJson('/projects/1/schools-data?search=Special&region=North&division=A&municipality=Town%20A')
        ->assertJsonPath('total', 1)->assertSee('Direct')->assertDontSee('Delivery');
    $this->getJson('/projects/1/schools-data?search=Delivery&region=North')->assertJsonPath('total', 0);
    $this->getJson('/projects/1/schools-data?search=%27%20OR%201%3D1--')->assertJsonPath('total', 0);
});

it('returns project scoped dependent location options', function () {
    projectDetailsUser();
    projectDetailsFixtures();

    $this->getJson('/projects/1/detail-options?section=schools&level=region')->assertExactJson(['options' => ['North', 'South']]);
    $this->getJson('/projects/1/detail-options?section=schools&level=division&region=North')->assertExactJson(['options' => ['A']]);
    $this->getJson('/projects/1/detail-options?section=schools&level=municipality&region=North&division=A')->assertExactJson(['options' => ['Town A']]);
});

it('filters items and packages using their actual schema and project membership', function () {
    projectDetailsUser();
    projectDetailsFixtures();

    $this->getJson('/projects/1/items-data?search=Microscope')->assertJsonPath('total', 1)->assertSee('Microscope')->assertDontSee('Other item');
    $this->getJson('/projects/1/packages-data')->assertJsonPath('total', 2)->assertSee('Package 1')->assertSee('Package 2')->assertDontSee('Package 3');
    $this->getJson('/projects/1/packages-data?lot=2&keystage=1&search=2')->assertJsonPath('total', 1)->assertSee('Package 2');
    $this->getJson('/projects/1/packages-data?search=Package%202')->assertJsonPath('total', 1)->assertSee('Package 2');
    $this->getJson('/projects/1/packages-data?lot=1&keystage=1')->assertJsonPath('total', 0);
    $this->getJson('/projects/1/detail-options?section=items')->assertExactJson(['options' => []]);
    $this->getJson('/projects/1/items-data?item_type=Unknown')->assertJsonPath('total', 0);
    $this->getJson('/projects/1/packages-data?package_type=Unknown')->assertJsonPath('total', 0);
});

it('uses type filters when the schema contains type columns', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    Schema::table('item', fn (Blueprint $table) => $table->string('item_type')->nullable());
    Schema::table('package', fn (Blueprint $table) => $table->string('package_type')->nullable());
    DB::table('item')->where('item_id', 1)->update(['item_type' => 'Science']);
    DB::table('package')->where('package_id', 2)->update(['package_type' => 'Kit']);

    $this->getJson('/projects/1/detail-options?section=items')->assertExactJson(['options' => ['Science']]);
    $this->getJson('/projects/1/items-data?item_type=Science')->assertJsonPath('total', 1);
    $this->getJson('/projects/1/items-data?item_type=Other')->assertJsonPath('total', 0);
    $this->getJson('/projects/1/packages-data?package_type=Kit')->assertJsonPath('total', 1)->assertSee('Package 2');
});

it('paginates detail rows in SQL with a default of 25 and a maximum of 100', function (string $section) {
    projectDetailsUser();
    projectDetailsFixtures();
    for ($number = 10; $number < 130; $number++) {
        $record = match ($section) {
            'schools' => ['school_id' => (string) $number, 'project_id' => 1, 'school_name' => 'School '.$number],
            'items' => ['item_id' => $number, 'project_id' => 1, 'item_name' => 'Item '.$number],
            'packages' => ['package_id' => $number, 'lot_id' => 1, 'package_num' => $number],
        };
        DB::table(match ($section) {
            'schools' => 'school', 'items' => 'item', 'packages' => 'package'
        })->insert($record);
    }
    DB::enableQueryLog();

    $first = $this->getJson('/projects/1/'.$section.'-data');

    $first->assertOk()->assertJsonPath('per_page', 25)->assertJsonPath('from', 1)->assertJsonPath('to', 25);
    expect(substr_count($first->json('html'), 'class="'.rtrim($section, 's').'-row'))->toBe(25);
    expect(collect(DB::getQueryLog())->pluck('query')->contains(fn (string $sql): bool => str_contains($sql, 'limit 25 offset 0')))->toBeTrue();
    $this->getJson('/projects/1/'.$section.'-data?page=2')->assertJsonPath('from', 26)->assertJsonPath('to', 50);
    $this->getJson('/projects/1/'.$section.'-data?per_page=100')->assertJsonPath('to', 100);
    $this->getJson('/projects/1/'.$section.'-data?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    $this->getJson('/projects/1/'.$section.'-data?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
})->with(['schools', 'items', 'packages']);

it('keeps large lot and keystage structures out of the initial HTML and bounds their endpoints', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    for ($number = 10; $number < 130; $number++) {
        DB::table('keystage')->insert(['keystage_id' => $number, 'lot_id' => 1, 'keystage_num' => $number, 'description' => 'Large structure detail']);
    }

    $this->get('/projects/1')->assertOk()->assertViewHas('structureIsSmall', false)->assertDontSee('Large structure detail');
    $this->getJson('/projects/1/lots-data')->assertJsonPath('total', 2)->assertSee('120 keystages');
    $this->getJson('/projects/1/keystages-data?lot=1&page=2')->assertJsonPath('total', 120)->assertJsonPath('from', 26)->assertJsonPath('to', 50);
    $this->getJson('/projects/1/detail-options?section=packages&level=keystage&lot=1')->assertJsonCount(100, 'options')->assertJsonPath('next_page', 2);
});

it('escapes school values and returns fresh rows and counts after data changes', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    DB::table('school')->insert(['school_id' => '004', 'project_id' => 1, 'school_name' => '<script>alert(1)</script>']);

    $response = $this->getJson('/projects/1/schools-data')->assertJsonPath('total', 3);
    expect($response->json('html'))->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>');
    DB::table('school')->where('school_id', '004')->update(['school_name' => 'Renamed']);
    $this->getJson('/projects/1/schools-data?search=Renamed')->assertJsonPath('total', 1)->assertJsonPath('total_count', 3);
    DB::table('school')->where('school_id', '004')->delete();
    $this->getJson('/projects/1/schools-data')->assertJsonPath('total', 2)->assertJsonPath('total_count', 2);
});

it('requires authentication and MMC project access on each detail endpoint', function (string $endpoint) {
    projectDetailsFixtures();
    $this->getJson('/projects/1/'.$endpoint)->assertUnauthorized();
    projectDetailsUser('MI');
    $this->getJson('/projects/1/'.$endpoint)->assertForbidden();
})->with(['schools-data', 'items-data', 'packages-data', 'lots-data', 'keystages-data', 'detail-options']);

it('rejects disallowed roles, missing projects and unsafe option identifiers', function () {
    projectDetailsFixtures();
    projectDetailsUser('MMC', 'Administrator');
    $this->getJson('/projects/1/schools-data')->assertForbidden();
    projectDetailsUser();

    $this->getJson('/projects/999/schools-data')->assertNotFound();
    $this->getJson('/projects/1/detail-options?section=schools&level=school_name')->assertUnprocessable()->assertJsonValidationErrors('level');
    $this->getJson('/projects/1/detail-options?section=deliveries&level=region')->assertUnprocessable()->assertJsonValidationErrors('section');
});

it('keeps the initial response bounded as detail counts grow', function () {
    projectDetailsUser();
    projectDetailsFixtures();
    $small = $this->get('/projects/1')->assertOk();
    for ($batch = 0; $batch < 20; $batch++) {
        $schools = $items = $packages = [];
        for ($offset = 0; $offset < 500; $offset++) {
            $number = 10 + $batch * 500 + $offset;
            $schools[] = ['school_id' => (string) $number, 'project_id' => 1, 'school_name' => 'Large school '.$number];
            $items[] = ['item_id' => $number, 'project_id' => 1, 'item_name' => 'Large item '.$number];
            $packages[] = ['package_id' => $number, 'lot_id' => 1, 'package_num' => $number];
        }
        DB::table('school')->insert($schools);
        DB::table('item')->insert($items);
        DB::table('package')->insert($packages);
    }
    unset($schools, $items, $packages);
    DB::flushQueryLog();
    DB::enableQueryLog();
    memory_reset_peak_usage();
    $start = hrtime(true);

    $response = $this->get('/projects/1');

    $elapsed = (hrtime(true) - $start) / 1_000_000;
    $peak = memory_get_peak_usage(true);
    $response->assertOk()->assertViewHas('schoolCount', 10002)->assertViewHas('itemCount', 10001)->assertViewHas('packageCount', 10002)
        ->assertDontSee('Large school')->assertDontSee('Large item');
    expect(strlen($response->getContent()))->toBeLessThan(strlen($small->getContent()) + 1000);
    expect(count(DB::getQueryLog()))->toBeLessThan(25);
    expect($peak)->toBeLessThan(128 * 1024 * 1024);
    fwrite(STDERR, PHP_EOL.'Synthetic project (10,000 added records per detail table): '.json_encode([
        'http_status' => $response->getStatusCode(), 'response_ms' => round($elapsed, 2),
        'peak_php_bytes' => $peak, 'html_bytes' => strlen($response->getContent()), 'queries' => count(DB::getQueryLog()),
    ]).PHP_EOL);
});

it('renders empty projects and empty filtered pages without changing their counts', function () {
    projectDetailsUser();
    DB::table('projects')->insert(['project_id' => 1, 'project_name' => 'Empty project']);

    $this->get('/projects/1')->assertOk()->assertViewHas('schoolCount', 0)->assertViewHas('itemCount', 0)->assertViewHas('packageCount', 0)->assertViewHas('structureIsSmall', true);
    $this->getJson('/projects/1/schools-data?page=5')->assertJsonPath('total', 0)->assertJsonPath('total_count', 0)->assertJsonPath('from', null)->assertSee('No schools found');
    $this->getJson('/projects/1/items-data')->assertJsonPath('total', 0)->assertSee('No items found');
    $this->getJson('/projects/1/packages-data')->assertJsonPath('total', 0)->assertSee('No packages found');
});

it('preserves projects that already have delivery records', function () {
    projectDetailsUser();
    projectDetailsFixtures();

    $this->delete(route('projects.destroy', 1))
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('error', 'This project has linked records and cannot be deleted.');

    $this->assertDatabaseHas('projects', ['project_id' => 1]);
});

/**
 * @return array{ref_no: string, project_name: string, agency: string, contract_amount: int, ABC: int, start_date: string, end_date: string, status: string}
 */
function projectAgencyPayload(): array
{
    return [
        'ref_no' => 'AGENCY-001',
        'project_name' => 'Community equipment',
        'agency' => '__other__',
        'contract_amount' => 1000,
        'ABC' => 1200,
        'start_date' => '2026-10-01',
        'end_date' => '2026-11-01',
        'status' => 'Pending',
    ];
}

describe('project agencies', function () {
    it('saves the trimmed custom agency name when Others is selected', function () {
        projectDetailsUser();
        $payload = [...projectAgencyPayload(), 'other_agency' => '  City Housing Office  '];

        $response = $this->from(route('projects.index'))->post(route('projects.store'), $payload);

        $response->assertRedirect(route('projects.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Project added successfully.');
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', ['ref_no' => 'AGENCY-001', 'agency' => 'City Housing Office']);
    });

    it('rejects invalid agency input without creating a project', function (array $input, string $field, string $message) {
        projectDetailsUser();

        $response = $this->from(route('projects.index'))->post(route('projects.store'), [...projectAgencyPayload(), ...$input]);

        $response->assertRedirect(route('projects.index'))->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseCount('projects', 0);
    })->with([
        'missing custom name' => [[], 'other_agency', 'Please enter an agency name.'],
        'whitespace custom name' => [['other_agency' => '   '], 'other_agency', 'Please enter an agency name.'],
        'custom name too long' => [['other_agency' => str_repeat('A', 256)], 'other_agency', 'The other agency field must not be greater than 255 characters.'],
        'custom name is an array' => [['other_agency' => ['City Housing Office']], 'other_agency', 'The other agency field must be a string.'],
        'selected name too long' => [['agency' => str_repeat('A', 256)], 'agency', 'The agency field must not be greater than 255 characters.'],
    ]);

    it('reuses the selected agency and ignores an unused custom name', function (string $agency) {
        projectDetailsUser();
        DB::table('projects')->insert(['project_name' => 'Existing project', 'agency' => $agency]);
        $payload = [...projectAgencyPayload(), 'agency' => $agency, 'other_agency' => ['Unused input']];

        $response = $this->from(route('projects.index'))->post(route('projects.store'), $payload);

        $response->assertRedirect(route('projects.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('projects', 2);
        $this->assertDatabaseHas('projects', ['ref_no' => 'AGENCY-001', 'agency' => $agency]);
    })->with(['Deped', 'Dpwh', 'City Housing Office']);

    it('restores the custom agency and entered project fields after validation fails', function () {
        projectDetailsUser();
        DB::connection()->getPdo()->sqliteCreateFunction('YEAR', static fn (?string $date): ?int => $date === null ? null : (int) substr($date, 0, 4), 1);
        $payload = [
            ...projectAgencyPayload(),
            'other_agency' => 'Housing "Office"',
            'contract_number' => 'CONTRACT-42',
            'notice_of_award_date' => '2026-09-23',
            'end_date' => '2026-09-01',
        ];

        $rejected = $this->from(route('projects.index'))->post(route('projects.store'), $payload);

        $rejected->assertRedirect(route('projects.index'))->assertSessionHasErrors('end_date')
            ->assertSessionHasInput('agency', '__other__')
            ->assertSessionHasInput('contract_amount', 1000)
            ->assertSessionHasInput('ABC', 1200)
            ->assertSessionHasInput('notice_of_award_date', '2026-09-23');
        $this->assertDatabaseCount('projects', 0);
        $response = $this->get(route('projects.index'));
        $response->assertSee('The end date field must be a date after or equal to start date.');
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        foreach (['ref_no', 'project_name', 'contract_number', 'other_agency', 'notice_of_award_date', 'start_date', 'end_date'] as $field) {
            expect($xpath->evaluate('string(//form[@id="addProjectForm"]//input[@name="'.$field.'"]/@value)'))->toBe($payload[$field]);
        }
        expect($xpath->evaluate('string(//form[@id="addProjectForm"]//label[@for="project_notice_of_award_date"])'))->toContain('Date awarded');
        expect($xpath->evaluate('string(//form[@id="addProjectForm"]//input[@name="notice_of_award_date"]/@type)'))->toBe('date');
        expect($xpath->query('//form[@id="addProjectForm"]//input[@name="notice_of_award_date"]/@required'))->toHaveCount(0);
        expect($xpath->evaluate('string(//form[@id="addProjectForm"]//*[@role="alert"])'))
            ->toContain('The end date field must be a date after or equal to start date.');
    });

    it('offers saved agencies once and escapes custom names in the agency dropdown', function () {
        projectDetailsUser();
        DB::connection()->getPdo()->sqliteCreateFunction('YEAR', static fn (?string $date): ?int => $date === null ? null : (int) substr($date, 0, 4), 1);
        $customAgency = 'Housing "Office" <script>alert(1)</script>';
        foreach (['Deped', $customAgency, $customAgency, '', null] as $index => $agency) {
            DB::table('projects')->insert([
                ...projectAgencyPayload(),
                'ref_no' => 'EXISTING-'.$index,
                'agency' => $agency,
                'created_at' => '2026-10-01 00:00:00',
            ]);
        }

        $response = $this->get(route('projects.index'));

        $response->assertSee($customAgency)->assertDontSee($customAgency, false);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $options = [];
        foreach ($xpath->query('//select[@id="project_agency"]/option') as $option) {
            $options[] = $option->getAttribute('value');
        }
        expect($options)->toHaveCount(5)->toContain('', 'Deped', 'Dpwh', $customAgency, '__other__');
        expect($xpath->evaluate('string(//select[@id="project_agency"]/option[@value="__other__"])'))->toBe('Others');
    });
});

describe('project award dates', function () {
    it('creates a project with an optional award date', function (array $awardInput, ?string $expectedDate) {
        projectDetailsUser();
        $payload = [...projectAgencyPayload(), 'agency' => 'Deped', ...$awardInput];

        $response = $this->from(route('projects.index'))->post(route('projects.store'), $payload);

        $response->assertRedirect(route('projects.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Project added successfully.');
        $this->assertDatabaseCount('projects', 1);
        $project = Project::query()->where('ref_no', 'AGENCY-001')->firstOrFail();
        expect($project->notice_of_award_date?->toDateString())->toBe($expectedDate);
    })->with([
        'provided date' => [['notice_of_award_date' => '2026-09-23'], '2026-09-23'],
        'omitted date' => [[], null],
        'blank date' => [['notice_of_award_date' => ''], null],
    ]);

    it('rejects invalid award dates without creating a project', function (string $awardDate) {
        projectDetailsUser();
        $payload = [...projectAgencyPayload(), 'agency' => 'Deped', 'notice_of_award_date' => $awardDate];

        $response = $this->from(route('projects.index'))->post(route('projects.store'), $payload);

        $response->assertRedirect(route('projects.index'))->assertSessionHasErrors([
            'notice_of_award_date' => 'The notice of award date field must match the format Y-m-d.',
        ]);
        $this->assertDatabaseCount('projects', 0);
    })->with([
        'impossible calendar date' => '2026-02-30',
        'wrong date format' => '23/09/2026',
    ]);
});
