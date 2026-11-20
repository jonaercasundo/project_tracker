<?php

use App\Models\Company;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View as BladeView;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
        Schema::create('logistics', function (Blueprint $table): void {
            $table->increments('logistic_id');
            $table->string('logistic_name');
        });
        Schema::create('logistics_location', function (Blueprint $table): void {
            $table->increments('logistics_location_id');
            $table->unsignedInteger('logistics_id');
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->unsignedInteger('project_id');
            $table->string('school_id');
            $table->unsignedInteger('lot_id');
            $table->unsignedInteger('keystage_id')->nullable();
            $table->string('dr_no');
            $table->date('delivery_date');
            $table->date('delivered_date')->nullable();
            $table->date('accepted_date')->nullable();
            $table->unsignedInteger('logistics_location_id')->nullable();
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
        $deliveries[] = [
            'delivery_id' => 13,
            'project_id' => 2,
            'school_id' => 'S-2',
            'lot_id' => 2,
            'dr_no' => 'DR-B-2025',
            'delivery_date' => '2025-04-03',
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
        $user->shouldReceive('checkPermissionTo')->andReturn(false);

        $dashboardService = Mockery::mock(DashboardService::class);
        $dashboardService->shouldReceive('currentCompany')->andReturn($company);
        $this->app->instance(DashboardService::class, $dashboardService);

        $progressService = Mockery::mock(ProjectDeliveryProgressService::class);
        $progressService->shouldReceive('report')->andReturn(['summary' => [
            'pending_deliveries_count' => 0,
            'stock_out_drs_count' => 0,
            'completed_deliveries_count' => 0,
            'billing_drs_count' => 0,
            'billed_delivery_drs_count' => 0,
        ]]);
        $this->app->instance(ProjectDeliveryProgressService::class, $progressService);

        $this->actingAs($user);
    }
}

class DeliveryTrackingConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(DeliveryTrackingFilterTestCase::class)->in(__FILE__);

it('filters delivery results by project and preserves the query on pagination', function (): void {
    $response = $this->getJson('/deliveries/tracking?'.http_build_query([
        'project' => 1,
        'status' => 'pending',
        'search' => 'DR-A',
        'per_page' => 10,
        'page' => 2,
        'phase' => 'records',
    ]));

    $response->assertOk();
    expect($response->json('html'))
        ->toContain('DR #DR-A-11', 'project=1', 'search=DR-A', 'status=pending')
        ->not->toContain('DR #DR-B-01');
});

it('renders the complete project name in the searchable dropdown and keeps the project selection after reload', function (): void {
    $projectName = '2026 School Nutrition and Learning Materials Distribution Program — Northern Division';

    $response = $this->get('/deliveries/tracking?project=1&lot=1');

    $response->assertOk()
        ->assertSee('role="combobox"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('keydown.arrow-down', false)
        ->assertSee('keydown.escape', false)
        ->assertSee('whitespace-normal break-words', false)
        ->assertDontSee('<select id="project"', false)
        ->assertSee('Search projects...', false)
        ->assertSee('Furniture Project')
        ->assertSee($projectName)
        ->assertSee('name="project" value="1"', false)
        ->assertSee('value="1" selected', false);
});

it('does not load delivery records before a project is selected', function (): void {
    $response = $this->get('/deliveries/tracking?lot=1&status=pending&search=DR-A');

    $response->assertOk()
        ->assertSee('name="project" value=""', false)
        ->assertSee('Choose a project to load delivery records.')
        ->assertDontSee('DR #DR-A-01');
});

it('offers all projects immediately without a Year filter', function (): void {
    $response = $this->get('/deliveries/tracking');

    $response->assertOk()
        ->assertDontSee('All Years')
        ->assertDontSee('Select Year First')
        ->assertSee('Select Project')
        ->assertSee('Furniture Project')
        ->assertSee('id="project"', false)
        ->assertDontSee('name="year"', false)
        ->assertSee('Choose a project to load delivery records.');
});

it('loads a selected project across all delivery years', function (): void {
    $response = $this->getJson('/deliveries/tracking?project=2&phase=records');

    $response->assertOk();
    expect($response->json('html'))
        ->toContain('Furniture Project', 'DR #DR-B-01', 'DR #DR-B-2025')
        ->not->toContain('DR #DR-A-01', 'All Projects', 'name="year"');
});

it('filters a project with lot, division, and search without a year filter', function (): void {
    $response = $this->getJson('/deliveries/tracking?'.http_build_query([
        'project' => 2,
        'lot' => 2,
        'division' => 'South Division',
        'search' => 'DR-B-2025',
        'status' => 'pending',
        'phase' => 'records',
    ]));

    $response->assertOk();
    expect($response->json('html'))
        ->toContain('DR #DR-B-2025', 'name="project" value="2"')
        ->not->toContain('DR #DR-A-01', 'name="year"');
});

it('removes a legacy year query while preserving the selected project and other filters', function (): void {
    $response = $this->get('/deliveries/tracking?year=2025&project=1&search=DR-A&status=pending');

    $response->assertRedirect(route('deliveries.tracking', ['project' => 1, 'search' => 'DR-A', 'status' => 'pending']));
});

it('accepts a project without a year', function (): void {
    $response = $this->getJson('/deliveries/tracking?project=1&search=DR-A&phase=records');

    $response->assertOk();
    expect($response->json('html'))->toContain('DR #DR-A-01')->not->toContain('DR #DR-B-01');
});

it('shows the latest status on tracked delivery package items', function (): void {
    DB::table('package')->insert([
        ['package_id' => 21, 'lot_id' => 1],
        ['package_id' => 22, 'lot_id' => 1],
        ['package_id' => 23, 'lot_id' => 1],
    ]);
    DB::table('item')->insert(['item_id' => 31, 'item_name' => 'Workbook']);
    DB::table('package_content')->insert(['package_id' => 21, 'item_id' => 31, 'qty' => 3]);
    DB::table('package_status')->insert([
        ['delivery_id' => 1, 'package_id' => 21, 'status' => 'released'],
        ['delivery_id' => 1, 'package_id' => 21, 'status' => 'delivered'],
        ['delivery_id' => 2, 'package_id' => 21, 'status' => 'released'],
        ['delivery_id' => 1, 'package_id' => 22, 'status' => 'released'],
    ]);

    $packages = [];
    View::composer('deliveries.index', function (BladeView $view) use (&$packages): void {
        $receipt = array_values($view->getData()['grouped_deliveries'])[0];
        $packages = array_values($receipt['deliveries'])[0]->packages;
    });

    $response = $this->getJson('/deliveries/tracking?project=1&search=DR-A-01&phase=records');

    $response->assertOk();
    expect($response->json('html'))->toContain('Workbook (3)');
    expect(array_column($packages, 'status'))->toBe(['delivered', 'released', 'pending']);
});

it('renders each package once per receipt when statuses repeat and delivery scopes overlap', function (): void {
    Schema::create('AR_settings', function (Blueprint $table): void {
        $table->unsignedInteger('project_id');
    });
    Schema::table('package_status', function (Blueprint $table): void {
        $table->string('remarks')->nullable();
    });
    DB::table('keystage')->insert(['keystage_id' => 7, 'keystage_num' => 'Stage 7']);
    DB::table('deliveries')->where('delivery_id', 2)->update([
        'dr_no' => 'DR-A-01',
        'keystage_id' => 7,
    ]);
    DB::table('deliveries')->where('delivery_id', 3)->update([
        'dr_no' => 'DR-A-01',
        'school_id' => 'S-2',
    ]);
    DB::table('package')->insert([
        ['package_id' => 21, 'lot_id' => 1, 'keystage_id' => 7],
        ['package_id' => 22, 'lot_id' => 1, 'keystage_id' => 7],
        ['package_id' => 23, 'lot_id' => 1, 'keystage_id' => null],
    ]);
    DB::table('item')->insert([
        ['item_id' => 31, 'item_name' => 'Shared Workbook'],
        ['item_id' => 32, 'item_name' => 'Lot Guide'],
    ]);
    DB::table('package_content')->insert([
        ['package_id' => 21, 'item_id' => 31, 'qty' => 3],
        ['package_id' => 22, 'item_id' => 31, 'qty' => 5],
        ['package_id' => 23, 'item_id' => 32, 'qty' => 1],
    ]);
    DB::table('package_status')->insert([
        ['delivery_id' => 2, 'package_id' => 21, 'status' => 'pending'],
        ['delivery_id' => 2, 'package_id' => 21, 'status' => 'pending'],
        ['delivery_id' => 1, 'package_id' => 21, 'status' => 'pending'],
    ]);
    $renderedReceipts = [];
    View::composer('deliveries.ar-layout', function (BladeView $view) use (&$renderedReceipts): void {
        $data = $view->getData();
        $statuses = $data['deliveries']->flatMap->packageStatuses;
        $renderedReceipts[] = $statuses->map(fn ($status): array => [
            $status->package_id,
            isset($data['qrCodes'][$status->package_status_id]),
        ])->all();
    });

    $response = $this->get(route('deliveries.pdf', ['ids' => '2,1,2,3']));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    expect($renderedReceipts)->toBe([
        [[21, true], [22, true], [23, true]],
        [[21, true], [22, true], [23, true]],
    ]);
    expect(DB::table('package_status')->where('delivery_id', 2)->where('package_id', 21)->count())->toBe(2);
});

it('shows logistics delivered when every package is delivered without partner tracking', function (): void {
    DB::table('package')->insert([
        ['package_id' => 21, 'lot_id' => 1],
        ['package_id' => 22, 'lot_id' => 1],
    ]);
    DB::table('package_status')->insert([
        ['delivery_id' => 1, 'package_id' => 21, 'status' => 'delivered'],
        ['delivery_id' => 1, 'package_id' => 22, 'status' => 'delivered'],
    ]);

    $response = $this->getJson('/deliveries/tracking?'.http_build_query([
        'project' => 1,
        'search' => 'DR-A-01',
        'phase' => 'records',
    ]));

    $response->assertOk();
    expect($response->json('html'))->toContain('DR #DR-A-01', 'LOGISTICS DELIVERED')
        ->not->toContain('PENDING LOGISTICS');
});

it('filters logistics status by the exact project school and delivery reference', function (string $status, array $expectedReferences, string $expectedBadge): void {
    Schema::create('logistics_integrations', function (Blueprint $table): void {
        $table->string('integration_id')->primary();
        $table->string('name');
    });
    Schema::create('logistics_dr_tracking', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('integration_id');
        $table->unsignedInteger('project_id');
        $table->string('school_id');
        $table->string('dr_reference');
        $table->string('logistics_status');
        $table->dateTime('event_occurred_at');
        $table->string('reconciliation_status');
        $table->unsignedInteger('latest_sequence');
    });
    foreach ([
        [1, 'S-1', 'DR-A-01', 'logistics_received'],
        [1, 'S-1', 'DR-A-02', 'delivered'],
        [2, 'S-1', 'DR-A-03', 'logistics_received'],
        [1, 'S-2', 'DR-A-04', 'logistics_received'],
        [1, 'S-1', 'dr-a-05', 'logistics_received'],
    ] as [$projectId, $schoolId, $reference, $trackingStatus]) {
        DB::table('logistics_dr_tracking')->insert([
            'integration_id' => 'test-partner',
            'project_id' => $projectId,
            'school_id' => $schoolId,
            'dr_reference' => $reference,
            'logistics_status' => $trackingStatus,
            'event_occurred_at' => '2026-03-01 12:00:00',
            'reconciliation_status' => 'synchronized',
            'latest_sequence' => 1,
        ]);
    }

    $references = [];
    View::composer('deliveries.index', function (BladeView $view) use (&$references): void {
        $references = array_column($view->getData()['grouped_deliveries'], 'dr_no');
    });

    $response = $this->getJson('/deliveries/tracking?'.http_build_query([
        'project' => 1,
        'logistics_status' => $status,
        'per_page' => 20,
        'phase' => 'records',
    ]));

    $response->assertOk();
    expect($references)->toBe($expectedReferences);
    expect($response->json('html'))->toContain($expectedBadge);

    $book = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), [
        'project' => 1, 'logistics_status' => $status, 'scope' => 'filtered', 'report_type' => 'dr-summary',
    ]));
    $exportedReferences = array_slice(array_column($book->getSheetByName('DR Summary')->toArray(), 0), 1);
    expect($exportedReferences)->toBe($expectedReferences);
})->with([
    'received' => ['logistics_received', ['DR-A-01'], 'LOGISTICS RECEIVED'],
    'delivered' => ['delivered', ['DR-A-02'], 'LOGISTICS DELIVERED'],
    'pending' => ['pending', ['DR-A-03', 'DR-A-04', 'DR-A-05', 'DR-A-06', 'DR-A-07', 'DR-A-08', 'DR-A-09', 'DR-A-10', 'DR-A-11'], 'PENDING LOGISTICS'],
]);

