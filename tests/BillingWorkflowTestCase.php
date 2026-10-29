<?php

namespace Tests;

use Database\Seeders\BillingPermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BillingWorkflowTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createSchema();

        (new BillingPermissionSeeder)->run();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('employee_id');
            $table->string('department');
            $table->string('position');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table): void {
            $table->increments('company_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_user', function (Blueprint $table): void {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');
            $table->timestamps();
            $table->primary(['user_id', 'company_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_name');
            $table->string('project_code')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('lot', function (Blueprint $table): void {
            $table->increments('lot_id');
            $table->string('lot_name', 100);
            $table->integer('project_id');
            $table->string('contract_no');
        });

        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->string('school_name');
            $table->string('division')->default('North Division');
        });

        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->integer('project_id');
            $table->string('school_id');
            $table->integer('lot_id')->nullable();
            $table->integer('keystage_id')->nullable();
            $table->integer('package_qty')->default(1);
            $table->string('dr_no');
            $table->date('delivery_date');
            $table->string('status');
        });

        Schema::create('package', function (Blueprint $table): void {
            $table->increments('package_id');
            $table->integer('lot_id');
            $table->integer('keystage_id')->nullable();
            $table->integer('package_num')->nullable();
        });

        Schema::create('item', function (Blueprint $table): void {
            $table->increments('item_id');
            $table->decimal('price', 15, 2)->nullable();
        });

        Schema::create('package_content', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('package_id');
            $table->integer('item_id');
            $table->decimal('qty', 15, 2)->default(1);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->increments('invoice_id');
            $table->integer('project_id');
            $table->string('invoice_no', 100);
            $table->decimal('amount', 15, 2);
            $table->date('invoice_date');
            $table->string('status')->default('Pending');
            $table->date('payment_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('project_id');
        });

        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->integer('delivery_id');
            $table->integer('package_id');
            $table->string('status');
        });

        Schema::create('delivery_proofs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('package_status_id');
            $table->string('photo');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        (require database_path('migrations/2026_10_13_030108_add_project_invoice_number_unique_index.php'))->up();
        (require database_path('migrations/2026_10_13_030107_create_delivery_billing_tables.php'))->up();
        (require database_path('migrations/2026_10_23_061316_create_billing_batches_table.php'))->up();
        (require database_path('migrations/2026_10_28_031729_add_confirmation_date_to_billing_batches_table.php'))->up();
        (require database_path('migrations/2026_10_29_060921_add_google_sheet_fields_to_billing_batches_table.php'))->up();
        (require database_path('migrations/2026_10_27_051316_create_billing_sheet_syncs_table.php'))->up();
        (require database_path('migrations/2026_10_29_054612_add_billing_sheet_sync_result_details.php'))->up();
        (require database_path('migrations/2026_10_29_073347_create_billing_spreadsheet_sync_audits_table.php'))->up();
        (require database_path('migrations/2026_10_27_052646_create_google_sheets_settings_table.php'))->up();
    }
}
