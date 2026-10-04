<?php

use App\Models\Company;
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