function readDeliveryTrackingWorkbook(TestResponse $response): Spreadsheet
{
    $response->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $path = tempnam(sys_get_temp_dir(), 'delivery-report-');

    try {
        file_put_contents($path, $response->streamedContent());

        return IOFactory::createReader('Xlsx')->load($path);
    } finally {
        unlink($path);
    }
}

it('exports every filtered DR independent of pagination and groups by exact school and DR', function (): void {
    DB::table('deliveries')->where('delivery_id', 11)->update(['school_id' => 'S-2', 'dr_no' => 'DR-A-01']);
    DB::table('deliveries')->insert([
        ['delivery_id' => 14, 'project_id' => 1, 'school_id' => 'S-1', 'lot_id' => 1, 'dr_no' => 'DR-A-01', 'delivery_date' => '2026-03-01', 'status' => 'pending'],
        ['delivery_id' => 15, 'project_id' => 1, 'school_id' => 'S-1', 'lot_id' => 1, 'dr_no' => 'DR-A-15', 'delivery_date' => '2026-03-01', 'status' => 'delivered'],
        ['delivery_id' => 16, 'project_id' => 1, 'school_id' => 'S-1', 'lot_id' => 1, 'dr_no' => 'Outside search', 'delivery_date' => '2026-03-01', 'status' => 'pending'],
        ['delivery_id' => 17, 'project_id' => 1, 'school_id' => 'S-1', 'lot_id' => 2, 'dr_no' => 'DR-A-17', 'delivery_date' => '2026-03-01', 'status' => 'pending'],
    ]);
    $this->travelTo(now()->setDate(2026, 3, 10)->setTime(8, 9, 10));

    $response = $this->post(route('deliveries.tracking.export', ['page' => 2, 'per_page' => 10]), [
        'project' => 1, 'lot' => 1, 'search' => 'DR-A', 'status' => 'pending',
        'scope' => 'filtered', 'report_type' => 'complete',
    ]);

    $response->assertDownload('Delivery_Report_complete_2026-school-nutrition-and-learning-materials-distribution-program-northern-division_20260310_080910.xlsx');
    $book = readDeliveryTrackingWorkbook($response);
    expect($book->getSheetNames())->toBe(['DR Summary', 'School Summary', 'Lot Summary', 'Logistics Assignment Summary', 'Item Details', 'Applied Filters']);
    $drSheet = $book->getSheetByName('DR Summary');
    expect($drSheet->getHighestDataRow())->toBe(12);
    expect($drSheet->getCell('O2')->getValue())->toBe(2);
    expect($drSheet->getCell('K2')->getFormattedValue())->toBe('01/03/2026');
    expect($drSheet->getFreezePane())->toBe('A2');
    expect($drSheet->getAutoFilter()->getRange())->toBe('A1:R12');
    $schoolSheet = $book->getSheetByName('School Summary');
    expect($schoolSheet->rangeToArray('H2:H3', formatData: false))->toBe([[10], [1]]);
    expect($schoolSheet->rangeToArray('Q2:Q3'))->toBe([['Lot A'], ['Lot A']]);
    $filters = array_column($book->getSheetByName('Applied Filters')->toArray(formatData: false), 1, 0);
    expect($filters)->toMatchArray(['Filtered DRs' => 11, 'Filtered Schools' => 2, 'Filtered Delivered DRs' => 0, 'Unassigned DRs' => 11]);
});

