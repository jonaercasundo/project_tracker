<?php

use App\Models\Company;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryTrackingFilterTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, DeliveryTrackingConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getPdo()->sqliteCreateFunction(
            'YEAR',
            fn (?string $date): ?int => $date === null ? null : (int) substr($date, 0, 4)
        );

        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_name');
        });
        Schema::create('lot', function (Blueprint $table): void {
            $table->increments('lot_id');
            $table->unsignedInteger('project_id');
            $table->string('lot_name');
        });
        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->string('school_name');
            $table->string('address')->nullable();
            $table->string('region')->nullable();
            $table->string('division')->nullable();
            $table->string('municipality')->nullable();
        });
        Schema::create('keystage', function (Blueprint $table): void {
            $table->increments('keystage_id');
            $table->string('keystage_num')->nullable();
            $table->string('description')->nullable();
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->unsignedInteger('project_id');
            $table->string('school_id');
            $table->unsignedInteger('lot_id');
            $table->unsignedInteger('keystage_id')->nullable();
            $table->string('dr_no');
            $table->date('delivery_date');
            $table->string('status');
            $table->unsignedInteger('package_qty')->default(1);
        });
        Schema::create('package', function (Blueprint $table): void {
            $table->increments('package_id');
            $table->unsignedInteger('lot_id')->nullable();
            $table->unsignedInteger('keystage_id')->nullable();
        });
        Schema::create('package_content', function (Blueprint $table): void {
            $table->increments('package_content_id');
            $table->unsignedInteger('package_id');
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('qty');
        });
        Schema::create('item', function (Blueprint $table): void {
            $table->increments('item_id');
            $table->string('item_name');
        });
        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('package_id');
            $table->string('status');
        });

        DB::table('projects')->insert([
            ['project_id' => 1, 'project_name' => '2026 School Nutrition and Learning Materials Distribution Program — Northern Division'],
            ['project_id' => 2, 'project_name' => 'Furniture Project'],
        ]);
        DB::table('lot')->insert([
            ['lot_id' => 1, 'project_id' => 1, 'lot_name' => 'Lot A'],
            ['lot_id' => 2, 'project_id' => 2, 'lot_name' => 'Lot B'],
        ]);
        DB::table('school')->insert([
            ['school_id' => 'S-1', 'school_name' => 'North School', 'address' => 'North Road', 'region' => 'North', 'division' => 'North Division', 'municipality' => 'North Town'],
            ['school_id' => 'S-2', 'school_name' => 'South School', 'address' => 'South Road', 'region' => 'South', 'division' => 'South Division', 'municipality' => 'South Town'],
        ]);
        $deliveries = [];
        for ($deliveryId = 1; $deliveryId <= 11; $deliveryId++) {
            $deliveries[] = [
                'delivery_id' => $deliveryId,
                'project_id' => 1,
                'school_id' => 'S-1',
                'lot_id' => 1,
                'dr_no' => 'DR-A-'.str_pad((string) $deliveryId, 2, '0', STR_PAD_LEFT),
                'delivery_date' => '2026-03-01',
                'status' => 'pending',
            ];
        }
        $deliveries[] = [
            'delivery_id' => 12,
            'project_id' => 2,
            'school_id' => 'S-2',
            'lot_id' => 2,
            'dr_no' => 'DR-B-01',
            'delivery_date' => '2026-03-03',
            'status' => 'pending',
        ];
        DB::table('deliveries')->insert($deliveries);

        $user = Mockery::mock(User::class)->makePartial();
        $company = new Company(['code' => 'MMC', 'is_active' => true]);
        $user->forceFill(['user_id' => 1, 'name' => 'Delivery Test', 'email' => 'delivery@example.test']);
        $user->setRelation('companies', collect());
        $user->shouldReceive('currentCompany')->andReturn($company);
        $user->shouldReceive('hasAnyRole')->andReturn(true);
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('can')->andReturn(false);

        $dashboardService = Mockery::mock(DashboardService::class);
        $dashboardService->shouldReceive('currentCompany')->andReturn($company);
        $this->app->instance(DashboardService::class, $dashboardService);

        $this->actingAs($user);
    }
}

class DeliveryTrackingConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(DeliveryTrackingFilterTestCase::class)->in(__FILE__);

it('filters delivery results by project and preserves the query on pagination', function (): void {
    $response = $this->get('/deliveries/tracking?'.http_build_query([
        'project' => 1,
        'year' => 2026,
        'status' => 'pending',
        'search' => 'DR-A',
        'per_page' => 10,
        'page' => 2,
    ]));

    $response->assertOk()
        ->assertSee('DR #DR-A-11')
        ->assertDontSee('DR #DR-B-01')
        ->assertSee('project=1')
        ->assertSee('search=DR-A')
        ->assertSee('status=pending');
});

it('renders the complete project name in the searchable dropdown and keeps the project selection after reload', function (): void {
    $projectName = '2026 School Nutrition and Learning Materials Distribution Program — Northern Division';

    $response = $this->get('/deliveries/tracking?project=1&lot=1&year=2026');

    $response->assertOk()
        ->assertSee('role="combobox"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('keydown.arrow-down', false)
        ->assertSee('keydown.escape', false)
        ->assertSee('whitespace-normal break-words', false)
        ->assertDontSee('<select id="project"', false)
        ->assertSee('Search projects...', false)
        ->assertSee('All Projects')
        ->assertSee($projectName)
        ->assertSee('name="project" value="1"', false)
        ->assertSee('value="1" selected', false);
});

it('keeps the selected lot available when All Projects is selected', function (): void {
    $response = $this->get('/deliveries/tracking?project=&lot=1&year=2026&status=pending&search=DR-A');

    $response->assertOk()
        ->assertSee('name="project" value=""', false)
        ->assertSee('value="1" selected', false)
        ->assertSee('DR #DR-A-01')
        ->assertDontSee('DR #DR-B-01');
});
