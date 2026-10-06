<?php

namespace Tests;

use App\Models\BudgetRequest;
use App\Models\Company;
use App\Models\Liquidation;
use App\Models\MI_Liquidation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MIWorkflowTestCase extends TestCase
{
    use RefreshDatabase;

    protected static ?string $mysqlReadyToken = null;

    protected static array $createdMysqlDatabases = [];

    public function createApplication(): Application
    {
        $application = require Application::inferBasePath().'/bootstrap/app.php';
        $application->singleton(Kernel::class, MIWorkflowConsoleKernel::class);
        $application->make(Kernel::class)->bootstrap();
        $application['mi_test_mysql_config'] = $application['config']->get('database.connections.mysql');
        $application['config']->set('database.default', 'sqlite');
        $application['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'url' => null, 'database' => ':memory:',
            'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        $application->make('db')->purge('sqlite');
        $token = getenv('MI_TEST_MYSQL_TOKEN');
        if ($token !== false && $token !== '') {
            if (! $application->environment('testing')) {
                throw new \RuntimeException('MySQL schema tests require the testing application environment.');
            }
            if (! preg_match('/^[a-f0-9]{24}$/D', $token)) {
                throw new \RuntimeException('Invalid isolated MySQL test token.');
            }
            $mysql = $application['mi_test_mysql_config'];
            $target = 'mi_workflow_test_'.$token;
            if (! in_array($mysql['host'], ['localhost', '127.0.0.1', '::1'], true) || $target === $mysql['database']) {
                throw new \RuntimeException('MySQL tests require a distinct local test database.');
            }
            $admin = array_replace($mysql, ['database' => '', 'url' => null]);
            $application['config']->set('database.connections.mi_test_admin', $admin);
            $connection = $application->make('db')->connection('mi_test_admin');
            $existing = $connection->selectOne('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$target]);
            if ($existing && ! isset(static::$createdMysqlDatabases[$target])) {
                throw new \RuntimeException('Refusing to reuse a database not created by this test process.');
            }
            if (! $existing) {
                $connection->statement('CREATE DATABASE '.$target.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                static::$createdMysqlDatabases[$target] = true;
                $adminPdo = $connection->getPdo();
                register_shutdown_function(function () use ($adminPdo, $target): void {
                    $adminPdo->exec('DROP DATABASE '.$target);
                });
            }
            $application['config']->set('database.connections.mi_test_mysql', array_replace($mysql, ['database' => $target, 'url' => null, 'strict' => true]));
            $application['config']->set('database.default', 'mi_test_mysql');
            $application->make('db')->purge('mi_test_mysql');
        }
        RefreshDatabaseState::$migrated = $application['config']->get('database.default') === 'mi_test_mysql' && static::$mysqlReadyToken === $token;
        RefreshDatabaseState::$inMemoryConnections = [];

        return $application;
    }

    protected function migrateFreshUsing(): array
    {
        $isolatedSqlite = config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';
        $token = getenv('MI_TEST_MYSQL_TOKEN');
        $isolatedMysql = is_string($token) && preg_match('/^[a-f0-9]{24}$/D', $token)
            && config('database.default') === 'mi_test_mysql'
            && config('database.connections.mi_test_mysql.database') === 'mi_workflow_test_'.$token
            && config('database.connections.mi_test_mysql.database') !== $this->app['mi_test_mysql_config']['database']
            && isset(static::$createdMysqlDatabases['mi_workflow_test_'.$token]);
        if (! $isolatedSqlite && ! $isolatedMysql) {
            throw new \RuntimeException('MI workflow tests require verified in-memory SQLite or a distinct token-scoped local MySQL database.');
        }

        if ($isolatedMysql && DB::selectOne('SELECT DATABASE() AS selected_database')->selected_database !== 'mi_workflow_test_'.$token) {
            throw new \RuntimeException('Actual SQL database differs from the verified test database.');
        }

        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_06_15_081615_create_permission_tables.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
            'database/migrations/2026_09_02_093217_create_liquidations_table.php',
            'database/migrations/2026_09_04_075822_change_requested_by_to_string_on_mi_liquidation_items_table.php',
            'database/migrations/2026_09_18_091109_create_budget_requests_table.php',
            'database/migrations/2026_09_18_091132_create_budget_requests_items_table.php',
            'database/migrations/2026_09_18_091159_create_liquidations_table.php',
            'database/migrations/2026_09_18_091209_create_liquidations_items_table.php',
            'database/migrations/2026_10_06_031105_add_mi_financial_attribution_and_audit_tables.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        if (config('database.default') === 'mi_test_mysql') {
            static::$mysqlReadyToken = getenv('MI_TEST_MYSQL_TOKEN');
        }
    }

    protected function tearDown(): void
    {
        $isMysql = config('database.default') === 'mi_test_mysql';
        parent::tearDown();
        if (! $isMysql) {
            RefreshDatabaseState::$migrated = false;
        }
        RefreshDatabaseState::$inMemoryConnections = [];
    }

    public function miUser(string $role = 'user', string $companyCode = 'MI'): User
    {
        $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $role]);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        if ($role === 'accounting') {
            foreach (['mi.accounting.dashboard.view', 'mi.budget.view', 'mi.budget.review', 'mi.budget.release', 'mi.liquidation.view', 'mi.liquidation.review', 'mi.settlement.view', 'mi.financial-reports.view', 'mi.audit.view'] as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $company = Company::firstOrCreate(['code' => $companyCode], ['name' => $companyCode, 'is_active' => true]);
        $user->companies()->attach($company);

        return $user;
    }

    public function signInMI(User $user): void
    {
        $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->getKey()]);
    }

    /** @return array<string, mixed> */
    public function budgetPayload(): array
    {
        return ['department' => 'Design', 'items' => [[
            'expense_category' => 'Travel', 'particular' => 'Fixture travel',
            'budget_cash' => '0.10', 'budget_credit_card' => '0.20', 'budget_travel_agent' => '0.03',
        ]]];
    }

    /** @return array<string, mixed> */
    public function travelPayload(BudgetRequest $budget): array
    {
        return ['budget_request_id' => $budget->getKey(), 'items' => [[
            'expense_category' => 'Travel', 'particular' => 'Fixture actual expense',
            'actual_cash' => '0.10', 'actual_credit_card' => '0.20', 'actual_travel_agent' => '0.03',
            'receipt_attached' => 'yes',
        ]]];
    }

    /** @return array<string, mixed> */
    public function ordinaryPayload(): array
    {
        return ['report_title' => 'Fixture ordinary liquidation', 'date_prepared' => '2026-10-06',
            'exchange_rate' => '25000.0000', 'pcf_amount' => '500.00', 'items' => [[
                'item_date' => '2026-10-06', 'requested_by' => 'Fixture requester', 'payee' => 'Fixture payee',
                'expense_type' => 'Travel', 'account_buyer' => 'Fixture buyer', 'amount_vnd' => '125.50',
            ]]];
    }
}

class MIWorkflowConsoleKernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

/** @extends Factory<BudgetRequest> */
class MIBudgetFixtureFactory extends Factory
{
    protected $model = BudgetRequest::class;

    public function definition(): array
    {
        return ['department' => 'Design', 'status' => 'budget_requested', 'company_id' => Company::where('code', 'MI')->value('company_id')];
    }
}

/** @extends Factory<Liquidation> */
class MITravelFixtureFactory extends Factory
{
    protected $model = Liquidation::class;

    public function definition(): array
    {
        return ['status' => 'submitted', 'company_id' => Company::where('code', 'MI')->value('company_id')];
    }
}

/** @extends Factory<MI_Liquidation> */
class MILiquidationFixtureFactory extends Factory
{
    protected $model = MI_Liquidation::class;

    public function definition(): array
    {
        return ['title' => 'Fixture ordinary liquidation', 'date_prepared' => '2026-10-06',
            'exchange_rate' => '25000.0000', 'pcf_amount' => '500.00', 'status' => 'Pending'];
    }
}