it('limits selected scope to checked delivery IDs that match the applied filters', function (): void {
    DB::table('deliveries')->where('delivery_id', 2)->update(['dr_no' => 'DR-A-01', 'package_qty' => 4]);
    DB::table('package')->insert(['package_id' => 21, 'lot_id' => 1]);
    DB::table('package_content')->insert(['package_id' => 21, 'item_id' => 31, 'qty' => 3]);
    DB::table('item')->insert(['item_id' => 31, 'item_name' => '=1+1']);

    $book = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), [
        'project' => 1, 'search' => 'DR-A-01', 'scope' => 'selected', 'report_type' => 'item-details',
        'delivery_ids' => [2, 3, 12, 2],
    ]));

    $items = $book->getSheetByName('Item Details');
    expect($items->getHighestDataRow())->toBe(2);
    expect($items->getCell('B2')->getValue())->toBe(2);
    expect($items->getCell('Q2')->getValue())->toBe(12);
    expect($items->getCell('N2')->getDataType())->toBe('s');
    expect($items->getCell('N2')->getValue())->toBe('=1+1');
});

function assignDeliveryTrackingProviders(): void
{
    DB::table('logistics')->insert([
        ['logistic_id' => 7, 'logistic_name' => 'Shared Provider'],
        ['logistic_id' => 8, 'logistic_name' => 'Shared Provider'],
        ['logistic_id' => 9, 'logistic_name' => 'Provider Without Location'],
    ]);
    DB::table('logistics_location')->insert([
        ['logistics_location_id' => 71, 'logistics_id' => 7],
        ['logistics_location_id' => 72, 'logistics_id' => 7],
        ['logistics_location_id' => 81, 'logistics_id' => 8],
    ]);
    DB::table('deliveries')->where('delivery_id', 1)->update(['logistics_location_id' => 71, 'status' => 'delivered']);
    DB::table('deliveries')->where('delivery_id', 2)->update(['logistics_location_id' => 72, 'dr_no' => 'DR-A-01', 'status' => 'accepted']);
    DB::table('deliveries')->where('delivery_id', 3)->update(['logistics_location_id' => 81]);
}

