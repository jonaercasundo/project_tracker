<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckCompany;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use App\Services\DuplicateDrCleanupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\BillingWorkflowTestCase;

class DuplicateDrCleanupTest extends BillingWorkflowTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('admin_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('event');
            $table->string('subject_type');
            $table->string('subject_id')->nullable();
            $table->text('summary');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        DB::table('projects')->insert(['project_name' => 'Project A', 'project_code' => 'A', 'status' => 'Active']);
        DB::table('school')->insert(['school_id' => 'S1', 'school_name' => 'School One']);
        DB::table('school')->insert(['school_id' => 'S2', 'school_name' => 'School Two']);
        DB::table('lot')->insert(['lot_name' => '1', 'project_id' => 1, 'contract_no' => 'C1']);
    }

    public function test_it_groups_by_school_and_lot_and_only_marks_selected_duplicate_delivery_rows_cancelled(): void
    {
        DB::table('deliveries')->insert([
            ['delivery_id' => 1, 'project_id' => 1, 'school_id' => 'S1', 'lot_id' => 1, 'dr_no' => 'DR-10', 'delivery_date' => '2026-10-01', 'status' => 'Pending'],
            ['delivery_id' => 2, 'project_id' => 1, 'school_id' => 'S1', 'lot_id' => 1, 'dr_no' => ' dr-10 ', 'delivery_date' => '2026-10-01', 'status' => 'Pending'],
            ['delivery_id' => 3, 'project_id' => 1, 'school_id' => 'S2', 'lot_id' => 1, 'dr_no' => 'DR-10', 'delivery_date' => '2026-10-03', 'status' => 'Delivered'],
        ]);
        DB::table('package')->insert(['package_id' => 1, 'lot_id' => 1, 'package_code' => 'P1']);
        DB::table('package_status')->insert([
            ['delivery_id' => 1, 'package_id' => 1, 'status' => 'Pending'],
            ['delivery_id' => 2, 'package_id' => 1, 'status' => 'Pending'],
        ]);
        DB::table('delivery_proofs')->insert(['package_status_id' => 1, 'photo' => 'proof.jpg']);
        $actorId = DB::table('users')->insertGetId([
            'name' => 'Reviewer', 'username' => 'reviewer', 'password' => bcrypt('secret'), 'employee_id' => 'E1',
            'department' => 'Operations', 'position' => 'Manager', 'created_at' => now(), 'updated_at' => now(),
        ], 'user_id');

        $service = app(DuplicateDrCleanupService::class);
        $groups = $service->groups(1, []);

        $this->assertCount(1, $groups);
        $this->assertSame([1, 2, 3], $groups->first()->records->pluck('delivery_id')->map(fn ($id): int => (int) $id)->all());
        $this->assertSame([1, 2], $groups->first()->exactDuplicateSets->first()->records->pluck('delivery_id')->map(fn ($id): int => (int) $id)->all());
        $this->assertSame(['groups' => 1, 'records' => 3], $service->summary(1, ['status' => 'Pending']));
        $this->assertSame(['groups' => 1, 'records' => 3], $service->summary(1, ['search' => 'School Two']));
        $service->resolve(1, User::findOrFail($actorId), 1, [2]);

        $this->assertSame('Pending', DB::table('deliveries')->where('delivery_id', 1)->value('status'));
        $this->assertSame('cancelled', DB::table('deliveries')->where('delivery_id', 2)->value('status'));
        $this->assertSame('Delivered', DB::table('deliveries')->where('delivery_id', 3)->value('status'));
        $this->assertDatabaseHas('package_status', ['delivery_id' => 2, 'status' => 'Pending']);
        $this->assertDatabaseHas('delivery_proofs', ['id' => 1, 'photo' => 'proof.jpg']);
        $this->assertDatabaseHas('admin_activity_logs', ['actor_user_id' => $actorId, 'event' => 'duplicate_dr_cleanup']);
    }

    public function test_it_blocks_cleanup_when_billing_depends_on_the_duplicate_group(): void
    {
        DB::table('deliveries')->insert([
            ['delivery_id' => 11, 'project_id' => 1, 'school_id' => 'S1', 'lot_id' => 1, 'dr_no' => 'DR-20', 'delivery_date' => '2026-10-02', 'status' => 'Pending'],
            ['delivery_id' => 12, 'project_id' => 1, 'school_id' => 'S1', 'lot_id' => 1, 'dr_no' => 'DR-20', 'delivery_date' => '2026-10-02', 'status' => 'Pending'],
        ]);
        DB::table('delivery_billing_records')->insert(['delivery_id' => 12, 'status' => 'pending_billing']);
        $actorId = DB::table('users')->insertGetId([
            'name' => 'Reviewer', 'username' => 'reviewer2', 'password' => bcrypt('secret'), 'employee_id' => 'E2',
            'department' => 'Operations', 'position' => 'Manager', 'created_at' => now(), 'updated_at' => now(),
        ], 'user_id');

        $this->expectException(ValidationException::class);
        app(DuplicateDrCleanupService::class)->resolve(1, User::findOrFail($actorId), 11, [12]);
    }

    public function test_authorized_company_user_can_load_the_cleanup_page(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Metro Mobilia', 'code' => 'MMC', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ], 'company_id');
        $userId = DB::table('users')->insertGetId([
            'name' => 'Reviewer', 'username' => 'page-reviewer', 'password' => bcrypt('secret'), 'employee_id' => 'E3',
            'department' => 'Operations', 'position' => 'Manager', 'created_at' => now(), 'updated_at' => now(),
        ], 'user_id');
        DB::table('company_user')->insert(['company_id' => $companyId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);

        $this->withoutMiddleware([CheckCompany::class, EnsureActiveUser::class, RoleMiddleware::class])
            ->withoutVite()
            ->actingAs(User::findOrFail($userId))
            ->withSession(['company_id' => $companyId]);
        $response = $this->get(route('deliveries.duplicate-cleanup'));

        $response->assertOk()->assertSee('Duplicate DR Cleanup')->assertSee('0')->assertSee('No possible duplicate DR-number groups');
    }
}
