<?php

namespace Tests;

use App\Models\Company;
use App\Models\New\Item;
use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use App\Models\ProjectLot;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Spatie\Permission\Models\Role;

class BiddingTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $application = require Application::inferBasePath().'/bootstrap/app.php';
        $application->singleton(Kernel::class, BiddingConsoleKernel::class);
        $application->make(Kernel::class)->bootstrap();
        $application['config']->set('database.default', 'sqlite');
        $application['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $application->make('db')->purge('sqlite');
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];

        return $application;
    }

    protected function migrateFreshUsing(): array
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Bidding tests require an isolated SQLite in-memory database.');
        }

        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_06_15_081615_create_permission_tables.php',
            'database/migrations/2026_06_26_094046_create_project_information_table.php',
            'database/migrations/2026_06_26_095001_create_lots_table.php',
            'database/migrations/2026_06_26_161100_create_project_items_table.php',
            'database/migrations/2026_06_27_031110_create_psgc_table.php',
            'database/migrations/2026_06_29_055633_create_items_table.php',
            'database/migrations/2026_07_14_082554_add_project_code_to_project_information.php',
            'database/migrations/2026_07_14_083101_add_table_to_project__information.php',
            'database/migrations/2026_07_16_070116_create_delivery_address_table.php',
            'database/migrations/2026_07_16_072602_add_columns_to_keystages_table.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
            'database/migrations/2026_10_04_053818_align_bidding_hierarchy.php',
            'database/migrations/2026_10_04_101906_create_bidding_document_management_tables.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }

    public function signInBiddingUser(string $role = 'user', string $companyCode = 'MMC'): User
    {
        $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $role]);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        $company = Company::query()->firstOrCreate(['code' => $companyCode], ['name' => $companyCode, 'is_active' => true]);
        $user->companies()->attach($company);
        $this->actingAs($user)->withSession(['company_id' => $company->company_id]);

        return $user;
    }

    /** @return array<string, mixed> */
    public function validBiddingPayload(): array
    {
        $catalogItem = BiddingCatalogFixtureFactory::new()->create();

        return [
            'project_code' => 'SME',
            'project_id' => fake()->unique()->bothify('BID-########'),
            'project_name' => 'Bidding workflow fixture',
            'procuring_entity' => 'Fixture Agency',
            'approved_budget_contract_abc' => '1000.00',
            'delivery_period' => 30,
            'date_of_pre_bid_conference' => '2026-10-01',
            'date_of_bid_opening' => '2026-10-04',
            'prepared_by' => 'Fixture Preparer',
            'prepared_date' => '2026-09-30',
            'verified_by' => 'Fixture Verifier',
            'notes_special_condition' => 'Preserve fixture metadata',
            'status' => 'Draft',
            'lots' => [[
                'lot_no' => 'Lot 1',
                'country_code' => 'PH',
                'addresses' => [[
                    'delivery_address' => 'Fixture delivery address',
                    'keystages' => [[
                        'name' => 'Fixture key stage',
                        'items' => [[
                            'catalog_item_id' => $catalogItem->id,
                            'item_description' => $catalogItem->description,
                            'unit' => 'pcs',
                            'quantity' => '2.50',
                            'unit_cost' => '10.20',
                            'total_amount' => '999.99',
                            'remarks' => 'Fixture item remarks',
                        ]],
                    ]],
                ]],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    public function biddingHierarchyPayload(ProjectInformation $project): array
    {
        $project->load('lots.addresses.keystages.items');

        return ['lots' => $project->lots->map(fn (ProjectLot $lot): array => [
            'id' => $lot->id,
            'lot_no' => $lot->lot_no,
            'addresses' => $lot->addresses->map(fn ($address): array => [
                'id' => $address->id,
                'delivery_address' => $address->delivery_address,
                'keystages' => $address->keystages->map(fn ($stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'items' => $stage->items->map(fn (ProjectItem $item): array => [
                        'id' => $item->id,
                        'catalog_item_id' => $item->catalog_item_id,
                        'item_description' => $item->item_description,
                        'unit' => $item->unit,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'total_amount' => $item->total_amount,
                        'remarks' => $item->remarks,
                    ])->all(),
                ])->all(),
            ])->all(),
        ])->all()];
    }
}

class BiddingConsoleKernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

/** @extends Factory<ProjectInformation> */
class BiddingProjectFixtureFactory extends Factory
{
    protected $model = ProjectInformation::class;

    public function definition(): array
    {
        return [
            'project_id' => fake()->unique()->bothify('BID-########'),
            'project_code' => 'SME',
            'project_name' => fake()->sentence(3),
            'approved_budget_contract_abc' => '500.00',
            'status' => 'Draft',
        ];
    }
}

/** @extends Factory<ProjectLot> */
class BiddingLotFixtureFactory extends Factory
{
    protected $model = ProjectLot::class;

    public function definition(): array
    {
        return ['project_id' => BiddingProjectFixtureFactory::new(), 'lot_no' => 'Lot 1', 'country' => 'Philippines'];
    }
}

/** @extends Factory<ProjectItem> */
class BiddingItemFixtureFactory extends Factory
{
    protected $model = ProjectItem::class;

    public function definition(): array
    {
        return [
            'lot_id' => BiddingLotFixtureFactory::new(),
            'item_no' => 1,
            'item_description' => 'Fixture desk',
            'unit' => 'pcs',
            'quantity' => '2.50',
            'unit_cost' => '10.20',
            'total_amount' => '25.50',
        ];
    }
}

/** @extends Factory<Item> */
class BiddingCatalogFixtureFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'code_prefix' => 'TEST',
            'item_name' => 'Fixture catalog desk',
            'description' => 'Fixture catalog description',
            'unit' => 'pcs',
            'price' => '10.20',
            'supplier_price' => '8.00',
            'active' => true,
        ];
    }
}