it('uses the assignment relationship for both the tracking page and the export', function (string $provider, string $visible, string $excluded, int $count): void {
    assignDeliveryTrackingProviders();

    $tracking = $this->getJson(route('deliveries.tracking', [
        'project' => 1, 'assigned_logistics' => $provider, 'phase' => 'records',
    ]));
    $book = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), [
        'project' => 1, 'assigned_logistics' => $provider, 'scope' => 'filtered', 'report_type' => 'dr-summary',
    ]));

    expect($tracking->json('html'))->toContain('DR #'.$visible)->not->toContain('DR #'.$excluded, 'Provider Without Location');
    $receipts = array_column($book->getSheetByName('DR Summary')->toArray(), 0);
    expect($receipts)->toHaveCount($count + 1)->toContain($visible)->not->toContain($excluded);
})->with([
    'assigned' => ['7', 'DR-A-01', 'DR-A-03', 1],
    'unassigned' => ['unassigned', 'DR-A-04', 'DR-A-01', 8],
]);

it('groups provider locations by provider ID and counts completed receipts once', function (): void {
    assignDeliveryTrackingProviders();

    $book = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), [
        'project' => 1, 'scope' => 'filtered', 'report_type' => 'assigned-logistics',
    ]));

    expect($book->getSheetByName('Logistics Assignment Summary')->rangeToArray('A2:F4', formatData: false))->toBe([
        ['Shared Provider', 7, 1, 1, 1, 0],
        ['Shared Provider', 8, 1, 1, 0, 1],
        ['Unassigned', null, 8, 1, 0, 8],
    ]);
    $filters = array_column($book->getSheetByName('Applied Filters')->toArray(formatData: false), 1, 0);
    expect($filters)->toMatchArray(['Assigned DRs' => 2, 'Unassigned DRs' => 8, 'Filtered Delivered DRs' => 1]);
});

it('lists each school lot once and excludes lots outside the export scope', function (): void {
    DB::table('lot')->insert(['lot_id' => 3, 'project_id' => 1, 'lot_name' => 'Lot C']);
    DB::table('deliveries')->where('delivery_id', 3)->update(['lot_id' => 3]);

    $payload = ['project' => 1, 'report_type' => 'school-summary'];
    $all = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), $payload + ['scope' => 'filtered']));
    $selected = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), $payload + [
        'scope' => 'selected', 'delivery_ids' => [3],
    ]));

    expect($all->getSheetByName('School Summary')->rangeToArray('Q1:Q2'))->toBe([['Lots'], ['Lot A, Lot C']]);
    expect($selected->getSheetByName('School Summary')->getCell('Q2')->getValue())->toBe('Lot C');
});

it('groups receipts by lot ID and honors the lot filter', function (): void {
    DB::table('lot')->insert(['lot_id' => 3, 'project_id' => 1, 'lot_name' => 'Lot A']);
    DB::table('deliveries')->whereIn('delivery_id', [2, 3])->update(['dr_no' => 'DR-A-01', 'status' => 'delivered']);
    DB::table('deliveries')->where('delivery_id', 3)->update(['lot_id' => 3]);

    $payload = ['project' => 1, 'report_type' => 'lot-summary', 'scope' => 'filtered'];
    $all = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), $payload));
    $filtered = readDeliveryTrackingWorkbook($this->post(route('deliveries.tracking.export'), $payload + ['lot' => 3]));

    expect($all->getSheetByName('Lot Summary')->rangeToArray('A2:G3', formatData: false))->toBe([
        [1, 'Lot A', '2026 School Nutrition and Learning Materials Distribution Program — Northern Division', 1, 9, 0, 9],
        [3, 'Lot A', '2026 School Nutrition and Learning Materials Distribution Program — Northern Division', 1, 1, 1, 0],
    ]);
    $lotSheet = $filtered->getSheetByName('Lot Summary');
    expect($lotSheet->getHighestDataRow())->toBe(2);
    expect($lotSheet->getCell('A2')->getValue())->toBe(3);
});

it('requires authentication to export delivery tracking', function (): void {
    auth()->logout();

    $this->postJson(route('deliveries.tracking.export'))->assertUnauthorized();
});

it('forbids logistics provider accounts without the tracking role from exporting', function (): void {
    $provider = Mockery::mock(User::class)->makePartial();
    $provider->forceFill(['user_id' => 2, 'name' => 'Provider Account', 'email' => 'provider@example.test']);
    $provider->shouldReceive('currentCompany')->andReturn(new Company(['code' => 'MMC', 'is_active' => true]));
    $provider->shouldReceive('hasAnyRole')->with(['user'])->andReturn(false);

    $this->actingAs($provider)->postJson(route('deliveries.tracking.export'), [
        'project' => 1, 'scope' => 'filtered', 'report_type' => 'complete',
    ])->assertForbidden();
});

it('requires a checked delivery ID for selected-only exports', function (): void {
    $this->postJson(route('deliveries.tracking.export'), [
        'project' => 1, 'scope' => 'selected', 'report_type' => 'dr-summary',
    ])->assertUnprocessable()->assertJsonValidationErrors('delivery_ids');
});

it('forbids export outside the MMC company context', function (): void {
    $dashboardService = Mockery::mock(DashboardService::class);
    $dashboardService->shouldReceive('currentCompany')->andReturn(new Company(['code' => 'MI', 'is_active' => true]));
    $this->app->instance(DashboardService::class, $dashboardService);

    $this->postJson(route('deliveries.tracking.export'), [
        'project' => 1, 'scope' => 'filtered', 'report_type' => 'complete',
    ])->assertForbidden();
});
